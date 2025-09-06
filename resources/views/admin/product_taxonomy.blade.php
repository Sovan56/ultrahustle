{{-- resources/views/admin/product_taxonomy.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
  .dt-center{text-align:center}
  .text-right{text-align:right}
  .desc-trunc{max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .icon-preview { font-size: 22px; }
  .filter-row .form-control { min-width: 220px; }
  .chip { display:inline-block; border-radius:20px; padding:2px 10px; background:#eef3f7; margin-left:8px; font-size:12px; }
  .clickable { cursor: pointer; }
  .service-card { width: 170px; }
.swal2-popup{
    background-color: black;
}
  /* Icon picker (inside SweetAlert modal) */
  .iconpicker-wrap { position: relative; }
  .iconpicker-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    z-index: 10000; background: black; border: 1px solid #e5e5e5;
    border-radius: 6px; margin-top: 6px; box-shadow: 0 8px 30px rgba(0,0,0,.08);
    padding: 10px; display: none;
  }
  .iconpicker-head { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
  .iconpicker-search { flex: 1; }
  .iconpicker-grid {
    max-height: 260px; overflow: auto; display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 8px;
  }
  .iconpicker-item {
    border: 1px solid #eee; border-radius: 6px; padding: 10px; text-align: center; cursor: pointer;
    transition: all .12s ease-in-out;
  }
  .iconpicker-item:hover { background: #f4f7fb; }
  .iconpicker-item i { font-size: 18px; display: block; margin-bottom: 6px; }
  .iconpicker-item small { font-size: 10px; color: #6c757d; display:block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .iconpicker-foot { display:flex; justify-content: space-between; align-items:center; margin-top:8px; }
  .badge-pill { border-radius: 999px; padding: 4px 10px; font-size: 11px; background:#eef3f7; }
  .btn-iconpicker { white-space: nowrap; }
  .form-hint { font-size: 12px; color:#6c757d; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">Product Taxonomy</h1>
      <div>
        <button id="btnAddType" class="btn btn-primary">Add Type</button>
        <button id="btnAddSub" class="btn btn-info ml-2">Add Subcategory</button>
      </div>
    </div>

    <div class="section-body">

      {{-- TYPES --}}
      <div class="card">
        <div class="card-header justify-content-between align-items-center w-100">
          <h4 class="m-0">Types <span class="chip" id="sumTypes">0</span></h4>
        </div>
        <div class="card-body">
          <table id="tblTypes" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>Name</th>
                <th class="dt-center">Active</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <small class="text-muted">Tip: Click a Type name to filter subcategories below.</small>
        </div>
      </div>

      {{-- SUBCATEGORIES --}}
      <div class="card">
        <div class="card-header justify-content-between align-items-center w-100">
          <h4 class="m-0">
            Subcategories <span class="chip" id="sumSubs">0</span>
          </h4>
          <div class="filter-row d-flex">
            <select id="fltType" class="form-control">
              <option value="0">All Types</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <table id="tblSubs" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>Type</th>
                <th>Icon</th>
                <th>Name</th>
                <th class="dt-center">Active</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>

          {{-- Preview row like your public icon cards --}}
          <hr>
          <h6 class="mb-3">Preview (icons & titles)</h6>
          <div id="iconPreview" class="container mt-2">
            <div class="row" id="iconRow"></div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

@include('admin.common.footer')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  const CSRF = $('meta[name="csrf-token"]').attr('content');
  const GET  = (u,d={})=>$.getJSON(u,d);
  const POST = (u,d)=>$.ajax({url:u,method:'POST',data:d,dataType:'json',headers:{'X-CSRF-TOKEN':CSRF}});
  const DEL  = (u)=>$.ajax({url:u,method:'DELETE',dataType:'json',headers:{'X-CSRF-TOKEN':CSRF}});
  const onErr=(xhr,msg='Something went wrong')=>Swal.fire({icon:'error',title:'Error',text:xhr?.responseJSON?.message||msg});

  let dtTypes, dtSubs;
  let typeCache = [];
  let lastTypeFilter = 0;

  // --- util: slugify (frontend auto) ---
  const slugify = (txt='') => txt
    .toString().trim().toLowerCase()
    .normalize('NFKD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').substring(0,255);

  /* ==============================
   * Font Awesome 5 icon catalog
   * ============================== */
  const FA5_ICONS = [
    'fas fa-chalkboard-teacher','fas fa-paint-brush','fas fa-bullhorn','fas fa-pen','fas fa-video','fas fa-robot','fas fa-music','fas fa-users','fas fa-user-tie',
    'fas fa-code','fas fa-laptop-code','fas fa-mobile-alt','fas fa-database','fas fa-shopping-cart','fas fa-camera','fas fa-camera-retro','fas fa-image','fas fa-images',
    'fas fa-film','fas fa-microphone','fas fa-headphones','fas fa-palette','fas fa-brain','fas fa-cogs','fas fa-wrench','fas fa-tools','fas fa-bug',
    'fas fa-ad','fas fa-comments','fas fa-envelope','fas fa-search','fas fa-search-dollar','fas fa-chart-line','fas fa-chart-bar','fas fa-globe',
    'fas fa-server','fas fa-cloud','fas fa-cloud-upload-alt','fas fa-cloud-download-alt','fas fa-lock','fas fa-shield-alt','fas fa-store','fas fa-store-alt',
    'fas fa-book','fas fa-book-open','fas fa-graduation-cap','fas fa-school','fas fa-language','fas fa-pen-nib','fas fa-pencil-alt','fas fa-edit',
    'fas fa-copy','fas fa-file','fas fa-file-alt','fas fa-folder','fas fa-folder-open','fas fa-tags','fas fa-tag','fas fa-star','fas fa-heart',
    'fas fa-hands-helping','fas fa-briefcase','fas fa-business-time','fas fa-chart-pie','fas fa-lightbulb','fas fa-sitemap',
    'far fa-file','far fa-file-alt','far fa-image','far fa-edit','far fa-star','far fa-heart','far fa-lightbulb',
    'fab fa-figma','fab fa-adobe','fab fa-sketch','fab fa-wordpress','fab fa-shopify','fab fa-android','fab fa-apple','fab fa-github','fab fa-bitbucket','fab fa-laravel','fab fa-react','fab fa-vuejs','fab fa-node-js','fab fa-python'
  ];

  function fillTypeFilter(){
    const $sel = $('#fltType');
    $sel.empty().append(`<option value="0">All Types</option>`);
    typeCache.forEach(t => $sel.append(`<option value="${t.id}">${t.name}</option>`));
    if (lastTypeFilter) $sel.val(String(lastTypeFilter));
  }

  async function loadTypes(){
    const list = await GET('{{ route('admin.types.list') }}');
    typeCache = list;
    $('#sumTypes').text(list.length);

    const rows = list.map(r=>({
      id: r.id,
      name_html: `<span class="clickable text-primary" data-type-id="${r.id}" title="Click to filter subcategories">${$('<div>').text(r.name).html()}</span>`,
      name_plain: r.name,
      active: r.is_active ? 'Yes' : 'No',
      updated: r.updated_at || '-',
      actions: `
        <button class="btn btn-sm btn-outline-secondary btnEditType" data-id="${r.id}">Edit</button>
        <button class="btn btn-sm btn-outline-danger btnDelType" data-id="${r.id}">Delete</button>
      `
    }));

    if (!dtTypes) {
      dtTypes = $('#tblTypes').DataTable({
        data: rows,
        columns: [
          {data:'name_html'},
          {data:'active', className:'dt-center', width:80},
          {data:'updated', width:160},
          {data:'actions', orderable:false, className:'text-right', width:160}
        ],
        order:[[2,'desc']],
        pageLength: 25,
        responsive:true,
        destroy:true
      });
    } else {
      dtTypes.clear().rows.add(rows).draw();
    }

    fillTypeFilter();
  }

  async function loadSubs(){
    const type_id = parseInt($('#fltType').val(),10) || 0;
    lastTypeFilter = type_id;
    const list = await GET('{{ route('admin.subs.list') }}', { type_id });
    $('#sumSubs').text(list.length);

    const rows = list.map(r=>{
      const t = r.type?.name || '-';
      const icon = r.icon_class ? `<i class="${r.icon_class} icon-preview" title="${r.icon_class}"></i>` : '-';
      return {
        id: r.id,
        type_id: r.product_type_id,
        type: t,
        icon_html: icon,
        name: r.name,
        active: r.is_active ? 'Yes' : 'No',
        updated: r.updated_at || '-',
        actions: `
          <button class="btn btn-sm btn-outline-secondary btnEditSub" data-id="${r.id}">Edit</button>
          <button class="btn btn-sm btn-outline-danger btnDelSub" data-id="${r.id}">Delete</button>
        `
      };
    });

    if (!dtSubs) {
      dtSubs = $('#tblSubs').DataTable({
        data: rows,
        columns: [
          {data:'type'},
          {data:'icon_html', orderable:false, className:'dt-center', width:70},
          {data:'name'},
          {data:'active', className:'dt-center', width:80},
          {data:'updated', width:160},
          {data:'actions', orderable:false, className:'text-right', width:160}
        ],
        order:[[4,'desc']],
        pageLength: 25,
        responsive:true,
        destroy:true
      });
    } else {
      dtSubs.clear().rows.add(rows).draw();
    }

    // Build the icon preview grid like your example
    const $row = $('#iconRow').empty();
    list.forEach(sc=>{
      $row.append(`
        <div class="col-auto mb-3">
          <div class="service-card text-center p-3 border rounded">
            <i class="${sc.icon_class || ''}" style="font-size:20px"></i>
            <h5 class="card-title mt-2" style="font-size:14px">${$('<div>').text(sc.name).html()}</h5>
          </div>
        </div>
      `);
    });
  }

  /* ==============================
   * Icon Picker UI (inside modal)
   * ============================== */
  function buildIconPicker($wrap, $input, $preview){
    const dd = $(`
      <div class="iconpicker-dropdown">
        <div class="iconpicker-head">
          <input type="text" class="form-control iconpicker-search" placeholder="Search icons... e.g. video, robot, github">
          <span class="badge-pill"><span class="ip-count"></span> icons</span>
        </div>
        <div class="iconpicker-grid"></div>
        <div class="iconpicker-foot">
          <small class="form-hint">Click an icon to select. Uses Font Awesome 5 (solid/regular/brands).</small>
          <button type="button" class="btn btn-sm btn-light ip-close">Close</button>
        </div>
      </div>
    `);
    $wrap.append(dd);

    const $grid = dd.find('.iconpicker-grid');
    const $search = dd.find('.iconpicker-search');
    const $count = dd.find('.ip-count');

    function renderIcons(filter=''){
      const f = (filter||'').toLowerCase().trim();
      $grid.empty();
      const icons = FA5_ICONS.filter(cls => !f || cls.toLowerCase().includes(f));
      $count.text(icons.length);
      icons.forEach(cls=>{
        const name = cls.replace(/^fa[rsb]\s+/,'');
        $grid.append(`
          <div class="iconpicker-item" data-class="${cls}">
            <i class="${cls}"></i>
            <small title="${cls}">${name}</small>
          </div>
        `);
      });
    }

    renderIcons();

    $wrap.find('.btn-iconpicker').on('click', function(){
      dd.toggle();
      $search.trigger('focus');
    });

    dd.on('click','.ip-close', ()=> dd.hide());

    $(document).on('mousedown.iconpicker', function(e){
      if (!$.contains($wrap[0], e.target)) dd.hide();
    });

    $search.on('input', function(){
      renderIcons($(this).val());
    });

    $grid.on('click','.iconpicker-item', function(){
      const cls = $(this).data('class');
      $input.val(cls).trigger('input');
      if ($preview && $preview.length) {
        $preview.attr('class', cls).css('font-size','22px');
      }
      dd.hide();
    });
  }

  // ---------- Modals (SweetAlert) ----------
  function bindSlugAuto($form, nameSel, slugSel) {
    const $name = $form.find(nameSel);
    const $slug = $form.find(slugSel);

    let manual = false;
    $slug.on('input', ()=> { manual = true; });
    $name.on('input', ()=>{ if(!manual) $slug.val(slugify($name.val()||'')); });

    if (!($slug.val()||'').trim()) $slug.val(slugify($name.val()||''));
  }

  async function openTypeForm(type=null){
    const html = `
      <form id="frmType">
        <input type="hidden" name="id" value="${type?type.id:''}">
        <div class="form-group text-left">
          <label>Name</label>
          <input name="name" type="text" class="form-control" value="${type? (type.name_plain || type.name || '') : ''}" required maxlength="255">
        </div>
        <div class="form-group text-left">
          <label class="custom-switch mt-2">
            <input type="checkbox" name="is_active" class="custom-switch-input" ${type&&type.is_active?'checked':''}>
            <span class="custom-switch-indicator"></span>
            <span class="custom-switch-description">Active</span>
          </label>
        </div>
      </form>
    `;
    const result = await Swal.fire({
      title: type?'Edit Type':'Add Type',
      html, focusConfirm:false, showCancelButton:true, width:'650px',
      confirmButtonText:'Save',
      preConfirm: () => {
        const $f = $('#frmType');
        const payload = {
          id: $f.find('[name="id"]').val(),
          name: $f.find('[name="name"]').val()?.trim(),
          is_active: $f.find('[name="is_active"]').is(':checked') ? 1 : 0
        };
        if (!payload.name) { Swal.showValidationMessage('Enter type name'); return false; }
        return payload;
      }
    });
    if (!result.isConfirmed) return;

    try{
      await POST('{{ route('admin.types.save') }}', result.value);
      Swal.fire({icon:'success', title:'Saved'});
      await loadTypes();
      await loadSubs();
    }catch(xhr){ onErr(xhr,'Failed to save type'); }
  }

  async function openSubForm(sub=null){
    if (!typeCache.length) await loadTypes();

    const options = typeCache.map(t=>`<option value="${t.id}" ${sub&&sub.product_type_id==t.id?'selected':''}>${$('<div>').text(t.name).html()}</option>`).join('');

    const html = `
      <form id="frmSub">
        <input type="hidden" name="id" value="${sub?sub.id:''}">
        <div class="form-row">
          <div class="form-group text-left col-md-6">
            <label>Type</label>
            <select name="product_type_id" class="form-control" required>
              <option value="" disabled ${sub?'':'selected'}>Select type</option>
              ${options}
            </select>
          </div>
          <div class="form-group text-left col-md-6">
            <label>Icon Class <small class="text-muted">(e.g. fas fa-video)</small></label>
            <div class="iconpicker-wrap">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="${sub? (sub.icon_class || 'fas fa-icons') : 'fas fa-icons'}" id="ipPreview"></i></span>
                </div>
                <input name="icon_class" type="text" class="form-control" value="${sub? (sub.icon_class||'') : ''}" maxlength="120" placeholder="fas fa-robot" aria-describedby="ipHelp">
                <div class="input-group-append">
                  <button type="button" class="btn btn-light btn-iconpicker"><i class="fas fa-search"></i> Cheat-sheet</button>
                </div>
              </div>
              <small id="ipHelp" class="form-text text-muted">Click “Cheat-sheet” to pick; or paste any FA5 class.</small>
              <!-- Dropdown gets injected here -->
            </div>
          </div>
        </div>
        <div class="form-group text-left">
          <label>Name</label>
          <input name="name" type="text" class="form-control" value="${sub?sub.name||'':''}" required maxlength="255">
        </div>
        <div class="form-group text-left">
          <label class="custom-switch mt-2">
            <input type="checkbox" name="is_active" class="custom-switch-input" ${sub&&sub.is_active?'checked':''}>
            <span class="custom-switch-indicator"></span>
            <span class="custom-switch-description">Active</span>
          </label>
        </div>
      </form>
    `;

    const result = await Swal.fire({
      title: sub?'Edit Subcategory':'Add Subcategory',
      html, focusConfirm:false, showCancelButton:true, width:'720px',
      didOpen: ()=>{
        const $wrap   = $('.iconpicker-wrap');
        const $input  = $wrap.find('[name="icon_class"]');
        const $prev   = $('#ipPreview');
        $input.on('input', function(){ $prev.attr('class', $(this).val() || 'fas fa-icons'); });
        buildIconPicker($wrap, $input, $prev);
      },
      confirmButtonText: 'Save',
      preConfirm: () => {
        const $f = $('#frmSub');
        const payload = {
          id: $f.find('[name="id"]').val(),
          product_type_id: $f.find('[name="product_type_id"]').val(),
          name: $f.find('[name="name"]').val()?.trim(),
          icon_class: ($f.find('[name="icon_class"]').val() || '').trim(),
          is_active: $f.find('[name="is_active"]').is(':checked') ? 1 : 0
        };
        if (!payload.product_type_id) { Swal.showValidationMessage('Select type'); return false; }
        if (!payload.name) { Swal.showValidationMessage('Enter subcategory name'); return false; }
        return payload;
      }
    });
    if (!result.isConfirmed) return;

    try{
      await POST('{{ route('admin.subs.save') }}', result.value);
      Swal.fire({icon:'success', title:'Saved'});
      await loadSubs();
    }catch(xhr){ onErr(xhr,'Failed to save subcategory'); }
  }

  // ---------- Actions ----------
  $('#btnAddType').on('click', ()=>openTypeForm());
  $('#btnAddSub').on('click', ()=>openSubForm());

  $('#fltType').on('change', loadSubs);

  $('#tblTypes').on('click','.btnEditType', async function(){
    const id = $(this).data('id');
    try{
      const list = await GET('{{ route('admin.types.list') }}');
      const t = list.find(x=>x.id==id);
      if (t) await openTypeForm(t);
    }catch(xhr){ onErr(xhr,'Failed to load type'); }
  });

  $('#tblTypes').on('click','.btnDelType', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete this type?', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete'})
      .then(async r=>{
        if (!r.isConfirmed) return;
        try{
          await DEL(`{{ url('/admin/product-types') }}/${id}`);
          Swal.fire({icon:'success', title:'Deleted'});
          await loadTypes();
          await loadSubs();
        }catch(xhr){ onErr(xhr,'Failed to delete type'); }
      });
  });

  $('#tblTypes').on('click','span[data-type-id]', function(){
    const id = $(this).data('type-id');
    $('#fltType').val(String(id)).trigger('change');
  });

  $('#tblSubs').on('click','.btnEditSub', async function(){
    const id = $(this).data('id');
    try{
      if (!typeCache.length) await loadTypes();
      const list = await GET('{{ route('admin.subs.list') }}', { type_id: 0 });
      const s = list.find(x=>x.id==id);
      if (s) await openSubForm(s);
    }catch(xhr){ onErr(xhr,'Failed to load subcategory'); }
  });

  $('#tblSubs').on('click','.btnDelSub', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete this subcategory?', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete'})
      .then(async r=>{
        if (!r.isConfirmed) return;
        try{
          await DEL(`{{ url('/admin/product-subcategories') }}/${id}`);
          Swal.fire({icon:'success', title:'Deleted'});
          await loadSubs();
        }catch(xhr){ onErr(xhr,'Failed to delete subcategory'); }
      });
  });

  (async function init(){
    await loadTypes();
    await loadSubs();
  })();

})();
</script>
