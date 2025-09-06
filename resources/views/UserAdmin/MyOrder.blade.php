@include('UserAdmin.common.header')
@section('title','My Orders')

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>My Orders</h1>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-3">
              <select id="fltType" class="form-control">
                <option value="">All Types</option>
                <option value="digital">Digital</option>
                <option value="course">Course</option>
                <option value="service">Service</option>
              </select>
            </div>
            <div class="col-md-3">
              <select id="fltStatus" class="form-control">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="delivered">Delivered</option>
                <option value="completed">Completed</option>
                <option value="refunded">Refunded</option>
              </select>
            </div>
            <div class="col-md-3">
              <button id="btnReload" class="btn btn-primary">Search</button>
            </div>
          </div>

          <div id="ordersList"></div>
          <nav><ul class="pagination mt-3" id="pager"></ul></nav>
        </div>
      </div>
    </div>
  </section>
</div>
@include('UserAdmin.common.footer')

<script>
const DATA_URL = "{{ route('user.myorders.list') }}";

function esc(s){
  return String(s ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function aTag(href, text, extra=''){
  return `<a href="${href}" ${extra}>${esc(text)}</a>`;
}

function loadOrders(page=1){
  const type   = document.getElementById('fltType').value;
  const status = document.getElementById('fltStatus').value;
  const url = new URL(DATA_URL, window.location.origin);
  url.searchParams.set('page', page);
  if (type)   url.searchParams.set('type', type);
  if (status) url.searchParams.set('status', status);

  const box = document.getElementById('ordersList');
  box.innerHTML = `<div class="text-center text-muted py-4">Loading orders…</div>`;

  fetch(url).then(r => {
    if (!r.ok) throw new Error('Network');
    return r.json();
  }).then(d => {
    box.innerHTML = '';
    (d.data || []).forEach(o => {
      // delivery_files: preferred shape [{url, name}], but also allow raw strings
      const fileLis = (o.delivery_files || []).map((f, i) => {
        const href = typeof f === 'object' && f ? (f.url || '') : String(f || '');
        const name = typeof f === 'object' && f ? (f.name || `File ${i+1}`) : `File ${i+1}`;
        if (!href) return '';
        // downloads: no target=_blank, let browser download inline
        return `<li>${aTag(href, `Download ${name}`)}</li>`;
      }).join('');

      const urlLis = (o.course_urls || []).map(u =>
        `<li>${aTag(u, u, 'target="_blank" rel="noopener"')}</li>`
      ).join('');

      box.insertAdjacentHTML('beforeend', `
        <div class="border rounded p-3 mb-2">
          <div class="d-flex justify-content-between">
            <div>
              <div class="fw-bold">${esc(o.product?.name ?? 'Product')}</div>
              <div class="small text-muted">
                Order #${esc(o.id)} • ${esc(o.currency ?? '')} ${Number(o.total_amount ?? 0).toFixed(2)}
              </div>
            </div>
            <div><span class="badge bg-secondary">${esc(o.status ?? '')}</span></div>
          </div>

          ${fileLis ? `<hr><div><b>Files</b><ul class="mb-0">${fileLis}</ul></div>` : ''}
          ${urlLis  ? `<hr><div><b>Course Links</b><ol class="mb-0">${urlLis}</ol></div>` : ''}
        </div>
      `);
    });

    // pager
    const pager = document.getElementById('pager'); pager.innerHTML = '';
    const last = Number(d.last_page ?? 1), cur = Number(d.current_page ?? 1);
    for (let i=1;i<=last;i++){
      pager.insertAdjacentHTML('beforeend', `
        <li class="page-item ${i===cur?'active':''}">
          <a class="page-link" href="javascript:void(0)" onclick="loadOrders(${i})">${i}</a>
        </li>
      `);
    }
  }).catch(() => {
    box.innerHTML = `<div class="alert alert-danger mb-0">Failed to load orders.</div>`;
  });
}

document.getElementById('btnReload').addEventListener('click', () => loadOrders(1));
document.addEventListener('DOMContentLoaded', () => loadOrders());
</script>
