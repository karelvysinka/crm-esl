@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <h4 class="page-title">Ops Historie</h4>
  <div class="card"><div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>ID</th><th>Typ</th><th>Status</th><th>Start</th><th>End</th><th>Trvání ms</th></tr></thead>
        <tbody>
          @foreach($items as $i)
            <tr>
              <td>{{ $i->id }}</td>
              <td>{{ $i->type }}</td>
              <td>{{ $i->status }}</td>
              <td>{{ $i->started_at }}</td>
              <td>{{ $i->finished_at }}</td>
              <td>{{ $i->duration_ms }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="p-2">{{ $items->links() }}</div>
  </div></div>
</div>
@endsection
