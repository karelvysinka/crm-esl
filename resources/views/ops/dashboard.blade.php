@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <h4 class="page-title">Git &amp; Zálohy (Ops)</h4>
  <div class="row gy-3">
    <div class="col-xl-3 col-md-6">
      <div class="card"><div class="card-body">
  <h6 class="text-muted">Aktuální verze @help('git.strategy')</h6>
        <div class="small">Hash: {{ $git['hash'] ?? 'N/A' }}</div>
        <div class="small">Tag: {{ $git['tag'] ?? 'N/A' }}</div>
        <div class="small">Deploy: {{ $git['deployed_at'] ?? 'N/A' }}</div>
      </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card"><div class="card-body">
  <h6 class="text-muted">DB Záloha @help('backup.rpo')</h6>
  @php($st = $db['status'] ?? 'N/A')
  @php($badgeClass = match($st){ 'OK'=>'bg-success-subtle text-success','STALE'=>'bg-warning-subtle text-warning','FAIL'=>'bg-danger-subtle text-danger','MISSING'=>'bg-danger-subtle text-danger', default=>'bg-secondary-subtle text-muted'})
  <div class="small">Status: <span class="badge {{$badgeClass}}">{{$st}}</span></div>
        <div class="small">Věk (min): {{ $db['age_minutes'] ?? '?' }}</div>
        <div class="small">Velikost: {{ isset($db['size_mb']) ? number_format($db['size_mb'],2).' MB' : 'N/A' }}</div>
      </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card"><div class="card-body">
  <h6 class="text-muted">Snapshot @help('backup.rpo')</h6>
        @php($snap = ($snapshot ?? null) ?: app(\App\Services\Ops\BackupStatusService::class)->latestSnapshotStatus())
        @php($snapBadge = match($snap['status'] ?? 'MISSING'){ 'OK'=>'bg-success-subtle text-success','STALE'=>'bg-warning-subtle text-warning','MISSING'=>'bg-danger-subtle text-danger', default=>'bg-secondary-subtle text-muted'})
        <div class="small">Status: <span class="badge {{$snapBadge}}">{{ $snap['status'] ?? 'N/A' }}</span></div>
        <div class="small">Věk (min): {{ $snap['age_minutes'] ?? '?' }}</div>
      </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card"><div class="card-body">
  <h6 class="text-muted">Verify Restore @help('backup.verify')</h6>
        @php($ver = ($verify ?? null) ?: app(\App\Services\Ops\BackupStatusService::class)->latestVerifyStatus())
        @php($verBadge = match($ver['status'] ?? 'MISSING'){ 'OK'=>'bg-success-subtle text-success','STALE'=>'bg-warning-subtle text-warning','MISSING'=>'bg-danger-subtle text-danger', default=>'bg-secondary-subtle text-muted'})
        <div class="small">Status: <span class="badge {{$verBadge}}">{{ $ver['status'] ?? 'N/A' }}</span></div>
        <div class="small">Věk (h): {{ $ver['age_hours'] ?? '?' }}</div>
      </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card"><div class="card-body">
  <h6 class="text-muted">Poslední operace @help('ops.limits')</h6>
        <ul class="list-unstyled small mb-0">
          @forelse($recent as $op)
            <li>{{ $op->type }} - {{ $op->status }}</li>
          @empty
            <li>Žádné záznamy</li>
          @endforelse
        </ul>
      </div></div>
    </div>
  </div>
  <div class="row mt-3">
    <div class="col-12">
      <div class="card"><div class="card-body">
  <h6 class="text-muted mb-2">Akce @help('release.process')</h6>
        @php($opsToken = bin2hex(random_bytes(16)))
        <form method="POST" action="{{ route('ops.action',['action'=>'db_backup']) }}" class="d-inline">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <button class="btn btn-primary btn-sm">Spustit DB Backup</button>
        </form>
        <form method="POST" action="{{ route('ops.action',['action'=>'storage_snapshot']) }}" class="d-inline ms-1">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <button class="btn btn-outline-secondary btn-sm">Snapshot Uploadů</button>
        </form>
        <form method="POST" action="{{ route('ops.action',['action'=>'verify_restore']) }}" class="d-inline ms-1">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <button class="btn btn-warning btn-sm">Verify Restore</button>
        </form>
        <form method="POST" action="{{ route('ops.action',['action'=>'report']) }}" class="d-inline ms-1">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <button class="btn btn-outline-secondary btn-sm">Report</button>
        </form>
        <form method="POST" action="{{ route('ops.action',['action'=>'create_tag']) }}" class="d-inline ms-1">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <input name="tag" class="form-control form-control-sm d-inline-block" style="width:120px" placeholder="v1.2.3" />
          <button class="btn btn-success btn-sm">Create Tag</button>
        </form>
        <form method="POST" action="{{ route('ops.docs.build') }}" class="d-inline ms-1">
          @csrf
          <input type="hidden" name="_ops_token" value="{{ $opsToken }}">
          <button class="btn btn-outline-info btn-sm">Build Docs</button>
        </form>
      </div></div>
    </div>
  </div>
</div>
@endsection
