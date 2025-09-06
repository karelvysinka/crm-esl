@extends('layouts.vertical')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Produkt #{{ $product->id }}</h4>
  <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">Zpět</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h5>{{ $product->name }}</h5>
        <p class="small text-muted mb-1">External: {{ $product->external_id }} | Group: {{ $product->group_id ?? '—' }}</p>
        <p class="mb-2">{{ $product->description ?? 'Bez popisu' }}</p>
        <dl class="row mb-0 small">
          <dt class="col-sm-4">Cena</dt><dd class="col-sm-8 fw-semibold">{{ number_format($product->price_vat_cents/100,2,',',' ') }} CZK</dd>
          <dt class="col-sm-4">Výrobce</dt><dd class="col-sm-8">{{ $product->manufacturer ?? '—' }}</dd>
          <dt class="col-sm-4">EAN</dt><dd class="col-sm-8">{{ $product->ean ?? '—' }}</dd>
          <dt class="col-sm-4">Kategorie</dt><dd class="col-sm-8">{{ $product->category_path }}</dd>
          <dt class="col-sm-4">Dostupnost</dt><dd class="col-sm-8"><span class="badge bg-info">{{ $product->availability_text }}</span> ({{ $product->availability_code ?? '—' }})</dd>
          <dt class="col-sm-4">Sklad</dt><dd class="col-sm-8">{{ $product->stock_quantity ?? '—' }}</dd>
          <dt class="col-sm-4">URL</dt><dd class="col-sm-8"><a href="{{ $product->url }}" target="_blank">Detail</a></dd>
          <dt class="col-sm-4">Obrázek</dt><dd class="col-sm-8">@if($product->image_url)<img src="{{ $product->image_url }}" style="max-height:120px" class="border rounded">@else — @endif</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header py-2"><strong>Historie cen (posledních 50)</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Čas</th><th>Staré</th><th>Nové</th></tr></thead>
          <tbody>
            @forelse($priceHistory as $h)
              <tr>
                <td class="small">{{ $h->changed_at->format('Y-m-d H:i') }}</td>
                <td class="text-muted small">{{ number_format($h->old_price_cents/100,2,',',' ') }}</td>
                <td class="fw-semibold small">{{ number_format($h->new_price_cents/100,2,',',' ') }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted small">Žádné změny</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header py-2"><strong>Historie dostupnosti (posledních 50)</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Čas</th><th>Kód</th><th>Stock</th></tr></thead>
          <tbody>
            @forelse($availabilityHistory as $h)
              <tr>
                <td class="small">{{ $h->changed_at->format('Y-m-d H:i') }}</td>
                <td class="small">{{ $h->new_code }}</td>
                <td class="small">{{ $h->new_stock_qty ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted small">Žádné změny</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
