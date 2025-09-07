@extends('layouts.vertical', ['page_title' => 'Objednávky - Nastavení synchronizace'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Objednávky</a></li>
                        <li class="breadcrumb-item active">Nastavení synchronizace</li>
                    </ol>
                </div>
                <h4 class="page-title">Objednávky – Nastavení synchronizace</h4>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                            <div class="avatar-sm bg-info rounded d-flex align-items-center justify-content-center" style="width:62px;height:62px;">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 1 9 9"/><path d="M3 3v5h5"/></svg>
                        </div>
                        <div class="text-end">
                            <h4 class="my-0">{{ $runsTotal }}</h4>
                            <p class="kpi-label mb-0">Spuštění</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Celkem běhů</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-success rounded d-flex align-items-center justify-content-center" style="width:62px;height:62px;">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="text-end">
                            <h4 class="my-0">{{ $runsSuccess }}</h4>
                            <p class="kpi-label mb-0">Úspěšné</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Status success</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-danger rounded d-flex align-items-center justify-content-center" style="width:62px;height:62px;">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </div>
                        <div class="text-end">
                            <h4 class="my-0">{{ $runsFailed }}</h4>
                            <p class="kpi-label mb-0">Neúspěšné</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Status failed</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Konfigurace</h5></div>
                <div class="card-body">
                    @if(session('status')) <div class="alert alert-success py-2 mb-3">{{ session('status') }}</div> @endif
                        @if($errors->any()) <div class="alert alert-danger py-2 mb-3">{{ $errors->first() }}</div> @endif
                        <div class="mb-3 small">
                            <strong>Poslední běh:</strong> {{ $lastRun? ($lastRun->started_at? $lastRun->started_at->format('H:i:s d.m.'):'—') .' ('.$lastRun->status.')':'—' }}<br>
                            <strong>Poslední úspěch:</strong> {{ $lastSuccess? $lastSuccess->started_at->format('H:i:s d.m.'):'—' }}
                        </div>
                    <form method="post" action="{{ route('orders.sync.settings.update') }}">
                        @csrf
                        @method('put')
                        <div class="mb-3">
                            <label class="form-label">Zdrojová URL *</label>
                            <input type="url" name="source_url" value="{{ old('source_url', $setting->source_url) }}" class="form-control @error('source_url') is-invalid @enderror" required>
                            @error('source_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Uživatel</label>
                                <input type="text" name="username" value="{{ old('username', $setting->username) }}" class="form-control @error('username') is-invalid @enderror">
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Heslo (ponechte prázdné pro nezměnu)</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Interval (minuty)</label>
                                <input type="number" min="5" max="1440" name="interval_minutes" value="{{ old('interval_minutes', $setting->interval_minutes) }}" class="form-control @error('interval_minutes') is-invalid @enderror" required>
                                @error('interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1" {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled">Automaticky povoleno</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i> Uložit</button>
                                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Zpět</a>
                                <form method="post" action="{{ route('orders.sync.settings.run') }}" class="d-inline">@csrf <button class="btn btn-outline-success" type="submit"><i class="ti ti-play me-1"></i> Run Now</button></form>
                                <form method="post" action="{{ route('orders.sync.settings.test') }}" class="d-inline">@csrf <button class="btn btn-outline-info" type="submit"><i class="ti ti-plug-connected me-1"></i> Test</button></form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Log běhů</h5>
                    <span class="badge bg-secondary">Posledních {{ $recent->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Start</th>
                                    <th>Trvání</th>
                                    <th>Status</th>
                                    <th>Nové</th>
                                    <th>Upd</th>
                                    <th>Zpráva</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent as $run)
                                <tr>
                                    <td>#{{ $run->id }}</td>
                                    <td>{{ optional($run->started_at)->format('H:i:s d.m.') }}</td>
                                    <td>
                                        @if($run->started_at && $run->finished_at)
                                            {{ $run->finished_at->diffInSeconds($run->started_at) }} s
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $run->status === 'success' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">{{ $run->status }}</span>
                                    </td>
                                    <td>{{ $run->new_orders }}</td>
                                    <td>{{ $run->updated_orders }}</td>
                                    <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $run->message }}">{{ $run->message }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">Žádné záznamy</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
