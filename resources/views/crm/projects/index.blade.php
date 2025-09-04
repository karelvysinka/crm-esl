@extends('layouts.vertical', ['page_title' => 'Projekty'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex justify-content-between">
    <h4 class="page-title">Projekty</h4>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">Nový projekt</a>
  </div>
  <div class="card">
    <div class="card-body table-responsive">
  <table class="table table-hover" id="projectsTable">
        <thead>
          <tr>
            <th>Název</th>
            <th>Status</th>
            <th>Firma</th>
            <th>Kontakt</th>
            <th>Přiřazeno</th>
            <th>Termín</th>
          </tr>
        </thead>
        <tbody>
          @forelse($projects as $project)
          <tr>
            <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span></td>
            <td>{{ $project->company->name ?? '—' }}</td>
            <td>{{ $project->contact->full_name ?? $project->contact->email ?? '—' }}</td>
            <td>{{ $project->assignedTo->name ?? '—' }}</td>
            <td>{{ optional($project->due_date)->format('d.m.Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-muted">Žádné projekty</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
$(function(){
  if ($.fn.DataTable) {
    $('#projectsTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[5, 'asc']],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/cs.json' }
    });
  }
});
</script>
@endsection
