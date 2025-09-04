<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;

class AgentV2SetupController extends Controller
{
    public function enable()
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
        SystemSetting::set('agent.v2_enabled', '1');
        SystemSetting::set('tools.playwright.enabled', '1');
        return redirect()->back()->with('status', 'Agent V2 a Playwright zapnuty.');
    }
}
