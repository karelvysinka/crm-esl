@extends('layouts.vertical', ['page_title' => 'Dealy'])

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
            <li class="breadcrumb-item active">Dealy</li>
          </ol>
        </div>
        <h4 class="page-title">Dealy</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <h4 class="header-title">Seznam dealů</h4>
      <a href="{{ route('deals.create') }}" class="btn btn-primary">Nový deal</a>
    </div>
    <div class="card-body">
      @include('layouts.partials.flash')
      <div class="table-responsive">
        <table class="table table-hover" id="dealsTable">
          <thead>
            <tr>
              <th>Název</th>
              <th>Částka</th>
              <th>Close date</th>
              <th>Status</th>
              <th>Opportunity</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($deals as $deal)
              <tr>
                <td><a href="{{ route('deals.show', $deal) }}">{{ $deal->name }}</a></td>
                <td>{{ number_format($deal->amount, 0, ',', ' ') }} Kč</td>
                <td>{{ optional($deal->close_date)->format('d.m.Y') }}</td>
                <td><span class="badge bg-info">{{ ucfirst($deal->status) }}</span></td>
                <td>{{ $deal->opportunity->name ?? '—' }}</td>
                <td><a href="{{ route('deals.edit', $deal) }}" class="btn btn-sm btn-outline-warning">Upravit</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted">Žádné dealy</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
$(function(){
  if ($.fn.DataTable) {
    $('#dealsTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[2, 'asc']],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/cs.json' },
      columnDefs: [ { orderable: false, targets: [5] } ]
    });
  }
});
</script>
@endsection
