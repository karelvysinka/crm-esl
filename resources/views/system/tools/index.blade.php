@extends('layouts.vertical', ['page_title' => 'Systém – Nástroje pro chat'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Nástroje</h4>
    <a href="{{ route('system.chat.index') }}" class="btn btn-outline-secondary">Nastavení chatu</a>
  </div>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="row g-3">
    @foreach($tools as $tool)
      <div class="col-xl-6">
        <div class="card h-100" id="{{ $tool['key'] }}">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h5 class="mb-1">{{ $tool['name'] }}</h5>
                <span class="badge {{ $tool['enabled'] ? 'bg-success' : 'bg-secondary' }}">{{ $tool['enabled'] ? 'Zapnuto' : 'Vypnuto' }}</span>
              </div>
              <form method="POST" action="{{ route('system.tools.playwright.toggle') }}">
                @csrf
                <input type="hidden" name="enabled" value="{{ $tool['enabled'] ? '0' : '1' }}">
                <button class="btn btn-sm {{ $tool['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                  {{ $tool['enabled'] ? 'Vypnout' : 'Zapnout' }}
                </button>
              </form>
            </div>
            <p class="text-muted">{{ $tool['description'] }}</p>
            @if($tool['key']==='playwright')
            <div class="small">
              <div class="mb-2">
                <span class="fw-semibold">Runner health:</span>
                @if(($tool['health']['ok'] ?? false))
                  <span class="badge bg-success">OK</span>
                @else
                  <span class="badge bg-danger">Nedostupný</span>
                  @if(!empty($tool['health']['error']))<span class="text-danger ms-1">{{ $tool['health']['error'] }}</span>@endif
                @endif
              </div>
              <div class="fw-semibold mb-1">Záměry a použití v chatu</div>
              <ul class="mb-2 ps-3">
                <li>„Zjisti z webu …“ – vyhledání a otevření cílové stránky, extrakce potřebných údajů.</li>
                <li>„Najdi kontakt na …“ – procházení webu firmy a zjištění kontaktů, provozních informací.</li>
                <li>„Získej ceny/parametry …“ – otevření produktové stránky a extrakce tabulek/parametrů.</li>
              </ul>
              <div class="text-muted">Agent rozhodne o použití nástroje podle záměru a dostupného kontextu (viz plán v dokumentaci níže).</div>
            </div>
            <hr>
            <form method="POST" action="{{ route('system.tools.playwright.save') }}" class="mb-2">
              @csrf
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">URL runneru</label>
                  <input name="url" class="form-control" value="{{ $tool['url'] }}" placeholder="http://playwright-runner:3000" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Timeout (ms)</label>
                  <input name="timeout_ms" type="number" min="1000" max="60000" class="form-control" value="{{ $tool['timeout_ms'] }}" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Max kroků</label>
                  <input name="max_steps" type="number" min="1" max="10" class="form-control" value="{{ $tool['max_steps'] }}" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Povolené domény (CSV)</label>
                  <input name="allowed_domains" class="form-control" value="{{ $tool['allowed_domains'] }}" placeholder="esl.cz,wikipedia.org" required>
                </div>
              </div>
              <div class="mt-2 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Uložit nastavení</button>
                <a href="/crm/system/tools#playwright" class="btn btn-outline-secondary">Otevřít dokumentaci</a>
              </div>
            </form>
            <form method="POST" action="{{ route('system.tools.playwright.test') }}">
              @csrf
              <div class="input-group">
                <input name="test_url" class="form-control" value="https://www.esl.cz/" placeholder="Testovací URL">
                <button class="btn btn-outline-primary" type="submit">Otestovat nástroj</button>
              </div>
            </form>
            @if(!empty($tool['audits']) && count($tool['audits']))
            <div class="mt-3">
              <div class="fw-semibold mb-1">Poslední běhy nástroje</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr><th>#</th><th>Čas</th><th>Uživatel</th><th>Intent</th><th>Stav</th><th>Doba (ms)</th><th></th></tr>
                  </thead>
                  <tbody>
                    @foreach($tool['audits'] as $a)
                    <tr>
                      <td>{{ $a->id }}</td>
                      <td>{{ $a->created_at }}</td>
                      <td>{{ $a->user_id ?: '—' }}</td>
                      <td>{{ $a->intent }}</td>
                      <td>{{ ($a->result_meta['ok'] ?? false) ? 'OK' : ($a->result_meta['status'] ?? 'ERR') }}</td>
                      <td>{{ $a->duration_ms }}</td>
                      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#audit-detail-{{ $a->id }}">Detail</button></td>
                    </tr>
                    <!-- Modal detailu běhu -->
                    <div class="modal fade" id="audit-detail-{{ $a->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Detail běhu #{{ $a->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-2">
                              <div><span class="fw-semibold">Čas:</span> {{ $a->created_at }} | <span class="fw-semibold">Uživatel:</span> {{ $a->user_id ?: '—' }} | <span class="fw-semibold">Doba:</span> {{ $a->duration_ms }} ms</div>
                              <div><span class="fw-semibold">Stav:</span> {{ ($a->result_meta['ok'] ?? false) ? 'OK' : ($a->result_meta['status'] ?? 'ERR') }}</div>
                            </div>
                            <div class="mb-2">
                              <div class="fw-semibold">Vstup (payload)</div>
                              <pre class="bg-light p-2 rounded small" style="white-space: pre-wrap;">{{ json_encode($a->payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            <div>
                              <div class="fw-semibold">Výstup (result_meta)</div>
                              <pre class="bg-light p-2 rounded small" style="white-space: pre-wrap;">{{ json_encode($a->result_meta, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            @endif
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
