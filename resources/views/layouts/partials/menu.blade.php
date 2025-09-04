<!-- Menu -->
@include('layouts.partials/sidenav')

@if (isset($topbarTitle))
@include('layouts.partials/topbar', ['topbarTitle' => $topbarTitle])

@else
@include('layouts.partials/topbar')
@endif

