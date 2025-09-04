<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request): View
	{
		$query = Lead::query()->with(['assignedTo']);

		// Optional filters
		if ($status = $request->string('status')->toString()) {
			$query->where('status', $status);
		}
		if ($source = $request->string('source')->toString()) {
			$query->where('source', $source);
		}
		if ($assigned = $request->integer('assigned_to')) {
			$query->where('assigned_to', $assigned);
		}

	// Use collection (non-paginated) to match DataTables usage in the view
	$leads = $query->orderByDesc('created_at')->get();
	$leadsByStatus = $leads->groupBy('status');

	return view('crm.leads.index', compact('leads', 'leadsByStatus'));
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create(): View
	{
		$users = User::orderBy('name')->get();
		$sources = ['website', 'referral', 'social_media', 'cold_call', 'email_campaign', 'trade_show', 'other'];
		$statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
		return view('crm.leads.create', compact('users', 'sources', 'statuses'));
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request): RedirectResponse
	{
		$data = $request->validate([
			'company_name' => ['required', 'string', 'max:255'],
			'contact_name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'phone' => ['nullable', 'string', 'max:50'],
			'source' => ['required', 'in:website,referral,social_media,cold_call,email_campaign,trade_show,other'],
			'status' => ['required', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
			'estimated_value' => ['nullable', 'numeric', 'min:0'],
			'notes' => ['nullable', 'string'],
			'assigned_to' => ['nullable', 'exists:users,id'],
		]);

	$data['created_by'] = auth()->id() ?? 1; // fallback to dummy user id

		$lead = Lead::create($data);
		$lead->updateScore();

	return redirect()->route('leads.show', $lead)->with('success', 'Lead byl úspěšně vytvořen.');
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Lead $lead): View
	{
		$lead->load(['assignedTo', 'createdBy']);
		return view('crm.leads.show', compact('lead'));
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(Lead $lead): View
	{
		$users = User::orderBy('name')->get();
		$sources = ['website', 'referral', 'social_media', 'cold_call', 'email_campaign', 'trade_show', 'other'];
		$statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
		return view('crm.leads.edit', compact('lead', 'users', 'sources', 'statuses'));
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, Lead $lead): RedirectResponse
	{
		$data = $request->validate([
			'company_name' => ['required', 'string', 'max:255'],
			'contact_name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255'],
			'phone' => ['nullable', 'string', 'max:50'],
			'source' => ['required', 'in:website,referral,social_media,cold_call,email_campaign,trade_show,other'],
			'status' => ['required', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
			'estimated_value' => ['nullable', 'numeric', 'min:0'],
			'notes' => ['nullable', 'string'],
			'assigned_to' => ['nullable', 'exists:users,id'],
		]);

		$lead->update($data);
		$lead->updateScore();

	return redirect()->route('leads.show', $lead)->with('success', 'Lead byl úspěšně aktualizován.');
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Lead $lead): RedirectResponse
	{
		$lead->delete();
	return redirect()->route('leads.index')->with('success', 'Lead byl úspěšně smazán.');
	}

	/**
	 * Kanban board view.
	 */
	public function kanban(Request $request): View
	{
		// Reuse index view which contains both Kanban and Table view
		return $this->index($request);
	}

	/**
	 * Update lead status (used by kanban or inline actions).
	 */
	public function updateStatus(Request $request, Lead $lead)
	{
		$validated = $request->validate([
			'status' => ['required', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
		]);
		$lead->update($validated);
		$lead->updateScore();

		if ($request->expectsJson() || $request->wantsJson() || $request->header('Content-Type') === 'application/json') {
			return response()->json(['success' => true]);
		}

		return back()->with('success', 'Status leadu byl aktualizován.');
	}
}

