@include('UserAdmin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('customcss/adminmarketplace.css') }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Orders</h1>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">

          <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
              <h4 class="m-0">
                Orders
                <span class="chip">New <b id="sumNew">0</b></span>
                <span class="chip">In-progress <b id="sumProg">0</b></span>
                <span class="chip">Completed <b id="sumDone">0</b></span>
                <span class="chip">Canceled <b id="sumCancel">0</b></span>
              </h4>
              <div class="d-flex">
                <select id="fltType" class="form-control mr-2" style="min-width:200px"></select>
                <select id="fltSub" class="form-control mr-2" style="min-width:220px">
                  <option value="">All subcategories</option>
                </select>
                <input type="text" id="orderSearch" class="form-control" placeholder="Search product..." style="min-width:220px;">
              </div>
            </div>

            <div class="card-body">
              <table class="table table-striped" id="tblOrders">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Subcategory</th>
                    <th>Buyer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Ordered</th>
                    <th class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  @include('UserAdmin.common.settingbar')
</div>

@include('UserAdmin.common.footer')

{{-- IMPORTANT: Do NOT include another jQuery here; common.footer already loaded jQuery + Bootstrap --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Safety shims: avoid crashes from missing niceScroll / modal plugin binding --}}
<script>
(function($){
  if (!$ || !$.fn) return;
  // Prevent theme scripts from crashing if niceScroll is missing
  if (!$.fn.niceScroll) $.fn.niceScroll = function(){ return this; };
})(window.jQuery);
</script>
<script>
(function($){
  // If Bootstrap 5 is present as window.bootstrap.Modal but $.fn.modal is missing (e.g., jQuery was reloaded elsewhere),
  // provide a small jQuery adapter so $('#...').modal('show') works.
  if ($ && !$.fn.modal && window.bootstrap && window.bootstrap.Modal) {
    $.fn.modal = function(action){
      return this.each(function(){
        var inst = window.bootstrap.Modal.getOrCreateInstance(this);
        if (action === 'show') inst.show();
        else if (action === 'hide') inst.hide();
      });
    };
  }
})(window.jQuery);
</script>

<script>
$(function(){
  const CSRF = $('meta[name="csrf-token"]').attr('content');

  const GET = (url, data = {}) => $.ajax({ url, data, method:'GET', dataType:'json', cache:false });
  const POST = (url, data, isFD = false) => $.ajax({
    url, method:'POST', data, dataType:'json',
    processData: !isFD, contentType: isFD ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
    headers: {'X-CSRF-TOKEN': CSRF}
  });
  const onErr = (xhr, msg='Something went wrong') => Swal.fire({ icon:'error', title:'Error', text: xhr?.responseJSON?.message || msg });

  let _types = [];

  async function fillOrderFilters(){
    // Types
    _types = await GET('/user-admin/marketplace/types');
    const $t = $('#fltType').empty();
    $t.append('<option value="">All types</option>');
    _types.forEach(x => $t.append(`<option value="${x.id}">${x.name}</option>`));

    // Handlers
    $('#fltType').off('change').on('change', async function(){
      const tid = this.value || '';
      const $s = $('#fltSub').empty().append('<option value="">All subcategories</option>');
      if (tid){
        try {
          const subs = await GET('/user-admin/marketplace/subcategories', { type_id: tid });
          subs.forEach(s => $s.append(`<option value="${s.id}">${s.name}</option>`));
        } catch (xhr) {
          onErr(xhr, 'Failed to load subcategories');
        }
      }
      loadOrders();
    });
    $('#fltSub').off('change').on('change', loadOrders);
    $('#orderSearch').off('keyup').on('keyup', loadOrders);
  }

  function orderRowHtml(o){
    let actions = '';
    const st = (o.status||'').toLowerCase();
    if (st === 'canceled'){
      actions = '';
    } else if (st === 'completed'){
      actions = `<button class="btn btn-sm btn-outline-secondary btnEditStages mr-1">Edit</button>`;
    } else if (st === 'new'){
      actions = `<button class="btn btn-sm btn-primary btnStart mr-1">Start</button><button class="btn btn-sm btn-outline-danger btnCancel">Cancel</button>`;
    } else {
      actions = `<button class="btn btn-sm btn-outline-secondary btnEditStages mr-1">Edit</button>`;
    }
    const badge = st === 'canceled' ? 'badge-danger' : (st === 'completed' ? 'badge-success' : 'badge-secondary');
    return `<tr data-id="${o.id}">
      <td><strong>${o.product_name||'-'}</strong></td>
      <td>${o.type||'-'}</td>
      <td>${o.subcategory||'-'}</td>
      <td>${o.buyer_name||'-'}</td>
      <td>${o.amount}</td>
      <td><span class="badge ${badge}">${o.status}</span></td>
      <td>${o.created_at||'-'}</td>
      <td class="text-right">${actions}</td>
    </tr>`;
  }

  async function refreshOrderSummary(){
    try{
      const s = await GET('/user-admin/marketplace/orders-summary');
      $('#sumNew').text(s.new||0);
      $('#sumProg').text(s.in_progress||0);
      $('#sumDone').text(s.completed||0);
      $('#sumCancel').text(s.canceled||0);
    }catch(e){}
  }

  async function loadOrders(){
    try{
      const params = {
        type_id: $('#fltType').val() || '',
        subcategory_id: $('#fltSub').val() || '',
        search: $('#orderSearch').val() || ''
      };
      const list = await GET('/user-admin/marketplace/orders', params);
      const $tb = $('#tblOrders tbody').empty();
      if (!list.length) $tb.append('<tr><td colspan="8" class="text-center text-muted">No orders.</td></tr>');
      else list.forEach(x => $tb.append(orderRowHtml(x)));
      refreshOrderSummary();
    } catch(xhr){ onErr(xhr, 'Failed to load orders'); }
  }

  // ===== Stages Modal (delegated) =====
  const stageItem = (i, title='', notes='', status='pending') => `
  <div class="card mb-2 stage-item" data-idx="${i}">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Stage ${i+1}</strong>
        <button type="button" class="btn btn-sm btn-outline-danger btnRemoveStage">Remove</button>
      </div>
      <div class="form-group"><label>Title</label>
        <input type="text" class="form-control st-title" value="${(title||'').replace(/"/g,'&quot;')}" required>
      </div>
      <div class="form-group"><label>Notes</label>
        <textarea class="form-control st-notes">${notes||''}</textarea>
      </div>
      <div class="form-group mb-0"><label>Status</label>
        <select class="form-control st-status">
          <option value="pending" ${status==='pending'?'selected':''}>Pending</option>
          <option value="in_progress" ${status==='in_progress'?'selected':''}>In progress</option>
          <option value="done" ${status==='done'?'selected':''}>Done</option>
        </select>
      </div>
    </div>
  </div>`;

  function renumberStages(){
    $('#stagesList .stage-item').each(function(i){
      $(this).attr('data-idx', i);
      $(this).find('strong').text(`Stage ${i+1}`);
    });
    evaluateDeliverablesVisibility();
  }

  $(document).on('click', '#btnAddDeliveries', () => $('#delivery_files').click());
  let newDeliveryFiles = [];
  $(document).on('change', '#delivery_files', function(){
    if (this.files?.length){
      for (const f of this.files) if (f) newDeliveryFiles.push(f);
      $('#delivCount').text(`${newDeliveryFiles.length} file(s) selected`);
      const $ul = $('#deliveriesPreview').empty();
      newDeliveryFiles.forEach(f => $ul.append(`<li class="list-group-item">${f.name}</li>`));
    }
    this.value = '';
  });

  function evaluateDeliverablesVisibility(){
    const isService = $('#deliverablesWrap').data('is-service') === 1;
    if (!isService){ $('#deliverablesWrap').hide(); return; }
    const $last = $('#stagesList .stage-item').last();
    const lastStatus = $last.find('.st-status').val();
    if (lastStatus === 'done') $('#deliverablesWrap').show();
    else $('#deliverablesWrap').hide();
  }
  $(document).on('change', '#stagesList .st-status', evaluateDeliverablesVisibility);

  // Use narrow try/catch: only network errors get the "Failed to open stages" message.
  $('#tblOrders').on('click','.btnStart', async function(){
    const id = $(this).closest('tr').data('id');
    try{
      const o = await GET(`/user-admin/marketplace/orders/${encodeURIComponent(id)}`);
      openStagesModalFromOrder(o, true);
    } catch(xhr){ onErr(xhr,'Failed to open stages'); }
  });
  $('#tblOrders').on('click','.btnEditStages', async function(){
    const id = $(this).closest('tr').data('id');
    try{
      const o = await GET(`/user-admin/marketplace/orders/${encodeURIComponent(id)}`);
      openStagesModalFromOrder(o, false);
    } catch(xhr){ onErr(xhr,'Failed to load stages'); }
  });

  function openStagesModalFromOrder(o, starting){
    const $modal = $('#stagesModal');
    const $orderId = $('#stages_order_id');
    if (!$modal.length || !$orderId.length){
      console.error('Stages modal markup not found on this page');
      Swal.fire({icon:'error', title:'UI error', text:'Stages modal is not present on this page.'});
      return;
    }

    $orderId.val(o.id);
    $('#stagesModalTitle').text('Stages');

    const $list = $('#stagesList').empty();
    if (Array.isArray(o.stages) && o.stages.length){
      o.stages.forEach((s,i)=>{
        $list.append(stageItem(i, s.title||'', s.notes||'', s.status||'pending'));
      });
    } else {
      $list.append(stageItem(0, starting ? 'Kickoff' : '', '', starting ? 'in_progress' : 'pending'));
    }

    $('#deliverablesWrap').data('is-service', o.is_service ? 1 : 0);
    evaluateDeliverablesVisibility();

    newDeliveryFiles = [];
    $('#deliveriesPreview').empty();
    $('#delivCount').text('No files selected');

    try {
      if (typeof $modal.modal === 'function') {
        $modal.modal('show');
      } else if (window.bootstrap && window.bootstrap.Modal) {
        // direct Bootstrap 5 fallback (in case jQuery plugin wasn't registered)
        const inst = window.bootstrap.Modal.getOrCreateInstance($modal[0]);
        inst.show();
      } else {
        // last resort
        $modal.show();
      }
    } catch(e){
      console.error('Error opening modal:', e);
      Swal.fire({icon:'error', title:'UI error', text:'Could not open the stages dialog.'});
    }
  }

  $(document).on('click','#btnAddStage', function(){
    const i = $('#stagesList .stage-item').length;
    $('#stagesList').append(stageItem(i)); renumberStages();
  });
  $(document).on('click','#stagesList .btnRemoveStage', function(){
    $(this).closest('.stage-item').remove();
    if (!$('#stagesList .stage-item').length) $('#stagesList').append(stageItem(0));
    renumberStages();
  });

  $('#tblOrders').on('click','.btnCancel', function(){
    const id = $(this).closest('tr').data('id');
    Swal.fire({
      title:'Cancel this order?',
      text:'Only NEW orders can be canceled.',
      icon:'warning', showCancelButton:true, confirmButtonText:'Yes, cancel'
    }).then(r=>{
      if (!r.isConfirmed) return;
      POST(`/user-admin/marketplace/orders/${encodeURIComponent(id)}/cancel`, {})
        .done(()=>{ Swal.fire({icon:'success', title:'Canceled'}); loadOrders(); refreshOrderSummary(); })
        .fail(xhr=>onErr(xhr,'Failed to cancel'));
    });
  });

  $(document).on('click','#btnSaveStages', function(){
    const orderId = $('#stages_order_id').val();
    const stages = [];
    let valid = true;
    $('#stagesList .stage-item').each(function(){
      const title = $(this).find('.st-title').val().trim();
      const notes = $(this).find('.st-notes').val().trim();
      const status= $(this).find('.st-status').val();
      if (!title) valid = false;
      stages.push({ title, notes, status });
    });
    if (!valid || !stages.length) return Swal.fire({icon:'error', title:'Add at least one stage with a title.'});

    const allDone = stages.every(s => s.status === 'done');
    const fd = new FormData();
    stages.forEach((s,i)=>{
      fd.append(`stages[${i}][title]`, s.title);
      fd.append(`stages[${i}][notes]`, s.notes);
      fd.append(`stages[${i}][status]`, s.status);
    });
    fd.append('set_in_progress_if_new', '1');
    if (allDone) fd.append('mark_complete_hint', '1');
    if ($('#deliverablesWrap').is(':visible') && newDeliveryFiles.length){
      newDeliveryFiles.forEach(f => fd.append('delivery_files[]', f));
    }

    POST(`/user-admin/marketplace/orders/${encodeURIComponent(orderId)}/stages`, fd, true)
      .done(()=>{
        // Close with whichever modal API exists
        const $modal = $('#stagesModal');
        if (typeof $modal.modal === 'function') $modal.modal('hide');
        else if (window.bootstrap && window.bootstrap.Modal) window.bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
        else $modal.hide();

        Swal.fire({icon:'success', title:'Saved'});
        newDeliveryFiles=[]; $('#deliveriesPreview').empty(); $('#delivCount').text('No files selected');
        loadOrders();
      })
      .fail(xhr=>onErr(xhr,'Failed to save stages'));
  });

  (async function init(){
    try{
      await fillOrderFilters();
      await refreshOrderSummary();
      await loadOrders();
    } catch(e){}
  })();
});
</script>

{{-- Stages Modal --}}
<div class="modal fade" id="stagesModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stagesModalTitle">Stages</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="stages_order_id">
        <div id="stagesList"></div>
        <button type="button" class="btn btn-outline-primary mt-2" id="btnAddStage">Add stage</button>

        <div id="deliverablesWrap" class="mt-3" style="display:none">
          <hr>
          <h6>Deliverables (Services only)</h6>
          <p class="text-muted mb-2">Shown when the last stage is set to <b>Done</b>.</p>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddDeliveries">+ Add files</button>
          <small class="text-muted ml-2" id="delivCount">No files selected</small>
          <input type="file" id="delivery_files" name="delivery_files[]" multiple class="form-control-file d-none">
          <ul class="list-group mt-2" id="deliveriesPreview"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <button id="btnSaveStages" class="btn btn-primary">Save</button>
        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>
