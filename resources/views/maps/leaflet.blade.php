@extends('layouts.vertical  ', ['title' => 'Leaflet Maps', 'topbarTitle' => 'Leaflet Maps'])

@section('css')
@vite(['node_modules/leaflet/dist/leaflet.css'])
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Basic Map</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="basicMap"></div>
            </div>
        </div> <!-- end card-->
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Marker Circle & Polygon</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="shapeMap"></div>
            </div>
        </div> <!-- end card-->
    </div>



    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Draggable Marker With Popup</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="dragMap"></div>
            </div>
        </div> <!-- end card-->
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">User Location</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="userLocation"></div>
            </div>
        </div> <!-- end card-->
    </div>


    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Custom Icons</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="customIcons"></div>
            </div>
        </div> <!-- end card-->
    </div>


    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Layer Control</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="layerControl"></div>
            </div>
        </div> <!-- end card-->
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed d-flex align-items-center">
                <h4 class="header-title">Interactive Choropleth Map</h4>
            </div>

            <div class="card-body">
                <div class="leaflet-map" id="geoJson"></div>
            </div>
        </div> <!-- end card-->
    </div>



</div>

@endsection

@section('scripts')
@vite(['resources/js/components/maps-leaflet.js', 'resources/js/maps/leaflet-data.js'])
@endsection