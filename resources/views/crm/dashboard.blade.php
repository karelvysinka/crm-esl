@extends('layouts.vertical')

@section('title', 'CRM Dashboard')

@section('css')
<!-- Chart.js CSS -->
<link href="{{ asset('libs/chart.js/Chart.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    @isset($acEnabled)
        @if(!$acEnabled)
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="ri-information-line me-2"></i>
            <div>
                ActiveCampaign synchronizace je vypnutá nebo dočasně nedostupná. Stránka běží bez AC dat.
                <a href="{{ route('system.ac.index') }}" class="alert-link">Otevřít nastavení</a>
            </div>
        </div>
        @endif
    @endisset
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Domů</a></li>
                        <li class="breadcrumb-item active">CRM Dashboard</li>
                    </ol>
                </div>
                <h4 class="page-title">CRM Dashboard</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Stats cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-sm bg-blue rounded">
                                <i class="ri-building-line avatar-title font-22 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <h3 class="text-dark my-1"><span data-plugin="counterup">{{ \App\Models\Company::count() }}</span></h3>
                                <p class="text-muted mb-1 text-truncate">Společnosti</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="text-uppercase">Celkem <span class="text-muted">(aktivní: {{ \App\Models\Company::where('status', 'active')->count() }})</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-sm bg-success rounded">
                                <i class="ri-contacts-line avatar-title font-22 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <h3 class="text-dark my-1"><span data-plugin="counterup">{{ \App\Models\Contact::count() }}</span></h3>
                                <p class="text-muted mb-1 text-truncate">Kontakty</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="text-uppercase">Celkem <span class="text-muted">(nové: {{ \App\Models\Contact::whereDate('created_at', today())->count() }})</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-sm bg-warning rounded">
                                <i class="ri-user-star-line avatar-title font-22 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <h3 class="text-dark my-1"><span data-plugin="counterup">{{ \App\Models\Lead::count() }}</span></h3>
                                <p class="text-muted mb-1 text-truncate">Leady</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="text-uppercase">Aktivní <span class="text-muted">(hot: {{ \App\Models\Lead::where('status', 'hot')->count() }})</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-sm bg-info rounded">
                                <i class="ri-money-dollar-circle-line avatar-title font-22 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <h3 class="text-dark my-1"><span data-plugin="counterup">{{ \App\Models\Opportunity::count() }}</span></h3>
                                <p class="text-muted mb-1 text-truncate">Příležitosti</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="text-uppercase">Aktivní <span class="text-muted">(hodnota: {{ number_format(\App\Models\Opportunity::sum('value'), 0, ',', ' ') }} Kč)</span></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end stats -->

    <!-- Contacts Over Time Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Kontakty v čase</h4>
                    <p class="text-muted font-13 mb-3">Počet nově vytvořených kontaktů za posledních 12 měsíců.</p>

                    @php
                        $since = now()->subMonths(11)->startOfMonth();
                        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                        $expr = match ($driver) {
                            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
                            'pgsql' => "to_char(created_at, 'YYYY-MM')",
                            default => "strftime('%Y-%m', created_at)", // sqlite & fallback
                        };
                        $raw = \App\Models\Contact::selectRaw("$expr as ym, COUNT(*) as c")
                            ->where('created_at', '>=', $since)
                            ->groupBy('ym')
                            ->orderBy('ym')
                            ->pluck('c','ym')
                            ->toArray();

                        $labels = [];
                        $seriesData = [];
                        for ($i = 0; $i < 12; $i++) {
                            $m = $since->copy()->addMonths($i);
                            $ym = $m->format('Y-m');
                            $labels[] = $m->isoFormat('MMM YYYY');
                            $seriesData[] = $raw[$ym] ?? 0;
                        }
                    @endphp

                    <div class="px-1" dir="ltr">
                        <div id="contacts-over-time-chart" class="apex-charts" data-colors="#10c469,#35b8e0"
                             data-labels='@json($labels)' data-series='@json($seriesData)'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end contacts over time chart -->

    <!-- Import Status -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Stav posledního importu</h4>
                    @php
                        $importDir = storage_path('app/import_logs');
                        $latestImport = null;
                        if (is_dir($importDir)) {
                            $files = collect(glob($importDir . '/*.json'))
                                ->map(fn($f) => ['path' => $f, 'mtime' => filemtime($f)])
                                ->sortByDesc('mtime')
                                ->values();
                            if ($files->count()) {
                                $latestImport = $files[0]['path'];
                            }
                        }
                        $summary = null;
                        if ($latestImport) {
                            $content = @file_get_contents($latestImport);
                            $data = @json_decode($content, true);
                            $summary = is_array($data) ? ($data['summary'] ?? $data) : null;
                        }
                    @endphp
                    @if($latestImport && $summary)
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Soubor</p>
                                <p class="mb-2"><i class="ri-file-list-2-line me-1"></i>{{ basename($latestImport) }}</p>
                                <p class="mb-1 text-muted">Čas</p>
                                <p class="mb-0">{{ \Carbon\Carbon::createFromTimestamp(filemtime($latestImport))->format('d.m.Y H:i:s') }}</p>
                            </div>
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            @foreach($summary as $k => $v)
                                            <tr>
                                                <td class="text-muted" style="width:40%">{{ ucfirst(str_replace('_',' ', $k)) }}</td>
                                                <td>{{ is_array($v) ? json_encode($v) : (is_numeric($v) ? number_format((float)$v, 0, ',', ' ') : (string)$v) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Zatím nebyly nalezeny žádné importy.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- end import status -->

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Rychlé akce</h4>
                    <p class="text-muted font-13 mb-4">
                        Nejčastěji používané funkce CRM systému pro rychlé vytvoření nových záznamů.
                    </p>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="{{ route('companies.create') }}" class="btn btn-outline-primary btn-rounded w-100 mb-2">
                                    <i class="ri-building-line me-1"></i> Nová společnost
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-success btn-rounded w-100 mb-2">
                                    <i class="ri-contacts-line me-1"></i> Nový kontakt
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-warning btn-rounded w-100 mb-2">
                                    <i class="ri-user-star-line me-1"></i> Nový lead
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-info btn-rounded w-100 mb-2">
                                    <i class="ri-money-dollar-circle-line me-1"></i> Nová příležitost
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-secondary btn-rounded w-100 mb-2">
                                    <i class="ri-task-line me-1"></i> Nový úkol
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-dark btn-rounded w-100 mb-2">
                                    <i class="ri-handshake-line me-1"></i> Nový deal
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="{{ route('companies.index') }}" class="btn btn-outline-primary btn-rounded w-100 mb-2">
                                    <i class="ri-list-check me-1"></i> Seznam společností
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="text-center">
                                <a href="#" class="btn btn-outline-success btn-rounded w-100 mb-2">
                                    <i class="ri-bar-chart-line me-1"></i> Reporty
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end quick actions -->

    <!-- Recent Activity & Latest Companies -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Nejnovější společnosti</h4>
                    <p class="text-muted font-13 mb-4">
                        Přehled posledních přidaných společností do CRM systému.
                    </p>

                    @php
                        $latestCompanies = \App\Models\Company::latest()->take(5)->get();
                    @endphp

                    @if($latestCompanies->count() > 0)
                        @foreach($latestCompanies as $company)
                        <div class="d-flex mb-3">
                            <div class="avatar-sm bg-soft-primary rounded me-3">
                                <span class="avatar-title text-primary font-20">
                                    <i class="ri-building-line"></i>
                                </span>
                            </div>
                            <div class="w-100">
                                <h5 class="mt-0 mb-1">
                                    <a href="{{ route('companies.show', $company) }}" class="text-dark">{{ $company->name }}</a>
                                </h5>
                                <span class="font-13 text-muted">
                                    @if($company->industry)
                                        {{ $company->industry }} • 
                                    @endif
                                    {{ $company->created_at->diffForHumans() }}
                                </span>
                                @if($company->status)
                                    @switch($company->status)
                                        @case('active')
                                            <span class="badge bg-success ms-1">Aktivní</span>
                                            @break
                                        @case('prospect')
                                            <span class="badge bg-warning ms-1">Prospect</span>
                                            @break
                                        @case('inactive')
                                            <span class="badge bg-secondary ms-1">Neaktivní</span>
                                            @break
                                    @endswitch
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center">
                            <div class="avatar-lg bg-soft-warning rounded-circle mx-auto">
                                <i class="ri-building-line avatar-title font-22 text-warning"></i>
                            </div>
                            <h5 class="mt-3">Žádné společnosti</h5>
                            <p class="text-muted">Zatím nebyly přidány žádné společnosti.</p>
                            <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
                                <i class="ri-add-line me-1"></i> Přidat první společnost
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Nadcházející úkoly</h4>
                    <p class="text-muted font-13 mb-4">
                        Přehled úkolů a aktivit na následující dny.
                    </p>

                    @php
                        $upcomingTasks = \App\Models\Task::where('due_date', '>=', now())
                                                         ->orderBy('due_date', 'asc')
                                                         ->take(5)
                                                         ->get();
                    @endphp

                    @if($upcomingTasks->count() > 0)
                        @foreach($upcomingTasks as $task)
                        <div class="d-flex mb-3">
                            <div class="avatar-sm bg-soft-info rounded me-3">
                                <span class="avatar-title text-info font-20">
                                    <i class="ri-task-line"></i>
                                </span>
                            </div>
                            <div class="w-100">
                                <h5 class="mt-0 mb-1">{{ $task->title }}</h5>
                                <span class="font-13 text-muted">
                                    Termín: {{ $task->due_date->format('d.m.Y H:i') }}
                                    @if($task->priority)
                                        @switch($task->priority)
                                            @case('high')
                                                <span class="badge bg-danger ms-1">Vysoká</span>
                                                @break
                                            @case('medium')
                                                <span class="badge bg-warning ms-1">Střední</span>
                                                @break
                                            @case('low')
                                                <span class="badge bg-info ms-1">Nízká</span>
                                                @break
                                        @endswitch
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center">
                            <div class="avatar-lg bg-soft-success rounded-circle mx-auto">
                                <i class="ri-task-line avatar-title font-22 text-success"></i>
                            </div>
                            <h5 class="mt-3">Žádné úkoly</h5>
                            <p class="text-muted">Momentálně nemáte žádné nadcházející úkoly.</p>
                            <button class="btn btn-success btn-sm">
                                <i class="ri-add-line me-1"></i> Přidat úkol
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

</div> <!-- container -->
@endsection

@section('script')
<!-- Chart.js -->
<script src="{{ asset('libs/chart.js/Chart.min.js') }}"></script>

<!-- Counter Up JS -->
<script>
// Counter animation
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('[data-plugin="counterup"]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.innerText);
        let count = 0;
        const increment = target / 100;
        
        const updateCounter = () => {
            if (count < target) {
                count += increment;
                counter.innerText = Math.ceil(count);
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }
        };
        
        updateCounter();
    });
});
</script>
@endsection

@push('scripts')
<script>
(function(){
    const el = document.querySelector('#contacts-over-time-chart');
    if (!el) return;
    const labels = (el.getAttribute('data-labels') ? JSON.parse(el.getAttribute('data-labels')) : []);
    const seriesData = (el.getAttribute('data-series') ? JSON.parse(el.getAttribute('data-series')) : []);
    const colorsAttr = el.getAttribute('data-colors') || '#10c469,#35b8e0';
    const colors = colorsAttr.split(',');

    function render() {
        const options = {
            series: [{ name: 'Nové kontakty', data: (seriesData || []).map(v => Number(v)||0) }],
            chart: { type: 'line', height: 299, zoom: { enabled: false }, toolbar: { show: false } },
            stroke: { width: 3, curve: 'straight' },
            dataLabels: { enabled: false },
            xaxis: { categories: labels || [] },
            colors: colors,
            tooltip: { shared: true, y: [{ formatter: function (y){ return (typeof y !== 'undefined') ? (Math.round(y)+' ks') : y; } }] }
        };
        const chart = new window.ApexCharts(el, options);
        chart.render();
    }

    if (window.ApexCharts) { render(); }
    else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
        s.onload = render;
        document.head.appendChild(s);
    }
})();
</script>
@endpush
