@extends('layouts.vertical', ['page_title' => 'Upravit znalost'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <h4 class="page-title mb-0">Upravit poznámku</h4>
    <a href="{{ route('knowledge.index') }}" class="btn btn-light">Zpět</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('knowledge.update', $note) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
          <label class="form-label">Titulek</label>
          <input name="title" class="form-control" required value="{{ old('title', $note->title) }}">
        </div>
        <div class="mb-3">
          <label class="form-label">Obsah (Markdown nebo prostý text)</label>
          <textarea name="content" class="form-control" rows="12" required>{{ old('content', $note->content) }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Tagy (oddělené čárkou)</label>
          <input name="tags" class="form-control" value="{{ old('tags', implode(',', $note->tags ?? [])) }}">
        </div>
        <div class="mb-3">
          <label class="form-label">Viditelnost</label>
          <select name="visibility" class="form-select">
            <option value="public" @selected(old('visibility', $note->visibility)==='public')>Veřejné v rámci CRM</option>
            <option value="private" @selected(old('visibility', $note->visibility)==='private')>Soukromé (jen pro mě)</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">Uložit</button>
        </div>
      </form>
      <form method="POST" action="{{ route('knowledge.destroy', $note) }}" class="mt-3" onsubmit="return confirm('Opravdu smazat?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger">Smazat</button>
      </form>
    </div>
  </div>
</div>
@endsection
