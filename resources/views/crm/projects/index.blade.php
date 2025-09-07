@extends('layouts.vertical', ['page_title' => 'Projekty'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex justify-content-between">
    <h4 class="page-title">Projekty</h4>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">Nový projekt</a>
  </div>
  @if(!empty($stats))
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-primary rounded"><i class="ri-folders-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['total'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Celkem</p></div>
        </div><div class="mt-2 small text-muted">Projekty</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-success rounded"><i class="ri-calendar-event-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['newMonth'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Tento měsíc</p></div>
        </div><div class="mt-2 small text-muted">Nové projekty</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-info rounded"><i class="ri-timer-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['inProgress'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Probíhá</p></div>
        </div><div class="mt-2 small text-muted">Status</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-warning rounded"><i class="ri-flag-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['planned'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Plán</p></div>
        </div><div class="mt-2 small text-muted">Status</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-success rounded"><i class="ri-check-double-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['completed'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Hotovo</p></div>
        </div><div class="mt-2 small text-muted">Status</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-secondary rounded"><i class="ri-time-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['onHold'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Pozastaveno</p></div>
        </div><div class="mt-2 small text-muted">Status</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-danger rounded"><i class="ri-close-circle-line avatar-title text-white font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['cancelled'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Zrušeno</p></div>
        </div><div class="mt-2 small text-muted">Status</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="avatar-sm bg-primary-subtle rounded"><i class="ri-calendar-schedule-line avatar-title text-primary font-22"></i></div>
          <div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['upcoming'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Do 30 dnů</p></div>
        </div><div class="mt-2 small text-muted">Termíny</div>
      </div></div>
    </div>
  </div>
  @endif
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
