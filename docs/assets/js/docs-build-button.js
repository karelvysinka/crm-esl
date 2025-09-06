// Inject Build Docs button into MkDocs top nav (only visible to authenticated CRM users via iframe/proxy context if cookie present)
(function(){
  try {
    const nav = document.querySelector('header.md-header__inner');
    if(!nav) return;
    if(document.getElementById('build-docs-btn')) return;
    const btn = document.createElement('button');
    btn.id = 'build-docs-btn';
    btn.textContent = 'Build Docs';
    btn.style.marginLeft = '1rem';
    btn.className = 'md-button md-button--primary';
    btn.onclick = async () => {
      btn.disabled = true; const original = btn.textContent; btn.textContent='Building...';
      try {
        const token = Math.random().toString(36).slice(2);
        const res = await fetch('/crm/ops/actions/docs-build', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.csrfToken||''},
          body:'_ops_token='+encodeURIComponent(token)+'&_token='+(window.csrfToken||'')
        });
        if(res.ok){ btn.textContent='Done'; setTimeout(()=>{btn.textContent=original; btn.disabled=false;},2000);} else { btn.textContent='Error'; setTimeout(()=>{btn.textContent=original; btn.disabled=false;},4000);}      
      } catch(e){ console.error(e); btn.textContent='Fail'; setTimeout(()=>{btn.textContent='Build Docs'; btn.disabled=false;},4000); }
    };
    nav.appendChild(btn);
  } catch(e){ console.warn('Docs build button inject failed', e); }
})();
