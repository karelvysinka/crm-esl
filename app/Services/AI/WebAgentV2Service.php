<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use App\Services\Tools\PlaywrightTool;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebAgentV2Service
 * Clean V2 web agent with LLM planning, Playwright execution, and LLM composition.
 * Key properties:
 * - No URL blocking at agent level (always allow all; external controls handle restrictions)
 * - Parallel fetch of all explicit URLs and planned targets
 * - Detailed SSE progress: planner → playwright → llm
 */
class WebAgentV2Service
{
    /**
     * Stream end-to-end V2 web agent flow.
     */
    public function stream(string $provider, string $userText, int $messageId, int $assistantMessageId, int $sessionId, array $meta = [], ?string $baseUrl = null): StreamedResponse
    {
        $timeoutMs = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
        return new StreamedResponse(function () use ($provider, $userText, $messageId, $assistantMessageId, $sessionId, $meta, $baseUrl, $timeoutMs) {
            // Planner: ask LLM for steps; also include explicit URLs from user text
            $plannerPrompt = "Jsi webový plánovač. Z dotazu uživatele a (volitelné) základní URL navrhni až 6 kroků.\n".
                             "Každý krok je JSON objekt s poli: id, label, url (absolutní nebo relativní k base), read_mode (readability|textContent|innerText), full (true/false), scroll (true/false), parallel_group (číslo).\n".
                             "POVINNĚ: Pokud jsou v zadání výslovně uvedeny absolutní URL, vytvoř krok pro každou z nich (url ponech absolutní).\n".
                             "Tipy: pro články preferuj read_mode=readability, pro homepage textContent; nastav full=true, scroll=true.";
            $userUrls = [];
            if (preg_match_all('#https?://[^\s]+#iu', $userText, $mAll)) { $userUrls = array_values(array_unique($mAll[0])); }
            $promptUser = "Base URL: ".($baseUrl ?: '(none)')."\nPožadavek: ".$userText;
            if (!empty($userUrls)) { $promptUser .= "\nExplicitní URL: ".implode(', ', $userUrls); }
            $planMessages = [ ['role'=>'system','content'=>$plannerPrompt], ['role'=>'user','content'=>$promptUser] ];

            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'start','label'=>'Plánuji kroky (LLM)'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();

            $steps = [];
            try {
                if ($provider === 'gemini' && SystemSetting::get('chat.gemini_enabled', '0') === '1') {
                    $key = (string) (SystemSetting::get('chat.gemini_api_key', env('GEMINI_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.gemini_model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
                    $resp = Http::withHeaders(['Content-Type'=>'application/json'])
                        ->post('https://generativelanguage.googleapis.com/v1beta/models/'.urlencode($model).':generateContent?key='.urlencode($key), [
                            'contents' => [ ['role'=>'user','parts'=>[['text'=> $plannerPrompt."\n\n".$promptUser]]] ],
                        ]);
                    $txt = trim((string)($resp->json()['candidates'][0]['content']['parts'][0]['text'] ?? ''));
                    $steps = json_decode($txt, true);
                } else {
                    $key = (string) (SystemSetting::get('chat.openrouter_api_key', env('OPENROUTER_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.openrouter_model', env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free')));
                    $resp = Http::withToken($key)->acceptJson()->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model'=>$model, 'stream'=>false, 'messages'=>$planMessages, 'max_tokens'=>500, 'temperature'=>0.1,
                    ]);
                    $txt = trim((string)($resp->json()['choices'][0]['message']['content'] ?? ''));
                    $steps = json_decode($txt, true);
                }
            } catch (\Throwable $e) { $steps = []; }
            if (!is_array($steps)) { $steps = []; }

            echo "event: meta\n"; echo 'data: ' . json_encode(array_merge($meta, ['diagnostics'=>array_merge($meta['diagnostics']??[], ['v2'=>'planned','steps'=>$steps])]), JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'end','label'=>'Plán hotov — kroky: '.count($steps)], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();

            // Executor: collect targets (planned + explicit), allow all URLs, fetch in parallel
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'start','label'=>'Načítám stránky'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            $tool = app(PlaywrightTool::class);
            $targets = [];
            foreach ($steps as $st) { $u = trim((string)($st['url'] ?? '')); if ($u !== '') { $targets[] = $u; } }
            if (!empty($userUrls)) { $targets = array_merge($targets, $userUrls); }
            $targets = array_values(array_unique($targets));

            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'progress','label'=>'Nalezené úkoly: '.count($targets)], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            foreach ($targets as $i => $u) {
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'progress','label'=>'Úkol '.($i+1).'/'.count($targets).': '.$u], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            }

            $jobs = [];
            foreach ($targets as $u) {
                if (!preg_match('#^https?://#i', $u)) {
                    if ($baseUrl) {
                        // Resolve relative to base
                        $u = rtrim($baseUrl, '/').'/'.ltrim($u, '/');
                    } else { continue; }
                }
                $readMode = preg_match('#/(clanek|clanky|article|news)/#i', (string)$u) ? 'readability' : 'textContent';
                $jobs[] = [
                    'url'=>$u,
                    'selectors'=>[],
                    // V2: always allow all domains; restrictions handled externally
                    'allowed_domains'=>['*'],
                    'timeout_ms'=>$timeoutMs,
                    'options'=>['wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>$readMode,'max_chars'=>600000],
                ];
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Načítám: '.$u], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            }

            $results = [];
            if (!empty($jobs)) {
                $batch = $tool->fetchMany($jobs, auth()->id(), $sessionId);
                foreach ($batch as $i => $br) {
                    $jobUrl = $jobs[$i]['url'];
                    $json = $br['json'] ?? null;
                    if (!$br['ok']) {
                        $results[] = [ 'url'=>$jobUrl, 'title'=>null, 'text'=>'', 'links'=>[], 'timings'=>$br['meta']['timings'] ?? null, 'error'=>'runner_error' ];
                        echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Chyba při načítání: '.$jobUrl], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                        continue;
                    }
                    $text = (string)($json['data']['text'] ?? '');
                    if (mb_strlen($text) < 300) {
                        // Second pass with innerText if too short
                        $r2 = $tool->fetch($jobUrl, [], ['*'], $timeoutMs, auth()->id(), $sessionId, [ 'wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>'innerText','max_chars'=>600000 ]);
                        if (($r2['ok'] ?? true) && mb_strlen((string)($r2['data']['text'] ?? '')) > mb_strlen($text)) {
                            $json = $r2; $text = (string)($r2['data']['text'] ?? '');
                        }
                    }
                    $linksArr = [];
                    if (isset($json['data']['links']) && is_array($json['data']['links'])) {
                        foreach ($json['data']['links'] as $lnk) { $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? '')); if ($href) { $linksArr[] = ['text' => $txt ?: $href, 'href' => $href]; } }
                    }
                    $title = $br['meta']['title'] ?? ($json['title'] ?? null);
                    $timings = $br['meta']['timings'] ?? ($json['timings'] ?? null);
                    $results[] = [ 'url'=>$jobUrl, 'title'=>$title, 'text'=>mb_substr($text, 0, 12000), 'links'=>$linksArr, 'timings'=>$timings ];
                    echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Hotovo: '.$jobUrl], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                }
            } else {
                // Fallback: if no jobs, try baseUrl or extract first URL from user text
                $fallback = $baseUrl; if (!$fallback && !empty($userUrls)) { $fallback = $userUrls[0]; }
                if ($fallback) {
                    $r = $tool->fetch($fallback, [], ['*'], $timeoutMs, auth()->id(), $sessionId, [ 'wait_until'=>'networkidle', 'auto_scroll'=>true, 'full_text'=>true, 'read_mode'=>'textContent', 'max_chars'=>400000 ]);
                    $text = (string)($r['data']['text'] ?? '');
                    $linksArr = [];
                    if (isset($r['data']['links']) && is_array($r['data']['links'])) {
                        foreach ($r['data']['links'] as $lnk) { $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? '')); if ($href) { $linksArr[] = ['text' => $txt ?: $href, 'href' => $href]; } }
                    }
                    $results[] = [ 'url'=>$fallback, 'title'=>($r['title'] ?? null), 'text'=>mb_substr($text,0,12000), 'links'=>$linksArr, 'timings'=>($r['timings'] ?? null) ];
                }
            }

            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'end','label'=>'Procházení dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();

            // Composer: aggregate results via LLM
            $webContext = [];
            foreach ($results as $res) {
                $webContext[] = [
                    'url' => $res['url'] ?? null,
                    'title' => $res['title'] ?? null,
                    'text' => mb_substr((string)($res['text'] ?? ''), 0, 12000),
                    'links' => array_slice(($res['links'] ?? []), 0, 200),
                    'timings' => $res['timings'] ?? null,
                ];
            }
            $webJson = json_encode(['pages' => $webContext], JSON_UNESCAPED_UNICODE);
            try { $dbg = $meta; $dbg['debug'] = $dbg['debug'] ?? []; $dbg['debug']['llm_v2'] = [ 'system' => 'web-summarizer-v2', 'context_json' => mb_substr($webJson,0,3000), 'user' => mb_substr($userText,0,1000) ]; echo "event: meta\n"; echo 'data: ' . json_encode($dbg, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush(); } catch (\Throwable $e) { }

            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'llm','status'=>'start','label'=>'Komponuji odpověď (LLM)'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            $final = '';
            try {
                if ($provider === 'gemini' && SystemSetting::get('chat.gemini_enabled', '0') === '1') {
                    $key = (string) (SystemSetting::get('chat.gemini_api_key', env('GEMINI_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.gemini_model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
                    $sys = "Jsi webový rešeršista a autor. Odpovídej česky, stručně a věcně. Použij výhradně poskytnuté webové texty (JSON).";
                    $content = [ ['role'=>'user','parts'=>[['text'=>$sys."\n\nWEB DATA (JSON):\n".$webJson."\n\nUživatel:\n".$userText]]] ];
                    $resp = Http::withHeaders(['Content-Type'=>'application/json'])
                        ->post('https://generativelanguage.googleapis.com/v1beta/models/'.urlencode($model).':generateContent?key='.urlencode($key), [ 'contents' => $content ]);
                    $final = trim((string)($resp->json()['candidates'][0]['content']['parts'][0]['text'] ?? ''));
                } else {
                    $key = (string) (SystemSetting::get('chat.openrouter_api_key', env('OPENROUTER_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.openrouter_model', env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free')));
                    $messages = [
                        ['role'=>'system','content'=>"Jsi webový rešeršista a autor. Odpovídej česky, stručně a věcně. Použij výhradně poskytnuté webové texty (JSON).\nPro KAŽDOU stránku v JSON vytvoř oddělenou sekci s nadpisem obsahujícím doménu nebo název (title).\nPokud uživatel chce shrnutí článku, napiš 'Shrnutí' a poté krátký 'Podobný článek'.\nPokud jde o homepage/rozcestník, napiš 'O čem je web' + klíčové sekce.\nNa závěr přidej krátký přehled Zdrojů (URL)."],
                        ['role'=>'system','content'=>"WEB DATA (JSON):\n".$webJson],
                        ['role'=>'user','content'=>$userText],
                    ];
                    $resp = Http::withToken($key)->acceptJson()->post('https://openrouter.ai/api/v1/chat/completions', [ 'model'=>$model, 'messages'=>$messages, 'stream'=>false, 'max_tokens'=>2400, 'temperature'=>0.4 ]);
                    $final = trim((string)($resp->json()['choices'][0]['message']['content'] ?? ''));
                }
            } catch (\Throwable $e) {
                $final = $final ?: "Nepodařilo se získat odpověď od LLM.";
            }
            if ($final === '') { $final = "Z poskytnutých stránek se nepodařilo sestavit odpověď."; }

            echo "event: delta\n"; echo 'data: ' . json_encode(['text'=>$final], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'llm','status'=>'end','label'=>'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: done\n"; echo 'data: ' . json_encode(['message_id'=>$messageId,'status'=>'ok']) . "\n\n"; @ob_flush(); @flush();

            DB::table('chat_messages')->where('id', $assistantMessageId)->update(['content'=>$final,'status'=>'done','updated_at'=>now()]);
            DB::table('chat_actions')->insert([
                'session_id'=>$sessionId,
                'message_id'=>$messageId,
                'tool_name'=>'web.agent.v2',
                'inputs'=>json_encode(['base'=>$baseUrl], JSON_UNESCAPED_UNICODE),
                'outputs'=>json_encode(['pages'=>count($webContext),'chars'=>strlen($final)], JSON_UNESCAPED_UNICODE),
                'status'=>'done','created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
}
