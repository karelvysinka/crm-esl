@extends('layouts.base', ['title' => 'Přihlášení'])

@section('css')
@endsection

@section('content')

<div class="auth-bg d-flex min-vh-100">
    <div class="row g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
        <div class="col-xxl-3 col-lg-5 col-md-6">
            <a href="{{ route('any', ['index'])}}" class="auth-brand d-flex justify-content-center mb-2">
                <img src="{{ asset('images/logo-dark.png') }}" alt="dark logo" height="26" class="logo-dark">
                <img src="/images/logo.png" alt="logo light" height="26" class="logo-light">
            </a>

            <p class="fw-semibold mb-4 text-center text-muted fs-15">E S L a.s.</p>

            <div class="card overflow-hidden text-center p-xxl-4 p-3 mb-0">

                <h4 class="fw-semibold mb-3 fs-18">Přihlášení do účtu</h4>

                <form action="{{ route('login.attempt') }}" method="POST" class="text-start mb-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="example-email">E-mail</label>
                        <input type="email" id="example-email" name="email" class="form-control"
                            placeholder="Zadejte svůj e-mail">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="example-password">Heslo</label>
                        <input type="password" id="example-password" name="password" class="form-control"
                            placeholder="Zadejte své heslo">
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="checkbox-signin" name="remember" value="1">
                            <label class="form-check-label" for="checkbox-signin">Zapamatovat</label>
                        </div>

                        <a href="{{ route ('second' , ['auth','recoverpw']) }}" class="text-muted border-bottom border-dashed">Zapomněli jste heslo?</a>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary fw-semibold" type="submit">Přihlásit</button>
                    </div>
                </form>

                <p class="text-muted fs-14 mb-0">Nemáte účet?
                    <a href="{{ route ('second' , ['auth','register']) }}" class="fw-semibold text-danger ms-1">Zaregistrovat</a>
                </p>

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
