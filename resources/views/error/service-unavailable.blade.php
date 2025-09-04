@extends('layouts.base', ['title' => 'Error 408'])

@section('body_attribute')
class="h-100"
@endsection

@section('content')

<div class="auth-bg d-flex min-vh-100">
    <div class="row g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
        <div class="col-xxl-3 col-lg-5 col-md-6">
            <a href="{{ route('any', ['index'])}}" class="auth-brand d-flex justify-content-center mb-2">
                <img src="/images/logo-dark.png" alt="dark logo" height="26" class="logo-dark">
                <img src="/images/logo.png" alt="logo light" height="26" class="logo-light">
            </a>

            <h4 class="fw-semibold mb-4 text-center fs-15">CRM rozhraní</h4>

            <div class="card overflow-hidden text-center p-xxl-4 p-3 mb-0">

                <div class="text-center">
                    <h4 class="text-error fs-36">Služba nedostupná</h4>
                    <h3 class="my-2">Web je dočasně mimo provoz kvůli údržbě.</h3>
                    <p class="text-muted mb-3">Server momentálně nemůže vyřídit požadavek z důvodu přetížení nebo údržby. Zkuste to prosím později.</p>
                    <a href="{{ route('crm.dashboard') }}" class="btn btn-danger">
                        <i class="ti ti-home fs-16 me-1"></i> Zpět na CRM Dashboard
                    </a>
                </div>

            </div>
            <p class="mt-4 text-center mb-0">
                <script>
                    document.write(new Date().getFullYear())
                </script> © E S L a.s.
            </p>
        </div>
    </div>
</div>

@endsection

@section('scripts')

@endsection