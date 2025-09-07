<?php

namespace App\Http\Controllers;

use App\Models\OrderSyncSetting;
use App\Models\OrderSyncRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class OrderSyncSettingsController extends Controller
{
    public function index()
    {
        // Graceful fallback pokud ještě nejsou migrace nasazeny
        if (!Schema::hasTable('order_sync_settings') || !Schema::hasTable('order_sync_runs')) {
            return view('orders.sync-settings-missing');
        }
        $setting = OrderSyncSetting::first();
        if (!$setting) { $setting = OrderSyncSetting::create(['source_url'=>'','interval_minutes'=>15,'enabled'=>true]); }
        $runsTotal = OrderSyncRun::count();
        $runsSuccess = OrderSyncRun::where('status','success')->count();
        $runsFailed = OrderSyncRun::where('status','failed')->count();
        $lastRun = OrderSyncRun::orderByDesc('id')->first();
        $lastSuccess = OrderSyncRun::where('status','success')->orderByDesc('id')->first();
        $nextRunAt = null;
        if ($lastRun && $lastRun->started_at) {
            $nextRunAt = $lastRun->started_at->copy()->addMinutes($setting->interval_minutes);
        }
        $recent = OrderSyncRun::orderByDesc('id')->limit(15)->get();
        return view('orders.sync-settings', compact('setting','runsTotal','runsSuccess','runsFailed','recent','lastRun','lastSuccess','nextRunAt'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'source_url'=>'required|url','username'=>'nullable|string|max:255','password'=>'nullable|string|max:255','interval_minutes'=>'required|integer|min:5|max:1440','enabled'=>'sometimes|boolean'
        ]);
        $setting = OrderSyncSetting::first();
        if (!$setting) { $setting = new OrderSyncSetting(); }
        $setting->source_url = $validated['source_url'];
        $setting->username = $validated['username'] ?? null;
        if(isset($validated['password']) && $validated['password'] !== ''){ $setting->password_encrypted = encrypt($validated['password']); }
        $setting->interval_minutes = $validated['interval_minutes'];
        $setting->enabled = $request->boolean('enabled');
        $setting->save();
        return redirect()->route('orders.sync.settings')->with('status','Nastavení uloženo');
    }

    public function runNow(Request $request)
    {
        if (!Schema::hasTable('order_sync_settings') || !Schema::hasTable('order_sync_runs')) {
            return redirect()->route('orders.sync.settings')->withErrors('Tabulky nejsou připraveny.');
        }
        $setting = OrderSyncSetting::first();
        if (!$setting) { return redirect()->route('orders.sync.settings')->withErrors('Není konfigurace.'); }
        $run = OrderSyncRun::create(['started_at'=>now(),'status'=>'running']);
        try {
            $before = \App\Models\Order::count();
            Artisan::call('orders:sync-incremental');
            $after = \App\Models\Order::count();
            $run->update([
                'finished_at'=>now(),
                'status'=>'success',
                'new_orders'=>max(0,$after-$before)
            ]);
            return redirect()->route('orders.sync.settings')->with('status','Synchronizace spuštěna a dokončena.');
        } catch (\Throwable $e) {
            $run->update([
                'finished_at'=>now(),
                'status'=>'failed',
                'message'=>substr($e->getMessage(),0,480)
            ]);
            return redirect()->route('orders.sync.settings')->withErrors('Chyba: '.$e->getMessage());
        }
    }

    public function testConnection()
    {
        if (!Schema::hasTable('order_sync_settings')) {
            return redirect()->route('orders.sync.settings')->withErrors('Chybí tabulka konfigurace.');
        }
        $setting = OrderSyncSetting::first();
        if (!$setting || !$setting->source_url) {
            return redirect()->route('orders.sync.settings')->withErrors('URL není nastavena.');
        }
        try {
            $req = Http::timeout(10)
                ->when($setting->username && $setting->password_encrypted, function($http) use ($setting){
                    return $http->withBasicAuth($setting->username, decrypt($setting->password_encrypted));
                })
                ->get($setting->source_url);
            if ($req->successful()) {
                return redirect()->route('orders.sync.settings')->with('status','Test OK (HTTP '.$req->status().')');
            }
            return redirect()->route('orders.sync.settings')->withErrors('Test selhal (HTTP '.$req->status().')');
        } catch (\Throwable $e) {
            return redirect()->route('orders.sync.settings')->withErrors('Výjimka: '.substr($e->getMessage(),0,160));
        }
    }
}
