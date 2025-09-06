@include('common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('customcss/marketplace.css') }}">

{{-- Wishlist (heart) minimal styles --}}
<style>
  .card.position-relative { position: relative; }
  .wish-btn {
    position: absolute; top: 8px; right: 8px;
    width: 36px; height: 36px;
    border: 0; outline: none;
    border-radius: 50%;
    background: rgba(0,0,0,.45);
    display: inline-flex; align-items: center; justify-content: center;
    z-index: 2;
  }
  .wish-btn i { font-size: 18px; color: #CEFF1B !important; }
  .wish-btn:hover { background: rgba(0,0,0,.6); }

  /* Small inline error under price inputs (kept minimal) */
  #priceError { display:none; font-size: 12px; color: #dc3545; margin-top: 6px; }
</style>

<div class="container mt-5">
  <!-- Top bar: CATEGORY scroller (➕ new) -->
  <div class="d-flex align-items-center gap-2 mb-2">
    <button class="filter-toggle" id="filterToggle"><i class="fas fa-filter"></i> Filter</button>
    <div class="scroll-container" id="categoryRow"></div>
  </div>

  <!-- Second row: SUBCATEGORY scroller (kept) -->
  <div class="d-flex align-items-center gap-2 mb-2">
    <div class="scroll-container" id="scrollableRow"></div>
  </div>

  <div id="appliedFilters" class="applied-filters mt-2 d-flex flex-wrap gap-2"></div>
</div>

<!-- Sidebar -->
<div class="filter-sidebar" id="filterSidebar">
  <div class="filter-header">
    <h5>Filters</h5>
    <button id="closeSidebar" style="color: white;">&times;</button>
  </div>
  <div class="filter-content mt-3">
    <h6>Product Categories</h6>
    <div class="filter-tags" id="typeTags">
      @foreach($types as $t)
        <span data-type="type" data-id="{{ $t->id }}">{{ $t->name }}</span>
      @endforeach
    </div>

    <h6 class="mt-3">Trust Filter</h6>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="verifiedSwitch">
      <label class="form-check-label" for="verifiedSwitch">Team</label>
    </div>
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="aiSwitch">
      <label class="form-check-label" for="aiSwitch">AI-Powered only</label>
    </div>
    <small class="text-muted d-block mt-1">(* Use “Apply filters” to apply Team/AI.)</small>

    <h6 class="mt-3">Price Range ({{ $targetCurrencySymbol }})</h6>
    <div class="d-flex gap-2">
      <input type="number" min="0" step="1" class="form-control" id="priceMin" placeholder="Min">
      <input type="number" min="0" step="1" class="form-control" id="priceMax" placeholder="Max">
    </div>
    <div id="priceError">Please enter a valid price range (Min ≤ Max).</div>

    <h6 class="mt-3">Sort By</h6>
    <select class="form-select" id="sortSelect">
      <option value="relevant">Most Relevant</option>
      <option value="price_asc">Price: Low to High</option>
      <option value="price_desc">Price: High to Low</option>
      <option value="newest">Newest</option>
    </select>

    <div class="d-grid mt-3">
      <button id="applyFilters" class="btn btn-dark">Apply filters</button>
    </div>
  </div>
</div>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

{{-- BOOSTED --}}
<section class="flat-spacing-17 flat-flash-sale">
  <div class="container">
    <div class="tf-flash-sale">
      <div class="flat-title flex-row justify-content-center">
        <span class="title fw-6">Popular Gigs</span>
      </div>

      <div id="boostedSection">
        @php $b = $boostedCards ?? collect(); @endphp
        @if($b->isEmpty())
          <p class="text-center text-muted">No boosted products yet.</p>
        @else
          @if(($boostedCount ?? $b->count()) > 6)
            <div id="boostedCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                @foreach($b->chunk(6) as $chunk)
                  <div class="carousel-item @if($loop->first) active @endif">
                    <div class="container py-4">
                      <div class="row g-4">
                        @foreach($chunk as $c)
                          <div class="col-md-4">
                            <a href="{{ $c['url'] ?? route('product.details', $c['id']) }}"
                               class="text-decoration-none text-reset product-card boosted-card"
                               data-id="{{ $c['id'] }}">
                              <div class="card h-100 position-relative">
                                {{-- Wishlist button --}}
                                <button type="button"
                                        class="wish-btn"
                                        title="Wishlist"
                                        data-id="{{ $c['id'] }}"
                                        data-wished="0">
                                  <i class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
                                </button>

                                <img src="{{ $c['cover'] }}" class="card-img-top" alt="{{ $c['name'] }}">
                                <div class="card-body">
                                  <div class="d-flex align-items-center mb-2 gap-2">
                                    <img src="{{ $c['avatar'] }}" class="rounded-circle" width="40" height="40" alt="User">
                                    <span class="username fw-6">{{ $c['seller'] }}</span>
                                    <span class="text-muted small d-flex align-items-center gap-1">
                                      <i class="fa fa-star text-warning"></i>
                                      {{ $c['rating'] }} ({{ $c['reviews'] }})
                                    </span>
                                  </div>
                                  <h6 class="card-title">{{ \Illuminate\Support\Str::limit($c['name'], 86) }}</h6>
                                  <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="badge bg-light border">Boosted</div>
                                    <div class="price">
                                      @if(!empty($c['price']))
                                        From {{ $c['price'] }}
                                      @else
                                        <span class="text-muted">Price N/A</span>
                                      @endif
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </a>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#boostedCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#boostedCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
              </button>
            </div>
          @else
            <div class="container py-4">
              <div class="row g-4">
                @foreach($b as $c)
                  <div class="col-md-4">
                    <a href="{{ $c['url'] ?? route('product.details', $c['id']) }}"
                       class="text-decoration-none text-reset product-card boosted-card"
                       data-id="{{ $c['id'] }}">
                      <div class="card h-100 position-relative">
                        {{-- Wishlist button --}}
                        <button type="button"
                                class="wish-btn"
                                title="Wishlist"
                                data-id="{{ $c['id'] }}"
                                data-wished="0">
                          <i class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
                        </button>

                        <img src="{{ $c['cover'] }}" class="card-img-top" alt="{{ $c['name'] }}">
                        <div class="card-body">
                          <div class="d-flex align-items-center mb-2 gap-2">
                            <img src="{{ $c['avatar'] }}" class="rounded-circle" width="40" height="40" alt="User">
                            <span class="username fw-6">{{ $c['seller'] }}</span>
                            <span class="text-muted small d-flex align-items-center gap-1">
                              <i class="fa fa-star text-warning"></i>
                              {{ $c['rating'] }} ({{ $c['reviews'] }})
                            </span>
                          </div>
                          <h6 class="card-title">{{ \Illuminate\Support\Str::limit($c['name'], 86) }}</h6>
                          <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="badge bg-light border">Verified</div>
                            <div class="price">
                              @if(!empty($c['price']))
                                From {{ $c['price'] }}
                              @else
                                <span class="text-muted">Price N/A</span>
                              @endif
                            </div>
                          </div>
                        </div>
                      </div>
                    </a>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        @endif
      </div>
    </div>
  </div>
</section>

{{-- MARKET GRID --}}
<section class="flat-flash-sale mb-3">
  <div class="container">
    <div class="flat-title flex-row justify-content-center">
      <span class="title fw-6">Marketplace</span>
    </div>

    <div class="container py-4">
      <div class="row g-4" id="marketGrid">
        @foreach($initialCards as $c)
          <div class="col-md-4">
            <a href="{{ $c['url'] }}" class="text-decoration-none text-reset market-card product-card" data-id="{{ $c['id'] }}">
              <div class="card h-100 position-relative">
                {{-- Wishlist button --}}
                <button type="button"
                        class="wish-btn"
                        title="Wishlist"
                        data-id="{{ $c['id'] }}"
                        data-wished="0">
                  <i class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
                </button>

                <img src="{{ $c['cover'] }}" class="card-img-top" alt="{{ $c['name'] }}">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-2 gap-2">
                    <img src="{{ $c['avatar'] }}" class="rounded-circle" width="40" height="40" alt="User">
                    <span class="username fw-6">{{ $c['seller'] }}</span>
                    <span class="text-muted small d-flex align-items-center gap-1">
                      <i class="fa fa-star text-warning"></i>
                      {{ $c['rating'] }} ({{ $c['reviews'] }})
                    </span>
                  </div>
                  <h6 class="card-title">{{ \Illuminate\Support\Str::limit($c['name'], 86) }}</h6>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="badge bg-light border">Verified</div>
                    <div class="price">
                      @if(!empty($c['price']) && $c['price'] !== 'N/A')
                        From {{ $c['price'] }}
                      @else
                        <span class="text-muted">Price N/A</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
        @endforeach
      </div>

      {{-- Load more --}}
      <div class="text-center mt-3">
        <button id="loadMoreBtn" class="btn btn-outline-dark"
          @if(!$hasMore) style="display:none" @endif
          data-next="{{ $nextPage ?? '' }}">Load more</button>
      </div>
    </div>
  </div>
</section>

@include('common.footer')

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const TYPES = @json($types->map(fn($t)=>['id'=>$t->id,'name'=>$t->name]));
  const ALL_SUBCATS = @json($subs->map(callback: fn($s)=>['id'=>$s->id,'name'=>$s->name,'type_id'=>$s->product_type_id]));
  const CLICK_URL = @json(route('analytics.product.click'));
  const LIST_URL = @json(route('marketplace.list'));
  const LIST_VIEW_URL = @json(route('analytics.list.view')); // batch views endpoint

  // 👉 Wishlist endpoints & auth
  const WL_TOGGLE_URL = @json(route('wishlist.toggle'));
  const WL_COUNT_URL  = @json(route('wishlist.count'));
  const WL_IDS_URL    = @json(route('wishlist.ids')); // must return {ids:[1,2,...]}
  const IS_LOGGED     = {{ auth()->check() || session('user_id') ? 'true' : 'false' }};
  const LOGIN_URL     = @json(route('login').'?redirect='.urlencode(request()->fullUrl()));

  // Sidebar open/close
  const filterToggle = document.getElementById('filterToggle');
  const filterSidebar = document.getElementById('filterSidebar');
  const closeSidebar = document.getElementById('closeSidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  function openSidebar(){ filterSidebar.classList.add('active'); sidebarBackdrop.classList.add('active'); document.body.classList.add('sidebar-open'); }
  function closeSidebarFunc(){ filterSidebar.classList.remove('active'); sidebarBackdrop.classList.remove('active'); document.body.classList.remove('sidebar-open'); }
  filterToggle.addEventListener('click', openSidebar);
  closeSidebar.addEventListener('click', closeSidebarFunc);
  sidebarBackdrop.addEventListener('click', closeSidebarFunc);

  // State
  const state = {
    type_id: null,
    sub_id: null,
    uses_ai: false, // applied via "Apply" button
    has_team: false, // applied via "Apply" button
    price_min: null, // applied via "Apply" button
    price_max: null, // applied via "Apply" button
    sort: 'relevant', // applies immediately
    page: 1,
    per_page: 24
  };

  /* 🔹 INIT FROM URL (server-provided for first load) */
  const INIT = {
    type_id: @json(request()->integer('type_id')),
    sub_id:  @json(request()->integer('sub_id')),
  };
  if (INIT.type_id) state.type_id = String(INIT.type_id);
  if (INIT.sub_id)  state.sub_id  = String(INIT.sub_id);

  // ===== URL <-> State helpers =====
  function paramsToState(search) {
    const u = new URLSearchParams(search || window.location.search);
    const get = (k)=>u.get(k);
    state.type_id   = get('type_id') || state.type_id || null;
    state.sub_id    = get('sub_id')  || state.sub_id  || null;
    state.sort      = get('sort') || state.sort || 'relevant';
    state.uses_ai   = get('uses_ai') === '1' ? true : (!!state.uses_ai && get('uses_ai')!== '0');
    state.has_team  = get('has_team')=== '1' ? true : (!!state.has_team && get('has_team')!== '0');
    state.price_min = get('price_min') ?? state.price_min;
    state.price_max = get('price_max') ?? state.price_max;
  }
  function stateToParams() {
    const p = new URLSearchParams();
    if (state.type_id)   p.set('type_id', state.type_id);
    if (state.sub_id)    p.set('sub_id', state.sub_id);
    if (state.sort && state.sort !== 'relevant') p.set('sort', state.sort);
    if (state.uses_ai)   p.set('uses_ai', '1');
    if (state.has_team)  p.set('has_team', '1');
    if (state.price_min) p.set('price_min', state.price_min);
    if (state.price_max) p.set('price_max', state.price_max);
    return p;
  }
  function updateUrl(replace=false){
    const url = new URL(window.location.href);
    const p = stateToParams();
    url.search = p.toString() ? ('?' + p.toString()) : '';
    const data = { marketplaceFilters: true, ...Object.fromEntries(p.entries()) };
    if (replace) history.replaceState(data, '', url);
    else history.pushState(data, '', url);
  }
  function syncUIFromState(){
    // highlight category chips (top row + sidebar tags)
    renderCategoryRow(); // will set active state for top row
    typeTags.querySelectorAll('span[data-type="type"]').forEach(el=>{
      el.classList.toggle('active', state.type_id && String(el.dataset.id)===String(state.type_id));
    });
    // checkboxes/inputs
    aiSwitch.checked       = !!state.uses_ai;
    verifiedSwitch.checked = !!state.has_team;
    priceMin.value         = state.price_min ?? '';
    priceMax.value         = state.price_max ?? '';
    sortSelect.value       = state.sort || 'relevant';
    // sub scroller + pills
    renderSubcategoryRow();
    renderAppliedFilters();
  }

  // Maps
  const typeMap = Object.fromEntries(TYPES.map(t=>[String(t.id), t.name]));
  const subsByType = ALL_SUBCATS.reduce((acc, s)=>((acc[s.type_id] ||= []).push(s), acc), {});

  // ===== CATEGORY scroller (top row)
  const categoryRow = document.getElementById('categoryRow');
  function renderCategoryRow() {
    if (!categoryRow) return;
    categoryRow.innerHTML = '';
    for (const t of TYPES) {
      const btn = document.createElement('button');
      btn.className = 'category-btn';
      btn.textContent = t.name;
      btn.dataset.id = String(t.id);
      if (String(state.type_id) === String(t.id)) btn.classList.add('active');
      btn.addEventListener('click', ()=>{
        // toggle highlight
        categoryRow.querySelectorAll('.category-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');

        // set state & choose first sub
        state.type_id = String(t.id);
        const list = subsByType[state.type_id] || [];
        state.sub_id = list.length ? String(list[0].id) : null;

        renderSubcategoryRow();
        renderAppliedFilters();

        // mirror highlight in sidebar tags, too
        typeTags.querySelectorAll('span[data-type="type"]').forEach(el=>{
          el.classList.toggle('active', String(el.dataset.id) === String(state.type_id));
        });

        updateUrl();        // reflect category change
        resetAndLoad(true);
      });
      categoryRow.appendChild(btn);
    }
  }

  // ===== SUBCATEGORY scroller (existing)
  const subRow = document.getElementById('scrollableRow');
  function renderSubcategoryRow() {
    subRow.innerHTML = '';
    const subs = state.type_id ? (subsByType[state.type_id] || []) : ALL_SUBCATS;
    for (const s of subs) {
      const btn = document.createElement('button');
      btn.className = 'category-btn';
      btn.textContent = s.name;
      if (String(state.sub_id) === String(s.id)) btn.classList.add('active');
      btn.addEventListener('click', ()=>{
        subRow.querySelectorAll('.category-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        state.sub_id = String(s.id);
        renderAppliedFilters();
        updateUrl();            // 🔄 reflect sub change
        resetAndLoad(true);     // sub selection auto-applies
      });
      subRow.appendChild(btn);
    }

    // drag / swipe
    let isDown=false,startX,scrollLeft;
    subRow.addEventListener("mousedown",(e)=>{isDown=true;subRow.classList.add("dragging");startX=e.pageX-subRow.offsetLeft;scrollLeft=subRow.scrollLeft;});
    subRow.addEventListener("mouseleave",()=>{isDown=false;subRow.classList.remove("dragging");});
    subRow.addEventListener("mouseup",()=>{isDown=false;subRow.classList.remove("dragging");});
    subRow.addEventListener("mousemove",(e)=>{if(!isDown)return;e.preventDefault();const x=e.pageX-subRow.offsetLeft;const walk=(x-startX)*2;subRow.scrollLeft=scrollLeft-walk;});
    subRow.addEventListener("touchstart",(e)=>{isDown=true;startX=e.touches[0].pageX-subRow.offsetLeft;scrollLeft=subRow.scrollLeft;});
    subRow.addEventListener("touchend",()=>{isDown=false;});
    subRow.addEventListener("touchmove",(e)=>{if(!isDown)return;const x=e.touches[0].pageX-subRow.offsetLeft;const walk=(x-startX)*2;subRow.scrollLeft=scrollLeft-walk;});
  }

  // Type tags (sidebar): auto-pick first subcategory under this category and apply
  const typeTags = document.getElementById('typeTags');

  /* 🔹 Highlight type chip on first load if URL had a type_id */
  if (state.type_id) {
    typeTags.querySelectorAll('span[data-type="type"]').forEach(el=>{
      if (String(el.dataset.id) === String(state.type_id)) el.classList.add('active');
    });
  }

  typeTags.addEventListener('click', (e)=>{
    const tag = e.target.closest('span[data-type="type"]'); if(!tag) return;
    typeTags.querySelectorAll('span').forEach(el=>el.classList.remove('active'));
    tag.classList.add('active');

    state.type_id = String(tag.dataset.id);
    const list = subsByType[state.type_id] || [];
    state.sub_id = list.length ? String(list[0].id) : null;

    // reflect in top category row too
    renderCategoryRow();

    renderSubcategoryRow();
    renderAppliedFilters();
    updateUrl();            // 🔄 reflect type change
    resetAndLoad(true);
    closeSidebarFunc();
  });

  // Trust toggles & price (apply on button)
  const aiSwitch = document.getElementById('aiSwitch');
  const verifiedSwitch = document.getElementById('verifiedSwitch');
  aiSwitch.addEventListener('change', ()=>{ state.uses_ai = aiSwitch.checked; renderAppliedFilters(); });
  verifiedSwitch.addEventListener('change', ()=>{ state.has_team = verifiedSwitch.checked; renderAppliedFilters(); });

  const priceMin = document.getElementById('priceMin');
  const priceMax = document.getElementById('priceMax');
  const priceError = document.getElementById('priceError');

  function validatePricesInline(){
    const minVal = priceMin.value ? Number(priceMin.value) : null;
    const maxVal = priceMax.value ? Number(priceMax.value) : null;
    const bad = (minVal !== null && minVal < 0) || (maxVal !== null && maxVal < 0) || (minVal !== null && maxVal !== null && minVal > maxVal);
    priceError.style.display = bad ? 'block' : 'none';
    return !bad;
  }
  priceMin.addEventListener('input', validatePricesInline);
  priceMax.addEventListener('input', validatePricesInline);

  // Sort applies immediately
  const sortSelect = document.getElementById('sortSelect');
  sortSelect.addEventListener('change', ()=>{
    state.sort = sortSelect.value;
    updateUrl();            // 🔄 reflect sort change
    resetAndLoad(true);
  });

  // Pills
  const appliedFiltersContainer = document.getElementById('appliedFilters');
  function renderAppliedFilters(){
    appliedFiltersContainer.innerHTML = '';
    if (state.type_id) addPill('Category: '+(typeMap[state.type_id] || state.type_id),'type_id');
    if (state.sub_id) {
      const subName = (ALL_SUBCATS.find(x=>String(x.id)===String(state.sub_id))||{}).name || state.sub_id;
      addPill('Subcategory: '+ subName,'sub_id');
    }
    if (state.uses_ai) addPill('AI-powered','uses_ai');
    if (state.has_team) addPill('Team','has_team');
    if (priceMin.value) addPill('Min ' + priceMin.value,'price_min');
    if (priceMax.value) addPill('Max ' + priceMax.value,'price_max');
  }
  function addPill(text,key){
    const el=document.createElement('span');
    el.innerHTML = `${text} <span class="remove-filter" data-key="${key}">&times;</span>`;
    appliedFiltersContainer.appendChild(el);
  }
  appliedFiltersContainer.addEventListener('click',(e)=>{
    const btn=e.target.closest('.remove-filter'); if(!btn) return;
    const k=btn.dataset.key;
    if (k==='type_id'){ state.type_id=null; typeTags.querySelectorAll('span').forEach(el=>el.classList.remove('active')); state.sub_id=null; renderSubcategoryRow(); renderCategoryRow(); }
    if (k==='sub_id'){ state.sub_id=null; }
    if (k==='uses_ai'){ state.uses_ai=false; aiSwitch.checked=false; }
    if (k==='has_team'){ state.has_team=false; verifiedSwitch.checked=false; }
    if (k==='price_min'){ priceMin.value=''; state.price_min=null; }
    if (k==='price_max'){ priceMax.value=''; state.price_max=null; }
    renderAppliedFilters();
    updateUrl(); // 🔄 reflect pill removal
    // auto-apply only for category/subcategory pills
    if (k==='type_id' || k==='sub_id') resetAndLoad(true);
  });

  // Apply button (AI/Team + price)
  document.getElementById('applyFilters').addEventListener('click', ()=>{
    // ✅ validate price inputs
    if (!validatePricesInline()) return;

    state.price_min = priceMin.value || null;
    state.price_max = priceMax.value || null;
    updateUrl();            // 🔄 reflect applied filters
    resetAndLoad(true);
    closeSidebarFunc();
  });

  // Initial selectors (from URL or server INIT)
  paramsToState();
  renderCategoryRow();      // ➕ new top row
  renderSubcategoryRow();
  renderAppliedFilters();
  // Normalize URL on first paint (remove empty/default params)
  updateUrl(true); // replaceState

  // Load more
  const loadMoreBtn = document.getElementById('loadMoreBtn');
  loadMoreBtn.addEventListener('click', ()=>{
    const next = parseInt(loadMoreBtn.dataset.next || '0', 10);
    if (!next) return;
    loadPage(next, false);
  });

  // Helpers
  function resetAndLoad(replace){
    state.page = 1;
    loadPage(1, replace);
  }

  function buildQuery(page){
    const p = new URLSearchParams();
    p.set('page', page);
    p.set('per_page', state.per_page);
    if (state.type_id) p.set('type_id', state.type_id);
    if (state.sub_id)  p.set('sub_id', state.sub_id);
    if (state.sort)    p.set('sort', state.sort);
    if (state.uses_ai) p.set('uses_ai', 1);
    if (state.has_team)p.set('has_team', 1);
    if (state.price_min) p.set('price_min', state.price_min);
    if (state.price_max) p.set('price_max', state.price_max);
    return p.toString();
  }

  let loading = false;
  function loadPage(page, replace){
    if (loading) return; loading=true;
    fetch(LIST_URL + '?' + buildQuery(page), { headers: { 'Accept':'application/json' }})
      .then(r=>r.json())
      .then(({ items, has_more, next, boosted_items })=>{
        if (page === 1 && boosted_items) renderBoosted(boosted_items);
        renderGrid(items, !replace);
        // update Load More
        if (has_more && next){
          loadMoreBtn.style.display = '';
          loadMoreBtn.dataset.next = next;
        } else {
          loadMoreBtn.style.display = 'none';
          loadMoreBtn.dataset.next = '';
        }
        // after rendering, sync wishlist visuals
        applyWishedHearts();
      })
      .catch(console.error)
      .finally(()=> loading=false);
  }

  function renderBoosted(items){
    const root = document.getElementById('boostedSection');
    if (!items || !items.length) {
      root.innerHTML = `<p class="text-center text-muted">No boosted products yet.</p>`;
      attachObservers(); // keep observers alive
      return;
    }
    if (items.length > 6){
      let html = `<div id="boostedCarousel" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner">`;
      for (let i=0; i<items.length; i+=6){
        const slide = items.slice(i,i+6);
        html += `<div class="carousel-item ${i===0?'active':''}"><div class="container py-4"><div class="row g-4">`;
        for (const c of slide) html += colHtml(c, true);
        html += `</div></div></div>`;
      }
      html += `</div>
        <button class="carousel-control-prev" type="button" data-bs-target="#boostedCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#boostedCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
      </div>`;
      root.innerHTML = html;
    } else {
      let html = `<div class="container py-4"><div class="row g-4">`;
      for (const c of items) html += colHtml(c, true);
      html += `</div></div>`;
      root.innerHTML = html;
    }
    attachObservers(); // observe new nodes
    applyWishedHearts(); // fill hearts based on wishlist
  }

  function renderGrid(items, append){
    const grid = document.getElementById('marketGrid');
    const frag = document.createDocumentFragment();
    for (const c of items){
      const col = document.createElement('div'); col.className='col-md-4';
      col.innerHTML = cardHtml(c, false);
      frag.appendChild(col);
    }
    if (!append) grid.innerHTML = '';
    grid.appendChild(frag);
    attachObservers(); // observe new nodes
  }

  // ✅ FIX: ensure dynamic boosted items are wrapped in Bootstrap columns
  function colHtml(c, boosted){
    return `<div class="col-md-4">` + cardHtml(c, boosted) + `</div>`;
  }

  function cardHtml(c, boosted){
    const priceHtml = (c.price && c.price !== 'N/A') ? `From ${escapeHtml(c.price)}`
                      : `<span class="text-muted">Price N/A</span>`;
    return `
      <a href="${c.url}" class="text-decoration-none text-reset product-card ${boosted?'boosted-card':'market-card'}" data-id="${c.id}">
        <div class="card h-100 position-relative">
          <button type="button" class="wish-btn" title="Wishlist" data-id="${c.id}" data-wished="0">
            <i class="fa-regular far fa-heart" style="color:#CEFF1B !important;"></i>
          </button>
          <img src="${c.cover}" class="card-img-top" alt="${escapeHtml(c.name)}">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2 gap-2">
              <img src="${c.avatar}" class="rounded-circle" width="40" height="40" alt="User">
              <span class="username fw-6">${escapeHtml(c.seller)}</span>
              <span class="text-muted small d-flex align-items-center gap-1">
                <i class="fa fa-star text-warning"></i>
                ${c.rating} (${c.reviews})
              </span>
            </div>
            <h6 class="card-title">${escapeHtml(c.name.length>86?c.name.slice(0,83)+'...':c.name)}</h6>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="badge bg-light border">Verified</div>
              <div class="price">${priceHtml}</div>
            </div>
          </div>
        </div>
      </a>`;
  }
  function escapeHtml(s){return (s??'').replace(/[&<>'"]/g,(c)=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}

  // -------- Wishlist helpers (no SweetAlert) --------
  function requireLogin(cb){
    if (IS_LOGGED) return cb();
    window.location.href = LOGIN_URL;
  }

  function setHeart(el, wished){
    const i = el.querySelector('i');
    el.dataset.wished = wished ? '1' : '0';
    if (i){
      i.classList.toggle('fa-solid', wished);
      i.classList.toggle('fas', wished);
      i.classList.toggle('fa-regular', !wished);
      i.classList.toggle('far', !wished);
      // keep the brand color always
      i.style.color = '#CEFF1B';
    }
  }

  async function refreshWishlistBadge(){
    try {
      const res = await fetch(WL_COUNT_URL);
      const js  = await res.json();
      const b   = document.getElementById('wishlistCountBadge') || document.querySelector('.nav-wishlist .count-box');
      if (b && js && typeof js.count==='number') b.textContent = js.count;
    } catch(_){}
  }

  // Delegated click for ALL current/future hearts
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.wish-btn');
    if (!btn) return;

    // prevent navigating to product link
    e.preventDefault(); e.stopPropagation();

    const pid = parseInt(btn.dataset.id || '0', 10);
    if (!pid) return;

    requireLogin(async () => {
      try{
        const res = await fetch(WL_TOGGLE_URL, {
          method: 'POST',
          headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json'},
          body: JSON.stringify({ product_id: pid })
        });
        const js = await res.json().catch(()=>({}));
        if (js && js.ok){
          setHeart(btn, !!js.wished);
          refreshWishlistBadge();
        }
      }catch(_){}
    });
  }, {passive:false});

  // Fill solid hearts for already-wished products
  async function applyWishedHearts(){
    try{
      const res = await fetch(WL_IDS_URL, { headers: { 'Accept':'application/json' }});
      const js  = await res.json().catch(()=>({}));
      const ids = (js && Array.isArray(js.ids)) ? new Set(js.ids.map(x=>+x)) : new Set();
      document.querySelectorAll('.wish-btn[data-id]').forEach(btn=>{
        const id = +btn.dataset.id;
        setHeart(btn, ids.has(id));
      });
    }catch(_){
      // ignore — icons will default to regular until toggled
    }
  }

  // -------- Analytics (Clicks + View/Impression via IntersectionObserver, BATCHED) --------
  // Clicks (delegated so it also works for dynamically loaded cards)
  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a.product-card');
    if (!a) return;
    // ignore clicks that originated from heart button
    if (e.target.closest('.wish-btn')) return;

    const id = a.getAttribute('data-id'); if (!id) return;
    // sendBeacon for reliability on navigation
    const body = new URLSearchParams({ product_id:id, source:'marketplace' });
    body.append('_token', CSRF);
    if (navigator.sendBeacon) {
      const blob = new Blob([body], {type:'application/x-www-form-urlencoded;charset=UTF-8'});
      navigator.sendBeacon(CLICK_URL, blob);
    } else {
      fetch(CLICK_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-CSRF-TOKEN':CSRF},
        body
      }).catch(()=>{});
    }
  }, { passive:true });

  // Views (in-viewport) — batched to analytics.list.view
  const seen = new Set();
  let batch = [];
  let flushTimer = null;

  function queueView(id){
    if (!id || seen.has(id)) return;
    seen.add(id);
    batch.push(id);
    clearTimeout(flushTimer);
    flushTimer = setTimeout(flushViews, 700);
  }
  function flushViews(){
    if (!batch.length) return;
    const items = batch.splice(0, batch.length);
    fetch(LIST_VIEW_URL, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ items, source: 'marketplace' }),
      keepalive: true
    }).catch(()=>{});
  }

  let io;
  function attachObservers(){
    if (io) io.disconnect();
    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll('.product-card[data-id]').forEach(a=>queueView(a.getAttribute('data-id')));
      flushViews();
      return;
    }
    io = new IntersectionObserver((entries)=>{
      entries.forEach(entry=>{
        if (entry.isIntersecting) {
          const id = entry.target.getAttribute('data-id');
          queueView(id);
          io.unobserve(entry.target);
        }
      });
    }, { root: null, threshold: 0.35 });

    document.querySelectorAll('.product-card[data-id]').forEach(a=>io.observe(a));
  }

  // 🔙 Back/forward: re-apply filters from URL and reload
  window.addEventListener('popstate', ()=>{
    paramsToState();
    syncUIFromState();
    resetAndLoad(true);
  });

  window.addEventListener('DOMContentLoaded', ()=>{
    // Make sure UI reflects current (normalized) state
    syncUIFromState();
    attachObservers();
    applyWishedHearts(); // prefill hearts on first paint
    refreshWishlistBadge(); // make sure header count is correct on landing
  });
</script>
