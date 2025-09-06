// Inject Build Docs button into MkDocs top nav (only visible to authenticated CRM users via iframe/proxy context if cookie present)
(function(){
  const TRY_MS_TOTAL = 5000;
  const INTERVAL = 200;
  let waited = 0;

  function hasSessionCookie(){
    return document.cookie.split(';').some(c=>c.trim().startsWith('laravel_session='));
  }

  function insert(){
    if(document.getElementById('build-docs-btn')) return true;
    // Prefer right side actions container if present
    let container = document.querySelector('header .md-header__inner .md-header__option');
    if(!container) container = document.querySelector('header .md-header__inner');
    if(!container) return false;
    const btn = document.createElement('button');
    btn.id = 'build-docs-btn';
    btn.type = 'button';
    btn.textContent = 'Build Docs';
    btn.style.marginLeft = '1rem';
    btn.className = 'md-button md-button--primary';
    btn.title = 'Spustí regeneraci dokumentace (vyžaduje přihlášení v /crm)';
    btn.addEventListener('click', async () => {
      // Pokud není session cookie, jen otevřeme CRM Ops v novém tabu
      if(!hasSessionCookie()) { window.open('/crm/ops','_blank'); return; }
      const original = btn.textContent; btn.disabled=true; btn.textContent='Building…';
      const token = Math.random().toString(36).slice(2);
      try {
        const res = await fetch('/crm/ops/actions/docs-build', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.csrfToken||''},
          body:'_ops_token='+encodeURIComponent(token)+'&_token='+(window.csrfToken||'')
        });
        if(res.ok){ btn.textContent='OK'; }
        else { btn.textContent='Chyba ('+res.status+')'; }
      } catch(err){ console.error('Docs build call failed', err); btn.textContent='Fail'; }
      setTimeout(()=>{ btn.disabled=false; btn.textContent=original; }, 3000);
    });
    container.appendChild(btn);
    return true;
  }

  function tryInsert(){
    if(insert()) return; // success
    waited += INTERVAL;
    if(waited < TRY_MS_TOTAL) setTimeout(tryInsert, INTERVAL);
  }

  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInsert);
  } else {
    tryInsert();
  }
})();
