@extends('layouts.vertical', ['page_title' => 'Migrate'])

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-body">
      <pre class="mb-0" style="white-space: pre-wrap;">{{ $output }}</pre>
    </div>
  </div>
</div>
@endsection
