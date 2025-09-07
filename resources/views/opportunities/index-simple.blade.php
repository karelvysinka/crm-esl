@extends('layouts.vertical', ['page_title' => 'Příležitosti'])

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Příležitosti</li>
                    </ol>
                </div>
                <h4 class="page-title">Příležitosti</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Opportunity KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-success rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ number_format($stats['win_rate'], 1) }}%</h5>
                            <p class="kpi-label mb-0">Win Rate</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Podíl vyhraných</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-primary rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ number_format($stats['avg_value'], 0, ',', ' ') }} Kč</h5>
                            <p class="kpi-label mb-0">Průměr</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Hodnota / příležitost</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-warning rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l3 3"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ $stats['open_count'] }}</h5>
                            <p class="kpi-label mb-0">Otevřeno</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Aktivní fáze</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-danger rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ $stats['lost_count'] }}</h5>
                            <p class="kpi-label mb-0">Prohráno</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Ztracené</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-info rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ $stats['won_count'] }}</h5>
                            <p class="kpi-label mb-0">Vyhráno</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Uzavřené výhry</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-sm bg-secondary rounded d-flex align-items-center justify-content-center" style="width:54px;height:54px;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v4H4z"/><path d="M4 12h16v8H4z"/><path d="M4 8v4"/><path d="M20 8v4"/></svg>
                        </div>
                        <div class="text-end">
                            <h5 class="my-0">{{ number_format($stats['total_value'], 0, ',', ' ') }} Kč</h5>
                            <p class="kpi-label mb-0">Celkem</p>
                        </div>
                    </div>
                    <div class="kpi-meta mt-1">Celková hodnota</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Seznam příležitostí</h4>
                    <a href="{{ route('opportunities.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Nová příležitost
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Název</th>
                                    <th>Společnost</th>
                                    <th>Hodnota</th>
                                    <th>Stádium</th>
                                    <th>Pravděpodobnost</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $opportunity)
                                <tr>
                                    <td>{{ $opportunity->name }}</td>
                                    <td>
                                        @if($opportunity->company)
                                            {{ $opportunity->company->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($opportunity->value, 0, ',', ' ') }} Kč</td>
                                    <td>
                                        <span class="badge bg-{{ $opportunity->stage_color }}">{{ $opportunity->stage_label }}</span>
                                    </td>
                                    <td>{{ $opportunity->probability }}%</td>
                                    <td>
                                        <a href="{{ route('opportunities.show', $opportunity) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('opportunities.edit', $opportunity) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $opportunities->links() }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
