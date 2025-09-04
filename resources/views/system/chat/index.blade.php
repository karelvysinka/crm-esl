@extends('layouts.vertical', ['title' => 'Chat – Nastavení'])

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">Chat – Nastavení</h4>
      </div>
      <div class="card-body">
        @if(session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('system.chat.save') }}" class="needs-validation" novalidate>
          @csrf
          <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1" {{ $enabled ? 'checked' : '' }}>
            <label class="form-check-label" for="enabled">Povolit chat</label>
          </div>

          <div class="mb-3">
            <label class="form-label">Poskytovatel</label>
            <select class="form-select" name="provider" required>
              <option value="openrouter" {{ $provider==='openrouter' ? 'selected' : '' }}>OpenRouter</option>
              <option value="gemini" {{ $provider==='gemini' ? 'selected' : '' }}>Google Gemini</option>
            </select>
            <div class="invalid-feedback">Zvolte poskytovatele.</div>
          </div>

          <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" id="show_diag_badges" name="show_diag_badges" value="1" {{ ($show_diag_badges ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="show_diag_badges">Zobrazovat diagnostické štítky v chat okně (provider/model, deterministická odpověď)</label>
          </div>

          <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" id="links_same_tab" name="links_same_tab" value="1" {{ ($links_same_tab ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="links_same_tab">Otevírat CRM odkazy z chatu ve stejné kartě</label>
          </div>

          <div class="border rounded p-3 mb-3">
            <h5 class="mb-3">OpenRouter</h5>
            <div class="mb-3">
              <label class="form-label">API klíč</label>
              <input type="password" class="form-control" name="openrouter_key" value="{{ $openrouter_key }}" autocomplete="off">
            </div>
            <div class="mb-3">
              <label class="form-label">Model</label>
              <input type="text" class="form-control" name="openrouter_model" value="{{ $openrouter_model }}" placeholder="deepseek/deepseek-chat-v3-0324:free">
            </div>
          </div>

          <div class="border rounded p-3 mb-3">
            <h5 class="mb-3">Google Gemini</h5>
            <div class="mb-3 form-check form-switch">
              <input type="checkbox" class="form-check-input" id="gemini_enabled" name="gemini_enabled" value="1" {{ $gemini_enabled ? 'checked' : '' }}>
              <label class="form-check-label" for="gemini_enabled">Povolit Gemini (pokud je nastaven jako poskytovatel)</label>
            </div>
            <div class="mb-3">
              <label class="form-label">API klíč</label>
              <input type="password" class="form-control" name="gemini_key" value="{{ $gemini_key }}" autocomplete="off">
            </div>
            <div class="mb-3">
              <label class="form-label">Model</label>
              <input type="text" class="form-control" name="gemini_model" value="{{ $gemini_model }}" placeholder="gemini-1.5-flash">
            </div>
          </div>

          <button class="btn btn-primary" type="submit">Uložit</button>
          <a class="btn btn-outline-secondary ms-2" href="{{ route('system.chat.index') }}">Zrušit</a>
          <button formaction="{{ route('system.chat.test') }}" formmethod="POST" class="btn btn-outline-info ms-2">Otestovat připojení</button>
          @csrf
        </form>
        <hr>
        <div>
          <a class="btn btn-outline-secondary" href="{{ route('system.chat.diagnostics') }}">Diagnostika a logy</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
