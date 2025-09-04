@extends('layouts.vertical', ['page_title' => 'Detail dealu'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('deals.index') }}">Dealy</a></li>
        <li class="breadcrumb-item active">{{ $deal->name }}</li>
      </ol>
    </div>
    <h4 class="page-title">{{ $deal->name }}</h4>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          @include('layouts.partials.flash')
          <h5 class="mb-2">Informace</h5>
          <p class="mb-1"><strong>Částka:</strong> {{ number_format($deal->amount, 0, ',', ' ') }} Kč</p>
          <p class="mb-1"><strong>Close date:</strong> {{ optional($deal->close_date)->format('d.m.Y') }}</p>
          <p class="mb-1"><strong>Status:</strong> {{ ucfirst($deal->status) }}</p>
          <p class="mb-1"><strong>Opportunity:</strong> {{ $deal->opportunity->name ?? '—' }}</p>
          <p class="mb-3"><strong>Podepsal:</strong> {{ $deal->signedByContact->name ?? '—' }}</p>
          <div class="d-flex gap-2">
            <a href="{{ route('deals.index') }}" class="btn btn-light">Zpět na seznam</a>
            <a href="{{ route('deals.edit', $deal) }}" class="btn btn-warning">Upravit</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('deals.destroy', $deal) }}" onsubmit="return confirm('Smazat deal?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger w-100">Smazat</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
