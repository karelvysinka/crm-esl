@extends('layouts.vertical', ['page_title' => 'Upravit příležitost'])

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/opportunities') }}">Příležitosti</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/opportunities/' . $opportunity->id) }}">{{ $opportunity->title }}</a></li>
                        <li class="breadcrumb-item active">Upravit</li>
                    </ol>
                </div>
                <h4 class="page-title">Upravit příležitost</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Upravit příležitost</h4>
                    <p class="text-muted mb-0">Upravte informace o příležitosti</p>
                </div>
                <div class="card-body">
                    
                    <form action="{{ url('/crm/opportunities/' . $opportunity->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-lg-6">
                                <h5 class="mb-3 text-uppercase bg-light p-2"><i class="ti ti-info-circle me-1"></i> Základní informace</h5>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Název příležitosti <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $opportunity->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Popis</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $opportunity->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="value" class="form-label">Hodnota (Kč) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('value') is-invalid @enderror" 
                                                   id="value" name="value" value="{{ old('value', $opportunity->value) }}" min="0" step="0.01" required>
                                            @error('value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="probability" class="form-label">Pravděpodobnost (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('probability') is-invalid @enderror" 
                                                   id="probability" name="probability" value="{{ old('probability', $opportunity->probability) }}" min="0" max="100" required>
                                            @error('probability')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="stage" class="form-label">Fáze <span class="text-danger">*</span></label>
                                            <select class="form-select @error('stage') is-invalid @enderror" id="stage" name="stage" required>
                                                <option value="">Vyberte fázi</option>
                                                <option value="qualification" {{ old('stage', $opportunity->stage) == 'qualification' ? 'selected' : '' }}>Kvalifikace</option>
                                                <option value="proposal" {{ old('stage', $opportunity->stage) == 'proposal' ? 'selected' : '' }}>Návrh</option>
                                                <option value="negotiation" {{ old('stage', $opportunity->stage) == 'negotiation' ? 'selected' : '' }}>Vyjednávání</option>
                                                <option value="closed_won" {{ old('stage', $opportunity->stage) == 'closed_won' ? 'selected' : '' }}>Uzavřeno - Vyhráno</option>
                                                <option value="closed_lost" {{ old('stage', $opportunity->stage) == 'closed_lost' ? 'selected' : '' }}>Uzavřeno - Prohráno</option>
                                            </select>
                                            @error('stage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                                <option value="">Vyberte status</option>
                                                <option value="open" {{ old('status', $opportunity->status) == 'open' ? 'selected' : '' }}>Otevřeno</option>
                                                <option value="won" {{ old('status', $opportunity->status) == 'won' ? 'selected' : '' }}>Vyhráno</option>
                                                <option value="lost" {{ old('status', $opportunity->status) == 'lost' ? 'selected' : '' }}>Prohráno</option>
                                                <option value="on_hold" {{ old('status', $opportunity->status) == 'on_hold' ? 'selected' : '' }}>Pozastaveno</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="expected_close_date" class="form-label">Očekávané datum uzavření <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('expected_close_date') is-invalid @enderror" 
                                                   id="expected_close_date" name="expected_close_date" 
                                                   value="{{ old('expected_close_date', $opportunity->expected_close_date?->format('Y-m-d')) }}" required>
                                            @error('expected_close_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label for="actual_close_date" class="form-label">Skutečné datum uzavření</label>
                                            <input type="date" class="form-control @error('actual_close_date') is-invalid @enderror" 
                                                   id="actual_close_date" name="actual_close_date" 
                                                   value="{{ old('actual_close_date', $opportunity->actual_close_date?->format('Y-m-d')) }}">
                                            @error('actual_close_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact & Assignment Information -->
                            <div class="col-lg-6">
                                <h5 class="mb-3 text-uppercase bg-light p-2"><i class="ti ti-users me-1"></i> Kontakt a přiřazení</h5>
                                
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Společnost <span class="text-danger">*</span></label>
                                    <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                                        <option value="">Vyberte společnost</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ old('company_id', $opportunity->company_id) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contact_id" class="form-label">Kontakt <span class="text-danger">*</span></label>
                                    <select class="form-select @error('contact_id') is-invalid @enderror" id="contact_id" name="contact_id" required>
                                        <option value="">Vyberte kontakt</option>
                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}" 
                                                    data-company="{{ $contact->company_id }}"
                                                    {{ old('contact_id', $opportunity->contact_id) == $contact->id ? 'selected' : '' }}>
                                                {{ $contact->name }} 
                                                @if($contact->company)
                                                    ({{ $contact->company->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Přiřazeno</label>
                                    <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                        <option value="">Nepřiřazeno</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="source" class="form-label">Zdroj</label>
                                    <input type="text" class="form-control @error('source') is-invalid @enderror" 
                                           id="source" name="source" value="{{ old('source', $opportunity->source) }}" 
                                           placeholder="např. Web, Doporučení, Reklama...">
                                    @error('source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Poznámky</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="4" 
                                              placeholder="Dodatečné informace o příležitosti...">{{ old('notes', $opportunity->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ url('/crm/opportunities/' . $opportunity->id) }}" class="btn btn-secondary me-2">
                                        <i class="ti ti-arrow-left me-1"></i> Zpět
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i> Uložit změny
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div> <!-- end card-body -->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    <!-- end row -->

</div> <!-- container -->
@endsection

@section('script')
<script>
$(document).ready(function() {
    // Filter contacts based on selected company
    $('#company_id').on('change', function() {
        const selectedCompany = $(this).val();
        const contactSelect = $('#contact_id');
        
        contactSelect.find('option').each(function() {
            const contactCompany = $(this).data('company');
            if (selectedCompany === '' || contactCompany == selectedCompany || $(this).val() === '') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Reset contact selection if hidden
        if (contactSelect.find('option:selected').is(':hidden')) {
            contactSelect.val('');
        }
    });

    // Auto-select company when contact is selected
    $('#contact_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const contactCompany = selectedOption.data('company');
        
        if (contactCompany) {
            $('#company_id').val(contactCompany);
        }
    });

    // Update probability and status based on stage
    $('#stage').on('change', function() {
        const stage = $(this).val();
        const probabilityInput = $('#probability');
        
        switch(stage) {
            case 'qualification':
                if (probabilityInput.val() == 0 || probabilityInput.val() == '') {
                    probabilityInput.val(25);
                }
                break;
            case 'proposal':
                if (probabilityInput.val() < 25) {
                    probabilityInput.val(50);
                }
                break;
            case 'negotiation':
                if (probabilityInput.val() < 50) {
                    probabilityInput.val(75);
                }
                break;
            case 'closed_won':
                probabilityInput.val(100);
                $('#status').val('won');
                $('#actual_close_date').val(new Date().toISOString().split('T')[0]);
                break;
            case 'closed_lost':
                probabilityInput.val(0);
                $('#status').val('lost');
                $('#actual_close_date').val(new Date().toISOString().split('T')[0]);
                break;
        }
    });

    // Auto-fill actual close date when status changes to won/lost
    $('#status').on('change', function() {
        const status = $(this).val();
        const actualCloseDateInput = $('#actual_close_date');
        
        if ((status === 'won' || status === 'lost') && !actualCloseDateInput.val()) {
            actualCloseDateInput.val(new Date().toISOString().split('T')[0]);
        } else if (status === 'open') {
            actualCloseDateInput.val('');
        }
    });
});
</script>
@endsection
