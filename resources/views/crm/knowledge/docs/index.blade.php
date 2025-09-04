@extends('layouts.vertical', ['page_title' => 'Znalosti – Dokumenty'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Znalostní dokumenty</h4>
    <a href="{{ route('knowledge.docs.create') }}" class="btn btn-primary">Nahrát dokument</a>
  </div>

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
