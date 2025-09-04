@extends('layouts.vertical', ['page_title' => 'Detail úkolu'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Úkoly</a></li>
        <li class="breadcrumb-item active">{{ $task->title }}</li>
      </ol>
    </div>
    <h4 class="page-title">{{ $task->title }}</h4>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="mb-2">Informace</h5>
          <p class="mb-1"><strong>Typ:</strong> {{ ucfirst(str_replace('_',' ',$task->type)) }}</p>
          <p class="mb-1"><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$task->status)) }}</p>
          <p class="mb-1"><strong>Priorita:</strong> {{ ucfirst(str_replace('_',' ',$task->priority)) }}</p>
          <p class="mb-1"><strong>Termín:</strong> {{ optional($task->due_date)->format('d.m.Y H:i') }}</p>
          <p class="mb-1"><strong>Projekt:</strong> {{ $task->project->name ?? '—' }}</p>
          <p class="mb-1"><strong>Vztah:</strong>
            @if($task->taskable)
              @php $label = class_basename($task->taskable_type); @endphp
              {{ $label }} — {{ $task->taskable->name ?? ($task->taskable->full_name ?? ('#'.$task->taskable_id)) }}
            @else
              —
            @endif
          </p>
          <p class="mb-3"><strong>Přiřazeno:</strong> {{ $task->assignedTo->name ?? '—' }}</p>
          <p>{{ $task->description }}</p>
          <a href="{{ route('tasks.edit', $task) }}" class="btn btn-warning">Upravit</a>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Smazat úkol?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger w-100">Smazat</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
