@extends('layouts.vertical')
@section('title', 'Marketing – Kalendář')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Kalendář'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Marketingový kalendář (preview)</h4>
        <p class="text-muted">Interaktivní pohled měsíc/týden/den, drag & drop.</p>
        <div class="bg-light rounded p-5 text-center text-muted">Placeholder pro kalendářní komponentu</div>
    </div></div>
</div>
@endsection
