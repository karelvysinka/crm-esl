<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        // Safe guard: if migrations not yet run, render placeholder view
        try {
            if (!\Schema::hasTable('orders')) {
                return view('orders.index-missing');
            }
        } catch (\Throwable $e) {
            return view('orders.index-missing');
        }
        $q = trim((string)$request->get('q',''));
        $state = $request->get('state');
        $completed = $request->get('completed');
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        $orders = Order::query()
            ->withCount('items')
            ->when($q !== '', function($qbuilder) use ($q){
                $qbuilder->where(function($w) use ($q){
                    $w->where('order_number','like',"%$q%");
                });
            })
            ->when($state, function($qb) use ($state){
                $qb->where(function($w) use ($state){
                    $w->where('last_state_code',$state)
                      ->orWhereHas('stateChanges', function($sc) use ($state){ $sc->where('new_code',$state); });
                });
            })
            ->when($completed !== null && $completed !== '', function($qb) use ($completed){
                $val = in_array($completed,['1','true','yes'],true);
                $qb->where('is_completed',$val);
            })
            ->when($dateFrom, fn($qb)=>$qb->whereDate('order_created_at','>=',$dateFrom))
            ->when($dateTo, fn($qb)=>$qb->whereDate('order_created_at','<=',$dateTo))
            ->orderByDesc('order_created_at')
            ->paginate(30)
            ->appends($request->query());

        $filters = [
            'q'=>$q,'state'=>$state,'completed'=>$completed,'from'=>$dateFrom,'to'=>$dateTo
        ];
        $runningImports = \App\Models\OpsActivity::query()
            ->whereIn('type',[ 'orders.full_import','orders.full_import.web','orders.full_import.manual' ])
            ->whereIn('status',['queued','running'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $lastImports = \App\Models\OpsActivity::query()
            ->whereIn('type',[ 'orders.full_import','orders.full_import.web','orders.full_import.manual' ])
            ->whereIn('status',['success','error','skipped'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        // Stats summary: total, last month, last week
        $now = now();
        $stats = [
            'total' => \App\Models\Order::count(),
            'last_month' => \App\Models\Order::where('order_created_at','>=',$now->copy()->subMonth())->count(),
            'last_week' => \App\Models\Order::where('order_created_at','>=',$now->copy()->subWeek())->count(),
        ];
        return view('orders.index', compact('orders','filters','runningImports','lastImports','stats'));
    }

    public function triggerImport(Request $request)
    {
    $user = $request->user();
    if (!$user) { abort(401); }
    $emailOk = str_ends_with($user->email, '@crm.esl.cz');
    $allowed = $user->can('ops.execute') || ($user->can('orders.view') && $emailOk);
    if (!$allowed) { abort(403, 'User not allowed to trigger import'); }
        $token = $request->input('_ops_token');
        $used = session()->get('orders.used_tokens', []);
        if (!$token || in_array($token, $used, true)) {
            return redirect()->route('orders.index')->withErrors('Neplatný / znovu použitý token akce.');
        }
        $used[] = $token; if (count($used) > 50) { $used = array_slice($used, -50); }
        session()->put('orders.used_tokens', $used);
        $pages = $request->input('pages');
        $activity = \App\Models\OpsActivity::create([
            'type'=>'orders.full_import.web','status'=>'queued','user_id'=>optional($request->user())->id,
            'meta'=>['ip'=>$request->ip(),'ua'=>substr($request->userAgent() ?? '',0,120),'pages'=>$pages]
        ]);
        \App\Jobs\Orders\RunFullImportJob::dispatch($activity->id, $pages ? (int)$pages : null);
        return redirect()->route('orders.index')
            ->with('status','Import zařazen do fronty')
            ->with('activity_id',$activity->id);
    }

    public function show(Order $order): View
    {
        // If table missing (route-model would already fail), but double-check
        if (!\Schema::hasTable('orders')) {
            abort(503,'Orders storage not initialized');
        }
        $order->load(['items','stateChanges'=>fn($q)=>$q->orderBy('changed_at')]);
        // Integrity check (simple): difference between stored total and sum of items
        $sumItems = $order->items->sum('total_vat_cents');
        $integrityDiff = $order->total_vat_cents - $sumItems;
        return view('orders.show', [
            'order'=>$order,
            'sumItems'=>$sumItems,
            'integrityDiff'=>$integrityDiff,
        ]);
    }
}
