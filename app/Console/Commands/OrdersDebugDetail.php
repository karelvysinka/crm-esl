<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;

class OrdersDebugDetail extends Command
{
    protected $signature = 'orders:debug-detail {orderNumber} {--internal-id= : Force internal numeric edit ID} {--dump : Write raw HTML + parsed JSON to storage/app/orders_debug}';
    protected $description = 'Fetch a single order detail and show parsed summary (no DB writes)';

    public function handle(OrderScrapeClient $client): int
    {
        $orderNumber = $this->argument('orderNumber');
        $client->login();
    $internalId = $this->option('internal-id') ? (int)$this->option('internal-id') : null;
    $detail = $client->fetchDetail($orderNumber, $internalId);
        $this->line('Order: '.$orderNumber);
    if ($internalId) { $this->line('Forced internalId: '.$internalId); }
        $this->line('Items parsed: '.count($detail->items));
        $this->line('State codes: '.implode(',', $detail->row->stateCodes));
        $this->line('Hash: '.substr($detail->rawHash,0,12));
        if ($this->option('dump')) {
            $dir = storage_path('app/orders_debug'); @mkdir($dir,0777,true);
            $safe = preg_replace('~[^0-9A-Za-z_-]+~','_', $orderNumber);
            file_put_contents($dir.'/detail_'.$safe.'_'.date('Ymd_His').'.json', json_encode([
                'items'=>$detail->items,
                'states'=>$detail->row->stateCodes,
                'hash'=>$detail->rawHash,
            ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            if ($html = $client->getLastDetailHtml()) {
                file_put_contents($dir.'/detail_'.$safe.'_'.date('Ymd_His').'.html', $html);
            }
            $this->info('Dumped JSON to storage/app/orders_debug');
        }
        return self::SUCCESS;
    }
}
