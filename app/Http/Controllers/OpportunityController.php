<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Contact;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityController extends Controller
{
    /**
     * Display a listing of opportunities with sales pipeline.
     */
    public function index()
    {
        $opportunities = Opportunity::with(['contact', 'company', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Sales pipeline statistics
        $stats = [
            'total_value' => Opportunity::sum('value'),
            'total_count' => Opportunity::count(),
            'won_value' => Opportunity::where('stage', 'won')->sum('value'),
            'won_count' => Opportunity::where('stage', 'won')->count(),
            'open_value' => Opportunity::whereNotIn('stage', ['won', 'lost'])->sum('value'),
            'open_count' => Opportunity::whereNotIn('stage', ['won', 'lost'])->count(),
            'avg_probability' => Opportunity::whereNotIn('stage', ['won', 'lost'])->avg('probability'),
        ];

        // Pipeline stages
        $pipeline = Opportunity::select('stage', DB::raw('count(*) as count'), DB::raw('sum(value) as total_value'))
            ->whereNotIn('stage', ['won', 'lost'])
            ->groupBy('stage')
            ->get();

        return view('opportunities.index-simple', compact('opportunities', 'stats', 'pipeline'));
    }

    /**
     * Show the form for creating a new opportunity.
     */
    public function create()
    {
        $contacts = Contact::with('company')->orderBy('first_name')->get();
        $companies = Company::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('opportunities.create-simple', compact('contacts', 'companies', 'users'));
    }

    /**
     * Store a newly created opportunity in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'probability' => 'required|integer|min:0|max:100',
            'stage' => 'required|in:prospecting,qualification,proposal,negotiation,closing,won,lost',
            'expected_close_date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'required|exists:companies,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $validated['created_by'] = auth()->id();

        $opportunity = Opportunity::create($validated);

        return redirect()->route('opportunities.index')
            ->with('success', 'Příležitost byla úspěšně vytvořena.');
    }

    /**
     * Display the specified opportunity.
     */
    public function show(Opportunity $opportunity)
    {
        $opportunity->load(['contact', 'company', 'assignedUser']);
        
        return view('opportunities.show', compact('opportunity'));
    }

    /**
     * Show the form for editing the specified opportunity.
     */
    public function edit(Opportunity $opportunity)
    {
        $contacts = Contact::with('company')->orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('opportunities.edit', compact('opportunity', 'contacts', 'companies', 'users'));
    }

    /**
     * Update the specified opportunity in storage.
     */
    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'probability' => 'required|integer|min:0|max:100',
            'stage' => 'required|in:qualification,proposal,negotiation,closed_won,closed_lost',
            'expected_close_date' => 'required|date',
            'actual_close_date' => 'nullable|date',
            'contact_id' => 'required|exists:contacts,id',
            'company_id' => 'required|exists:companies,id',
            'assigned_to' => 'nullable|exists:users,id',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:open,won,lost,on_hold',
            'notes' => 'nullable|string',
        ]);

        $opportunity->update($validated);

        return redirect()->route('opportunities.show', $opportunity)
            ->with('success', 'Příležitost byla úspěšně aktualizována.');
    }

    /**
     * Remove the specified opportunity from storage.
     */
    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();

        return redirect()->route('opportunities.index')
            ->with('success', 'Příležitost byla úspěšně smazána.');
    }

    /**
     * Show sales pipeline board view.
     */
    public function pipeline()
    {
        $stages = [
            'qualification' => 'Kvalifikace',
            'proposal' => 'Návrh',
            'negotiation' => 'Vyjednávání',
            'closed_won' => 'Uzavřeno - Vyhráno',
            'closed_lost' => 'Uzavřeno - Prohráno'
        ];

        $opportunities = Opportunity::with(['contact', 'company', 'assignedUser'])
            ->orderBy('value', 'desc')
            ->get()
            ->groupBy('stage');

        return view('opportunities.pipeline', compact('opportunities', 'stages'));
    }
}
