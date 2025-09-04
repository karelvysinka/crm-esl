@extends('layouts.vertical', ['page_title' => 'Leads'])

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Leads</li>
                    </ol>
                </div>
                <h4 class="page-title">Leads</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Sales Pipeline Stats -->
    <div class="row mb-3">
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-info">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-users widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Celkem leadů">Celkem leadů</h6>
                    <h2 class="my-2">{{ $stats['total_leads'] }}</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">Aktivních v pipeline</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-warning">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-star widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Nové leady">Nové leady</h6>
                    <h2 class="my-2">{{ $stats['new_leads'] }}</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">Čekají na kontakt</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-success">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-check widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Kvalifikované">Kvalifikované</h6>
                    <h2 class="my-2">{{ $stats['qualified_leads'] }}</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">Připravené k prodeji</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-primary">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-currency-dollar widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Potenciální hodnota">Potenciální hodnota</h6>
                    <h2 class="my-2">{{ number_format($stats['total_estimated_value'], 0, ',', ' ') }} Kč</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">Odhad revenue</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Seznam leadů</h4>
                    <div>
                        <a href="{{ route('leads.kanban') }}" class="btn btn-info me-2">
                            <i class="ti ti-layout-kanban me-1"></i>Kanban board
                        </a>
                        <a href="{{ route('leads.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Nový lead
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Společnost</th>
                                    <th>Kontakt</th>
                                    <th>Email</th>
                                    <th>Zdroj</th>
                                    <th>Status</th>
                                    <th>Skóre</th>
                                    <th>Odhadovaná hodnota</th>
                                    <th>Přiřazeno</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leads as $lead)
                                <tr>
                                    <td>
                                        <strong>{{ $lead->company_name }}</strong>
                                    </td>
                                    <td>{{ $lead->contact_name }}</td>
                                    <td>
                                        <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $lead->source_color }}">{{ $lead->source_label }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $lead->status_color }}">{{ $lead->status_label }}</span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $lead->score_color }}" 
                                                 style="width: {{ $lead->score }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $lead->score }}%</small>
                                    </td>
                                    <td>
                                        @if($lead->estimated_value)
                                            {{ number_format($lead->estimated_value, 0, ',', ' ') }} Kč
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($lead->assignedTo)
                                            <span class="badge bg-light text-dark">{{ $lead->assignedTo->name }}</span>
                                        @else
                                            <span class="text-muted">Nepřiřazeno</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('leads.edit', $lead) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
