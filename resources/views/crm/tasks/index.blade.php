@extends('layouts.vertical', ['page_title' => 'Úkoly'])

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
            <li class="breadcrumb-item active">Úkoly</li>
          </ol>
        </div>
        <h4 class="page-title">Úkoly</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <h4 class="header-title">Seznam úkolů</h4>
      <a href="{{ route('tasks.create') }}" class="btn btn-primary">Nový úkol</a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
  <table class="table table-hover" id="tasksTable">
          <thead>
            <tr>
              <th>Titulek</th>
              <th>Typ</th>
              <th>Status</th>
              <th>Priorita</th>
              <th>Termín</th>
              <th>Projekt</th>
              <th>Vztah</th>
              <th>Přiřazeno</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($tasks as $task)
              <tr>
                <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                <td>{{ ucfirst(str_replace('_',' ',$task->type)) }}</td>
                <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',$task->priority)) }}</span></td>
                <td>{{ optional($task->due_date)->format('d.m.Y H:i') }}</td>
                <td>{{ $task->project->name ?? '—' }}</td>
                <td>
                  @if($task->taskable)
                    @php
                      $label = class_basename($task->taskable_type);
                      $name = $task->taskable->name ?? ($task->taskable->full_name ?? ('#'.$task->taskable_id));
                    @endphp
                    <span class="text-muted">{{ $label }}:</span> {{ $name }}
                  @else
                    —
                  @endif
                </td>
                <td>{{ $task->assignedTo->name ?? '—' }}</td>
                <td>
                  <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-warning">Upravit</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-muted">Žádné úkoly</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
$(function(){
  if ($.fn.DataTable) {
    $('#tasksTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[4, 'asc']],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/cs.json' },
      columnDefs: [
        { orderable: false, targets: [8] }
      ]
    });
  }
});
</script>
@endsection
