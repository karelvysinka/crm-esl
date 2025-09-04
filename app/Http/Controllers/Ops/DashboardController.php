<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Services\Ops\GitInfoService;
use App\Services\Ops\BackupStatusService;
use App\Models\OpsActivity;

class DashboardController extends Controller
{
    public function __construct(private GitInfoService $git, private BackupStatusService $backup) {
        $this->middleware('permission:ops.view');
    }

    public function index()
    {
        if (function_exists('auth') && auth()->check() && !auth()->user()->can('ops.view')) {
            abort(403);
        }
        $git = $this->git->currentRevision();
        $db = $this->backup->latestDbDumpStatus();
        $uploads = $this->backup->uploadsStatus();
        $recent = OpsActivity::latest()->limit(5)->get();
        return view('ops.dashboard', compact('git','db','uploads','recent'));
    }
}
