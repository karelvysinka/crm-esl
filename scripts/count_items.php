<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$orders = App\Models\Order::count();
$items = App\Models\OrderItem::count();
$withItems = App\Models\Order::has('items')->count();
echo json_encode([
  'orders'=>$orders,
  'orders_with_items'=>$withItems,
  'order_items'=>$items,
]);
