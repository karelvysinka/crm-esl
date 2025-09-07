<?php

namespace App\Http\Controllers;

use App\Models\OrderSyncSetting;
use App\Models\OrderSyncRun;
use Illuminate\Http\Request;

class OrderSyncSettingsController extends Controller
{
    public function index()
    {
        // Graceful fallback pokud ještě nejsou migrace nasazeny
        if (!\Schema::hasTable('order_sync_settings') || !\Schema::hasTable('order_sync_runs')) {
            return view('orders.sync-settings-missing');
        }
        $setting = OrderSyncSetting::first();
        if (!$setting) { $setting = OrderSyncSetting::create(['source_url'=>'','interval_minutes'=>15,'enabled'=>true]); }
        $runsTotal = OrderSyncRun::count();
        $runsSuccess = OrderSyncRun::where('status','success')->count();
        $runsFailed = OrderSyncRun::where('status','failed')->count();
        $recent = OrderSyncRun::orderByDesc('id')->limit(15)->get();
        return view('orders.sync-settings', compact('setting','runsTotal','runsSuccess','runsFailed','recent'));
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
}
