<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use App\Services\AI\Tools\ContactsTool;
use App\Services\AI\Planner;
use App\Services\Tools\PlaywrightTool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\KnowledgeNote;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use App\Services\Knowledge\QdrantClient;
use App\Services\Knowledge\EmbeddingsService;

class AgentService
{
    /**
     * Tokenize Czech/EN text into searchable terms (length >= 3), remove common stopwords.
     */
    protected function tokenize(string $text): array
    {
        $t = mb_strtolower($text);
        // Replace punctuation with space
        $t = preg_replace('/[\p{P}\p{S}]+/u', ' ', $t);
        $parts = preg_split('/\s+/u', (string)$t, -1, PREG_SPLIT_NO_EMPTY);
        $stop = [
            'jaká','jake','jaka','jaké','jakeho','co','kde','na','se','zde','jsme','je','jsou','byl','byla','být','byt',
            'prosím','prosim','mi','můžu','mohu','můžete','muzete','chci','potrebuji','potřebuji','a','i','o','u','v','s','do','pro',
            'informace','kontaktni','kontaktní','oddělení','oddeleni','pracovníky','pracovniky','společnost','spolecnost','firma','firmy',
            'telefonní','telefonni','emailové','emailove','emailem','emailu','e-mail','e‑mail','e‑mailem'
        ];
        $stop = array_flip($stop);
        $out = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 3) { continue; }
            if (isset($stop[$p])) { continue; }
            $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    /**
     * Simple extractor for general contact lines from knowledge note text.
     */
    protected function extractGeneralContacts(string $text): array
    {
        $found = [];
        $hay = (string)$text;
        // Try to locate the "Obecné kontakty" section and scan a small window after it
        $pos = mb_stripos($hay, 'obecné kontakty');
        if ($pos === false) { $pos = mb_stripos($hay, 'obecne kontakty'); }
        if ($pos !== false) {
            $slice = mb_substr($hay, $pos, 600);
            if (preg_match('/(?im)^(?:telefon|tel\.?):\s*(.+)$/u', $slice, $m1)) {
                $found['phone'] = trim($m1[1]);
            }
            if (preg_match('/(?im)^(?:e[-‑]?mail|email):\s*(.+)$/u', $slice, $m2)) {
                $found['email'] = trim($m2[1]);
            }
            if (!empty($found)) { return $found; }
        }
        // Fallback: scan entire text for labeled lines (might capture first general labels if present)
        if (preg_match('/(?im)^(?:telefon|tel\.?):\s*(.+)$/u', $hay, $m1)) {
            $found['phone'] = trim($m1[1]);
        }
        if (preg_match('/(?im)^(?:e[-‑]?mail|email):\s*(.+)$/u', $hay, $m2)) {
            $found['email'] = trim($m2[1]);
        }
        return $found;
    }

    /**
     * Stream a concise answer built deterministically from knowledge snippets (no LLM).
     */
    protected function streamDeterministicKnowledge(array $kbSnippets, int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        $primary = $kbSnippets[0] ?? null;
        $text = '';
        if ($primary) {
            $ex = $this->extractGeneralContacts($primary['full'] ?? ($primary['snippet'] ?? ''));
            $lines = [];
            $lines[] = 'Kontakty na ESL (z interní poznámky):';
            if (!empty($ex['phone'])) { $lines[] = '- Telefon: ' . $ex['phone']; }
            if (!empty($ex['email'])) { $lines[] = '- E‑mail: ' . $ex['email']; }
            if (count($lines) <= 1) {
                // Fallback: take first 5 lines of the full text/snippet
                $src = $primary['full'] ?? ($primary['snippet'] ?? '');
                $snippetLines = array_slice(preg_split('/\r?\n/u', trim($src)), 0, 5);
                foreach ($snippetLines as $sl) { if (trim($sl) !== '') $lines[] = trim($sl); }
            }
            $lines[] = 'Zdroj: interní znalosti.';
            $text = implode("\n", $lines);
        } else {
            $text = "V interních znalostech nebyly nalezeny žádné odpovídající informace.";
        }

        return new StreamedResponse(function () use ($messageId, $assistantMessageId, $sessionId, $meta, $text) {
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            // Progress: deterministic knowledge
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'knowledge', 'status' => 'start', 'label' => 'Čtu interní znalosti'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: delta\n";
            echo 'data: ' . json_encode(['text' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            // Progress end
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'knowledge', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $text,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'metrics.stream',
                'inputs' => json_encode(['provider' => 'deterministic', 'model' => 'knowledge-v1', 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['duration_ms' => 0, 'ttft_ms' => 0, 'chars' => strlen($text)], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Use Playwright tool to fetch page and stream a concise summary with source link.
     */
    protected function streamViaPlaywright(string $url, array $allowedDomains, int $timeoutMs, int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        return new StreamedResponse(function () use ($url, $allowedDomains, $timeoutMs, $messageId, $assistantMessageId, $sessionId, $meta) {
            try {
                $tool = app(\App\Services\Tools\PlaywrightTool::class);
                // Emit meta first
                $m = $meta;
                $m['diagnostics']['tool'] = 'playwright.fetch';
                $m['diagnostics']['url'] = $url;
                echo "event: meta\n";
                echo 'data: ' . json_encode($m, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
                // Progress: starting Playwright
                echo "event: progress\n";
                echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'start', 'label' => 'Spouštím Playwright'], JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
                $out = $tool->fetch($url, [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId);
                if (!($out['ok'] ?? false)) {
                    $msg = 'Nepodařilo se načíst stránku ('.$url.').';
                    echo "event: delta\n";
                    echo 'data: ' . json_encode(['text' => $msg], JSON_UNESCAPED_UNICODE) . "\n\n";
                    // Progress: end Playwright
                    echo "event: progress\n";
                    echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
                    @ob_flush(); @flush();
                    @ob_flush(); @flush();
                    echo "event: done\n";
                    echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
                    @ob_flush(); @flush();
                    \DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                        'content' => $msg,
                        'status' => 'done',
                        'updated_at' => now(),
                    ]);
                    return;
                }
                $text = (string) ($out['data']['text'] ?? '');
                $links = [];
                if (isset($out['data']['links']) && is_array($out['data']['links'])) {
                    // links: [{text, href}]
                    foreach ($out['data']['links'] as $lnk) {
                        $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? ''));
                        if ($href) { $links[] = ['text' => $txt ?: $href, 'href' => $href]; }
                    }
                }
                // Debug: emit meta chunk with raw lengths
                try {
                    $debugMeta = $m;
                    $debugMeta['diagnostics']['playwright_bytes'] = mb_strlen($text);
                    $debugMeta['diagnostics']['playwright_links'] = count($links);
                    echo "event: meta\n";
                    echo 'data: ' . json_encode($debugMeta, JSON_UNESCAPED_UNICODE) . "\n\n";
                    @ob_flush(); @flush();
                } catch (\Throwable $e) { /* ignore */ }
                // Heuristicky vytáhnout pár klíčových řádků a udělat krátkou „analýzu obsahu“
                $lines = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r?\n/u', $text)), fn($x)=>$x!=='')), 0, 20);
                $snippet = implode("\n", array_slice($lines, 0, 8));
                $hasMenu = count(array_filter($lines, fn($l)=>preg_match('/(kontakt|o\s?nás|služby|produkty|eshop|blog|kariera|kariéra)/iu', $l)))>0;
                $hasLogin = count(array_filter($lines, fn($l)=>preg_match('/(přihlášení|login|mojeid)/iu', $l)))>0;
                $analysisParts = [];
                if ($hasMenu) { $analysisParts[] = 'Stránka obsahuje navigační položky (menu).'; }
                if ($hasLogin) { $analysisParts[] = 'Na stránce je přihlášení/účet.'; }
                if (!$analysisParts && mb_strlen($text) < 120) { $analysisParts[] = 'Obsah je velmi stručný.'; }
                $analysis = $analysisParts ? ("\n\nKrátká analýza: " . implode(' ', $analysisParts)) : '';
                $source = $out['sources'][0]['url'] ?? $url;
                $linksBlock = '';
                if ($links) {
                    $top = array_slice($links, 0, 5);
                    $linesL = array_map(fn($l)=>'- '.$l['text'].' — '.$l['href'], $top);
                    $linksBlock = "\n\nOdkazy nalezené na stránce:\n".implode("\n", $linesL);
                }
                // If the user asked for menu items, extract likely nav anchors from links and text
                $menuBlock = '';
                $userTextLower = mb_strtolower($meta['user_text'] ?? '');
                $wantsMenu = (mb_strpos($userTextLower, 'menu') !== false) || (mb_strpos($userTextLower, 'položky') !== false) || (mb_strpos($userTextLower, 'naviga') !== false);
                if ($wantsMenu) {
                    $menuKeywords = ['o nás','o&nbsp;nás','kontakt','kontakty','eshop','e-shop','blog','kariera','kariéra','produkty','služby','sluzby','půjčovna','půjcovna','pujcovna','novinky'];
                    $nav = [];
                    foreach ($links as $ln) {
                        $t = mb_strtolower($ln['text'] ?? '');
                        foreach ($menuKeywords as $kw) { if ($t && mb_strpos($t, $kw) !== false) { $nav[$ln['href']] = $ln['text']; break; } }
                    }
                    // Also scan first 30 lines of text for standalone headings that look like menu labels
                    $candidates = array_slice($lines, 0, 30);
                    foreach ($candidates as $c) {
                        $t = mb_strtolower(trim($c));
                        foreach ($menuKeywords as $kw) { if ($t && mb_strpos($t, $kw) !== false) { $nav[$t] = $c; break; } }
                    }
                    if (!empty($nav)) {
                        $menuBlock = "\n\nPoložky menu (odhad):\n".implode("\n", array_map(function($href,$txt){ return '- '.($txt ?: $href); }, array_keys($nav), array_values($nav)));
                    }
                }
                // Emit debug meta with captured excerpt and top links for transparency
                try {
                    $debug2 = $m;
                    $debug2['debug']['web_snippet'] = mb_substr($text, 0, 1200);
                    $debug2['debug']['web_links_top'] = array_slice($links, 0, 10);
                    $debug2['debug']['web_source'] = $source;
                    echo "event: meta\n";
                    echo 'data: ' . json_encode($debug2, JSON_UNESCAPED_UNICODE) . "\n\n";
                    @ob_flush(); @flush();
                } catch (\Throwable $e) { /* ignore */ }
                $summary = "Našel jsem tyto informace na webu:\n".$snippet.$analysis.$menuBlock.$linksBlock."\n\nZdroj: ".$source;
                echo "event: delta\n";
                echo 'data: ' . json_encode(['text' => $summary], JSON_UNESCAPED_UNICODE) . "\n\n";
                // Progress: end Playwright
                echo "event: progress\n";
                echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
                @ob_flush(); @flush();
                echo "event: done\n";
                echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
                @ob_flush(); @flush();
                \DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                    'content' => $summary,
                    'status' => 'done',
                    'updated_at' => now(),
                ]);
                \DB::table('chat_actions')->insert([
                    'session_id' => $sessionId,
                    'message_id' => $messageId,
                    'tool_name' => 'playwright.fetch',
                    'inputs' => json_encode(['url' => $url, 'allowed' => $allowedDomains], JSON_UNESCAPED_UNICODE),
                    'outputs' => json_encode(['source' => $source, 'chars' => strlen($text)], JSON_UNESCAPED_UNICODE),
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                echo "event: delta\n";
                echo 'data: ' . json_encode(['text' => 'Chyba nástroje: '.$e->getMessage()], JSON_UNESCAPED_UNICODE) . "\n\n";
                // Ensure progress closure
                echo "event: progress\n";
                echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
                @ob_flush(); @flush();
                echo "event: done\n";
                echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
                @ob_flush(); @flush();
            }
        });
    }

    /**
     * Iterative web flow: when the user asks for multiple sub-goals (menu + ceny + kontakt),
     * execute steps and present a structured summary.
     */
    protected function streamIterativeWeb(string $url, array $allowedDomains, int $timeoutMs, int $messageId, int $assistantMessageId, int $sessionId, array $meta = [], string $userText = ''): StreamedResponse
    {
        return new StreamedResponse(function () use ($url, $allowedDomains, $timeoutMs, $messageId, $assistantMessageId, $sessionId, $meta, $userText) {
            $tool = app(\App\Services\Tools\PlaywrightTool::class);
            $m = $meta; $m['diagnostics']['tool'] = 'playwright.iterative'; $m['diagnostics']['url'] = $url;
            echo "event: meta\n"; echo 'data: ' . json_encode($m, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            // Steps plan (show to user)
            $steps = [
                ['id'=>'fetch_home','label'=>'Načtu domovskou stránku (plný obsah)','status'=>'start'],
                ['id'=>'extract_menu','label'=>'Vytáhnu položky menu','status'=>'wait'],
            ];
            $lt = mb_strtolower($userText);
            $wantsMenu = (mb_strpos($lt,'menu')!==false) || (mb_strpos($lt,'položky')!==false) || (mb_strpos($lt,'naviga')!==false);
            $wantsPrices = (mb_strpos($lt,'ceník')!==false) || (mb_strpos($lt,'cenik')!==false) || (mb_strpos($lt,'ceny')!==false) || (mb_strpos($lt,'price')!==false) || (mb_strpos($lt,'pricing')!==false);
            $wantsContact = (mb_strpos($lt,'kontakt')!==false) || (mb_strpos($lt,'kontakty')!==false) || (mb_strpos($lt,'contact')!==false);
            if ($wantsPrices) { $steps[] = ['id'=>'follow_pricing','label'=>'Najdu stránku ceníku/cen','status'=>'wait']; }
            if ($wantsContact) { $steps[] = ['id'=>'follow_contact','label'=>'Najdu stránku kontaktů','status'=>'wait']; }
            // Emit plan
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'start','label'=>'Iterační web úlohy'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            // Step 1: fetch home with full content
            $home = $tool->fetch($url, [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId, [
                'wait_until' => 'networkidle', 'auto_scroll' => true, 'full_text' => true, 'read_mode' => 'textContent', 'max_chars' => 400000,
            ]);
            $text = (string) ($home['data']['text'] ?? '');
            $links = [];
            if (isset($home['data']['links']) && is_array($home['data']['links'])) {
                foreach ($home['data']['links'] as $lnk) { $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? '')); if ($href) { $links[] = ['text'=>$txt ?: $href, 'href'=>$href]; } }
            }
            // Update debug meta
            try { $m2 = $m; $m2['debug']['web_snippet'] = mb_substr($text,0,2000); $m2['debug']['web_links_top'] = array_slice($links,0,15); $m2['debug']['web_source'] = $home['sources'][0]['url'] ?? $url; echo "event: meta\n"; echo 'data: ' . json_encode($m2, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush(); } catch (\Throwable $e) { }
            // Step progress
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Domovská stránka načtena'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            // Extract menu
            $menuKeywords = ['o nás','o&nbsp;nás','kontakt','kontakty','eshop','e-shop','blog','kariera','kariéra','produkty','služby','sluzby','půjčovna','půjcovna','pujcovna','novinky','ceník','cenik','ceny','price','pricing'];
            $nav = [];
            foreach ($links as $ln) { $t = mb_strtolower($ln['text'] ?? ''); foreach ($menuKeywords as $kw) { if ($t && mb_strpos($t, $kw) !== false) { $nav[$ln['href']] = $ln['text']; break; } } }
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Menu extrahováno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            // Optional: follow pricing/contact if requested
            $sections = [];
            if ($wantsPrices) {
                $cand = array_values(array_filter(array_keys($nav), fn($h)=>preg_match('/cen[ií]k|ceny|pricing|price/i', $h)));
                $target = $cand[0] ?? null;
                if ($target) {
                    echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Načítám stránku ceníku'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                    $p = $tool->fetch($target, [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId, ['wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>'textContent','max_chars'=>400000]);
                    $sections['cenik'] = [ 'url' => $target, 'text' => mb_substr((string)($p['data']['text'] ?? ''), 0, 5000) ];
                }
            }
            if ($wantsContact) {
                $cand = array_values(array_filter(array_keys($nav), fn($h)=>preg_match('/kontakt/i', $h)));
                $target = $cand[0] ?? null;
                if ($target) {
                    echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Načítám stránku kontaktů'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                    $p = $tool->fetch($target, [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId, ['wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>'textContent','max_chars'=>400000]);
                    $sections['kontakty'] = [ 'url' => $target, 'text' => mb_substr((string)($p['data']['text'] ?? ''), 0, 5000) ];
                }
            }
            // Compose structured answer
            $out = [];
            $out[] = "Iterační web úlohy (plán):";
            foreach ($steps as $s) { $out[] = '- '.$s['label']; }
            $out[] = "\nNašel jsem tyto informace:";
            // Menu
            if ($wantsMenu && !empty($nav)) {
                $out[] = "Položky menu (odhad):";
                foreach ($nav as $href=>$txt) { $out[] = '- '.($txt ?: $href); }
            }
            // Sections
            foreach ($sections as $k=>$sec) {
                $out[] = "\nSekce: ".$k." (".($sec['url'] ?? '').")";
                $snippet = implode("\n", array_slice(array_filter(array_map('trim', preg_split('/\r?\n/u', (string)$sec['text'])), fn($x)=>$x!==''), 0, 12));
                $out[] = $snippet;
            }
            $out[] = "\nZdroj: ".($home['sources'][0]['url'] ?? $url);
            $final = implode("\n", $out);
            echo "event: delta\n"; echo 'data: ' . json_encode(['text' => $final], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'end','label'=>'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: done\n"; echo 'data: ' . json_encode(['message_id'=>$messageId,'status'=>'ok']) . "\n\n"; @ob_flush(); @flush();
            \DB::table('chat_messages')->where('id', $assistantMessageId)->update(['content'=>$final,'status'=>'done','updated_at'=>now()]);
            \DB::table('chat_actions')->insert([
                'session_id'=>$sessionId,'message_id'=>$messageId,'tool_name'=>'playwright.iterative','inputs'=>json_encode(['url'=>$url,'allowed'=>$allowedDomains], JSON_UNESCAPED_UNICODE),'outputs'=>json_encode(['chars'=>strlen($final)], JSON_UNESCAPED_UNICODE),'status'=>'done','created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }

    /**
     * LLM-driven web agent: LLM naplánuje kroky (intenty), my je realizujeme přes Playwright a výsledek agregujeme.
     * Bez heuristik: plán vychází z LLM, ne z pevných klíčových slov.
     */
    protected function streamWebAgentLLM(string $provider, string $userText, array $allowedDomains, int $timeoutMs, int $messageId, int $assistantMessageId, int $sessionId, array $meta = [], string $baseUrl = null): StreamedResponse
    {
        return new StreamedResponse(function () use ($provider, $userText, $allowedDomains, $timeoutMs, $messageId, $assistantMessageId, $sessionId, $meta, $baseUrl) {
            // 1) Požádej LLM o plán kroků
            $plannerPrompt = "Jsi webový plánovač. Z dotazu uživatele a (volitelné) základní URL navrhni až 6 kroků.\n".
                             "Každý krok je JSON objekt s poli: id, label, url (absolutní nebo relativní k base), read_mode (readability|textContent|innerText), full (true/false pro full_text), scroll (true/false pro auto_scroll), parallel_group (stejné číslo = běžet paralelně).\n".
                             "POVINNĚ: Pokud jsou v zadání výslovně uvedeny absolutní URL, vytvoř krok pro každou z nich (url ponech absolutní).\n".
                             "Pokyny: Pro články/zpravodajství (např. /clanky, /article) preferuj read_mode=readability, pro homepage rozcestníky textContent. full=true, scroll=true. Neplánuj více než 2 kroky na doménu, pokud to není nutné.";
            // Extract explicit URLs to help the planner
            $userUrls = [];
            if (preg_match_all('#https?://[^\s]+#iu', $userText, $mAll)) { $userUrls = array_values(array_unique($mAll[0])); }
            $promptUser = "Base URL: ".($baseUrl ?: '(none)')."\nPožadavek: ".$userText;
            if (!empty($userUrls)) { $promptUser .= "\nExplicitní URL: ".implode(', ', $userUrls); }
            $planMessages = [ ['role'=>'system','content'=>$plannerPrompt], ['role'=>'user','content'=>$promptUser] ];
            // Progress: planner start
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'start','label'=>'Plánuji kroky (LLM)'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            // Pro jednoduchost použijeme OpenRouter/Gemini wrapper podle provideru, ale nestreamujeme (rychlá 1-shot odpověď)
            $steps = [];
            try {
                if ($provider === 'gemini' && SystemSetting::get('chat.gemini_enabled', '0') === '1') {
                    $key = (string) (SystemSetting::get('chat.gemini_api_key', env('GEMINI_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.gemini_model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
                    $resp = \Illuminate\Support\Facades\Http::withHeaders(['Content-Type'=>'application/json'])
                        ->post('https://generativelanguage.googleapis.com/v1beta/models/'.urlencode($model).':generateContent?key='.urlencode($key), [
                            'contents' => [ ['role'=>'user','parts'=>[['text'=> $plannerPrompt."\n\nBase URL: ".$baseUrl."\nPožadavek: ".$userText]]] ],
                        ]);
                    $txt = trim((string)($resp->json()['candidates'][0]['content']['parts'][0]['text'] ?? ''));
                    $steps = json_decode($txt, true);
                } else {
                    $key = (string) (SystemSetting::get('chat.openrouter_api_key', env('OPENROUTER_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.openrouter_model', env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free')));
                    $resp = \Illuminate\Support\Facades\Http::withToken($key)->acceptJson()->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model'=>$model,
                        'stream'=>false,
                        'messages'=>$planMessages,
                        'max_tokens'=>500,
                        'temperature'=>0.1,
                    ]);
                    $txt = trim((string)($resp->json()['choices'][0]['message']['content'] ?? ''));
                    $steps = json_decode($txt, true);
                }
            } catch (\Throwable $e) { $steps = []; }
            if (!is_array($steps)) { $steps = []; }

            // 2) Pokud plán selže, fallback: v rámci stejného streamu načti základní URL a vypiš shrnutí/menu/počet odkazů
            if (empty($steps)) {
                echo "event: meta\n"; echo 'data: ' . json_encode(array_merge($meta, ['diagnostics'=>array_merge($meta['diagnostics']??[], ['web_agent'=>'plan_failed'])]), JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'start','label'=>'Načítám web (fallback)'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                $tool = app(\App\Services\Tools\PlaywrightTool::class);
                // Choose a sensible fallback URL: prefer explicit URLs in user text; else base; else esl.cz
                $fallbackUrl = null;
                $allowedCsvFb = (string) SystemSetting::get('tools.playwright.allowed_domains', env('TOOLS_PLAYWRIGHT_ALLOWED_DOMAINS', 'esl.cz'));
                $allowedFb = array_values(array_filter(array_map('trim', explode(',', $allowedCsvFb)))); if (empty($allowedFb)) { $allowedFb = ['esl.cz']; }
                $allowAllFb = in_array('*', $allowedFb, true);
                $urlsFound = [];
                if (preg_match_all('#https?://[^\s]+#iu', $userText, $mFb)) { $urlsFound = $mFb[0]; }
                $norm = function($h){ return preg_replace('/^www\./i','', $h); };
                $isAllowedFb = function(string $host) use ($allowedFb, $allowAllFb, $norm){ if ($allowAllFb) return true; $host = $norm($host); foreach ($allowedFb as $r){ $rn = $norm(preg_replace('/^\*\.?/','', trim($r))); if($rn && ($host===$rn || str_ends_with($host, '.'.$rn))) return true; } return false; };
                foreach ($urlsFound as $u) { $h = parse_url($u, PHP_URL_HOST) ?: ''; if ($h && $isAllowedFb($h)) { $fallbackUrl = $u; break; } }
                if (!$fallbackUrl) { $fallbackUrl = $baseUrl ?: 'https://www.esl.cz/'; }
                $r = $tool->fetch($fallbackUrl, [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId, [ 'wait_until'=>'networkidle', 'auto_scroll'=>true, 'full_text'=>true, 'read_mode'=>'textContent', 'max_chars'=>400000 ]);
                $text = (string)($r['data']['text'] ?? '');
                $links = [];
                if (isset($r['data']['links']) && is_array($r['data']['links'])) {
                    foreach ($r['data']['links'] as $lnk) { $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? '')); if ($href) { $links[] = ['text' => $txt ?: $href, 'href' => $href]; } }
                }
                // Debug meta for UI
                try { $dbg = $meta; $dbg['debug']['web_snippet'] = mb_substr($text, 0, 1200); $dbg['debug']['web_links_top'] = array_slice($links, 0, 10); $dbg['debug']['web_source'] = $fallbackUrl; echo "event: meta\n"; echo 'data: ' . json_encode($dbg, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush(); } catch (\Throwable $e) { }
                // Compose output: summary + menu + link count
                $lines = [];
                $allLines = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r?\n/u', $text)), fn($x)=>$x!=='')), 0, 20);
                $snippet = implode("\n", array_slice($allLines, 0, 8));
                $lines[] = "Krátké shrnutí:";
                $lines[] = $snippet;
                $menuKeywords = ['o nás','o&nbsp;nás','kontakt','kontakty','eshop','e-shop','blog','kariera','kariéra','produkty','služby','sluzby','půjčovna','půjcovna','pujcovna','novinky','ceník','cenik','ceny','price','pricing'];
                $nav = [];
                foreach ($links as $ln) { $t = mb_strtolower($ln['text'] ?? ''); foreach ($menuKeywords as $kw) { if ($t && mb_strpos($t, $kw) !== false) { $nav[$ln['href']] = $ln['text']; break; } } }
                if (!empty($nav)) {
                    $lines[] = "\nPoložky menu (odhad):";
                    foreach ($nav as $href=>$txt) { $lines[] = '- '.($txt ?: $href); }
                }
                $lines[] = "\nPočet odkazů na stránce: ".count($links);
                $lines[] = "\nZdroj: ".$fallbackUrl;
                $final = implode("\n", $lines);
                echo "event: delta\n"; echo 'data: ' . json_encode(['text'=>$final], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'end','label'=>'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                echo "event: done\n"; echo 'data: ' . json_encode(['message_id'=>$messageId,'status'=>'ok']) . "\n\n"; @ob_flush(); @flush();
                \DB::table('chat_messages')->where('id', $assistantMessageId)->update(['content'=>$final,'status'=>'done','updated_at'=>now()]);
                \DB::table('chat_actions')->insert([
                    'session_id'=>$sessionId,'message_id'=>$messageId,'tool_name'=>'web.agent.fallback','inputs'=>json_encode(['base'=>$baseUrl,'allowed'=>$allowedDomains], JSON_UNESCAPED_UNICODE),'outputs'=>json_encode(['chars'=>strlen($final)], JSON_UNESCAPED_UNICODE),'status'=>'done','created_at'=>now(),'updated_at'=>now(),
                ]);
                return; // end stream
            }

            // 3) Zobraz plán do progressu
            echo "event: meta\n"; echo 'data: ' . json_encode(array_merge($meta, ['diagnostics'=>array_merge($meta['diagnostics']??[], ['web_agent'=>'planned','steps'=>$steps])]), JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'end','label'=>'Plán hotov (LLM) — kroky: '.count($steps)], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'start','label'=>'Procházím weby'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();

            // 4) Provedení kroků (respektujeme parallel_group) + zahrň explicitní URL z dotazu
            $tool = app(\App\Services\Tools\PlaywrightTool::class);
            $results = [];
            // Collect URLs from steps and from user text (explicit)
            $allUrls = [];
            foreach ($steps as $st) { $u = trim((string)($st['url'] ?? '')); if ($u !== '') { $allUrls[] = $u; } }
            if (preg_match_all('#https?://[^\s]+#iu', $userText, $mAllExec)) { $allUrls = array_merge($allUrls, $mAllExec[0]); }
            $allUrls = array_values(array_unique($allUrls));
            $disallowed = [];
            $normalizeHostExec = function(string $h){ return preg_replace('/^www\./i','', $h); };
            $allowAllExec = in_array('*', $allowedDomains, true);
            $isAllowedExec = function(string $host) use ($allowedDomains, $allowAllExec, $normalizeHostExec){ if ($allowAllExec) return true; $host = $normalizeHostExec($host); foreach ($allowedDomains as $rule){ $rn = $normalizeHostExec(preg_replace('/^\*\.?/','', trim($rule))); if ($rn && ($host===$rn || str_ends_with($host, '.'.$rn))) return true; } return false; };
            foreach ($allUrls as $u) { if (!preg_match('#^https?://#i', $u)) continue; $h = parse_url($u, PHP_URL_HOST) ?: ''; if ($h && !$isAllowedExec($h)) { $disallowed[] = $h; } }
            $disallowed = array_values(array_unique($disallowed));
            if (!empty($disallowed) && !$allowAllExec) {
                $warn = "Následující domény nejsou povoleny a budou přeskočeny: ".implode(', ', $disallowed).". Přidejte je do Nastavení → Nástroje → Playwright (Povolené domény).";
                echo "event: delta\n"; echo 'data: ' . json_encode(['text'=>$warn], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                echo "event: meta\n"; echo 'data: ' . json_encode(array_merge($meta, ['diagnostics'=>array_merge($meta['diagnostics']??[], ['disallowed'=>$disallowed])]), JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            }
            // Announce discovered tasks for transparency
            $totalTasks = count($allUrls);
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'progress','label'=>'Nalezené úkoly: '.$totalTasks], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            foreach (array_values($allUrls) as $i => $u) {
                $host = parse_url($u, PHP_URL_HOST) ?: '';
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'planner','status'=>'progress','label'=>'Úkol '.($i+1).'/'.$totalTasks.': '.$u.($host?(' ('.$host.')'):'')], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            }
            // Build jobs for parallel fetch
            $jobs = [];
            $labels = [];
            foreach ($allUrls as $idx => $u) {
                if (!preg_match('#^https?://#i', $u)) continue;
                $h = parse_url($u, PHP_URL_HOST) ?: '';
                if ($h && !$isAllowedExec($h) && !$allowAllExec) {
                    $results[] = [ 'id'=>null, 'label'=>$u, 'url'=>$u, 'timings'=>null, 'text'=>'', 'links'=>[], 'error'=>'domain_not_allowed' ];
                    echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Přeskočeno (doména není povolena): '.$u], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                    continue;
                }
                $readMode = preg_match('#/(clanek|clanky|article|news)/#i', (string)$u) ? 'readability' : 'textContent';
                $jobs[] = [ 'url'=>$u, 'selectors'=>[], 'allowed_domains'=>$allowedDomains, 'timeout_ms'=>$timeoutMs, 'options'=>[ 'wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>$readMode,'max_chars'=>600000 ] ];
                $labels[] = $u;
                echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Načítám: '.$u], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            }
            if (!empty($jobs)) {
                $batch = $tool->fetchMany($jobs, auth()->id(), $sessionId);
                foreach ($batch as $i => $br) {
                    $label = $labels[$i] ?? ('cíl #'.$i);
                    $json = $br['json'] ?? null;
                    if (!$br['ok']) {
                        $results[] = [ 'id'=>null, 'label'=>$label, 'url'=>$jobs[$i]['url'], 'timings'=>($br['meta']['timings'] ?? null), 'text'=>'', 'links'=>[], 'error'=>'runner_error' ];
                        echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Chyba při načítání: '.$label], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                        continue;
                    }
                    $text = (string)($json['data']['text'] ?? '');
                    if (mb_strlen($text) < 300) {
                        // Optional second pass (sequential) with innerText to boost content
                        $r2 = $tool->fetch($jobs[$i]['url'], [], $allowedDomains, $timeoutMs, auth()->id(), $sessionId, [ 'wait_until'=>'networkidle','auto_scroll'=>true,'full_text'=>true,'read_mode'=>'innerText','max_chars'=>600000 ]);
                        if (($r2['ok'] ?? true) && mb_strlen((string)($r2['data']['text'] ?? '')) > mb_strlen($text)) {
                            $json = $r2; $text = (string)($r2['data']['text'] ?? '');
                        }
                    }
                    $linksArr = [];
                    if (isset($json['data']['links']) && is_array($json['data']['links'])) {
                        foreach ($json['data']['links'] as $lnk) { $href = $lnk['href'] ?? null; $txt = trim((string)($lnk['text'] ?? '')); if ($href) { $linksArr[] = ['text' => $txt ?: $href, 'href' => $href]; } }
                    }
                    $timings = $br['meta']['timings'] ?? ($json['timings'] ?? null);
                    $title = $br['meta']['title'] ?? ($json['title'] ?? null);
                    $results[] = [ 'id'=>null, 'label'=>$label, 'url'=>$jobs[$i]['url'], 'title'=>$title, 'timings'=>$timings, 'text'=>mb_substr($text,0,12000), 'links'=>$linksArr ];
                    if ($timings) { $md = $meta; $md['diagnostics']['runner_timings'] = $timings; echo "event: meta\n"; echo 'data: ' . json_encode($md, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush(); }
                    echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'progress','label'=>'Hotovo: '.$label], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
                }
            }
            // Close playwright phase
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'playwright','status'=>'end','label'=>'Procházení dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();

            // 5) LLM agregace a kompozice na základě stažených stránek (bez heuristik v odpovědi)
            // Připrav kontext pro LLM
            $webContext = [];
        foreach ($results as $res) {
                $webContext[] = [
                    'url' => $res['url'] ?? null,
            'title' => $res['title'] ?? null,
                    'text' => mb_substr((string)($res['text'] ?? ''), 0, 12000),
                    'links' => array_slice(($res['links'] ?? []), 0, 200),
                    'timings' => $res['timings'] ?? null,
                    'label' => $res['label'] ?? null,
                ];
            }
            $webJson = json_encode(['pages' => $webContext], JSON_UNESCAPED_UNICODE);
            // Emit debug meta (what we send to LLM)
            try { $dbg = $meta; $dbg['debug'] = $dbg['debug'] ?? []; $dbg['debug']['llm'] = [ 'system' => 'web-summarizer', 'context_json' => mb_substr($webJson,0,3000), 'user' => mb_substr($userText,0,1000) ]; echo "event: meta\n"; echo 'data: ' . json_encode($dbg, JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush(); } catch (\Throwable $e) { }
            // Progress: start LLM
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'llm','status'=>'start','label'=>'Komponuji odpověď (LLM)'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            $final = '';
            try {
                // Prefer OpenRouter for richer output unless explicitly set to Gemini
                if ($provider === 'gemini' && SystemSetting::get('chat.gemini_enabled', '0') === '1') {
                    $key = (string) (SystemSetting::get('chat.gemini_api_key', env('GEMINI_API_KEY', '')));
                    $model = (string) (SystemSetting::get('chat.gemini_model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
                    $sys = "Jsi šikovný webový rešeršista a autor. Odpovídej česky, stručně a věcně, a když je to vyžádáno, napiš krátký podobný článek.\n".
                           "Použij výhradně poskytnuté webové texty a odkazy (JSON). Pro každý požadovaný web/článek splň přesně zadání uživatele.\n".
                           "Pokud uživatel chce menu a počet odkazů, uveď je explicitně. U článků napiš shrnutí a poté krátký podobný článek (oddělit nadpisem).";
                    $content = [
                        ['role'=>'user','parts'=>[['text'=>$sys."\n\nWEB DATA (JSON):\n".$webJson."\n\nUživatel: \n".$userText]]],
                    ];
                    $resp = \Illuminate\Support\Facades\Http::withHeaders(['Content-Type'=>'application/json'])
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
                    $payload = [ 'model'=>$model, 'messages'=>$messages, 'stream'=>false, 'max_tokens'=>2400, 'temperature'=>0.4 ];
                    $resp = \Illuminate\Support\Facades\Http::withToken($key)->acceptJson()->post('https://openrouter.ai/api/v1/chat/completions', $payload);
                    $final = trim((string)($resp->json()['choices'][0]['message']['content'] ?? ''));
                }
            } catch (\Throwable $e) {
                $final = $final ?: "Nepodařilo se získat odpověď od LLM.";
            }
            if ($final === '') { $final = "Z poskytnutých stránek se nepodařilo sestavit odpověď."; }
            echo "event: delta\n"; echo 'data: ' . json_encode(['text'=>$final], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: progress\n"; echo 'data: ' . json_encode(['stage'=>'llm','status'=>'end','label'=>'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n"; @ob_flush(); @flush();
            echo "event: done\n"; echo 'data: ' . json_encode(['message_id'=>$messageId,'status'=>'ok']) . "\n\n"; @ob_flush(); @flush();
            \DB::table('chat_messages')->where('id', $assistantMessageId)->update(['content'=>$final,'status'=>'done','updated_at'=>now()]);
            \DB::table('chat_actions')->insert([
                'session_id'=>$sessionId,'message_id'=>$messageId,'tool_name'=>'web.agent.llm','inputs'=>json_encode(['base'=>$baseUrl,'allowed'=>$allowedDomains], JSON_UNESCAPED_UNICODE),'outputs'=>json_encode(['steps'=>count($results),'chars'=>strlen($final)], JSON_UNESCAPED_UNICODE),'status'=>'done','created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
    /**
     * Use LLM to extract a concise CRM search term (name/email/phone fragment) from user text.
     * Returns null on failure or when no provider/key available.
     */
    protected function tryLlmAssistQuery(string $provider, string $userText): ?string
    {
        try {
            if ($provider !== 'openrouter') { return null; }
            $key = (string) (SystemSetting::get('chat.openrouter_api_key', env('OPENROUTER_API_KEY', '')));
            $model = (string) (SystemSetting::get('chat.openrouter_model', env('OPENROUTER_MODEL', 'deepseek/deepseek-chat-v3-0324:free')));
            if (!$key || !$model) { return null; }
            $prompt = "Z textu uživatele vyber JEDNO krátké klíčové slovo nebo dvojici slov vhodnou pro CRM vyhledávání kontaktu (jméno, příjmení, e-mail, nebo fragment).\n".
                      "Neodpovídej větou, nekomentuj.\n".
                      "Pokud není nic vhodného, napiš 'NONE'.\n\n".
                      "Text: ".$userText;
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Jsi extraktor klíčových slov pro CRM. Výstup je pouze klíčové slovo nebo NONE.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
                'max_tokens' => 10,
                'temperature' => 0.2,
            ];
            $resp = Http::withToken($key)->acceptJson()->post('https://openrouter.ai/api/v1/chat/completions', $payload);
            if (!$resp->ok()) { return null; }
            $json = $resp->json();
            $text = trim((string)($json['choices'][0]['message']['content'] ?? ''));
            if ($text === '' || strtoupper($text) === 'NONE') { return null; }
            // sanitize output: strip quotes and control chars
            $text = trim($text, "\"'`\s");
            // only allow reasonable length
            if (mb_strlen($text) > 64) { $text = mb_substr($text, 0, 64); }
            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('tryLlmAssistQuery error: '.$e->getMessage());
            return null;
        }
    }
    public function streamResponse(int $messageId, int $sessionId, string $userText, int $assistantMessageId): StreamedResponse
    {
    $enabled = SystemSetting::get('chat.enabled', '0') === '1';
    $provider = SystemSetting::get('chat.provider', 'openrouter');
    $geminiEnabled = SystemSetting::get('chat.gemini_enabled', '0') === '1';
    // Playwright global toggle (default ON if not set); allow env override
    $pwEnabledFlag = SystemSetting::get('tools.playwright.enabled', '1') === '1';
    if (env('TOOLS_PLAYWRIGHT_FORCE_ENABLED', '0') === '1') { $pwEnabledFlag = true; }
        if ($provider === 'gemini' && !$geminiEnabled) {
            // Gemini je vypnuté → vrať se na OpenRouter
            $provider = 'openrouter';
        }

        if (!$enabled) {
            return $this->streamMockResponse($messageId);
        }

    // Earliest hard override: explicit web intent routes directly to web agent before any other work
    try {
            $ltIntent0 = mb_strtolower($userText);
            $explicitWebPhrase0 = (mb_strpos($ltIntent0, 'webu') !== false) || (mb_strpos($ltIntent0, 'internetu') !== false) || (mb_strpos($ltIntent0, 'ověř') !== false) || (mb_strpos($ltIntent0, 'over') !== false) || (mb_strpos($ltIntent0, 'zjisti') !== false) || (mb_strpos($ltIntent0, 'vyhledej') !== false) || (mb_strpos($ltIntent0, 'jdi na') !== false) || (mb_strpos($ltIntent0, 'otevři') !== false) || (mb_strpos($ltIntent0, 'navštiv') !== false);
            $mentionsLink0 = (bool) preg_match('#https?://[^\s]+#iu', $userText) || (bool) preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText);
            $explicitWebIntent0 = $explicitWebPhrase0 || $mentionsLink0;
            if ($explicitWebIntent0) {
                // Optional global toggle still respected
                if (!$pwEnabledFlag) {
                    // Inform immediately that web is disabled
                    $meta0 = [ 'user_text' => $userText, 'diagnostics' => ['explicit_web_intent' => true, 'provider' => $provider, 'model' => $provider === 'openrouter' ? (string) SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free') : ($provider === 'gemini' ? (string) SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash') : '')]];
                    return $this->streamWebDisabledNotice($userText, $messageId, $assistantMessageId, $sessionId, $meta0);
                }
                // Feature flag: route to V2 when enabled
                // V2 feature flag (default ON if not set); allow env override
                $useV2 = SystemSetting::get('agent.v2_enabled', '1') === '1';
                if (env('AGENT_V2_FORCE', '0') === '1') { $useV2 = true; }
                if ($useV2) {
                    $meta0 = [ 'user_text' => $userText, 'diagnostics' => ['explicit_web_intent' => true, 'provider' => $provider, 'v2' => true] ];
                    return app(\App\Services\AI\WebAgentV2Service::class)->stream($provider, $userText, $messageId, $assistantMessageId, $sessionId, $meta0, null);
                }
                // Legacy V1 path retained for compatibility (allowlist logic)
                $allowedCsv0 = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
                $allowed0 = array_values(array_filter(array_map('trim', explode(',', $allowedCsv0))));
                if (empty($allowed0)) { $allowed0 = ['esl.cz']; }
                $allowAll0 = in_array('*', $allowed0, true);
                $timeout0 = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
                $mentionedUrl0 = null; $mentionedDomain0 = null; $target0 = null; $allUrls0 = [];
                if (preg_match_all('#https?://[^\s]+#iu', $userText, $mUrl0All)) { $allUrls0 = $mUrl0All[0]; $mentionedUrl0 = $allUrls0[0] ?? null; }
                if (!$mentionedUrl0 && preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText, $mDom0)) { $mentionedDomain0 = mb_strtolower($mDom0[1]); }
                $normalize0 = function(string $h){ return preg_replace('/^www\./i','', $h); };
                $isAllowed0 = function(string $host) use ($allowed0, $normalize0, $allowAll0){ if ($allowAll0) return true; $host = $normalize0($host); foreach ($allowed0 as $rule){ $rn = $normalize0(preg_replace('/^\*\.?/','', trim($rule))); if($rn && ($host === $rn || str_ends_with($host, '.'.$rn))) return true; } return false; };
                if ($mentionedUrl0) {
                    $h0 = parse_url($mentionedUrl0, PHP_URL_HOST) ?: '';
                    if ($h0 && $isAllowed0($h0)) { $target0 = $mentionedUrl0; }
                } elseif ($mentionedDomain0 && $isAllowed0($mentionedDomain0)) {
                    $target0 = 'https://'.$mentionedDomain0;
                }
                if (!$target0 && count($allUrls0) > 1) {
                    $meta0 = [ 'user_text' => $userText, 'diagnostics' => ['explicit_web_intent' => true, 'provider' => $provider, 'used' => $provider, 'user_urls' => $allUrls0 ] ];
                    return $this->streamWebAgentLLM($provider, $userText, $allowed0, $timeout0, $messageId, $assistantMessageId, $sessionId, $meta0, null);
                }
                if (!$target0) {
                    if ($allowAll0) { $target0 = $mentionedDomain0 ? 'https://'.$mentionedDomain0 : 'https://www.esl.cz/'; }
                    else {
                        foreach ($allowed0 as $dom0) { $dom0 = preg_replace('/^\*\.?/','', trim($dom0)); if(!$dom0) continue; $target0 = 'https://'.(stripos($dom0,'www.')===0 ? $dom0 : 'www.'.$dom0).'/'; break; }
                    }
                }
                if ($target0) {
                    $meta0 = [ 'user_text' => $userText, 'diagnostics' => ['explicit_web_intent' => true, 'provider' => $provider, 'used' => $provider] ];
                    $meta0['progress_pre'] = [ ['stage'=>'init','status'=>'end','label'=>'Zpracovávám dotaz'], ['stage'=>'planner','status'=>'start','label'=>'Plánuji kroky (LLM)'], ];
                    return $this->streamWebAgentLLM($provider, $userText, $allowed0, $timeout0, $messageId, $assistantMessageId, $sessionId, $meta0, $target0);
                }
            }
        } catch (\Throwable $e) { /* ignore and continue */ }

    // Planner: infer intent/entities and propose read-only actions; also annotate system prompt
        $planner = new Planner();
        $plan = $planner->plan($userText);

    // Log planner decision as a tool message for audit trail
        DB::table('chat_messages')->insert([
            'session_id' => $sessionId,
            'role' => 'tool',
            'content' => json_encode(['tool' => 'planner.plan', 'result' => $plan], JSON_UNESCAPED_UNICODE),
            'status' => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    // Execute read-only actions (contact lookups) based on plan
    $toolResult = $this->executeReadOnlyPlan($sessionId, $messageId, $plan);
    $toolsNote = is_array($toolResult) ? ($toolResult['note'] ?? null) : $toolResult;
    $links = is_array($toolResult) ? ($toolResult['links'] ?? []) : [];
    $foundContacts = is_array($toolResult) ? (($toolResult['found']['contacts'] ?? []) ) : [];
    if (!$toolsNote) {
            // Fallback heuristic lookup for compatibility
            $toolsNote = $this->maybeLookupContact($sessionId, $messageId, $userText);
        }

        // If nothing found yet, try LLM-assisted query refinement, then deterministic CRM search
        $assistUsed = false; $assistTerm = null;
        if (empty($foundContacts)) {
            try {
                $assist = $this->tryLlmAssistQuery($provider, $userText);
                if ($assist && mb_strlen($assist) >= 3) {
                    $assistUsed = true; $assistTerm = $assist;
                    $rows = (new ContactsTool())->searchByText($assist, 5);
                    if ($rows) {
                        foreach ($rows as $r) {
                            $name = trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? ''));
                            $abs = URL::to('/crm/contacts/'.$r['id']);
                            $foundContacts[] = [
                                'id' => $r['id'],
                                'name' => $name ?: null,
                                'email' => $r['email'] ?? null,
                                'phone' => $r['phone'] ?? null,
                                'url' => $abs,
                            ];
                            $links['contacts'][] = [ 'id' => $r['id'], 'url' => $abs ];
                        }
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $messageId,
                            'tool_name' => 'assist.search_term',
                            'inputs' => json_encode(['text' => mb_strimwidth($userText,0,200,'…')], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['term' => $assist, 'count' => count($rows)], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'assist.search_term', 'input' => ['text' => $userText], 'result' => ['term' => $assist, 'count' => count($rows)]], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $messageId,
                            'tool_name' => 'assist.search_term',
                            'inputs' => json_encode(['text' => mb_strimwidth($userText,0,200,'…')], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['term' => $assist, 'count' => 0], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('LLM assist failed: '.$e->getMessage());
            }
        }

    $systemPrompt = 'Jsi asistent CRM. Odpovídej česky, stručně a věcně. Pokud uvádíš data, sděl zdroj (CRM) a uveď ID. Nikdy si nevymýšlej jména, ID, telefony ani firmy. Pokud ověřený kontext uvedený níže neobsahuje hledaný záznam, jasně napiš, že nebyl nalezen.';
        if ($toolsNote) {
            $systemPrompt .= "\n\nPoznámka (interní nález): " . $toolsNote;
        }
        if (!empty($plan['requires_confirmation'])) {
            $systemPrompt .= "\n\nUpozornění: Uživatel možná požaduje změnu dat. Neprováděj žádné změny. Nejprve si vyžádej potvrzení. Popiš, co by se mělo stát, a počkej na potvrzení.";
        }

    // Determine if this is a deterministic lookup before preparing meta
        $isLookup = in_array('lookup_contact_by_email', $plan['actions'] ?? [], true)
            || in_array('lookup_contact_by_phone', $plan['actions'] ?? [], true)
            || in_array('search_contact_by_text', $plan['actions'] ?? [], true);
    // If LLM assist surfaced CRM results, treat as deterministic
    if (!$isLookup && !empty($foundContacts)) { $isLookup = true; }

        // Knowledge retrieval (notes + lightweight document chunks or Qdrant vectors)
        $kbSnippets = [];
        $preferKb = false;
        try {
            $q = mb_substr($userText, 0, 160);
            $tokens = $this->tokenize($q);
            if (count($tokens) >= 1) {
                // Vector retrieval via Qdrant if enabled
                if (config('qdrant.enabled')) {
                    try {
                        $embedder = app(EmbeddingsService::class);
                        $qdrant = app(QdrantClient::class);
                        $vector = $embedder->embed($q);
                        if (is_array($vector)) {
                            $col = (string) config('qdrant.collection');
                            // Filter by visibility and user
                            $filter = [
                                'must' => [
                                    ['key' => 'visibility', 'match' => ['value' => 'public']],
                                ],
                            ];
                            if (auth()->check()) {
                                $filter['should'][] = ['key' => 'user_id', 'match' => ['value' => auth()->id()]];
                                $filter['minimum_should'] = 0;
                            }
                            $results = $qdrant->search($col, $vector, 5, $filter);
                            foreach ($results as $r) {
                                $p = $r['payload'] ?? [];
                                $title = $p['title'] ?? ('Dokument #'.($p['document_id'] ?? ''));
                                // Qdrant payload uses 'preview' for snippet text
                                $text = (string) ($p['preview'] ?? ($p['text'] ?? ''));
                                $kbSnippets[] = [
                                    'id' => ($p['document_id'] ?? 'doc').'#'.($p['chunk_index'] ?? 0),
                                    'title' => $title.' – část '.($p['chunk_index'] ?? 0),
                                    'snippet' => mb_substr($text, 0, 800),
                                    'full' => $text,
                                    'updated_at' => (string) now(),
                                ];
                            }
                        }
                    } catch (\Throwable $e) { /* qdrant off */ }
                }

                // Fallback to DB search/scoring (notes + chunks)
                // Build OR-like query for tokens
                $notesQ = KnowledgeNote::query()
                    ->where(function($w) use ($tokens){
                        foreach ($tokens as $t) {
                            $w->orWhere('title','like',"%$t%")
                              ->orWhere('content','like',"%$t%")
                              ->orWhereJsonContains('tags', $t);
                        }
                    })
                    ->where(function($w){
                        $w->where('visibility','public');
                        if (auth()->check()) { $w->orWhere('user_id', auth()->id()); }
                    })
                    ->limit(10)
                    ->get(['id','title','content','updated_at']);
                // Score notes by token matches
                $scoredNotes = [];
                foreach ($notesQ as $n) {
                    $text = mb_strtolower((string)$n->title . ' ' . (string)$n->content);
                    $score = 0; foreach ($tokens as $t) { if ($t !== '' && mb_strpos($text, $t) !== false) { $score++; } }
                    if ($score > 0) {
                        $snippet = trim(mb_substr(strip_tags($n->content), 0, 800));
                        $scoredNotes[] = [ 'id' => $n->id, 'title' => $n->title, 'snippet' => $snippet, 'full' => (string)$n->content, 'updated_at' => (string)$n->updated_at, 'score' => $score ];
                    }
                }
                usort($scoredNotes, fn($a,$b)=> $b['score'] <=> $a['score']);
                $dbNoteSnippets = array_slice(array_map(function($x){ unset($x['score']); return $x; }, $scoredNotes), 0, 3);
                // Merge: keep existing vector results first, then append note snippets up to total cap
                $kbSnippets = array_values(array_slice(array_merge($kbSnippets, $dbNoteSnippets), 0, 5));
                // Document chunks (heuristic ranking by term frequency)
                $chunks = KnowledgeChunk::query()
                    ->whereHas('document', function($w){
                        $w->where('status','ready')
                          ->where(function($v){ $v->where('visibility','public'); if(auth()->check()) $v->orWhere('user_id', auth()->id()); });
                    })
                    ->limit(150)
                    ->get(['id','document_id','chunk_index','text']);
                $terms = $tokens;
                $scored = [];
                foreach ($chunks as $c) {
                    $score = 0; $lt = mb_strtolower($c->text);
                    foreach ($terms as $t) { if ($t !== '' && mb_strpos($lt, $t) !== false) { $score++; } }
                    if ($score > 0) { $scored[] = ['chunk'=>$c, 'score'=>$score]; }
                }
                usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
                $top = array_slice($scored, 0, 3);
                if ($top) {
                    $docMap = KnowledgeDocument::whereIn('id', array_unique(array_map(fn($x)=>$x['chunk']->document_id, $top)))->get(['id','title','updated_at'])->keyBy('id');
                    foreach ($top as $row) {
                        $ch = $row['chunk'];
                        $doc = $docMap[$ch->document_id] ?? null;
                        $title = $doc?->title ?: ('Dokument #'.$ch->document_id);
                        $kbSnippets[] = [
                            'id' => $ch->document_id.'#'.$ch->chunk_index,
                            'title' => $title.' – část '.$ch->chunk_index,
                            'snippet' => trim(mb_substr(strip_tags($ch->text), 0, 800)),
                            'updated_at' => (string)($doc?->updated_at ?: now()),
                        ];
                    }
                    // Keep total cap on snippets
                    $kbSnippets = array_values(array_slice($kbSnippets, 0, 5));
                }
                // Heuristic: prefer KB for company-level/leadership contact queries
                $ltQuery = mb_strtolower($q);
                $hasContactIntent = (mb_strpos($ltQuery, 'kontakt') !== false || mb_strpos($ltQuery, 'kontakty') !== false)
                    && (mb_strpos($ltQuery, 'esl') !== false);
                $isLeadership = (mb_strpos($ltQuery, 'vedení') !== false) || (mb_strpos($ltQuery, 'management') !== false) || (mb_strpos($ltQuery, 'ředitel') !== false) || (mb_strpos($ltQuery, 'statutární') !== false) || (mb_strpos($ltQuery, 'statutarni') !== false);
                $kb0 = $kbSnippets[0]['full'] ?? ($kbSnippets[0]['snippet'] ?? '');
                $kbHasGeneral = (mb_stripos($kb0, 'obecné kontakty') !== false) || (mb_stripos($kb0, 'telefon:') !== false) || (mb_stripos($kb0, 'e‑mail:') !== false) || (mb_stripos($kb0, 'email:') !== false);
                $kbHasLeads = (mb_stripos($kb0, 'vedení společnosti') !== false) || (mb_stripos($kb0, 'statutární ředitel') !== false) || (mb_stripos($kb0, 'výkonný ředitel') !== false) || (mb_stripos($kb0, 'režitel') !== false);
                if ($hasContactIntent && ($kbHasGeneral || $isLeadership || $kbHasLeads)) {
                    $preferKb = true;
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

    if (!empty($kbSnippets)) {
            $systemPrompt .= "\n\nZnalostní poznámky (interní citace, pokud relevantní):\n";
            foreach ($kbSnippets as $i => $k) {
                $systemPrompt .= sprintf("[%d] %s — %s\n%s\n", $k['id'], $k['title'], $k['updated_at'], mb_substr($k['snippet'],0,800));
            }
        }

    // Prepare meta for UI (confirmation flag and extracted entities)
    if ($preferKb) { $isLookup = false; }
    $usedProvider = $preferKb ? 'deterministic' : ($isLookup ? 'deterministic' : $provider);
    $usedModel = $preferKb ? 'knowledge-v1' : ($isLookup ? 'contacts-v1' : ($provider === 'openrouter' ? (string) SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free') : ($provider === 'gemini' ? (string) SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash') : '')));
    $meta = [
            'requires_confirmation' => (bool)($plan['requires_confirmation'] ?? false),
            'entities' => [
                'emails' => $plan['entities']['emails'] ?? [],
                'phones' => $plan['entities']['phones'] ?? [],
            ],
            'links' => $links,
            'found' => [ 'contacts' => $foundContacts ],
            'diagnostics' => [
    'provider' => $provider,
    'model' => $provider === 'openrouter' ? (string) SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free') : ($provider === 'gemini' ? (string) SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash') : ''),
    'used' => $usedProvider,
    'used_model' => $usedModel,
    'deterministic' => $preferKb || $isLookup,
                'badges_enabled' => SystemSetting::get('chat.show_diag_badges', '0') === '1',
                'assist' => (bool) $assistUsed,
                'assist_term' => $assistTerm,
                'links_same_tab' => SystemSetting::get('chat.links_same_tab', '1') === '1',
        'system_prompt' => $systemPrompt,
            ],
            'knowledge' => $kbSnippets,
            'user_text' => $userText,
        ];

        // Strict verified context (JSON) to reduce hallucinations
        $verifiedContext = [
            'contacts' => array_map(function($c){
                return [
                    'id' => $c['id'] ?? null,
                    'name' => $c['name'] ?? null,
                    'email' => $c['email'] ?? null,
                    'phone' => $c['phone'] ?? null,
                    'url' => $c['url'] ?? null,
                ];
            }, $foundContacts)
        ];
    $contextJson = json_encode($verifiedContext, JSON_UNESCAPED_UNICODE);
    $meta['diagnostics']['context_json'] = $contextJson;

    // Precomputed progress steps already done before streaming begins
        $pre = [];
        $pre[] = ['stage' => 'init', 'status' => 'end', 'label' => 'Zpracovávám dotaz'];
        $pre[] = ['stage' => 'planner', 'status' => 'end', 'label' => 'Plánuji postup'];
        if (config('qdrant.enabled')) {
            $pre[] = ['stage' => 'qdrant', 'status' => 'end', 'label' => 'Dívám se do Qdrant DB'];
        }
        if (!empty($kbSnippets)) {
            $pre[] = ['stage' => 'knowledge', 'status' => 'end', 'label' => 'Načítám interní znalosti'];
        }
        if ($assistUsed) {
            $pre[] = ['stage' => 'assist', 'status' => 'end', 'label' => 'Pomáhám si LLM pro klíčová slova'];
        }
        if ($isLookup) {
            $pre[] = ['stage' => 'crm', 'status' => 'end', 'label' => 'Kontroluji CRM'];
        }
        $meta['progress_pre'] = $pre;

        // If user explicitly asks for web content (phrases or a URL/domain), but Playwright is disabled,
        // return a clear notice instead of falling back to CRM/KB.
        try {
            $ltIntent = mb_strtolower($userText);
            $explicitWebPhrase = (mb_strpos($ltIntent, 'webu') !== false) || (mb_strpos($ltIntent, 'internetu') !== false) || (mb_strpos($ltIntent, 'ověř') !== false) || (mb_strpos($ltIntent, 'over') !== false) || (mb_strpos($ltIntent, 'zjisti') !== false) || (mb_strpos($ltIntent, 'vyhledej') !== false) || (mb_strpos($ltIntent, 'jdi na') !== false) || (mb_strpos($ltIntent, 'otevři') !== false) || (mb_strpos($ltIntent, 'navštiv') !== false);
            $mentionsLink = (bool) preg_match('#https?://[^\s]+#iu', $userText) || (bool) preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText);
            $explicitWebIntent = $explicitWebPhrase || $mentionsLink;
            if (!$pwEnabledFlag && $explicitWebIntent) {
                return $this->streamWebDisabledNotice($userText, $messageId, $assistantMessageId, $sessionId, $meta);
            }
            // Save flags for later decisions
            $meta['diagnostics']['explicit_web_intent'] = (bool)$explicitWebIntent;
            $meta['diagnostics']['mentions_link'] = (bool)$mentionsLink;
        } catch (\Throwable $e) { /* ignore */ }

        // Early override: if uživatel explicitně zmíní URL/doménu povolenou v Playwright nastavení,
        // upřednostni web scraping hned (před deterministickými návraty z CRM/KG).
        try {
            if ($pwEnabledFlag) {
                $allowedCsvEarly = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
                $allowedEarly = array_values(array_filter(array_map('trim', explode(',', $allowedCsvEarly))));
                if (empty($allowedEarly)) { $allowedEarly = ['esl.cz']; }
                $allowAllEarly = in_array('*', $allowedEarly, true);
                $timeoutEarly = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
                $mentionedUrl = null; $mentionedDomain = null; $targetUrlEarly = null;
                if (preg_match('#https?://[^\s]+#iu', $userText, $mUrl)) { $mentionedUrl = $mUrl[0]; }
                if (!$mentionedUrl && preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText, $mDom)) {
                    $mentionedDomain = mb_strtolower($mDom[1]);
                }
                $isAllowedEarly = function(string $host) use ($allowedEarly, $allowAllEarly): bool {
                    if ($allowAllEarly) { return true; }
                    $host = preg_replace('/^www\./i','', $host);
                    foreach ($allowedEarly as $rule) {
                        $rule = trim($rule); if ($rule === '') continue;
                        $rule = preg_replace('/^\*\.?/','', $rule);
                        if ($host === $rule || str_ends_with($host, '.'.$rule)) { return true; }
                    }
                    return false;
                };
                if ($mentionedUrl) {
                    $host = parse_url($mentionedUrl, PHP_URL_HOST) ?: '';
                    $host = preg_replace('/^www\./i','', (string)$host);
                    if ($host && $isAllowedEarly($host)) { $targetUrlEarly = $mentionedUrl; }
                } elseif ($mentionedDomain && $isAllowedEarly($mentionedDomain)) {
                    $targetUrlEarly = 'https://'.$mentionedDomain;
                }
                if ($targetUrlEarly) {
                    return $this->streamWebAgentLLM($provider, $userText, $allowedEarly, $timeoutEarly, $messageId, $assistantMessageId, $sessionId, $meta, $targetUrlEarly);
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        // If explicit web intent is requested and Playwright is enabled, handle it BEFORE deterministic CRM/KB
        try {
            if ($pwEnabledFlag && ($meta['diagnostics']['explicit_web_intent'] ?? false)) {
                $allowedCsv = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
                $allowed = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
                if (empty($allowed)) { $allowed = ['esl.cz']; }
                $allowAll = in_array('*', $allowed, true);
                $timeout = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
                // Try to resolve target from explicit mention
                $mentionedUrl = null; $mentionedDomain = null; $targetUrlForce = null; $domainMentionedButNotAllowed = null;
                if (preg_match('#https?://[^\s]+#iu', $userText, $mUrl)) { $mentionedUrl = $mUrl[0]; }
                if (!$mentionedUrl && preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText, $mDom)) { $mentionedDomain = mb_strtolower($mDom[1]); }
                $normalizeHost = function(string $host){ return preg_replace('/^www\./i','', $host); };
                $isAllowed = function(string $host) use ($allowed, $normalizeHost, $allowAll): bool {
                    if ($allowAll) { return true; }
                    $host = $normalizeHost($host);
                    foreach ($allowed as $rule) {
                        $rule = trim($rule); if ($rule === '') continue;
                        $ruleNorm = $normalizeHost(preg_replace('/^\*\.?/','', $rule));
                        if ($host === $ruleNorm || str_ends_with($host, '.'.$ruleNorm)) { return true; }
                    }
                    return false;
                };
                $mapToAllowedVariant = function(string $domain) use ($allowed, $normalizeHost, $allowAll): ?string {
                    $base = $normalizeHost($domain);
                    if ($allowAll) { return 'https://'.$base; }
                    // exact allowed
                    foreach ($allowed as $rule) {
                        $ruleNorm = $normalizeHost(preg_replace('/^\*\.?/','', $rule));
                        if ($base === $ruleNorm || str_ends_with($ruleNorm, '.'.$base) || str_ends_with($base, '.'.$ruleNorm)) {
                            // Prefer the allowed form (keeps www if present)
                            return 'https://'.($ruleNorm === $base && stripos($rule, 'www.') === 0 ? 'www.'.$base : $ruleNorm);
                        }
                    }
                    // fallback: try www.
                    return 'https://www.'.$base;
                };
                if ($mentionedUrl) {
                    $host = $normalizeHost(parse_url($mentionedUrl, PHP_URL_HOST) ?: '');
                    if ($host) {
                        if ($isAllowed($host)) { $targetUrlForce = $mentionedUrl; }
                        else { $domainMentionedButNotAllowed = $host; }
                    }
                } elseif ($mentionedDomain) {
                    $host = $normalizeHost($mentionedDomain);
                    if ($isAllowed($host)) { $targetUrlForce = 'https://'.$host; }
                    else {
                        // Try mapping to an allowed variant (e.g., www.esl.cz)
                        $mapped = $mapToAllowedVariant($host);
                        if ($mapped) {
                            $mHost = $normalizeHost(parse_url($mapped, PHP_URL_HOST) ?: '');
                            if ($isAllowed($mHost)) { $targetUrlForce = $mapped; }
                            else { $domainMentionedButNotAllowed = $host; }
                        } else { $domainMentionedButNotAllowed = $host; }
                    }
                }
                if ($targetUrlForce) {
                    return $this->streamWebAgentLLM($provider, $userText, $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta, $targetUrlForce);
                }
                if ($domainMentionedButNotAllowed) {
                    return $this->streamWebDomainNotAllowedNotice($domainMentionedButNotAllowed, $messageId, $assistantMessageId, $sessionId, $meta);
                }
                // No explicit domain – use a sensible default within allowed list (prefer esl.cz)
                $default = null;
                foreach ($allowed as $dom) {
                    $norm = $normalizeHost(preg_replace('/^\*\.?/','', $dom));
                    if ($norm === 'esl.cz') { $default = 'https://www.esl.cz/'; break; }
                    if (!$default && $norm) { $default = 'https://'.$norm.'/'; }
                }
                if ($default) {
                    return $this->streamWebAgentLLM($provider, $userText, $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta, $default);
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        // Final guard: if explicit web intent is set and Playwright is enabled, route to web agent before deterministic paths
        if ($pwEnabledFlag && ($meta['diagnostics']['explicit_web_intent'] ?? false)) {
            $allowedCsv = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
            $allowed = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
            if (empty($allowed)) { $allowed = ['esl.cz']; }
            $allowAll = in_array('*', $allowed, true);
            $timeout = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
            $target = null;
            // Prefer explicitly mentioned URL/domain in the user text
            $mentionedUrl = null; $mentionedDomain = null;
            if (preg_match('#https?://[^\s]+#iu', $userText, $mUrl)) { $mentionedUrl = $mUrl[0]; }
            if (!$mentionedUrl && preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText, $mDom)) { $mentionedDomain = mb_strtolower($mDom[1]); }
            $normalize = function(string $h){ return preg_replace('/^www\./i','', $h); };
            $isAllowed = function(string $host) use ($allowed, $normalize, $allowAll){
                if ($allowAll) return true;
                $host = $normalize($host);
                foreach ($allowed as $rule){ $rule = preg_replace('/^\*\.?/','', trim($rule)); if($rule==='') continue; $rn = $normalize($rule); if ($host === $rn || str_ends_with($host, '.'.$rn)) return true; }
                return false;
            };
            if ($mentionedUrl) {
                $h = parse_url($mentionedUrl, PHP_URL_HOST) ?: '';
                if ($h && $isAllowed($h)) { $target = $mentionedUrl; }
            } elseif ($mentionedDomain) {
                if ($isAllowed($mentionedDomain)) { $target = 'https://'.$mentionedDomain; }
            }
            // Fallback: first allowed domain or sensible default
            if (!$target) {
                if ($allowAll) {
                    // Bezpečný default – zkusíme esl.cz, pokud bylo v dotazu, jinak první podobná doména
                    if ($mentionedDomain) { $target = 'https://'.$mentionedDomain; }
                    else { $target = 'https://www.esl.cz/'; }
                } else {
                    foreach ($allowed as $dom) {
                        $dom = preg_replace('/^\*\.?/','', trim($dom)); if(!$dom) continue;
                        $target = 'https://'.(stripos($dom,'www.')===0 ? $dom : 'www.'.$dom).'/'; break;
                    }
                }
            }
            if ($target) {
                return $this->streamWebAgentLLM($provider, $userText, $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta, $target);
            }
        }

        // Deterministic path for knowledge-preferred queries
    if ($preferKb && !empty($kbSnippets)) {
            return $this->streamDeterministicKnowledge($kbSnippets, $messageId, $assistantMessageId, $sessionId, $meta);
        }

    // Deterministic path for pure contact lookups to avoid hallucinations
        if ($isLookup) {
            if (!empty($foundContacts)) {
                return $this->streamDeterministicContacts($foundContacts, $messageId, $assistantMessageId, $sessionId, $meta);
            } else {
                return $this->streamDeterministicNotFound($messageId, $assistantMessageId, $sessionId, $meta);
            }
        }

        // Heuristické využití webového nástroje (Playwright) pro aktuální informace
        try {
            if ($pwEnabledFlag) {
                $lt = mb_strtolower($userText);
                $wantsWeb = (mb_strpos($lt, 'na webu') !== false) || (mb_strpos($lt, 'ověř') !== false) || (mb_strpos($lt, 'over') !== false) || (mb_strpos($lt, 'zjisti z webu') !== false) || (mb_strpos($lt, 'vyhledej') !== false) || (mb_strpos($lt, 'na internetu') !== false) || (mb_strpos($lt, 'webu') !== false);
                $mentionsEsl = (mb_strpos($lt, 'esl') !== false) || (mb_strpos($lt, 'esl a.s') !== false);
                $wantsContacts = (mb_strpos($lt, 'kontakt') !== false) || (mb_strpos($lt, 'kontakty') !== false);
                $wantsPricing = (mb_strpos($lt, 'cena') !== false) || (mb_strpos($lt, 'ceník') !== false) || (mb_strpos($lt, 'cenik') !== false) || (mb_strpos($lt, 'kolik stojí') !== false);
                $wantsSpecs = (mb_strpos($lt, 'parametr') !== false) || (mb_strpos($lt, 'specifikace') !== false) || (mb_strpos($lt, 'technické') !== false) || (mb_strpos($lt, 'technicke') !== false);

                $allowedCsv = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
                $allowed = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
                $allowAllLate = in_array('*', $allowed, true);
                $timeout = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');

                // Pokud uživatel explicitně zmiňuje URL/doménu (např. "esl.cz"), preferuj Playwright hned,
                // i když už máme CRM/KG výsledky – jde o dotaz "z webu".
                $mentionedUrl = null; $mentionedDomain = null; $targetUrl = null;
                if (preg_match('#https?://[^\s]+#iu', $userText, $mUrl)) {
                    $mentionedUrl = $mUrl[0];
                }
                if (!$mentionedUrl && preg_match('/\b([a-z0-9.-]+\.(?:cz|com|net|org|io|sk|eu))\b/iu', $userText, $mDom)) {
                    $mentionedDomain = mb_strtolower($mDom[1]);
                }
                $isAllowed = function(string $host) use ($allowed, $allowAllLate): bool {
                    if ($allowAllLate) { return true; }
                    $host = preg_replace('/^www\./i','', $host);
                    foreach ($allowed as $rule) {
                        $rule = trim($rule); if ($rule === '') continue;
                        $rule = preg_replace('/^\*\.?/','', $rule);
                        if ($host === $rule || str_ends_with($host, '.'.$rule)) { return true; }
                    }
                    return false;
                };
                if ($mentionedUrl) {
                    $host = parse_url($mentionedUrl, PHP_URL_HOST) ?: '';
                    $host = preg_replace('/^www\./i','', (string)$host);
                    if ($host && $isAllowed($host)) {
                        $targetUrl = $mentionedUrl;
                    }
                } elseif ($mentionedDomain && $isAllowed($mentionedDomain)) {
                    $targetUrl = 'https://'.($mentionedDomain);
                }

                if ($targetUrl && ($wantsWeb || true)) { // explicit web zmínka má prioritu
                    return $this->streamViaPlaywright($targetUrl, $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta);
                }

                // If asking about ESL contacts, go straight to contacts page
                if ($mentionsEsl && $wantsContacts) {
                    return $this->streamViaPlaywright('https://www.esl.cz/kontakty', $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta);
                }
                // If user clearly wants web verification or pricing/specs and KB/CRM didn't satisfy
                if ((empty($kbSnippets) && empty($foundContacts) && ($wantsWeb || $wantsPricing || $wantsSpecs)) || ($wantsPricing || $wantsSpecs)) {
                    // Pick a sensible default target within allowed domains (prefer esl.cz)
                    $targetUrl = 'https://www.esl.cz/';
                    if ($allowAllLate) {
                        // Keep default as ESL, or we could choose nothing special
                    } else {
                        foreach ($allowed as $dom) {
                            $dom = preg_replace('/^\*\.?/','', $dom);
                            if ($dom && $dom !== 'esl.cz') { $targetUrl = 'https://'.$dom.'/'; break; }
                        }
                    }
                    return $this->streamViaPlaywright($targetUrl, $allowed, $timeout, $messageId, $assistantMessageId, $sessionId, $meta);
                }
            }
        } catch (\Throwable $e) { /* ignore and continue */ }

    if ($provider === 'openrouter') {
            $key = (string) SystemSetting::get('chat.openrouter_api_key', '');
            $model = (string) SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free');
            if ($key) {
                try {
            return $this->streamViaOpenRouter($key, $model, $systemPrompt, $userText, $messageId, $assistantMessageId, $sessionId, $meta, $contextJson);
                } catch (\Throwable $e) {
                    Log::error('OpenRouter stream error: '.$e->getMessage());
                    return $this->streamMockResponse($messageId);
                }
            }
        }

    if ($provider === 'gemini') {
            $key = (string) SystemSetting::get('chat.gemini_api_key', '');
            $model = (string) SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash');
            if ($key) {
                try {
            return $this->streamViaGemini($key, $model, $systemPrompt, $userText, $messageId, $assistantMessageId, $sessionId, $meta, $contextJson);
                } catch (\Throwable $e) {
                    Log::error('Gemini stream error: '.$e->getMessage());
                    return $this->streamMockResponse($messageId);
                }
            }
        }
        return $this->streamMockResponse($messageId);
    }

    protected function executeReadOnlyPlan(int $sessionId, int $sourceMessageId, array $plan): array|string|null
    {
        $noteParts = [];
        $links = [ 'contacts' => [] ];
        $foundContacts = [];
    $queriedEmails = [];
        try {
            $tool = new ContactsTool();
            // Partial search by text
            if (in_array('search_contact_by_text', $plan['actions'] ?? [], true)) {
                foreach ($plan['entities']['partial'] ?? [] as $q) {
                    $rows = $tool->searchByText($q, 5);
                    DB::table('chat_actions')->insert([
                        'session_id' => $sessionId,
                        'message_id' => $sourceMessageId,
                        'tool_name' => 'contacts.searchByText',
                        'inputs' => json_encode(['q' => $q], JSON_UNESCAPED_UNICODE),
                        'outputs' => json_encode(['count' => count($rows)], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('chat_messages')->insert([
                        'session_id' => $sessionId,
                        'role' => 'tool',
                        'content' => json_encode(['tool' => 'contacts.searchByText', 'input' => ['q' => $q], 'result' => $rows], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    foreach ($rows as $r) {
                        $name = trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? ''));
                        $abs = URL::to('/crm/contacts/'.$r['id']);
                        $foundContacts[] = [
                            'id' => $r['id'],
                            'name' => $name ?: null,
                            'email' => $r['email'] ?? null,
                            'phone' => $r['phone'] ?? null,
                            'url' => $abs,
                        ];
                        $links['contacts'][] = [ 'id' => $r['id'], 'url' => $abs ];
                    }
                    if ($rows) {
                        $noteParts[] = 'Výsledky podle textu "'.$q.'": '.count($rows).' shod.';
                    }
                }
            }
            if (in_array('lookup_contact_by_email', $plan['actions'] ?? [], true)) {
                foreach ($plan['entities']['emails'] ?? [] as $email) {
                    $queriedEmails[] = $email;
                    $found = $tool->findByEmail($email);
                    if ($found) {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $sourceMessageId,
                            'tool_name' => 'contacts.findByEmail',
                            'inputs' => json_encode(['email' => $email], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'contacts.findByEmail', 'input' => ['email' => $email], 'result' => ['id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null]], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $noteParts[] = 'Kontakt dle e-mailu: ID='.$found['id'].'; jméno='.$found['name'].'; email='.$found['email'];
                        $abs = URL::to('/crm/contacts/'.$found['id']);
                        $links['contacts'][] = [ 'id' => $found['id'], 'url' => $abs ];
                        $foundContacts[] = [ 'id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null, 'phone' => $found['phone'] ?? null, 'url' => $abs ];
                    } else {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $sourceMessageId,
                            'tool_name' => 'contacts.findByEmail',
                            'inputs' => json_encode(['email' => $email], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['id' => null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'contacts.findByEmail', 'input' => ['email' => $email], 'result' => null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            if (in_array('lookup_contact_by_phone', $plan['actions'] ?? [], true)) {
                foreach ($plan['entities']['phones'] ?? [] as $phone) {
                    $found = $tool->findByPhone($phone);
                    if ($found) {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $sourceMessageId,
                            'tool_name' => 'contacts.findByPhone',
                            'inputs' => json_encode(['phone' => $phone], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['id' => $found['id'], 'name' => $found['name'] ?? null, 'phone' => $found['phone'] ?? null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'contacts.findByPhone', 'input' => ['phone' => $phone], 'result' => ['id' => $found['id'], 'name' => $found['name'] ?? null, 'phone' => $found['phone'] ?? null]], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $noteParts[] = 'Kontakt dle telefonu: ID='.$found['id'].'; jméno='.$found['name'].'; telefon='.$found['phone'];
                        $abs = URL::to('/crm/contacts/'.$found['id']);
                        $links['contacts'][] = [ 'id' => $found['id'], 'url' => $abs ];
                        $foundContacts[] = [ 'id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null, 'phone' => $found['phone'] ?? null, 'url' => $abs ];
                    } else {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $sourceMessageId,
                            'tool_name' => 'contacts.findByPhone',
                            'inputs' => json_encode(['phone' => $phone], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['id' => null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'contacts.findByPhone', 'input' => ['phone' => $phone], 'result' => null], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('executeReadOnlyPlan failed: '.$e->getMessage());
        }
        // Fallback: if email lookup yielded nothing, try a text search with the same email string(s)
        if (empty($foundContacts) && !empty($queriedEmails)) {
            try {
                $tool = new ContactsTool();
                foreach ($queriedEmails as $q) {
                    $rows = $tool->searchByText($q, 5);
                    if ($rows) {
                        DB::table('chat_actions')->insert([
                            'session_id' => $sessionId,
                            'message_id' => $sourceMessageId,
                            'tool_name' => 'contacts.searchByText',
                            'inputs' => json_encode(['q' => $q], JSON_UNESCAPED_UNICODE),
                            'outputs' => json_encode(['count' => count($rows)], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('chat_messages')->insert([
                            'session_id' => $sessionId,
                            'role' => 'tool',
                            'content' => json_encode(['tool' => 'contacts.searchByText', 'input' => ['q' => $q], 'result' => $rows], JSON_UNESCAPED_UNICODE),
                            'status' => 'done',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        foreach ($rows as $r) {
                            $name = trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? ''));
                            $abs = \Illuminate\Support\Facades\URL::to('/crm/contacts/'.$r['id']);
                            $foundContacts[] = [
                                'id' => $r['id'],
                                'name' => $name ?: null,
                                'email' => $r['email'] ?? null,
                                'phone' => $r['phone'] ?? null,
                                'url' => $abs,
                            ];
                            $links['contacts'][] = [ 'id' => $r['id'], 'url' => $abs ];
                        }
                        $noteParts[] = 'Přesná shoda e‑mailu nenalezena, zobrazuji související výsledky podle textu.';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('fallback searchByText failed: '.$e->getMessage());
            }
        }
    $note = empty($noteParts) ? null : implode("\n", $noteParts);
    // If nothing found, return only note (possibly null) to keep BC
    if (!$note && empty($links['contacts'])) { return null; }
    return [ 'note' => $note, 'links' => $links, 'found' => [ 'contacts' => $foundContacts ] ];
    }

    protected function streamViaOpenRouter(string $apiKey, string $model, string $systemPrompt, string $userText, int $messageId, int $assistantMessageId, int $sessionId, array $meta = [], ?string $contextJson = null): StreamedResponse
    {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        if ($contextJson) {
            $messages[] = ['role' => 'system', 'content' => "OVĚŘENÝ KONTEXT (JSON) – používej pouze tyto údaje, jinak napiš, že nebylo nalezeno:\n".$contextJson];
        }
        $messages[] = ['role' => 'user', 'content' => $userText];
        $payload = [ 'model' => $model, 'stream' => true, 'messages' => $messages ];

        $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
            ])
            ->withOptions(['stream' => true])
            ->post($url, $payload);

        if (!$response->ok()) {
            throw new \RuntimeException('OpenRouter HTTP error: '.$response->status());
        }

    return new StreamedResponse(function () use ($response, $messageId, $assistantMessageId, $sessionId, $meta, $model) {
            $t0 = microtime(true);
            $ttft = null;
            // Emit meta event once at the beginning
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            // Emit debug payload with what goes to the LLM (masked/truncated)
            try {
                $dbg = $meta;
                $dbg['debug'] = $dbg['debug'] ?? [];
                $dbg['debug']['llm'] = [
                    'system' => mb_substr($meta['diagnostics']['system_prompt'] ?? '', 0, 1200),
                    'context_json' => isset($meta['diagnostics']['context_json']) ? mb_substr($meta['diagnostics']['context_json'], 0, 2000) : null,
                    'user' => mb_substr($meta['user_text'] ?? '', 0, 1000),
                ];
                echo "event: meta\n";
                echo 'data: ' . json_encode($dbg, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            } catch (\Throwable $e) { /* ignore */ }
            // Progress: LLM (OpenRouter)
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'start', 'label' => 'Komunikuji s LLM (OpenRouter)'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            $body = $response->toPsrResponse()->getBody();
            $aggregated = '';
            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '' || $chunk === false) { usleep(20000); continue; }
                // SSE frames may contain multiple lines
                $lines = preg_split("/\r?\n/", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, ':')) continue; // comments/keepalive
                    if (stripos($line, 'data:') === 0) {
                        $data = trim(substr($line, 5));
                        if ($data === '[DONE]') {
                            // Before finishing, if we have exactly one contact link, append it visibly
                            if (!empty($meta['links']['contacts']) && count($meta['links']['contacts']) === 1) {
                                $url = $meta['links']['contacts'][0]['url'] ?? null;
                                if ($url) {
                                    $suffix = "\n\nOdkaz na detail kontaktu: " . $url;
                                    $aggregated .= $suffix;
                                    echo "event: delta\n";
                                    echo 'data: ' . json_encode(['text' => $suffix], JSON_UNESCAPED_UNICODE) . "\n\n";
                                    @ob_flush(); @flush();
                                }
                            }
                            echo "event: done\n";
                            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
                            @ob_flush(); @flush();
                            // Progress end
                            echo "event: progress\n";
                            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
                            @ob_flush(); @flush();
                            // persist final content
                            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                                'content' => $aggregated,
                                'status' => 'done',
                                'updated_at' => now(),
                            ]);
                            // metrics
                            $durationMs = (int) round((microtime(true) - $t0) * 1000);
                            $ttftMs = $ttft !== null ? (int) round($ttft * 1000) : null;
                            DB::table('chat_actions')->insert([
                                'session_id' => $sessionId,
                                'message_id' => $messageId,
                                'tool_name' => 'metrics.stream',
                                'inputs' => json_encode(['provider' => 'openrouter', 'model' => $model, 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                                'outputs' => json_encode(['duration_ms' => $durationMs, 'ttft_ms' => $ttftMs, 'chars' => strlen($aggregated)], JSON_UNESCAPED_UNICODE),
                                'status' => 'done',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            return;
                        }
                        $json = json_decode($data, true);
                        $delta = $json['choices'][0]['delta']['content'] ?? ($json['choices'][0]['message']['content'] ?? '');
                        if ($delta !== '') {
                            $aggregated .= $delta;
                            if ($ttft === null) { $ttft = microtime(true) - $t0; }
                            echo "event: delta\n";
                            echo 'data: ' . json_encode(['text' => $delta], JSON_UNESCAPED_UNICODE) . "\n\n";
                            @ob_flush(); @flush();
                        }
                    }
                }
            }
            // Fallback: if stream ended without [DONE]
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $aggregated,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            $ttftMs = $ttft !== null ? (int) round($ttft * 1000) : null;
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'metrics.stream',
                'inputs' => json_encode(['provider' => 'openrouter', 'model' => $model, 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['duration_ms' => $durationMs, 'ttft_ms' => $ttftMs, 'chars' => strlen($aggregated)], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function streamDeterministicContacts(array $contacts, int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        // Build a concise, factual answer strictly from CRM data
        $lines = [];
        if (count($contacts) === 1) {
            $c = $contacts[0];
            $lines[] = 'Podle CRM byl nalezen následující kontakt:';
            $lines[] = '';
            if (!empty($c['name'])) { $lines[] = '**Jméno:** ' . $c['name']; }
            if (!empty($c['email'])) { $lines[] = '**E‑mail:** ' . $c['email']; }
            if (!empty($c['phone'])) { $lines[] = '**Telefon:** ' . $c['phone']; }
            $lines[] = 'Zdroj: CRM, ID: ' . ($c['id'] ?? '—');
            if (!empty($c['url'])) { $lines[] = 'Odkaz na detail: ' . $c['url']; }
        } else {
            $lines[] = 'Podle CRM bylo nalezeno více kontaktů:';
            foreach ($contacts as $c) {
                $label = trim(($c['name'] ?? '') . ' ' . (($c['email'] ?? '') ? '(' . $c['email'] . ')' : ''));
                $label = $label !== '' ? $label : ('ID ' . ($c['id'] ?? '?'));
                $url = $c['url'] ?? URL::to('/crm/contacts/' . ($c['id'] ?? ''));
                $lines[] = '- ' . $label . ' – ' . $url;
            }
            $lines[] = 'Zdroj: CRM.';
        }
        $text = implode("\n", $lines);

        return new StreamedResponse(function () use ($messageId, $assistantMessageId, $sessionId, $meta, $text) {
            // Emit meta
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            // Progress: CRM
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'crm', 'status' => 'start', 'label' => 'Hledám v CRM'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            // Stream as a single delta for simplicity
            echo "event: delta\n";
            echo 'data: ' . json_encode(['text' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            // Progress end
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'crm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();

            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();

            // Persist and log metrics
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $text,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'metrics.stream',
                'inputs' => json_encode(['provider' => 'deterministic', 'model' => 'contacts-v1', 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['duration_ms' => 0, 'ttft_ms' => 0, 'chars' => strlen($text)], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function streamDeterministicNotFound(int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        $text = "V CRM nebyl nalezen žádný odpovídající kontakt.\nZdroj: CRM.";
        return new StreamedResponse(function () use ($messageId, $assistantMessageId, $sessionId, $meta, $text) {
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'crm', 'status' => 'start', 'label' => 'Hledám v CRM'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: delta\n";
            echo 'data: ' . json_encode(['text' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'crm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $text,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'metrics.stream',
                'inputs' => json_encode(['provider' => 'deterministic', 'model' => 'contacts-v1', 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['duration_ms' => 0, 'ttft_ms' => 0, 'chars' => strlen($text)], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function streamViaGemini(string $apiKey, string $model, string $systemPrompt, string $userText, int $messageId, int $assistantMessageId, int $sessionId, array $meta = [], ?string $contextJson = null): StreamedResponse
    {
        // SSE endpoint
        $base = 'https://generativelanguage.googleapis.com/v1beta/models/'.urlencode($model).':streamGenerateContent?alt=sse&key='.urlencode($apiKey);
        $contextBlock = $contextJson ? ("\n\nOVĚŘENÝ KONTEXT (JSON) – používej pouze tyto údaje, jinak napiš, že nebylo nalezeno:\n".$contextJson) : '';
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [ ['text' => $systemPrompt.$contextBlock."\n\nUživatel: ".$userText] ]],
            ],
        ];

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
            ])
            ->withOptions(['stream' => true])
            ->post($base, $payload);

        if (!$response->ok()) {
            throw new \RuntimeException('Gemini HTTP error: '.$response->status());
        }

    return new StreamedResponse(function () use ($response, $messageId, $assistantMessageId, $sessionId, $meta, $model) {
            $t0 = microtime(true);
            $ttft = null;
            // Emit meta event once at the beginning
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            // Emit debug payload with what goes to the LLM (masked/truncated)
            try {
                $dbg = $meta;
                $dbg['debug'] = $dbg['debug'] ?? [];
                $dbg['debug']['llm'] = [
                    'system' => mb_substr($meta['diagnostics']['system_prompt'] ?? '', 0, 1200),
                    'context_json' => isset($meta['diagnostics']['context_json']) ? mb_substr($meta['diagnostics']['context_json'], 0, 2000) : null,
                    'user' => mb_substr($meta['user_text'] ?? '', 0, 1000),
                ];
                echo "event: meta\n";
                echo 'data: ' . json_encode($dbg, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            } catch (\Throwable $e) { /* ignore */ }
            // Progress: LLM (Gemini)
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'start', 'label' => 'Komunikuji s LLM (Gemini)'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            $body = $response->toPsrResponse()->getBody();
            $aggregated = '';
            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '' || $chunk === false) { usleep(20000); continue; }
                $lines = preg_split("/\r?\n/", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, ':')) continue;
                    if (stripos($line, 'data:') === 0) {
                        $data = trim(substr($line, 5));
                        if ($data === '[DONE]') {
                            // Before finishing, if we have exactly one contact link, append it visibly
                            if (!empty($meta['links']['contacts']) && count($meta['links']['contacts']) === 1) {
                                $url = $meta['links']['contacts'][0]['url'] ?? null;
                                if ($url) {
                                    $suffix = "\n\nOdkaz na detail kontaktu: " . $url;
                                    $aggregated .= $suffix;
                                    echo "event: delta\n";
                                    echo 'data: ' . json_encode(['text' => $suffix], JSON_UNESCAPED_UNICODE) . "\n\n";
                                    @ob_flush(); @flush();
                                }
                            }
                            echo "event: done\n";
                            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
                            @ob_flush(); @flush();
                            echo "event: progress\n";
                            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
                            @ob_flush(); @flush();
                            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                                'content' => $aggregated,
                                'status' => 'done',
                                'updated_at' => now(),
                            ]);
                            $durationMs = (int) round((microtime(true) - $t0) * 1000);
                            $ttftMs = $ttft !== null ? (int) round($ttft * 1000) : null;
                            DB::table('chat_actions')->insert([
                                'session_id' => $sessionId,
                                'message_id' => $messageId,
                                'tool_name' => 'metrics.stream',
                                'inputs' => json_encode(['provider' => 'gemini', 'model' => $model, 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                                'outputs' => json_encode(['duration_ms' => $durationMs, 'ttft_ms' => $ttftMs, 'chars' => strlen($aggregated)], JSON_UNESCAPED_UNICODE),
                                'status' => 'done',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            return;
                        }
                        $json = json_decode($data, true);
                        // Streaming chunks may contain candidates[0].content.parts[].text
                        $delta = '';
                        if (isset($json['candidates'][0]['content']['parts'])) {
                            foreach ($json['candidates'][0]['content']['parts'] as $p) {
                                if (isset($p['text']) && $p['text'] !== '') {
                                    $delta .= $p['text'];
                                }
                            }
                        }
                        if ($delta !== '') {
                            $aggregated .= $delta;
                            if ($ttft === null) { $ttft = microtime(true) - $t0; }
                            echo "event: delta\n";
                            echo 'data: ' . json_encode(['text' => $delta], JSON_UNESCAPED_UNICODE) . "\n\n";
                            @ob_flush(); @flush();
                        }
                    }
                }
            }
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'llm', 'status' => 'end', 'label' => 'Dokončeno'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $aggregated,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            $ttftMs = $ttft !== null ? (int) round($ttft * 1000) : null;
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'metrics.stream',
                'inputs' => json_encode(['provider' => 'gemini', 'model' => $model, 'assistant_message_id' => $assistantMessageId], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['duration_ms' => $durationMs, 'ttft_ms' => $ttftMs, 'chars' => strlen($aggregated)], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function maybeLookupContact(int $sessionId, int $sourceMessageId, string $text): ?string
    {
        try {
            $tool = new ContactsTool();
            // Email regex (simple)
            if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m)) {
                $found = $tool->findByEmail($m[0]);
                if ($found) {
                    // Log action
                    DB::table('chat_actions')->insert([
                        'session_id' => $sessionId,
                        'message_id' => $sourceMessageId,
                        'tool_name' => 'contacts.findByEmail',
                        'inputs' => json_encode(['email' => $m[0]], JSON_UNESCAPED_UNICODE),
                        'outputs' => json_encode(['id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    // Add tool message (for audit trail)
                    DB::table('chat_messages')->insert([
                        'session_id' => $sessionId,
                        'role' => 'tool',
                        'content' => json_encode(['tool' => 'contacts.findByEmail', 'input' => ['email' => $m[0]], 'result' => ['id' => $found['id'], 'name' => $found['name'] ?? null, 'email' => $found['email'] ?? null]], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return 'Kontakt dle e-mailu: ID='.$found['id'].'; jméno='.$found['name'].'; email='.$found['email'];
                }
                // Not found – also audit
                DB::table('chat_actions')->insert([
                    'session_id' => $sessionId,
                    'message_id' => $sourceMessageId,
                    'tool_name' => 'contacts.findByEmail',
                    'inputs' => json_encode(['email' => $m[0]], JSON_UNESCAPED_UNICODE),
                    'outputs' => json_encode(['id' => null], JSON_UNESCAPED_UNICODE),
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('chat_messages')->insert([
                    'session_id' => $sessionId,
                    'role' => 'tool',
                    'content' => json_encode(['tool' => 'contacts.findByEmail', 'input' => ['email' => $m[0]], 'result' => null], JSON_UNESCAPED_UNICODE),
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // Phone regex (simple, picks +420 etc.)
            if (preg_match('/(?:(?:\+|00)\d{1,3})?[\s-]?\d{3}[\s-]?\d{3}[\s-]?\d{3,4}/', $text, $m)) {
                $phone = preg_replace('/\s|-/', '', $m[0]);
                $found = $tool->findByPhone($phone);
                if ($found) {
                    DB::table('chat_actions')->insert([
                        'session_id' => $sessionId,
                        'message_id' => $sourceMessageId,
                        'tool_name' => 'contacts.findByPhone',
                        'inputs' => json_encode(['phone' => $phone], JSON_UNESCAPED_UNICODE),
                        'outputs' => json_encode(['id' => $found['id'], 'name' => $found['name'] ?? null, 'phone' => $found['phone'] ?? null], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('chat_messages')->insert([
                        'session_id' => $sessionId,
                        'role' => 'tool',
                        'content' => json_encode(['tool' => 'contacts.findByPhone', 'input' => ['phone' => $phone], 'result' => ['id' => $found['id'], 'name' => $found['name'] ?? null, 'phone' => $found['phone'] ?? null]], JSON_UNESCAPED_UNICODE),
                        'status' => 'done',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return 'Kontakt dle telefonu: ID='.$found['id'].'; jméno='.$found['name'].'; telefon='.$found['phone'];
                }
                // Not found – also audit
                DB::table('chat_actions')->insert([
                    'session_id' => $sessionId,
                    'message_id' => $sourceMessageId,
                    'tool_name' => 'contacts.findByPhone',
                    'inputs' => json_encode(['phone' => $phone], JSON_UNESCAPED_UNICODE),
                    'outputs' => json_encode(['id' => null], JSON_UNESCAPED_UNICODE),
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('chat_messages')->insert([
                    'session_id' => $sessionId,
                    'role' => 'tool',
                    'content' => json_encode(['tool' => 'contacts.findByPhone', 'input' => ['phone' => $phone], 'result' => null], JSON_UNESCAPED_UNICODE),
                    'status' => 'done',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ContactsTool lookup failed: '.$e->getMessage());
        }
        return null;
    }
    public function streamMockResponse(int $messageId): StreamedResponse
    {
        $chunks = [
            'Zpracovávám dotaz…',
            ' Vyhledávám v kontaktech…',
            ' Nalezeno: 1 záznam.',
            ' Sestavuji odpověď…',
            ' Hotovo.'
        ];
        return new StreamedResponse(function () use ($messageId, $chunks) {
            foreach ($chunks as $chunk) {
                echo 'event: delta' . "\n";
                echo 'data: ' . json_encode(['text' => $chunk], JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush(); usleep(250000);
            }
            echo 'event: done' . "\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
        });
    }

    protected function streamWebDisabledNotice(string $userText, int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        $text = "Rozumím, že chcete ověřit informace na webu, ale webové procházení je aktuálně vypnuto.\n".
                "Požádejte prosím administrátora o zapnutí v Nastavení → Nástroje → Playwright, nebo upřesněte dotaz bez odkazu na web.";
        return new StreamedResponse(function () use ($messageId, $assistantMessageId, $sessionId, $meta, $text) {
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'start', 'label' => 'Webový dotaz'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: delta\n";
            echo 'data: ' . json_encode(['text' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'end', 'label' => 'Nelze – vypnuto'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $text,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'web.disabled',
                'inputs' => json_encode(['text' => mb_strimwidth($userText ?? '',0,200,'…')], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['notice' => true], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    protected function streamWebDomainNotAllowedNotice(string $domain, int $messageId, int $assistantMessageId, int $sessionId, array $meta = []): StreamedResponse
    {
        $text = "Požadovaný web (".$domain.") není povolen v konfiguraci.\n".
                "Přidejte prosím doménu do Nastavení → Nástroje → Playwright → Povolené domény (např. '".$domain."') a zkuste to znovu.";
        return new StreamedResponse(function () use ($messageId, $assistantMessageId, $sessionId, $meta, $text, $domain) {
            if (!empty($meta)) {
                echo "event: meta\n";
                echo 'data: ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'start', 'label' => 'Webový dotaz'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: delta\n";
            echo 'data: ' . json_encode(['text' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: progress\n";
            echo 'data: ' . json_encode(['stage' => 'playwright', 'status' => 'end', 'label' => 'Doména není povolena'], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush(); @flush();
            echo "event: done\n";
            echo 'data: ' . json_encode(['message_id' => $messageId, 'status' => 'ok']) . "\n\n";
            @ob_flush(); @flush();
            DB::table('chat_messages')->where('id', $assistantMessageId)->update([
                'content' => $text,
                'status' => 'done',
                'updated_at' => now(),
            ]);
            DB::table('chat_actions')->insert([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'tool_name' => 'web.domain_not_allowed',
                'inputs' => json_encode(['domain' => $domain], JSON_UNESCAPED_UNICODE),
                'outputs' => json_encode(['notice' => true], JSON_UNESCAPED_UNICODE),
                'status' => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
