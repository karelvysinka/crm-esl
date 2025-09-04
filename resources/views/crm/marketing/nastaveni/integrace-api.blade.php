@extends('layouts.vertical')
@section('title', 'Marketing – Integrace & API')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Integrace & API'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Integrace & API (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">OAuth2, webhooky, import/export (mock)</div>
    </div></div>
</div>
@endsection
