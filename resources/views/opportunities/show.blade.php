@extends('layouts.vertical', ['page_title' => 'Detail příležitosti'])

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
                        <li class="breadcrumb-item active">{{ $opportunity->title }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ $opportunity->title }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8">
            <!-- Opportunity Details -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">Detail příležitosti</h4>
                    <div>
                        <a href="{{ url('/crm/opportunities/' . $opportunity->id . '/edit') }}" class="btn btn-warning btn-sm">
                            <i class="ti ti-pencil me-1"></i> Upravit
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete-modal">
                            <i class="ti ti-trash me-1"></i> Smazat
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Základní informace</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Název:</label>
                                <p class="mb-1">{{ $opportunity->title }}</p>
                            </div>

                            @if($opportunity->description)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Popis:</label>
                                <p class="mb-1">{{ $opportunity->description }}</p>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Hodnota:</label>
                                        <p class="mb-1 fs-5 text-success fw-bold">{{ number_format($opportunity->value, 0, ',', ' ') }} Kč</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Pravděpodobnost:</label>
                                        <div class="progress mt-1" style="height: 25px;">
                                            <div class="progress-bar bg-{{ $opportunity->probability >= 75 ? 'success' : ($opportunity->probability >= 50 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $opportunity->probability }}%"
                                                 aria-valuenow="{{ $opportunity->probability }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $opportunity->probability }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Fáze:</label>
                                        <p class="mb-1">
                                            <span class="badge bg-{{ $opportunity->stage_color }}-lighten text-{{ $opportunity->stage_color }} fs-6">
                                                {{ match($opportunity->stage) {
                                                    'qualification' => 'Kvalifikace',
                                                    'proposal' => 'Návrh',
                                                    'negotiation' => 'Vyjednávání', 
                                                    'closed_won' => 'Uzavřeno - Vyhráno',
                                                    'closed_lost' => 'Uzavřeno - Prohráno',
                                                    default => $opportunity->stage
                                                } }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status:</label>
                                        <p class="mb-1">
                                            <span class="badge bg-{{ $opportunity->status_badge }}-lighten text-{{ $opportunity->status_badge }} fs-6">
                                                {{ match($opportunity->status) {
                                                    'open' => 'Otevřeno',
                                                    'won' => 'Vyhráno',
                                                    'lost' => 'Prohráno',
                                                    'on_hold' => 'Pozastaveno',
                                                    default => $opportunity->status
                                                } }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($opportunity->source)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Zdroj:</label>
                                <p class="mb-1">{{ $opportunity->source }}</p>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">Termíny a přiřazení</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Očekávané uzavření:</label>
                                <p class="mb-1">
                                    @if($opportunity->expected_close_date)
                                        <i class="ti ti-calendar me-1"></i>
                                        {{ $opportunity->expected_close_date->format('d.m.Y') }}
                                        <small class="text-muted">
                                            ({{ $opportunity->expected_close_date->diffForHumans() }})
                                        </small>
                                    @else
                                        <span class="text-muted">Neurčeno</span>
                                    @endif
                                </p>
                            </div>

                            @if($opportunity->actual_close_date)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Skutečné uzavření:</label>
                                <p class="mb-1 text-success">
                                    <i class="ti ti-check me-1"></i>
                                    {{ $opportunity->actual_close_date->format('d.m.Y') }}
                                </p>
                            </div>
                            @endif

                            @if($opportunity->assignedUser)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Přiřazeno:</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <span class="avatar-title bg-primary-lighten text-primary rounded-circle">
                                            {{ substr($opportunity->assignedUser->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="mb-0">{{ $opportunity->assignedUser->name }}</p>
                                        @if($opportunity->assignedUser->email)
                                            <small class="text-muted">{{ $opportunity->assignedUser->email }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-bold">Vytvořeno:</label>
                                <p class="mb-1">
                                    <i class="ti ti-clock me-1"></i>
                                    {{ $opportunity->created_at->format('d.m.Y H:i') }}
                                    <small class="text-muted">
                                        ({{ $opportunity->created_at->diffForHumans() }})
                                    </small>
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Upraveno:</label>
                                <p class="mb-1">
                                    <i class="ti ti-edit me-1"></i>
                                    {{ $opportunity->updated_at->format('d.m.Y H:i') }}
                                    <small class="text-muted">
                                        ({{ $opportunity->updated_at->diffForHumans() }})
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($opportunity->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Poznámky</h5>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-0">{{ $opportunity->notes }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Company Information -->
            @if($opportunity->company)
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title mb-0">Společnost</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-primary-lighten text-primary rounded">
                                {{ substr($opportunity->company->name, 0, 2) }}
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                <a href="{{ url('/crm/companies/' . $opportunity->company->id) }}" class="text-decoration-none">
                                    {{ $opportunity->company->name }}
                                </a>
                            </h5>
                            @if($opportunity->company->industry)
                                <p class="text-muted mb-0">{{ $opportunity->company->industry }}</p>
                            @endif
                        </div>
                    </div>

                    @if($opportunity->company->website)
                    <div class="mb-2">
                        <i class="ti ti-world me-2"></i>
                        <a href="{{ $opportunity->company->website }}" target="_blank" class="text-decoration-none">
                            {{ $opportunity->company->website }}
                        </a>
                    </div>
                    @endif

                    @if($opportunity->company->email)
                    <div class="mb-2">
                        <i class="ti ti-mail me-2"></i>
                        <a href="mailto:{{ $opportunity->company->email }}" class="text-decoration-none">
                            {{ $opportunity->company->email }}
                        </a>
                    </div>
                    @endif

                    @if($opportunity->company->phone)
                    <div class="mb-2">
                        <i class="ti ti-phone me-2"></i>
                        <a href="tel:{{ $opportunity->company->phone }}" class="text-decoration-none">
                            {{ $opportunity->company->phone }}
                        </a>
                    </div>
                    @endif

                    <div class="text-end mt-3">
                        <a href="{{ url('/crm/companies/' . $opportunity->company->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-eye me-1"></i> Zobrazit detail
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Contact Information -->
            @if($opportunity->contact)
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title mb-0">Kontakt</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg me-3">
                            <span class="avatar-title bg-info-lighten text-info rounded-circle">
                                {{ substr($opportunity->contact->name, 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                <a href="{{ url('/crm/contacts/' . $opportunity->contact->id) }}" class="text-decoration-none">
                                    {{ $opportunity->contact->name }}
                                </a>
                            </h5>
                            @if($opportunity->contact->position)
                                <p class="text-muted mb-0">{{ $opportunity->contact->position }}</p>
                            @endif
                        </div>
                    </div>

                    @if($opportunity->contact->email)
                    <div class="mb-2">
                        <i class="ti ti-mail me-2"></i>
                        <a href="mailto:{{ $opportunity->contact->email }}" class="text-decoration-none">
                            {{ $opportunity->contact->email }}
                        </a>
                    </div>
                    @endif

                    @if($opportunity->contact->phone)
                    <div class="mb-2">
                        <i class="ti ti-phone me-2"></i>
                        <a href="tel:{{ $opportunity->contact->phone }}" class="text-decoration-none">
                            {{ $opportunity->contact->phone }}
                        </a>
                    </div>
                    @endif

                    <div class="text-end mt-3">
                        <a href="{{ url('/crm/contacts/' . $opportunity->contact->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="ti ti-eye me-1"></i> Zobrazit detail
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title mb-0">Rychlé akce</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ url('/crm/opportunities/' . $opportunity->id . '/edit') }}" class="btn btn-warning">
                            <i class="ti ti-pencil me-1"></i> Upravit příležitost
                        </a>
                        
                        @if($opportunity->contact && $opportunity->contact->email)
                        <a href="mailto:{{ $opportunity->contact->email }}?subject=Ohledně {{ $opportunity->title }}" class="btn btn-info">
                            <i class="ti ti-mail me-1"></i> Napsat email
                        </a>
                        @endif
                        
                        @if($opportunity->contact && $opportunity->contact->phone)
                        <a href="tel:{{ $opportunity->contact->phone }}" class="btn btn-success">
                            <i class="ti ti-phone me-1"></i> Zavolat
                        </a>
                        @endif
                        
                        <a href="{{ url('/crm/opportunities-pipeline') }}" class="btn btn-outline-primary">
                            <i class="ti ti-layout-kanban me-1"></i> Zobrazit pipeline
                        </a>
                        <a href="{{ route('tasks.create', ['taskable_type' => 'opportunity', 'taskable_id' => $opportunity->id]) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-list-check me-1"></i> Nový úkol k příležitosti
                        </a>
                        <a href="{{ route('deals.create', ['opportunity_id' => $opportunity->id]) }}" class="btn btn-primary">
                            <i class="ti ti-currency-dollar me-1"></i> Nový deal z této příležitosti
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal" tabindex="-1" aria-labelledby="delete-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="delete-modal-label">Potvrdit smazání</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Opravdu chcete smazat příležitost <strong>{{ $opportunity->title }}</strong>?</p>
                <p class="text-muted">Tato akce je nevratná.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                <form action="{{ url('/crm/opportunities/' . $opportunity->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Smazat</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
