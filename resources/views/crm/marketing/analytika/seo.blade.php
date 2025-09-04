@extends('layouts.vertical')
@section('title', 'Marketing – SEO přehled')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – SEO přehled'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">SEO přehled (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Napojení na Google Search Console (mock)</div>
    </div></div>
</div>
@endsection
