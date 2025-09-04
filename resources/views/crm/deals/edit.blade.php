@extends('layouts.vertical', ['page_title' => 'Upravit deal'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('deals.index') }}">Dealy</a></li>
        <li class="breadcrumb-item"><a href="{{ route('deals.show', $deal) }}">{{ $deal->name }}</a></li>
        <li class="breadcrumb-item active">Upravit</li>
      </ol>
    </div>
    <h4 class="page-title">Upravit deal</h4>
  </div>

  <div class="card">
    <div class="card-body">
  @include('layouts.partials.flash')
      <form method="POST" action="{{ route('deals.update', $deal) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Název</label>
            <input name="name" class="form-control" value="{{ old('name',$deal->name) }}" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Částka</label>
            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount',$deal->amount) }}" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Close date</label>
            <input type="date" name="close_date" class="form-control" value="{{ old('close_date', optional($deal->close_date)->format('Y-m-d')) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Opportunity</label>
            <select name="opportunity_id" class="form-select" required>
              @foreach($opportunities as $o)
                <option value="{{ $o->id }}" @selected(old('opportunity_id',$deal->opportunity_id)===$o->id)>{{ $o->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              @foreach(['pending','won','lost'] as $s)
                <option value="{{ $s }}" @selected(old('status',$deal->status)===$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Podepsal (kontakt)</label>
            <select name="signed_by_contact_id" class="form-select">
              <option value="">—</option>
              @foreach($contacts as $c)
                <option value="{{ $c->id }}" @selected(old('signed_by_contact_id',$deal->signed_by_contact_id)===$c->id)>{{ $c->full_name ?? ($c->email ?? ('Kontakt #'.$c->id)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Podepsáno kdy</label>
            <input type="datetime-local" name="signed_at" class="form-control" value="{{ old('signed_at', optional($deal->signed_at)->format('Y-m-d\TH:i')) }}" />
          </div>
          <div class="col-12">
            <label class="form-label">Podmínky</label>
            <textarea name="terms" class="form-control" rows="3">{{ old('terms',$deal->terms) }}</textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Poznámky</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes',$deal->notes) }}</textarea>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('deals.show', $deal) }}" class="btn btn-light">Zpět</a>
          <button class="btn btn-primary" type="submit">Uložit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
