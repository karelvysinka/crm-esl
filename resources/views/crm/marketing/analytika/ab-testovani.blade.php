@extends('layouts.vertical')
@section('title', 'Marketing – AB testování')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – AB testování'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">A/B testování (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Evidence testů, výsledky, doporučení (mock)</div>
    </div></div>
</div>
@endsection
