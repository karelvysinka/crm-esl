@extends('layouts.vertical', ['title' => 'Apex Pie Charts', 'topbarTitle' => 'Apex Pie Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Simple Pie Chart</h4>
                <div dir="ltr">
                    <div id="simple-pie" class="apex-charts"
                        data-colors="#5b69bc,#35b8e0,#10c469,#fa5c7c,#e3eaef"></div>
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
                <h4 class="header-title">Simple Donut Chart</h4>
                <div dir="ltr">
                    <div id="simple-donut" class="apex-charts"
                        data-colors="#39afd1,#f9c851,#313a46,#fa5c7c,#10c469"></div>
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
                <h4 class="header-title mb-4">Monochrome Pie Chart</h4>
                <div dir="ltr">
                    <div id="monochrome-pie" class="apex-charts"></div>
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
                <h4 class="header-title mb-4">Gradient Donut Chart</h4>
                <div dir="ltr">
                    <div id="gradient-donut" class="apex-charts"
                        data-colors="#5b69bc,#35b8e0,#10c469,#fa5c7c,#e3eaef"></div>
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
                <h4 class="header-title mb-4">Patterned Donut Chart</h4>
                <div dir="ltr">
                    <div id="patterned-donut" class="apex-charts"
                        data-colors="#39afd1,#f9c851,#313a46,#fa5c7c,#10c469"></div>
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
                <h4 class="header-title mb-4">Pie Chart with Image fill</h4>
                <div dir="ltr">
                    <div id="image-pie" class="apex-charts"
                        data-colors="#39afd1,#f9c851,#5b69bc,#10c469"></div>
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
                <h4 class="header-title mb-4">Donut Update</h4>
                <div dir="ltr">
                    <div id="update-donut" class="apex-charts"
                        data-colors="#5b69bc,#35b8e0,#10c469,#fa5c7c"></div>
                </div>

                <div class="text-center mt-2">
                    <button class="btn btn-sm btn-primary" id="randomize">RANDOMIZE</button>
                    <button class="btn btn-sm btn-primary" id="add">ADD</button>
                    <button class="btn btn-sm btn-primary" id="remove">REMOVE</button>
                    <button class="btn btn-sm btn-primary" id="reset">RESET</button>
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
@vite(['resources/js/components/chart-apex-pie.js'])
@endsection