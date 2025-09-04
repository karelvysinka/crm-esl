@extends('layouts.vertical', ['page_title' => 'Nová znalost'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Nová poznámka</h4>
    <a href="{{ route('knowledge.index') }}" class="btn btn-light">Zpět</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('knowledge.store') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Titulek</label>
          <input name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Obsah (Markdown nebo prostý text)</label>
          <textarea name="content" class="form-control" rows="10" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Tagy (oddělené čárkou)</label>
          <input name="tags" class="form-control" placeholder="např. nabídka, billing, proces">
        </div>
        <div class="mb-3">
          <label class="form-label">Viditelnost</label>
          <select name="visibility" class="form-select">
            <option value="public">Veřejné v rámci CRM</option>
            <option value="private">Soukromé (jen pro mě)</option>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Uložit</button>
      </form>
    </div>
  </div>
</div>
@endsection
