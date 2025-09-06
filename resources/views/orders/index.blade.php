@extends('layouts.vertical')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
  <div>
    <h4 class="mb-1">Objednávky</h4>
    <div class="text-muted small">Celkem: {{ number_format($orders->total(),0,',',' ') }} • Strana {{ $orders->currentPage() }}/{{ $orders->lastPage() }}</div>
  </div>
  @php($__canImport = auth()->user() && (auth()->user()->can('ops.execute') || auth()->user()->can('ops.view') || str_ends_with(auth()->user()->email,'@crm.esl.cz')))
  @if($__canImport)
    <div class="d-flex gap-2 align-items-start">
      <form method="post" action="{{ route('orders.triggerImport') }}" onsubmit="return confirm('Spustit full import? Spustí se na pozadí.')">
        @csrf
        <input type="hidden" name="_ops_token" value="{{ Str::random(16) }}">
        <div class="input-group input-group-sm">
          <input type="number" name="pages" class="form-control" placeholder="pages" min="1" style="max-width:90px" title="Limit počtu stránek (volitelné)">
          <button class="btn btn-outline-primary"><i class="ti ti-cloud-download"></i> Full import</button>
        </div>
        <div class="form-text small text-muted">Spustí job `orders:import-full` (queue)</div>
      </form>
    </div>
  @endif
</div>

@isset($stats)
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card shadow-sm border-0 bg-light">
      <div class="card-body py-2 d-flex flex-wrap gap-4">
        <div>
          <div class="text-muted small text-uppercase">Celkem objednávek</div>
          <div class="fw-semibold fs-5 mb-0">{{ number_format($stats['total'],0,'',' ') }}</div>
        </div>
        <div>
          <div class="text-muted small text-uppercase">Za poslední měsíc</div>
          <div class="fw-semibold fs-5 mb-0">{{ number_format($stats['last_month'],0,'',' ') }}</div>
        </div>
        <div>
          <div class="text-muted small text-uppercase">Za poslední týden</div>
          <div class="fw-semibold fs-5 mb-0">{{ number_format($stats['last_week'],0,'',' ') }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endisset

<div class="card mb-3 shadow-sm border-0">
  <div class="card-body py-2">
    <form class="row g-2 align-items-end" method="get">
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">Hledat</label>
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Číslo" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">Stav (code)</label>
        <input type="text" name="state" value="{{ $filters['state'] }}" class="form-control form-control-sm" placeholder="NEW">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">Od</label>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">Do</label>
        <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">Komplet.</label>
        <select name="completed" class="form-select form-select-sm">
          <option value="">—</option>
          <option value="1" @selected($filters['completed']==='1')>Ano</option>
          <option value="0" @selected($filters['completed']==='0')>Ne</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label text-muted small mb-1">&nbsp;</label>
        <button class="btn btn-sm btn-primary w-100"><i class="ti ti-filter"></i> Filtrovat</button>
      </div>
      @if(array_filter($filters))
        <div class="col-auto">
          <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i> Reset</a>
        </div>
      @endif
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="d-flex card-header justify-content-between align-items-center py-2">
    <h5 class="header-title mb-0">Seznam objednávek</h5>
    <div class="small text-muted">Zobrazeno {{ $orders->firstItem() }}–{{ $orders->lastItem() }}</div>
  </div>
  @if(isset($runningImports) && $runningImports->count())
    <div class="alert alert-info border-0 rounded-0 mb-0 py-2 px-3 d-flex flex-wrap gap-3 align-items-center">
      <div class="fw-semibold"><i class="ti ti-activity me-1"></i>Probíhající importy:</div>
      @foreach($runningImports as $ri)
        <div class="badge bg-warning-subtle text-dark border border-warning d-flex align-items-center gap-1">
          <span>#{{ $ri->id }}</span>
          <span class="text-uppercase small">{{ Str::replace('orders.full_import','FULL',$ri->type) }}</span>
          <span class="small">status: {{ $ri->status }}</span>
          @if(!empty($ri->meta['pages']))<span class="small">pages={{ $ri->meta['pages'] }}</span>@endif
        </div>
      @endforeach
      <a href="{{ route('ops.history.index') }}" class="ms-auto small">Historie &rsaquo;</a>
    </div>
  @endif
  @if(isset($lastImports) && $lastImports->count())
    <div class="alert alert-secondary border-0 rounded-0 mb-0 py-2 px-3 d-flex flex-wrap gap-3 align-items-center" style="border-top:1px solid #eee;">
      <div class="fw-semibold"><i class="ti ti-history me-1"></i>Poslední importy:</div>
      @foreach($lastImports as $li)
        <div class="badge bg-light text-dark border d-flex flex-column align-items-start p-2" style="min-width:140px;">
          <div class="d-flex w-100 justify-content-between"><span>#{{ $li->id }}</span><span class="small {{ $li->status==='success'?'text-success':($li->status==='error'?'text-danger':'text-warning') }}">{{ $li->status }}</span></div>
          <div class="small">new: {{ $li->meta['orders_new'] ?? '—' }}</div>
          <div class="small">pages: {{ $li->meta['pages'] ?? '—' }}</div>
          <div class="small">ms: {{ $li->meta['duration_ms'] ?? '—' }}</div>
          @if(!empty($li->meta['skipped_reason']))<div class="small text-muted">skip: {{ $li->meta['skipped_reason'] }}</div>@endif
        </div>
      @endforeach
    </div>
  @endif
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Číslo</th>
            <th>Datum</th>
            <th>Částka</th>
            <th>Měna</th>
            <th>Stavy (poslední)</th>
            <th>Položky</th>
            <th>Kompl.</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $o)
            @php($lastState = $o->stateChanges()->latest('changed_at')->value('new_code'))
            <tr onclick="location='{{ route('orders.show',$o) }}'" style="cursor:pointer;">
              <td class="text-muted small">{{ $o->id }}</td>
              <td class="fw-semibold">{{ $o->order_number }}</td>
              <td class="text-muted small">{{ $o->order_created_at?->format('d.m.Y H:i') }}</td>
              <td>{{ number_format($o->total_vat_cents/100,2,',',' ') }}</td>
              <td class="text-muted small">{{ $o->currency }}</td>
              <td class="text-muted small">{{ $lastState ?: '—' }}</td>
              <td class="text-muted small">{{ $o->items_count }}</td>
              <td>@if($o->is_completed)<i class="ti ti-check text-success"></i>@else<i class="ti ti-clock text-warning"></i>@endif</td>
              <td class="text-end"><i class="ti ti-chevron-right text-muted"></i></td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Žádná data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer py-2">
    {{ $orders->onEachSide(1)->links('pagination::bootstrap-5') }}
  </div>
</div>

@push('styles')
<style>
  .table tbody tr:hover { background: rgba(0,0,0,.03); }
</style>
@endpush

@push('scripts')
<script>
  (function(){
    const form = document.querySelector('form[action*="trigger-import"]');
    if(!form) return;
    form.addEventListener('submit', function(e){
      const btn = form.querySelector('button[type="submit"],button:not([type])');
      if(btn){ btn.disabled=true; btn.dataset.originalText=btn.innerHTML; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Queuing...'; }
    });
  })();
</script>
@endpush

@if(session('status'))
  <div class="alert alert-success mt-3 d-flex justify-content-between align-items-center">
    <div>
      <strong>{{ session('status') }}</strong>
      @if(session('activity_id')) – Aktivita #{{ session('activity_id') }} (<a href="{{ route('ops.history.index') }}" class="alert-link">Historie</a>) @endif
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if($errors->any())
  <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
@endif
@endsection
