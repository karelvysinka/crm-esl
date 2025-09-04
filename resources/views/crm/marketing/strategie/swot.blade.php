@extends('layouts.vertical')
@section('title', 'Marketing – SWOT a konkurence')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – SWOT & konkurence'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">SWOT & konkurence (preview)</h4>
        <div class="row g-3">
            <div class="col-md-6"><div class="bg-light rounded p-5 text-center text-muted">SWOT mřížka (mock)</div></div>
            <div class="col-md-6"><div class="bg-light rounded p-5 text-center text-muted">Evidence konkurence (mock)</div></div>
        </div>
    </div></div>
</div>
@endsection
