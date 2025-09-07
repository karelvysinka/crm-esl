<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
    $projects = Project::with(['company','contact','assignedTo'])->orderByDesc('id')->get();

    // Stats panels
    $total = Project::count();
    $newMonth = Project::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    $statusCounts = Project::select('status')->selectRaw('COUNT(*) as c')->groupBy('status')->pluck('c','status');
    $upcoming = Project::whereNotNull('due_date')->whereBetween('due_date', [now(), now()->addDays(30)])->count();
    $completed = (int) ($statusCounts['completed'] ?? 0);
    $inProgress = (int) ($statusCounts['in_progress'] ?? 0);
    $onHold = (int) ($statusCounts['on_hold'] ?? 0);
    $planned = (int) ($statusCounts['planned'] ?? 0);
    $cancelled = (int) ($statusCounts['cancelled'] ?? 0);
    $stats = compact('total','newMonth','upcoming','completed','inProgress','onHold','planned','cancelled');

    return view('crm.projects.index', compact('projects','stats'));
    }

    public function create(): View
    {
        $companies = Company::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $users = User::orderBy('name')->get();
        return view('crm.projects.create', compact('companies','contacts','users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required','in:planned,in_progress,on_hold,completed,cancelled'],
            'start_date' => ['nullable','date'],
            'due_date' => ['nullable','date'],
            'company_id' => ['nullable','exists:companies,id'],
            'contact_id' => ['nullable','exists:contacts,id'],
            'assigned_to' => ['nullable','exists:users,id'],
        ]);
        $data['created_by'] = auth()->id() ?? 1;
        $project = Project::create($data);
        return redirect()->route('projects.show', $project)->with('success','Projekt vytvořen.');
    }

    public function show(Project $project): View
    {
        $project->load(['company','contact','assignedTo']);
        return view('crm.projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        $companies = Company::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $users = User::orderBy('name')->get();
        return view('crm.projects.edit', compact('project','companies','contacts','users'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required','in:planned,in_progress,on_hold,completed,cancelled'],
            'start_date' => ['nullable','date'],
            'due_date' => ['nullable','date'],
            'company_id' => ['nullable','exists:companies,id'],
            'contact_id' => ['nullable','exists:contacts,id'],
            'assigned_to' => ['nullable','exists:users,id'],
        ]);
        $project->update($data);
        return redirect()->route('projects.show', $project)->with('success','Projekt upraven.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success','Projekt smazán.');
    }
}
