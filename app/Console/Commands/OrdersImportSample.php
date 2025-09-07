<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;

class OrdersImportSample extends Command
{
    protected $signature = 'orders:import-sample 
        {--pages=1 : How many listing pages}
        {--details=3 : Fetch detail for first N new rows per page}
        {--sleep=0 : Extra sleep ms between pages}
        {--debug-http : Verbose output (HTTP/page debug)}';
    protected $description = 'Safe sampling of orders listing + a few detail fetches (no DB writes) for diagnostic.';

    public function handle(OrderScrapeClient $client): int
    {
        $pagesLimit = (int)$this->option('pages');
        $detailLimit = (int)$this->option('details');
        $extraSleep = (int)$this->option('sleep');
    $verbose = (bool)$this->option('debug-http');
        $client->login();
        $summary = [];
        for ($p=1;$p<=$pagesLimit;$p++) {
            $rows = $client->fetchListingPage($p);
            if (empty($rows)) { $this->warn("Page $p empty -> stop"); break; }
            $this->line("Page $p: ".count($rows).' rows');
            $pageInfo = [ 'count'=>count($rows), 'numbers'=>[], 'details'=>[] ];
            $i=0;
            foreach ($rows as $dto) {
                $pageInfo['numbers'][] = $dto->orderNumber;
                if ($i < $detailLimit) {
                    try {
                        $detail = $client->fetchDetail($dto->orderNumber);
                        $pageInfo['details'][] = [
                            'order'=>$dto->orderNumber,
                            'items'=>count($detail->items),
                            'hash'=>substr($detail->rawHash,0,10),
                            'states'=>count($detail->row->stateCodes)
                        ];
                    } catch (\Throwable $e) {
                        $pageInfo['details'][] = [ 'order'=>$dto->orderNumber, 'error'=>$e->getMessage() ];
                    }
                }
                $i++;
            }
            $summary[] = $pageInfo;
            if ($verbose) { $this->line(json_encode($pageInfo, JSON_UNESCAPED_UNICODE)); }
            $sleep = 200 + random_int(0,200) + $extraSleep; usleep($sleep*1000);
        }
        $this->newLine();
        $this->info('Sample complete.');
        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return self::SUCCESS;
    }
}
