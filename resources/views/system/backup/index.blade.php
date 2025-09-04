@extends('layouts.vertical', ['title' => 'Záloha', 'topbarTitle' => 'Systém – Záloha'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h4 class="mb-0">Zálohování systému</h4>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('system.backup.run') }}">
                        @csrf
                        <button class="btn btn-primary"><i class="ti ti-player-play"></i> Spustit zálohu</button>
                    </form>
                    <form method="POST" action="{{ route('system.backup.clean') }}">
                        @csrf
                        <button class="btn btn-outline-warning"><i class="ti ti-broom"></i> Vyčistit staré zálohy</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('backup_output'))
                    <pre class="bg-light p-2 border">{{ session('backup_output') }}</pre>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted">Poslední záloha</div>
                            <div class="fw-semibold">{{ $lastBackupAt ? \Carbon\Carbon::createFromTimestamp($lastBackupAt)->diffForHumans() : '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted">Počet záloh</div>
                            <div class="fw-semibold">{{ $backupCount }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted">Celková velikost</div>
                            <div class="fw-semibold">{{ number_format($totalSize/1024/1024, 2) }} MB</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted">Zdraví</div>
                            <div class="fw-semibold {{ $healthy ? 'text-success' : 'text-danger' }}">{{ $healthy ? 'OK' : 'Zastaralé' }} @if($maxAgeDays) <span class="text-muted">(≤ {{ $maxAgeDays }} dnů)</span> @endif</div>
                        </div>
                    </div>
                </div>

                <h5 class="mt-2">Dostupné zálohy</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Soubor</th>
                                <th>Velikost</th>
                                <th>Datum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($files as $file)
                                <tr>
                                    <td><code>{{ $file['path'] }}</code></td>
                                    <td>{{ number_format($file['size']/1024/1024, 2) }} MB</td>
                                    <td>{{ \Carbon\Carbon::createFromTimestamp($file['lastModified'])->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('system.backup.download', ['path' => $file['path']]) }}">
                                            <i class="ti ti-download"></i> Stáhnout
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Žádné zálohy zatím nejsou.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <hr>
                <h5>Automatické zálohy</h5>
                <p class="text-muted mb-1">Aktuální plán: <code>{{ $scheduleCron }}</code></p>
                <p class="text-muted">Zapnuto: <strong>{{ $scheduleEnabled ? 'Ano' : 'Ne' }}</strong></p>
                <p class="small text-muted mb-0">Pozn.: Spouštění je v praxi řešitelné cronem uvnitř kontejneru (např. každou minutu php artisan schedule:run). Rád zapnu, jakmile potvrdíte frekvenci.</p>
            </div>
        </div>
    </div>
</div>
@endsection
