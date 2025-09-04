@extends('layouts.vertical', ['title' => 'Apex Candlestick Charts', 'topbarTitle' => 'Apex Candlestick Charts'])

@section('css')
@endsection

@section('content')

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Simple Candlestick Chart</h4>
                <div dir="ltr">
                    <div id="simple-candlestick" class="apex-charts" data-colors="#10c469,#fa5c7c">
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
                <h4 class="header-title">Combo Candlestick Chart</h4>
                <div dir="ltr">
                    <div id="combo-candlestick" class="apex-charts" data-colors="#10c469,#fa5c7c"></div>
                    <div id="combo-bar-candlestick" class="apex-charts" data-colors="#5b69bc,#f9c851">
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
                <h4 class="header-title mb-3">Category X-Axis</h4>
                <div dir="ltr">
                    <div id="x-axis-candlestick" class="apex-charts"
                        data-colors="#10c469,#fa5c7c,#5b69bc"></div>
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
                <h4 class="header-title mb-3">Candlestick with Line</h4>
                <div dir="ltr">
                    <div id="candlestick-with-line" class="apex-charts"
                        data-colors="#5b69bc,#10c469,#fa5c7c"></div>
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
<script src="https://apexcharts.com/samples/assets/ohlc.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.17/dayjs.min.js"></script>
@vite(['resources/js/components/chart-apex-candlestick.js'])
@endsection