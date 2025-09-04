@extends('layouts.vertical', ['page_title' => 'Detail Lead', 'mode' => 'light'])

@section('css')
<style>
    .score-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        color: white;
        margin: 0 auto;
    }
    
    .score-high { background: linear-gradient(135deg, #38a169, #48bb78); }
    .score-medium { background: linear-gradient(135deg, #d69e2e, #ed8936); }
    .score-low { background: linear-gradient(135deg, #e53e3e, #fc8181); }
    
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .status-new { background: #f7f8fc; color: #6c757d; }
    .status-contacted { background: #e1f5fe; color: #0277bd; }
    .status-qualified { background: #e8eaf6; color: #3f51b5; }
    .status-proposal { background: #fff3e0; color: #f57c00; }
    .status-negotiation { background: #fff8e1; color: #ff8f00; }
    .status-won { background: #e8f5e8; color: #2e7d32; }
    .status-lost { background: #ffebee; color: #c62828; }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
    }
    
    .info-value {
        color: #495057;
        text-align: right;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: -20px;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item:last-child::before {
        display: none;
    }
    
    .timeline-marker {
        position: absolute;
        left: 0;
        top: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #6c757d;
        border: 3px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #6c757d;
    }
    
    .progress-pipeline {
        display: flex;
        align-items: center;
        margin: 20px 0;
    }
    
    .pipeline-step {
        flex: 1;
        text-align: center;
        position: relative;
    }
    
    .pipeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 15px;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #dee2e6;
        z-index: 1;
    }
    
    .pipeline-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        position: relative;
        z-index: 2;
        color: white;
        font-size: 12px;
    }
    
    .pipeline-step.active .pipeline-circle {
        background: #5a67d8;
    }
    
    .pipeline-step.completed .pipeline-circle {
        background: #38a169;
    }
    
    .pipeline-step.completed:not(:last-child)::after {
        background: #38a169;
    }
    
    .pipeline-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 500;
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
                        <li class="breadcrumb-item"><a href="{{ url('/crm/leads') }}">Leads</a></li>
                        <li class="breadcrumb-item active">{{ $lead->company_name }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ $lead->company_name }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Action Bar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ $lead->contact_name }}</h5>
                            <p class="text-muted mb-0">
                                <i class="mdi mdi-email-outline"></i> {{ $lead->email }}
                                @if($lead->phone)
                                    <span class="ms-3"><i class="mdi mdi-phone"></i> {{ $lead->phone }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ url('/crm/leads/' . $lead->id . '/edit') }}" class="btn btn-warning btn-sm">
                                <i class="mdi mdi-square-edit-outline"></i> Upravit
                            </a>
                            <a href="{{ url('/crm/leads') }}" class="btn btn-secondary btn-sm">
                                <i class="mdi mdi-arrow-left"></i> Zpět na seznam
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Lead Status Pipeline -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Sales Pipeline</h4>
                </div>
                <div class="card-body">
                    @php
                        $pipelineSteps = [
                            'new' => 'Nový',
                            'contacted' => 'Kontakt',
                            'qualified' => 'Kvalif.',
                            'proposal' => 'Nabídka',
                            'negotiation' => 'Vyjedn.',
                            'won' => 'Vyhráno'
                        ];
                        
                        $stepOrder = array_keys($pipelineSteps);
                        $currentIndex = array_search($lead->status, $stepOrder);
                        if ($currentIndex === false) $currentIndex = -1;
                    @endphp
                    
                    <div class="progress-pipeline">
                        @foreach($pipelineSteps as $step => $label)
                            @php
                                $stepIndex = array_search($step, $stepOrder);
                                $isCompleted = $stepIndex < $currentIndex;
                                $isActive = $stepIndex === $currentIndex;
                            @endphp
                            
                            <div class="pipeline-step {{ $isCompleted ? 'completed' : '' }} {{ $isActive ? 'active' : '' }}">
                                <div class="pipeline-circle">
                                    @if($isCompleted)
                                        <i class="mdi mdi-check"></i>
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </div>
                                <div class="pipeline-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="text-center">
                        <span class="status-badge status-{{ $lead->status }}">
                            {{ $lead->status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Lead Notes -->
            @if($lead->notes)
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Poznámky</h4>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $lead->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Activity Timeline -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Časová osa aktivit</h4>
                </div>
                <div class="card-body">
                    <div class="timeline-item">
                        <div class="timeline-marker" style="background: #38a169;"></div>
                        <div class="timeline-content" style="border-left-color: #38a169;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Lead vytvořen</h6>
                                    <p class="text-muted mb-0">
                                        Lead byl úspěšně vytvořen v systému
                                        @if($lead->createdBy)
                                            uživatelem <strong>{{ $lead->createdBy->name }}</strong>
                                        @endif
                                    </p>
                                </div>
                                <small class="text-muted">{{ $lead->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>
                    </div>

                    @if($lead->last_activity_at && $lead->last_activity_at != $lead->created_at)
                    <div class="timeline-item">
                        <div class="timeline-marker" style="background: #3f51b5;"></div>
                        <div class="timeline-content" style="border-left-color: #3f51b5;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Poslední aktivita</h6>
                                    <p class="text-muted mb-0">Lead byl naposledy aktualizován</p>
                                </div>
                                <small class="text-muted">{{ $lead->last_activity_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($lead->tasks && $lead->tasks->count() > 0)
                        @foreach($lead->tasks->take(3) as $task)
                        <div class="timeline-item">
                            <div class="timeline-marker" style="background: #f57c00;"></div>
                            <div class="timeline-content" style="border-left-color: #f57c00;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $task->title }}</h6>
                                        <p class="text-muted mb-0">{{ $task->description }}</p>
                                    </div>
                                    <small class="text-muted">{{ $task->created_at->format('d.m.Y') }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Lead Score -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Lead Skóre</h4>
                </div>
                <div class="card-body text-center">
                    <div class="score-circle {{ $lead->score >= 70 ? 'score-high' : ($lead->score >= 40 ? 'score-medium' : 'score-low') }}">
                        {{ $lead->score }}
                    </div>
                    <p class="mt-3 mb-0">
                        @if($lead->score >= 70)
                            <span class="text-success fw-semibold">Vysoce kvalitní lead</span>
                        @elseif($lead->score >= 40)
                            <span class="text-warning fw-semibold">Středně kvalitní lead</span>
                        @else
                            <span class="text-danger fw-semibold">Nízko kvalitní lead</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Lead Information -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Informace</h4>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Zdroj:</span>
                        <span class="info-value">{{ $lead->source_label }}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $lead->status }}">
                                {{ $lead->status_label }}
                            </span>
                        </span>
                    </div>
                    
                    @if($lead->estimated_value)
                    <div class="info-item">
                        <span class="info-label">Odhadovaná hodnota:</span>
                        <span class="info-value fw-semibold text-success">
                            {{ number_format($lead->estimated_value, 0, ',', ' ') }} Kč
                        </span>
                    </div>
                    @endif
                    
                    <div class="info-item">
                        <span class="info-label">Přiřazeno:</span>
                        <span class="info-value">
                            @if($lead->assignedTo)
                                <span class="badge bg-primary">{{ $lead->assignedTo->name }}</span>
                            @else
                                <span class="text-muted">Nepřiřazeno</span>
                            @endif
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Vytvořil:</span>
                        <span class="info-value">
                            @if($lead->createdBy)
                                {{ $lead->createdBy->name }}
                            @else
                                <span class="text-muted">Neznámý</span>
                            @endif
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Vytvořeno:</span>
                        <span class="info-value">{{ $lead->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Poslední aktivita:</span>
                        <span class="info-value">
                            @if($lead->last_activity_at)
                                {{ $lead->last_activity_at->format('d.m.Y H:i') }}
                            @else
                                <span class="text-muted">Žádná</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Rychlé akce</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tasks.create', ['taskable_type' => 'lead', 'taskable_id' => $lead->id]) }}" class="btn btn-primary">
                            <i class="mdi mdi-clipboard-plus"></i> Nový úkol k leadu
                        </a>
                        <button class="btn btn-outline-primary" onclick="updateStatus('contacted')">
                            <i class="mdi mdi-phone"></i> Označit jako kontaktovaný
                        </button>
                        <button class="btn btn-outline-success" onclick="updateStatus('qualified')">
                            <i class="mdi mdi-check-circle"></i> Kvalifikovat lead
                        </button>
                        <button class="btn btn-outline-info" onclick="updateStatus('proposal')">
                            <i class="mdi mdi-file-document"></i> Odeslat nabídku
                        </button>
                        <a href="mailto:{{ $lead->email }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-email"></i> Odeslat email
                        </a>
                        @if($lead->phone)
                        <a href="tel:{{ $lead->phone }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-phone"></i> Zavolat
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<!-- container -->
@endsection

@section('script')
<script>
    function updateStatus(status) {
        if (confirm('Opravdu chcete změnit status tohoto lead?')) {
            fetch(`/crm/leads/{{ $lead->id }}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Chyba při aktualizaci statusu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Chyba při aktualizaci statusu');
            });
        }
    }
</script>
@endsection
