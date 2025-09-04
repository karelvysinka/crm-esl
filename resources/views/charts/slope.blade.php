@extends('layouts.vertical', ['title' => 'Apex Slope Charts'])

@section('css')
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Apex', 'subtitle' => 'Slope Charts'])

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-4">Basic Slope Chart</h4>
                <div dir="ltr">
                    <div id="slope-basic" class="apex-charts" data-colors="#39afd1"></div>
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
                <h4 class="header-title mb-4">Multiple Slop Chart</h4>
                <div dir="ltr">
                    <div id="slope-mluti-group" class="apex-charts"
                        data-colors="#45bbe0,#f9bc0b,#777edd,#0acf97"></div>
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
@vite(['resources/js/components/chart-apex-slope.js'])
@endsection