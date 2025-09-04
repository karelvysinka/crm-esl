@extends('layouts.vertical', ['title' => 'Apex Boxplot Chart', 'topbarTitle' => 'Apex Boxplot Chart'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-4">Basic Boxplot</h4>
                <div dir="ltr">
                    <div id="basic-boxplot" class="apex-charts" data-colors="#5b69bc,#10c469"></div>
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
                <h4 class="header-title mb-4">Scatter Boxplot </h4>
                <div dir="ltr">
                    <div id="scatter-boxplot" class="apex-charts" data-colors="#fa5c7c,#35b8e0"></div>
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
                <h4 class="header-title mb-4">Horizontal BoxPlot</h4>
                <div dir="ltr">
                    <div id="horizontal-boxplot" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#e3eaef"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <!-- end col-->
</div>
<!-- end row-->

@endsection

@section('scripts')
@vite(['resources/js/components/chart-apex-boxplot.js'])
@endsection