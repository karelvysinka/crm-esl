@extends('layouts.vertical')
@section('title', 'Marketing – Segmentace')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Segmentace'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Segmentace (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Tvorba segmentů dle chování a demografie (mock)</div>
    </div></div>
</div>
@endsection
