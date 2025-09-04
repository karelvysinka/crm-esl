@extends('layouts.vertical', ['page_title' => 'ActiveCampaign – Archiv běhů'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex justify-content-between">
    <h4 class="page-title">Systém / ActiveCampaign / Archiv běhů</h4>
    <div>
      <a href="{{ route('system.ac.index') }}" class="btn btn-light">Zpět na přehled</a>
    </div>
  </div>

  @include('layouts.partials.flash')

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Běhy synchronizace</h5>
      <form method="GET" class="d-flex align-items-center">
        <label class="me-2 small text-muted">Na stránku</label>
        <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
          @foreach([25,50,100,200] as $pp)
            <option value="{{ $pp }}" @selected(request('per_page', 50)==$pp)>{{ $pp }}</option>
          @endforeach
        </select>
      </form>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
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
            @forelse($runs as $run)
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
      <div class="p-2">
        {{ $runs->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
  <div class="text-end mt-2">
    <a href="{{ route('system.ac.index') }}" class="btn btn-light">Zpět</a>
  </div>
</div>
@endsection
