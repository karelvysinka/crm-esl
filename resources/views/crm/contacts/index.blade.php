@extends('layouts.vertical', ['title' => 'Kontakty'])

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Kontakty</li>
                    </ol>
                </div>
                <h4 class="page-title">Kontakty</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @if(!empty($stats))
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-primary rounded"><i class="ri-contacts-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['total'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Celkem</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Kontakty</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-success rounded"><i class="ri-calendar-event-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['new_month'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Tento měsíc</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Nové kontakty</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-success rounded"><i class="ri-checkbox-circle-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['statuses']['active'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Aktivní</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Status</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-warning rounded"><i class="ri-user-add-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['statuses']['lead'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Lead</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Status</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-info rounded"><i class="ri-user-search-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['statuses']['prospect'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Prospect</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Status</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-secondary rounded"><i class="ri-exchange-line avatar-title text-white font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['statuses']['inactive'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">Neaktivní</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Status</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="avatar-sm bg-primary-subtle rounded"><i class="ri-database-2-line avatar-title text-primary font-22"></i></div>
                    <div class="text-end">
                        <h4 class="my-0 text-dark" data-plugin="counterup">{{ number_format($stats['ac'],0,',',' ') }}</h4>
                        <p class="text-muted mb-0 small">AC import</p>
                    </div>
                </div>
                <div class="mt-2 small text-muted">Contacts z ActiveCampaign</div>
            </div></div>
        </div>
    </div>
    @endif

    @include('layouts.partials.flash')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-contacts-line me-2"></i>
                            Seznam kontaktů
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ url('/crm/contacts/create') }}" class="btn btn-primary">
                                <i class="ri-add-line me-1"></i>
                                Nový kontakt
                            </a>
                        </div>
                    </div>
                    <p class="text-muted mb-2 mt-2">Přehled všech kontaktů v CRM systému s možností filtrování a vyhledávání.</p>
                    <form method="GET" action="{{ url('/crm/contacts') }}" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label mb-0">Hledat</label>
                            <input type="text" name="q" value="{{ $qText ?? request('q') }}" class="form-control" placeholder="Jméno, email, telefon, společnost...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Vše</option>
                                <option value="active" {{ ($status ?? request('status'))==='active' ? 'selected' : '' }}>Aktivní</option>
                                <option value="inactive" {{ ($status ?? request('status'))==='inactive' ? 'selected' : '' }}>Neaktivní</option>
                                <option value="lead" {{ ($status ?? request('status'))==='lead' ? 'selected' : '' }}>Lead</option>
                                <option value="prospect" {{ ($status ?? request('status'))==='prospect' ? 'selected' : '' }}>Prospect</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="onlyAc" name="ac" {{ ($onlyAc ?? request('ac')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="onlyAc">
                                    Jen ActiveCampaign
                                </label>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="ri-search-line me-1"></i> Filtrovat
                            </button>
                            <a href="{{ url('/crm/contacts') }}" class="btn btn-link btn-sm mt-1 w-100">Vyčistit</a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap mb-0" id="contactsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Kontakt</th>
                                    <th>Společnost</th>
                                    <th>Pozice</th>
                                    <th>Kontaktní údaje</th>
                                    <th>Status</th>
                                    <th>Vytvořeno</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contacts as $contact)
                                <tr>
                                    <!-- Kontakt -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <span class="text-primary fw-bold">
                                                    @php
                                                        $fn = (string)($contact->first_name ?? '');
                                                        $ln = (string)($contact->last_name ?? '');
                                                        $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
                                                        if ($initials === '') { $initials = '•'; }
                                                    @endphp
                                                    {{ $initials }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">
                                                    {{ $contact->full_name }}
                                                    @if($contact->ac_id)
                                                        <span class="badge bg-primary-subtle text-primary ms-2" title="Importováno z ActiveCampaign">AC</span>
                                                    @endif
                                                </h6>
                                                @if($contact->department)
                                                    <small class="text-muted">{{ $contact->department }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Společnost -->
                                    <td>
                                        @if($contact->company)
                                            <a href="{{ url('/crm/companies/' . $contact->company->id) }}" class="text-decoration-none">
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-building-line text-muted me-2"></i>
                                                    <span>{{ $contact->company->name }}</span>
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <!-- Pozice -->
                                    <td>
                                        @if($contact->position)
                                            <span class="badge bg-info-subtle text-info">{{ $contact->position }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <!-- Kontaktní údaje -->
                                    <td>
                                        <div class="d-flex flex-column">
                                            @php $isPlaceholder = $contact->email && (str_contains($contact->email, '@placeholder.local') || str_starts_with($contact->email, 'noemail-')); @endphp
                                            @if($contact->email && !$isPlaceholder)
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="ri-mail-line text-muted me-2" style="font-size: 12px;"></i>
                                                    <small>{{ $contact->email }}</small>
                                                </div>
                                            @endif
                                            @if($contact->phone)
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="ri-phone-line text-muted me-2" style="font-size: 12px;"></i>
                                                    <small>{{ $contact->phone }}</small>
                                                </div>
                                            @endif
                                            @if($contact->mobile)
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-smartphone-line text-muted me-2" style="font-size: 12px;"></i>
                                                    <small>{{ $contact->mobile }}</small>
                                                </div>
                                            @endif
                                            @if(!($contact->email && !$isPlaceholder) && !($contact->phone) && !($contact->mobile))
                                                <small class="text-muted">—</small>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        @switch($contact->status)
                                            @case('active')
                                                <span class="badge bg-success-subtle text-success">Aktivní</span>
                                                @break
                                            @case('inactive') 
                                                <span class="badge bg-secondary-subtle text-secondary">Neaktivní</span>
                                                @break
                                            @case('lead')
                                                <span class="badge bg-warning-subtle text-warning">Lead</span>
                                                @break
                                            @case('prospect')
                                                <span class="badge bg-info-subtle text-info">Prospect</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-muted">{{ $contact->status }}</span>
                                        @endswitch
                                    </td>

                                    <!-- Vytvořeno -->
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-muted">{{ $contact->created_at->format('d.m.Y') }}</small>
                                            <small class="text-muted">{{ $contact->created_at->format('H:i') }}</small>
                                        </div>
                                    </td>

                                    <!-- Akce -->
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ url('/crm/contacts/' . $contact->id) }}" class="action-icon" title="Zobrazit detail">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ url('/crm/contacts/' . $contact->id . '/edit') }}" class="action-icon" title="Upravit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($contacts, 'links'))
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $contacts->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-primary-subtle">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="text-primary mb-1">Rychlé akce</h6>
                            <p class="text-muted mb-0">Často používané funkce pro správu kontaktů</p>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <a href="{{ url('/crm/contacts/create') }}" class="btn btn-primary btn-sm">
                                    <i class="ri-add-line me-1"></i>Nový kontakt
                                </a>
                                <a href="{{ url('/crm/companies') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="ri-building-line me-1"></i>Společnosti
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#contactsTable').DataTable({
        responsive: true,
        paging: false,      // vypnout stránkování DataTables (použijeme serverové)
        info: false,        // skrýt "Showing x of y"
        searching: false,   // hledání řeší náš formulář nahoře
        ordering: false,    // řazení řeší backend / aktuální pořadí
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/cs.json'
        }
    });
});
</script>
<style>
/* Pagination alignment and icon sizing */
.pagination { margin-bottom: 0; }
.page-link { display: flex; align-items: center; justify-content: center; }
.page-item .page-link svg { width: 1em; height: 1em; }
/* Prevent any rogue SVG from stretching full width */
nav .page-item svg, nav .page-link svg { max-width: 1.25rem; max-height: 1.25rem; }
</style>
@endsection
