<?php

namespace App\Services\Orders;

use App\Services\Orders\DTO\ListingOrderRowDTO;
use App\Services\Orders\DTO\OrderDetailDTO;
use Symfony\Component\HttpClient\HttpClient;
use App\Services\Orders\Exceptions\AuthException;
use App\Services\Orders\Exceptions\NetworkException;
use App\Services\Orders\Exceptions\ParseException;
use App\Services\Orders\Support\Retry;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;

class OrderScrapeClient
{
    protected HttpClientInterface $http;
    protected array $cookies = [];
    protected string $baseUrl;
    protected ?string $csrfToken = null;
    protected ?string $lastDetailHtml = null; // raw HTML of last fetched detail
    protected array $editIdMap = [];

    public function __construct(?HttpClientInterface $httpClient = null)
    {
        $this->baseUrl = rtrim(config('orders.base_url', config('app.url')), '/');
        $this->http = $httpClient ?? HttpClient::create([
            'headers' => [
                'User-Agent' => 'CRM OrdersSyncBot/1.0',
            ],
            'timeout' => 15,
        ]);
    }

    private function buildUrl(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function login(): void
    {
        $cookieBootstrap = env('ORDERS_SYNC_COOKIE');
    $user = env('ORDERS_SYNC_USER') ?: env('ORDERS_USER');
    $pass = env('ORDERS_SYNC_PASSWORD') ?: env('ORDERS_PASSWORD');
        // If explicit session cookie provided, use it and skip form login
        if ($cookieBootstrap && (!$user || !$pass)) {
            // Accept formats: "key=val; other=val2" OR raw single cookie
            $pairs = preg_split('~;\s*~', $cookieBootstrap) ?: [];
            foreach ($pairs as $p) {
                if (str_contains($p,'=')) {
                    [$k,$v] = explode('=',$p,2); $this->cookies[trim($k)] = trim($v);
                }
            }
            // Quick probe to verify access (GET listing)
            try {
                $resp = $this->http->request('GET', $this->buildUrl('/admin/order/'), [ 'headers'=>['Cookie'=>$this->cookieHeader()] ]);
                if ($resp->getStatusCode() >= 400) {
                    throw new AuthException('Session cookie invalid (HTTP '.$resp->getStatusCode().')');
                }
                return; // success using cookie
            } catch (\Throwable $e) {
                throw new AuthException('ORDERS_SYNC_COOKIE login failed: '.$e->getMessage(),0,$e);
            }
        }
        if (!$user || !$pass) {
            throw new \RuntimeException('Missing ORDERS_SYNC_USER / ORDERS_SYNC_PASSWORD (or provide ORDERS_SYNC_COOKIE)');
        }

        // Step 1: GET login page (actual form lives at /admin/ or /admin/sign/in)
        try {
            $resp = $this->http->request('GET', $this->buildUrl('/admin/')); // may redirect or show forwardLink form
    } catch (\Throwable $e) { throw new NetworkException('Login GET failed: '.$e->getMessage(),0,$e); }
        $html = $resp->getContent();
        $this->storeCookies($resp->getHeaders(false));
        $crawler = new Crawler($html);
        // Detect actual login form action and hidden token (Nette style forms may not use _token)
        $form = $crawler->filter('form.login-box');
        $action = '/admin/sign/in';
        if ($form->count()) { $actAttr = $form->attr('action'); if ($actAttr) { $action = $actAttr; } }
    // Hidden token field appears to be named _token_ (trailing underscore) in this admin
    $tokenInput = $crawler->filter('input[name=_token_], input[name=_token], input[name=csrf_token]');
    $token = $tokenInput->count() ? $tokenInput->attr('value') : null; // optional
    $doField = $crawler->filter('input[name=_do]')->count() ? $crawler->filter('input[name=_do]')->attr('value') : 'signInForm-submit';
        $this->csrfToken = $token;        

        // Step 2: POST credentials to detected action
        try {
            $postResp = $this->http->request('POST', $this->buildUrl($action), [
            'headers' => [
                'Cookie' => $this->cookieHeader(),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => http_build_query([
                '_token_' => $token,
                'username' => $user,
                'password' => $pass,
                '_do' => $doField,
            ])
            ]);
        } catch (\Throwable $e) { throw new NetworkException('Login POST failed: '.$e->getMessage(),0,$e); }
        $this->storeCookies($postResp->getHeaders(false));
        $status = $postResp->getStatusCode();
        if ($status >= 400) {
            throw new AuthException('Login failed HTTP '.$status);
        }
        // Quick auth verification: fetch listing; if still shows login form keyword, treat as auth failure
        try {
            $probe = $this->http->request('GET', $this->buildUrl('/admin/order/'), ['headers'=>['Cookie'=>$this->cookieHeader()]]);
            $body = $probe->getContent(false);
            if (str_contains($body,'Přihlaste se') && str_contains($body,'login-box')) {
                $debugDir = base_path('storage/app/orders-debug'); @mkdir($debugDir,0775,true);
                @file_put_contents($debugDir.'/login_failure_'.date('Ymd_His').'.html',$body);
                throw new AuthException('Login appears unsuccessful (login form still present)');
            }
        } catch (\Throwable $e) { throw new AuthException('Post-login verification failed: '.$e->getMessage(),0,$e); }
    }

    /**
     * @return ListingOrderRowDTO[]
     */
    public function fetchListingPage(int $page = 1): array
    {
        $variants = [
            '/admin/order/?grid-sort[created]=desc&grid-page='.$page,
            '/admin/order/?grid-page='.$page,
            '/admin/order/?page='.$page,
            '/admin/order/'.($page>1?('?page='.$page):''),
            '/admin/order' . ($page>1?('?page='.$page):'')
        ];
        $lastHtml = null; $rows = [];
        foreach ($variants as $urlPath) {
            try {
                $resp = Retry::run(function() use ($urlPath) {
                    return $this->http->request('GET', $this->buildUrl($urlPath), [
                        'headers' => [ 'Cookie' => $this->cookieHeader() ]
                    ]);
                }, attempts:2, baseDelayMs:150);
                $this->storeCookies($resp->getHeaders(false));
                $html = $resp->getContent(false);
                $lastHtml = $html;
                $crawler = new Crawler($html);
                // Strategy 1
                $rows = $this->parseListingTable($crawler);
                if (!empty($rows)) break;
                // Strategy 2
                $rows = $this->parseListingAnchors($crawler);
                if (!empty($rows)) break;
                // Strategy 3
                $rows = $this->fallbackParseListing($html);
                if (!empty($rows)) break;
            } catch (\Throwable $e) {
                // continue to next variant
            }
        }
        if (empty($rows) && $page === 1 && $lastHtml) {
            // Dump debug HTML for inspection (first 1KB truncated marker)
            $debugDir = base_path('storage/app/orders-debug');
            if (!is_dir($debugDir)) { @mkdir($debugDir, 0775, true); }
            $file = $debugDir.'/listing_page1_'.date('Ymd_His').'.html';
            @file_put_contents($file, $lastHtml);
        }
        return $rows;
    }

    /**
     * Robust table-based parser: iterates <tr> (skips header) and validates presence of edit link cell.
     * @return ListingOrderRowDTO[]
     */
    private function parseListingTable(Crawler $crawler): array
    {
        $out = [];
    $dumped = false;
    $crawler->filter('table')->filter('tr')->each(function(Crawler $tr) use (&$out) {
            // skip header if any TH present
            if ($tr->filter('th')->count() > 0) { return; }
            $cells = $tr->filter('td');
            if ($cells->count() < 11) {
                // our fixture has exactly 11 data columns; be tolerant and allow >=4 though
                if ($cells->count() < 4) { return; }
            }
            try {
                // cell indexes per fixture: 0:id,1:?,2:created,3:number(a),4:name,5:ship,6:pay,7:items,8:total,9:states,10:completed
                $numberCellHtml = $cells->eq(3)->html('');
                if (!str_contains($numberCellHtml, '/admin/order/edit/')) { return; }
                $createdRaw = trim($cells->eq(2)->text(''));
                $number = trim($cells->eq(3)->text(''));
                if (preg_match('~href=\"[^\"]*/admin/order/edit/(\d+)\"~', $numberCellHtml, $mHref)) {
                    $this->editIdMap[$number] = (int)$mHref[1];
                }
                $nameCell = $cells->count() > 4 ? trim($cells->eq(4)->text('')) : null;
                $shipping = $cells->count() > 5 ? trim($cells->eq(5)->text('')) : null;
                $payment = $cells->count() > 6 ? trim($cells->eq(6)->text('')) : null;
                $itemsCount = $cells->count() > 7 ? (int)preg_replace('~[^0-9]~','', $cells->eq(7)->text('0')) : 0;
                $totalRaw = $cells->count() > 8 ? trim($cells->eq(8)->text('0')) : '0';
                $statesRaw = $cells->count() > 9 ? trim($cells->eq(9)->text('')) : '';
                $completedRaw = $cells->count() > 10 ? trim($cells->eq(10)->text('')) : '';
                $createdAt = $this->parseDateTime($createdRaw);
                $totalCents = $this->parseMoneyToCents($totalRaw);
                $currency = $this->extractCurrency($totalRaw) ?? 'CZK';
                $stateCodes = $this->extractStateCodes($statesRaw);
                $isCompleted = str_contains($completedRaw, 'V');
                if ($number !== '') {
                    $out[] = new ListingOrderRowDTO($number, $createdAt, $nameCell, $shipping, $payment, $itemsCount, $totalCents, $currency, $stateCodes, $isCompleted, $this->editIdMap[$number] ?? null);
                }
            } catch (\Throwable $e) { /* skip */ }
        });
        return $out;
    }

    /** Legacy anchor->parent traversal retained for production HTML variations */
    private function parseListingAnchors(Crawler $crawler): array
    {
        $rows = [];
    $crawler->filter('a')->each(function(Crawler $a) use (&$rows) {
            try {
                $href = $a->attr('href');
                if (!$href || !str_contains($href, '/admin/order/edit/')) { return; }
                $tr = $a->ancestors()->filter('tr');
                if ($tr->count() === 0) { return; }
                $tr = $tr->first();
                $cells = $tr->filter('td');
                if ($cells->count() < 11) { if ($cells->count() < 4) { return; } }
                $createdRaw = trim($cells->eq(2)->text(''));
                $number = trim($cells->eq(3)->text('')) ?: trim($a->text(''));
                if ($number==='') { return; }
                $nameCell = $cells->count() > 4 ? trim($cells->eq(4)->text('')) : null;
                $shipping = $cells->count() > 5 ? trim($cells->eq(5)->text('')) : null;
                $payment = $cells->count() > 6 ? trim($cells->eq(6)->text('')) : null;
                $itemsCount = $cells->count() > 7 ? (int)preg_replace('~[^0-9]~','', $cells->eq(7)->text('0')) : 0;
                $totalRaw = $cells->count() > 8 ? trim($cells->eq(8)->text('0')) : '0';
                $statesRaw = $cells->count() > 9 ? trim($cells->eq(9)->text('')) : '';
                $completedRaw = $cells->count() > 10 ? trim($cells->eq(10)->text('')) : '';
                $createdAt = $this->parseDateTime($createdRaw);
                $totalCents = $this->parseMoneyToCents($totalRaw);
                $currency = $this->extractCurrency($totalRaw) ?? 'CZK';
                $stateCodes = $this->extractStateCodes($statesRaw);
                $isCompleted = str_contains($completedRaw, 'V');
                if (preg_match('~/admin/order/edit/(\d+)~', $href, $m)) {
                    $this->editIdMap[$number] = (int)$m[1];
                }
                $rows[] = new ListingOrderRowDTO($number, $createdAt, $nameCell, $shipping, $payment, $itemsCount, $totalCents, $currency, $stateCodes, $isCompleted, $this->editIdMap[$number] ?? null);
            } catch (\Throwable $e) { /* ignore */ }
        });
        return $rows;
    }
    
    private function fallbackParseListing(string $html): array
    {
        $rows = [];
    if (preg_match_all('~<tr[^>]*>\s*(?:<td|<th).*?</tr>~si', $html, $matches)) {
            foreach ($matches[0] as $trHtml) {
                if (!preg_match('~href=\"[^\"]*/admin/order/edit/[^\"]+\"~', $trHtml)) continue;
                $cols = [];
                if (preg_match_all('~<td[^>]*>(.*?)</td>~si', $trHtml, $tdm)) { $cols = $tdm[1]; }
                if (count($cols) < 4) continue;
                $createdRaw = strip_tags($cols[2] ?? '');
                $number = trim(strip_tags($cols[3] ?? ''));
                $totalRaw = strip_tags($cols[8] ?? '0');
                $statesRaw = strip_tags($cols[9] ?? '');
                $itemsCount = (int)trim(strip_tags($cols[7] ?? '0'));
                $createdAt = $this->parseDateTime($createdRaw);
                $totalCents = $this->parseMoneyToCents($totalRaw);
                $currency = $this->extractCurrency($totalRaw) ?? 'CZK';
                $stateCodes = $this->extractStateCodes($statesRaw);
                $rows[] = new ListingOrderRowDTO($number, $createdAt, null, null, null, $itemsCount, $totalCents, $currency, $stateCodes, false, null);
            }
        }
        return $rows;
    }

    public function fetchDetail(string $orderNumber, ?int $internalId = null): OrderDetailDTO
    {
        // If listing supplied a direct edit id, prefer it
        if ($internalId !== null) {
            $normalized = $internalId;
        } elseif (isset($this->editIdMap[$orderNumber])) {
            $normalized = $this->editIdMap[$orderNumber];
        } else {
            // Normalize order number: remove anything after first '(' and strip non-digit chars except dashes
            $normalized = preg_replace('~\(.*$~','', $orderNumber); // remove parentheses suffixes
            $normalized = preg_replace('~[^0-9-]+~','', $normalized); // keep digits and dashes
            if ($normalized === '') { $normalized = $orderNumber; }
        }
        $resp = Retry::run(function() use ($normalized){
            return $this->http->request('GET', $this->buildUrl('/admin/order/edit/'.$normalized), [
                'headers' => [ 'Cookie' => $this->cookieHeader() ]
            ]);
        }, attempts:3, baseDelayMs:250);
        $this->storeCookies($resp->getHeaders(false));
    $html = $resp->getContent();
    $this->lastDetailHtml = $html;
    try { $crawler = new Crawler($html); } catch (\Throwable $e) { throw new ParseException('Detail DOM init failed: '.$e->getMessage(),0,$e); }

        // ---------------------------------------------------------------------
        // Structured items tab fetch (explicit /tab-ite) observed in admin UI.
        // We fetch a second page that contains the authoritative items table.
        // ---------------------------------------------------------------------
                        $debugLines = []; // Initialize debug lines
    $itemsCount = 0; // final count
    $structuredItemsCount = 0; // count from structured parser only
        $itemsTabHtmlForHash = null;
        try {
            $itemsResp = Retry::run(function() use ($normalized){
                return $this->http->request('GET', $this->buildUrl('/admin/order/edit/'.$normalized.'/tab-ite'), [
                    'headers' => [ 'Cookie' => $this->cookieHeader() ]
                ]);
            }, attempts:2, baseDelayMs:200);
            $this->storeCookies($itemsResp->getHeaders(false));
            $itemsHtml = $itemsResp->getContent(false);
            $itemsTabHtmlForHash = $itemsHtml; // include whole tab in hash later
            $itemsCrawler = new Crawler($itemsHtml);
            // Locate the table whose header contains columns: Název, Množství, Za kus
            $itemsCrawler->filter('table')->each(function(Crawler $table) use (&$items,&$itemsCount) {
                if ($itemsCount > 0) { return; } // only first matching table
                $rows = $table->filter('tr');
                if ($rows->count() < 2) { return; }
                $headerCells = $rows->first()->filter('th, td');
                $headerText = mb_strtolower($rows->first()->text(''));
                $needed = ['název','množství','za kus'];
                foreach ($needed as $n) { if (!str_contains($headerText, $n)) { return; } }
                $seenIds = [];
                $seenComposite = [];
                // Skip paginator / summary rows (those containing "Položky" word in first cell)
                for ($i=1; $i < $rows->count(); $i++) {
                    $tr = $rows->eq($i);
                    $tds = $tr->filter('td');
                    if ($tds->count() < 8) { continue; }
                    $firstCell = trim($tds->eq(0)->text(''));
                    if ($firstCell === '' || str_contains(mb_strtolower($firstCell), 'položky')) { continue; }
                    if (!preg_match('~^[0-9.,]+$~', $firstCell)) { continue; }
                    $hasPrice = false; for ($c=0;$c<$tds->count();$c++){ if(preg_match('~[0-9].*(Kč|€|EUR)~u', trim($tds->eq($c)->text('')))){ $hasPrice=true; break; } }
                    if (!$hasPrice) { continue; }
                    // Column indices per observed layout:
                    // 0:ID, 1:ID Helios, 2:Název, 3:Kód produktu, 4:Kód varianty, 5:Specifikace,
                    // 6:Množství, 7:Jednotka, 8:Za kus, 9:DPH, 10:Sleva, 11:Celkem, 12:Celkem s DPH, 13:Akce
                    $get = fn(int $idx) => ($tds->count() > $idx) ? trim($tds->eq($idx)->text('')) : null;
                    $name = $get(2) ?: '';
                    if ($name === '') { continue; }
                    $qtyRaw = $get(6) ?: '1';
                    $qty = (int)preg_replace('~[^0-9]+~','', $qtyRaw) ?: 1;
                    $unit = $get(7) ?: null;
                    $unitPriceRaw = $get(8) ?: '0';
                    $vatRaw = $get(9) ?: '0%';
                    $discountRaw = $get(10) ?: null;
                    $totalExRaw = $get(11) ?: null;
                    $totalVatRaw = $get(12) ?: ($totalExRaw ?? $unitPriceRaw);
                    $unitPriceCents = $this->parseMoneyToCents($unitPriceRaw);
                    $totalVatCents = $this->parseMoneyToCents($totalVatRaw);
                    $vatRate = (int)preg_replace('~[^0-9]+~','', $vatRaw) ?: 0;
                    $discountPercent = $discountRaw !== null ? ((int)preg_replace('~[^0-9]+~','', $discountRaw) ?: null) : null;
                    $lineType = 'product';
                    $lname = mb_strtolower($name);
                        // Write debug lines
                        if (env('ORDERS_DEBUG_DUMP_ITEMS', false)) {
                            $dir = storage_path('app/orders_debug'); @mkdir($dir,0777,true);
                            @file_put_contents($dir.'/detail_items_trace_'.$normalized.'_'.date('Ymd_His').'.log', implode("\n", $debugLines));
                        }
                    if (str_contains($lname,'doprava') || in_array($lname,['dpd','ppl','gls','zasilkovna'], true)) { $lineType='shipping'; }
                    if (str_contains($lname,'platba') || str_contains($lname,'převodem') || str_contains($lname,'kartou')) { $lineType='payment'; }
                    $extId = (int)preg_replace('~[^0-9]+~','', $firstCell) ?: null;
                    $compositeKey = md5(mb_strtolower($name).'|'.$qty.'|'.$unitPriceCents.'|'.$totalVatCents.'|'.$lineType);
                    if ($extId && isset($seenIds[$extId])) { continue; }
                    if (isset($seenComposite[$compositeKey])) { continue; }
                    if ($extId) { $seenIds[$extId] = true; }
                    $seenComposite[$compositeKey] = true;
                    $items[] = [
                        'external_item_id' => $extId,
                        'name' => $name,
                        'product_code' => $get(3) ?: null,
                        'variant_code' => $get(4) ?: null,
                        'specification' => $get(5) ?: null,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'unit_price_vat_cents' => $unitPriceCents,
                        'vat_rate_percent' => $vatRate ?: 21,
                        'discount_percent' => $discountPercent,
                        'total_ex_vat_cents' => $totalExRaw ? $this->parseMoneyToCents($totalExRaw) : null,
                        'total_vat_cents' => $totalVatCents ?: ($unitPriceCents * $qty),
                        'line_type' => $lineType,
                        'currency' => $this->extractCurrency($unitPriceRaw) ?? 'CZK'
                    ];
                    $itemsCount++;
                    $structuredItemsCount++;
                }
            });
            if (env('ORDERS_DEBUG_DUMP_ITEMS', false)) {
                $dir = storage_path('app/orders_debug'); @mkdir($dir,0777,true);
                $ts = date('Ymd_His');
                @file_put_contents($dir.'/detail_items_tab_'.$normalized.'_'.$ts.'.html', $itemsHtml);
                @file_put_contents($dir.'/detail_items_parsed_'.$normalized.'_'.$ts.'.json', json_encode($items, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            // Ignore structured fetch errors; fall back to legacy heuristics
        }

        // Compute raw hash (after items parsed)
        $hashBlocks = [];
        foreach (['#order-basic','#order-items','#order-status-history'] as $sel) {
            $seg = $crawler->filter($sel);
            if ($seg->count()) { $hashBlocks[] = trim($seg->html()); }
        }
        if ($itemsTabHtmlForHash) { $hashBlocks[] = sha1($itemsTabHtmlForHash); }
        if (empty($hashBlocks)) {
            $body = $crawler->filter('body'); if ($body->count()) { $hashBlocks[] = trim($body->html()); }
        }
        $rawHash = sha1(implode("\n---\n", $hashBlocks));

        // Extract header info (customer, shipping, payment, totals) using heuristic selectors
    $createdAt = new \DateTimeImmutable();
    $currency = 'CZK';
    $customerName = null; $shipping = null; $payment = null; $totalVatCents = 0; $stateCodes = []; $isCompleted = false; // do NOT reset $itemsCount here (already set by structured parser)
        try {
            $headerBox = $crawler->filter('#order-basic');
            if ($headerBox->count()) {
                $text = $headerBox->text('');
                // Rough extraction patterns; refine with real HTML later.
                if (preg_match('~Zákazník:\s*(.+)~u', $text, $m)) { $customerName = trim($m[1]); }
                if (preg_match('~Doprava:\s*(.+)~u', $text, $m)) { $shipping = trim($m[1]); }
                if (preg_match('~Platba:\s*(.+)~u', $text, $m)) { $payment = trim($m[1]); }
                if (preg_match('~Celkem.*?([0-9][0-9 .,:]*[0-9])\s*(Kč|EUR|€)~u', $text, $m)) {
                    $totalVatCents = $this->parseMoneyToCents($m[1]);
                    $currency = $this->extractCurrency($m[0]) ?? $currency;
                }
            }
        } catch (\Throwable $e) { /* ignore heuristic failures */ }

        // If structured items tab parsing failed, fall back to legacy heuristics on summary page HTML.
    if ($structuredItemsCount === 0 && $itemsCount === 0) {
            $detailGridRows = $crawler->filter('table tr');
            if ($detailGridRows->count() > 0) {
                $detailGridRows->each(function(Crawler $tr, $i) use (&$items,&$itemsCount){
                    $tds = $tr->filter('td');
                    if ($tds->count() < 5) { return; }
                    $rowText = strtolower($tr->text(''));
                    if ($tr->filter('select, input[type=checkbox]')->count() && strpos($rowText,'položky') !== false) { return; }
                    if (preg_match('~^\s*$~',$tds->eq(0)->text(''))) { return; }
                    $name = null; $qty = 1; $priceCents = null; $totalCents = null;
                    for ($c=0;$c<$tds->count();$c++) {
                        $txt = trim($tds->eq($c)->text(''));
                        if ($txt==='') continue;
                        if ($name === null && preg_match('~[A-Za-zÁ-ž]~u',$txt) && !preg_match('~^\d{1,3}$~',$txt)) { $name = $txt; continue; }
                        if (preg_match('~^\d{1,3}$~',$txt)) { $qty = (int)$txt; }
                        if (preg_match('~[0-9].*(Kč|€|EUR)~u',$txt)) {
                            $val = $this->parseMoneyToCents($txt);
                            if ($priceCents === null) { $priceCents = $val; $totalCents = $val; }
                            else {
                                if ($val < $priceCents) { $priceCents = $val; }
                                elseif ($val > $totalCents) { $totalCents = $val; }
                            }
                        }
                    }
                    if ($name === null) { return; }
                    if ($priceCents === null) { $priceCents = 0; }
                    if ($totalCents === null || $totalCents < $priceCents) { $totalCents = $priceCents * max(1,$qty); }
                    $lineType = 'product';
                    if (stripos($name,'doprava')!==false) { $lineType='shipping'; }
                    if (stripos($name,'platba')!==false) { $lineType='payment'; }
                    $items[] = [
                        'external_item_id'=>null,
                        'name'=>$name,
                        'product_code'=>null,
                        'variant_code'=>null,
                        'specification'=>null,
                        'quantity'=>$qty ?: 1,
                        'unit'=>null,
                        'unit_price_vat_cents'=>$priceCents,
                        'vat_rate_percent'=>21,
                        'discount_percent'=>null,
                        'total_ex_vat_cents'=>null,
                        'total_vat_cents'=>$totalCents,
                        'line_type'=>$lineType,
                        'currency'=>'CZK'
                    ];
                    $itemsCount++;
                });
            }
        }

    // Regex fallback for items (test fixtures / unexpected markup)
        if ($itemsCount === 0 && preg_match('~<div id="order-items">.*?<table>(.*?)</table>~si', $html, $mTbl)) {
            if (preg_match_all('~<tr>\s*<td>(.*?)</td>\s*<td>(.*?)</td>\s*<td>(.*?)</td>\s*<td>(.*?)</td>~si', $mTbl[1], $mRows, PREG_SET_ORDER)) {
                foreach ($mRows as $r) {
                    $name = trim(strip_tags($r[1]));
                    $qty = (int)preg_replace('~[^0-9]+~','', $r[2]) ?: 1;
                    $unitPriceCents = $this->parseMoneyToCents($r[3]);
                    $lineTotalCents = $this->parseMoneyToCents($r[4]);
                    $lineType = 'product';
                    if (stripos($name, 'doprava') !== false) { $lineType = 'shipping'; }
                    if (stripos($name, 'platba') !== false) { $lineType = 'payment'; }
                    $items[] = [
                        'external_item_id' => null,
                        'name' => $name,
                        'product_code' => null,
                        'variant_code' => null,
                        'specification' => null,
                        'quantity' => $qty,
                        'unit' => null,
                        'unit_price_vat_cents' => $unitPriceCents,
                        'vat_rate_percent' => 21,
                        'discount_percent' => null,
                        'total_ex_vat_cents' => null,
                        'total_vat_cents' => $lineTotalCents ?: ($unitPriceCents * $qty),
                        'line_type' => $lineType,
                        'currency' => 'CZK'
                    ];
                    $itemsCount++;
                }
            }
        }

        // Generic fallback: scan all tables for potential item rows if still zero
        if ($itemsCount === 0) {
            try {
                $crawler->filter('table')->each(function(Crawler $table) use (&$items,&$itemsCount) {
                    if ($itemsCount > 0) { return; }
                    $rows = $table->filter('tr');
                    if ($rows->count() < 2) { return; }
                    // Heuristic: header contains at least one of keywords
                    $headerText = strtolower($rows->first()->text(''));
                    $kw = ['název','produkt','cena','ks','množství'];
                    $hasKw = false; foreach ($kw as $k){ if(strpos($headerText,$k)!==false){ $hasKw = true; break; }}
                    if (!$hasKw) { return; }
                    // Parse body rows until a summary row (contains 'celkem')
                    for ($i=1; $i<$rows->count(); $i++) {
                        $tr = $rows->eq($i);
                        $textAll = strtolower($tr->text(''));
                        if (strpos($textAll,'celkem') !== false && $itemsCount>0) { break; }
                        $tds = $tr->filter('td');
                        if ($tds->count() < 2) { continue; }
                        $name = trim($tds->eq(0)->text(''));
                        if ($name==='') { continue; }
                        // Find first numeric quantity cell (pref small integer <=999)
                        $qty = 1;
                        for ($c=1;$c<$tds->count();$c++) {
                            $raw = trim($tds->eq($c)->text(''));
                            if (preg_match('~^[0-9]{1,3}$~',$raw)) { $qty=(int)$raw; break; }
                        }
                        // Find last cell with currency symbol
                        $priceCents = 0; $totalCents = 0;
                        for ($c=$tds->count()-1; $c>=1; $c--) {
                            $raw = trim($tds->eq($c)->text(''));
                            if (preg_match('~[0-9].*(Kč|€|EUR)~u',$raw)) { $totalCents = $priceCents = $this->parseMoneyToCents($raw); break; }
                        }
                        $lineType = 'product';
                        if (stripos($name,'doprava')!==false) { $lineType='shipping'; }
                        if (stripos($name,'platba')!==false) { $lineType='payment'; }
                        $items[] = [
                            'external_item_id'=>null,
                            'name'=>$name,
                            'product_code'=>null,
                            'variant_code'=>null,
                            'specification'=>null,
                            'quantity'=>$qty,
                            'unit'=>null,
                            'unit_price_vat_cents'=>$priceCents,
                            'vat_rate_percent'=>21,
                            'discount_percent'=>null,
                            'total_ex_vat_cents'=>null,
                            'total_vat_cents'=>$totalCents ?: ($priceCents*$qty),
                            'line_type'=>$lineType,
                            'currency'=>'CZK'
                        ];
                        $itemsCount++;
                    }
                });
            } catch (\Throwable $e) { /* ignore generic fallback failures */ }
        }

        // Fallback states extraction if still none: scan for inline badge-like tokens (uppercase, <=6 chars)
        if (empty($stateCodes)) {
            try {
                if (preg_match_all('~>([A-Z0-9_\-]{2,6})<~', $html, $mAll)) {
                    $guess = array_unique($mAll[1]);
                    // Filter out common table headers
                    $bad = ['Kč','VAT','DPH'];
                    $stateCodes = array_values(array_filter($guess, fn($c)=>!in_array($c,$bad,true)));
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Optional debug dump when nothing parsed
    if ($itemsCount === 0 && (empty($stateCodes) || (bool)env('ORDERS_DEBUG_DUMP_EMPTY', false))) {
            try {
                $dir = storage_path('app/orders_debug'); @mkdir($dir,0777,true);
        $safe = preg_replace('~[^0-9A-Za-z_-]+~','_', $orderNumber);
        file_put_contents($dir.'/detail_'.$safe.'_'.date('Ymd_His').'.html',$html);
            } catch (\Throwable $e) { /* ignore */ }
        }

        // State codes extraction: production uses inline <div class="uk-button ..." uk-tooltip="Popis">KÓD</div>
        try {
            $crawler->filter('div.uk-button.uk-button-tiny')->each(function(Crawler $node) use (&$stateCodes,&$isCompleted){
                $txt = trim($node->text(''));
                if ($txt==='') return;
                // Only short tokens (1–3 UTF-8 chars) as codes; others ignored
                if (mb_strlen($txt) <= 3 && !in_array($txt,$stateCodes,true)) { $stateCodes[] = $txt; }
                $tip = $node->attr('uk-tooltip') ?? $node->attr('data-uk-tooltip') ?? '';
                $full = $txt.' '.$tip;
                if (preg_match('~vyřízen~iu',$full) || preg_match('~odeslán~iu',$full)) { $isCompleted = true; }
            });
        } catch (\Throwable $e) { /* ignore */ }

        $row = new ListingOrderRowDTO(
            $orderNumber,
            $createdAt,
            $customerName,
            $shipping,
            $payment,
            $itemsCount,
            $totalVatCents,
            $currency,
            $stateCodes,
            $isCompleted,
            $internalId ?? ($this->editIdMap[$orderNumber] ?? null)
        );

        return new OrderDetailDTO($row, $items, $rawHash);
    }

    private function parseDateTime(string $raw): \DateTimeImmutable
    {
        $raw = trim($raw);
        $dt = \DateTimeImmutable::createFromFormat('d.m.Y H:i', $raw, new \DateTimeZone('Europe/Prague'));
        if (!$dt) { return new \DateTimeImmutable(); }
        return $dt;
    }

    private function parseMoneyToCents(string $raw): int
    {
        $raw = preg_replace('~[^0-9, ]+~u', '', $raw) ?? '';
        $raw = trim($raw);
        if ($raw === '') return 0;
        // Replace space thousands, comma decimal
        $normalized = str_replace(' ', '', $raw);
        $normalized = str_replace(',', '.', $normalized);
        $val = (float)$normalized;
        return (int)round($val * 100);
    }

    private function extractCurrency(string $raw): ?string
    {
        if (str_contains($raw, '€')) return 'EUR';
        if (str_contains($raw, 'Kč')) return 'CZK';
        return null;
    }

    private function extractStateCodes(string $raw): array
    {
        $parts = preg_split('~\s+~u', trim($raw)) ?: [];
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }

    private function storeCookies(array $headers): void
    {
        if (!isset($headers['set-cookie'])) return;
        foreach ($headers['set-cookie'] as $cookieLine) {
            $segments = explode(';', $cookieLine);
            $kv = explode('=', trim($segments[0]), 2);
            if (count($kv) === 2) {
                $this->cookies[$kv[0]] = $kv[1];
            }
        }
    }

    private function cookieHeader(): string
    {
        return collect($this->cookies)->map(fn($v,$k) => $k.'='.$v)->implode('; ');
    }

    public function getLastDetailHtml(): ?string
    {
        return $this->lastDetailHtml;
    }
}

