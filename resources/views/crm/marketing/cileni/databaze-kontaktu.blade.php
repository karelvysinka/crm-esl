@extends('layouts.vertical')
@section('title', 'Marketing – Databáze kontaktů')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Databáze kontaktů'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Databáze kontaktů (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Fulltext, historie interakcí (mock)</div>
    </div></div>
</div>
@endsection
