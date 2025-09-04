<!DOCTYPE html>
<html @yield('html_attribute')>

<head>
    @include('layouts.partials/title-meta', ['title' => $title])

    @include('layouts.partials/head-css')
</head>

<body @yield('body_attribute')>

    @yield('content')

    @include('layouts.partials/footer-scripts')

</body>

</html>
