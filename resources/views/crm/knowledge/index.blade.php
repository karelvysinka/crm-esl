@extends('layouts.vertical', ['page_title' => 'Znalosti'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Znalosti</h4>
    <a href="{{ route('knowledge.create') }}" class="btn btn-primary">Nová poznámka</a>
  </div>

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
