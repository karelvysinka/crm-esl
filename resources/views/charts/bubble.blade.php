@extends('layouts.vertical', ['title' => 'Apex Bubble Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Simple Bubble Chart</h4>
                <div dir="ltr">
                    <div id="simple-bubble" class="apex-charts" data-colors="#5b69bc,#f9c851,#fa5c7c">
                    </div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <!-- end col-->

    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">3D Bubble Chart</h4>
                <div dir="ltr">
                    <div id="second-bubble" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#fa5c7c,#39afd1"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <!-- end col-->
</div>

@endsection

@section('scripts')
@vite(['resources/js/components/chart-apex-bubble.js'])
@endsection