@extends('layouts.vertical', ['title' => $company->name])

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/companies') }}">Společnosti</a></li>
                        <li class="breadcrumb-item active">{{ $company->name }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Detail společnosti</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function attachPaginationHandlers(container, orderId) {
        // Use event delegation to handle all anchors inside the container
        if (container.__itemsDelegated) return; // avoid duplicate binding
        container.__itemsDelegated = true;
        container.addEventListener('click', function(e) {
            var a = e.target && e.target.closest ? e.target.closest('a') : null;
            if (!a || !a.getAttribute) return;
            var href = a.getAttribute('href');
            if (!href) return;
            // Intercept pagination links; handle absolute and relative (?page=...) forms
            var isPagination = href.indexOf('page=') !== -1 || (href.indexOf('/crm/orders/') !== -1 && href.indexOf('/items') !== -1);
            if (isPagination) {
                e.preventDefault();
                var basePath = container.dataset.baseUrl || ('/crm/orders/' + orderId + '/items');
                var base = window.location.origin + basePath;
                var url = new URL(href, base);
                url.searchParams.set('partial', '1');
                loadOrderItems(orderId, container, url.toString());
            }
        });
    }

    function loadOrderItems(orderId, targetEl, url) {
        var basePath = '/crm/orders/' + orderId + '/items';
        var fetchUrl = url || (basePath + '?partial=1');
        // Store base URL (without query) on the container for resolving relative paginator links
        try {
            var u0 = new URL(fetchUrl, window.location.origin);
            targetEl.dataset.baseUrl = u0.pathname;
        } catch (e) {
            targetEl.dataset.baseUrl = basePath;
        }
        return fetch(fetchUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml'
                },
                credentials: 'same-origin'
            })
            .then(function(resp){
                if (!resp.ok) {
                    return resp.text().then(function(t){
                        throw new Error('HTTP ' + resp.status + ' ' + resp.statusText + '\n' + t.slice(0, 500));
                    });
                }
                return resp.text();
            })
            .then(function(html) {
                targetEl.innerHTML = html;
                attachPaginationHandlers(targetEl, orderId);
            })
            .catch(function(err) {
                targetEl.innerHTML = '<div class="text-danger small p-2">Chyba načítání položek.</div>';
                console.error('Items load error:', err);
            });
    }

    document.querySelectorAll('.show-items-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var orderId = this.getAttribute('data-order-id');
            var target = document.getElementById('order-items-' + orderId);
            if (target.innerHTML.trim() !== '') {
                target.innerHTML = '';
                this.innerHTML = '<i class="ri-list-unordered me-1"></i> Zobrazit položky';
                return;
            }
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Načítám...';
            loadOrderItems(orderId, target).then(() => {
                this.innerHTML = '<i class="ri-list-unordered me-1"></i> Skrýt položky';
            });
        });
    });
});
</script>
@endpush

    <div class="row">
        <!-- Company Header -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg me-3">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </div>
                                </div>
                                <div>
                                    <h3 class="mb-1">{{ $company->name }}</h3>
                                    <p class="text-muted mb-0">
                                        @if($company->industry)
                                            <i class="ri-building-2-line me-1"></i>{{ $company->industry }}
                                        @endif
                                        @if($company->industry && $company->city)
                                            <span class="mx-2">•</span>
                                        @endif
                                        @if($company->city)
                                            <i class="ri-map-pin-line me-1"></i>{{ $company->city }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            @php
                                $statusClass = match($company->status) {
                                    'active' => 'bg-success-subtle text-success',
                                    'inactive' => 'bg-danger-subtle text-danger',
                                    'prospect' => 'bg-warning-subtle text-warning',
                                    default => 'bg-secondary-subtle text-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-6 px-3 py-2 mb-2 d-inline-block">
                                @switch($company->status)
                                    @case('active') <i class="ri-check-line me-1"></i>Aktivní @break
                                    @case('inactive') <i class="ri-close-line me-1"></i>Neaktivní @break
                                    @case('prospect') <i class="ri-eye-line me-1"></i>Prospect @break
                                    @default {{ ucfirst($company->status) }}
                                @endswitch
                            </span>
                            <div class="mt-2">
                                <a href="{{ url('/crm/companies/' . $company->id . '/edit') }}" class="btn btn-primary btn-sm me-2">
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
        </div>
    </div>

    <div class="row">
        <!-- Company Information -->
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-information-line me-2"></i>
                        Základní informace
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Velikost společnosti</h6>
                            <p class="mb-0">
                                @switch($company->size)
                                    @case('small')
                                        <i class="ri-building-line me-1"></i>Malá (1-50 zaměstnanců)
                                        @break
                                    @case('medium')
                                        <i class="ri-building-2-line me-1"></i>Střední (51-250 zaměstnanců)
                                        @break
                                    @case('large')
                                        <i class="ri-building-3-line me-1"></i>Velká (250+ zaměstnanců)
                                        @break
                                    @default
                                        <span class="text-muted">Neuvedeno</span>
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Země</h6>
                            <p class="mb-0">
                                @if($company->country)
                                    <i class="ri-flag-line me-1"></i>{{ $company->country }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
                        </div>
                        @if($company->employees_count)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Počet zaměstnanců</h6>
                            <p class="mb-0">
                                <i class="ri-team-line me-1"></i>{{ number_format($company->employees_count) }}
                            </p>
                        </div>
                        @endif
                        @if($company->revenue)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Roční obrat</h6>
                            <p class="mb-0">
                                <i class="ri-money-dollar-circle-line me-1"></i>{{ number_format($company->revenue) }} Kč
                            </p>
                        </div>
                        @endif
                    </div>
                    
                    @if($company->description)
                    <div class="mt-3">
                        <h6 class="text-muted fw-normal mb-2">Popis společnosti</h6>
                        <p class="mb-0">{{ $company->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-contacts-line me-2"></i>
                        Kontaktní informace
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($company->website)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Website</h6>
                            <p class="mb-0">
                                <i class="ri-global-line me-1"></i>
                                <a href="{{ $company->website }}" target="_blank" class="text-decoration-none">
                                    {{ $company->website }}
                                    <i class="ri-external-link-line ms-1 small"></i>
                                </a>
                            </p>
                        </div>
                        @endif
                        
                        @if($company->email)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Email</h6>
                            <p class="mb-0">
                                <i class="ri-mail-line me-1"></i>
                                <a href="mailto:{{ $company->email }}" class="text-decoration-none">
                                    {{ $company->email }}
                                </a>
                            </p>
                        </div>
                        @endif
                        
                        @if($company->phone)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Telefon</h6>
                            <p class="mb-0">
                                <i class="ri-phone-line me-1"></i>
                                <a href="tel:{{ $company->phone }}" class="text-decoration-none">
                                    {{ $company->phone }}
                                </a>
                            </p>
                        </div>
                        @endif
                        
                        @if($company->address)
                        <div class="col-12 mb-3">
                            <h6 class="text-muted fw-normal mb-1">Adresa</h6>
                            <p class="mb-0">
                                <i class="ri-map-pin-line me-1"></i>
                                {{ $company->address }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabs: Contacts | Orders -->
            <div class="card">
                <div class="card-header border-bottom-0 pb-0">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="tab-contacts" data-bs-toggle="tab" href="#pane-contacts" role="tab" aria-controls="pane-contacts" aria-selected="true">
                                <i class="ri-contacts-line me-1"></i> Kontakty
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="tab-orders" data-bs-toggle="tab" href="#pane-orders" role="tab" aria-controls="pane-orders" aria-selected="false">
                                <i class="ri-file-list-3-line me-1"></i> Objednávky
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Contacts pane -->
                        <div class="tab-pane fade show active" id="pane-contacts" role="tabpanel" aria-labelledby="tab-contacts">
                            <div class="d-flex justify-content-end mb-2">
                                <a href="{{ url('/crm/contacts/create?company_id=' . $company->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-user-add-line me-1"></i> Přidat kontakt
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kontakt</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>Obrat kontaktu</th>
                                            <th>Akce</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contacts as $contact)
                                        @php $contactTurnover = (float) ($contactTurnovers[$contact->id] ?? 0); @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ url('/crm/contacts/' . $contact->id) }}" class="text-decoration-none">
                                                    {{ $contact->full_name }}
                                                </a>
                                            </td>
                                            <td>
                                                @php $isPlaceholder = $contact->email && (str_contains($contact->email, '@placeholder.local') || str_starts_with($contact->email, 'noemail-')); @endphp
                                                @if($contact->email && !$isPlaceholder)
                                                    <i class="ri-mail-line me-1"></i>
                                                    <span>{{ $contact->email }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($contact->phone)
                                                    <i class="ri-phone-line me-1"></i>{{ $contact->phone }}
                                                @elseif($contact->mobile)
                                                    <i class="ri-smartphone-line me-1"></i>{{ $contact->mobile }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($contactTurnover, 2, ',', ' ') }} Kč</td>
                                            <td>
                                                <a href="{{ url('/crm/contacts/' . $contact->id . '/edit') }}" class="btn btn-sm btn-outline-secondary">Upravit</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @if($contacts->isEmpty())
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Žádné kontakty</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div class="mt-2">
                                    {{ $contacts->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>

                        <!-- Orders pane -->
                        <div class="tab-pane fade" id="pane-orders" role="tabpanel" aria-labelledby="tab-orders">
                            <div class="table-responsive">
                                <table class="table table-hover table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th># Zakázky</th>
                                            <th>Datum</th>
                                            <th>Kontakt</th>
                                            <th>Autor</th>
                                            <th>Celkem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                        <tr>
                                            <td>{{ $order->external_order_no }}</td>
                                            <td>{{ optional($order->order_date)->format('d.m.Y') }}</td>
                                            <td>
                                                @if($order->contact_id)
                                                    @php
                                                        $contact = null;
                                                        foreach ($contacts as $c) {
                                                            if ($c->id === $order->contact_id) { $contact = $c; break; }
                                                        }
                                                    @endphp
                                                    @if($contact)
                                                        <a href="{{ url('/crm/contacts/' . $contact->id) }}">{{ $contact->full_name }}</a>
                                                    @else
                                                        <span class="text-muted">ID {{ $order->contact_id }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->author ?? '-' }}</td>
                                            <td>{{ number_format($order->total_amount, 2, ',', ' ') }} Kč</td>
                                        </tr>
                                        <tr class="bg-light-subtle">
                                            <td colspan="5" class="p-0">
                                                <button class="btn btn-link btn-sm text-primary show-items-btn" data-order-id="{{ $order->id }}" type="button">
                                                    <i class="ri-list-unordered me-1"></i> Zobrazit položky
                                                </button>
                                                <div id="order-items-{{ $order->id }}"></div>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @if($orders->isEmpty())
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Žádné objednávky</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            <div class="mt-2">
                                {{ $orders->withQueryString()->links() }}
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (placeholder) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-time-line me-2"></i>
                        Nedávná aktivita
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-light text-muted rounded-circle">
                                <i class="ri-history-line fs-4"></i>
                            </div>
                        </div>
                        <h6 class="text-muted">Žádná aktivita</h6>
                        <p class="text-muted mb-0">Pro tuto společnost zatím není evidována žádná aktivita.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-pie-chart-line me-2"></i>
                        Rychlé statistiky
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Kontakty</span>
                        <span class="badge bg-primary-subtle text-primary">{{ $contactsCount ?? ($contacts ? $contacts->total() : 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Objednávky</span>
                        <span class="badge bg-success-subtle text-success">{{ $ordersCount ?? ($orders ? $orders->total() : 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Úkoly</span>
                        <span class="badge bg-warning-subtle text-warning">{{ isset($company->tasks) ? $company->tasks->count() : 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Celkový obrat</span>
                        <span class="fw-bold">{{ number_format(($totalTurnover ?? 0), 2, ',', ' ') }} Kč</span>
                    </div>
                    @if(!empty($yearlyTurnover) && count($yearlyTurnover))
                    <hr>
                    <h6 class="text-muted fw-normal mb-2">Obrat podle roku</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($yearlyTurnover as $yr => $sum)
                        <li class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $yr }}</span>
                            <span class="fw-semibold">{{ number_format($sum, 2, ',', ' ') }} Kč</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>

            <!-- Company Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-calendar-line me-2"></i>
                        Časová osa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Společnost vytvořena</h6>
                                <p class="timeline-text text-muted">
                                    {{ $company->created_at->format('d.m.Y H:i') }}
                                </p>
                            </div>
                        </div>
                        @if($company->updated_at != $company->created_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Poslední aktualizace</h6>
                                <p class="timeline-text text-muted">
                                    {{ $company->updated_at->format('d.m.Y H:i') }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-flashlight-line me-2"></i>
                        Rychlé akce
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" disabled>
                            <i class="ri-user-add-line me-2"></i>
                            Přidat kontakt
                        </button>
                        <button class="btn btn-outline-success" disabled>
                            <i class="ri-briefcase-line me-2"></i>
                            Vytvořit obchod
                        </button>
                        <a class="btn btn-outline-info" href="{{ route('tasks.create', ['taskable_type' => 'company', 'taskable_id' => $company->id]) }}">
                            <i class="ri-task-line me-2"></i>
                            Nový úkol
                        </a>
                        <button class="btn btn-outline-warning" disabled>
                            <i class="ri-calendar-event-line me-2"></i>
                            Naplánovat schůzku
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="ri-information-line me-1"></i>
                        Funkce budou dostupné v příštích verzích
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Potvrzení smazání</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="avatar-md mx-auto mb-3">
                        <div class="avatar-title bg-danger-subtle text-danger rounded-circle">
                            <i class="ri-delete-bin-line fs-4"></i>
                        </div>
                    </div>
                    <h5>Opravdu chcete smazat společnost?</h5>
                    <p class="text-muted">
                        Společnost <strong>{{ $company->name }}</strong> bude trvale smazána. 
                        Tuto akci nelze vrátit zpět.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                <form action="{{ url('/crm/companies/' . $company->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-delete-bin-line me-1"></i>
                        Smazat společnost
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e3e6ef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #e3e6ef;
}

.timeline-content {
    padding-left: 20px;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-text {
    font-size: 13px;
    margin-bottom: 0;
}
</style>
@endsection
