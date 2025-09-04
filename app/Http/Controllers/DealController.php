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
        return view('crm.deals.index', compact('deals'));
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
