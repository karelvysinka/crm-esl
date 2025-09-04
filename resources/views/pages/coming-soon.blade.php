@extends('layouts.base', ['title' => 'Coming Soon'])

@section('css')
@endsection

@section('body_attribute')
class="h-100"
@endsection

@section('content')

<div class="auth-bg d-flex min-vh-100 justify-content-center align-items-center">
    <div class="row g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
        <div class="col-xl-4 col-lg-5 col-md-6">
            <div class="card overflow-hidden text-center h-100 p-xxl-4 p-3 mb-0">
                <a href="{{ route('any', ['index'])}}" class="auth-brand mb-3">
                    <img src="/images/logo-dark.png" alt="dark logo" height="26" class="logo-dark">
                    <img src="/images/logo.png" alt="logo light" height="26" class="logo-light">
                </a>

                <div>
                    <h3 class="fw-semibold mb-2">Stay tunned, we're launching very soon</h3>
                    <p class="text-muted mb-0">We're making the system more awesome.</p>
                </div>

                <div class="row text-center justify-content-center my-4 g-2">
                    <div class="col-6 col-sm-4 col-md-3 col-lg">
                        <div class="bg-body-secondary border border-primary-subtle border-dashed p-2 rounded">
                            <h3 id="days" class="fw-bold text-primary fs-35">00</h3>
                            <p class="fw-semibold fs-12 mb-0">Days</p>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3 col-lg">
                        <div class="bg-body-secondary border border-primary-subtle border-dashed p-2 rounded">
                            <h3 id="hours" class="fw-bold text-primary fs-35">00</h3>
                            <p class="fw-semibold fs-12 mb-0">Hours</p>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3 col-lg">
                        <div class="bg-body-secondary border border-primary-subtle border-dashed p-2 rounded">
                            <h3 id="minutes" class="fw-bold text-primary fs-35">00</h3>
                            <p class="fw-semibold fs-12 mb-0">Minutes</p>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3 col-lg">
                        <div class="bg-body-secondary border border-primary-subtle border-dashed p-2 rounded">
                            <h3 id="seconds" class="fw-bold text-primary fs-35">00</h3>
                            <p class="fw-semibold fs-12 mb-0">Seconds</p>
                        </div>
                    </div>
                </div>


                <p class="mt-3 mb-0">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © Adminto - By <span class="fw-bold text-decoration-underline text-uppercase text-reset fs-12">Coderthemes</span>
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/pages/coming-soon.js'])
@endsection