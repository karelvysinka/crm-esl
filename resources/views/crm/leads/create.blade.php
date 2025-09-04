@extends('layouts.vertical', ['page_title' => 'Přidat Lead', 'mode' => 'light'])

@section('css')
@endsection

@section('content')
<!-- Start Content-->
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/crm/leads') }}">Leads</a></li>
                        <li class="breadcrumb-item active">Přidat Lead</li>
                    </ol>
                </div>
                <h4 class="page-title">Přidat Lead</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Informace o Lead</h4>
                    <p class="text-muted mb-0">Vyplňte základní informace o novém lead</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ url('/crm/leads') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Název společnosti <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="{{ old('company_name') }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Jméno kontaktu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                           value="{{ old('contact_name') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="{{ old('email') }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="{{ old('phone') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source" class="form-label">Zdroj <span class="text-danger">*</span></label>
                                    <select class="form-select" id="source" name="source" required>
                                        <option value="">Vyberte zdroj</option>
                                        <option value="website" {{ old('source') == 'website' ? 'selected' : '' }}>Website</option>
                                        <option value="referral" {{ old('source') == 'referral' ? 'selected' : '' }}>Doporučení</option>
                                        <option value="social_media" {{ old('source') == 'social_media' ? 'selected' : '' }}>Sociální sítě</option>
                                        <option value="cold_call" {{ old('source') == 'cold_call' ? 'selected' : '' }}>Studený hovor</option>
                                        <option value="email_campaign" {{ old('source') == 'email_campaign' ? 'selected' : '' }}>Email kampaň</option>
                                        <option value="trade_show" {{ old('source') == 'trade_show' ? 'selected' : '' }}>Veletrh</option>
                                        <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>Ostatní</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="new" {{ old('status', 'new') == 'new' ? 'selected' : '' }}>Nový</option>
                                        <option value="contacted" {{ old('status') == 'contacted' ? 'selected' : '' }}>Kontaktovaný</option>
                                        <option value="qualified" {{ old('status') == 'qualified' ? 'selected' : '' }}>Kvalifikovaný</option>
                                        <option value="proposal" {{ old('status') == 'proposal' ? 'selected' : '' }}>Nabídka</option>
                                        <option value="negotiation" {{ old('status') == 'negotiation' ? 'selected' : '' }}>Vyjednávání</option>
                                        <option value="won" {{ old('status') == 'won' ? 'selected' : '' }}>Vyhráno</option>
                                        <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Prohráno</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estimated_value" class="form-label">Odhadovaná hodnota (Kč)</label>
                                    <input type="number" class="form-control" id="estimated_value" name="estimated_value" 
                                           value="{{ old('estimated_value') }}" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Přiřadit k</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Nepřiřazeno</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Poznámky</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                        </div>

                        <!-- Action buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ url('/crm/leads') }}" class="btn btn-secondary me-2">
                                        <i class="mdi mdi-arrow-left"></i> Zpět
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="mdi mdi-content-save"></i> Uložit Lead
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="mdi mdi-help-circle-outline text-info"></i> Nápověda
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Skóre Lead</h6>
                            <p class="text-muted small">
                                Skóre se počítá automaticky na základě:
                            </p>
                            <ul class="text-muted small">
                                <li>Odhadované hodnoty obchodu</li>
                                <li>Zdroje lead (website = vyšší skóre)</li>
                                <li>Statusu v sales pipeline</li>
                                <li>Posledního kontaktu</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Sales Pipeline</h6>
                            <p class="text-muted small">
                                Typický postup lead v pipeline:
                            </p>
                            <ul class="text-muted small">
                                <li><strong>Nový</strong> - právě zachycený lead</li>
                                <li><strong>Kontaktovaný</strong> - první kontakt navázán</li>
                                <li><strong>Kvalifikovaný</strong> - má zájem a rozpočet</li>
                                <li><strong>Nabídka</strong> - odeslána nabídka</li>
                                <li><strong>Vyjednávání</strong> - probíhá vyjednávání</li>
                                <li><strong>Vyhráno/Prohráno</strong> - finální stav</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<!-- container -->
@endsection

@section('script')
<script>
    $(document).ready(function() {
        // Auto-format estimated value with thousands separator
        $('#estimated_value').on('input', function() {
            let value = this.value.replace(/\s/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('cs-CZ');
            }
        });

        // Remove formatting on form submit
        $('form').on('submit', function() {
            let estimatedValue = $('#estimated_value').val();
            if (estimatedValue) {
                $('#estimated_value').val(estimatedValue.replace(/\s/g, ''));
            }
        });
    });
</script>
@endsection
