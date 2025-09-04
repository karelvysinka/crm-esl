@extends('layouts.vertical')
@section('title', 'Marketing – Atribuce konverzí')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Atribuce konverzí'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Atribuce konverzí (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Modely last click, linear, time decay (mock)</div>
    </div></div>
</div>
@endsection
