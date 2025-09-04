<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;

class OrderItemsController extends Controller
{
    public function show(Request $request, $orderId)
    {
        $perPage = (int) $request->get('per_page', 50);
        if ($perPage < 10) { $perPage = 10; }
        if ($perPage > 200) { $perPage = 200; }

    $order = SalesOrder::findOrFail($orderId);
    $items = $order->items()->paginate($perPage);
    // Ensure paginator generates links pointing to the items endpoint and keeps current query string
    $items->setPath(url("/crm/orders/{$order->id}/items"))->withQueryString();
    // If the request is not AJAX and not explicitly marked as partial, render a full page to preserve CRM layout/design
    $forcePartial = (bool) $request->query('partial', false);
    $isAjax = $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    if (!$isAjax && !$forcePartial) {
            return view('crm.orders.items_page', compact('order', 'items'));
        }
        // For AJAX requests, return the partial only
        return view('crm.orders._items', compact('order', 'items'));
    }
}
