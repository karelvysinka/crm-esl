@extends('layouts.vertical')
@section('title', 'Marketing – Knihovna obsahu')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Knihovna obsahu'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Knihovna obsahu (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Seznam a grid assetů, AI generátor (mock)</div>
    </div></div>
</div>
@endsection
