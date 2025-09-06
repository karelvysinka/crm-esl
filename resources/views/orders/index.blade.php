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
<div class="row mb-3">
  <!-- Total Orders -->
  <div class="col-xl-3 col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-5">
            <div class="avatar-sm bg-primary rounded d-flex align-items-center justify-content-center">
              <i class="ri-shopping-bag-2-line avatar-title font-22 text-white"></i>
            </div>
          </div>
          <div class="col-7 text-end">
            <h3 class="text-dark my-1"><span data-plugin="counterup">{{ $stats['total'] }}</span></h3>
            <p class="text-muted mb-1 text-truncate">Celkem</p>
          </div>
        </div>
        <div class="mt-2">
          <h6 class="text-uppercase small text-muted mb-0">Objednávky</h6>
        </div>
      </div>
    </div>
  </div>
  <!-- Last Month -->
  <div class="col-xl-3 col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-5">
            <div class="avatar-sm bg-success rounded d-flex align-items-center justify-content-center">
              <i class="ri-calendar-event-line avatar-title font-22 text-white"></i>
            </div>
          </div>
          <div class="col-7 text-end">
            <h3 class="text-dark my-1"><span data-plugin="counterup">{{ $stats['last_month'] }}</span></h3>
            <p class="text-muted mb-1 text-truncate">Posl. měsíc</p>
          </div>
        </div>
        <div class="mt-2">
          <h6 class="text-uppercase small text-muted mb-0">{{ now()->subMonth()->isoFormat('MMM YYYY') }} →</h6>
        </div>
      </div>
    </div>
  </div>
  <!-- Last Week -->
  <div class="col-xl-3 col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-5">
            <div class="avatar-sm bg-info rounded d-flex align-items-center justify-content-center">
              <i class="ri-calendar-week-line avatar-title font-22 text-white"></i>
            </div>
          </div>
          <div class="col-7 text-end">
            <h3 class="text-dark my-1"><span data-plugin="counterup">{{ $stats['last_week'] }}</span></h3>
            <p class="text-muted mb-1 text-truncate">Posl. týden</p>
          </div>
        </div>
        <div class="mt-2">
          <h6 class="text-uppercase small text-muted mb-0">7 dní</h6>
        </div>
      </div>
    </div>
  </div>
  <!-- Placeholder / Avg per day (optional) -->
  <div class="col-xl-3 col-md-6">
    @php($avgDay = $stats['last_week'] ? round($stats['last_week']/7,1) : 0)
    <div class="card h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-5">
            <div class="avatar-sm bg-warning rounded d-flex align-items-center justify-content-center">
              <i class="ri-bar-chart-grouped-line avatar-title font-22 text-white"></i>
            </div>
          </div>
          <div class="col-7 text-end">
            <h3 class="text-dark my-1">{{ $avgDay }}</h3>
            <p class="text-muted mb-1 text-truncate">/den (7d)</p>
          </div>
        </div>
        <div class="mt-2">
          <h6 class="text-uppercase small text-muted mb-0">Průměr</h6>
        </div>
      </div>
    </div>
  </div>
</div>
@endisset

@if(isset($chart))
<div class="row mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="header-title">Objednávky v čase</h4>
        <p class="text-muted font-13 mb-3">Počet objednávek a tržby za posledních 12 měsíců.</p>
        <div class="px-1" dir="ltr">
          <div id="orders-12m-chart" class="apex-charts" style="height:320px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

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
  .small-hover:hover { background: rgba(0,0,0,.03); transition: background .2s; }
  #orders-12m-chart .apexcharts-canvas { margin: 0 auto; }
  .avatar-sm { width:48px; height:48px; }
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
  @if(isset($chart))
  (function(){
    function renderChart(){
      const options = {
        chart:{ type:'line', height:200, toolbar:{show:false}, fontFamily:'inherit' },
        stroke:{ curve:'smooth', width:3 },
        series:[
          { name:'Objednávky', type:'column', data:@json($chart['orders']) },
          { name:'Tržby (CZK)', type:'line', data:@json($chart['revenue']) }
        ],
        labels:@json($chart['labels']),
        xaxis:{ categories:@json($chart['labels']), labels:{ style:{ fontSize:'11px' } } },
        yaxis:[
          { labels:{ style:{ fontSize:'11px' } }, title:{ text:'Objednávky', style:{ fontSize:'11px' } }, min:0 },
          { opposite:true, labels:{ style:{ fontSize:'11px' } }, title:{ text:'CZK', style:{ fontSize:'11px' } }, min:0 }
        ],
        colors:['#35b8e0','#188ae2'],
        legend:{ position:'top', horizontalAlign:'right', fontSize:'11px' },
        grid:{ strokeDashArray:4 },
        tooltip:{ shared:true }
      };
      try { new ApexCharts(document.querySelector('#orders-12m-chart'), options).render(); } catch(e) { console.error(e); }
    }
    if(window.ApexCharts) { renderChart(); }
    else {
      const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/apexcharts'; s.onload=renderChart; document.head.appendChild(s);
    }
  })();
  @endif
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
