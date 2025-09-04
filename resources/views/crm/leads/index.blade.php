@extends('layouts.vertical', ['page_title' => 'Leads', 'mode' => 'light'])

@section('css')
<style>
    .kanban-container {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding: 20px 0;
        min-height: 600px;
    }
    
    .kanban-column {
        flex: 0 0 300px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        height: fit-content;
        min-height: 500px;
    }
    
    .kanban-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .kanban-title {
        font-weight: 600;
        font-size: 16px;
        margin: 0;
    }
    
    .kanban-count {
        background: #6c757d;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }
    
    .lead-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #e3e6f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .lead-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .lead-company {
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 5px;
    }
    
    .lead-contact {
        color: #858796;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .lead-value {
        color: #1cc88a;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .lead-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #858796;
    }
    
    .score-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 11px;
    }
    
    .score-high { background: #d4edda; color: #155724; }
    .score-medium { background: #fff3cd; color: #856404; }
    .score-low { background: #f8d7da; color: #721c24; }
    
    /* Status specific colors */
    .status-new .kanban-count { background: #6c757d; }
    .status-contacted .kanban-count { background: #36b9cc; }
    .status-qualified .kanban-count { background: #5a67d8; }
    .status-proposal .kanban-count { background: #f6ad55; }
    .status-negotiation .kanban-count { background: #ed8936; }
    .status-won .kanban-count { background: #38a169; }
    .status-lost .kanban-count { background: #e53e3e; }
    
    .assigned-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #e74a3b;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<!-- Start Content-->
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
                <h4 class="page-title">
                    Leads 
                    <span class="badge bg-primary ms-2">{{ $leads->count() }}</span>
                </h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Action Bar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="d-flex gap-2">
                                <a href="{{ url('/crm/leads/create') }}" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-plus"></i> Přidat Lead
                                </a>
                                <button class="btn btn-outline-secondary btn-sm" onclick="toggleView()">
                                    <i class="mdi mdi-view-list" id="view-icon"></i> 
                                    <span id="view-text">Seznam</span>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-end gap-2">
                                <select class="form-select form-select-sm" style="width: 150px;" onchange="filterBySource(this.value)">
                                    <option value="">Všechny zdroje</option>
                                    <option value="website">Website</option>
                                    <option value="referral">Doporučení</option>
                                    <option value="social_media">Sociální sítě</option>
                                    <option value="cold_call">Studený hovor</option>
                                    <option value="email_campaign">Email kampaň</option>
                                    <option value="trade_show">Veletrh</option>
                                    <option value="other">Ostatní</option>
                                </select>
                                <div class="input-group" style="width: 250px;">
                                    <input type="text" class="form-control form-control-sm" placeholder="Hledat leads..." id="searchInput">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-account-multiple widget-icon"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Celkem leads">Celkem</h5>
                    <h3 class="mt-3 mb-3">{{ $leads->count() }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-success me-2">
                            <i class="mdi mdi-arrow-up-bold"></i> {{ $leads->where('created_at', '>=', now()->subDays(30))->count() }}
                        </span>
                        <span class="text-nowrap">za posledních 30 dní</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-trophy widget-icon bg-success-lighten text-success"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Úspěšně uzavřené">Uzavřené</h5>
                    <h3 class="mt-3 mb-3">{{ $leadsByStatus->get('won', collect())->count() }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-success me-2">
                            {{ $leads->count() > 0 ? round(($leadsByStatus->get('won', collect())->count() / $leads->count()) * 100, 1) : 0 }}%
                        </span>
                        <span class="text-nowrap">úspěšnost</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-currency-usd widget-icon bg-info-lighten text-info"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Potenciální hodnota">Potenciál</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($leads->sum('estimated_value'), 0, ',', ' ') }} Kč</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-info me-2">
                            Ø {{ $leads->count() > 0 ? number_format($leads->avg('estimated_value'), 0, ',', ' ') : 0 }} Kč
                        </span>
                        <span class="text-nowrap">průměrná hodnota</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-star widget-icon bg-warning-lighten text-warning"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Průměrné skóre">Skóre</h5>
                    <h3 class="mt-3 mb-3">{{ $leads->count() > 0 ? round($leads->avg('score'), 1) : 0 }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-warning me-2">
                            <i class="mdi mdi-arrow-up-bold"></i> {{ $leads->where('score', '>=', 70)->count() }}
                        </span>
                        <span class="text-nowrap">vysoce kvalitních</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="row" id="kanban-view">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="kanban-container">
                        @php
                            $statuses = [
                                'new' => ['title' => 'Nové', 'class' => 'status-new'],
                                'contacted' => ['title' => 'Kontaktované', 'class' => 'status-contacted'],
                                'qualified' => ['title' => 'Kvalifikované', 'class' => 'status-qualified'],
                                'proposal' => ['title' => 'Nabídka', 'class' => 'status-proposal'],
                                'negotiation' => ['title' => 'Vyjednávání', 'class' => 'status-negotiation'],
                                'won' => ['title' => 'Vyhráno', 'class' => 'status-won'],
                                'lost' => ['title' => 'Prohráno', 'class' => 'status-lost']
                            ];
                        @endphp

                        @foreach($statuses as $status => $config)
                            <div class="kanban-column {{ $config['class'] }}">
                                <div class="kanban-header">
                                    <h6 class="kanban-title">{{ $config['title'] }}</h6>
                                    <span class="kanban-count">{{ $leadsByStatus->get($status, collect())->count() }}</span>
                                </div>
                                
                                <div class="kanban-cards" data-status="{{ $status }}">
                                    @foreach($leadsByStatus->get($status, collect()) as $lead)
                                        <div class="lead-card" onclick="window.location.href='{{ url('/crm/leads/' . $lead->id) }}'">
                                            <div class="lead-company">{{ $lead->company_name }}</div>
                                            <div class="lead-contact">{{ $lead->contact_name }}</div>
                                            
                                            @if($lead->estimated_value)
                                                <div class="lead-value">{{ number_format($lead->estimated_value, 0, ',', ' ') }} Kč</div>
                                            @endif
                                            
                                            <div class="lead-meta">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($lead->assignedTo)
                                                        <div class="assigned-avatar" title="{{ $lead->assignedTo->name }}">
                                                            {{ strtoupper(substr($lead->assignedTo->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    
                                                    <span class="score-badge {{ $lead->score >= 70 ? 'score-high' : ($lead->score >= 40 ? 'score-medium' : 'score-low') }}">
                                                        {{ $lead->score }}
                                                    </span>
                                                </div>
                                                
                                                <span>{{ $lead->created_at->format('d.m.Y') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table View (hidden by default) -->
    <div class="row" id="table-view" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="leads-table">
                            <thead>
                                <tr>
                                    <th>Společnost</th>
                                    <th>Kontakt</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Skóre</th>
                                    <th>Hodnota</th>
                                    <th>Přiřazeno</th>
                                    <th>Vytvořeno</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leads as $lead)
                                    <tr>
                                        <td>
                                            <a href="{{ url('/crm/leads/' . $lead->id) }}" class="text-body fw-semibold">
                                                {{ $lead->company_name }}
                                            </a>
                                        </td>
                                        <td>{{ $lead->contact_name }}</td>
                                        <td>{{ $lead->email }}</td>
                                        <td>
                                            <span class="badge bg-{{ $lead->status === 'won' ? 'success' : ($lead->status === 'lost' ? 'danger' : 'info') }}">
                                                {{ $lead->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $lead->score >= 70 ? 'bg-success' : ($lead->score >= 40 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ $lead->score }}
                                            </span>
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
                                        <td>{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <a href="{{ url('/crm/leads/' . $lead->id) }}" class="action-icon">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ url('/crm/leads/' . $lead->id . '/edit') }}" class="action-icon">
                                                <i class="mdi mdi-square-edit-outline"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="action-icon" onclick="confirmDelete({{ $lead->id }})">
                                                <i class="mdi mdi-delete"></i>
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
<!-- container -->
@endsection

@section('script')
<!-- third party js -->
<script src="{{ asset('assets/js/vendor/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/dataTables.bootstrap5.js') }}"></script>
<script src="{{ asset('assets/js/vendor/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/responsive.bootstrap5.min.js') }}"></script>

<script>
    // Initialize DataTable for table view
    $(document).ready(function() {
        $('#leads-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[7, 'desc']], // Sort by created date
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/Czech.json"
            }
        });
    });

    // Toggle between kanban and table view
    function toggleView() {
        const kanbanView = document.getElementById('kanban-view');
        const tableView = document.getElementById('table-view');
        const viewIcon = document.getElementById('view-icon');
        const viewText = document.getElementById('view-text');
        
        if (kanbanView.style.display === 'none') {
            kanbanView.style.display = 'block';
            tableView.style.display = 'none';
            viewIcon.className = 'mdi mdi-view-list';
            viewText.textContent = 'Seznam';
        } else {
            kanbanView.style.display = 'none';
            tableView.style.display = 'block';
            viewIcon.className = 'mdi mdi-view-grid';
            viewText.textContent = 'Kanban';
        }
    }

    // Filter functionality
    function filterBySource(source) {
        // Implementation for filtering by source
        console.log('Filter by source:', source);
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        // Implementation for search
        console.log('Search:', searchTerm);
    });

    // Delete confirmation
    function confirmDelete(leadId) {
        if (confirm('Opravdu chcete smazat tento lead?')) {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/crm/leads/${leadId}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Drag and drop functionality for kanban board
    // This would require additional implementation for full drag/drop support
</script>
@endsection
