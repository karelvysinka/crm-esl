@extends('layouts.vertical', ['title' => 'Grid Js Tables'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0 flex-grow-1">Base Example</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-gridjs"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Pagination</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-pagination"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Search</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-search"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Sorting</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-sorting"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Loading State</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-loading-state"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Fixed Header</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-fixed-header"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-bottom border-dashed">
                <h4 class="card-title mb-0">Hidden Columns</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <div id="table-hidden-column"></div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

@endsection

@section('scripts')
@vite(['resources/js/components/table-gridjs.js'])
@endsection