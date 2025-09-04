@extends('layouts.vertical', ['title' => 'Chat'])

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">Chat</h4>
      </div>
      <div class="card-body">
        @php $chatEnabled = \App\Models\SystemSetting::get('chat.enabled', '0') === '1'; @endphp
        @if(!$chatEnabled)
          <div class="alert alert-warning">Chat je aktuálně vypnutý v nastavení systému.</div>
        @endif
        @include('layouts.partials.chat-widget')
        <p class="text-muted">Toto je plovoucí chat. Použijte tlačítko vpravo dole.</p>
        <div class="alert alert-info mt-2">
          Tip: Chcete, aby asistent ověřil informace na webu? Zkuste dotaz typu „Ověř na webu…“ nebo „Najdi kontakty na esl.cz“. Odpověď vždy obsahuje odkaz na zdroj.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
