@include('UserAdmin.common.header')
@section('title','My Orders')
<!-- <link rel="stylesheet" href="{{ asset('rebuildfrontend/css/marketplace.css') }}"> -->

<!-- Font Awesome -->
<!-- Font Awesome 6.6.0 -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
  referrerpolicy="no-referrer"
/>

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1 style="color: #cefe1b;">My Orders</h1>
    </div>

    <div class="section-body">
      <div class="row" id="orders-container">
        <!-- Orders load here -->
      </div>

      <!-- Pagination -->
      <nav id="pagination-wrapper" class="mt-4">
        <ul class="pagination justify-content-center"></ul>
      </nav>

      <!-- Loader -->
      <div class="text-center mt-4" id="loader" style="display:none;">
        <div class="spinner-border text-primary"></div>
      </div>
    </div>
  </section>
</div>
@include('UserAdmin.common.footer')

<!-- ===== FILES MODAL ===== -->
<div class="modal fade" id="filesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius: 16px;border: 1px solid #CEFE1B;">
      <div class="modal-header" style="border-top-left-radius: 16px;background: #1e1e1e !important; border-top-right-radius: 16px;border: 1px solid #CEFE1B;">
        <h5 class="modal-title">Order Files</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="filesModalBody" style="background: #1e1e1e;border-bottom-left-radius: 16px;border-bottom-right-radius: 16px;border-bottom: 1px solid #CEFE1B;"></div>
    </div>
  </div>
</div>

<!-- ===== LINKS MODAL ===== -->
<div class="modal fade" id="linksModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius: 16px;border: 1px solid #CEFE1B;">
      <div class="modal-header" style="border-top-left-radius: 16px;background: #1e1e1e !important; border-top-right-radius: 16px;border: 1px solid #CEFE1B;">
        <h5 class="modal-title">Course Links</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="linksModalBody" style="background: #1e1e1e;border-bottom-left-radius: 16px;border-bottom-right-radius: 16px;border-bottom: 1px solid #CEFE1B;"></div>
    </div>
  </div>
</div>


<script>
$(function(){
  let currentPage = 1;
  loadOrders(currentPage);

  function loadOrders(page=1){
    $("#loader").show();
    $.get("{{ route('user.myorders.data') }}", { page: page }, function(res){
      $("#loader").hide();
      const orders = res.data || [];
      const container = $("#orders-container").empty();

      if(!orders.length){
        container.html(`<div class="col-12 text-center text-muted mt-5">No orders found.</div>`);
        $("#pagination-wrapper").hide();
        return;
      }

      orders.forEach(o => container.append(orderCard(o)));
      renderPagination(res.current_page, res.last_page);
    }).fail(()=>alert("Error loading orders."));
  }














function coverUrl(path) {
  if (!path) return '/static/img/no-image.jpg';

  // If it's a JSON array string, parse it safely
  if (typeof path === 'string' && path.trim().startsWith('[')) {
    try {
      const arr = JSON.parse(path);
      if (Array.isArray(arr) && arr.length > 0) {
        path = arr[0];
      }
    } catch (_) {
      // ignore parse errors and continue with original
    }
  }

  // If it's an actual array (not stringified)
  if (Array.isArray(path) && path.length > 0) {
    path = path[0];
  }

  // Prevent double prefix if it's already a full URL
  if (/^https?:\/\//i.test(path)) return path;

  // Normalize and prepend /media/
  return `/media/${String(path).replace(/^\/+/, '')}`;
}


function orderCard(o) {
  const p = o.product || {};
  const likes = Number(o.wishlist_count ?? 0);
  const files = p.delivery_files || [];
  const links = p.course_urls || [];
  const price = (p.price && p.price !== 'N/A') ? p.price : 'N/A';
  const rating = (p.rating ?? 0).toFixed(1);
  const reviews = p.reviews ?? 0;

  // Files & links
  const hasFiles = files.length > 0, hasLinks = links.length > 0;
  const fileBtn = hasFiles
    ? (files.length === 1
      ? `<a href="${esc(files[0].url)}" class="btn btn-sm"><i class="fa fa-download" ></i> ${esc(files[0].name)}</a>`
      : `<button class="btn btn-sm  view-files" style="font-size: 20px;padding: 10px; border-radius: 16px;" data-files='${esc(JSON.stringify(files))}'><i class="fa fa-folder-open"></i> Files (${files.length})</button>`)
    : '';

  const linkBtn = hasLinks
    ? (links.length === 1
      ? `<a href="${esc(links[0])}" target="_blank" class="btn btn-sm" style="display: flex;justify-content: center;align-items: center;padding: 10px;border-radius: 16px;font-size:20px;"><i class="fa fa-link" style="margin-right:10px;"></i> Open Link</a>`
      : `<button class="btn btn-sm view-links" style="font-size: 20px;padding: 10px; border-radius: 16px;" data-links='${esc(JSON.stringify(links))}'><i class="fa fa-link"></i> Links (${links.length})</button>`)
    : '';

  // === Seller & avatar ===
  const avatar = coverUrl(p.avatar);
  const sellerName = sellerNameHtml({ seller: p.seller, seller_id: p.seller_id });

  // === Proper cover URL using Laravel route ===
  const cover = esc(coverUrl(p.cover));

  return `
<article class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4" style="padding:5px 15px;">
  <div class="gig product-card card h-100" style="box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35); border-radius:16px !important; margin-bottom:0px !important; background-color: #1e1e1e !important;">
    <a href="/product/${esc(o.product.id)}" class="product-link d-block" style="text-decoration:none;">
      <div class="gig-media" style="padding:5px 05px;">
        <img src="${cover}" alt="${esc(p.name || '')}"
             style="height:160px;width:100%;object-fit:cover;border-radius:10px;">
      </div>

      <div class="gig-body p-3">

        <h5 class="mb-1">${esc(p.name || '')}</h5>
        <p class="mb-1"><b>Status:</b> 
          <span style="color: ${o.status === 'completed' ? 'red' : 'white'};">
            ${esc(o.status)}
          </span>
        </p>
        <div class="price-row mt-2">
          <span class="from">Price </span>
          <span class="price text-primary">${esc(price)}</span>
        </div>
      </div>
    </a>

    <div class="card-footer d-flex flex-wrap gap-2 justify-content-between">
      <a href="/product/${esc(o.product.id)}" class="btn btn-sm" style="font-size: 20px;padding: 10px; border-radius: 16px;">
        <i class="fa fa-eye"></i> View
      </a>
      ${fileBtn}
      ${linkBtn}
    </div>
  </div>
</article>`;
}

 function esc(s){ return String(s ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
  function formatCount(n){
    n = Number(n||0);
    if (n >= 1e9) return (n/1e9).toFixed(n%1e9?1:0)+'B';
    if (n >= 1e6) return (n/1e6).toFixed(n%1e6?1:0)+'M';
    if (n >= 1e3) return (n/1e3).toFixed(n%1e3?1:0)+'K';
    return String(n);
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













  // <div class="gig-top p-2">
  //       <div class="seller d-flex align-items-center">
  //         <div class="avatar mr-2">${avatar}</div>
  //         <div class="meta">
  //           <div class="name-wrap"><strong>${p.seller ?? 'Seller'}</strong></div>
  //           <div class="badge badge-success"><i class="fa fa-star"></i> Top Rated</div>
  //         </div>
  //       </div>
  //     </div>


  //  <div class="d-flex justify-content-between align-items-center mb-2">
  //         <div class="likes">
  //           <i class="fa-regular fa-heart text-danger"></i>
  //           <span class="likes-count">${likes}</span>
  //         </div>
  //         <div class="stars text-warning">
  //           <i class="fa-solid fa-star"></i> <b>${rating}</b>
  //           <span class="text-muted">(${reviews})</span>
  //         </div>
  //       </div>

  // === Avatar Parser (JSON or URL) ===
  function parseAvatar(data, name){
    let url = '';
    if(data){
      try {
        const obj = JSON.parse(data);
        if(Array.isArray(obj) && obj.length) url = obj[0];
        else url = obj.url || obj.path || obj.src || '';
      } catch(e){ url = data; }
    }
    if(url) return `<img src="${url}" class="rounded-circle" style="width:38px;height:38px;object-fit:cover;">`;
    const initial = (name && name.length) ? name.charAt(0).toUpperCase() : '?';
    return `<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">${initial}</div>`;
  }

  // === Cover Parser (JSON or URL) ===
  function parseCover(cover){
    if(!cover) return '/images/placeholder.png';
    try {
      const img = JSON.parse(cover);
      if(Array.isArray(img) && img.length) return img[0];
      if(img.url || img.path) return img.url || img.path;
    } catch(e){ return cover; }
    return cover;
  }

  // === File Modal ===
  $(document).on("click",".view-files",function(){
    const files = JSON.parse($(this).attr("data-files"));
    let html = `<ul class="list-group">`;
    files.forEach(f=>{
      html += `<li class="list-group-item d-flex justify-content-between align-items-center" style="background-color: #1e1e1e !important;border: none !important;">
        <span><i class="fa fa-file me-2"></i> ${f.name}</span>
        <a href="${f.url}" class="btn btn-sm" style="background: #1e1e1e !important; border:none !important;"><i class="fa fa-download" style="background: #1e1e1e !important; border:none !important; font-size: 20px;"></i></a>
      </li>`;
    });
    html += `</ul>`;
    $("#filesModalBody").html(html);
    $("#filesModal").modal("show");
  });

  // === Links Modal ===
  $(document).on("click",".view-links",function(){
    const links = JSON.parse($(this).attr("data-links"));
    let html = `<ul class="list-group">`;
    links.forEach(l=>{
      html += `<li class="list-group-item d-flex justify-content-between align-items-center" style="background-color: #1e1e1e !important;border: none !important;">
        <span><i class="fa fa-link me-2 text-info"></i> ${l}</span>
        <a href="${l}" target="_blank" class="btn btn-sm" style="background: #1e1e1e !important; border:none !important;"><i class="fa fa-external-link-alt" style="background: #1e1e1e !important; border:none !important;font-size: 20px;"></i></a>
      </li>`;
    });
    html += `</ul>`;
    $("#linksModalBody").html(html);
    $("#linksModal").modal("show");
  });

  // === Pagination ===
  function renderPagination(current,last){
    const pag = $("#pagination-wrapper .pagination").empty();
    $("#pagination-wrapper").show();
    if(last<=1){ $("#pagination-wrapper").hide(); return; }

    const prevDis = current===1?'disabled':'';
    pag.append(`<li class="page-item ${prevDis}"><a href="#" class="page-link" data-page="${current-1}">&laquo;</a></li>`);

    for(let i=1;i<=last;i++){
      const act = i===current?'active':'';
      pag.append(`<li class="page-item ${act}"><a href="#" class="page-link" data-page="${i}">${i}</a></li>`);
    }

    const nextDis = current===last?'disabled':'';
    pag.append(`<li class="page-item ${nextDis}"><a href="#" class="page-link" data-page="${current+1}">&raquo;</a></li>`);
  }

  $(document).on("click",".pagination a",function(e){
    e.preventDefault();
    const p = $(this).data("page");
    if(p && p>0){ currentPage=p; loadOrders(p); $("html,body").animate({scrollTop:0},300); }
  });
});
</script>
