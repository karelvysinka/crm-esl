@extends('layouts.vertical', ['title' => 'Detail kontaktu'])

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/contacts') }}">Kontakty</a></li>
                        <li class="breadcrumb-item active">{{ $contact->full_name }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Detail kontaktu</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Success Message -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Left Column - Contact Info -->
        <div class="col-xl-8">
            <!-- Contact Header -->
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <span class="text-primary fw-bold fs-3">
                                        {{ strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="mb-1">{{ $contact->full_name }}</h4>
                                    @if($contact->position)
                                        <p class="text-muted mb-1">{{ $contact->position }}</p>
                                    @endif
                                    @if($contact->company)
                                        <div class="d-flex align-items-center">
                                            <i class="ri-building-line text-muted me-2"></i>
                                            <a href="{{ url('/crm/companies/' . $contact->company->id) }}" class="text-decoration-none">
                                                {{ $contact->company->name }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            @switch($contact->status)
                                @case('active')
                                    <span class="badge bg-success-subtle text-success fs-6">Aktivní</span>
                                    @break
                                @case('inactive')
                                    <span class="badge bg-secondary-subtle text-secondary fs-6">Neaktivní</span>
                                    @break
                                @case('blocked')
                                    <span class="badge bg-danger-subtle text-danger fs-6">Blokovaný</span>
                                    @break
                                @case('lead')
                                    <span class="badge bg-warning-subtle text-warning fs-6">Lead</span>
                                    @break
                                @case('prospect')
                                    <span class="badge bg-info-subtle text-info fs-6">Prospect</span>
                                    @break
                                @default
                                    <span class="badge bg-light text-muted fs-6">{{ $contact->status }}</span>
                            @endswitch
                            <div class="mt-2">
                                <a href="{{ url('/crm/contacts/' . $contact->id . '/edit') }}" class="btn btn-primary btn-sm me-2">
                                    <i class="ri-edit-line me-1"></i>Upravit
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="ri-delete-bin-line me-1"></i>Smazat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Details -->
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="ri-user-line me-2"></i>
                                Základní informace
                            </h6>
                            
                            <div class="row mb-2">
                                <div class="col-4"><strong>Velikost společnosti:</strong></div>
                                <div class="col-8">
                                    @if($contact->company && $contact->company->size)
                                        @switch($contact->company->size)
                                            @case('small')
                                                Malá (1-50 zaměstnanců)
                                                @break
                                            @case('medium')
                                                Střední (51-250 zaměstnanců)
                                                @break
                                            @case('large')
                                                Velká (250+ zaměstnanců)
                                                @break
                                            @default
                                                {{ $contact->company->size }}
                                        @endswitch
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>

                            @if($contact->department)
                            <div class="row mb-2">
                                <div class="col-4"><strong>Oddělení:</strong></div>
                                <div class="col-8">{{ $contact->department }}</div>
                            </div>
                            @endif

                            <div class="row mb-2">
                                <div class="col-4"><strong>Země:</strong></div>
                                <div class="col-8">
                                    <i class="ri-global-line text-muted me-2"></i>
                                    {{ $contact->country ?? 'Česká republika' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="ri-phone-line me-2"></i>
                                Kontaktní informace
                            </h6>
                            
                            @if($contact->email)
                            <div class="row mb-2">
                                <div class="col-4"><strong>Email:</strong></div>
                                <div class="col-8">
                                    <a href="mailto:{{ $contact->email }}" class="text-decoration-none">
                                        <i class="ri-mail-line text-muted me-2"></i>
                                        {{ $contact->email }}
                                    </a>
                                </div>
                            </div>
                            @endif

                            @if($contact->phone)
                            <div class="row mb-2">
                                <div class="col-4"><strong>Telefon:</strong></div>
                                <div class="col-8">
                                    <a href="tel:{{ $contact->phone }}" class="text-decoration-none">
                                        <i class="ri-phone-line text-muted me-2"></i>
                                        {{ $contact->phone }}
                                    </a>
                                </div>
                            </div>
                            @endif

                            @if($contact->mobile)
                            <div class="row mb-2">
                                <div class="col-4"><strong>Mobil:</strong></div>
                                <div class="col-8">
                                    <a href="tel:{{ $contact->mobile }}" class="text-decoration-none">
                                        <i class="ri-smartphone-line text-muted me-2"></i>
                                        {{ $contact->mobile }}
                                    </a>
                                </div>
                            </div>
                            @endif

                            @if($contact->preferred_contact)
                            <div class="row mb-2">
                                <div class="col-4"><strong>Preferovaný kontakt:</strong></div>
                                <div class="col-8">
                                    @switch($contact->preferred_contact)
                                        @case('email')
                                            <span class="badge bg-info-subtle text-info">Email</span>
                                            @break
                                        @case('phone')
                                            <span class="badge bg-primary-subtle text-primary">Telefon</span>
                                            @break
                                        @case('mobile')
                                            <span class="badge bg-success-subtle text-success">Mobil</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                            @endif

                            @if($contact->address || $contact->city)
                            <hr class="my-3">
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="ri-map-pin-line me-2"></i>Adresa
                            </h6>
                            @if($contact->address)
                                <p class="mb-1">{{ $contact->address }}</p>
                            @endif
                            @if($contact->city)
                                <p class="mb-0 text-muted">{{ $contact->city }}, {{ $contact->country ?? 'Česká republika' }}</p>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($contact->notes)
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="ri-file-text-line me-2"></i>
                        Poznámky
                    </h6>
                    <p class="mb-0">{{ $contact->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="ri-time-line me-2"></i>
                        Nedávná aktivita
                    </h6>
                    
                    <div class="timeline-container">
                        <div class="d-flex mb-3">
                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="ri-user-add-line text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Kontakt vytvořen</h6>
                                <p class="text-muted mb-0">{{ $contact->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>

                        @if($contact->updated_at != $contact->created_at)
                        <div class="d-flex mb-3">
                            <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="ri-edit-line text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Poslední aktualizace</h6>
                                <p class="text-muted mb-0">{{ $contact->updated_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($contact->opportunities->count() > 0)
                        <div class="d-flex mb-3">
                            <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="ri-briefcase-line text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Obchodní příležitosti</h6>
                                <p class="text-muted mb-0">{{ $contact->opportunities->count() }} aktivních příležitostí</p>
                            </div>
                        </div>
                        @endif

                        <div class="text-center mt-4">
                            <div class="avatar-sm bg-light rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="ri-more-line text-muted"></i>
                            </div>
                            <p class="text-muted mt-2 mb-0">
                                Pro tento kontakt zatím není evidována žádná aktivita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Stats & Actions -->
        <div class="col-xl-4">
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="ri-bar-chart-line me-2"></i>
                        Rychlé statistiky
                    </h6>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0 text-primary">Kontakty</h6>
                            <small class="text-muted">V této společnosti</small>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0 text-primary">
                                @if($contact->company)
                                    {{ $contact->company->contacts->count() }}
                                @else
                                    0
                                @endif
                            </h4>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0 text-warning">Obchody</h6>
                            <small class="text-muted">Celkový počet</small>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0 text-warning">{{ $contact->opportunities->count() }}</h4>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-success">Celková hodnota</h6>
                            <small class="text-muted">Ročního obratu</small>
                        </div>
                        <div class="text-end">
                            @if($contact->company && $contact->company->annual_revenue)
                                <h4 class="mb-0 text-success">{{ number_format($contact->company->annual_revenue, 0, ',', ' ') }} Kč</h4>
                            @else
                                <h4 class="mb-0 text-muted">0 Kč</h4>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="ri-calendar-event-line me-2"></i>
                        Časová osa
                    </h6>

                    <div class="timeline-item d-flex mb-3">
                        <div class="timeline-marker">
                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                <span class="text-primary fw-bold">
                                    {{ $contact->created_at->format('d.m') }}
                                </span>
                            </div>
                        </div>
                        <div class="timeline-content ms-3">
                            <h6 class="mb-1">Kontakt vytvořen</h6>
                            <p class="text-muted mb-0 small">{{ $contact->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>

                    @if($contact->updated_at != $contact->created_at)
                    <div class="timeline-item d-flex mb-3">
                        <div class="timeline-marker">
                            <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center">
                                <span class="text-warning fw-bold">
                                    {{ $contact->updated_at->format('d.m') }}
                                </span>
                            </div>
                        </div>
                        <div class="timeline-content ms-3">
                            <h6 class="mb-1">Poslední aktualizace</h6>
                            <p class="text-muted mb-0 small">{{ $contact->updated_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="ri-flashlight-line me-2"></i>
                        Rychlé akce
                    </h6>

                    <div class="d-grid gap-2">
                        @if($contact->email)
                        <a href="mailto:{{ $contact->email }}" class="btn btn-outline-primary btn-sm">
                            <i class="ri-mail-line me-1"></i>Napsat email
                        </a>
                        @endif

                        @if($contact->phone)
                        <a href="tel:{{ $contact->phone }}" class="btn btn-outline-success btn-sm">
                            <i class="ri-phone-line me-1"></i>Zavolat
                        </a>
                        @endif

                        <a href="{{ url('/crm/contacts/' . $contact->id . '/edit') }}" class="btn btn-outline-warning btn-sm">
                            <i class="ri-edit-line me-1"></i>Upravit kontakt
                        </a>

                        @if($contact->company)
                        <a href="{{ url('/crm/companies/' . $contact->company->id) }}" class="btn btn-outline-info btn-sm">
                            <i class="ri-building-line me-1"></i>Zobrazit společnost
                        </a>
                        @endif

                        <a href="{{ route('tasks.create', ['taskable_type' => 'contact', 'taskable_id' => $contact->id]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-task-line me-1"></i>Nový úkol
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Smazat kontakt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="avatar-md bg-danger-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <i class="ri-delete-bin-line text-danger fs-3"></i>
                    </div>
                    <h5 class="mb-3">Opravdu chcete smazat tento kontakt?</h5>
                    <p class="text-muted mb-0">
                        Tato akce je nevratná. Kontakt <strong>{{ $contact->full_name }}</strong> bude trvale odstraněn ze systému.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                <form action="{{ url('/crm/contacts/' . $contact->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-delete-bin-line me-1"></i>
                        Smazat kontakt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
