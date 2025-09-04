@extends('layouts.vertical', ['title' => 'Apex Polar Area Charts', 'topbarTitle' => 'Apex Polar Area Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Basic Polar Area Chart</h4>
                <div dir="ltr">
                    <div id="basic-polar-area" class="apex-charts"
                        data-colors="#5b69bc,#35b8e0,#10c469,#fa5c7c,#f9c851,#39afd1"></div>
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
                <h4 class="header-title">Monochrome Polar Area</h4>
                <div dir="ltr">
                    <div id="monochrome-polar-area" class="apex-charts" data-colors="#35b8e0"></div>
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
@vite(['resources/js/components/chart-apex-polar-area.js'])
@endsection