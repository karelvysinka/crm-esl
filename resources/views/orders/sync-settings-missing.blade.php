@extends('layouts.vertical', ['page_title' => 'Objednávky - Nastavení synchronizace'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Objednávky</a></li>
                        <li class="breadcrumb-item active">Nastavení synchronizace</li>
                    </ol>
                </div>
                <h4 class="page-title">Objednávky – Nastavení synchronizace</h4>
            </div>
        </div>
    </div>

    <div class="alert alert-warning">
        <strong>Tabulky pro nastavení synchronizace nejsou dostupné.</strong><br>
        Spusťte prosím migrace: <code>php artisan migrate</code> a obnovte stránku.
    </div>
</div>
@endsection
