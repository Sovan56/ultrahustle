{{-- resources/views/marketplace.blade.php --}}
@include('common.header')

<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('rebuildfrontend/css/marketplace.css') }}">

@php
  // Viewer name passed from controller, but keep a safe fallback
  $viewer = auth()->user() ?? \App\Models\User::find(session('user_id'));
  $fallbackViewerName = $viewer
      ? trim(($viewer->first_name ?? '').' '.($viewer->last_name ?? '')) ?: ($viewer->unique_id ?? 'there')
      : null;
  $helloName = $viewerName ?? $fallbackViewerName ?? 'there';
@endphp

<div class="container" style="margin-top: 30px !important;">
  <h3>🙏 Welcome back, <span style="color: #ceff1b;">{{ $helloName }}</span>! Your hustle starts here.</h3>
  <p>🚀 Browse services, products, courses, and webinars tailored just for you. Keep building, keep growing.</p>
</div>

<!-- ===== MARKETPLACE FILTERS (ADD) ===== -->
<section class="mp-filters container" id="mpFilters">
  <!-- Row 1: Categories -->
  <div class="mp-chips-row">
    <div id="catChips" class="chip-wrap"></div>
  </div>

  <!-- Row 2: Fixed Filter button + horizontally scrolling subcategories -->
  <div class="mp-chips-row" id="subRow">
    <button id="openFilter" class="fchip fchip-accent sub-filter" aria-label="Open Filters">
      <i class="fa-solid fa-filter"></i> Filter
    </button>
    <div id="subChips" class="chip-wrap" role="listbox" aria-label="Subcategories"></div>
  </div>

  <div class="mp-selected" id="selectedTags"></div>
</section>

<!-- Left filter drawer -->
<div class="filter-mask" id="filterMask"></div>
<aside class="filter-drawer" id="filterDrawer" aria-hidden="true">
  <div class="fd-head">
    <strong>Filters</strong>
    <button class="icon close" id="closeFilter" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="fd-body">
    <div class="fd-group">
      <div class="fd-title">Product Categories</div>
      <div id="fdCat" class="fd-pills"></div>
    </div>

    <!-- Trust Filter -->
    <div class="fd-group">
      <div class="fd-title">Trust Filter</div>
      <div class="fd-toggles">
        <label class="toggle">
          <input type="checkbox" id="trustTeam">
          <span></span> Team
        </label>
        <label class="toggle">
          <input type="checkbox" id="trustAI">
          <span></span> AI-Powered only
        </label>
      </div>
      <div class="fd-note">* Use “Apply filters” to apply Team/AI.</div>
    </div>

    <div class="fd-group">
      <div class="fd-title">Price Range ({{ $targetCurrencySymbol ?? '$' }})</div>
      <div class="fd-price">
        <input class="input" id="priceMin" type="number" placeholder="Min" min="0" />
        <input class="input" id="priceMax" type="number" placeholder="Max" min="0" />
      </div>
    </div>

    <div class="fd-group">
      <div class="fd-title">Sort By</div>
      <select id="sortBy" class="input">
        <option value="relevant">Most Relevant</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="newest">Newest</option>
        <option value="rating">Rating</option>
      </select>
    </div>
  </div>

  <div class="fd-foot">
    <div id="fdSelected" class="fd-selected"></div>
    <button class="btn btn-accent" id="applyFilters">Apply filters</button>
  </div>
</aside>

<section class="marketrail">
  <div class="container">
    <div class="rail-head">
      <h2>Continue browsing <i class="fa-solid fa-arrow-right-long"></i></h2>
      <div class="rail-ctrl">
        <button class="rail-btn" data-dir="prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="rail-btn" data-dir="next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>

    <!-- BOOSTED PRODUCT SHOW HERE (Rendered by JS) -->
    <div class="rail" id="marketRail" tabindex="0" aria-label="Listings"></div>
    <div class="rail-dots" id="railDots" aria-hidden="true"></div>
  </div>
</section>

<style>
  .marketgrid { padding: 8px 0 60px; }
  .marketgrid .head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin: 6px 0 14px; }
  .marketgrid .head h2{ font-size:22px; color:var(--accent); display:flex; align-items:center; gap:8px; }
  .grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:18px; }
  .gig.grid-gig .gig-media .img{ height: 170px; }
  @media (max-width: 480px){ .marketgrid .head h2{ font-size:18px; } .gig.grid-gig .gig-media .img{ height: 150px; } }
  .is-hidden{ display:none !important; }
  .empty-box{padding:18px;border:1px dashed #2a2a2a;border-radius:12px;color:#cfcfcf;text-align:center;}
</style>

<section class="marketgrid">
  <div class="container">
    <div class="head">
      <h2>All products</h2>
      <div id="gridCount" style="color:#cfcfcf;font:500 13px Roboto;"></div>
    </div>

    <div class="grid" id="marketGrid"></div>
    <div class="text-center" style="margin-top:16px;">
      <button id="loadMoreBtn" class="btn btn-outline-dark" style="min-width:160px; display:none;">Load more</button>
    </div>
  </div>
</section>

{{-- ===== Route + data constants (server values) ===== --}}
<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const LIST_URL       = @json(route('marketplace.list'));
  const WL_TOGGLE_URL  = @json(route('wishlist.toggle'));
  const WL_COUNT_URL   = @json(route('wishlist.count'));
  const WL_IDS_URL     = @json(route('wishlist.ids'));
  const CLICK_URL      = @json(route('analytics.product.click'));
  const LIST_VIEW_URL  = @json(route('analytics.list.view'));

  const IS_LOGGED      = {{ auth()->check() || session('user_id') ? 'true' : 'false' }};
  const LOGIN_URL      = @json(route('login').'?redirect='.urlencode(request()->fullUrl()));

  // Types & Subs provided by controller
  const TYPES = @json(($types ?? collect())->map(fn($t)=>['id'=>$t->id,'name'=>$t->name]));
  const ALL_SUBCATS = @json(($subs ?? collect())->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'type_id'=>$s->product_type_id]));

  // User details route (click avatar/name); we pass seller_id from backend cards
  const USER_DETAIL_BASE = @json(route('user.details', ['id' => 0])); // replace trailing /0
  function userDetailsUrl(userId){
    return USER_DETAIL_BASE.replace(/0$/, String(userId || 0));
  }
</script>



<!-- 2nd script — drop-in replacement -->
<script>
    
    // Update EVERY instance of a product card (rail + grid) in one go
function setWishStateForProduct(pid, wished, serverCount /* optional */){
  const nodes = document.querySelectorAll(`.product-card[data-id="${pid}"]`);
  nodes.forEach(card => {
    const likesEl   = card.querySelector('.likes');
    const icon      = likesEl?.querySelector('i');
    const countSpan = likesEl?.querySelector('.likes-count');

    let current = Number(card.dataset.likes || 0);
    let next    = (typeof serverCount === 'number')
      ? serverCount
      : Math.max(0, current + (wished ? 1 : -1));

    card.dataset.likes = String(next);
    if (countSpan) countSpan.textContent = (typeof formatCount === 'function') ? formatCount(next) : String(next);

    if (icon){
      if (wished){
        icon.classList.remove('fa-regular','far');
        icon.classList.add('fa-solid','fas');
        icon.style.color = '#ff3b3b'; // red when wished
      } else {
        icon.classList.remove('fa-solid','fas');
        icon.classList.add('fa-regular','far');
        icon.style.color = '';        // default when not wished
      }
    }
  });
}

// Try to open a login modal; gracefully fall back to redirect
function openLoginModal(){
  // 1) Bootstrap modal with id="loginModal"
  const modalEl = document.getElementById('loginModal');
  if (modalEl){
    // Bootstrap 5
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function'){
      (openLoginModal._inst ||= new window.bootstrap.Modal(modalEl, {backdrop:'static'})).show();
      return true;
    }
    // Bootstrap 4
    if (typeof window.$ === 'function' && typeof window.$(modalEl).modal === 'function'){
      window.$(modalEl).modal({backdrop:'static', show:true});
      return true;
    }
    // Simple toggle class if you use custom CSS
    modalEl.classList.add('open','show');
    return true;
  }

  // 2) A generic trigger (if you use a hidden button)
  const trigger = document.querySelector('[data-login-modal],[data-toggle="login-modal"],.js-open-login');
  if (trigger){ trigger.click(); return true; }

  // 3) As a last resort, redirect
  if (typeof LOGIN_URL === 'string') window.location.href = LOGIN_URL;
  return false;
}

    
(function () {
  // CSS once
  if (!document.getElementById('uh-toast-css')) {
    const css = document.createElement('style');
    css.id = 'uh-toast-css';
    css.textContent = `
      .uh-toasty{
        position: fixed; bottom: 24px; right: 24px;
        padding: 10px 16px; border-radius: 12px;
        font: 700 14px/1.2 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
        color: #0b0b0b; background: #ceff1b;   /* default success */
        box-shadow: 0 8px 28px rgba(0,0,0,.25);
        opacity: 0; transform: translateY(16px);
        transition: opacity .22s ease, transform .22s ease;
        z-index: 99999; pointer-events: none;
      }
      .uh-toasty.show    { opacity: 1; transform: translateY(0); }
      .uh-toasty.success { background:#ceff1b; color:#0b0b0b; }
      .uh-toasty.danger  {background:#0b0b0b; 
  color:#CEFF1B;
  border: 1px solid #CEFF1B; }
    `;
    document.head.appendChild(css);
  }

  const TOAST_ID = 'uh-toast-singleton';

  // Your namespaced toast
  window.uhToast = function (message, type) {
    // support either order: uhToast('Added','success') or uhToast('success','Added')
    const isType = v => v === 'success' || v === 'danger';
    if (isType(message) && typeof type === 'string') [message, type] = [type, message];
    if (!isType(type)) type = 'success';
    if (typeof message !== 'string' || !message.trim()) {
      message = (type === 'danger') ? 'Removed' : 'Done';
    }

    let el = document.getElementById(TOAST_ID);
    if (!el) {
      el = document.createElement('div');
      el.id = TOAST_ID;
      el.className = 'uh-toasty';
      document.body.appendChild(el);
    }

    el.textContent = message;
    el.classList.remove('success','danger','show');
    el.classList.add(type);

    requestAnimationFrame(() => el.classList.add('show'));
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(() => el.classList.remove('show'), 1700);
  };

  // OPTIONAL: also alias window.toast -> uhToast *after* this file loads.
  // If some other script overwrites it later, your calls below still use uhToast directly.
  window.toast = window.uhToast;
})();
</script>





<!-- 3rd script  -->
<script>
/* ===== Build filters UI from backend TYPES / ALL_SUBCATS (multi-sub + URL preselect) ===== */
(function initMarketplaceFilters(){
  const catChips   = document.getElementById('catChips');
  const subChips   = document.getElementById('subChips');
  const selectedEl = document.getElementById('selectedTags');
  const openBtn    = document.getElementById('openFilter');
  const drawer     = document.getElementById('filterDrawer');
  const mask       = document.getElementById('filterMask');
  const closeBtn   = document.getElementById('closeFilter');
  const fdCat      = document.getElementById('fdCat');
  const fdSelected = document.getElementById('fdSelected');

  const priceMin   = document.getElementById('priceMin');
  const priceMax   = document.getElementById('priceMax');
  const sortBySel  = document.getElementById('sortBy');
  const applyBtn   = document.getElementById('applyFilters');

  const trustTeamEl = document.getElementById('trustTeam');
  const trustAIEl   = document.getElementById('trustAI');

  const typeByName = Object.fromEntries((TYPES||[]).map(t=>[String(t.name).toLowerCase(), t]));
  const subsByType = (ALL_SUBCATS||[]).reduce((acc,s)=>((acc[s.type_id] ||= []).push(s), acc), {});
  const allSubNames = (ALL_SUBCATS||[]).map(s=>s.name);

  // === NEW: maps by id for URL → state init
const typeById = Object.fromEntries((TYPES||[]).map(t => [Number(t.id), t]));
const subsById = Object.fromEntries((ALL_SUBCATS||[]).map(s => [Number(s.id), s]));

// === NEW: apply initial filters from URL (?type_id, ?sub_id, sub_ids[]=)
function applyInitialFromURL(){
  const sp = new URLSearchParams(window.location.search);

  // type_id → category (by name, since state/category holds the name)
  const tid = Number(sp.get('type_id') || 0);
  if (tid && typeById[tid]) {
    state.category = typeById[tid].name;
  }

  // sub_ids[] (multi) + legacy sub_id → subcategories (names)
  const arr  = sp.getAll('sub_ids[]').map(v => Number(v)).filter(Boolean);
  const one  = Number(sp.get('sub_id') || 0);
  if (one) arr.push(one);

  const uniq = [...new Set(arr)];
  uniq.forEach(id => {
    const s = subsById[id];
    if (s) state.subcategories.add(s.name);
  });

  // If a category is selected, keep only subs that belong to it (for visual chip row)
  if (state.category){
    const t = typeByName[state.category.toLowerCase()];
    if (t){
      const allowed = new Set((subsByType[t.id]||[]).map(s=>s.name));
      [...state.subcategories].forEach(sc => { if (!allowed.has(sc)) state.subcategories.delete(sc); });
    }
  }
}


  // ===== Read URL params to preselect =====
// Single source of truth for state
const state = {
  category: null,            // type NAME
  subcategories: new Set(),  // sub NAMES (multi)
  priceMin: null,
  priceMax: null,
  sortBy: 'relevant',
  trustTeam: false,
  trustAIOnly: false
};

// Seed from URL once
applyInitialFromURL();


  function renderCategoryRow(){
    if (!catChips) return;
    catChips.innerHTML = '';
    (TYPES||[]).forEach(t=>{
      const on = state.category && state.category.toLowerCase() === t.name.toLowerCase();
      const btn = document.createElement('button');
      btn.className = 'fchip bolderadd' + (on ? ' active':'');
      btn.setAttribute('aria-pressed', on ? 'true':'false');
      btn.textContent = t.name;
      btn.addEventListener('click', ()=>{
        if (on) {
          state.category = null;
          state.subcategories.clear();
        } else {
          state.category = t.name;
          const allowed = new Set((subsByType[t.id]||[]).map(s=>s.name));
          [...state.subcategories].forEach(sc => { if (!allowed.has(sc)) state.subcategories.delete(sc); });
        }
        renderCategoryRow(); renderSubRow(); renderSelected(); emit();
        
      });
      catChips.appendChild(btn);
    });
  }

  function renderSubRow(){
    if (!subChips) return;
    subChips.innerHTML = '';
    let list = [];
    if (state.category){
      const typeObj = typeByName[state.category.toLowerCase()];
      list = (typeObj ? (subsByType[typeObj.id]||[]) : ALL_SUBCATS).map(s=>s.name);
    } else {
      list = allSubNames;
    }
    list.forEach(sc=>{
      const on = state.subcategories.has(sc);
      const btn = document.createElement('button');
      btn.className = 'fchip' + (on ? ' active':'');
      btn.setAttribute('aria-pressed', on ? 'true':'false');
      btn.textContent = sc;
      btn.addEventListener('click', ()=>{
        if (on) { state.subcategories.delete(sc); }
        else { state.subcategories.add(sc); }
        renderSubRow(); renderSelected(); emit(); // emit every toggle
      });
      subChips.appendChild(btn);
    });
  }

function renderDrawerCats(){
  if (!fdCat) return;
  fdCat.innerHTML = '';
  (TYPES||[]).forEach(t=>{
    const on = state.category && state.category.toLowerCase() === t.name.toLowerCase();
    const b = document.createElement('button');
    b.className = 'fchip bolderadd' + (on ? ' active':'');
    b.setAttribute('aria-pressed', on ? 'true':'false');
    b.textContent = t.name;
    b.addEventListener('click', ()=>{
      if (on) { 
        state.category = null; 
        state.subcategories.clear(); 
      } else {
        state.category = t.name;
        const allowed = new Set((subsByType[t.id]||[]).map(s=>s.name));
        [...state.subcategories].forEach(sc => { if (!allowed.has(sc)) state.subcategories.delete(sc); });
      }
      renderCategoryRow(); 
      renderSubRow(); 
      renderDrawerCats(); 
      renderSelected(); 
      emit();
    });
    fdCat.appendChild(b);
  });
}

  function selectedTag(label, key, val){
    const tag = document.createElement('span');
    tag.className='tag';
    tag.innerHTML = `${label}: ${val} <span class="x" aria-label="Remove">×</span>`;
    tag.querySelector('.x').addEventListener('click', ()=>{
      if (key==='category'){ state.category=null; state.subcategories.clear(); }
      if (key==='sub') state.subcategories.delete(val);
      if (key==='pmin') { state.priceMin=null; priceMin && (priceMin.value=''); }
      if (key==='pmax') { state.priceMax=null; priceMax && (priceMax.value=''); }
      if (key==='sort') { state.sortBy='relevant'; sortBySel && (sortBySel.value='relevant'); }
      if (key==='trustTeam'){ state.trustTeam=false; trustTeamEl && (trustTeamEl.checked=false); }
      if (key==='trustAI'){ state.trustAIOnly=false; trustAIEl && (trustAIEl.checked=false); }
      renderCategoryRow(); renderSubRow(); renderSelected(); emit();
    });
    return tag;
  }

  function renderSelected(){
    if (selectedEl) selectedEl.innerHTML = '';
    if (fdSelected) fdSelected.innerHTML = '';

    if (state.category){
      const t=selectedTag('Category','category',state.category);
      const t2=selectedTag('Category','category',state.category);
      selectedEl && selectedEl.appendChild(t); fdSelected && fdSelected.appendChild(t2);
    }
    [...state.subcategories].forEach(sc=>{
      const t=selectedTag('Subcategory','sub',sc);
      const t2=selectedTag('Subcategory','sub',sc);
      selectedEl && selectedEl.appendChild(t); fdSelected && fdSelected.appendChild(t2);
    });
    if (state.priceMin!=null) selectedEl && selectedEl.appendChild(selectedTag('Min','pmin',state.priceMin));
    if (state.priceMax!=null) selectedEl && selectedEl.appendChild(selectedTag('Max','pmax',state.priceMax));
    if (state.sortBy && state.sortBy!=='relevant' && sortBySel){
      selectedEl && selectedEl.appendChild(selectedTag('Sort','sort',sortBySel.options[sortBySel.selectedIndex].text));
    }
    if (state.trustTeam)   selectedEl && selectedEl.appendChild(selectedTag('Trust','trustTeam','Team'));
    if (state.trustAIOnly) selectedEl && selectedEl.appendChild(selectedTag('Trust','trustAI','AI-Only'));
  }

  function openDrawer(){ if (!drawer||!mask) return; mask.classList.add('open'); drawer.classList.add('open'); drawer.setAttribute('aria-hidden','false'); }
  function closeDrawer(){ if (!drawer||!mask) return; mask.classList.remove('open'); drawer.classList.remove('open'); drawer.setAttribute('aria-hidden','true'); }

  function emit(){
    const detail = {
      category: state.category,
      subcategories: [...state.subcategories],
      priceMin: state.priceMin,
      priceMax: state.priceMax,
      sortBy: state.sortBy,
      trustTeam: state.trustTeam,
      trustAIOnly: state.trustAIOnly
    };
    document.dispatchEvent(new CustomEvent('filters:change', { detail }));
  }

  openBtn && openBtn.addEventListener('click', openDrawer);
  closeBtn && closeBtn.addEventListener('click', closeDrawer);
  mask && mask.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeDrawer(); });

  priceMin && priceMin.addEventListener('change', ()=>{ const v=priceMin.value; state.priceMin = v? Math.max(0, +v) : null; renderSelected(); emit(); });
  priceMax && priceMax.addEventListener('change', ()=>{ const v=priceMax.value; state.priceMax = v? Math.max(0, +v) : null; renderSelected(); emit(); });
  sortBySel && sortBySel.addEventListener('change', ()=>{ state.sortBy = sortBySel.value; renderSelected(); emit(); });

  trustTeamEl && trustTeamEl.addEventListener('change', ()=>{ state.trustTeam   = !!trustTeamEl.checked; renderSelected(); });
  trustAIEl   && trustAIEl.addEventListener('change',   ()=>{ state.trustAIOnly = !!trustAIEl.checked; renderSelected(); });

  applyBtn && applyBtn.addEventListener('click', ()=>{
    state.priceMin = priceMin && priceMin.value ? Math.max(0, +priceMin.value) : null;
    state.priceMax = priceMax && priceMax.value ? Math.max(0, +priceMax.value) : null;
    state.sortBy   = sortBySel ? sortBySel.value : 'relevant';
    state.trustTeam   = !!(trustTeamEl && trustTeamEl.checked);
    state.trustAIOnly = !!(trustAIEl   && trustAIEl.checked);
    renderSelected(); closeDrawer(); emit();
  });

 // Initial UI (already seeded with URL selections)
renderCategoryRow(); renderSubRow(); renderDrawerCats(); renderSelected();

// Save once for the 5th script to dispatch (prevents race & double fetch)
window.__initialFilters = {
  category: state.category,
  subcategories: [...state.subcategories],
  priceMin: state.priceMin,
  priceMax: state.priceMax,
  sortBy: state.sortBy,
  trustTeam: state.trustTeam,
  trustAIOnly: state.trustAIOnly
};
// DO NOT emit here — the 5th script will dispatch one filters:change using this.

})();
</script>


<!-- Horizontal subcategory scroller (drag-friendly fix) -->
<script>
(function(){
  const wrap = document.querySelector('#subRow .chip-wrap');
  if (!wrap) return;

  wrap.addEventListener('wheel', (e) => {
    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
      wrap.scrollLeft += e.deltaY;
      e.preventDefault();
    }
  }, { passive: false });

  let isDown=false, startX=0, startLeft=0, moved=0;
  wrap.addEventListener('pointerdown', (e)=>{ isDown=true; moved=0; startX=e.clientX; startLeft=wrap.scrollLeft; });
  wrap.addEventListener('pointermove', (e)=>{
    if(!isDown) return;
    const dx = e.clientX - startX; moved = Math.max(moved, Math.abs(dx));
    if (moved > 5){ wrap.scrollLeft = startLeft - dx; e.preventDefault(); }
  });
  function endDrag(){ isDown=false; }
  wrap.addEventListener('pointerup', endDrag);
  wrap.addEventListener('pointercancel', endDrag);
})();
</script>

<!-- 5th script  -->
<script>
/* ===== Renderers + Backend Bridge (preserve your card structure) ===== */
(function(){
  const railRoot   = document.getElementById('marketRail');
  const dotsWrap   = document.getElementById('railDots');
  const gridRoot   = document.getElementById('marketGrid');
  const gridCount  = document.getElementById('gridCount');

  // ===== Bottom-centered neon spinner INSIDE the products area (no overlap with footer) =====
  (function ensureBottomSpinner(){
    if (document.getElementById('infiniteLoader')) return;

    const css = document.createElement('style');
    css.textContent = `
      .uh-spinner-wrap{
        width: 100%;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 22px 0;
      }
      .uh-spinner{
        width: 56px; height: 56px; border-radius: 50%;
        border: 3px solid rgba(206,255,27,.2);
        border-top-color: #ceff1b;
        animation: uhSpin .8s linear infinite;
        box-shadow: 0 0 18px rgba(206,255,27,.35), inset 0 0 8px rgba(206,255,27,.15);
      }
      @keyframes uhSpin { to { transform: rotate(360deg); } }
    `;
    document.head.appendChild(css);

    const wrap = document.createElement('div');
    wrap.id = 'infiniteLoader';
    wrap.className = 'uh-spinner-wrap';
    wrap.innerHTML = `<div class="uh-spinner" aria-label="Loading"></div>`;

    // A tiny sentinel that we observe to trigger more loads
    const sentinel = document.createElement('div');
    sentinel.id = 'infiniteSentinel';
    sentinel.style.cssText = 'height:1px;width:100%;';

    // Place both directly AFTER the grid so they sit at the bottom of products
    if (gridRoot && gridRoot.parentElement){
      gridRoot.parentElement.appendChild(wrap);
      gridRoot.parentElement.appendChild(sentinel);
    } else {
      document.body.appendChild(wrap);
      document.body.appendChild(sentinel);
    }
  })();

  function showSpinner(on){
    const el = document.getElementById('infiniteLoader');
    if (!el) return;
    el.style.display = on ? 'flex' : 'none';
  }

  const pager = { page:1, per_page:6, has_more:false, next:null };
  let currentParams = new URLSearchParams(); // always rebuilt from filter event
  let currentFilterDetail = {
    category:null, subcategories:[], priceMin:null, priceMax:null, sortBy:'relevant', trustTeam:false, trustAIOnly:false
  };

  function esc(s){ return String(s ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
  function formatCount(n){
    n = Number(n||0);
    if (n >= 1e9) return (n/1e9).toFixed(n%1e9?1:0)+'B';
    if (n >= 1e6) return (n/1e6).toFixed(n%1e6?1:0)+'M';
    if (n >= 1e3) return (n/1e3).toFixed(n%1e3?1:0)+'K';
    return String(n);
  }

  // Create header wishlist badges (desktop + mobile) and keep updated
  function ensureWishlistBadges(){
    const desk = document.getElementById('openWishlistDesktop');
    const mob  = document.getElementById('openWishlistMobile');
    [desk, mob].forEach(holder=>{
      if (!holder) return;
      if (!holder.querySelector('.wl-badge')){
        const b=document.createElement('span');
        b.className='wl-badge';
        b.style.cssText='position:absolute;transform:translate(8px,-8px);min-width:18px;height:18px;border-radius:9px;background:#ceff1b;color:#0b0b0b;font:700 11px Roboto;display:grid;place-items:center;padding:0 5px;';
        holder.style.position='relative';
        holder.appendChild(b);
      }
    });
  }
  async function refreshWishlistBadge(){
    try{
      const res = await fetch(WL_COUNT_URL, { headers:{'Accept':'application/json'} });
      const js  = await res.json();
      const count = (js && typeof js.count==='number') ? js.count : 0;
      ensureWishlistBadges();
      document.querySelectorAll('#openWishlistDesktop .wl-badge, #openWishlistMobile .wl-badge').forEach(b=> b.textContent = count);
    }catch(_){}
  }

  function avatarHtml(avatarUrl, sellerName){
    const initial = (sellerName||'U').trim() ? (sellerName.trim()[0] || 'U').toUpperCase() : 'U';
    if (avatarUrl) {
      return `<img src="${esc(avatarUrl)}" alt="${esc(sellerName||'User')}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
    }
    return initial; // only the first letter (no "Img" placeholder)
  }
  function sellerNameHtml(c){
  const name = (c.seller||'').trim() || 'User';
  const sid  = c.seller_id || c.user_id || null;
  const url  = sid ? userDetailsUrl(sid) : '';
  // Use a span with a data-user-url so we can navigate on click
  return `<span class="name" data-user-url="${esc(url)}" style="cursor:pointer;">${esc(name)}</span>`;
}


  // ====== Cards (KEEP your .gig structure; with likes heart + count) ======
  function railCardHtml(c){
    const price  = (c.price && c.price!=='N/A') ? c.price : 'N/A';
    const likesRaw = c.wishlist_count ?? c.wish_count ?? c.likes ?? 0;
    const likes  = Number(likesRaw || 0);
    const userUrl = c.seller_id ? userDetailsUrl(c.seller_id) : '#';
    return `
<article class="gig product-card" data-id="${c.id}" data-likes="${likes}">
  <a href="${esc(c.url)}" class="product-link" data-id="${c.id}">
    <div class="gig-top">
      <div class="seller">
        <div class="avatar" data-user-url="${esc(userUrl)}">${avatarHtml(c.avatar, c.seller)}</div>
        <div class="meta">
          <div class="name-wrap">${sellerNameHtml(c)}</div>
          <div class="badge green">Top Rated Seller</div>
        </div>
      </div>
    </div>
    <div class="gig-media">
      <div class="img ph">
        <img src="${esc(c.cover)}" alt="${esc(c.name)}" style="height: 140px; width: 100%; border-radius: 12px; object-fit:cover;">
      </div>
    </div>
    <div class="gig-body">
      <div class="likes">
        <i class="fa-regular fa-heart"></i> <span class="likes-count">${formatCount(likes)}</span>
      </div>
      <h3>${esc(c.name || '')}</h3>
      <div class="stars"><i class="fa-solid fa-star"></i> <b>${esc(c.rating ?? '0.0')}</b> <span>(${esc(c.reviews ?? 0)})</span></div>
      <div class="price-row"><span class="from">Price</span><span class="price">${esc(price)}</span></div>
    </div>
  </a>
</article>`;
  }

  function gridCardHtml(c){
  const price   = (c.price && c.price!=='N/A') ? c.price : 'N/A';
  const likesRaw= c.wishlist_count ?? c.wish_count ?? c.likes ?? 0;
  const theLikes = Number(likesRaw || 0);
  const userUrl = c.seller_id ? userDetailsUrl(c.seller_id) : '#';
  return `
<article class="gig grid-gig product-card" data-id="${c.id}" data-likes="${theLikes}">
  <a href="${esc(c.url)}" class="product-link" data-id="${c.id}" style="text-decoration:none;">
    <div class="gig-top">
      <div class="seller">
        <div class="avatar" data-user-url="${esc(userUrl)}">${avatarHtml(c.avatar, c.seller)}</div>
        <div class="meta">
          <div class="name-wrap">${sellerNameHtml(c)}</div>
        </div>
      </div>
    </div>
    <div class="gig-media">
      <div class="img ph">
        <img src="${esc(c.cover)}" alt="${esc(c.name)}" style="height:170px;width:100%;border-radius:12px;object-fit:cover;">
      </div>
    </div>
    <div class="gig-body">
      <div class="likes">
        <i class="fa-regular fa-heart"></i> <span class="likes-count">${formatCount(theLikes)}</span>
      </div>
      <h3>${esc(c.name || '')}</h3>
      <div class="stars"><i class="fa-solid fa-star"></i> <b>${esc(c.rating ?? '0.0')}</b> <span>(${esc(c.reviews ?? 0)})</span></div>
      <div class="price-row"><span class="from">Price</span><span class="price">${esc(price)}</span></div>
    </div>
  </a>
</article>`;
}


  function renderRail(items){
    if (!railRoot) return;
    if (!items || !items.length){
      railRoot.innerHTML = `<div class="empty-box" style="grid-column:1/-1;">No products found.</div>`;
      dotsWrap && (dotsWrap.innerHTML = '');
      return;
    }
    railRoot.innerHTML = items.map(railCardHtml).join('');
    // dots
    if (dotsWrap){
      dotsWrap.innerHTML = '';
      items.forEach((_,i)=>{
        const d = document.createElement('span');
        d.className = 'dot' + (i===0?' active':'');
        dotsWrap.appendChild(d);
      });
      railRoot.addEventListener('scroll', ()=>{
        window.requestAnimationFrame(()=>{
          const cards = railRoot.querySelectorAll('.gig');
          if (!cards.length) return;
          const cardWidth = cards[0].getBoundingClientRect().width + 18;
          const idx = Math.round(railRoot.scrollLeft / cardWidth);
          dotsWrap.querySelectorAll('.dot').forEach((el,i)=> el.classList.toggle('active', i===idx));
        });
      }, { passive:true });
      document.querySelectorAll('.rail-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const dir = btn.dataset.dir === 'prev' ? -1 : 1;
          railRoot.scrollBy({ left: railRoot.clientWidth * 0.9 * dir, behavior: 'smooth' });
        });
      });
    }
    attachObservers();
    paintWishedHearts(); // after rendering
  }

  function renderGrid(items, append){
    if (!gridRoot) return;

    if (!append){
      if (!items || !items.length){
        gridRoot.innerHTML = `<div class="empty-box" style="grid-column:1/-1;">No products found.</div>`;
        if (gridCount) gridCount.textContent = '0 results';
        attachObservers();
        paintWishedHearts();
        return;
      }
      gridRoot.innerHTML = items.map(gridCardHtml).join('');
    } else {
      if (!items || !items.length) return;
      gridRoot.insertAdjacentHTML('beforeend', items.map(gridCardHtml).join(''));
    }

    if (gridCount){
      const total = gridRoot.querySelectorAll('.product-card').length;
      gridCount.textContent = total + ' results';
    }
    attachObservers();
    paintWishedHearts();
  }

  // ===== Toggle wishlist on likes click =====
document.addEventListener('click', async (e)=>{
  const likesEl = e.target.closest('.likes');
  if (!likesEl) return;

  e.preventDefault();
  e.stopPropagation();

  const card = likesEl.closest('.product-card');
  const pid  = +card?.dataset?.id || 0;
  if (!pid) return;

  // prevent double taps while in-flight
  if (card.dataset.busy === '1') return;
  card.dataset.busy = '1';

  // If not logged in -> open modal instead of redirect
  if (!IS_LOGGED) {
    openLoginModal();
    card.dataset.busy = '0';
    return;
  }

  try{
    const res = await fetch(WL_TOGGLE_URL, {
      method:'POST',
      headers:{
        'X-CSRF-TOKEN': CSRF,
        'Content-Type':'application/json',
        'Accept':'application/json'
      },
      body: JSON.stringify({ product_id: pid })
    });

    const js = await res.json().catch(()=>({}));

    // accept several common shapes: {ok,wished} or {status:'ok'} or {added:true}/{removed:true} …
    const ok = js.ok === true || js.status === 'ok' || js.success === true;
    const wished =
      (typeof js.wished === 'boolean') ? js.wished :
      (js.added === true) ? true :
      (js.removed === true) ? false :
      !!js.is_wished;

    // some APIs also return the absolute count
    const serverCount = (typeof js.count === 'number') ? js.count
                      : (typeof js.likes === 'number') ? js.likes
                      : undefined;

    if (ok || typeof js.wished !== 'undefined'){
      setWishStateForProduct(pid, wished, serverCount);
      // your toast already aliased as uhToast in your code
      (window.uhToast || window.toast)?.(
        wished ? 'Added to wishlist' : 'Removed from wishlist',
        wished ? 'success' : 'danger'
      );
      refreshWishlistBadge?.();
    }
  } catch(err){
    console.error('Wishlist toggle failed:', err);
  } finally {
    card.dataset.busy = '0';
  }
}, { passive:false });




  // ===== Avatar/Name → user details =====
document.addEventListener('click', (e)=>{
  const who = e.target.closest('.seller .avatar, .seller .name');
  if (!who) return;
  const url = who.getAttribute('data-user-url');
  if (url){
    e.preventDefault();
    // stop the outer product <a> from navigating
    e.stopPropagation();
    window.location.href = url;
  }
}, { passive:false });


  // ===== Apply filled hearts for already-wished products (persist after reload) =====
 async function paintWishedHearts(){
  try{
    const res = await fetch(WL_IDS_URL, { headers:{ 'Accept':'application/json' }});
    const js  = await res.json().catch(()=>({}));
    const ids = (js && Array.isArray(js.ids)) ? new Set(js.ids.map(x=>+x)) : new Set();

    document.querySelectorAll('.product-card[data-id]').forEach(card=>{
      const id    = +card.dataset.id;
      const heart = card.querySelector('.likes i');
      if (!heart) return;
      const wished = ids.has(id);

      heart.classList.toggle('fa-solid',   wished);
      heart.classList.toggle('fas',        wished);
      heart.classList.toggle('fa-regular', !wished);
      heart.classList.toggle('far',        !wished);
      heart.style.color = wished ? '#ff3b3b' : '';
    });
  }catch(_){}
}


  // ===== Analytics (click + batched views) =====
  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a.product-link'); if(!a) return;
    if (e.target.closest('.likes')) return; // ignore if clicked on heart/likes
    // Allow normal navigation; just fire analytics (no preventDefault)
    const id = a.dataset.id || a.closest('.product-card')?.dataset.id;
    if (!id) return;
    const body = new URLSearchParams({ product_id:id, source:'marketplace' });
    body.append('_token', CSRF);
    if (navigator.sendBeacon){
      const blob = new Blob([body], {type:'application/x-www-form-urlencoded;charset=UTF-8'});
      navigator.sendBeacon(CLICK_URL, blob);
    } else {
      fetch(CLICK_URL, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-CSRF-TOKEN':CSRF}, body }).catch(()=>{});
    }
  }, { passive:true });

  const seen = new Set(); let batch = []; let flushTimer=null;
  function queueView(id){
    if (!id || seen.has(id)) return;
    seen.add(id);
    batch.push(+id);
    clearTimeout(flushTimer);
    flushTimer = setTimeout(flushViews, 700);
  }
  function flushViews(){
    if (!batch.length) return;
    const items = batch.splice(0, batch.length);
    fetch(LIST_VIEW_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ items, source:'marketplace' }),
      keepalive:true
    }).catch(()=>{});
  }
  let io;
  function attachObservers(){
    if (io) io.disconnect();
    if (!('IntersectionObserver' in window)){
      document.querySelectorAll('.product-card[data-id]').forEach(a=>queueView(a.getAttribute('data-id')));
      flushViews(); return;
    }
    io = new IntersectionObserver((entries)=>{
      entries.forEach(entry=>{
        if (entry.isIntersecting){
          const id = entry.target.getAttribute('data-id');
          queueView(id); io.unobserve(entry.target);
        }
      });
    }, { root:null, threshold:0.35 });
    document.querySelectorAll('.product-card[data-id]').forEach(a=>io.observe(a));
  }

// ===== Filters → backend list (ALSO applies to boosted); multi-sub aware =====
function buildParamsFromDetail(d){
  const p = new URLSearchParams();
  p.set('page', pager.page);
  p.set('per_page', pager.per_page);

  // ALSO read URL directly as a fallback (first-load from other pages)
  const sp  = new URLSearchParams(window.location.search);
  const tid = parseInt(sp.get('type_id') || '', 10);

  // Category NAME → type_id; if no category in state, fall back to URL ?type_id
  if (d.category && TYPES && TYPES.length){
    const mt = TYPES.find(t => String(t.name).toLowerCase() === String(d.category).toLowerCase());
    if (mt) p.set('type_id', mt.id);
    else if (tid) p.set('type_id', tid); // safety fallback
  } else if (tid) {
    p.set('type_id', tid);                // safety fallback
  }

  // Multi-sub: from state AND from URL (?sub_id, sub_ids[])
  const stateSubIds = [];
  if (Array.isArray(d.subcategories) && d.subcategories.length && ALL_SUBCATS && ALL_SUBCATS.length){
    const byName = Object.create(null);
    ALL_SUBCATS.forEach(s => byName[String(s.name).toLowerCase()] = s.id);
    d.subcategories.forEach(scName=>{
      const sid = byName[String(scName).toLowerCase()];
      if (sid) stateSubIds.push(sid);
    });
  }
  const urlSubIds = sp.getAll('sub_ids[]').map(v => parseInt(v,10)).filter(Boolean);
  const legacyOne = parseInt(sp.get('sub_id') || '', 10);
  if (legacyOne) urlSubIds.push(legacyOne);

  // Merge & append
  const merged = Array.from(new Set([...stateSubIds, ...urlSubIds]));
  merged.forEach(id => p.append('sub_ids[]', id));

  // Trust + Price range (applies to boosted because server recomputes both)
  if (d.trustAIOnly) p.set('uses_ai', '1');   else p.delete('uses_ai');
  if (d.trustTeam)   p.set('has_team', '1');  else p.delete('has_team');
  if (d.priceMin != null) p.set('price_min', d.priceMin); else p.delete('price_min');
  if (d.priceMax != null) p.set('price_max', d.priceMax); else p.delete('price_max');

  // relevant | price_asc | price_desc | newest | rating(if supported)
  p.set('sort', d.sortBy || 'relevant');

  return p;
}

  // Small response normalizer so “items/boosted_items/next/has_more” always exist
function normalizeResponse(raw){
  const pagedItems      = (raw?.items?.data) ?? raw?.data ?? raw?.cards ?? raw?.initialCards ?? raw?.items ?? [];
  const boostedFallback = raw?.boosted_items ?? raw?.boosted ?? raw?.boostedCards ?? [];

  // compute has_more without mixing ?? and ||
  let hasMoreGuess = false;
  if (raw) {
    if (typeof raw.has_more !== 'undefined') {
      hasMoreGuess = !!raw.has_more;
    } else if (typeof raw.hasMore !== 'undefined') {
      hasMoreGuess = !!raw.hasMore;
    } else if (typeof raw.more !== 'undefined') {
      hasMoreGuess = !!raw.more;
    } else {
      const hasNext =
        (raw.next != null) ||
        (raw.nextPage != null) ||
        (raw.next_page_url != null) ||
        (raw.items && raw.items.next_page_url != null);
      hasMoreGuess = !!hasNext;
    }
  }

  const nextGuess =
    (raw?.next ?? raw?.nextPage ?? raw?.next_page ?? raw?.items?.next_page ?? null);

  return {
    items: Array.isArray(raw) ? raw : (Array.isArray(pagedItems) ? pagedItems : []),
    boosted_items: Array.isArray(boostedFallback) ? boostedFallback : [],
    has_more: hasMoreGuess,
    next: nextGuess
  };
}


  document.addEventListener('filters:change', (e)=>{
    pager.page = 1;
    currentFilterDetail = e.detail || currentFilterDetail;
    currentParams = buildParamsFromDetail(currentFilterDetail);

    showSpinner(true);
    fetch(LIST_URL + '?' + currentParams.toString(), { headers:{ 'Accept':'application/json' }})
      .then(r=>{
        if (!r.ok) throw new Error('List fetch failed: ' + r.status);
        return r.json();
      })
      .then((raw)=>{
        const { items, boosted_items, has_more, next } = normalizeResponse(raw);

        // Boosted respect same filters (including price range + trust)
        if (Array.isArray(boosted_items)) renderRail(boosted_items);
        else renderRail([]); // clear

        renderGrid(items, false);

        pager.has_more = !!has_more;
        pager.next = next || null;

        // Spinner shows only if more pages exist
        showSpinner(pager.has_more && !!pager.next);
        refreshWishlistBadge(); // update header badge on each load
      })
      .catch((err)=>{
        console.error(err);
        renderRail([]);
        renderGrid([], false);
        showSpinner(false);
      });
  });

  // ===== Load more (infinite scroll via sentinel) =====
  let loadingMore = false;

  async function loadMoreProducts(){
    if (loadingMore || !(pager.has_more && pager.next)) return;
    loadingMore = true;
    showSpinner(true);

    try{
      const p = new URLSearchParams(currentParams);
      p.set('page', pager.next);
      p.set('per_page', pager.per_page);

      const res = await fetch(LIST_URL + '?' + p.toString(), { headers:{ 'Accept':'application/json' }});
      if (!res.ok) throw new Error('Load more failed: ' + res.status);

      const raw = await res.json();
      const { items, has_more, next } = normalizeResponse(raw);

      renderGrid(items, true);
      pager.has_more = !!has_more;
      pager.next     = next || null;

      // toggle spinner based on availability of more pages
      showSpinner(pager.has_more && !!pager.next);
      refreshWishlistBadge();
    } catch (e) {
      console.error(e);
      showSpinner(false);
    } finally {
      loadingMore = false;
    }
  }

  // Observer that watches the sentinel just under the grid
  (function attachLoadMoreObserver(){
    const sentinel = document.getElementById('infiniteSentinel');
    if (!('IntersectionObserver' in window) || !sentinel) return;
    const io = new IntersectionObserver((entries)=>{
      entries.forEach((ent)=>{
        if (ent.isIntersecting && pager.has_more && !loadingMore){
          loadMoreProducts();
        }
      });
    }, { root:null, rootMargin: '220px 0px 0px 0px', threshold: 0 });
    io.observe(sentinel);
  })();

document.addEventListener('DOMContentLoaded', () => {
  // Use filters that the 3rd script prepared, or fall back to "show all"
  const detail = (window.__initialFilters) ? window.__initialFilters : {
    category: null,
    subcategories: [],
    priceMin: null,
    priceMax: null,
    sortBy: 'relevant',
    trustTeam: false,
    trustAIOnly: false
  };

  document.dispatchEvent(new CustomEvent('filters:change', { detail }));
  if (typeof refreshWishlistBadge === 'function') refreshWishlistBadge();
});


})();
</script>




@include('common.footer')
