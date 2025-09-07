<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;

class OrdersLoginTest extends Command
{
    protected $signature = 'orders:login-test';
    protected $description = 'Otestuje přihlášení (credentials nebo ORDERS_SYNC_COOKIE) a vypíše počet načtených řádků z první stránky';

    public function handle(OrderScrapeClient $client): int
    {
        try {
            $client->login();
            $rows = $client->fetchListingPage(1);
            $this->info('Login OK. Rows parsed: '.count($rows));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Login FAILED: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
