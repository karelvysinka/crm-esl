@extends('layouts.vertical', ['page_title' => 'Znalosti'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Znalosti</h4>
    <a href="{{ route('knowledge.create') }}" class="btn btn-primary">Nová poznámka</a>
  </div>

  @if(!empty($stats))
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-primary rounded"><i class="ri-book-2-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['total'] }}</h4><p class="text-muted mb-0 small">Celkem</p></div></div><div class="mt-2 small text-muted">Poznámek</div></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success rounded"><i class="ri-global-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['public'] }}</h4><p class="text-muted mb-0 small">Veřejné</p></div></div><div class="mt-2 small text-muted">Sdílené</div></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-warning rounded"><i class="ri-lock-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['private'] }}</h4><p class="text-muted mb-0 small">Soukromé</p></div></div><div class="mt-2 small text-muted">Jen moje</div></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-info rounded"><i class="ri-calendar-event-line avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['newMonth'] }}</h4><p class="text-muted mb-0 small">Tento měsíc</p></div></div><div class="mt-2 small text-muted">Nové</div></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-success-subtle rounded"><i class="ri-refresh-line avatar-title text-success font-22"></i></div><div class="text-end"><h4 class="my-0" data-plugin="counterup">{{ $stats['updatedMonth'] }}</h4><p class="text-muted mb-0 small">Aktualiz.</p></div></div><div class="mt-2 small text-muted">Tento měsíc</div></div></div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
      <div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between align-items-start"><div class="avatar-sm bg-secondary rounded"><i class="ri-hashtag avatar-title text-white font-22"></i></div><div class="text-end"><h4 class="my-0">{{ count($stats['topTags']) }}</h4><p class="text-muted mb-0 small">Top tagy</p></div></div><div class="mt-2 small text-muted">@forelse($stats['topTags'] as $tg => $cnt)<span class="badge bg-light text-dark me-1">#{{ $tg }} ({{ $cnt }})</span>@empty<span class="text-muted">Žádné</span>@endforelse</div></div></div>
    </div>
  </div>
  @endif


  <div class="card">
    <div class="card-body">
      <form method="GET" class="mb-3">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Hledat v titulcích, obsahu a tagách" value="{{ $q }}">
          <button class="btn btn-outline-secondary" type="submit">Hledat</button>
        </div>
      </form>
      <div class="list-group">
        @forelse($notes as $n)
          <a class="list-group-item list-group-item-action" href="{{ route('knowledge.edit', $n) }}">
            <div class="d-flex w-100 justify-content-between">
              <h5 class="mb-1">{{ $n->title }}</h5>
              <small class="text-muted">{{ $n->updated_at->diffForHumans() }}</small>
            </div>
            <p class="mb-1 text-muted">{{ Str::limit(strip_tags($n->content), 160) }}</p>
            @if(!empty($n->tags))
              <div>
                @foreach($n->tags as $t)
                  <span class="badge bg-light text-dark me-1">#{{ $t }}</span>
                @endforeach
              </div>
            @endif
          </a>
        @empty
          <div class="text-muted">Nic k zobrazení.</div>
        @endforelse
      </div>
      <div class="mt-3">{{ $notes->withQueryString()->links() }}</div>
    </div>
  </div>
</div>
@endsection
