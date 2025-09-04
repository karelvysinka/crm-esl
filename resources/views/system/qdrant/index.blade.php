@extends('layouts.vertical', ['page_title' => 'Qdrant – Status a Test vyhledávání'])

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">Qdrant – Status</h4>
        <div>
          <span class="badge {{ ($health ?? null) === 'ok' ? 'bg-success' : 'bg-danger' }}">
            Health: {{ $health ?? 'unknown' }}
          </span>
          <span class="badge {{ ($ensured ?? false) ? 'bg-primary' : 'bg-secondary' }} ms-2">
            Collection: {{ $collection }}
          </span>
          <span class="badge bg-info ms-2">Vectors: {{ $total }}</span>
        </div>
      </div>
      <div class="card-body">
        @if(session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <dl class="row">
          <dt class="col-sm-3">Qdrant URL</dt>
          <dd class="col-sm-9"><code>{{ $baseUrl }}</code></dd>
          <dt class="col-sm-3">Collection</dt>
          <dd class="col-sm-9"><code>{{ $collection }}</code></dd>
        </dl>

        <form action="{{ route('system.qdrant.verify') }}" method="POST" class="mb-3">
          @csrf
          <button class="btn btn-outline-secondary" type="submit">Ověřit nastavení</button>
        </form>
        @if(isset($verify))
          <div class="mb-2">
            <span class="badge {{ ($verify['embedder'] ?? 'fail')==='ok' ? 'bg-success' : 'bg-danger' }}">Embedder: {{ $verify['embedder'] ?? 'unknown' }}</span>
            <span class="badge {{ ($verify['qdrant'] ?? 'fail')==='ok' ? 'bg-success' : 'bg-danger' }} ms-2">Qdrant: {{ $verify['qdrant'] ?? 'unknown' }}</span>
            <span class="badge {{ ($verify['dimension_match'] ?? 'unknown')==='ok' ? 'bg-success' : (($verify['dimension_match'] ?? '')==='mismatch' ? 'bg-warning' : 'bg-secondary') }} ms-2">Dimenze: {{ $verify['dimension_match'] ?? 'unknown' }}</span>
          </div>
          <div class="text-muted small mb-3">
            <div>Embedder model: <code>{{ $verify_embedder_model ?? 'n/a' }}</code></div>
            <div>Očekávaná dimenze (UI): <code>{{ $verify_dim ?? 'n/a' }}</code>, Qdrant vectors.size: <code>{{ $verify_qdrant_size ?? 'n/a' }}</code></div>
            <div>Mini test: "{{ $verify_sample ?? '' }}" → výsledků: <code>{{ isset($verify_search_count) ? $verify_search_count : 'n/a' }}</code></div>
          </div>
          @if(!empty($verify_messages))
            <div class="alert alert-warning">
              <ul class="mb-0">
                @foreach($verify_messages as $m)
                  <li>{{ $m }}</li>
                @endforeach
              </ul>
            </div>
          @else
            <div class="alert alert-success">Nastavení vypadá v pořádku.</div>
          @endif
        @endif

        <hr>
        <h5 class="mb-2">Nastavení embeddings</h5>
        <form action="{{ route('system.qdrant.save') }}" method="POST" class="row g-3 mb-4">
          @csrf
          <div class="col-md-4">
            <label class="form-label">Poskytovatel</label>
            <select name="provider" class="form-select">
              @php $p = $embed['provider'] ?? 'local'; @endphp
              <option value="openai" {{ $p==='openai'?'selected':'' }}>OpenAI</option>
              <option value="openrouter" {{ $p==='openrouter'?'selected':'' }}>OpenRouter (OpenAI-kompatibilní)</option>
              <option value="local" {{ $p==='local'?'selected':'' }}>Lokální (Seznam/small-e-czech)</option>
              <option value="gemini" {{ $p==='gemini'?'selected':'' }}>Google Gemini</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Model</label>
            @php
              $defaultModel = $embed['model'] ?? 'Seznam/small-e-czech';
            @endphp
            <input type="text" class="form-control" name="model" value="{{ $defaultModel }}" placeholder="např. text-embedding-3-small" required>
            <div class="form-text">Pro lokální: <code>Seznam/small-e-czech</code> (256 dim). Pro OpenAI: <code>text-embedding-3-small</code> (1536 dim).</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Rozměr (dimension)</label>
            @php
              $dimVal = $embed['dimension'] ?? '';
              if ($dimVal === '' && ($defaultModel ?? '') === 'text-embedding-3-small') { $dimVal = 1536; }
              if (!$dimVal && ($p ?? '') === 'local') { $dimVal = 256; }
            @endphp
            <input type="number" class="form-control" name="dimension" value="{{ $dimVal }}" placeholder="např. 256">
            <div class="form-text">Musí odpovídat zvolenému modelu (např. Seznam/small-e-czech = 256, text-embedding-3-small = 1536).</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Lokální embedder URL</label>
            @php $localUrl = $embed['local_url'] ?? 'http://embedder:8080'; @endphp
            <input type="url" class="form-control" name="local_url" value="{{ $localUrl }}" placeholder="http://embedder:8080">
            <div class="form-text">Pouze interní síť. Není třeba publikovat na hostitele.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">OpenRouter API Key</label>
            <input type="password" class="form-control" name="openrouter_api_key" value="{{ $embed['openrouter_api_key'] ? '••••••••' : '' }}" placeholder="skrytý – zadejte pro změnu">
          </div>
          <div class="col-md-4">
            <label class="form-label">OpenRouter Referer (HTTP-Referer)</label>
            <input type="text" class="form-control" name="openrouter_referer" value="{{ $embed['openrouter_referer'] ?? '' }}" placeholder="https://váš-web.example">
          </div>
          <div class="col-md-4">
            <label class="form-label">OpenRouter X-Title</label>
            <input type="text" class="form-control" name="openrouter_title" value="{{ $embed['openrouter_title'] ?? '' }}" placeholder="Název aplikace pro žebříček">
          </div>
          <div class="col-12">
            <button class="btn btn-success" type="submit">Uložit nastavení</button>
          </div>
        </form>

        <hr>
        <h5>Test vyhledávání</h5>
        <form action="{{ route('system.qdrant.test') }}" method="POST" class="mb-3">
          @csrf
          <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Zadejte dotaz…" value="{{ $query ?? '' }}" required>
            <button class="btn btn-primary" type="submit">Hledat</button>
          </div>
        </form>

        @if(!empty($error))
          <div class="alert alert-danger">Chyba: {{ $error }}</div>
        @endif

        @if(is_array($results))
          @if(count($results) === 0 && ($query ?? '') !== '')
            <div class="alert alert-warning">Žádné výsledky.</div>
          @endif
          @if(count($results) > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Score</th>
                    <th>Document ID</th>
                    <th>Chunk</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($results as $i => $r)
                    @php
                      $payload = $r['payload'] ?? [];
                      $preview = $payload['preview'] ?? ($payload['text'] ?? '');
                      $docId = $payload['document_id'] ?? ($payload['documentId'] ?? '');
                    @endphp
                    <tr>
                      <td>{{ $i+1 }}</td>
                      <td>{{ number_format($r['score'] ?? 0, 4) }}</td>
                      <td>{{ $docId }}</td>
                      <td style="max-width: 640px">
                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($preview, 240) }}</div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        @endif

        <hr>
        <h5>Údržba kolekce</h5>
        <div class="d-flex gap-2">
          <form action="{{ route('system.qdrant.recreate') }}" method="POST" onsubmit="return confirm('Opravdu znovu vytvořit kolekci? VŠECHNA data budou smazána.');">
            @csrf
            <input type="hidden" name="dimension" value="{{ $dimVal ?: 256 }}">
            <button class="btn btn-outline-danger" type="submit">Recreate collection (size={{ $dimVal ?: 256 }})</button>
          </form>
          <form action="{{ route('system.qdrant.purgeReindex') }}" method="POST" onsubmit="return confirm('Spustit Purge & Reindex?');">
            @csrf
            <button class="btn btn-outline-warning" type="submit">Purge & Reindex</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
