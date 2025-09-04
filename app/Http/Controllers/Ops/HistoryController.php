<?php
namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\OpsActivity;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct() { $this->middleware('permission:ops.view'); }
    public function index(Request $request)
    {
        if (function_exists('auth') && auth()->check() && !auth()->user()->can('ops.view')) abort(403);
        $items = OpsActivity::latest()->paginate(30);
        return view('ops.history', compact('items'));
    }
}
