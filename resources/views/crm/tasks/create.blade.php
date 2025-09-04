@extends('layouts.vertical', ['page_title' => 'Nový úkol'])

@section('content')
<div class="container-fluid">
  <div class="page-title-box">
    <div class="page-title-right">
      <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ url('/crm') }}">CRM</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Úkoly</a></li>
        <li class="breadcrumb-item active">Nový</li>
      </ol>
    </div>
    <h4 class="page-title">Nový úkol</h4>
  </div>

  <div class="card">
    <div class="card-body">
  <form method="POST" action="{{ route('tasks.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Titulek</label>
            <input name="title" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select">
              <option value="call">Hovor</option>
              <option value="email">Email</option>
              <option value="meeting">Schůzka</option>
              <option value="follow_up">Follow up</option>
              <option value="proposal">Nabídka</option>
              <option value="other">Jiné</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="pending">Čeká</option>
              <option value="in_progress">Probíhá</option>
              <option value="completed">Hotovo</option>
              <option value="cancelled">Zrušeno</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Priorita</label>
            <select name="priority" class="form-select">
              <option value="low">Nízká</option>
              <option value="medium" selected>Střední</option>
              <option value="high">Vysoká</option>
              <option value="urgent">Urgentní</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Termín</label>
            <input type="datetime-local" name="due_date" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Projekt</label>
            <select name="project_id" class="form-select">
              <option value="">—</option>
              @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Přiřazeno</label>
            <select name="assigned_to" class="form-select" required>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Vztah</label>
            <div class="input-group">
              @php $presetType = request('taskable_type'); $presetId = request('taskable_id'); @endphp
              <select id="rel-type" name="taskable_type" class="form-select" style="max-width:220px">
                <option value="">—</option>
                <option value="company" {{ $presetType==='company' ? 'selected' : '' }}>Společnost</option>
                <option value="contact" {{ $presetType==='contact' ? 'selected' : '' }}>Kontakt</option>
                <option value="lead" {{ $presetType==='lead' ? 'selected' : '' }}>Lead</option>
                <option value="opportunity" {{ $presetType==='opportunity' ? 'selected' : '' }}>Příležitost</option>
                <option value="project" {{ $presetType==='project' ? 'selected' : '' }}>Projekt</option>
              </select>
              <input type="hidden" name="taskable_id" id="rel-id" value="{{ $presetId }}">
              <input type="text" id="rel-search" class="form-control" placeholder="Vyhledejte související záznam (min. 2 znaky)">
            </div>
            <div id="rel-results" class="list-group mt-1" style="max-height:240px; overflow:auto; display:none;"></div>
            <div id="rel-loading" class="text-muted small mt-1" style="display:none;">
              <i class="ri-loader-4-line spin me-1"></i> Hledám…
            </div>
            <small class="text-muted">Vyberte typ vztahu a konkrétní záznam. ID se doplní automaticky.</small>
          </div>
          <div class="col-12">
            <label class="form-label">Popis</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('tasks.index') }}" class="btn btn-light">Zpět</a>
          <button class="btn btn-primary" type="submit">Uložit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const typeSel = document.getElementById('rel-type');
  const idInput = document.getElementById('rel-id');
  const searchInput = document.getElementById('rel-search');
  const list = document.getElementById('rel-results');
  const loading = document.getElementById('rel-loading');
  let timer;

  function render(items){
    list.innerHTML = '';
    const { companies=[], contacts=[], leads=[], opportunities=[], projects=[] } = items || {};
    const add = (label, type, id)=>{
      const a = document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; a.textContent=label;
      a.addEventListener('click', (e)=>{ e.preventDefault(); typeSel.value = type; idInput.value = id; list.style.display='none'; });
      list.appendChild(a);
    };
    if(!typeSel.value || typeSel.value==='company') companies.forEach(x=> add(`Společnost: ${x.name} (#${x.id})`, 'company', x.id));
    if(!typeSel.value || typeSel.value==='contact') contacts.forEach(x=> add(`Kontakt: ${x.name}${x.email? ' ('+x.email+')':''} (#${x.id})`, 'contact', x.id));
    if(!typeSel.value || typeSel.value==='lead') leads.forEach(x=> add(`Lead: ${x.name}${x.email? ' ('+x.email+')':''} (#${x.id})`, 'lead', x.id));
    if(!typeSel.value || typeSel.value==='opportunity') opportunities.forEach(x=> add(`Příležitost: ${x.name} (#${x.id})`, 'opportunity', x.id));
    if(!typeSel.value || typeSel.value==='project') projects.forEach(x=> add(`Projekt: ${x.name} (#${x.id})`, 'project', x.id));
    list.style.display = list.childElementCount ? 'block' : 'none';
  }

  async function search(q){
    const params = new URLSearchParams({ q });
    if (typeSel.value) params.set('type', typeSel.value);
    const url = `{{ route('search.taskables') }}` + `?${params.toString()}`;
    try{
      loading.style.display='inline';
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if(!res.ok) return render();
      const data = await res.json();
      render(data);
    }catch(e){ render(); }
    finally{ loading.style.display='none'; }
  }

  searchInput.addEventListener('input', ()=>{
    clearTimeout(timer);
    const q = searchInput.value.trim();
  // clear selected ID when user starts typing a new query
  idInput.value = '';
    if(q.length < 2){ list.style.display='none'; return; }
    timer = setTimeout(()=> search(q), 250);
  });
  typeSel.addEventListener('change', ()=>{
  // clear selected ID when type changes
  idInput.value = '';
  const q = searchInput.value.trim(); if(q.length>=2) search(q);
  });
});
</script>
@endpush
