@extends('layouts.vertical')

@section('content')
@php($viewMode = request('view','table'))
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
  <div>
    <h4 class="mb-1">Produkty</h4>
    <div class="text-muted small">Celkem: {{ number_format($products->total(),0,',',' ') }} • Strana {{ $products->currentPage() }}/{{ $products->lastPage() }}</div>
  </div>
  <div class="ms-auto d-flex align-items-center gap-2">
    <a href="{{ request()->fullUrlWithQuery(['view'=>'grid']) }}" class="btn btn-sm {{ $viewMode==='grid' ? 'btn-primary':'btn-outline-secondary' }}">
      <i class="ti ti-layout-grid"></i>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['view'=>'table']) }}" class="btn btn-sm {{ $viewMode==='table' ? 'btn-primary':'btn-outline-secondary' }}">
      <i class="ti ti-table"></i>
    </a>
  </div>
</div>

@if(!empty($stats))
  <div class="row g-3 mb-3">
    <!-- Total products -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card shadow-sm h-100">
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-start">
            <div class="avatar-sm bg-primary rounded"><i class="ri-archive-2-line avatar-title text-white font-22"></i></div>
            <div class="text-end">
              <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['total'],0,',',' ') }}</h4>
              <p class="text-muted mb-0 small text-truncate">Produkty celkem</p>
            </div>
          </div>
          <div class="mt-2 small text-muted">Záznamy v systému</div>
        </div>
      </div>
    </div>
    <!-- Added this month -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card shadow-sm h-100">
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-start">
            <div class="avatar-sm bg-success rounded"><i class="ri-calendar-event-line avatar-title text-white font-22"></i></div>
            <div class="text-end">
              <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['added_this_month'],0,',',' ') }}</h4>
              <p class="text-muted mb-0 small text-truncate">Tento měsíc</p>
            </div>
          </div>
          <div class="mt-2 small text-muted">Nově přidané</div>
        </div>
      </div>
    </div>
    <!-- Availability cards -->
    @foreach($stats['availability'] as $av)
      @php($codeLower = Str::lower($av['label']))
      @php($color = str_contains($codeLower,'sklad') ? 'success' : (str_contains($codeLower,'3') ? 'info' : (str_contains($codeLower,'objed') ? 'warning' : 'secondary')))
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card shadow-sm h-100">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start">
              <div class="avatar-sm bg-{{ $color }} rounded"><i class="ri-stack-line avatar-title text-white font-22"></i></div>
              <div class="text-end">
                <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($av['count'],0,',',' ') }}</h4>
                <p class="text-muted mb-0 small text-truncate">{{ $av['label'] }}</p>
              </div>
            </div>
            <div class="mt-2 small text-muted">Dostupnost</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif

<div class="card mb-3 shadow-sm border-0">
  <div class="card-body py-2">
    <form class="row g-2 align-items-end" method="get">
      <input type="hidden" name="view" value="{{ $viewMode }}"/>
      <div class="col-6 col-md-3 col-xl-2">
        <label class="form-label text-muted small mb-1">Hledat</label>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Název / EAN" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-3 col-xl-2">
        <label class="form-label text-muted small mb-1">Výrobce</label>
        <input type="text" name="manufacturer" value="{{ $filters['manufacturer'] ?? '' }}" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-3 col-xl-2">
        <label class="form-label text-muted small mb-1">Kategorie hash</label>
        <input type="text" name="category_hash" value="{{ $filters['category_hash'] ?? '' }}" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-3 col-xl-2">
        <label class="form-label text-muted small mb-1">Dostupnost</label>
        <select name="availability" class="form-select form-select-sm">
          <option value="">—</option>
          @foreach(config('products.availability_map') as $code=>$label)
            <option value="{{ $code }}" @selected(($filters['availability'] ?? '')===$code)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-3 col-xl-2">
        <label class="form-label text-muted small mb-1">&nbsp;</label>
        <button class="btn btn-sm btn-primary w-100"><i class="ti ti-filter"></i> Filtrovat</button>
      </div>
      @if(array_filter($filters ?? []))
        <div class="col-auto">
          <a href="{{ url()->current() }}?view={{ $viewMode }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i> Reset</a>
        </div>
      @endif
    </form>
  </div>
</div>

@if($viewMode==='table')
  <div class="card shadow-sm border-0">
    <div class="d-flex card-header justify-content-between align-items-center py-2">
      <h5 class="header-title mb-0">Seznam produktů</h5>
      <div class="small text-muted">Strana {{ $products->currentPage() }}/{{ $products->lastPage() }}</div>
    </div>
    <div class="card-body p-0">
      <div class="bg-light bg-opacity-50 py-1 text-center small">
        <p class="m-0"><b>{{ number_format($products->total(),0,',',' ') }}</b> produktů celkem • Zobrazeno {{ $products->firstItem() }}–{{ $products->lastItem() }}</p>
      </div>
      <div class="table-responsive">
        <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:44px" class="text-muted small">#</th>
              <th>Produkt</th>
              <th style="width:110px">Cena</th>
              <th style="width:150px">Dostupnost</th>
              <th style="width:110px">EAN</th>
              <th style="width:120px">Aktualizace</th>
              <th style="width:34px"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($products as $p)
              @php($availLower = Str::lower($p->availability_text))
              @php($statusColor = str_contains($availLower,'sklad') ? 'success' : (str_contains($availLower,'dostup') ? 'warning' : 'secondary'))
              <tr onclick="location='{{ route('products.show',$p) }}'">
                <td class="text-muted small">{{ $p->id }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-md flex-shrink-0 me-2">
                      <span class="avatar-title bg-light rounded-circle overflow-hidden p-0 d-inline-flex align-items-center justify-content-center">
                        @if($p->image_url)
                          <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="img-fluid" style="max-height:44px;object-fit:contain;">
                        @else
                          <span class="text-muted small">∅</span>
                        @endif
                      </span>
                    </div>
                    <div class="min-w-0">
                      <span class="text-muted fs-12 d-block text-truncate">{{ $p->manufacturer ?: 'Neznámý výrobce' }}</span>
                      <h6 class="fs-14 mt-1 mb-0 text-truncate" title="{{ $p->name }}">{{ $p->name }}</h6>
                    </div>
                  </div>
                </td>
                <td class="fw-medium">{{ number_format($p->price_vat_cents/100,2,',',' ') }} Kč</td>
                <td>
                  <span class="text-muted fs-12 d-block">Status</span>
                  <span class="d-inline-flex align-items-center gap-1"><i class="ti ti-circle-filled text-{{ $statusColor }} fs-12"></i> {{ $p->availability_text }}</span>
                </td>
                <td class="text-truncate">{{ $p->ean ?: '—' }}</td>
                <td class="text-muted small">{{ $p->updated_at?->diffForHumans() }}</td>
                <td>
                  <div class="dropdown">
                    <a href="#" class="text-muted drop-arrow-none" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
                    <div class="dropdown-menu dropdown-menu-end">
                      <a href="{{ route('products.show',$p) }}" class="dropdown-item">Detail</a>
                      <button type="button" class="dropdown-item" onclick="navigator.clipboard.writeText('{{ $p->id }}');return false;">Kopírovat ID</button>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">Žádná data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer py-2">
      <div class="row align-items-center text-center text-sm-start g-2">
        <div class="col-sm small text-muted">
          Zobrazeno <span class="fw-semibold">{{ $products->firstItem() }}–{{ $products->lastItem() }}</span> z <span class="fw-semibold">{{ number_format($products->total(),0,',',' ') }}</span>
        </div>
        <div class="col-sm-auto">
          {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@else
  <div class="row g-3">
    @forelse($products as $p)
      <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 product-card border-0 shadow-sm">
          <div class="position-relative">
            <div class="ratio ratio-4x3 bg-light rounded-top overflow-hidden">
              @if($p->image_url)
                <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="img-fluid object-fit-cover">
              @else
                <div class="d-flex w-100 h-100 align-items-center justify-content-center text-muted">Bez obrázku</div>
              @endif
            </div>
            <span class="badge position-absolute top-0 start-0 m-2 bg-{{ str_contains(Str::lower($p->availability_text),'sklad') ? 'success':'secondary' }}">{{ $p->availability_text }}</span>
          </div>
          <div class="card-body p-2 d-flex flex-column">
            <h6 class="mb-1 text-truncate" title="{{ $p->name }}">{{ $p->name }}</h6>
            <div class="small text-muted mb-1 d-flex justify-content-between">
              <span>{{ $p->manufacturer ?: '—' }}</span>
              <span class="fw-semibold">{{ number_format($p->price_vat_cents/100,2,',',' ') }} Kč</span>
            </div>
            <div class="small text-muted mb-2 text-truncate">EAN: {{ $p->ean ?: '—' }}</div>
            <div class="mt-auto d-flex justify-content-between align-items-center small text-muted">
              <span>#{{ $p->id }}</span>
              <a href="{{ route('products.show',$p) }}" class="stretched-link text-decoration-none fw-semibold">Detail →</a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12"><div class="text-center text-muted py-5">Žádná data</div></div>
    @endforelse
  </div>
@endif

@if($viewMode !== 'table')
  <div class="mt-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 pagination-wrapper">
    <div class="small text-muted order-2 order-sm-1">
      Zobrazeno {{ $products->firstItem() }}–{{ $products->lastItem() }} z {{ number_format($products->total(),0,',',' ') }}
    </div>
    <div class="order-1 order-sm-2">
      {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
  </div>
@endif

@push('styles')
<style>
  .product-card img {transition: transform .4s ease;}
  .product-card:hover img {transform: scale(1.03);} 
  .product-card .badge {backdrop-filter: blur(4px);}
  .pagination-wrapper nav ul.pagination {margin-bottom: 0;}
  .table-custom tbody tr {cursor:pointer;}
  .avatar-md {width:48px;height:48px;}
  .avatar-md .avatar-title {width:48px;height:48px;}
</style>
@endpush
@endsection
