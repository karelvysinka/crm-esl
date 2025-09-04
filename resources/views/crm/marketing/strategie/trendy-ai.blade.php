@extends('layouts.vertical')
@section('title', 'Marketing – Trendy & AI predikce')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Trendy & AI'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Analýza trendů & AI predikce (preview)</h4>
        <div class="row g-3">
            <div class="col-md-6"><div class="bg-light rounded p-4 text-center text-muted">Google Trends (mock)</div></div>
            <div class="col-md-6"><div class="bg-light rounded p-4 text-center text-muted">Predikce poptávky (mock)</div></div>
        </div>
    </div></div>
</div>
@endsection
