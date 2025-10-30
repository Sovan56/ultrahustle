{{-- resources/views/forum.blade.php --}}
@include('common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('rebuildfrontend/css/forum.css') }}">

<script>
  (function(w){ if(!w.jQuery){ w.__needsNiceScrollShim__=true; }
  else if(!jQuery.fn.niceScroll){ jQuery.fn.niceScroll=function(){ return this; }; } })(window);
</script>

<style>
  /* Small toast that matches your theme */
  .uh-toast {
    position: fixed; right: 18px; bottom: 18px; z-index: 9999;
    display: flex; align-items: center; gap: 10px;
    background: #0b0b0b; border: 1px solid var(--uh-border,#1a1a1a);
    color: #fff; padding: 10px 14px; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.6);
    transform: translateY(20px); opacity: 0; pointer-events: none; transition: .2s ease;
    font-family:'Roboto Slab',serif;
  }
  .uh-toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
  .uh-toast .uh-dot { width:10px; height:10px; border-radius:50%; background: var(--uh-neon,#CEFF1B); box-shadow:0 0 12px rgba(206,255,27,.6); }
  .uh-toast.error .uh-dot { background:#ff4d4f; box-shadow:0 0 12px rgba(255,77,79,.6); }
  .uh-toast .uh-txt { font-size: 13px; }
</style>

<section id="ultraHustleForum" class="uh-root">
  <!-- Frame -->
  <div class="uh-frame">
    <!-- 3-column layout -->
    <div class="uh-grid">
      <!-- Left sidebar -->
      <aside class="uh-left">
        <div class="uh-cat-head">
          <h3>Categories</h3>
          <i class="fa-solid fa-filter"></i>
        </div>
        <nav id="uhCatNav" class="uh-catnav"></nav>

         <nav class="uh-catnav">
  <a class="uh-catlink"
     href="{{ route('admin.forum.page') }}"
     onclick="return handleForumClick(event)">
    <span>My Threads</span>
  </a>
</nav>

<script>
  // Inject auth state from server
  const IS_AUTH = @json(auth()->check());

  function handleForumClick(event) {
    // If user is not authenticated, stop link navigation and open login
    if (!IS_AUTH) {
      event.preventDefault();
      if (typeof goHomeAndOpenLogin === 'function') {
        goHomeAndOpenLogin();
      }
      return false;
    }
    return true;
  }
</script>




        <div class="uh-cta">
          <h4>Ready to Share?</h4>
          <p>Start a thread and connect with fellow creators</p>
          <button class="uh-btn uh-btn-neon" id="uhStartThreadBtn">Start New Thread</button>
        </div>
      </aside>

      <!-- Main feed -->
      <main class="uh-main">
        <!-- Composer -->
        <section class="uh-composer" id="uhComposer">
          <div class="uh-composer-top">
            <div class="uh-avatar" id="uhUserAva">U</div>
            <div class="uh-composer-pill">Start a new thread</div>
          </div>
          <div class="uh-composer-actions" style="margin-bottom:8px">
            <button class="uh-chip" data-mode="text"><i class="fa-regular fa-rectangle-list"></i> Text</button>
            <button class="uh-chip" data-mode="image"><i class="fa-regular fa-image"></i> Photo</button>
            <button class="uh-chip" data-mode="video"><i class="fa-solid fa-video"></i> Video</button>
            <button class="uh-chip" data-mode="poll"><i class="fa-solid fa-chart-simple"></i> Poll</button>
          </div>

          <!-- Minimal composer form (keeps your layout) -->
          <form id="uhPostForm" class="_hide">
            <div style="display:grid; gap:8px; margin-top:8px">
              <input type="text" class="uh-composer-pill" name="title" placeholder="Title" required>
              <select class="uh-composer-pill" name="category" id="uhCategorySelect" required style="appearance:none; background:#101010">
  <option value="" disabled selected>Select category</option>
</select>
<script>
  function populateCategorySelect(){
  const sel = uh('#uhCategorySelect');
  if (!sel) return;
  // clear and rebuild
  sel.innerHTML = `<option value="" disabled selected>Select category</option>`;
  UH_CATEGORIES.forEach(c=>{
    const opt = document.createElement('option');
    opt.value = c.name;       // backend expects name (you resolve to id server-side)
    opt.textContent = c.name;
    sel.appendChild(opt);
  });
}

</script>
              <textarea class="uh-composer-pill" name="body_html" placeholder="Write something…" rows="3" style="border-radius: 10px;"></textarea>

              <!-- Image mode -->
              <div id="uhImgRow" class="_hide" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                <input type="file" accept="image/*" id="uhImgFile" hidden>
                <button type="button" class="uh-chip" id="uhImgPick"><i class="fa-regular fa-image"></i> Choose image</button>
                <input type="text" class="uh-composer-pill" id="uhImgUrl" placeholder="…or paste image URL">
                <input type="text" class="uh-composer-pill" name="media_alt" placeholder="Alt text (optional)">
                <img id="uhImgPreview" style="max-height:120px; border-radius:12px; border:1px solid var(--uh-border)" class="_hide">
              </div>

              <!-- Video mode -->
              <div id="uhVidRow" class="_hide" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                <input type="file" accept="video/*" id="uhVidFile" hidden>
                <button type="button" class="uh-chip" id="uhVidPick"><i class="fa-solid fa-video"></i> Choose video</button>
                <input type="text" class="uh-composer-pill" id="uhVidUrl" placeholder="…or paste video URL (MP4)">
                <input type="text" class="uh-composer-pill" id="uhVidPoster" placeholder="Poster image URL (optional)">
              </div>

              <!-- Poll mode -->
              <div id="uhPollRow" class="_hide" style="display:grid; gap:8px">
                <div style="display:flex; gap:8px; flex-wrap:wrap">
                  <button type="button" class="uh-chip" id="uhAddPollOpt"><i class="fa-solid fa-plus"></i> Add option</button>
                  <label class="uh-chip" style="cursor:pointer">
                    <input type="checkbox" id="uhPollMultiple" style="accent-color:black; margin-right:6px"> Allow multiple
                  </label>
                </div>
                <div id="uhPollOpts" style="display:grid; gap:6px"></div>
              </div>

              <div style="display:flex; gap:8px; justify-content:flex-end">
                <button type="button" class="uh-chip" id="uhCancelPost">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-neon">Post</button>
              </div>
            </div>
          </form>
        </section>

        <!-- Latest Threads -->
        <section class="uh-latest">
          <h2>Latest Threads</h2>
          <p class="uh-muted">Connect, learn, and grow with the creator community</p>
          <ul id="uhThreads" class="uh-thread-list"></ul>
          <div id="uhFeedEnd" style="height: 48px;"></div>
        </section>
      </main>

      <!-- Right sidebar -->
      <aside class="uh-right">
        <section class="uh-card">
          <h4>Top Threads This Week</h4>
          <ul id="uhTopThreads" class="uh-toplist"></ul>
        </section>

        <section class="uh-card">
          <h4>Recently Visited</h4>
          <ul id="uhRecent" class="uh-recentlist">
            <li class="uh-empty">No recent threads yet.</li>
          </ul>
        </section>

        <section class="uh-card">
          <h4><i class="fa-solid fa-tag"></i> Explore Categories</h4>
          <div id="uhExploreCats" class="uh-catchips"></div>
        </section>
      </aside>
    </div>
  </div>
</section>

<!-- Toast -->
<div id="uhToast" class="uh-toast"><span class="uh-dot"></span><span class="uh-txt">Saved</span></div>

<script>
/* ================= ULTRA HUSTLE — CONFIG ================= */
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const ROUTES = {
  list:      '{{ route('forum.list') }}',
  show:      '{{ url('/forum') }}/',
  store:     '{{ route('forum.store') }}',
  like:      (id)=> '{{ url('/forum') }}/'+id+'/like',
  save:      (id)=> '{{ url('/forum') }}/'+id+'/save',
  share:     (id)=> '{{ url('/forum') }}/'+id+'/share',
  upload:    '{{ route('forum.upload') }}',
  cats:      '{{ route('forum.categories') }}', // <— add this line
};

/* ================= DATA + STATE ================= */
let UH_PAGE_NEXT = null;
let UH_LOADING   = false;
let UH_THREADS   = []; // unified array for UI (cards)
const UH_EXPLORE = ["Feedback","Showcase","Tips","Jobs","Announcements","AI Tools","Off-Topic"];
const UH_CATS    = ["All","Community","Explore","Recent","Popular","Saved"]; // left nav (visual)

/* Category color map (server may also send) */
const UH_CAT_STYLE = {
  "Showcase":     {bg:"rgba(167,139,250,.12)", fg:"#E9D5FF", border:"#A78BFA"},
  "Jobs":         {bg:"rgba(74,222,128,.12)",  fg:"#BBF7D0", border:"#4ADE80"},
  "Question":     {bg:"rgba(250,204,21,.12)",  fg:"#FEF08A", border:"#FACC15"},
  "Feedback":     {bg:"rgba(110,231,183,.12)", fg:"#A7F3D0", border:"#34D399"},
  "AI Tools":     {bg:"rgba(147,197,253,.12)", fg:"#BFDBFE", border:"#93C5FD"},
  "Announcements":{bg:"rgba(253,186,116,.12)", fg:"#FED7AA", border:"#FDBA74"},
  "Off-Topic":    {bg:"rgba(212,212,212,.10)", fg:"#E5E7EB", border:"#A3A3A3"},
  "General":      {bg:"rgba(212,212,212,.10)", fg:"#E5E7EB", border:"#A3A3A3"},
};

let UH_USER_SCROLLED = false;     // ← new
const UH_SCROLL_ARM_DISTANCE = 30; // px the user must scroll before we arm auto-loading


let UH_SEEN_IDS = new Set();
function dedupePush(arr){
  const added = [];
  for(const t of arr){
    if(!UH_SEEN_IDS.has(t.id)){
      UH_SEEN_IDS.add(t.id);
      UH_THREADS.push(t);
      added.push(t);
    }
  }
  return added; // return only the truly new ones
}


/* Utils */
const uh = (sel, root=document) => root.querySelector(sel);
const uhAll = (sel, root=document) => Array.from(root.querySelectorAll(sel));
const uhInitial = (name) => (name?.replace(/[^a-zA-Z0-9]/g,"").trim()[0] || "U").toUpperCase();
const uhFmt = (n) => n>=1_000_000? (n/1_000_000).toFixed(n%1_000_000?1:0)+"M"
                : n>=1_000? (n/1_000).toFixed(n%1_000?1:0)+"K" : String(n);
function toast(msg, type='ok'){
  const t = uh('#uhToast');
  t.querySelector('.uh-txt').textContent = msg;
  t.classList.toggle('error', type==='error');
  t.classList.add('show');
  clearTimeout(t._h);
  t._h = setTimeout(()=> t.classList.remove('show'), 1800);
}

/* ================= LEFT NAV (visual) ================= */
let UH_CATEGORIES = []; // from backend

async function fetchCategories(){
  try{
    const res = await fetch(ROUTES.cats, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    if(!res.ok) throw new Error('cats failed');
    const json = await res.json();
    UH_CATEGORIES = json.data || [];
    // merge colors into map used by pills
    UH_CATEGORIES.forEach(c=>{
      if(!UH_CAT_STYLE[c.name]) UH_CAT_STYLE[c.name] = {
        bg: c.colors?.bg || "rgba(212,212,212,.10)",
        fg: c.colors?.fg || "#E5E7EB",
        border: c.colors?.border || "#A3A3A3",
      };
    });
  }catch(e){ console.warn('categories load failed', e); }
}



function renderLeftCats(){
  const nav = uh('#uhCatNav');
  nav.innerHTML = ''; // reset

  const frag = document.createDocumentFragment();

  // Build list: All + categories (from DB) + Saved (fixed)
  const items = [{ name: 'All', slug: 'all', type:'all' }, ...UH_CATEGORIES.map(c=>({name:c.name, slug:c.slug, type:'cat'}))];

  items.forEach((c, i)=>{
    const a = document.createElement('a');
    a.href = "#";
    a.className = "uh-catlink"+(i===0?" active":"");
    a.dataset.type = c.type;         // 'all' or 'cat'
    a.dataset.cat  = c.type==='cat' ? c.name : ''; // pass name to backend
    a.innerHTML = `<span>${c.name}</span>${i===0?`<span class="uh-active-tag" style="color: var(--uh-neon);font-size:10px;letter-spacing:.4px;">ACTIVE</span>`:""}`;
    a.addEventListener('click', (e)=>{ 
      e.preventDefault();
      uhAll('.uh-catnav a').forEach(n=>{
        n.classList.remove('active');
        const tag = n.querySelector('.uh-active-tag'); if (tag) tag.remove();
      });
      a.classList.add('active');
      a.insertAdjacentHTML('beforeend', `<span class="uh-active-tag" style="color: var(--uh-neon);font-size:10px;letter-spacing:.4px;">ACTIVE</span>`);

      // set filters
      UH_FILTER.saved = false;
      UH_FILTER.category = a.dataset.type === 'cat' ? a.dataset.cat : null; // null => All
      resetAndLoad();
    });
    frag.appendChild(a);
  });

  // ---- Saved (bottom) ----
  const savedA = document.createElement('a');
  savedA.href="#";
  savedA.className = "uh-catlink";
  savedA.dataset.type = "saved";
  savedA.innerHTML = `<span>Saved</span>`;
  savedA.addEventListener('click', (e)=>{
    e.preventDefault();
    uhAll('.uh-catnav a').forEach(n=>{
      n.classList.remove('active');
      const tag = n.querySelector('.uh-active-tag'); if (tag) tag.remove();
    });
    savedA.classList.add('active');
    savedA.insertAdjacentHTML('beforeend', `<span class="uh-active-tag" style="color: var(--uh-neon);font-size:10px;letter-spacing:.4px;">ACTIVE</span>`);
    UH_FILTER.category = null;
    UH_FILTER.saved = true;
    resetAndLoad();
  });
  frag.appendChild(savedA);

  nav.appendChild(frag);
}




/* ================= RIGHT EXPLORE FILTER (actual filter) ================= */
let UH_FILTER = { category: null, q: '', saved: false };


(function renderExplore(){
  const host = uh('#uhExploreCats');
  host.innerHTML = UH_EXPLORE.map(c=>`<button class="uh-chip" data-cat="${c}">${c}</button>`).join("");
  host.addEventListener('click', (e)=>{
    const btn = e.target.closest('.uh-chip');
    if(!btn) return;
    const cat = btn.dataset.cat;
    UH_FILTER.category = cat;
    // Visual highlight
    uhAll('#uhExploreCats .uh-chip').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    // Reload feed
    resetAndLoad();
  });
})();

/* ================= RECENT VISITS ================= */
const UH_RECENT = [];
function pushRecent(tid, title){
  const filtered = UH_RECENT.filter(x=>x.id!==tid);
  UH_RECENT.length = 0;
  UH_RECENT.push({id:tid, title, ts: Date.now()}, ...filtered);
  if (UH_RECENT.length>3) UH_RECENT.length = 3;
  renderRecent();
}
function fmtAgo(ms){
  const diff = Math.max(0, Date.now()-ms);
  const m = Math.floor(diff/60000);
  if(m<1) return "now";
  if(m<60) return `${m}m`;
  const h = Math.floor(m/60);
  if(h<24) return `${h}h`;
  return `${Math.floor(h/24)}d`;
}
function renderRecent(){
  const host = uh('#uhRecent');
  if(!UH_RECENT.length){ host.innerHTML = `<li class="uh-empty">No recent threads yet.</li>`; return; }
  host.innerHTML = UH_RECENT.map(r=>`
    <li class="uh-recentrow"><span class="uh-rtitle">${r.title}</span><span class="uh-muted">${fmtAgo(r.ts)} ago</span></li>
  `).join("");
}

/* ================= TOP THREADS ================= */
function renderTopThreads(){
  const top = [...UH_THREADS].sort((a,b)=>b.commentCount-a.commentCount).slice(0,5);
  const host = uh('#uhTopThreads');
  host.innerHTML = top.map(t=>`
    <li>
      <a href="${ROUTES.show}${t.id}" data-thread="${t.id}">${t.title}</a>
      <span class="uh-muted"><i class="fa-regular fa-message"></i> ${t.commentCount}</span>
    </li>
  `).join("");
}

/* ================= CARD RENDERING ================= */
function catPill(category){
  const s = UH_CAT_STYLE[category] || {bg:"rgba(255,255,255,.06)", fg:"#E5E7EB", border:"#9CA3AF"};
  return `<span class="uh-catpill" style="background:${s.bg};color:${s.fg};border-color:${s.border}">${category}</span>`;
}
function avatarHtml(author, avatarUrl){
  if (avatarUrl) {
    return `<div class="uh-a-ava" style="background:#0b0b0b; border:1px solid var(--uh-border); overflow:hidden">
              <img src="${avatarUrl}" alt="${author}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            </div>`;
  }
  return `<div class="uh-a-ava">${uhInitial(author)}</div>`;
}
function threadCard(t){
  const likeId = `uh-like-${t.id}`;
  const saveId = `uh-save-${t.id}`;
  const likedCls = t.liked ? ' active' : '';
  const savedIcon = t.saved ? 'fa-solid' : 'fa-regular';
  const mediaHtml =
    t.postType==="image" && t.mediaUrl ? `<img src="${t.mediaUrl}" alt="${t.mediaAlt||"Post image"}" class="uh-openable" />` :
    t.postType==="video" && t.mediaUrl ? `<video src="${t.mediaUrl}" poster="${t.mediaPoster||""}" controls class="uh-openable"></video>` :
    t.postType==="poll"  ? `<div class="uh-tcontent"><i class="fa-solid fa-chart-simple"></i> Poll — open to vote</div>` : ``;

  return `
  <article class="uh-tcard" data-thread="${t.id}">
    <div class="uh-thead">
      <div class="uh-author">
        ${avatarHtml(t.author, t.author_avatar)}
        <div class="uh-a-info">
          <div class="uh-a-row">
            <span class="uh-a-name">${t.author}</span>
            ${catPill(t.category)}
          </div>
          <div class="uh-a-time">${t.timeAgo} ago</div>
        </div>
      </div>
    </div>
    <h3 class="uh-ttitle">${t.title}</h3>
    ${t.content? `<p class="uh-tcontent">${t.content}</p>` : ``}
    <div class="uh-tmedia">
      ${mediaHtml}
    </div>
    <div class="uh-tbar">
      <button class="uh-act uh-like${likedCls}" id="${likeId}" data-id="${t.id}">
        <i class="${t.liked?'fa-solid':'fa-regular'} fa-thumbs-up"></i><span>${uhFmt(t.likes)}</span>
      </button>
      <a class="uh-act uh-godetail" href="${ROUTES.show}${t.id}">
        <i class="fa-regular fa-message"></i><span>${t.commentCount}</span>
      </a>
      <button class="uh-act uh-share" data-id="${t.id}">
        <i class="fa-solid fa-share-nodes"></i><span>Share</span>
      </button>
      <button class="uh-act uh-save" id="${saveId}" data-id="${t.id}">
        <i class="${savedIcon} fa-bookmark"></i><span>Save</span>
      </button>
    </div>
  </article>`;
}
function renderThreads(append=false){
  const host = uh('#uhThreads');
  const html = UH_THREADS.map(threadCard).join("");
  if (append) host.insertAdjacentHTML('beforeend', UH_THREADS.slice(-_LAST_BATCH).map(threadCard).join(""));
  else host.innerHTML = html;

  wireCardInteractions(append);
}

/* ================= FETCH: LIST & PAGINATION ================= */
let _LAST_BATCH = 0;

async function loadPage(url){
  if (UH_LOADING) return;
  UH_LOADING = true;
  try{
    // Always normalize to a URL object so we can safely inject filters
    const u = new URL(url, location.origin);

    if (UH_FILTER.category) u.searchParams.set('category', UH_FILTER.category);
    if (UH_FILTER.q)        u.searchParams.set('q', UH_FILTER.q);
    if (UH_FILTER.saved)    u.searchParams.set('saved', '1'); // persist saved-only filter

    const target = u.toString();

    const res = await fetch(target, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
    if (!res.ok) throw new Error('Failed to load');

    const json = await res.json();
    const newOnes = dedupePush(json.data || []);
    _LAST_BATCH = newOnes.length;
    UH_PAGE_NEXT = json.next || null; // backend already appends current query, so "saved" persists

    appendThreads(newOnes);
    renderTopThreads();
  } catch(e){
    console.error(e);
    toast('Failed to load threads', 'error');
  } finally {
    UH_LOADING = false;
  }
}


function appendThreads(newItems){
  if (!newItems.length) return;
  const host = uh('#uhThreads');
  const html = newItems.map(threadCard).join('');
  host.insertAdjacentHTML('beforeend', html);
  wireCardInteractions(true);
}

function resetAndLoad(){
  UH_THREADS.length = 0;
  UH_SEEN_IDS = new Set();
  UH_PAGE_NEXT = null;
  uh('#uhThreads').innerHTML = '';
  // first page fixed to 5 as you wanted
  loadPage(ROUTES.list + '?per=5');
}

// IntersectionObserver + fallback
(function armScrollGate(){
  const arm = ()=>{ UH_USER_SCROLLED = true; cleanup(); };
  const maybeArmOnScroll = ()=>{
    if (window.scrollY > UH_SCROLL_ARM_DISTANCE) arm();
  };
  const cleanup = ()=>{
    window.removeEventListener('scroll', maybeArmOnScroll, {passive:true});
    window.removeEventListener('wheel', arm, {passive:true});
    window.removeEventListener('touchstart', arm, {passive:true});
    window.removeEventListener('keydown', keyArm, {passive:true});
  };
  const keyArm = (e)=>{
    // Space/PageDown/ArrowDown/Home/End etc. all count as "user scrolled intent"
    const keys = [' ', 'PageDown', 'ArrowDown', 'End'];
    if (keys.includes(e.key)) arm();
  };

  window.addEventListener('scroll', maybeArmOnScroll, {passive:true});
  window.addEventListener('wheel', arm, {passive:true});
  window.addEventListener('touchstart', arm, {passive:true});
  window.addEventListener('keydown', keyArm, {passive:true});
})();

let _io;
let _scrollPumpAttached = false;

function setupInfiniteScroll(){
  const end = uh('#uhFeedEnd');

  if (_io) { _io.disconnect(); _io = null; }
  _io = new IntersectionObserver(async (entries)=>{
    const ent = entries[0];
    // ← Gate on user scroll: do nothing if user hasn't scrolled yet
    if (!UH_USER_SCROLLED) return;

    if (ent && ent.isIntersecting && !UH_LOADING && UH_PAGE_NEXT){
      await loadPage(UH_PAGE_NEXT);
    }
  }, { root: null, rootMargin: '800px 0px 800px 0px', threshold: 0.01 });
  _io.observe(end);

  // Fallback pump (also gated on scroll)
  if (!_scrollPumpAttached) {
    _scrollPumpAttached = true;
    let t = null;
    window.addEventListener('scroll', ()=>{
      if (!UH_USER_SCROLLED) return; // gate
      if (t) return;
      t = setTimeout(()=>{
        t = null;
        autoPump();
      }, 120);
    }, { passive:true });
  }

  // IMPORTANT: no initial autoPump() call here anymore
}

function isSentinelNear(){
  const end = uh('#uhFeedEnd');
  if (!end) return false;
  const rect = end.getBoundingClientRect();
  const vpH = window.innerHeight || document.documentElement.clientHeight;
  return rect.top - vpH < 1200;
}

async function autoPump(){
  if (!UH_USER_SCROLLED) return; // gate
  if (UH_LOADING) return;
  if (!UH_PAGE_NEXT) return;
  if (!isSentinelNear()) return;
  await loadPage(UH_PAGE_NEXT);
}





/* ================= INTERACTIONS ================= */
function wireCardInteractions(append=false){
  // Whole card (title/media area) navigates to detail
  uhAll('.uh-tcard').forEach(card=>{
    const id = Number(card.dataset.thread||0);

    // Make image/video click push recent + navigate
    uhAll('.uh-openable', card).forEach(el=>{
      const go = ()=>{ pushRecent(id, card.querySelector('.uh-ttitle')?.textContent || 'Thread'); location.href = ROUTES.show+id; };
      if(el.tagName==='VIDEO'){ el.addEventListener('play', go); }
      el.addEventListener('click', go);
    });

    // Clicking anywhere on title navigates
    card.querySelector('.uh-ttitle')?.addEventListener('click', ()=>{ pushRecent(id, card.querySelector('.uh-ttitle')?.textContent||''); location.href = ROUTES.show+id; });
  });

  // Like buttons
  uhAll('.uh-like').forEach(btn=>{
    btn._wiredLike || btn.addEventListener('click', async (e)=>{
      e.stopPropagation();
      const id = Number(btn.dataset.id);
      try{
        const res = await fetch(ROUTES.like(id), {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest', Accept:'application/json'}});
        if(!res.ok){ if(res.status===401){ 
          if (typeof goHomeAndOpenLogin === 'function') goHomeAndOpenLogin();
          return; 
        }
         throw new Error('Like failed'); 
        }
        const json = await res.json();
        // update UI
        btn.classList.toggle('active', json.liked);
        const icon = btn.querySelector('i');
        icon.classList.remove('fa-regular','fa-solid'); icon.classList.add(json.liked?'fa-solid':'fa-regular','fa-thumbs-up');
        const span = btn.querySelector('span:last-child');
        span.textContent = uhFmt(json.count);
      }catch(err){ console.error(err); toast('Failed to like','error'); }
    });
    btn._wiredLike = true;
  });

  // Save buttons
  uhAll('.uh-save').forEach(btn=>{
    btn._wiredSave || btn.addEventListener('click', async (e)=>{
      e.stopPropagation();
      const id = Number(btn.dataset.id);
      try{
        const res = await fetch(ROUTES.save(id), {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}});
        if(!res.ok){ if(res.status===401){ if (typeof goHomeAndOpenLogin === 'function') goHomeAndOpenLogin();return; } throw new Error('Save failed'); }
        const json = await res.json();
        const icon = btn.querySelector('i');
        icon.classList.remove('fa-regular','fa-solid');
        icon.classList.add(json.saved ? 'fa-solid' : 'fa-regular', 'fa-bookmark');
        toast(json.saved?'Saved':'Removed from saved', json.saved?'ok':'ok');
      }catch(err){ console.error(err); toast('Failed to save','error'); }
    });
    btn._wiredSave = true;
  });

  // Share buttons
// Share buttons: try native share, always copy, record share
uhAll('.uh-share').forEach(btn=>{
  btn._wiredShare || btn.addEventListener('click', async (e)=>{
    e.stopPropagation();
    const id  = Number(btn.dataset.id);
    const url = ROUTES.show + id;

    // Try native share first (optional)
    let shared = false;
    const card = btn.closest('.uh-tcard');
    const title = card?.querySelector('.uh-ttitle')?.textContent?.trim() || 'Check this out';

    if (navigator.share) {
      try {
        await navigator.share({ title, text: title, url });
        shared = true;
      } catch (_) { /* user cancelled or not supported */ }
    }

    // Always copy to clipboard
    try { await navigator.clipboard.writeText(url); } catch {}

    // Record share server-side
    try {
      await fetch(ROUTES.share(id), {
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
        body:new URLSearchParams({channel: shared ? 'web_share' : 'copy_link'})
      });
    } catch {}

    toast(shared ? 'Shared & link copied' : 'Link copied');
  });
  btn._wiredShare = true;
});

}

/* ================= COMPOSER ================= */
const composer = (function(){
  const form = uh('#uhPostForm');
  const chips = uhAll('.uh-composer-actions .uh-chip');
  const imgRow = uh('#uhImgRow'), vidRow = uh('#uhVidRow'), pollRow = uh('#uhPollRow');
  const imgFile = uh('#uhImgFile'), imgPick = uh('#uhImgPick'), imgUrl = uh('#uhImgUrl'), imgPreview = uh('#uhImgPreview');
  const vidFile = uh('#uhVidFile'), vidPick = uh('#uhVidPick'), vidUrl = uh('#uhVidUrl'), vidPoster = uh('#uhVidPoster');
  const pollOptsHost = uh('#uhPollOpts'), pollAdd = uh('#uhAddPollOpt'), pollMultiple = uh('#uhPollMultiple');
  const cancelBtn = uh('#uhCancelPost');
  const startBtn = uh('#uhStartThreadBtn');
  let mode = null;

  function openForm(m){
    mode = m;
    form.classList.remove('_hide');
    imgRow.classList.toggle('_hide', m!=='image');
    vidRow.classList.toggle('_hide', m!=='video');
    pollRow.classList.toggle('_hide', m!=='poll');
    if(m==='poll' && !pollOptsHost.children.length){
      addOpt(); addOpt();
    }

    requestAnimationFrame(()=>{
    form.scrollIntoView({ behavior:'smooth', block:'center' });
    form.querySelector('input[name="title"]')?.focus();
  });
  }
  function closeForm(){
    form.reset();
    form.classList.add('_hide');
    imgPreview.classList.add('_hide'); imgPreview.src='';
    pollOptsHost.innerHTML = '';
    mode = null;
  }

  chips.forEach(c=>{
    c.addEventListener('click', ()=> openForm(c.dataset.mode));
  });
  startBtn?.addEventListener('click', ()=>{
  document.getElementById('uhComposer')?.scrollIntoView({ behavior:'smooth', block:'start' });
  setTimeout(()=> openForm('text'), 200);
});
// Clicking the "Start a new thread" pill also opens the form
uh('#uhComposer .uh-composer-pill')?.addEventListener('click', ()=> openForm('text'));

  cancelBtn?.addEventListener('click', closeForm);



  // ===== Upload constraints (mirror backend) =====
const UH_UPLOAD = {
  image: {
    maxMB: 10,
    types: ['image/jpeg','image/png','image/gif','image/webp'],
  },
  video: {
    maxMB: 200,
    types: ['video/mp4','video/webm','video/ogg','video/quicktime'],
  }
};

function humanTypes(list){
  return list.map(t => t.split('/')[1]).join(', ');
}

function preflightFile(file, kind){ // kind = 'image' | 'video'
  const rule = UH_UPLOAD[kind];
  const sizeMB = (file?.size || 0) / 1024 / 1024;
  const okType = rule.types.includes(file?.type || '');
  const okSize = sizeMB <= rule.maxMB + 0.0001;
  return {
    ok: okType && okSize,
    okType, okSize,
    sizeMB: Math.round(sizeMB*10)/10,
    maxMB: rule.maxMB,
    typesNice: humanTypes(rule.types),
  };
}

async function parseErrorResponse(res){
  // Try JSON first
  try {
    const j = await res.clone().json();
    // Laravel validation typical shapes
    if (j?.errors?.file?.length) return j.errors.file[0];
    if (j?.message) return j.message;
  } catch(_) {}

  // Try text fallback (may be HTML)
  try {
    const txt = await res.clone().text();
    if (txt) {
      // Bare minimum: strip tags to show decent text
      const m = txt.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();
      if (m) return m.slice(0, 300);
    }
  } catch(_) {}

  // Status based fallback
  if (res.status === 413) return 'File is too large for the server. Try a smaller file.';
  return 'Upload failed. Please try again.';
}


  // Image
  imgPick?.addEventListener('click', ()=> imgFile.click());
imgFile?.addEventListener('change', async ()=>{
  const f = imgFile.files?.[0];
  if(!f) return;

  // Client preflight for instant, precise message
  const pf = preflightFile(f, 'image');
  if (!pf.ok) {
    if (!pf.okType) toast(`Unsupported image type. Allowed: ${pf.typesNice}.`, 'error');
    else if (!pf.okSize) toast(`Image too large. Max ${pf.maxMB} MB, yours ~${pf.sizeMB} MB.`, 'error');
    return;
  }

  try{
    const fd = new FormData(); fd.append('file', f);
    const res = await fetch(ROUTES.upload, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'},
      body: fd
    });

    if(!res.ok){
      const msg = await parseErrorResponse(res);
      toast(msg, 'error');
      return;
    }

    const json = await res.json();
    imgUrl.value = json.url;
    imgPreview.src = json.url; imgPreview.classList.remove('_hide');
    toast('Image uploaded');
  }catch{
    toast('Image upload failed','error');
  }
});


  // Video (URL first; to support manual upload later, wire to a /forum/upload that accepts video/*)
vidPick?.addEventListener('click', ()=> vidFile.click());
vidFile?.addEventListener('change', async ()=>{
  const f = vidFile.files?.[0];
  if(!f){ toast('No video selected','error'); return; }

  // Client preflight for instant, precise message
  const pf = preflightFile(f, 'video');
  if (!pf.ok) {
    if (!pf.okType) toast(`Unsupported video type. Allowed: ${pf.typesNice}.`, 'error');
    else if (!pf.okSize) toast(`Video too large. Max ${pf.maxMB} MB, yours ~${pf.sizeMB} MB.`, 'error');
    return;
  }

  try{
    const fd = new FormData(); fd.append('file', f);
    const res = await fetch(ROUTES.upload, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'},
      body: fd
    });

    if(!res.ok){
      const msg = await parseErrorResponse(res);
      toast(msg,'error');
      return;
    }

    const json = await res.json();
    if (json?.type !== 'video') { toast('Uploaded file is not a video','error'); return; }
    uh('#uhVidUrl').value = json.url;
    toast('Video uploaded');
  }catch(err){
    console.error(err);
    toast('Video upload failed','error');
  }
});



  // Poll
  function addOpt(val=''){
    const i = document.createElement('input');
    i.type='text'; i.className='uh-composer-pill'; i.placeholder='Poll option'; i.value=val; i.required=true;
    pollOptsHost.appendChild(i);
  }
  pollAdd?.addEventListener('click', ()=> addOpt());

  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    try{
      const fd = new FormData(form);
      const payload = new URLSearchParams();
      payload.set('title', fd.get('title') || '');
      if (fd.get('category')) payload.set('category', fd.get('category'));
      const bodyHtml = (fd.get('body_html')||'').toString();
      if (bodyHtml) payload.set('body_html', bodyHtml);

      let postType = 'text';
      if (mode==='image') {
        postType = 'image';
        const url = (uh('#uhImgUrl').value||'').trim();
        if(!url){ toast('Pick or paste an image','error'); return; }
        payload.set('media_url', url);
        const alt = (fd.get('media_alt')||'').toString();
        if (alt) payload.set('media_alt', alt);
      } else if (mode==='video') {
        postType = 'video';
        const url = (uh('#uhVidUrl').value||'').trim();
        if(!url){ toast('Paste a video URL (MP4)','error'); return; }
        payload.set('media_url', url);
        const poster = (uh('#uhVidPoster').value||'').trim();
        if (poster) payload.set('media_poster', poster);
      } else if (mode==='poll') {
        postType = 'poll';
        const opts = Array.from(pollOptsHost.querySelectorAll('input')).map(i=>i.value.trim()).filter(Boolean);
        if (opts.length<2){ toast('Add at least 2 options','error'); return; }
        payload.set('poll_multiple', uh('#uhPollMultiple').checked ? '1':'0');
        opts.forEach(o=> payload.append('poll_options[]', o));
      }
      payload.set('post_type', postType);

      const res = await fetch(ROUTES.store, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF, 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded'},
        body: payload.toString()
      });
      if(!res.ok){
        if(res.status===401){ if (typeof goHomeAndOpenLogin === 'function') goHomeAndOpenLogin();return; }
        const txt = await res.text(); console.error(txt);
        throw new Error('Create failed');
      }
      const json = await res.json();
      toast('Posted!');
      closeForm();
      // Prepend by reloading first page
      resetAndLoad();
    }catch(err){ console.error(err); toast('Failed to post','error'); }
  });

  return { openForm, closeForm };
})();

/* ================= INIT ================= */
(async function init(){
  const nameFromHeader = '{{ auth()->user()->name ?? '' }}'.trim();
  if (nameFromHeader) uh('#uhUserAva').textContent = uhInitial(nameFromHeader);

  await fetchCategories();
  renderLeftCats();
  populateCategorySelect();

  await loadPage(ROUTES.list + '?per=5');
  setupInfiniteScroll();
  renderRecent();
})();

</script>

@include('common.footer')
