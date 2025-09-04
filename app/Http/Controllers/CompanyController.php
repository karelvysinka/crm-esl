<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index()
    {
        $sort = request('sort');
        $dir = strtolower(request('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    $qText = trim((string) request('q', ''));
        $status = request('status');
        $size = request('size');
        $industry = trim((string) request('industry', ''));
    $minTurnover = request('min_turnover');
    $maxTurnover = request('max_turnover');

        $query = Company::query()
            ->withCount('contacts')
            ->with(['createdBy'])
            ->leftJoin('sales_orders as so', 'so.company_id', '=', 'companies.id')
            ->select('companies.*', DB::raw('COALESCE(SUM(so.total_amount),0) as total_turnover'))
            ->groupBy('companies.id');

        // Filters
        if ($status && in_array($status, ['active','inactive','prospect','lost'], true)) {
            $query->where('companies.status', $status);
        }
        if ($size && in_array($size, ['startup','small','medium','large','enterprise'], true)) {
            $query->where('companies.size', $size);
        }
        if ($industry !== '') {
            $term = '%'.$industry.'%';
            $query->where('companies.industry', 'like', $term);
        }
        if ($qText !== '') {
            $term = '%'.$qText.'%';
            $query->where(function($qq) use ($term) {
                $qq->where('companies.name', 'like', $term)
                   ->orWhere('companies.email', 'like', $term)
                   ->orWhere('companies.phone', 'like', $term)
                   ->orWhere('companies.website', 'like', $term)
                   ->orWhere('companies.city', 'like', $term);
            });
        }

        // Turnover range HAVING filters on aggregated alias
        if ($minTurnover !== null && $minTurnover !== '') {
            $minVal = (float) $minTurnover;
            $query->having('total_turnover', '>=', $minVal);
        }
        if ($maxTurnover !== null && $maxTurnover !== '') {
            $maxVal = (float) $maxTurnover;
            $query->having('total_turnover', '<=', $maxVal);
        }

        if ($sort === 'turnover') {
            $query->orderBy('total_turnover', $dir);
        } else {
            $query->orderBy('companies.created_at', 'desc');
        }

    $companies = $query->paginate(20)->withQueryString();

    return view('crm.companies.index', compact('companies', 'sort', 'dir', 'qText', 'status', 'size', 'industry', 'minTurnover', 'maxTurnover'));
    }

    public function create()
    {
        return view('crm.companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'size' => 'required|in:startup,small,medium,large,enterprise',
            'status' => 'required|in:active,inactive,prospect,lost',
            'website' => 'nullable|url',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'annual_revenue' => 'nullable|numeric|min:0',
            'employee_count' => 'nullable|integer|min:0',
        ]);

        // Add a dummy user ID as created_by (since we don't have authentication yet)
        $validated['created_by'] = 1; // Using dummy user ID
        $company = Company::create($validated);

        return redirect()->route('companies.index')->with('success', 'Společnost byla úspěšně vytvořena.');
    }

    public function show(Company $company)
    {
        // Stránkované kontakty a objednávky
    $contacts = $company->contacts()->orderBy('last_name')->orderBy('first_name')->paginate(20, ['*'], 'contacts_page');
    $orders = $company->salesOrders()->orderByDesc('order_date')->paginate(10, ['*'], 'orders_page');

        // Statistika (počty a obraty)
        $totalTurnover = $company->salesOrders()->sum('total_amount');
        $contactsCount = $company->contacts()->count();
        $ordersCount = $company->salesOrders()->count();

        // Per-contact turnover map (contact_id => sum(total_amount))
        $contactTurnovers = SalesOrder::query()
            ->where('company_id', $company->id)
            ->whereNotNull('contact_id')
            ->select('contact_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Yearly turnover aggregation (year => sum)
        $yearlyTurnover = DB::table('sales_orders')
            ->select(DB::raw('YEAR(order_date) as yr'), DB::raw('SUM(total_amount) as total'))
            ->where('company_id', $company->id)
            ->whereNotNull('order_date')
            ->groupBy(DB::raw('YEAR(order_date)'))
            ->orderBy('yr', 'desc')
            ->pluck('total', 'yr');

        // Předat stránkované kolekce do view
        return view('crm.companies.show', compact('company', 'contacts', 'orders', 'totalTurnover', 'contactsCount', 'ordersCount', 'contactTurnovers', 'yearlyTurnover'));
    }

    public function edit(Company $company)
    {
        return view('crm.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'size' => 'required|in:startup,small,medium,large,enterprise',
            'status' => 'required|in:active,inactive,prospect,lost',
            'website' => 'nullable|url',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'annual_revenue' => 'nullable|numeric|min:0',
            'employee_count' => 'nullable|integer|min:0',
        ]);

        $company->update($validated);
        return redirect()->route('companies.show', $company)->with('success', 'Společnost byla úspěšně aktualizována.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Společnost byla úspěšně smazána.');
    }
}
