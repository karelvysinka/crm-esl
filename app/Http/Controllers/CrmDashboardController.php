<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Deal;
use App\Models\Project;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function index(): View
    {
        $acEnabled = (bool) json_decode((string) optional(\App\Models\SystemSetting::where('key','ac_sync_enabled')->first())->value ?: 'false');

    // Orders (objednávky)
    $now = now();
    $ordersToday = Order::whereBetween('order_created_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count();
    $ordersWeek = Order::whereBetween('order_created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();
    $ordersMonth = Order::whereBetween('order_created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count();
    $ordersYear = Order::whereBetween('order_created_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count();

        // Companies
        $companiesTotal = Company::count();
        $companiesActive = Company::where('status','active')->count();
        $companiesNewMonth = Company::whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();

        // Contacts
        $contactsTotal = Contact::count();
        $contactsNewMonth = Contact::whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
        $contactsStatus = Contact::select('status')->selectRaw('COUNT(*) as c')->groupBy('status')->pluck('c','status');

        // Leads
        $leadsTotal = Lead::count();
        $leadsHot = Lead::where('status','hot')->count();
        $leadsNewMonth = Lead::whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();

        // Opportunities
        $oppsTotal = Opportunity::count();
        $oppsValue = (float) Opportunity::sum('value');
        // Open = any stage not won/lost
        $oppsOpen = Opportunity::whereNotIn('stage',[ 'won','lost' ])->count();
        $oppsWon = Opportunity::where('stage','won')->count();
        $oppsLost = Opportunity::where('stage','lost')->count();
        $oppsWinRate = ($oppsWon + $oppsLost) > 0 ? round($oppsWon/($oppsWon+$oppsLost)*100,1) : 0;
        $oppsAvgValue = $oppsTotal > 0 ? round($oppsValue / $oppsTotal, 2) : 0;
        $oppsAvgCloseDays = (int) Opportunity::where('stage','won')
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(DATEDIFF(closed_at, created_at)) as d')
            ->value('d');
        $oppsAvgCloseDays = $oppsAvgCloseDays ?: 0;

    // Tasks
    $tasksOpen = Task::whereNotIn('status',['completed','cancelled'])->count();
    $tasksOverdue = Task::whereNotIn('status',['completed','cancelled'])->where('due_date','<',now())->count();
    $tasksDue7 = Task::whereNotIn('status',['completed','cancelled'])->whereBetween('due_date',[now(), now()->addDays(7)])->count();
    $tasksCompletedMonth = Task::where('status','completed')->whereBetween('completed_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
    $tasksCompletionRate = ($tasksOpen + $tasksCompletedMonth) > 0 ? round($tasksCompletedMonth / ($tasksOpen + $tasksCompletedMonth) * 100,1) : 0;
    $tasksOverdueRate = ($tasksOpen + $tasksCompletedMonth) > 0 ? round($tasksOverdue / ($tasksOpen + $tasksCompletedMonth) * 100,1) : 0;

        // Deals
        $dealsTotal = Deal::count();
        $dealsPending = Deal::where('status','pending')->count();
        $dealsWon = Deal::where('status','won')->count();
        $dealsLost = Deal::where('status','lost')->count();
        $dealsWinRate = ($dealsWon + $dealsLost) > 0 ? round($dealsWon/($dealsWon+$dealsLost)*100,1) : 0;
        $dealsPipelineValue = (float) Deal::where('status','pending')->sum('amount');
        $dealsWonMonthValue = (float) Deal::where('status','won')->whereBetween('updated_at',[now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $dealsAvgSize = $dealsTotal > 0 ? round(Deal::avg('amount'),2) : 0;
        $dealsAvgCloseDays = (int) Deal::whereIn('status',['won','lost'])
            ->whereNotNull('close_date')
            ->selectRaw('AVG(DATEDIFF(close_date, created_at)) as d')
            ->value('d');
        $dealsAvgCloseDays = $dealsAvgCloseDays ?: 0;

        // Projects
        $projectsTotal = Project::count();
        $projectsInProgress = Project::where('status','in_progress')->count();
        $projectsCompleted = Project::where('status','completed')->count();
        $projectsUpcoming = Project::whereNotNull('due_date')->whereBetween('due_date',[now(), now()->addDays(30)])->count();
        $projectsOnTimeRate = $projectsCompleted > 0 ? round(Project::where('status','completed')
            ->whereRaw('completed_at <= due_date')
            ->count() / $projectsCompleted * 100,1) : 0;

    // Products
    $productsTotal = Product::count();
    $productsNewMonth = Product::whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
    // Availability: use availability_code mapping (config products.availability_map). '7' represents 'Skladem'.
    $productsAvailable = Product::where('availability_code','7')->count();
    $productsInStockRate = $productsTotal > 0 ? round($productsAvailable / $productsTotal * 100,1) : 0;

        // Contacts over last 12 months (for chart)
        $since = now()->subMonths(11)->startOfMonth();
        $driver = DB::connection()->getDriverName();
        $expr = match ($driver) {
            'mysql','mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "strftime('%Y-%m', created_at)",
        };
        $raw = Contact::selectRaw("$expr as ym, COUNT(*) as c")
            ->where('created_at','>=',$since)
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('c','ym')
            ->toArray();
        $chartLabels = [];$chartSeries=[];
        for($i=0;$i<12;$i++){ $m=$since->copy()->addMonths($i); $ym=$m->format('Y-m'); $chartLabels[]=$m->isoFormat('MMM YYYY'); $chartSeries[]=$raw[$ym]??0; }

        return view('crm.dashboard', compact(
            'acEnabled',
            'companiesTotal','companiesActive','companiesNewMonth',
            'ordersToday','ordersWeek','ordersMonth','ordersYear',
            'contactsTotal','contactsNewMonth','contactsStatus',
            'leadsTotal','leadsHot','leadsNewMonth',
            'oppsTotal','oppsValue','oppsOpen','oppsWon','oppsLost','oppsWinRate','oppsAvgValue','oppsAvgCloseDays',
            'tasksOpen','tasksOverdue','tasksDue7','tasksCompletedMonth','tasksCompletionRate','tasksOverdueRate',
            'dealsTotal','dealsPending','dealsWon','dealsLost','dealsWinRate','dealsPipelineValue','dealsWonMonthValue','dealsAvgSize','dealsAvgCloseDays',
            'projectsTotal','projectsInProgress','projectsCompleted','projectsUpcoming','projectsOnTimeRate',
            'productsTotal','productsNewMonth','productsAvailable','productsInStockRate',
            'chartLabels','chartSeries'
        ));
    }
}
