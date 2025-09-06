{{-- resources/views/admin/boost_plans.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
  .hidden{display:none}
  .dt-center{text-align:center}
  .desc-trunc {
    max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">Boost Pricing Plans</h1>
      <button id="btnAdd" class="btn btn-primary">Add Plan</button>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header justify-content-between align-items-center w-100">
          <h4 class="m-0">Plans</h4>
        </div>
        <div class="card-body">
          <table id="tblPlans" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>Name</th>
                <th class="dt-center">Days</th>
                <th class="dt-center">Price (USD)</th>
                <th>Description</th>
                <th class="dt-center">Active</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
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

  let dt;

  async function loadTable(){
    const list = await GET('/admin/boost-plans/list');
    const rows = list.map(r=>({
      id: r.id,
      name: r.name,
      days: r.days,
      price: r.price_usd,
      desc: r.description || '-',
      active: r.is_active ? 'Yes' : 'No',
      updated: r.updated_at || '-',
      actions: `
        <button class="btn btn-sm btn-outline-secondary btnEdit" data-id="${r.id}">Edit</button>
        <button class="btn btn-sm btn-outline-danger btnDelete" data-id="${r.id}">Delete</button>
      `
    }));

    if (!dt) {
      dt = $('#tblPlans').DataTable({
        data: rows,
        columns: [
          {data:'name'},
          {data:'days', className:'dt-center', width:70},
          {data:'price', className:'dt-center', width:120},
          {data:'desc',  className:'desc-trunc'},
          {data:'active', className:'dt-center', width:80},
          {data:'updated', width:160},
          {data:'actions', orderable:false, className:'text-right', width:160}
        ],
        order:[[5,'desc']],
        pageLength: 25,
        responsive:true,
        destroy:true
      });
    } else {
      dt.clear().rows.add(rows).draw();
    }
  }

  // Modal (SweetAlert)
  async function openForm(plan=null){
    const html = `
      <form id="frmPlan">
        <input type="hidden" name="id" value="${plan?plan.id:''}">
        <div class="form-group text-left">
          <label>Plan Name</label>
          <input name="name" type="text" class="form-control" value="${plan?plan.name||'':''}" required maxlength="120">
        </div>
        <div class="form-row">
          <div class="form-group text-left col-md-6">
            <label>Days</label>
            <input name="days" type="number" min="1" class="form-control" value="${plan?plan.days||'': ''}" required>
          </div>
          <div class="form-group text-left col-md-6">
            <label>Price (USD)</label>
            <input name="price_usd" type="number" step="0.01" min="0" class="form-control" value="${plan?plan.price_usd||'': ''}" required>
          </div>
        </div>
        <div class="form-group text-left">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="4" placeholder="Short details...">${plan? (plan.description||'') : ''}</textarea>
        </div>

        <div class="form-group text-left">
          <label class="custom-switch mt-2">
            <input type="checkbox" name="is_active" class="custom-switch-input" ${plan&&plan.is_active?'checked':''}>
            <span class="custom-switch-indicator"></span>
            <span class="custom-switch-description">Active</span>
          </label>
        </div>
      </form>`;

    const result = await Swal.fire({
      title: plan?'Edit Plan':'Add Plan',
      html, focusConfirm:false, showCancelButton:true,
      width: '650px',
      confirmButtonText: 'Save',
      preConfirm: () => {
        const $f = $('#frmPlan');
        const payload = {
          id: $f.find('[name="id"]').val(),
          name: $f.find('[name="name"]').val()?.trim(),
          days: parseInt($f.find('[name="days"]').val(), 10),
          price_usd: $f.find('[name="price_usd"]').val(),
          description: $f.find('[name="description"]').val(),
          is_active: $f.find('[name="is_active"]').is(':checked') ? 1 : 0
        };
        if (!payload.name) { Swal.showValidationMessage('Enter plan name'); return false; }
        if (!payload.days || payload.days < 1) { Swal.showValidationMessage('Days must be >= 1'); return false; }
        if (payload.price_usd === '' || parseFloat(payload.price_usd) < 0) { Swal.showValidationMessage('Enter a valid USD price'); return false; }
        return payload;
      }
    });
    if (!result.isConfirmed) return;

    try{
      await POST('/admin/boost-plans/save', result.value);
      Swal.fire({icon:'success', title:'Saved'});
      await loadTable();
    }catch(xhr){ onErr(xhr,'Failed to save'); }
  }

  $('#btnAdd').on('click',()=>openForm());

  $('#tblPlans').on('click','.btnEdit', async function(){
    const id = $(this).data('id');
    try{
      const list = await GET('/admin/boost-plans/list');
      const plan = list.find(r=>r.id==id);
      if (!plan) return;
      await openForm(plan);
    }catch(xhr){ onErr(xhr,'Failed to load'); }
  });

  $('#tblPlans').on('click','.btnDelete', function(){
    const id = $(this).data('id');
    Swal.fire({
      title:'Delete this plan?', icon:'warning',
      showCancelButton:true, confirmButtonText:'Yes, delete'
    }).then(async r=>{
      if (!r.isConfirmed) return;
      try{
        await DEL(`/admin/boost-plans/${id}`);
        Swal.fire({icon:'success', title:'Deleted'});
        await loadTable();
      }catch(xhr){ onErr(xhr,'Failed to delete'); }
    });
  });

  (async function init(){
    await loadTable();
  })();

})();
</script>
