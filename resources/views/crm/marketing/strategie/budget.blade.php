@extends('layouts.vertical')
@section('title', 'Marketing – Správa Budgetu')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Správa Budgetu'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Správa Budgetu (preview)</h4>
        <div class="row g-3">
            <div class="col-md-6"><div class="bg-light rounded p-4 text-center text-muted">Graf čerpání (mock)</div></div>
            <div class="col-md-6"><div class="bg-light rounded p-4 text-center text-muted">Tabulka kanálů (mock)</div></div>
        </div>
    </div></div>
</div>
@endsection
