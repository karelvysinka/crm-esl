@extends('layouts.vertical', ['title' => 'Položky objednávky #' . ($order->external_order_no ?? $order->id)])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        @if($order->company)
                        <li class="breadcrumb-item"><a href="{{ url('/crm/companies/' . $order->company_id) }}">{{ $order->company->name }}</a></li>
                        @endif
                        <li class="breadcrumb-item active">Objednávka {{ $order->external_order_no ?? ('#' . $order->id) }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Položky objednávky</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ri-file-list-3-line me-2"></i>
                Objednávka {{ $order->external_order_no ?? ('#' . $order->id) }}
            </h5>
            <a class="btn btn-outline-secondary btn-sm" href="{{ url()->previous() }}">Zpět</a>
        </div>
        <div class="card-body">
            @include('crm.orders._items', ['order' => $order, 'items' => $items])
        </div>
    </div>
</div>
@endsection
