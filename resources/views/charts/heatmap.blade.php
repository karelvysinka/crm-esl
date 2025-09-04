@extends('layouts.vertical', ['title' => 'Apex Heatmap Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Basic Heatmap - Single Series</h4>
                <div dir="ltr">
                    <div id="basic-heatmap" class="apex-charts" data-colors="#5b69bc"></div>
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
                <h4 class="header-title">Heatmap - Multiple Series</h4>
                <div dir="ltr">
                    <div id="multiple-series-heatmap" class="apex-charts"
                        data-colors="#F3B415,#F27036,#663F59,#6A6E94,#4E88B4,#00A7C6,#18D8D8,#A9D794,#46AF78">
                    </div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <!-- end col-->
</div>
<!-- end row-->

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Heatmap - Color Range</h4>
                <div dir="ltr">
                    <div id="color-range-heatmap" class="apex-charts"
                        data-colors="#fa5c7c,#f9c851,#39afd1,#10c469"></div>
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
                <h4 class="header-title mb-3">Heatmap - Range without Shades</h4>
                <div dir="ltr">
                    <div id="rounded-heatmap" class="apex-charts" data-colors="#10c469,#f9c851"></div>
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
@vite(['resources/js/components/chart-apex-heatmap.js'])
@endsection