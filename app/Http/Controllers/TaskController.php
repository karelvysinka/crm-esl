<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
    $tasks = Task::with(['assignedTo', 'createdBy', 'taskable'])->orderByDesc('due_date')->get();

    // Stats
    $total = Task::count();
    $newMonth = Task::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    $statusCounts = Task::select('status')->selectRaw('COUNT(*) as c')->groupBy('status')->pluck('c','status');
    $pending = (int) ($statusCounts['pending'] ?? 0);
    $inProgress = (int) ($statusCounts['in_progress'] ?? 0);
    $completed = (int) ($statusCounts['completed'] ?? 0);
    $cancelled = (int) ($statusCounts['cancelled'] ?? 0);
    $overdue = Task::whereNotIn('status',['completed','cancelled'])->where('due_date','<', now())->count();
    $dueNext7 = Task::whereNotIn('status',['completed','cancelled'])->whereBetween('due_date',[now(), now()->addDays(7)])->count();
    $highPriorityOpen = Task::whereIn('priority',['high','urgent'])->whereNotIn('status',['completed','cancelled'])->count();
    $completedMonth = Task::where('status','completed')->whereBetween('updated_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
    $stats = compact('total','newMonth','pending','inProgress','completed','cancelled','overdue','dueNext7','highPriorityOpen','completedMonth');

    return view('crm.tasks.index', compact('tasks','stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
    $users = User::orderBy('name')->get();
    $projects = Project::orderBy('name')->get();
    $companies = Company::orderBy('name')->get();
    $contacts = Contact::orderBy('last_name')->get();
    return view('crm.tasks.create', compact('users','projects','companies','contacts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:call,email,meeting,follow_up,proposal,other'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'due_date' => ['required', 'date'],
            'assigned_to' => ['required', 'exists:users,id'],
            'taskable_type' => ['nullable', 'in:company,contact,lead,opportunity,project'],
            'taskable_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = auth()->id() ?? 1;

        // Map simple taskable selector to FQCN
        if (!empty($data['taskable_type']) && !empty($data['taskable_id'])) {
            $map = [
                'company' => Company::class,
                'contact' => Contact::class,
                'lead' => \App\Models\Lead::class,
                'opportunity' => \App\Models\Opportunity::class,
                'project' => \App\Models\Project::class,
            ];
            $data['taskable_type'] = $map[$data['taskable_type']] ?? null;
        } else {
            $data['taskable_type'] = null;
            $data['taskable_id'] = null;
        }

        $task = Task::create($data);
        return redirect()->route('tasks.show', $task)->with('success', 'Úkol byl vytvořen.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): View
    {
        $task->load(['assignedTo', 'createdBy', 'taskable']);
        return view('crm.tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task): View
    {
        $users = User::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        // Normalize taskable to short key for form
        $taskableKey = null;
        if ($task->taskable_type) {
            $map = [
                Company::class => 'company',
                Contact::class => 'contact',
                \App\Models\Lead::class => 'lead',
                \App\Models\Opportunity::class => 'opportunity',
                \App\Models\Project::class => 'project',
            ];
            $taskableKey = $map[$task->taskable_type] ?? null;
        }
        return view('crm.tasks.edit', compact('task', 'users','projects','companies','contacts','taskableKey'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:call,email,meeting,follow_up,proposal,other'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'due_date' => ['required', 'date'],
            'assigned_to' => ['required', 'exists:users,id'],
            'taskable_type' => ['nullable', 'in:company,contact,lead,opportunity,project'],
            'taskable_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($data['taskable_type']) && !empty($data['taskable_id'])) {
            $map = [
                'company' => Company::class,
                'contact' => Contact::class,
                'lead' => \App\Models\Lead::class,
                'opportunity' => \App\Models\Opportunity::class,
                'project' => \App\Models\Project::class,
            ];
            $data['taskable_type'] = $map[$data['taskable_type']] ?? null;
        } else {
            $data['taskable_type'] = null;
            $data['taskable_id'] = null;
        }
        $task->update($data);
        return redirect()->route('tasks.show', $task)->with('success', 'Úkol byl aktualizován.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Úkol byl smazán.');
    }
}
