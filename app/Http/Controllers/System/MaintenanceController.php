<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function migrate(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->is_admin) {
            abort(403, 'Only admin may run migrations');
        }
        try {
            Artisan::call('migrate', ['--force' => true]);
            $out = Artisan::output();
            return response()->view('system.migrate-result', ['output' => $out]);
        } catch (\Throwable $e) {
            return response()->view('system.migrate-result', ['output' => 'Error: '.$e->getMessage()], 500);
        }
    }
}
