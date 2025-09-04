@extends('layouts.vertical')

@section('title', 'Nová společnost')

@section('css')
<!-- Select2 css -->
<link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Bootstrap Select css -->
<link href="{{ asset('libs/bootstrap-select/css/bootstrap-select.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('crm.dashboard') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Společnosti</a></li>
                        <li class="breadcrumb-item active">Nová společnost</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    Nová společnost
                    <a href="{{ route('companies.index') }}" class="btn btn-light btn-sm ms-2">
                        <i class="ri-arrow-left-line me-1"></i> Zpět na seznam
                    </a>
                </h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Základní informace</h4>
                    <p class="text-muted font-13 mb-4">
                        Vyplňte základní údaje o nové společnosti. Povinná pole jsou označena hvězdičkou (*).
                    </p>

                    <form action="{{ route('companies.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        
                        <div class="row">
                            <!-- Název společnosti -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Název společnosti <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" placeholder="Název společnosti" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="valid-feedback">
                                        Vypadá dobře!
                                    </div>
                                </div>
                            </div>

                            <!-- Odvětví -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="industry" class="form-label">Odvětví</label>
                                    <input type="text" id="industry" name="industry" class="form-control @error('industry') is-invalid @enderror" 
                                           value="{{ old('industry') }}" placeholder="Např. Information Technology, Marketing">
                                    @error('industry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Velikost společnosti -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="size" class="form-label">Velikost společnosti <span class="text-danger">*</span></label>
                                    <select id="size" name="size" class="form-select @error('size') is-invalid @enderror" required>
                                        <option value="">Vyberte velikost</option>
                                        <option value="startup" {{ old('size') == 'startup' ? 'selected' : '' }}>Startup (1-10 zaměstnanců)</option>
                                        <option value="small" {{ old('size') == 'small' ? 'selected' : '' }}>Malá (11-50 zaměstnanců)</option>
                                        <option value="medium" {{ old('size') == 'medium' ? 'selected' : '' }}>Střední (51-250 zaměstnanců)</option>
                                        <option value="large" {{ old('size') == 'large' ? 'selected' : '' }}>Velká (251-1000 zaměstnanců)</option>
                                        <option value="enterprise" {{ old('size') == 'enterprise' ? 'selected' : '' }}>Enterprise (1000+ zaměstnanců)</option>
                                    </select>
                                    @error('size')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="">Vyberte status</option>
                                        <option value="prospect" {{ old('status') == 'prospect' ? 'selected' : '' }}>Prospect</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktivní</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Neaktivní</option>
                                        <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Ztracená</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Země -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Země <span class="text-danger">*</span></label>
                                    <input type="text" id="country" name="country" class="form-control @error('country') is-invalid @enderror" 
                                           value="{{ old('country', 'Česká republika') }}" placeholder="Země" required>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Kontaktní údaje</h5>

                        <div class="row">
                            <!-- Website -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-global-line"></i></span>
                                        <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror" 
                                               value="{{ old('website') }}" placeholder="https://www.example.com">
                                    </div>
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-mail-line"></i></span>
                                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                               value="{{ old('email') }}" placeholder="info@company.com">
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Telefon -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-phone-line"></i></span>
                                        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                               value="{{ old('phone') }}" placeholder="+420 123 456 789">
                                    </div>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Město -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Město</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-map-pin-line"></i></span>
                                        <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" 
                                               value="{{ old('city') }}" placeholder="Praha">
                                    </div>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Adresa -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresa</label>
                                    <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" 
                                              rows="2" placeholder="Úplná adresa společnosti">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Další údaje</h5>

                        <div class="row">
                            <!-- Roční tržby -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="annual_revenue" class="form-label">Roční tržby (CZK)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-money-dollar-circle-line"></i></span>
                                        <input type="number" id="annual_revenue" name="annual_revenue" class="form-control @error('annual_revenue') is-invalid @enderror" 
                                               value="{{ old('annual_revenue') }}" placeholder="5000000" min="0">
                                    </div>
                                    @error('annual_revenue')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Počet zaměstnanců -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_count" class="form-label">Počet zaměstnanců</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-group-line"></i></span>
                                        <input type="number" id="employee_count" name="employee_count" class="form-control @error('employee_count') is-invalid @enderror" 
                                               value="{{ old('employee_count') }}" placeholder="25" min="0">
                                    </div>
                                    @error('employee_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Poznámky -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Poznámky</label>
                                    <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" 
                                              rows="4" placeholder="Interní poznámky o společnosti...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tlačítka -->
                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ route('companies.index') }}" class="btn btn-light me-2">
                                        <i class="ri-close-line me-1"></i> Zrušit
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i> Uložit společnost
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>

                </div> <!-- end card body-->
            </div> <!-- end card -->
        </div><!-- end col-->
    </div>
    <!-- end row-->

</div> <!-- container -->
@endsection

@section('script')
<!-- Bootstrap Validation -->
<script>
    // Bootstrap form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
@endsection
