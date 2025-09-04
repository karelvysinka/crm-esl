@extends('layouts.vertical', ['title' => 'Aplikace', 'topbarTitle' => 'Systém – Aplikace'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Aplikace v horním menu</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('system.apps.store') }}" class="row g-3 border rounded p-3 mb-4">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Název</label>
                        <input type="text" name="name" class="form-control" required placeholder="Např. ESL">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">URL</label>
                        <input type="url" name="url" class="form-control" required placeholder="https://...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ikona (URL)</label>
                        <input type="url" name="icon_url" class="form-control" placeholder="/images/brands/slack.svg">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Pozice</label>
                        <input type="number" name="position" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Aktivní</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="ti ti-plus"></i> Přidat</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Pozice</th>
                                <th>Ikona</th>
                                <th>Název</th>
                                <th>URL</th>
                                <th>Aktivní</th>
                                <th class="text-end">Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($links as $link)
                            <tr>
                                <td style="width:100px;">
                                    <form method="POST" action="{{ route('system.apps.update', $link) }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="number" name="position" class="form-control form-control-sm" style="max-width:90px" value="{{ $link->position }}">
                                </td>
                                <td style="width:80px;">
                                    <img src="{{ $link->icon_url ?: '/images/brands/bootstrap.svg' }}" alt="icon" style="width:32px;height:32px;">
                                </td>
                                <td>
                                        <input type="text" name="name" value="{{ $link->name }}" class="form-control form-control-sm">
                                </td>
                                <td>
                                        <input type="url" name="url" value="{{ $link->url }}" class="form-control form-control-sm">
                                </td>
                                <td style="width:120px;">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $link->is_active ? 'checked' : '' }}>
                                        </div>
                                </td>
                                <td class="text-end" style="width:220px;">
                                        <input type="url" name="icon_url" value="{{ $link->icon_url }}" class="form-control form-control-sm mb-2" placeholder="Ikona URL">
                                        <button class="btn btn-sm btn-outline-primary"><i class="ti ti-device-floppy"></i> Uložit</button>
                                    </form>
                                    <form method="POST" action="{{ route('system.apps.destroy', $link) }}" class="d-inline ms-1" onsubmit="return confirm('Odebrat odkaz?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Zatím žádné odkazy.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
