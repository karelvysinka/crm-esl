@extends('layouts.vertical')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h4 class="mb-1">Objednávka #{{ $order->order_number }}</h4>
    <div class="text-muted small">Vytvořeno: {{ $order->order_created_at?->format('d.m.Y H:i') }} • ID {{ $order->id }}</div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left"></i> Zpět</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Přehled</h5>
        <span class="badge bg-{{ $order->is_completed ? 'success':'warning' }}">{{ $order->is_completed ? 'Dokončeno':'Probíhá' }}</span>
      </div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">Číslo</dt><dd class="col-7">{{ $order->order_number }}</dd>
          <dt class="col-5 text-muted">Datum</dt><dd class="col-7">{{ $order->order_created_at?->format('d.m.Y H:i') }}</dd>
          <dt class="col-5 text-muted">Částka</dt><dd class="col-7 fw-semibold">{{ number_format($order->total_vat_cents/100,2,',',' ') }} {{ $order->currency }}</dd>
          <dt class="col-5 text-muted">Hash</dt><dd class="col-7"><code class="text-break">{{ Str::limit($order->source_raw_hash,18) }}</code></dd>
          <dt class="col-5 text-muted">Položky (součet)</dt><dd class="col-7">{{ number_format($sumItems/100,2,',',' ') }} {{ $order->currency }}</dd>
          <dt class="col-5 text-muted">Integrita</dt><dd class="col-7">@if($integrityDiff===0)<span class="text-success">OK</span>@else<span class="text-danger">Δ {{ number_format($integrityDiff/100,2,',',' ') }}</span>@endif</dd>
          <dt class="col-5 text-muted">Změněno</dt><dd class="col-7">{{ $order->updated_at?->diffForHumans() }}</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header py-2"><h5 class="mb-0">Timeline stavů</h5></div>
      <div class="card-body">
        <ul class="timeline list-unstyled mb-0">
          @forelse($order->stateChanges as $sc)
            <li class="mb-2 d-flex gap-2">
              <div class="flex-shrink-0"><i class="ti ti-circle-filled text-primary"></i></div>
              <div class="small">
                <div><strong>{{ $sc->new_code }}</strong> <span class="text-muted">{{ $sc->changed_at?->format('d.m.Y H:i') }}</span></div>
              </div>
            </li>
          @empty
            <li class="text-muted small">Žádné stavy</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Položky ({{ $order->items->count() }})</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Název</th>
                <th>Množství</th>
                <th>Cena/ks</th>
                <th>Řádek (s DPH)</th>
                <th>Typ</th>
              </tr>
            </thead>
            <tbody>
              @forelse($order->items as $i=>$it)
                <tr>
                  <td class="text-muted small">{{ $i+1 }}</td>
                  <td class="text-truncate" style="max-width:260px">{{ $it->name }}</td>
                  <td class="text-muted small">{{ $it->quantity }}</td>
                  <td>{{ number_format($it->unit_price_vat_cents/100,2,',',' ') }} {{ $it->currency }}</td>
                  <td>{{ number_format($it->total_vat_cents/100,2,',',' ') }} {{ $it->currency }}</td>
                  <td class="text-muted small">{{ $it->line_type }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-3">Žádné položky</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .timeline {position:relative;}
  .timeline:before {content:'';position:absolute;left:6px;top:0;bottom:0;width:2px;background:var(--bs-border-color);} 
  .timeline li {position:relative;padding-left:18px;}
  .timeline li i {font-size:10px;}
</style>
@endpush
@endsection
