@extends('layouts.vertical', ['title' => 'Apex Mixed Charts', 'topbarTitle' => 'Apex Mixed Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Line & Column Chart</h4>
                <div dir="ltr">
                    <div id="line-column-mixed" class="apex-charts" data-colors="#5b69bc,#10c469"></div>
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
                <h4 class="header-title">Multiple Y-Axis Chart</h4>
                <div dir="ltr">
                    <div id="multiple-yaxis-mixed" class="apex-charts"
                        data-colors="#5b69bc,#39afd1,#fa5c7c"></div>
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
                <h4 class="header-title">Line & Area Chart</h4>
                <div dir="ltr">
                    <div id="line-area-mixed" class="apex-charts" data-colors="#10c469,#fa5c7c"></div>
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
                <h4 class="header-title">Line, Column & Area Chart</h4>
                <div dir="ltr">
                    <div id="all-mixed" class="apex-charts" data-colors="#5b69bc,#39afd1,#fa5c7c"></div>
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
@vite(['resources/js/components/chart-apex-mixed.js'])
@endsection