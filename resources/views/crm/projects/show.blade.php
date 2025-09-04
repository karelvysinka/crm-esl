@extends('layouts.vertical', ['page_title' => 'Detail projektu'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projekty</a></li>
        <li class="breadcrumb-item active">{{ $project->name }}</li>
      </ol>
    </div>
    <h4 class="page-title">{{ $project->name }}</h4>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <p class="mb-1"><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$project->status)) }}</p>
          <p class="mb-1"><strong>Firma:</strong> {{ $project->company->name ?? '—' }}</p>
          <p class="mb-1"><strong>Kontakt:</strong> {{ $project->contact->full_name ?? $project->contact->email ?? '—' }}</p>
          <p class="mb-1"><strong>Přiřazeno:</strong> {{ $project->assignedTo->name ?? '—' }}</p>
          <p class="mb-1"><strong>Začátek:</strong> {{ optional($project->start_date)->format('d.m.Y') }}</p>
          <p class="mb-3"><strong>Termín:</strong> {{ optional($project->due_date)->format('d.m.Y') }}</p>
          <p>{{ $project->description }}</p>
          <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning">Upravit</a>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Smazat projekt?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger w-100">Smazat</button>
          </form>
          <hr>
          <a href="{{ route('tasks.create', ['taskable_type' => 'project', 'taskable_id' => $project->id]) }}" class="btn btn-outline-secondary w-100 mt-2">
            <i class="ri-task-line me-1"></i> Nový úkol k projektu
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
