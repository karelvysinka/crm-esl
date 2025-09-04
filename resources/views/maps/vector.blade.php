@extends('layouts.vertical', ['title' => 'Vector Maps', 'topbarTitle' => 'Vector Maps'])

@section('css')
@vite(['node_modules/jsvectormap/dist/jsvectormap.min.css'])
@endsection

@section('content')

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">World Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="world-map-markers" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">World Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="world-map-markers-line" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">India Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="india-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">Canada Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="canada-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>


    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">Russia Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="russia-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>


    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">US Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="usa-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>


    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">Iraq Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="iraq-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 border-bottom border-dashed">
                <h4 class="header-title">Spain Vector Map</h4>
            </div>
            <div class="card-body">
                <div id="spain-vector-map" style="height: 360px"></div>
            </div> <!-- end card-body-->
        </div> <!-- card end -->
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/components/maps-vector.js', 'resources/js/maps/in-mill-en.js', 'resources/js/maps/canada.js', 'resources/js/maps/iraq.js', 'resources/js/maps/russia.js', 'resources/js/maps/spain.js','resources/js/maps/us-aea-en.js','resources/js/maps/us-lcc-en.js','resources/js/maps/us-mill-en.js'])
@endsection