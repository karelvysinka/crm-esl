@extends('layouts.vertical', ['title' => 'Apex Timeline Chart', 'topbarTitle' => 'Timeline Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Basic Timeline</h4>
                <div dir="ltr">
                    <div id="basic-timeline" class="apex-charts" data-colors="#ff5b5b"></div>
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
                <h4 class="header-title mb-3">Distributed Timeline </h4>
                <div dir="ltr">
                    <div id="distributed-timeline" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#fa5c7c,#35b8e0,#39afd1"></div>
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
                <h4 class="header-title mb-3">Multi Series Timeline</h4>

                <div dir="ltr">
                    <div id="multi-series-timeline" class="apex-charts" data-colors="#35b8e0,#39afd1">
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
                <h4 class="header-title mb-3">Advanced Timeline</h4>
                <div dir="ltr">
                    <div id="advanced-timeline" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#fa5c7c"></div>
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
                <h4 class="header-title mb-3">Multiple Series - Group Rows</h4>
                <div dir="ltr">
                    <div id="group-rows-timeline" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#fa5c7c,#35b8e0,#39afd1,#ffc35a, #eef2f7, #313a46,#3577f1, #0ab39c, #f0a548,#68eaff">
                    </div>
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
@vite(['resources/js/components/chart-apex-timeline.js'])
@endsection