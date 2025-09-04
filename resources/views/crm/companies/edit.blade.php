@extends('layouts.vertical', ['title' => 'Upravit společnost'])

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/companies') }}">Společnosti</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/companies/' . $company->id) }}">{{ $company->name }}</a></li>
                        <li class="breadcrumb-item active">Upravit</li>
                    </ol>
                </div>
                <h4 class="page-title">Upravit společnost</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-edit-line me-2"></i>
                        Upravit informace o společnosti
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="ri-information-line me-2"></i>
                        Upravte údaje o společnosti. Povinná pole jsou označena hvězdičkou (*).
                    </div>

                    <form action="{{ url('/crm/companies/' . $company->id) }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        
                        <!-- Základní informace -->
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="ri-building-line me-2"></i>
                            Základní informace
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">
                                    Název společnosti <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $company->name) }}" 
                                       required 
                                       placeholder="Název společnosti">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">
                                    Zadejte název společnosti.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="industry" class="form-label">Odvětví</label>
                                <input type="text" 
                                       class="form-control @error('industry') is-invalid @enderror" 
                                       id="industry" 
                                       name="industry" 
                                       value="{{ old('industry', $company->industry) }}" 
                                       placeholder="Např. Information Technology, Marketing">
                                @error('industry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="size" class="form-label">
                                    Velikost společnosti <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('size') is-invalid @enderror" 
                                        id="size" 
                                        name="size" 
                                        required>
                                    <option value="">Vyberte velikost</option>
                                    <option value="small" {{ (old('size', $company->size) == 'small') ? 'selected' : '' }}>Malá (1-50 zaměstnanců)</option>
                                    <option value="medium" {{ (old('size', $company->size) == 'medium') ? 'selected' : '' }}>Střední (51-250 zaměstnanců)</option>
                                    <option value="large" {{ (old('size', $company->size) == 'large') ? 'selected' : '' }}>Velká (250+ zaměstnanců)</option>
                                </select>
                                @error('size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">
                                    Vyberte velikost společnosti.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status" 
                                        required>
                                    <option value="">Vyberte status</option>
                                    <option value="active" {{ (old('status', $company->status) == 'active') ? 'selected' : '' }}>Aktivní</option>
                                    <option value="inactive" {{ (old('status', $company->status) == 'inactive') ? 'selected' : '' }}>Neaktivní</option>
                                    <option value="prospect" {{ (old('status', $company->status) == 'prospect') ? 'selected' : '' }}>Prospect</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">
                                    Vyberte status společnosti.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="country" class="form-label">
                                    Země <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('country') is-invalid @enderror" 
                                       id="country" 
                                       name="country" 
                                       value="{{ old('country', $company->country) }}" 
                                       required 
                                       placeholder="Česká republika">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">
                                    Zadejte zemi.
                                </div>
                            </div>
                        </div>

                        <!-- Kontaktní údaje -->
                        <h6 class="fw-bold text-primary mb-3 mt-4">
                            <i class="ri-contacts-line me-2"></i>
                            Kontaktní údaje
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-global-line"></i></span>
                                    <input type="url" 
                                           class="form-control @error('website') is-invalid @enderror" 
                                           id="website" 
                                           name="website" 
                                           value="{{ old('website', $company->website) }}" 
                                           placeholder="https://www.example.com">
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-mail-line"></i></span>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $company->email) }}" 
                                           placeholder="info@company.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Telefon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-phone-line"></i></span>
                                    <input type="tel" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $company->phone) }}" 
                                           placeholder="+420 123 456 789">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">Město</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-map-pin-line"></i></span>
                                    <input type="text" 
                                           class="form-control @error('city') is-invalid @enderror" 
                                           id="city" 
                                           name="city" 
                                           value="{{ old('city', $company->city) }}" 
                                           placeholder="Praha">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="address" class="form-label">Adresa</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" 
                                          name="address" 
                                          rows="3" 
                                          placeholder="Úplná adresa společnosti">{{ old('address', $company->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Další údaje -->
                        <h6 class="fw-bold text-primary mb-3 mt-4">
                            <i class="ri-money-dollar-circle-line me-2"></i>
                            Další údaje
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="revenue" class="form-label">Roční obrat (CZK)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Kč</span>
                                    <input type="number" 
                                           class="form-control @error('revenue') is-invalid @enderror" 
                                           id="revenue" 
                                           name="revenue" 
                                           value="{{ old('revenue', $company->revenue) }}" 
                                           min="0" 
                                           step="1000"
                                           placeholder="5000000">
                                    @error('revenue')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Zadejte roční obrat společnosti</small>
                            </div>
                            <div class="col-md-6">
                                <label for="employees_count" class="form-label">Počet zaměstnanců</label>
                                <input type="number" 
                                       class="form-control @error('employees_count') is-invalid @enderror" 
                                       id="employees_count" 
                                       name="employees_count" 
                                       value="{{ old('employees_count', $company->employees_count) }}" 
                                       min="1" 
                                       placeholder="50">
                                @error('employees_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Přibližný počet zaměstnanců</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="description" class="form-label">Popis společnosti</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          placeholder="Krátký popis společnosti, jejich aktivit a zaměření...">{{ old('description', $company->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Akce -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ url('/crm/companies/' . $company->id) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i>
                                Zpět na detail
                            </a>
                            <div>
                                <a href="{{ url('/crm/companies') }}" class="btn btn-secondary me-2">
                                    <i class="ri-close-line me-1"></i>
                                    Zrušit
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i>
                                    Uložit změny
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
