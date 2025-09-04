@extends('layouts.vertical')
@section('title', 'Marketing – Role & práva')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Role & práva'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Přístupová práva a role (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">RBAC pomocí spatie/laravel-permission (mock)</div>
    </div></div>
</div>
@endsection
