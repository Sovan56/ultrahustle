@include('admin.common.header')
@section('title','All Members')

<div class="main-content">
  <section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
      <h1 class="m-0">All Members</h1>
      <div>
        <a href="{{ route('admin.members.export',['type'=>'csv']) }}" class="btn btn-outline-primary mx-1">Export CSV</a>
        <a href="{{ route('admin.members.export',['type'=>'pdf']) }}" class="btn btn-primary mx-1">Export PDF</a>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <input id="m-search" type="text" class="form-control" placeholder="Search name / email / phone" style="max-width:320px;">
            <select id="m-perpage" class="form-control" style="max-width:120px;">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <button id="m-btn-search" class="btn btn-primary">Search</button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone Number</th>
                  <th>Location</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody id="m-tbody">
                <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="m-total" class="text-muted small"></div>
            <nav>
              <ul id="m-pager" class="pagination mb-0"></ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

<script>
(function(){
  function getCsrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.getAttribute) return m.getAttribute('content');
    const i = document.querySelector('input[name="_token"]');
    if (i) return i.value;
    return null;
  }
  const csrf = getCsrfToken();
  if (csrf && window.jQuery) $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf }});

  const $tbody = $('#m-tbody');
  const $pager = $('#m-pager');
  const $total = $('#m-total');
  const $q     = $('#m-search');
  const $per   = $('#m-perpage');

  let state = { page: 1, q: '', per_page: 10 };

  function fetchList() {
    $tbody.html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    $.getJSON('{{ route('admin.members.list') }}', state, function(res){
      if (!res || !res.ok) return $tbody.html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
      renderTable(res.data || []);
      renderMeta(res.meta || {});
    }).fail(function(){
      $tbody.html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
    });
  }

  function renderTable(rows) {
    if (!rows.length) {
      $tbody.html('<tr><td colspan="6" class="text-center text-muted">No data</td></tr>');
      return;
    }
    let html = '';
    rows.forEach(r => {
      const img = r.avatar
        ? `<img src="${r.avatar}" class="rounded" style="width:36px;height:36px;object-fit:cover;border:1px solid #eee;">`
        : `<img src="{{ asset('assets/img/default-user.png') }}" class="rounded" style="width:36px;height:36px;object-fit:cover;border:1px solid #eee;">`;
      html += `
        <tr>
          <td>${img}</td>
          <td>${esc(r.name || '')}</td>
          <td>${esc(r.email || '')}</td>
          <td>${esc(r.phone_number || '')}</td>
          <td>${esc(r.location || '')}</td>
          <td>${esc(r.created_at || '')}</td>
        </tr>`;
    });
    $tbody.html(html);
  }

  function renderMeta(meta) {
    const cp = meta.current_page || 1, lp = meta.last_page || 1, total = meta.total || 0;
    $total.text(`Page ${cp} of ${lp} — Total ${total}`);
    let html = '';
    function li(p, label, active, disabled) {
      return `<li class="page-item ${active?'active':''} ${disabled?'disabled':''}">
                <a class="page-link" href="#" data-p="${p}">${label}</a>
              </li>`;
    }
    html += li(cp-1, '&laquo;', false, cp<=1);
    for (let p = Math.max(1, cp-2); p <= Math.min(lp, cp+2); p++) html += li(p, p, cp===p, false);
    html += li(cp+1, '&raquo;', false, cp>=lp);
    $pager.html(html);
  }

  $pager.on('click', 'a.page-link', function(e){
    e.preventDefault();
    const p = parseInt(this.dataset.p, 10);
    if (Number.isFinite(p)) { state.page = p; fetchList(); }
  });

  $('#m-btn-search').on('click', function(){
    state.q = $q.val().trim();
    state.page = 1;
    state.per_page = parseInt($per.val(), 10) || 10;
    fetchList();
  });

  function esc(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

  fetchList();
})();
</script>

@include('admin.common.footer')
