@yield('css')

@vite(['resources/scss/app.scss', 'resources/scss/icons.scss'])
@vite(['resources/js/config.js'])
<style>[x-cloak]{display:none!important}</style>
<style>
/* === Globální CRM unifikace vzhledu (rámečky + černý text) === */
/* Text */
.wrapper, .wrapper h1, .wrapper h2, .wrapper h3, .wrapper h4, .wrapper h5, .wrapper h6,
.wrapper .page-title, .wrapper .header-title, .wrapper label, .wrapper p, .wrapper span,
.wrapper table td, .wrapper table th { color:#000 !important; }

/* Odstíny, které dříve byly šedé */
.wrapper .text-muted { color:#000 !important; opacity:1 !important; }

/* Breadcrumb odkazy */
.wrapper .page-title-box .breadcrumb-item a { color:#000 !important; }

/* ApexCharts texty */
.wrapper .apexcharts-legend-text,
.wrapper .apexcharts-xaxis-texts-g text,
.wrapper .apexcharts-yaxis-texts-g text { fill:#000 !important; }

/* Karty – jednotný rámeček a čistý vzhled */
.wrapper .card { border:1px solid #d7dadd; box-shadow:none; }
.wrapper .card:hover { box-shadow:0 6px 18px rgba(0,0,0,.10); }

/* KPI popisky (pokud existují) */
.wrapper .kpi-label { font-weight:600; color:#000 !important; }
.wrapper .kpi-meta { font-weight:500; color:#000 !important; opacity:.85; }

/* Sekční nadpisy obecně (ponecháme individuální třídy) */
.wrapper .section-heading { color:#000; border-left:4px solid #000; }

/* Tabulky */
.wrapper table thead th { font-weight:600; }

/* Utility */
.wrapper [data-plugin="counterup"] { letter-spacing:.5px; }

/* Zachování kontrastu ikon na barevných pozadích avatarů */
.wrapper .avatar-sm svg { stroke-width:1.4; }
</style>