@extends('layouts.vertical', ['page_title' => 'Nový deal'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('deals.index') }}">Dealy</a></li>
        <li class="breadcrumb-item active">Nový</li>
      </ol>
    </div>
    <h4 class="page-title">Nový deal</h4>
  </div>

  <div class="card">
    <div class="card-body">
      @include('layouts.partials.flash')
      <form method="POST" action="{{ route('deals.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Název</label>
            <input name="name" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Částka</label>
            <input type="number" step="0.01" name="amount" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Close date</label>
            <input type="date" name="close_date" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Opportunity</label>
            <select name="opportunity_id" class="form-select" required>
              @php $presetOpp = request('opportunity_id'); @endphp
              @foreach($opportunities as $o)
                <option value="{{ $o->id }}" @selected(old('opportunity_id', $presetOpp)===$o->id)>{{ $o->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="won">Won</option>
              <option value="lost">Lost</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Podepsal (kontakt)</label>
            <select name="signed_by_contact_id" class="form-select">
              <option value="">—</option>
              @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->full_name ?? ($c->email ?? ('Kontakt #'.$c->id)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Podepsáno kdy</label>
            <input type="datetime-local" name="signed_at" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label">Podmínky</label>
            <textarea name="terms" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Poznámky</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('deals.index') }}" class="btn btn-light">Zpět</a>
          <button class="btn btn-primary" type="submit">Uložit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
