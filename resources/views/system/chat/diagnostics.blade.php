@extends('layouts.vertical', ['title' => 'Chat – Diagnostika'])

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">Chat – Diagnostika</h4>
        <a href="{{ route('system.chat.index') }}" class="btn btn-sm btn-outline-secondary">Zpět na nastavení</a>
      </div>
      <div class="card-body">
        <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('system.chat.diagnostics') }}">
          <div class="col-auto">
            <label class="form-label">Okno</label>
            <select class="form-select" name="hours">
              @foreach([1,6,12,24,48,72] as $h)
                <option value="{{ $h }}" {{ (request('hours',24)==$h)?'selected':'' }}>{{ $h }} h</option>
              @endforeach
            </select>
          </div>
          <div class="col-auto">
            <button class="btn btn-sm btn-primary" type="submit">Aktualizovat</button>
          </div>
        </form>

        @isset($summary)
        <div class="mb-3">
          <h5 class="mb-2">Souhrn za posledních {{ $summary['hours'] }} h</h5>
          <div class="row row-cols-1 row-cols-md-3 g-2">
            <div class="col"><div class="p-2 border rounded">Vzorky: <strong>{{ $summary['samples'] }}</strong></div></div>
            <div class="col"><div class="p-2 border rounded">Avg TTFT: <strong>{{ $summary['avg_ttft_ms'] ?? '—' }} ms</strong> · P95: <strong>{{ $summary['p95_ttft_ms'] ?? '—' }} ms</strong></div></div>
            <div class="col"><div class="p-2 border rounded">Avg Doba: <strong>{{ $summary['avg_duration_ms'] ?? '—' }} ms</strong> · P95: <strong>{{ $summary['p95_duration_ms'] ?? '—' }} ms</strong></div></div>
            <div class="col"><div class="p-2 border rounded">Avg Chars: <strong>{{ $summary['avg_chars'] ?? '—' }}</strong></div></div>
          </div>
        </div>
        @endisset

        @isset($breakdown)
        <h5>Rozpad podle provider/model</h5>
        <div class="table-responsive mb-4">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th>Provider</th>
                <th>Model</th>
                <th>Počet</th>
                <th>Avg TTFT (ms)</th>
                <th>P95 TTFT (ms)</th>
                <th>Avg Doba (ms)</th>
                <th>P95 Doba (ms)</th>
                <th>Avg Chars</th>
              </tr>
            </thead>
            <tbody>
              @forelse($breakdown as $b)
              <tr>
                <td>{{ $b['provider'] }}</td>
                <td>{{ $b['model'] }}</td>
                <td>{{ $b['count'] }}</td>
                <td>{{ $b['avg_ttft_ms'] ?? '—' }}</td>
                <td>{{ $b['p95_ttft_ms'] ?? '—' }}</td>
                <td>{{ $b['avg_duration_ms'] ?? '—' }}</td>
                <td>{{ $b['p95_duration_ms'] ?? '—' }}</td>
                <td>{{ $b['avg_chars'] ?? '—' }}</td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted">Žádná data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @endisset
        <h5>Nedávné akce nástrojů</h5>
        <div class="table-responsive mb-4">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Session</th>
                <th>Message</th>
                <th>Nástroj</th>
                <th>Vstupy</th>
                <th>Výstupy</th>
                <th>Status</th>
                <th>Čas</th>
              </tr>
            </thead>
            <tbody>
              @forelse($actions as $a)
              <tr>
                <td>{{ $a->id }}</td>
                <td>{{ $a->session_id }}</td>
                <td>{{ $a->message_id }}</td>
                <td><code>{{ $a->tool_name }}</code></td>
                <td><pre class="mb-0" style="white-space: pre-wrap; word-break: break-word">{{ $a->inputs }}</pre></td>
                <td><pre class="mb-0" style="white-space: pre-wrap; word-break: break-word">{{ $a->outputs }}</pre></td>
                <td>{{ $a->status }}</td>
                <td>{{ $a->created_at }}</td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted">Žádné záznamy.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <h5>Poslední tool zprávy</h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Session</th>
                <th>Role</th>
                <th>Obsah</th>
                <th>Čas</th>
              </tr>
            </thead>
            <tbody>
              @forelse($toolMessages as $m)
              <tr>
                <td>{{ $m->id }}</td>
                <td>{{ $m->session_id }}</td>
                <td>{{ $m->role }}</td>
                <td><pre class="mb-0" style="white-space: pre-wrap; word-break: break-word">{{ $m->content }}</pre></td>
                <td>{{ $m->created_at }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted">Žádné záznamy.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @isset($chatMessages)
        <h5 class="mt-4">Poslední dotazy a odpovědi</h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Session</th>
                <th>Role</th>
                <th>Obsah</th>
                <th>Status</th>
                <th>Čas</th>
              </tr>
            </thead>
            <tbody>
              @forelse($chatMessages as $m)
              <tr>
                <td>{{ $m->id }}</td>
                <td>{{ $m->session_id }}</td>
                <td><span class="badge bg-{{ $m->role === 'user' ? 'secondary' : 'primary' }}">{{ $m->role }}</span></td>
                <td><pre class="mb-0" style="white-space: pre-wrap; word-break: break-word">{{ $m->content }}</pre></td>
                <td>{{ $m->status }}</td>
                <td>{{ $m->created_at }}</td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted">Žádné zprávy.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @endisset
      </div>
    </div>
  </div>
</div>
@endsection
