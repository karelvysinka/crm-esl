@extends('layouts.vertical', ['page_title' => 'Sales Pipeline'])

@section('css')
<style>
.pipeline-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
}

.pipeline-column {
    min-width: 300px;
    flex: 1;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.pipeline-header {
    background: #fff;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.opportunity-card {
    background: #fff;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 4px solid #dee2e6;
}

.opportunity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.opportunity-card.qualification {
    border-left-color: #17a2b8;
}

.opportunity-card.proposal {
    border-left-color: #ffc107;
}

.opportunity-card.negotiation {
    border-left-color: #007bff;
}

.opportunity-card.closed_won {
    border-left-color: #28a745;
}

.opportunity-card.closed_lost {
    border-left-color: #dc3545;
}

.card-value {
    font-weight: bold;
    color: #28a745;
    font-size: 1.1rem;
}

.card-company {
    font-size: 0.9rem;
    color: #6c757d;
}

.card-probability {
    font-size: 0.85rem;
}

.stage-stats {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>
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
                        <li class="breadcrumb-item"><a href="{{ url('/crm/opportunities') }}">Příležitosti</a></li>
                        <li class="breadcrumb-item active">Pipeline</li>
                    </ol>
                </div>
                <h4 class="page-title">Sales Pipeline</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Pipeline Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ url('/crm/opportunities') }}" class="btn btn-outline-primary">
                        <i class="ti ti-list me-1"></i> Seznam příležitostí
                    </a>
                </div>
                <div>
                    <a href="{{ url('/crm/opportunities/create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Nová příležitost
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Board -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <div class="pipeline-board">
                        
                        @foreach($stages as $stageKey => $stageName)
                        <div class="pipeline-column">
                            <div class="pipeline-header">
                                <h5 class="mb-1">{{ $stageName }}</h5>
                                @php
                                    $stageOpportunities = $opportunities->get($stageKey, collect());
                                    $stageCount = $stageOpportunities->count();
                                    $stageValue = $stageOpportunities->sum('value');
                                @endphp
                                <div class="stage-stats">
                                    {{ $stageCount }} příležitostí • {{ number_format($stageValue, 0, ',', ' ') }} Kč
                                </div>
                            </div>

                            <div class="pipeline-cards">
                                @forelse($stageOpportunities as $opportunity)
                                <div class="opportunity-card {{ $stageKey }}" 
                                     onclick="window.location.href='{{ url('/crm/opportunities/' . $opportunity->id) }}'">
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">{{ Str::limit($opportunity->title, 30) }}</h6>
                                        <span class="badge bg-{{ $opportunity->status_badge }}-lighten text-{{ $opportunity->status_badge }} ms-1">
                                            {{ match($opportunity->status) {
                                                'open' => 'Otevřeno',
                                                'won' => 'Vyhráno',
                                                'lost' => 'Prohráno',
                                                'on_hold' => 'Pozastaveno',
                                                default => $opportunity->status
                                            } }}
                                        </span>
                                    </div>

                                    <div class="card-value mb-2">
                                        {{ number_format($opportunity->value, 0, ',', ' ') }} Kč
                                    </div>

                                    @if($opportunity->company)
                                    <div class="card-company mb-2">
                                        <i class="ti ti-building me-1"></i>
                                        {{ Str::limit($opportunity->company->name, 25) }}
                                    </div>
                                    @endif

                                    @if($opportunity->contact)
                                    <div class="card-company mb-2">
                                        <i class="ti ti-user me-1"></i>
                                        {{ Str::limit($opportunity->contact->name, 25) }}
                                    </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="card-probability">
                                            <div class="progress" style="height: 6px; width: 60px;">
                                                <div class="progress-bar bg-{{ $opportunity->probability >= 75 ? 'success' : ($opportunity->probability >= 50 ? 'warning' : 'danger') }}" 
                                                     style="width: {{ $opportunity->probability }}%"></div>
                                            </div>
                                            <span class="text-muted">{{ $opportunity->probability }}%</span>
                                        </div>
                                        
                                        @if($opportunity->expected_close_date)
                                        <small class="text-muted">
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ $opportunity->expected_close_date->format('d.m') }}
                                        </small>
                                        @endif
                                    </div>

                                    @if($opportunity->assignedUser)
                                    <div class="mt-2 d-flex align-items-center">
                                        <div class="avatar-xs me-1">
                                            <span class="avatar-title bg-primary-lighten text-primary rounded-circle fs-6">
                                                {{ substr($opportunity->assignedUser->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ Str::limit($opportunity->assignedUser->name, 15) }}</small>
                                    </div>
                                    @endif
                                </div>
                                @empty
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-circle-plus fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">Žádné příležitosti</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Summary -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title mb-0">Souhrn pipeline</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($stages as $stageKey => $stageName)
                        @php
                            $stageOpportunities = $opportunities->get($stageKey, collect());
                            $stageCount = $stageOpportunities->count();
                            $stageValue = $stageOpportunities->sum('value');
                            $avgProbability = $stageOpportunities->avg('probability') ?: 0;
                        @endphp
                        <div class="col-md-6 col-xl-{{ $stageKey === 'closed_won' || $stageKey === 'closed_lost' ? '3' : '2' }} mb-3">
                            <div class="card border border-{{ match($stageKey) {
                                'qualification' => 'info',
                                'proposal' => 'warning',
                                'negotiation' => 'primary',
                                'closed_won' => 'success',
                                'closed_lost' => 'danger',
                                default => 'secondary'
                            } }}">
                                <div class="card-body text-center">
                                    <h5 class="card-title">{{ $stageName }}</h5>
                                    <h3 class="text-{{ match($stageKey) {
                                        'qualification' => 'info',
                                        'proposal' => 'warning', 
                                        'negotiation' => 'primary',
                                        'closed_won' => 'success',
                                        'closed_lost' => 'danger',
                                        default => 'secondary'
                                    } }}">{{ $stageCount }}</h3>
                                    <p class="mb-1 text-muted">{{ number_format($stageValue, 0, ',', ' ') }} Kč</p>
                                    @if($stageCount > 0 && $stageKey !== 'closed_won' && $stageKey !== 'closed_lost')
                                    <small class="text-muted">Ø {{ number_format($avgProbability, 0) }}%</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> <!-- container -->
@endsection

@section('script')
<script>
$(document).ready(function() {
    // Add drag and drop functionality (future enhancement)
    $('.opportunity-card').on('click', function() {
        // Card click is handled by onclick in the HTML
    });

    // Tooltip for opportunity cards
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endsection
