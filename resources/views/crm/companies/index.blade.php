@extends('layouts.vertical')

@section('title', 'Společnosti')

@section('css')
<!-- DataTables css -->
<link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item active">Společnosti</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    Společnosti
                    <a href="{{ url('/crm/companies/create') }}" class="btn btn-primary btn-sm ms-2">
                        <i class="ri-add-circle-line me-1"></i> Nová společnost
                    </a>
                </h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @include('layouts.partials.flash')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-5">
                            <h4 class="header-title">Seznam společností</h4>
                            <p class="text-muted font-13 mb-4">
                                Přehled všech společností v CRM systému s možností filtrování a vyhledávání.
                            </p>
                        </div>
                        <div class="col-sm-7">
                            <div class="text-sm-end">
                                <button type="button" class="btn btn-success btn-rounded waves-effect waves-light mb-2 me-2">
                                    <i class="ri-settings-3-line me-1"></i> Nastavení
                                </button>
                                <a href="{{ url('/crm/companies/create') }}" class="btn btn-primary btn-rounded waves-effect waves-light mb-2">
                                    <i class="ri-add-circle-line me-1"></i> Přidat společnost
                                </a>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.index') }}" class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label mb-0">Hledat</label>
                            <input type="text" name="q" class="form-control" value="{{ $qText ?? request('q') }}" placeholder="Název, email, telefon, web, město...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Vše</option>
                                @foreach(['active'=>'Aktivní','inactive'=>'Neaktivní','prospect'=>'Prospect','lost'=>'Ztracená'] as $key=>$label)
                                    <option value="{{ $key }}" {{ ($status ?? request('status'))===$key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0">Velikost</label>
                            <select name="size" class="form-select">
                                <option value="">Vše</option>
                                @foreach(['startup'=>'Startup','small'=>'Malá','medium'=>'Střední','large'=>'Velká','enterprise'=>'Enterprise'] as $key=>$label)
                                    <option value="{{ $key }}" {{ ($size ?? request('size'))===$key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0">Odvětví</label>
                            <input type="text" name="industry" class="form-control" value="{{ $industry ?? request('industry') }}" placeholder="Např. strojírenství">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0">Obrat min (Kč)</label>
                            <input type="number" step="0.01" name="min_turnover" class="form-control" value="{{ $minTurnover ?? request('min_turnover') }}" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0">Obrat max (Kč)</label>
                            <input type="number" step="0.01" name="max_turnover" class="form-control" value="{{ $maxTurnover ?? request('max_turnover') }}" placeholder="">
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="ri-search-line me-1"></i> Filtrovat
                            </button>
                            <a href="{{ route('companies.index') }}" class="btn btn-link btn-sm mt-1 w-100">Vyčistit</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="companies-datatable" class="table table-centered w-100 dt-responsive nowrap" >
                            <thead class="table-light">
                                <tr>
                                    <th class="all" style="width: 20px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck1">
                                            <label class="form-check-label" for="customCheck1">&nbsp;</label>
                                        </div>
                                    </th>
                                    <th class="all">Společnost</th>
                                    <th>Odvětví</th>
                                    <th>Velikost</th>
                                    <th>Status</th>
                                    <th>Město</th>
                                    <th>Kontakty</th>
                                    <th>
                                        Celkový obrat
                                        <a href="{{ route('companies.index', array_merge(request()->query(), ['sort' => 'turnover', 'dir' => ($sort==='turnover' && ($dir??'desc')==='desc') ? 'asc' : 'desc'])) }}" class="ms-1" title="Seřadit podle obratu">
                                            <i class="ri-sort-desc"></i>
                                        </a>
                                    </th>
                                    <th>Vytvořeno</th>
                                    <th style="width: 85px;">Akce</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companies as $company)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="customCheck{{ $company->id }}">
                                            <label class="form-check-label" for="customCheck{{ $company->id }}">&nbsp;</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-soft-primary rounded me-3">
                                                <span class="avatar-title text-primary font-20">
                                                    <i class="ri-building-line"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ route('companies.show', $company) }}" class="text-body fw-bold">{{ $company->name }}</a>
                                                @if($company->website)
                                                    <p class="m-0 d-inline-block text-muted font-12">
                                                        <a href="{{ $company->website }}" target="_blank" class="text-muted">
                                                            <i class="ri-global-line me-1"></i>{{ $company->website }}
                                                        </a>
                                                    </p>
                                                @endif
                                                @if($company->email)
                                                    <p class="m-0 d-inline-block text-muted font-12">
                                                        <i class="ri-mail-line me-1"></i>{{ $company->email }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($company->industry)
                                            <span class="badge badge-outline-secondary">{{ $company->industry }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($company->size)
                                            @case('startup')
                                                <span class="badge bg-info">Startup</span>
                                                @break
                                            @case('small')
                                                <span class="badge bg-light text-dark">Malá</span>
                                                @break
                                            @case('medium')
                                                <span class="badge bg-warning">Střední</span>
                                                @break
                                            @case('large')
                                                <span class="badge bg-success">Velká</span>
                                                @break
                                            @case('enterprise')
                                                <span class="badge bg-primary">Enterprise</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($company->status)
                                            @case('active')
                                                <span class="badge bg-success">Aktivní</span>
                                                @break
                                            @case('prospect')
                                                <span class="badge bg-warning">Prospect</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge bg-secondary">Neaktivní</span>
                                                @break
                                            @case('lost')
                                                <span class="badge bg-danger">Ztracená</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($company->city)
                                            <i class="ri-map-pin-line text-muted me-1"></i>{{ $company->city }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-info text-info">
                                            <i class="ri-group-line me-1"></i>{{ $company->contacts_count ?? $company->contacts->count() }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ number_format($company->total_turnover ?? 0, 2, ',', ' ') }} Kč
                                    </td>
                                    <td>
                                        {{ $company->created_at->format('d.m.Y') }}
                                        <small class="text-muted d-block">{{ $company->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('companies.show', $company) }}" class="action-icon"> 
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('companies.edit', $company) }}" class="action-icon"> 
                                                <i class="ri-edit-box-line"></i>
                                            </a>
                                            <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon btn btn-link p-0" onclick="return confirm('Opravdu chcete smazat tuto společnost?')" style="border: none; background: none;">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($companies->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $companies->appends(array_merge(request()->query(), ['sort' => $sort, 'dir' => $dir]))->links('pagination::bootstrap-5') }}
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
<!-- DataTables js -->
<script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

<script>
$(document).ready(function() {
    $('#companies-datatable').DataTable({
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
<style>
/* Pagination alignment and icon sizing */
.pagination { margin-bottom: 0; }
.page-link { display: flex; align-items: center; justify-content: center; }
.page-item .page-link svg { width: 1em; height: 1em; }
nav .page-item svg, nav .page-link svg { max-width: 1.25rem; max-height: 1.25rem; }
</style>
</script>
@endsection
