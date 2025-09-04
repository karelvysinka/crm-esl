@extends('layouts.vertical')
@section('title', 'Marketing – Landing Pages Builder')
@section('content')
<div class="container-fluid">
    @include('layouts.partials.breadcrumb', ['title' => 'Marketing – Landing Pages'])
    <div class="card"><div class="card-body">
        <h4 class="header-title">Landing Pages Builder (preview)</h4>
        <div class="bg-light rounded p-5 text-center text-muted">Drag & drop builder, AB testy (mock)</div>
    </div></div>
</div>
@endsection
