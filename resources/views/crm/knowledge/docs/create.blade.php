@extends('layouts.vertical', ['page_title' => 'Nahrát znalostní dokument'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box d-flex align-items-center justify-content-between">
    <div>
      <h4 class="page-title mb-0">Nahrát dokument</h4>
      <span class="text-muted">Přidejte znalostní obsah (TXT/MD/HTML/PDF). Text se rozseká na části a zařadí do vektorového vyhledávání.</span>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('knowledge.docs.index') }}" class="btn btn-outline-secondary">Zpět na seznam</a>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Formulář obsahuje chyby:</div>
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <form method="POST" action="{{ route('knowledge.docs.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="mb-3">
              <label class="form-label">Titulek <span class="text-danger">*</span></label>
              <input name="title" class="form-control" placeholder="Např. Ceník 2025 / Produktová brožura" value="{{ old('title') }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Soubor <span class="text-danger">*</span></label>
              <input type="file" name="file" class="form-control" accept="text/plain,text/markdown,text/x-markdown,text/html,application/pdf" required>
              <div class="form-text">Podporované typy: TXT, MD, HTML, PDF. Max. 20&nbsp;MB.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Tagy</label>
              <input name="tags" class="form-control" value="{{ old('tags') }}" placeholder="např. produkt, ceník, proces">
              <div class="form-text">Zadejte volitelné tagy oddělené čárkou. Usnadní vyhledávání v seznamu dokumentů.</div>
            </div>

            <div class="mb-3">
              <label class="form-label d-block">Viditelnost <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="visibility" id="vis_public" value="public" {{ old('visibility','public')==='public' ? 'checked' : '' }}>
                  <label class="form-check-label" for="vis_public">Veřejné v rámci CRM</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="visibility" id="vis_private" value="private" {{ old('visibility')==='private' ? 'checked' : '' }}>
                  <label class="form-check-label" for="vis_private">Soukromé (jen pro mě)</label>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-primary" type="submit">
                <i class="bi bi-cloud-upload me-1"></i> Nahrát a zařadit
              </button>
              <a href="{{ route('knowledge.docs.index') }}" class="btn btn-light">Zrušit</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header border-0 pb-0">
          <h6 class="mb-0">Jak probíhá zpracování</h6>
        </div>
        <div class="card-body">
          <ol class="mb-3 ps-3">
            <li>Nahraný soubor se přečte (PDF se pokusíme převést na text).</li>
            <li>Text se rozdělí na menší části (chunky) s překryvem.</li>
            <li>Pokud je zapnuto vektorové vyhledávání, části se zavektorizují do Qdrantu.</li>
          </ol>
          <div class="small text-muted">
            U naskenovaných PDF bez textové vrstvy může být nutné OCR (lze doplnit později).
          </div>
          <hr>
          <div class="small">
            Tip: Pro lepší výsledky přidejte do PDF textové nadpisy a sekce. Pro krátké poznámky použijte raději TXT/MD.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
