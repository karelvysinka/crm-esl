<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
    $deals = Deal::with(['opportunity', 'signedByContact'])->orderByDesc('created_at')->get();

    // Stats
    $total = Deal::count();
    $newMonth = Deal::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    $statusCounts = Deal::select('status')->selectRaw('COUNT(*) as c')->groupBy('status')->pluck('c','status');
    $pending = (int) ($statusCounts['pending'] ?? 0);
    $won = (int) ($statusCounts['won'] ?? 0);
    $lost = (int) ($statusCounts['lost'] ?? 0);
    $totalValue = (float) Deal::sum('amount');
    $pipelineValue = (float) Deal::where('status','pending')->sum('amount');
    $wonMonthValue = (float) Deal::where('status','won')->whereBetween('updated_at',[now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
    $closingNext30 = Deal::whereBetween('close_date',[now(), now()->addDays(30)])->count();
    $closingNext30Value = (float) Deal::whereBetween('close_date',[now(), now()->addDays(30)])->sum('amount');
    $winRate = $won + $lost > 0 ? round(($won / ($won + $lost)) * 100, 1) : 0;
    $avgDeal = $total > 0 ? round($totalValue / $total, 2) : 0;
    $stats = compact('total','newMonth','pending','won','lost','totalValue','pipelineValue','wonMonthValue','closingNext30','closingNext30Value','winRate','avgDeal');

    return view('crm.deals.index', compact('deals','stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
    $opportunities = Opportunity::orderBy('name')->get();
    $contacts = Contact::orderBy('last_name')->orderBy('first_name')->get();
    return view('crm.deals.create', compact('opportunities', 'contacts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opportunity_id' => ['required', 'exists:opportunities,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'close_date' => ['required', 'date'],
            'status' => ['required', 'in:pending,won,lost'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'signed_by_contact_id' => ['nullable', 'exists:contacts,id'],
            'signed_at' => ['nullable', 'date'],
        ]);
        $data['created_by'] = auth()->id() ?? 1;

        $deal = Deal::create($data);
        return redirect()->route('deals.show', $deal)->with('success', 'Deal byl vytvořen.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal): View
    {
        $deal->load(['opportunity', 'signedByContact', 'createdBy']);
        return view('crm.deals.show', compact('deal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deal $deal): View
    {
    $opportunities = Opportunity::orderBy('name')->get();
    $contacts = Contact::orderBy('last_name')->orderBy('first_name')->get();
        return view('crm.deals.edit', compact('deal', 'opportunities', 'contacts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deal $deal): RedirectResponse
    {
        $data = $request->validate([
            'opportunity_id' => ['required', 'exists:opportunities,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'close_date' => ['required', 'date'],
            'status' => ['required', 'in:pending,won,lost'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'signed_by_contact_id' => ['nullable', 'exists:contacts,id'],
            'signed_at' => ['nullable', 'date'],
        ]);
        $deal->update($data);
        return redirect()->route('deals.show', $deal)->with('success', 'Deal byl aktualizován.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal): RedirectResponse
    {
        $deal->delete();
        return redirect()->route('deals.index')->with('success', 'Deal byl smazán.');
    }
}
