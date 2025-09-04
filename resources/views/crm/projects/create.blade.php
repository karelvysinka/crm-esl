@extends('layouts.vertical', ['page_title' => 'Nový projekt'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <h4 class="page-title">Nový projekt</h4>
  </div>
  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Název</label>
            <input name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              @foreach(['planned','in_progress','on_hold','completed','cancelled'] as $s)
                <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Firma</label>
            <select name="company_id" class="form-select">
              <option value="">—</option>
              @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kontakt</label>
            <select name="contact_id" class="form-select">
              <option value="">—</option>
              @foreach($contacts as $contact)
                <option value="{{ $contact->id }}">{{ $contact->full_name ?? ($contact->email) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Přiřazeno</label>
            <select name="assigned_to" class="form-select">
              <option value="">—</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Začátek</label>
            <input type="date" name="start_date" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Termín</label>
            <input type="date" name="due_date" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label">Popis</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('projects.index') }}" class="btn btn-light">Zpět</a>
          <button class="btn btn-primary" type="submit">Uložit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
