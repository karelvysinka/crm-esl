@extends('layouts.vertical', ['page_title' => 'Znalosti – Dokumenty'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Znalostní dokumenty</h4>
    <a href="{{ route('knowledge.docs.create') }}" class="btn btn-primary">Nahrát dokument</a>
  </div>

  @if(!empty($stats))
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-primary rounded"><i class="ri-file-2-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['total'] }}</h4><p class="text-muted mb-0 small">Celkem</p></div></div><div class="mt-2 small text-muted">Dokumentů</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success rounded"><i class="ri-global-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['public'] }}</h4><p class="text-muted mb-0 small">Veřejné</p></div></div><div class="mt-2 small text-muted">Sdílené</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-warning rounded"><i class="ri-lock-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['private'] }}</h4><p class="text-muted mb-0 small">Soukromé</p></div></div><div class="mt-2 small text-muted">Jen moje</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success-subtle rounded"><i class="ri-checkbox-circle-line avatar-title text-success font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['ready'] }}</h4><p class="text-muted mb-0 small">Ready</p></div></div><div class="mt-2 small text-muted">Připravené</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-info rounded"><i class="ri-time-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['processing'] }}</h4><p class="text-muted mb-0 small">Processing</p></div></div><div class="mt-2 small text-muted">Zpracování</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-danger rounded"><i class="ri-error-warning-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['failed'] }}</h4><p class="text-muted mb-0 small">Failed</p></div></div><div class="mt-2 small text-muted">Chyby</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-primary-subtle rounded"><i class="ri-database-2-line avatar-title text-primary font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['vectorized'] }}</h4><p class="text-muted mb-0 small">Vektoriz.</p></div></div><div class="mt-2 small text-muted">Má vektory</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-secondary rounded"><i class="ri-stack-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['vectorsTotal'] }}</h4><p class="text-muted mb-0 small">Vektorů</p></div></div><div class="mt-2 small text-muted">Celkem</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-dark rounded"><i class="ri-bar-chart-2-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0">{{ $stats['avgVectors'] }}</h4><p class="text-muted mb-0 small">Průměr</p></div></div><div class="mt-2 small text-muted">Vektorů/dok</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-info-subtle rounded"><i class="ri-calendar-event-line avatar-title text-info font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['newMonth'] }}</h4><p class="text-muted mb-0 small">Nové M</p></div></div><div class="mt-2 small text-muted">Tento měsíc</div></div></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success rounded"><i class="ri-refresh-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['updatedMonth'] }}</h4><p class="text-muted mb-0 small">Aktualiz. M</p></div></div><div class="mt-2 small text-muted">Tento měsíc</div></div></div></div>
  </div>
  @endif


  <div class="card">
    <div class="card-body">
      <form method="GET" class="mb-3">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Hledat podle titulku nebo tagu" value="{{ $q }}">
          <button class="btn btn-outline-secondary" type="submit">Hledat</button>
        </div>
      </form>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Titulek</th>
              <th>Stav</th>
              <th>Viditelnost</th>
              <th>MIME</th>
              <th>Velikost</th>
              <th>Vectors</th>
              <th>Model</th>
              <th>Vectorized</th>
              <th>Akce</th>
            </tr>
          </thead>
          <tbody>
            @forelse($docs as $d)
              <tr>
                <td>{{ $d->title }}</td>
                <td><span class="badge bg-{{ $d->status==='ready' ? 'success' : ($d->status==='failed' ? 'danger' : 'secondary') }}">{{ $d->status }}</span></td>
                <td>{{ $d->visibility }}</td>
                <td>{{ $d->mime }}</td>
                <td>{{ number_format($d->size/1024,1) }} kB</td>
                <td>{{ $d->vectors_count }}</td>
                <td>{{ $d->embedding_model }}</td>
                <td>{{ optional($d->vectorized_at)->diffForHumans() }}</td>
                <td>
                  <form action="{{ route('knowledge.docs.reindex', $d->id) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" {{ $d->status!=='ready' ? 'disabled' : '' }}>Reindex</button></form>
                  <form action="{{ route('knowledge.docs.purge', $d->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Smazat vektory pro tento dokument?')">@csrf<button class="btn btn-sm btn-outline-danger">Purge</button></form>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-muted">Nic k zobrazení.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $docs->withQueryString()->links() }}</div>
    </div>
  </div>
</div>
@endsection
