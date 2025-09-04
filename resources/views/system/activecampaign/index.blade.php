@extends('layouts.vertical', ['page_title' => 'ActiveCampaign'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex justify-content-between">
    <h4 class="page-title">Systém / ActiveCampaign</h4>
  </div>

  @include('layouts.partials.flash')

  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Rychlé akce</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('system.ac.test') }}" class="d-inline">
            @csrf
            <button class="btn btn-outline-primary">Otestovat připojení</button>
          </form>
          <form method="POST" action="{{ route('system.ac.import10') }}" class="d-inline ms-2">
            @csrf
            <button class="btn btn-primary">Import prvních 10 kontaktů</button>
          </form>
          <form method="POST" action="{{ route('system.ac.import') }}" class="d-inline ms-2">
            @csrf
            <input type="number" name="limit" class="form-control d-inline w-auto" value="100" min="1" max="500" title="Počet" />
            <input type="number" name="offset" class="form-control d-inline w-auto ms-1" value="0" min="0" title="Offset" />
            <button class="btn btn-success ms-1">Import dávky</button>
          </form>
          <form method="POST" action="{{ route('system.ac.importAll') }}" class="d-inline ms-2" onsubmit="return confirm('Spustit Import všeho do fronty?');">
            @csrf
            <input type="number" name="limit" class="form-control d-inline w-auto" value="100" min="1" max="500" title="Limit na stránku" />
            <input type="number" name="start" class="form-control d-inline w-auto ms-1" value="0" min="0" title="Start offset" />
            <input type="number" name="max" class="form-control d-inline w-auto ms-1" placeholder="max (nepovinné)" title="Maximální počet (volit.)" />
            <button class="btn btn-warning ms-1">Import všeho (fronta)</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Automatika synchronizace</h5></div>
        <div class="card-body">
          <p class="text-muted mb-2 small">Automatická synchronizace načítá dávky z ActiveCampaign a průběžně je importuje bez duplicit. Lze ji kdykoliv zapnout/vypnout.</p>
          <div class="d-flex align-items-center mb-2">
            <form method="POST" action="{{ route('system.ac.toggle') }}">
              @csrf
              <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}" />
              <button class="btn {{ $enabled ? 'btn-danger' : 'btn-success' }}">
                {{ $enabled ? 'Vypnout automatiku' : 'Zapnout automatiku' }}
              </button>
            </form>
            <div class="ms-3">
              <span class="badge {{ $enabled ? 'bg-success' : 'bg-secondary' }}">Stav: {{ $enabled ? 'zapnuto' : 'vypnuto' }}</span>
            </div>
          </div>
          <div class="mt-2">
            <div class="mb-2">Aktuální offset: <strong>{{ $offset }}</strong></div>
            <form method="POST" action="{{ route('system.ac.resetOffset') }}" class="d-inline" onsubmit="return confirm('Resetovat offset na 0?');">
              @csrf
              <button class="btn btn-outline-secondary">Resetovat offset</button>
            </form>
            <form method="POST" action="{{ route('system.ac.runBatch') }}" class="d-inline ms-2">
              @csrf
              <input type="number" name="limit" class="form-control d-inline w-auto" value="200" min="1" max="500" title="Limit dávky" />
              <button class="btn btn-outline-primary ms-1">Spustit 1 dávku nyní</button>
            </form>
          </div>
        </div>
      </div>
    </div>

  <div class="col-lg-6 mt-3">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Konfigurace</h5></div>
        <div class="card-body">
          @php
            $acBase = config('services.activecampaign.base_url');
            $acToken = config('services.activecampaign.api_token');
          @endphp
          <ul class="list-unstyled mb-0">
            <li>
              <strong>AC_BASE_URL:</strong>
              <span class="ms-1">{{ $acBase ?: 'nenastaveno' }}</span>
            </li>
            <li class="mt-1">
              <strong>AC_API_TOKEN:</strong>
              <span class="ms-1">{{ $acToken ? 'nastaven (skrytý)' : 'nenastaven' }}</span>
            </li>
          </ul>
          <p class="text-muted mt-2 small">Formát URL: např. https://YOURACCOUNT.api-us1.com (bez /api/3). Token najdete v ActiveCampaign → Settings → Developer.</p>
          <p class="text-muted mt-2 small">Bezpečnost: stránka je určena jen pro administrátory. Pro rozhraní API je možné použít hlavičku X-Admin-Token s hodnotou z proměnné ADMIN_TOKEN.</p>
        </div>
      </div>
    </div>

    <div class="col-lg-12 mt-3">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Poslední běhy synchronizace</h5>
          <small class="text-muted">pro rychlou kontrolu co se zpracovalo</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th class="text-nowrap">ID</th>
                  <th>Začátek</th>
                  <th>Konec</th>
                  <th class="text-end">Limit</th>
                  <th class="text-end">Offset</th>
                  <th class="text-end">Vytvořeno</th>
                  <th class="text-end">Aktualizováno</th>
                  <th class="text-end">Přeskočeno</th>
                  <th class="text-end">Beze změny</th>
                  <th class="text-end">Chyby</th>
                  <th>Ukázky ID</th>
                  <th>Zpráva</th>
                </tr>
              </thead>
              <tbody>
                @forelse(($runs ?? collect()) as $run)
                  <tr>
                    <td>{{ $run->id }}</td>
                    <td>{{ optional($run->started_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ optional($run->finished_at)->format('Y-m-d H:i:s') }}</td>
                    <td class="text-end">{{ $run->limit }}</td>
                    <td class="text-end">{{ $run->offset }}</td>
                    <td class="text-end">{{ $run->created }}</td>
                    <td class="text-end">{{ $run->updated }}</td>
                    <td class="text-end">{{ $run->skipped }}</td>
                    <td class="text-end">{{ $run->skipped_unchanged }}</td>
                    <td class="text-end">{{ $run->errors }}</td>
                    <td class="small">
                      @php($sc = (array)($run->sample_created_ids ?? []))
                      @php($su = (array)($run->sample_updated_ids ?? []))
                      @if($sc)
                        <div><strong>Vytvořené:</strong>
                          @foreach(array_slice($sc, 0, 10) as $cid)
                            <a href="{{ url('/crm/contacts/' . $cid) }}" target="_blank">{{ $cid }}</a>@if(!$loop->last), @endif
                          @endforeach
                        </div>
                      @endif
                      @if($su)
                        <div><strong>Aktualizované:</strong>
                          @foreach(array_slice($su, 0, 10) as $cid)
                            <a href="{{ url('/crm/contacts/' . $cid) }}" target="_blank">{{ $cid }}</a>@if(!$loop->last), @endif
                          @endforeach
                        </div>
                      @endif
                    </td>
                    <td class="small">{{ $run->message }}</td>
                  </tr>
                @empty
                  <tr><td colspan="12" class="text-center text-muted py-3">Žádné záznamy běhů zatím nejsou.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 mt-2 text-end">
      <a href="{{ route('system.ac.runs') }}" class="btn btn-outline-secondary btn-sm">Otevřít archiv běhů</a>
    </div>

  </div>
</div>
@endsection
