@extends('layouts.vertical', ['page_title' => 'Příležitosti'])

@section('css')
@vite(['resources/js/pages/datatable.init.js'])
@endsection

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

    <!-- Sales Pipeline Stats -->
    <div class="row mb-3">
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-primary">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-currency-dollar widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Celkova hodnota">Celková hodnota</h6>
                    <h2 class="my-2">{{ number_format($stats['total_value'], 0, ',', ' ') }} Kč</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">{{ $stats['total_count'] }} příležitostí</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-success">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-trophy widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Vyhráno">Vyhráno</h6>
                    <h2 class="my-2">{{ number_format($stats['won_value'], 0, ',', ' ') }} Kč</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">{{ $stats['won_count'] }} obchodů</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-info">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-target widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Otevřeno">Otevřeno</h6>
                    <h2 class="my-2">{{ number_format($stats['open_value'], 0, ',', ' ') }} Kč</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">{{ $stats['open_count'] }} aktivních</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-warning">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-percentage widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0" title="Průměrná pravděpodobnost">Úspěšnost</h6>
                    <h2 class="my-2">{{ number_format($stats['avg_probability'], 0) }}%</h2>
                    <p class="mb-0 text-white-50">
                        <span class="text-nowrap">průměrná šance</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Seznam příležitostí</h4>
                    <div>
                        <a href="{{ url('/crm/opportunities-pipeline') }}" class="btn btn-info btn-sm me-2">
                            <i class="ti ti-layout-kanban me-1"></i> Pipeline View
                        </a>
                        <a href="{{ url('/crm/opportunities/create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i> Nová příležitost
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted font-13 mb-4">
                        Přehled všech příležitostí v CRM systému s možností filtrování a vyhledávání.
                    </p>

                    <table id="opportunities-datatable" class="table table-striped dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>Název</th>
                                <th>Společnost</th>
                                <th>Kontakt</th>
                                <th>Hodnota</th>
                                <th>Pravděpodobnost</th>
                                <th>Fáze</th>
                                <th>Status</th>
                                <th>Uzavření</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <a href="{{ url('/crm/opportunities/' . $opportunity->id) }}" class="text-decoration-none">
                                        <strong>{{ $opportunity->title }}</strong>
                                    </a>
                                    @if($opportunity->description)
                                        <br><small class="text-muted">{{ Str::limit($opportunity->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($opportunity->company)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title bg-primary-lighten text-primary rounded">
                                                    {{ substr($opportunity->company->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ url('/crm/companies/' . $opportunity->company->id) }}" class="text-decoration-none">
                                                    {{ $opportunity->company->name }}
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($opportunity->contact)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title bg-info-lighten text-info rounded-circle">
                                                    {{ substr($opportunity->contact->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ url('/crm/contacts/' . $opportunity->contact->id) }}" class="text-decoration-none">
                                                    {{ $opportunity->contact->name }}
                                                </a>
                                                @if($opportunity->contact->email)
                                                    <br><small class="text-muted">{{ $opportunity->contact->email }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-success">{{ number_format($opportunity->value, 0, ',', ' ') }} Kč</strong>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $opportunity->probability >= 75 ? 'success' : ($opportunity->probability >= 50 ? 'warning' : 'danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $opportunity->probability }}%"
                                             aria-valuenow="{{ $opportunity->probability }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            {{ $opportunity->probability }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $opportunity->stage_color }}-lighten text-{{ $opportunity->stage_color }}">
                                        {{ match($opportunity->stage) {
                                            'qualification' => 'Kvalifikace',
                                            'proposal' => 'Návrh', 
                                            'negotiation' => 'Vyjednávání',
                                            'closed_won' => 'Vyhráno',
                                            'closed_lost' => 'Prohráno',
                                            default => $opportunity->stage
                                        } }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $opportunity->status_badge }}-lighten text-{{ $opportunity->status_badge }}">
                                        {{ match($opportunity->status) {
                                            'open' => 'Otevřeno',
                                            'won' => 'Vyhráno',
                                            'lost' => 'Prohráno', 
                                            'on_hold' => 'Pozastaveno',
                                            default => $opportunity->status
                                        } }}
                                    </span>
                                </td>
                                <td>
                                    @if($opportunity->expected_close_date)
                                        <small class="text-muted">Očekáváno:</small><br>
                                        {{ $opportunity->expected_close_date->format('d.m.Y') }}
                                        @if($opportunity->actual_close_date)
                                            <br><small class="text-success">Uzavřeno: {{ $opportunity->actual_close_date->format('d.m.Y') }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ url('/crm/opportunities/' . $opportunity->id) }}" class="btn btn-soft-info btn-sm" title="Zobrazit">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ url('/crm/opportunities/' . $opportunity->id . '/edit') }}" class="btn btn-soft-warning btn-sm" title="Editovat">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="ti ti-currency-dollar fa-3x mb-3 opacity-50"></i>
                                    <p>Zatím žádné příležitosti.</p>
                                    <a href="{{ url('/crm/opportunities/create') }}" class="btn btn-primary btn-sm">
                                        <i class="ti ti-plus me-1"></i> Vytvořit první příležitost
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($opportunities->hasPages())
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $opportunities->links() }}
                        </div>
                    @endif

                </div> <!-- end card body-->
            </div> <!-- end card -->
        </div><!-- end col-->
    </div>
    <!-- end row-->

</div> <!-- container -->
@endsection

@section('script')
@vite(['resources/js/pages/datatable.init.js'])
<script>
$(document).ready(function() {
    $('#opportunities-datatable').DataTable({
        "pageLength": 25,
        "ordering": true,
        "searching": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Czech.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [8] } // Disable sorting on Actions column
        ]
    });
});
</script>
@endsection
