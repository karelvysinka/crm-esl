@extends('layouts.vertical', ['title' => 'Upravit kontakt'])

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/contacts') }}">Kontakty</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/contacts/' . $contact->id) }}">{{ $contact->full_name }}</a></li>
                        <li class="breadcrumb-item active">Upravit</li>
                    </ol>
                </div>
                <h4 class="page-title">Upravit kontakt</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-user-settings-line me-2"></i>
                        Upravit informace o kontaktu
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="ri-information-line me-2"></i>
                        Upravte údaje o kontaktu. Povinná pole jsou označena hvězdičkou (*).
                    </div>

                    <form action="{{ url('/crm/contacts/' . $contact->id) }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        
                        <!-- Základní informace -->
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="ri-user-line me-2"></i>
                            Základní informace
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">
                                    Jméno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" name="first_name" value="{{ old('first_name', $contact->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Vypadá dobře!</div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">
                                    Příjmení <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" name="last_name" value="{{ old('last_name', $contact->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Vypadá dobře!</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="company_id" class="form-label">
                                    Společnost <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('company_id') is-invalid @enderror" 
                                        id="company_id" name="company_id" required>
                                    <option value="">Vyberte společnost</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $contact->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Vypadá dobře!</div>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="">Vyberte status</option>
                                    <option value="active" {{ old('status', $contact->status) == 'active' ? 'selected' : '' }}>Aktivní</option>
                                    <option value="inactive" {{ old('status', $contact->status) == 'inactive' ? 'selected' : '' }}>Neaktivní</option>
                                    <option value="blocked" {{ old('status', $contact->status) == 'blocked' ? 'selected' : '' }}>Blokovaný</option>
                                    <option value="lead" {{ old('status', $contact->status) == 'lead' ? 'selected' : '' }}>Lead</option>
                                    <option value="prospect" {{ old('status', $contact->status) == 'prospect' ? 'selected' : '' }}>Prospect</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="valid-feedback">Vypadá dobře!</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="position" class="form-label">Pozice</label>
                                <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                       id="position" name="position" value="{{ old('position', $contact->position) }}" 
                                       placeholder="např. Ředitel, Manažer, Obchodník">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Oddělení</label>
                                <input type="text" class="form-control @error('department') is-invalid @enderror" 
                                       id="department" name="department" value="{{ old('department', $contact->department) }}" 
                                       placeholder="např. IT, Obchod, Marketing">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Kontaktní údaje -->
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="ri-phone-line me-2"></i>
                            Kontaktní údaje
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="email" class="form-label">
                                    <i class="ri-mail-line me-1"></i>Email
                                </label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $contact->email) }}" 
                                       placeholder="jan.novak@company.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="phone" class="form-label">
                                    <i class="ri-phone-line me-1"></i>Telefon
                                </label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $contact->phone) }}" 
                                       placeholder="+420 123 456 789">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="mobile" class="form-label">
                                    <i class="ri-smartphone-line me-1"></i>Mobil
                                </label>
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" 
                                       id="mobile" name="mobile" value="{{ old('mobile', $contact->mobile) }}" 
                                       placeholder="+420 123 456 789">
                                @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="preferred_contact" class="form-label">Preferovaný způsob kontaktu</label>
                                <select class="form-select @error('preferred_contact') is-invalid @enderror" 
                                        id="preferred_contact" name="preferred_contact">
                                    <option value="">Vyberte způsob kontaktu</option>
                                    <option value="email" {{ old('preferred_contact', $contact->preferred_contact) == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="phone" {{ old('preferred_contact', $contact->preferred_contact) == 'phone' ? 'selected' : '' }}>Telefon</option>
                                    <option value="mobile" {{ old('preferred_contact', $contact->preferred_contact) == 'mobile' ? 'selected' : '' }}>Mobil</option>
                                </select>
                                @error('preferred_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Adresa -->
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="ri-map-pin-line me-2"></i>
                            Adresa
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label">Adresa</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="2" 
                                          placeholder="Úplná adresa kontaktu">{{ old('address', $contact->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label">
                                    <i class="ri-map-pin-2-line me-1"></i>Město
                                </label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $contact->city) }}" 
                                       placeholder="Praha">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">
                                    <i class="ri-global-line me-1"></i>Země
                                </label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror" 
                                       id="country" name="country" value="{{ old('country', $contact->country) }}" 
                                       placeholder="Česká republika">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Poznámky -->
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="ri-file-text-line me-2"></i>
                            Dodatečné informace
                        </h6>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Poznámky</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="4" 
                                          placeholder="Jakékoliv další informace o kontaktu...">{{ old('notes', $contact->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Tlačítka -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ url('/crm/contacts/' . $contact->id) }}" class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i>
                                Zpět na detail
                            </a>
                            <div>
                                <a href="{{ url('/crm/contacts') }}" class="btn btn-secondary me-2">
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
@endsection

@section('script')
<script>
// Bootstrap validation
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
