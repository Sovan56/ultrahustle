{{-- resources/views/UserAdmin/boosts.blade.php --}}
@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>

<style>
  .chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:14px;background:#eef;border:1px solid #dde;font-size:12px;margin-left:6px}
  .pointer{cursor:pointer}
  .hidden{display:none}
  .table td,.table th{vertical-align:middle}
  .thumb{width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #eee;margin-right:8px}
  .btn-loading{pointer-events:none;opacity:.7}
  .btn-boosted{background:#CEFF1B!important;color:#000!important;border-color:#CEFF1B!important}
  .btn-boosted:disabled{opacity:1}
  .dataTables_filter{display:none}

  /* Otika-style pricing cards */
  .pricing-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
  .pricing-card{position:relative;background:#fff;border:1px solid #e9ecef;border-radius:16px;padding:18px 16px 14px;box-shadow:0 6px 24px rgba(0,0,0,.04);transition:transform .12s ease,box-shadow .12s ease}
  .pricing-card:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,0,0,.06)}
  .pricing-badge{display:inline-block;font-weight:700;color:#fff;font-size:12px;padding:6px 10px;border-radius:12px;background:linear-gradient(45deg,#6C63FF,#B845FF)}
  .pricing-price{font-weight:800;font-size:20px;margin:10px 0 6px}
  .pricing-meta{color:#6c757d;font-size:12px;margin-bottom:10px}
  .pricing-features{list-style:none;padding:0;margin:10px 0 16px}
  .pricing-features li{display:flex;align-items:center;margin-bottom:6px;font-size:13px}
  .pricing-features i{font-size:12px;margin-right:6px}
  .btn-buy{width:100%}
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Boost Posts</h1>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">

          {{-- PRODUCTS + FILTERS --}}
          <div class="card">
            <div class="card-header justify-content-between align-items-center w-100">
              <h4 class="m-0">Boost a Product</h4>
              <div class="text-muted">
                <span class="chip">Your currency: <b id="userCcy">—</b></span>
              </div>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <input type="text" id="searchProducts" class="form-control mr-2 mb-2" placeholder="Search your products…" style="max-width:320px">
                <select id="filterType" class="form-control mr-2 mb-2" style="max-width:220px"><option value="">All Types</option></select>
                <select id="filterSub" class="form-control mr-2 mb-2" style="max-width:240px" disabled><option value="">All Subcategories</option></select>
                <button id="btnResetFilters" class="btn btn-light mb-2">Reset</button>
              </div>

              <table class="table table-striped" id="tblProducts" style="width:100%">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Sales</th>
                    <th>Revenue</th>
                    <th>Price</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
              <div class="mt-2 text-right">
                <small id="prodCount">Totals 0</small>
              </div>
            </div>
          </div>

          {{-- ACTIVE BOOSTS --}}
          <div class="card">
            <div class="card-header justify-content-between align-items-center">
              <h4 class="m-0">Active Boosts</h4>
            </div>
            <div class="card-body">
              <table class="table table-striped" id="tblBoosts" style="width:100%">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Country</th>
                    <th>Days</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Amount (USD)</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
              <div class="text-muted"><small>Boost amounts are stored in USD for records; pricing is shown in your currency before purchase.</small></div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  @include('UserAdmin.common.settingbar')
</div>

@include('UserAdmin.common.footer')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function() {
  const CSRF = $('meta[name="csrf-token"]').attr('content');
  const GET = (url, data = {}) => $.ajax({ url, data, method: 'GET', dataType: 'json', cache: false });
  const POST = (url, data, isFD = false) => $.ajax({
    url, method: 'POST', data, dataType: 'json',
    processData: !isFD,
    contentType: isFD ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
    headers: { 'X-CSRF-TOKEN': CSRF }
  });
  const onErr = (xhr, msg = 'Something went wrong') => Swal.fire({ icon: 'error', title: 'Error', text: xhr?.responseJSON?.message || msg });
  const debounce = (fn, ms) => { let t=null; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms) } };

  let USER_CCY = 'USD';
  let SELECTED_PRODUCT_ID = null;
  let dtProducts = null, dtBoosts = null;
  let BOOSTED_SET = new Set();

  // server + client filters
  let FILTERS = { search:'', type_id:'', subcategory_id:'' };

  // ===== User meta
  async function loadUserMeta() {
    try {
      const meta = await GET('/user-admin/marketplace/user-meta');
      USER_CCY = meta?.currency || 'USD';
    } catch { USER_CCY = 'USD'; }
    $('#userCcy').text(USER_CCY);
  }

  // ===== Lookups
  async function loadTypes() {
    const types = await GET('/user-admin/marketplace/types');
    const $sel = $('#filterType').empty().append('<option value="">All Types</option>');
    (types||[]).forEach(t => $sel.append(`<option value="${t.id}">${t.name}</option>`));
  }
  async function loadSubcategories(typeId) {
    const $sub = $('#filterSub').prop('disabled', !typeId).empty().append('<option value="">All Subcategories</option>');
    if (!typeId) return;
    const subs = await GET('/user-admin/marketplace/subcategories', { type_id: typeId });
    (subs||[]).forEach(s => $sub.append(`<option value="${s.id}">${s.name}</option>`));
  }

  // ===== DataTables init (safe on empty)
  function initTables() {
    if (!dtProducts) {
      dtProducts = $('#tblProducts').DataTable({
        data: [],
        columns: [
          { data: 'product', orderable:false },
          { data: 'sales' },
          { data: 'revenue' },
          { data: 'price' },
          { data: 'action', orderable:false, className:'text-right' },
        ],
        pageLength: 10,
        order: [],
        language: { emptyTable: 'No products found' }
      });

      // Instant client-side search + debounced server refresh
      const reloadServer = debounce(() => loadProducts(true), 300);
      $('#searchProducts').on('input', function(){
        FILTERS.search = this.value || '';
        // client-side feel
        dtProducts.search(FILTERS.search).draw();
        // keep server in sync (fetch filtered dataset)
        reloadServer();
      });
    }

    if (!dtBoosts) {
      dtBoosts = $('#tblBoosts').DataTable({
        data: [],
        columns: [
          { data: 'product' },
          { data: 'country' },
          { data: 'days' },
          { data: 'start' },
          { data: 'end' },
          { data: 'amount_usd' },
        ],
        pageLength: 10,
        order: [[3,'desc']],
        language: { emptyTable: 'No active boosts' }
      });
    }
  }

  // ===== Products loader
  // forceRefetch=true when triggered by debounced search; otherwise initial loads keep current filter
  async function loadProducts(forceRefetch = false) {
    try {
      const list = await GET('/user-admin/marketplace/products', {
        search: FILTERS.search || '',
        type_id: FILTERS.type_id || '',
        subcategory_id: FILTERS.subcategory_id || ''
      });

      const rows = (list || []).map(p => ({
        id: p.id,
        product: `
          <div class="d-flex align-items-center">
            <img src="${p.thumbnail_url}" class="thumb" alt="">
            <div><strong>${p.name}</strong></div>
          </div>
        `,
        sales: p.sales,
        revenue: p.revenue,
        price: p.price,
        action: BOOSTED_SET.has(p.id)
          ? `<button class="btn btn-sm btn-boosted" disabled>Boosted</button>`
          : `<button class="btn btn-sm btn-primary btnBoost" data-id="${p.id}">Boost</button>`
      }));

      // replace data
      dtProducts.clear().rows.add(rows).draw();

      // re-apply client-side filter so visual matches the input immediately
      if (FILTERS.search) dtProducts.search(FILTERS.search).draw(false);
      else                dtProducts.search('').draw(false);

      $('#prodCount').text(`Totals ${rows.length}`);
    } catch (xhr) { onErr(xhr, 'Failed to load products'); }
  }

  // Filters
  $('#filterType').on('change', async function(){
    FILTERS.type_id = this.value || '';
    FILTERS.subcategory_id = '';
    await loadSubcategories(FILTERS.type_id);
    $('#filterSub').val('');
    await loadProducts(true);
  });

  $('#filterSub').on('change', async function(){
    FILTERS.subcategory_id = this.value || '';
    await loadProducts(true);
  });

  $('#btnResetFilters').on('click', async function(){
    FILTERS = { search:'', type_id:'', subcategory_id:'' };
    $('#searchProducts').val('');
    dtProducts && dtProducts.search('').draw(); // clear client-side filter immediately
    $('#filterType').val('');
    await loadSubcategories('');
    $('#filterSub').val('').prop('disabled', true);
    await loadProducts(true);
  });

  // ===== Active boosts
  async function loadActiveBoosts() {
    try {
      const list = await GET('/user-admin/marketplace/boosts/active');
      BOOSTED_SET = new Set((list||[]).map(b => b.product_id));

      const rows = (list || []).map(b => ({
        product: `${b.product_thumb ? `<img src="${b.product_thumb}" class="thumb">` : ''} ${b.product_name}`,
        country: b.country || '-',
        days: b.days,
        start: b.start_at || '-',
        end: b.end_at || '-',
        amount_usd: `$ ${b.amount_usd}`
      }));

      dtBoosts.clear().rows.add(rows).draw();
    } catch (xhr) { onErr(xhr, 'Failed to load boosts'); }
  }

  // ===== Plans
  async function openPlanModal(productId) {
    SELECTED_PRODUCT_ID = productId;
    $('#plansWrap').empty().append('<div class="text-center text-muted py-3">Loading plans…</div>');
    $('#boostModal').modal('show');

    try {
      const plans = await GET('/user-admin/marketplace/boost-plans'); // local currency from server
      renderPlanCards(plans || []);
    } catch (xhr) {
      $('#plansWrap').empty().append('<div class="text-center text-danger py-3">Failed to load plans</div>');
    }
  }

  function featuresFromDescription(text) {
    const items = String(text||'').split(/\r?\n/).map(s => s.trim()).filter(Boolean);
    if (!items.length) return '';
    return items.map(li => `<li><i class="fas fa-check text-success"></i>${li}</li>`).join('');
  }

  function renderPlanCards(plans) {
    const $wrap = $('#plansWrap').empty();
    if (!plans.length) {
      $wrap.append('<div class="text-center text-muted py-3">No active plans. Please contact Admin.</div>');
      return;
    }

    const grid = $('<div class="pricing-grid"></div>');
    plans.forEach((p) => {
      const price = `${p.currency} ${Number(p.price_local||0).toFixed(2)}`;
      const card = $(`
        <div class="pricing-card"
             data-id="${p.id}"
             data-name="${(p.name||'').replace(/"/g,'&quot;')}"
             data-days="${p.days}"
             data-ccy="${p.currency}"
             data-price="${Number(p.price_local||0).toFixed(2)}">
          <div class="d-flex align-items-center justify-content-between">
            <span class="pricing-badge">${(p.name||'').toUpperCase()}</span>
            <span class="badge badge-light">${p.days} day(s)</span>
          </div>
          <div class="pricing-price mt-2">${price}</div>
          <div class="pricing-meta">One-time total for this boost</div>
          ${p.description ? `<ul class="pricing-features">${featuresFromDescription(p.description)}</ul>` : `<ul class="pricing-features"><li><i class="fas fa-check text-success"></i>Boost visibility</li><li><i class="fas fa-check text-success"></i>Priority placement</li></ul>`}
          <button class="btn btn-primary btn-buy">BUY NOW</button>
        </div>
      `);
      grid.append(card);
    });
    $wrap.append(grid);
  }

  // Confirm before deducting
  $(document).on('click', '.pricing-card .btn-buy', async function() {
    const $card = $(this).closest('.pricing-card');
    const planId = $card.data('id');
    const planName = $card.data('name');
    const planDays = $card.data('days');
    const ccy = $card.data('ccy');
    const price = Number($card.data('price')).toFixed(2);

    if (!SELECTED_PRODUCT_ID || !planId) return;

    const confirm = await Swal.fire({
      icon: 'question',
      title: 'Confirm your boost',
      html: `
        <div class="text-left">
          <div><b>Plan:</b> ${planName}</div>
          <div><b>Duration:</b> ${planDays} day(s)</div>
          <div><b>Total:</b> ${ccy} ${price}</div>
        </div>
        <hr>
        <small class="text-muted">This amount will be deducted from your wallet.</small>
      `,
      showCancelButton: true,
      confirmButtonText: 'Confirm & Pay',
      cancelButtonText: 'Cancel'
    });

    if (!confirm.isConfirmed) return;

    const $btn = $(this);
    if ($btn.hasClass('btn-loading')) return;
    $btn.addClass('btn-loading').text('Processing…');

    try {
      await POST(`/user-admin/marketplace/products/${encodeURIComponent(SELECTED_PRODUCT_ID)}/boost`, { plan_id: planId });
      $('#boostModal').modal('hide');
      Swal.fire({ icon: 'success', title: 'Boost started' });
      await loadActiveBoosts();
      await loadProducts(true);
    } catch (xhr) {
      const res = xhr?.responseJSON || {};
      if (res.code === 'INSUFFICIENT_FUNDS') {
        const ccy = res.wallet_currency || $('#userCcy').text();
        const bal = Number(res.balance || 0).toFixed(2);
        const req = Number(res.required || 0).toFixed(2);
        Swal.fire({
          icon: 'warning',
          title: 'Not enough wallet balance',
          html: `Your balance is <b>${ccy} ${bal}</b> but <b>${ccy} ${req}</b> is required.<br><br>Recharge your wallet to continue.`,
          showCancelButton: true,
          confirmButtonText: 'Go to Wallet',
          cancelButtonText: 'Close'
        }).then(r => { if (r.isConfirmed) window.location.href = res.topup_url || '/UserAdmin/wallet'; });
      } else {
        onErr(xhr, res.message || 'Failed to start boost');
      }
    } finally {
      $btn.removeClass('btn-loading').text('BUY NOW');
    }
  });

  // Hook the "Boost" button (open plan picker)
  $('#tblProducts').on('click', '.btnBoost', function() {
    const id = $(this).data('id');
    openPlanModal(id);
  });

  // ===== Init
  (async function init() {
    initTables();              // set up DTs first to avoid column warnings
    await loadUserMeta();
    await loadTypes();
    await loadSubcategories('');
    await loadActiveBoosts();  // fills boosts DT
    await loadProducts(true);  // fills products DT
  })();
})();
</script>

<!-- Boost Plan Modal -->
<div class="modal fade" id="boostModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Choose a Pricing Plan</h5></div>
      <div class="modal-body">
        <div id="plansWrap" class="mb-2"><!-- cards injected here --></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
