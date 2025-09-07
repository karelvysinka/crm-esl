@extends('layouts.vertical', ['page_title' => 'Dealy'])

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
            <li class="breadcrumb-item active">Dealy</li>
          </ol>
        </div>
        <h4 class="page-title">Dealy</h4>
      </div>
    </div>
  </div>

  @if(!empty($stats))
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-primary rounded"><i class="ri-handbag-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['total'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Celkem</p></div></div><div class="mt-2 small text-muted">Dealů</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success rounded"><i class="ri-calendar-event-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['newMonth'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Tento měsíc</p></div></div><div class="mt-2 small text-muted">Nové dealy</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-warning rounded"><i class="ri-hourglass-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['pending'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Otevřené</p></div></div><div class="mt-2 small text-muted">Stav otevřené</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success rounded"><i class="ri-checkbox-circle-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['won'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Vyhráno</p></div></div><div class="mt-2 small text-muted">Status won</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-danger rounded"><i class="ri-close-circle-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['lost'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Prohráno</p></div></div><div class="mt-2 small text-muted">Status lost</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-secondary rounded"><i class="ri-percent-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0">{{ $stats['winRate'] }}%</h4><p class="text-muted mb-0 small">Úspěšnost</p></div></div><div class="mt-2 small text-muted">Vyhrané/(Vyhr.+Prohr.)</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-info rounded"><i class="ri-exchange-dollar-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['totalValue'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Celkem Kč</p></div></div><div class="mt-2 small text-muted">Hodnota všech</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-primary-subtle rounded"><i class="ri-stack-line avatar-title text-primary font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['pipelineValue'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Pipeline Kč</p></div></div><div class="mt-2 small text-muted">Hodnota otevřených</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success-subtle rounded"><i class="ri-award-line avatar-title text-success font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['wonMonthValue'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Vyhráno M Kč</p></div></div><div class="mt-2 small text-muted">Tento měsíc</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-warning-subtle rounded"><i class="ri-timer-line avatar-title text-warning font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['closingNext30'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Do 30 dnů</p></div></div><div class="mt-2 small text-muted">Počet uzavření</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-warning rounded"><i class="ri-money-dollar-circle-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ number_format($stats['closingNext30Value'],0,',',' ') }}</h4><p class="text-muted mb-0 small">Do 30 dnů Kč</p></div></div><div class="mt-2 small text-muted">Hodnota uzavření</div>
      </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3">
  <div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-dark rounded"><i class="ri-bar-chart-2-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0">{{ number_format($stats['avgDeal'],2,',',' ') }}</h4><p class="text-muted mb-0 small">Průměr Kč</p></div></div><div class="mt-2 small text-muted">Průměrná velikost</div>
      </div></div>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <h4 class="header-title">Seznam dealů</h4>
      <a href="{{ route('deals.create') }}" class="btn btn-primary">Nový deal</a>
    </div>
    <div class="card-body">
      @include('layouts.partials.flash')
      <div class="table-responsive">
        <table class="table table-hover" id="dealsTable">
          <thead>
            <tr>
              <th>Název</th>
              <th>Částka</th>
              <th>Close date</th>
              <th>Status</th>
              <th>Opportunity</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($deals as $deal)
              <tr>
                <td><a href="{{ route('deals.show', $deal) }}">{{ $deal->name }}</a></td>
                <td>{{ number_format($deal->amount, 0, ',', ' ') }} Kč</td>
                <td>{{ optional($deal->close_date)->format('d.m.Y') }}</td>
                <td><span class="badge bg-info">{{ ucfirst($deal->status) }}</span></td>
                <td>{{ $deal->opportunity->name ?? '—' }}</td>
                <td><a href="{{ route('deals.edit', $deal) }}" class="btn btn-sm btn-outline-warning">Upravit</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted">Žádné dealy</td></tr>
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
    $('#dealsTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[2, 'asc']],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/cs.json' },
      columnDefs: [ { orderable: false, targets: [5] } ]
    });
  }
});
</script>
@endsection
