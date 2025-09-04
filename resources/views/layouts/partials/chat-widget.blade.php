@php $chatEnabled = \App\Models\SystemSetting::get('chat.enabled', '0') === '1'; @endphp
@if($chatEnabled)
<style>
[x-cloak]{display:none!important}
.chat-msg{border-radius:10px; padding:8px 10px; background:#fff; border:1px solid rgba(0,0,0,.06)}
.chat-msg-user{background:#e9f3ff; border-color:#d4e7ff}
.chat-msg-assistant{background:#ffffff}
.chat-msg-header{display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px}
.chat-msg-actions .btn{color:#6c757d}
.chat-msg-body{font-size:0.95rem; line-height:1.35}
.chat-msg-body h1,.chat-msg-body h2,.chat-msg-body h3{font-size:1rem; margin:8px 0 6px; font-weight:600}
.chat-msg-body h4,.chat-msg-body h5,.chat-msg-body h6{font-size:0.95rem; margin:6px 0 4px; font-weight:600}
.chat-msg-body p{margin:0 0 6px}
.chat-msg-body ul{margin:0 0 6px 18px}
.chat-msg-body ol{margin:0 0 6px 18px}
.chat-msg-body blockquote{border-left:3px solid #ddd; padding-left:8px; color:#555; margin:6px 0}
.chat-msg-body hr{border:0; border-top:1px dashed rgba(0,0,0,.15); margin:8px 0}
.chat-code{background:#0b1020; color:#dfe7ff; border-radius:8px; padding:8px; overflow:auto; border:1px solid rgba(255,255,255,.08);}
.chat-divider{height:8px}
/* Progress panel */
.chat-progress{border:1px dashed rgba(0,0,0,.2); background:#fffef7; border-radius:10px; padding:8px 10px}
.chat-progress .step{display:flex; align-items:center; gap:8px; margin:4px 0}
.chat-progress .dot{width:10px;height:10px;border-radius:50%}
.chat-progress .dot.init{background:#6c757d}
.chat-progress .dot.crm{background:#0d6efd}
.chat-progress .dot.knowledge{background:#198754}
.chat-progress .dot.llm{background:#6f42c1}
.chat-progress .dot.playwright{background:#fd7e14}
.chat-progress .dot.qdrant{background:#20c997}
.chat-progress .label{font-weight:500}
.spinner{display:inline-block; width:14px; height:14px; border:2px solid rgba(0,0,0,.2); border-top-color: rgba(0,0,0,.6); border-radius:50%; animation:spin 0.8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
<div x-data="chatWidget()" x-cloak x-init="init()" style="position: fixed; right: 20px; bottom: 20px; z-index: 1050;">
    <button class="btn btn-primary rounded-circle shadow" style="width:56px;height:56px;" @click="open = true" title="Chat">
        <i class="ti ti-message"></i>
    </button>

    <div class="offcanvas offcanvas-end position-relative" :class="open ? 'show' : ''" x-show="open" x-cloak x-transition.opacity data-bs-backdrop="false" data-bs-scroll="true" tabindex="-1" :aria-modal="open ? 'true' : 'false'" role="dialog" :style="`width: ${widthPx}px; pointer-events: ${open ? 'auto' : 'none'}`">
        <div class="position-absolute"
             :style="`left:0; top:0; bottom:0; width:8px; cursor: col-resize; background:${isResizing ? 'rgba(0,0,0,0.12)' : 'rgba(0,0,0,0.06)'}; border-right:1px solid rgba(0,0,0,0.25);`"
             @mousedown="startResize($event)"
             title="Táhněte pro změnu šířky"></div>
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Chat</h5>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" @click="newChat()" title="Nový chat">Nový</button>
                <button class="btn btn-sm btn-outline-secondary" @click="toggleSessions()" title="Moje chaty">Chaty</button>
                <button type="button" class="btn-close text-reset" @click="open=false"></button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column" style="height: calc(100vh - 120px);">
            <div x-show="showSessions" class="border rounded p-2 mb-2" style="max-height: 40vh; overflow:auto;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Moje chaty</strong>
                    <button class="btn btn-sm btn-primary" @click="newChat()">Nový chat</button>
                </div>
                <template x-if="sessions.length===0">
                    <div class="text-muted small">Žádné uložené chaty.</div>
                </template>
                <ul class="list-unstyled mb-0">
                    <template x-for="s in sessions" :key="s.id">
                        <li class="mb-1">
                            <button class="btn btn-sm btn-light w-100 text-start" @click="openSession(s.id)">
                                <span x-text="s.title || ('Sezení #' + s.id)"></span>
                                <small class="text-muted" x-text="' · ' + (new Date(s.updated_at||s.created_at)).toLocaleString()"></small>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
            <div class="flex-grow-1 overflow-auto border rounded p-2 mb-2 bg-light" id="chat-log">
                <template x-if="meta && meta.requires_confirmation">
                    <div class="alert alert-warning py-1 px-2 mb-2">
                        Pozor: zpráva zřejmě žádá změnu dat. Asistent nic nemění bez potvrzení. Napište prosím "potvrzuji" nebo upřesněte požadavek.
                    </div>
                </template>
                <template x-if="meta && meta.diagnostics && meta.diagnostics.badges_enabled">
                    <div class="mb-2">
            <span class="badge bg-info me-1" x-text="'Provider: '+(meta.diagnostics.used||meta.diagnostics.provider||'—')"></span>
            <span class="badge bg-secondary me-1" x-text="'Model: '+(meta.diagnostics.used_model||meta.diagnostics.model||'—')"></span>
                        <span class="badge" :class="meta.diagnostics.deterministic ? 'bg-success' : 'bg-dark'" x-text="meta.diagnostics.deterministic ? 'Deterministická odpověď' : 'LLM odpověď'"></span>
                        <span class="badge bg-light text-dark" x-text="(meta.diagnostics.links_same_tab ? 'Odkazy: stejná karta' : 'Odkazy: nová karta')"></span>
                    </div>
                </template>
                <template x-if="meta && (meta.knowledge?.length||0)">
                    <div class="alert alert-info py-1 px-2 mb-2">
                        <div class="small mb-1"><strong>Citované poznámky:</strong></div>
                        <ul class="small mb-0">
                            <template x-for="k in (meta.knowledge||[])" :key="k.id">
                                <li>
                                    <strong x-text="k.title"></strong>
                                    <span class="text-muted" x-text="' · ' + (new Date(k.updated_at)).toLocaleDateString()"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
                <!-- Debug: web snippet and links (collapsible) -->
                <template x-if="meta && meta.debug && (meta.debug.web_snippet || (meta.debug.web_links_top && meta.debug.web_links_top.length))">
                    <div class="border rounded p-2 mb-2 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Debug: Získaná data z webu</strong>
                            <button class="btn btn-sm btn-outline-secondary" @click="meta._showWebDebug = !meta._showWebDebug" x-text="meta._showWebDebug ? 'skrýt' : 'zobrazit'"></button>
                        </div>
                        <div x-show="meta._showWebDebug" class="mt-2 small" x-transition>
                            <div class="mb-1 text-muted">Zdroj: <span x-text="meta.debug.web_source || meta.diagnostics?.url || ''"></span></div>
                            <template x-if="meta.debug.web_snippet">
                                <div>
                                    <div class="fw-semibold">Výřez textu:</div>
                                    <pre class="chat-code" style="white-space: pre-wrap;" x-text="meta.debug.web_snippet"></pre>
                                </div>
                            </template>
                            <template x-if="meta.debug.web_links_top && meta.debug.web_links_top.length">
                                <div>
                                    <div class="fw-semibold">Nalezené odkazy:</div>
                                    <ul class="small mb-0">
                                        <template x-for="(lnk, i) in meta.debug.web_links_top" :key="'wlnk-'+i">
                                            <li><a :href="lnk.href" target="_blank" rel="noopener" x-text="(lnk.text||lnk.href)"></a></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <!-- Debug: co posíláme do LLM (collapsible) -->
                <template x-if="meta && meta.debug && meta.debug.llm">
                    <div class="border rounded p-2 mb-2 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Debug: Kontext pro LLM</strong>
                            <button class="btn btn-sm btn-outline-secondary" @click="meta._showLlmDebug = !meta._showLlmDebug" x-text="meta._showLlmDebug ? 'skrýt' : 'zobrazit'"></button>
                        </div>
                        <div x-show="meta._showLlmDebug" class="mt-2 small" x-transition>
                            <div class="mb-1"><span class="fw-semibold">System prompt:</span>
                                <pre class="chat-code" style="white-space: pre-wrap;" x-text="meta.debug.llm.system || meta.diagnostics?.system_prompt || ''"></pre>
                            </div>
                            <template x-if="meta.debug.llm.context_json || meta.diagnostics?.context_json">
                                <div class="mb-1"><span class="fw-semibold">Ověřený kontext (JSON):</span>
                                    <pre class="chat-code" style="white-space: pre-wrap;" x-text="meta.debug.llm.context_json || meta.diagnostics?.context_json"></pre>
                                </div>
                            </template>
                            <div class="mb-1"><span class="fw-semibold">Dotaz uživatele:</span>
                                <pre class="chat-code" style="white-space: pre-wrap;" x-text="meta.debug.llm.user || ''"></pre>
                            </div>
                        </div>
                    </div>
                </template>
                <!-- Live progress panel -->
                <template x-if="progress && progress.length">
                    <div class="chat-progress mb-2">
            <template x-for="p in progress" :key="p._k">
                            <div class="step">
                                <span class="dot" :class="p.stage"></span>
                                <span class="label" x-text="p.label"></span>
                <span class="text-muted small" x-text="p.status==='start' ? 'probíhá…' : 'hotovo'"></span>
                <span class="small ms-1" x-text="(p.t0 && p.t1) ? formatMs(p.t1 - p.t0) : (p.status==='end' ? '—' : '')"></span>
                                <span x-show="p.status==='start'" class="spinner"></span>
                            </div>
                        </template>
                        <hr class="my-2">
                        <div class="small text-muted">Níže se průběžně vykresluje odpověď asistenta.</div>
                    </div>
                </template>
                <template x-for="(m, idx) in messages" :key="m.id">
                    <div>
                        <div class="chat-divider" x-show="idx>0"></div>
                        <template x-if="m.role==='progress'">
                            <div class="chat-msg chat-msg-assistant">
                                <div class="chat-msg-header">
                                    <span class="badge bg-dark">průběh</span>
                                </div>
                                <div class="chat-msg-body">
                                    <div class="chat-progress">
                                        <template x-for="(p, i) in m.steps" :key="m.id+'-'+i">
                                            <div class="step">
                                                <span class="dot" :class="p.stage"></span>
                                                <span class="label" x-text="p.label"></span>
                                                <span class="text-muted small" x-text="p.status==='start' ? 'probíhá…' : 'hotovo'"></span>
                                                <span class="small ms-1" x-text="(p.t0 && p.t1) ? formatMs(p.t1 - p.t0) : (p.status==='end' ? '—' : '')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="m.role!=='progress'">
                            <div class="chat-msg" :class="m.role==='user' ? 'chat-msg-user' : 'chat-msg-assistant'">
                                <div class="chat-msg-header">
                                    <span class="badge" :class="m.role==='user' ? 'bg-primary' : 'bg-secondary'" x-text="m.role"></span>
                                    <div class="chat-msg-actions" x-show="m.role==='assistant'">
                                        <button class="btn btn-sm btn-link" @click="copyMessage(m)" title="Kopírovat odpověď">
                                            <i class="ri-file-copy-line"></i>
                                            <span class="small" x-show="m._copied" x-transition>zkopírováno</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="chat-msg-body" x-html="renderMessage(m.content)"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="input-group">
                <input x-model="input" type="text" class="form-control" placeholder="Napište dotaz…" @keydown.enter.prevent="send()">
                <button class="btn btn-primary" @click="send()">Odeslat</button>
            </div>
            <template x-if="meta && ((meta.entities?.emails?.length||0) || (meta.entities?.phones?.length||0))">
                <div class="mt-2 small text-muted">
                    <span x-show="meta.entities?.emails?.length">E‑maily: <span x-text="meta.entities.emails.join(', ')"></span></span>
                    <span x-show="meta.entities?.emails?.length && meta.entities?.phones?.length"> · </span>
                    <span x-show="meta.entities?.phones?.length">Telefony: <span x-text="meta.entities.phones.join(', ')"></span></span>
                </div>
            </template>
        </div>
    </div>
</div>
<script>
function chatWidget(){
    return {
    open:false, sessionId:null, lastMessageId:null, input:'', messages:[], meta:null, streamingAssistant:null, sending:false, error:null,
        progress:[], _progressMsgId:null,
        isResizing:false,
        widthPx: 420, minWidth: 320, maxWidth: 1000, _onMouseMove:null, _onMouseUp:null, _startX:0, _startW:0,
        showSessions:false, sessions:[],
    _saveTimer:null,
        init(){
            this.open = false;
            // Expose instance for global helpers
            window.__chatWidget = this;
            // Load stored width
            try{ const w = parseInt(localStorage.getItem('crm.chat.width')||''); if(w && !isNaN(w)) { this.widthPx = Math.max(this.minWidth, Math.min(this.maxWidth, w)); } }catch(_){ }
            // Try restore after navigation
            this.restoreIfAny();
        },
        async copyMessage(m){
            const text = m && m.content ? m.content : '';
            if(!text) return;
            try{
                if(navigator.clipboard && navigator.clipboard.writeText){
                    await navigator.clipboard.writeText(text);
                }else{
                    const ta = document.createElement('textarea');
                    ta.value = text; ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                }
                this.$nextTick(()=>{ m._copied = true; setTimeout(()=>{ m._copied = false; }, 1200); });
            }catch(_){ /* ignore */ }
        },
        startResize(e){
            this.isResizing = true;
            this._startX = e.clientX; this._startW = this.widthPx;
            this._onMouseMove = (ev)=>{
                let dx = this._startX - ev.clientX; // drag left increases width
                let maxW = Math.min(this.maxWidth, window.innerWidth - 60);
                let w = this._startW + dx; if(w < this.minWidth) w = this.minWidth; if(w > maxW) w = maxW;
                this.widthPx = Math.round(w);
                try{ localStorage.setItem('crm.chat.width', String(this.widthPx)); }catch(_){ }
            };
            this._onMouseUp = ()=>{
                this.isResizing = false;
                if(this._onMouseMove){ document.removeEventListener('mousemove', this._onMouseMove); }
                if(this._onMouseUp){ document.removeEventListener('mouseup', this._onMouseUp); }
                this._onMouseMove = null; this._onMouseUp = null;
            };
            document.addEventListener('mousemove', this._onMouseMove);
            document.addEventListener('mouseup', this._onMouseUp);
            e.preventDefault();
        },
        async ensureSession(){
            if(this.sessionId) return;
            const headers = this.buildHeaders();
            const res = await fetch('/crm/chat/sessions',{method:'POST', headers, body: JSON.stringify({})});
            const data = await res.json(); this.sessionId = data.session_id;
            this.fetchSessions();
        },
        toggleSessions(){ this.showSessions = !this.showSessions; if(this.showSessions){ this.fetchSessions(); } },
        async fetchSessions(){
            try{
                const headers = this.buildHeaders();
                const res = await fetch('/crm/chat/sessions',{headers});
                if(!res.ok) return; const data = await res.json();
                const arr = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
                this.sessions = arr;
            }catch(_){ /* ignore */ }
        },
        async openSession(id){
            this.sessionId = id; this.showSessions = false; this.messages = []; this.meta = null; this.lastMessageId = null;
            await this.loadMessagesForSession(id);
            this.scheduleSave(true);
        },
        async newChat(){
            const headers = this.buildHeaders();
            const res = await fetch('/crm/chat/sessions',{method:'POST', headers, body: JSON.stringify({})});
            const data = await res.json(); this.sessionId = data.session_id; this.messages=[]; this.meta=null; this.lastMessageId=null; this.input='';
            this.showSessions=false; this.open = true; this.fetchSessions(); this.scheduleSave(true);
        },
        async send(){
            if(!this.input.trim() || this.sending) return; this.sending=true; this.error=null; await this.ensureSession();
            const userMsg = {id:Date.now(), role:'user', content:this.input}; this.messages.push(userMsg);
            this.scheduleSave();
            try{
                const headers = this.buildHeaders();
                const res = await fetch('/crm/chat/messages',{method:'POST', headers, body: JSON.stringify({session_id:this.sessionId, content:this.input})});
                if(!res.ok){ throw new Error('HTTP '+res.status); }
                this.input=''; const data = await res.json(); this.lastMessageId = data.message_id;
            }catch(e){ this.error = 'Odeslání se nezdařilo ('+(e?.message||'chyba')+'). Zkuste to prosím znovu.'; this.sending=false; return; }
            // Insert persistent progress message BEFORE assistant answer
            const pm = { id: 'progress-'+this.lastMessageId, role:'progress', steps: [] };
            this.messages.push(pm); this._progressMsgId = pm.id;
            // create a single assistant message placeholder for streaming
            this.streamingAssistant = {id: 'assistant-'+this.lastMessageId, role:'assistant', content:''};
            this.messages.push(this.streamingAssistant);
            this.scheduleSave();
            this.stream();
        },
        stream(){
            // reset progress
            this.progress = [];
            const src = new EventSource(`/crm/chat/stream?session_id=${this.sessionId}&message_id=${this.lastMessageId}`);
            src.addEventListener('meta', (e)=>{ try{ const m = JSON.parse(e.data); this.meta = Object.assign({}, this.meta||{}, m); window.__chatMeta = this.meta; if(Array.isArray(this.meta?.progress_pre)){ const now = Date.now(); this.progress = this.meta.progress_pre.map((p,i)=>({...p, _k: now+'-pre-'+i, t0:null, t1:null})); this.updateProgressMessage(); } }catch(_){ /* ignore */ } });
            src.addEventListener('delta', (e)=>{ const d = JSON.parse(e.data); this.appendAssistant(d.text); });
            src.addEventListener('progress', (e)=>{ try{ const p = JSON.parse(e.data); const now = Date.now(); p._k = now+'-'+(p.stage||''); // upsert by stage
                const idx = this.progress.findIndex(x=>x.stage===p.stage);
                if(idx>=0){ const prev = this.progress[idx]; p.t0 = (p.status==='start') ? (prev.t0 || now) : (prev.t0 || null); p.t1 = (p.status==='end') ? now : (prev.t1 || null); this.progress[idx] = {...prev, ...p}; }
                else { p.t0 = (p.status==='start') ? now : null; p.t1 = (p.status==='end') ? now : null; this.progress.push(p); }
                this.updateProgressMessage(); this.scheduleSave(); }catch(_){ /* ignore */ } });
            src.addEventListener('done', ()=>{ src.close(); this.finishAssistant(); });
            src.onerror = ()=>{ src.close(); this.finishAssistant(); };
        },
        updateProgressMessage(){
            if(!this._progressMsgId) return;
            const i = this.messages.findIndex(m=>m.id===this._progressMsgId && m.role==='progress');
            if(i>=0){ this.messages[i].steps = this.progress.map(p=>({stage:p.stage, status:p.status, label:p.label, t0:p.t0||null, t1:p.t1||null})); }
        },
        appendAssistant(t){
            if(!this.streamingAssistant){
                this.streamingAssistant = {id: 'assistant-'+(this.lastMessageId ?? Date.now()), role:'assistant', content:''};
                this.messages.push(this.streamingAssistant);
            }
            this.streamingAssistant.content += t ?? '';
            const log = document.getElementById('chat-log'); if(log){ log.scrollTop = log.scrollHeight; }
            this.scheduleSave();
        },
        finishAssistant(){
            // finalize live progress snapshot into the existing progress message
            this.updateProgressMessage();
            this.streamingAssistant = null; this.sending=false; this.scheduleSave(true);
            // Clear live progress panel (history stays in messages)
            this.progress = [];
            this._progressMsgId = null;
        },
        formatMs(ms){ try{ if(!ms || ms<1) return '—'; if(ms<1000) return ms.toFixed(0)+' ms'; const s = ms/1000; return (s<10 ? s.toFixed(2) : s<60 ? s.toFixed(1) : Math.round(s)+' s'); }catch(_){ return '—'; } },
        async loadMessagesForSession(sessionId){
            try{
                const headers = this.buildHeaders();
                const res = await fetch(`/crm/chat/sessions/${encodeURIComponent(sessionId)}/messages`, {headers});
                if(!res.ok) return;
                const data = await res.json();
                // Accept either plain array or Laravel paginator { data: [...] }
                const arr = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
                this.messages = arr.map(m=>({
                    id: m.id || m.message_id || (Date.now()+Math.random()),
                    role: m.role || m.author || 'assistant',
                    content: m.content || m.text || ''
                }));
                this.scheduleSave();
            }catch(_){ /* ignore */ }
        },
        persistStateAndNavigate(url){
            this.saveStateNow();
            window.location.href = url;
        },
        restoreIfAny(){
            try{
                const raw = sessionStorage.getItem('crm.chat.state'); if(!raw) return;
                sessionStorage.removeItem('crm.chat.state');
                const st = JSON.parse(raw);
                if(!st || !st.sessionId) return;
                // Optional: ignore if too old (>30min)
                if(st.ts && (Date.now() - st.ts) > 30*60*1000) return;
                this.sessionId = st.sessionId; this.open = st.open === true; this.input = st.input || '';
                // Restore messages immediately for seamless UX
                if(Array.isArray(st.messages)){
                    this.messages = st.messages.map(m=>({id:m.id||Date.now()+Math.random(), role:m.role||'assistant', content:m.content||''}));
                }
                if(st.meta){ this.meta = st.meta; window.__chatMeta = st.meta; }
                this.loadMessagesForSession(this.sessionId);
                // Also restore meta in badge if available later via new stream; otherwise leave as-is
            }catch(_){ /* ignore */ }
        },
        saveStateNow(){
            try{
                const snapshotMsgs = (this.messages||[]).slice(-100).map(m=>({id:m.id, role:m.role, content:m.content}));
                const state = {v:2, ts: Date.now(), sessionId: this.sessionId, open: this.open, input: this.input, messages: snapshotMsgs, meta: this.meta };
                sessionStorage.setItem('crm.chat.state', JSON.stringify(state));
            }catch(_){ /* ignore */ }
        },
        scheduleSave(immediate=false){
            if(immediate){ this.saveStateNow(); return; }
            if(this._saveTimer){ clearTimeout(this._saveTimer); }
            this._saveTimer = setTimeout(()=>{ this.saveStateNow(); this._saveTimer=null; }, 400);
        },
        buildHeaders(){
            const h = {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'};
            const meta = document.querySelector('meta[name="csrf-token"]');
            if(meta && meta.content){ h['X-CSRF-TOKEN'] = meta.content; return h; }
            // Try cookie XSRF-TOKEN
            const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
            if(m){ try{ h['X-XSRF-TOKEN'] = decodeURIComponent(m[1]); }catch(_){ /* ignore */ } }
            return h;
        }
    }
}
// Render assistant/user message with lightweight Markdown and smart links
function renderMessage(text){
    if(!text) return '';
    // Extract code blocks first
    const codeBlocks = [];
    text = String(text).replace(/```([\s\S]*?)```/g, (m, code)=>{
        codeBlocks.push(code);
        return `\uE000${codeBlocks.length-1}\uE001`;
    });
    // Escape HTML
    const esc = (s)=>s.replace(/[&<>]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;"}[c]));
    let t = esc(text);
    // Lines processing
    const lines = t.split(/\r?\n/);
    let html = '';
    let inUL = false, inOL = false;
    const closeLists = ()=>{ if(inUL){ html += '</ul>'; inUL=false; } if(inOL){ html += '</ol>'; inOL=false; } };
    for(let i=0;i<lines.length;i++){
        let line = lines[i];
        if(/^\s*$/.test(line)){ closeLists(); html += ''; continue; }
        // hr
        if(/^\s*(?:-{3,}|_{3,}|\*{3,})\s*$/.test(line)){ closeLists(); html += '<hr>'; continue; }
        // headings
        let m;
        if((m = line.match(/^\s*#{1,6}\s+(.*)$/))){ closeLists(); const level = (line.match(/^\s*(#{1,6})/)[1].length); html += `<h${level}>${m[1]}</h${level}>`; continue; }
        // blockquote
        if((m = line.match(/^\s*>\s?(.*)$/))){ closeLists(); html += `<blockquote>${m[1]}</blockquote>`; continue; }
        // lists
        if((m = line.match(/^\s*[-*]\s+(.*)$/))){ if(inOL){ html += '</ol>'; inOL=false; } if(!inUL){ html += '<ul>'; inUL=true; } html += `<li>${m[1]}</li>`; continue; }
        if((m = line.match(/^\s*\d+\.\s+(.*)$/))){ if(inUL){ html += '</ul>'; inUL=false; } if(!inOL){ html += '<ol>'; inOL=true; } html += `<li>${m[1]}</li>`; continue; }
        // paragraph-ish line (apply inline formatting)
        closeLists();
        line = line.replace(/`([^`]+)`/g, '<code>$1</code>');
        line = line.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        line = line.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        // Linkify URLs inside the escaped line
        line = linkifyEscaped(line);
        html += `<p>${line}</p>`;
    }
    closeLists();
    // Restore code blocks
    html = html.replace(/\uE000(\d+)\uE001/g, (_m, idx)=>{
        const code = esc(codeBlocks[Number(idx)]||'');
        return `<pre class="chat-code"><code>${code}</code></pre>`;
    });
    return html;
}
// Linkify for already-escaped text (used inside renderMessage)
function linkifyEscaped(escapedText){
    if(!escapedText) return '';
    const urlRe = /(https?:\/\/[^\s)]+)|(\/[a-zA-Z0-9_\-\/]+\/[0-9]+)/g;
    const sameTab = (window.__chatMeta && window.__chatMeta.diagnostics && window.__chatMeta.diagnostics.links_same_tab) === true;
    return escapedText.replace(urlRe, (m)=>{
        const isHttp = m.startsWith('http');
        let url = m;
        if(!isHttp){
            // make relative absolute for consistent handling
            try{ url = new URL(m, window.location.origin).toString(); }catch(_){ url = m; }
        }
        // Identify internal CRM contact links specifically
        let isInternalContact = false;
        try{
            const u = new URL(url);
            if(u.origin === window.location.origin && /^\/crm\/contacts\/\d+/.test(u.pathname)){
                isInternalContact = true;
            }
        }catch(_){ /* ignore */ }

        if(isHttp && !isInternalContact){
            return `<a href="${url}" target="_blank" rel="noopener noreferrer">${m}</a>`;
        }
        const target = sameTab ? '_self' : '_blank';
        const rel = sameTab ? '' : ' rel="noopener noreferrer"';
        const btn = `<button class=\"btn btn-sm btn-link p-0 ms-1 align-baseline\" title=\"Otevřít zde (bez ztráty chatu)\" onclick=\"chatOpenHere('${url.replace(/'/g, "&#39;")}'); return false;\">↪</button>`;
        return `<a href="${url}" target="${target}"${rel}>${m}</a>${btn}`;
    });
}
// Simple URL to anchor converter for assistant messages
function linkify(text){
    if(!text) return '';
    const esc = (s)=>s.replace(/[&<>]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;"}[c]));
    const urlRe = /(https?:\/\/[^\s)]+)|(\/crm\/contacts\/[0-9]+)/g;
    const sameTab = (window.__chatMeta && window.__chatMeta.diagnostics && window.__chatMeta.diagnostics.links_same_tab) === true;
    return esc(text).replace(urlRe, (m)=>{
        const isHttp = m.startsWith('http');
        let url = isHttp ? m : (window.location.origin + m);
        // Decide if this is an internal CRM contact link (even when absolute)
        let isInternalContact = false;
        try{
            const u = new URL(url);
            if(u.origin === window.location.origin && /^\/crm\/contacts\/\d+/.test(u.pathname)){
                isInternalContact = true;
            }
        }catch(_){ /* ignore URL parse errors */ }

        if(isHttp && !isInternalContact){
            // External links always new tab
            return `<a href="${url}" target="_blank" rel="noopener noreferrer">${m}</a>`;
        }
        // Internal CRM contact link: respect setting and add inline open-here button
        const target = sameTab ? '_self' : '_blank';
        const rel = sameTab ? '' : ' rel="noopener noreferrer"';
        const btn = `<button class=\"btn btn-sm btn-link p-0 ms-1 align-baseline\" title=\"Otevřít zde (bez ztráty chatu)\" onclick=\"chatOpenHere('${url.replace(/'/g, "&#39;")}'); return false;\">↪</button>`;
        return `<a href="${url}" target="${target}"${rel}>${m}</a>${btn}`;
    });
}
// Global helper callable from rendered message HTML
window.chatOpenHere = function(url){
    try{
        const inst = window.__chatWidget; if(inst && typeof inst.persistStateAndNavigate === 'function'){ inst.persistStateAndNavigate(url); return; }
    }catch(_){ /* ignore */ }
    window.location.href = url;
}
</script>
@endif
