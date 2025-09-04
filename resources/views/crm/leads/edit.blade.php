@extends('layouts.vertical', ['page_title' => 'Upravit Lead', 'mode' => 'light'])

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
                        <li class="breadcrumb-item"><a href="{{ url('/crm/leads/' . $lead->id) }}">{{ $lead->company_name }}</a></li>
                        <li class="breadcrumb-item active">Upravit</li>
                    </ol>
                </div>
                <h4 class="page-title">Upravit Lead</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Upravit informace o Lead</h4>
                    <p class="text-muted mb-0">Aktualizujte informace o lead "{{ $lead->company_name }}"</p>
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

                    <form action="{{ url('/crm/leads/' . $lead->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Název společnosti <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="{{ old('company_name', $lead->company_name) }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Jméno kontaktu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                           value="{{ old('contact_name', $lead->contact_name) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="{{ old('email', $lead->email) }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="{{ old('phone', $lead->phone) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="source" class="form-label">Zdroj <span class="text-danger">*</span></label>
                                    <select class="form-select" id="source" name="source" required>
                                        <option value="">Vyberte zdroj</option>
                                        <option value="website" {{ old('source', $lead->source) == 'website' ? 'selected' : '' }}>Website</option>
                                        <option value="referral" {{ old('source', $lead->source) == 'referral' ? 'selected' : '' }}>Doporučení</option>
                                        <option value="social_media" {{ old('source', $lead->source) == 'social_media' ? 'selected' : '' }}>Sociální sítě</option>
                                        <option value="cold_call" {{ old('source', $lead->source) == 'cold_call' ? 'selected' : '' }}>Studený hovor</option>
                                        <option value="email_campaign" {{ old('source', $lead->source) == 'email_campaign' ? 'selected' : '' }}>Email kampaň</option>
                                        <option value="trade_show" {{ old('source', $lead->source) == 'trade_show' ? 'selected' : '' }}>Veletrh</option>
                                        <option value="other" {{ old('source', $lead->source) == 'other' ? 'selected' : '' }}>Ostatní</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="new" {{ old('status', $lead->status) == 'new' ? 'selected' : '' }}>Nový</option>
                                        <option value="contacted" {{ old('status', $lead->status) == 'contacted' ? 'selected' : '' }}>Kontaktovaný</option>
                                        <option value="qualified" {{ old('status', $lead->status) == 'qualified' ? 'selected' : '' }}>Kvalifikovaný</option>
                                        <option value="proposal" {{ old('status', $lead->status) == 'proposal' ? 'selected' : '' }}>Nabídka</option>
                                        <option value="negotiation" {{ old('status', $lead->status) == 'negotiation' ? 'selected' : '' }}>Vyjednávání</option>
                                        <option value="won" {{ old('status', $lead->status) == 'won' ? 'selected' : '' }}>Vyhráno</option>
                                        <option value="lost" {{ old('status', $lead->status) == 'lost' ? 'selected' : '' }}>Prohráno</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estimated_value" class="form-label">Odhadovaná hodnota (Kč)</label>
                                    <input type="number" class="form-control" id="estimated_value" name="estimated_value" 
                                           value="{{ old('estimated_value', $lead->estimated_value) }}" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Přiřadit k</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Nepřiřazeno</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Poznámky</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $lead->notes) }}</textarea>
                        </div>

                        <!-- Current Score Display -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-information-outline me-2"></i>
                                        <div>
                                            <strong>Aktuální skóre lead: {{ $lead->score }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Skóre se po uložení automaticky přepočítá na základě nových hodnot.
                                                @if($lead->score >= 70)
                                                    Tento lead je vysoce kvalitní.
                                                @elseif($lead->score >= 40)
                                                    Tento lead má střední kvalitu.
                                                @else
                                                    Tento lead má nízkou kvalitu.
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ url('/crm/leads/' . $lead->id) }}" class="btn btn-secondary me-2">
                                        <i class="mdi mdi-arrow-left"></i> Zpět
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="mdi mdi-content-save"></i> Uložit změny
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change History -->
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Historie změn</h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm bg-success rounded-circle">
                                    <span class="avatar-title text-white">
                                        <i class="mdi mdi-plus"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Lead vytvořen</h6>
                                <p class="text-muted mb-1">
                                    Lead byl vytvořen
                                    @if($lead->createdBy)
                                        uživatelem <strong>{{ $lead->createdBy->name }}</strong>
                                    @endif
                                </p>
                                <small class="text-muted">{{ $lead->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>

                        @if($lead->updated_at != $lead->created_at)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm bg-info rounded-circle">
                                    <span class="avatar-title text-white">
                                        <i class="mdi mdi-pencil"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Poslední aktualizace</h6>
                                <p class="text-muted mb-1">Lead byl naposledy upraven</p>
                                <small class="text-muted">{{ $lead->updated_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>
                        @endif

                        @if($lead->last_activity_at && $lead->last_activity_at != $lead->updated_at)
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm bg-warning rounded-circle">
                                    <span class="avatar-title text-white">
                                        <i class="mdi mdi-clock"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Poslední aktivita</h6>
                                <p class="text-muted mb-1">Zaznamenaná aktivita na lead</p>
                                <small class="text-muted">{{ $lead->last_activity_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Delete Lead -->
            <div class="card border-danger">
                <div class="card-header bg-danger">
                    <h4 class="header-title text-white">Nebezpečná zona</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Smazání lead je nevratná operace. Všechna související data budou trvale ztracena.
                    </p>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="mdi mdi-delete"></i> Smazat Lead
                    </button>
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

    function confirmDelete() {
        if (confirm('Opravdu chcete smazat tento lead? Tato operace je nevratná!')) {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("/crm/leads/" . $lead->id) }}';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection
