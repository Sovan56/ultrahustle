@include('UserAdmin.common.header')

<div class="main-content">
<section class="section">
  <div class="section-header">
    <h1>My Orders</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item"><a href="{{ route('user.admin.index') }}">Dashboard</a></div>
      <div class="breadcrumb-item active">My Orders</div>
    </div>
  </div>

  <div class="section-body">
    <div class="card">
      <div class="card-header">
        <h4 class="m-0">Orders I purchased</h4>
        <div class="ml-auto d-flex">
          <select id="fltTypeB" class="form-control mr-2" style="min-width:200px"></select>
          <select id="fltSubB" class="form-control mr-2" style="min-width:220px"><option value="">All subcategories</option></select>
          <input type="text" id="searchB" class="form-control" placeholder="Search product..." style="min-width:220px;">
        </div>
      </div>
      <div class="card-body">
        <table class="table table-striped" id="tblMyOrders">
          <thead><tr><th>Product</th><th>Type</th><th>Subcategory</th><th>Status</th><th>Amount</th><th class="text-right">Actions</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>
</div>

@include('UserAdmin.common.footer')

<script>
(function(){
  const GET=(u,d={})=>$.ajax({url:u,data:d,method:'GET',dataType:'json',cache:false});
  const POST=(u,d)=>$.ajax({url:u,method:'POST',data:d,dataType:'json',headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

  let _types=[], _subsByType={};
  async function loadTypes(){
    _types = await GET('/user-admin/marketplace/types');
    const $t=$('#fltTypeB').empty().append('<option value="">All types</option>');
    _types.forEach(x=>$t.append(`<option value="${x.id}">${x.name}</option>`));
    $('#fltTypeB').on('change', async function(){
      const id=this.value||'';
      const $s=$('#fltSubB').empty().append('<option value="">All subcategories</option>');
      if(id){
        const subs = await GET('/user-admin/marketplace/subcategories',{type_id:id});
        subs.forEach(s=>$s.append(`<option value="${s.id}">${s.name}</option>`));
      }
      loadMyOrders();
    });
    $('#fltSubB').on('change', loadMyOrders);
    $('#searchB').on('keyup', loadMyOrders);
  }

  function row(o){
    const btn = o.can_approve ? `<button class="btn btn-success btnApprove" data-id="${o.id}">Approve</button>` : '';
    const track = o.is_digital ? `<button class="btn btn-outline-primary btnTrack" data-id="${o.id}">Track</button>` : '';
    return `<tr data-id="${o.id}">
      <td><strong>${o.product?.name||'-'}</strong></td>
      <td>${o.type||'-'}</td>
      <td>${o.subcategory||'-'}</td>
      <td><span class="badge ${o.status==='completed'?'badge-success': (o.status==='in_progress'?'badge-info':'badge-secondary')}">${o.status}</span></td>
      <td>${o.amount}</td>
      <td class="text-right">${track} ${btn}</td>
    </tr>`;
  }

  async function loadMyOrders(){
    const params={
      type_id: $('#fltTypeB').val()||'',
      subcategory_id: $('#fltSubB').val()||'',
      search: $('#searchB').val()||'',
    };
    const list=await GET('/user/my-orders', params);
    const $tb=$('#tblMyOrders tbody').empty();
    if(!list.length) $tb.append('<tr><td colspan="6" class="text-center text-muted">No orders</td></tr>');
    else list.forEach(o=>$tb.append(row(o)));
  }

  $('#tblMyOrders').on('click','.btnApprove', async function(){
    const id=$(this).data('id');
    try{
      await POST(`/user/my-orders/${id}/approve`,{});
      swal('Approved','Delivery approved successfully','success'); // if sweetalert available globally
      loadMyOrders();
    }catch(e){ alert(e?.responseJSON?.message||'Failed'); }
  });

  $('#tblMyOrders').on('click','.btnTrack', async function(){
    const id=$(this).data('id');
    const o = await GET(`/user/my-orders/${id}`);
    let html = '<div>';
    o.stages.forEach(s=>{
      html += `<div><strong>${s.position+1}. ${s.title}</strong> — ${s.status}<br><small>${s.notes}</small></div><hr>`;
    });
    html += '</div>';
    swal({title:`Order #${id} Stages`, content:$('<div/>',{html})[0]});
  });

  (async function init(){ await loadTypes(); await loadMyOrders(); })();
})();
</script>
