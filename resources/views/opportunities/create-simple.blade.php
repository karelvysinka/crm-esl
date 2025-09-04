@extends('layouts.vertical', ['page_title' => 'Nová příležitost'])

@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('opportunities.index') }}">Příležitosti</a></li>
                        <li class="breadcrumb-item active">Nová příležitost</li>
                    </ol>
                </div>
                <h4 class="page-title">Nová příležitost</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Vytvořit novou příležitost</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('opportunities.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Název příležitosti *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="value" class="form-label">Hodnota (Kč) *</label>
                                    <input type="number" class="form-control" id="value" name="value" step="0.01" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Společnost *</label>
                                    <select class="form-select" id="company_id" name="company_id" required>
                                        <option value="">Vyberte společnost</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_id" class="form-label">Kontakt</label>
                                    <select class="form-select" id="contact_id" name="contact_id">
                                        <option value="">Vyberte kontakt</option>
                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stage" class="form-label">Stádium *</label>
                                    <select class="form-select" id="stage" name="stage" required>
                                        <option value="prospecting">Prospektování</option>
                                        <option value="qualification">Kvalifikace</option>
                                        <option value="proposal">Návrh</option>
                                        <option value="negotiation">Vyjednávání</option>
                                        <option value="closing">Uzavírání</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="probability" class="form-label">Pravděpodobnost (%)</label>
                                    <input type="number" class="form-control" id="probability" name="probability" min="0" max="100" value="10">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expected_close_date" class="form-label">Očekávané uzavření *</label>
                                    <input type="date" class="form-control" id="expected_close_date" name="expected_close_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Popis</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Přiřazeno *</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to" required>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('opportunities.index') }}" class="btn btn-secondary me-2">Zrušit</a>
                            <button type="submit" class="btn btn-primary">Vytvořit příležitost</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
