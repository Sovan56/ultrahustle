{{-- resources/views/admin/faq/index.blade.php --}}
@include('admin.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<style>
  .dt-center { text-align:center; }
  .text-right { text-align:right; }
  .desc-trunc { max-width: 520px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .swal2-popup { background-color: black; }
  .badge-on { display:inline-block; padding:2px 8px; border-radius: 999px; background:#e6fff2; color:#0f5132; font-size:12px; border:1px solid #b9f0d0; }
  .badge-off{ display:inline-block; padding:2px 8px; border-radius: 999px; background:#ffefef; color:#842029; font-size:12px; border:1px solid #f3c2c2; }
  .chip { display:inline-block; border-radius:20px; padding:2px 10px; background:#eef3f7; margin-left:8px; font-size:12px; }
  .form-hint { font-size: 12px; color:#6c757d; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header w-100 justify-content-between align-items-center">
      <h1 class="m-0">FAQs / Testimonials</h1>
      <div>
        <button id="btnAddFaq" class="btn btn-primary">Add FAQ</button>
      </div>
    </div>

    <div class="section-body">

      <div class="card">
        <div class="card-header justify-content-between align-items-center w-100">
          <h4 class="m-0">
            All Entries <span class="chip" id="sumFaqs">0</span>
          </h4>
          <div class="d-flex align-items-center">
            <label class="mr-2 mb-0">Active only</label>
            <input type="checkbox" id="fltActiveOnly" style="transform: scale(1.3);" />
          </div>
        </div>
        <div class="card-body">
          <table id="tblFaqs" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th style="width: 48%;">Quote</th>
                <th>Author</th>
                <th class="dt-center">Active</th>
                <th class="dt-center">Sort</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <small class="text-muted">Tip: Use sort order to control the display sequence on the public page.</small>
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
  const onErr=(xhr,msg='Something went wrong')=>{
    Swal.fire({icon:'error',title:'Error',text:(xhr?.responseJSON?.message)||msg});
  };

  const ROUTES = {
    page:  '{{ route("admin.faqs.page") }}',
    list:  '{{ route("admin.faqs.list") }}',
    show:  '{{ url("/admin/faqs") }}',          // + '/{id}'
    save:  '{{ route("admin.faqs.save") }}',
    sort:  '{{ route("admin.faqs.sort") }}',
    del:   '{{ url("/admin/faqs") }}',          // + '/{id}'
  };

  let dt;

  async function loadTable(){
    const onlyActive = $('#fltActiveOnly').is(':checked') ? 1 : 0;
    const list = await GET(ROUTES.list, { active_only: onlyActive });

    $('#sumFaqs').text(list.length);

    const rows = list.map(r=>({
      id: r.id,
      quote_html: `<span class="desc-trunc" title="${$('<div>').text(r.quote).html()}">${$('<div>').text(r.quote).html()}</span>`,
      author: r.author,
      active_html: (r.is_active === 'Yes') ? '<span class="badge-on">Yes</span>' : '<span class="badge-off">No</span>',
      sort_order: r.sort_order,
      updated: r.updated_at || '-',
      actions: `
        <button class="btn btn-sm btn-outline-secondary btnEditFaq" data-id="${r.id}">Edit</button>
        <button class="btn btn-sm btn-outline-danger btnDelFaq" data-id="${r.id}">Delete</button>
      `
    }));

    if (!dt) {
      dt = $('#tblFaqs').DataTable({
        data: rows,
        columns: [
          {data:'quote_html'},
          {data:'author'},
          {data:'active_html', className:'dt-center', width:80},
          {data:'sort_order', className:'dt-center', width:70},
          {data:'updated', width:160},
          {data:'actions', orderable:false, className:'text-right', width:160}
        ],
        order:[[3,'asc'],[4,'desc']],
        pageLength: 25,
        responsive:true,
        destroy:true
      });
    } else {
      dt.clear().rows.add(rows).draw();
    }
  }

  async function openFaqForm(id=null){
    let payload = {
      id: '',
      quote: '',
      author_name: '',
      author_role: '',
      author_location: '',
      is_active: true,
      sort_order: ''
    };

    if (id) {
      try {
        const d = await GET(`${ROUTES.show}/${id}`);
        payload = d;
      } catch (xhr) { onErr(xhr, 'Failed to load FAQ'); return; }
    }

    const html = `
      <form id="frmFaq">
        <input type="hidden" name="id" value="${payload.id || ''}">
        <div class="form-group text-left">
          <label>Quote / Testimonial</label>
          <textarea name="quote" class="form-control" rows="4" required minlength="10" placeholder="Enter the testimonial text...">${payload.quote ? $('<div>').text(payload.quote).html() : ''}</textarea>
          <small class="form-hint">This text appears publicly on the homepage in the FAQ/testimonials section.</small>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4 text-left">
            <label>Author Name</label>
            <input name="author_name" type="text" class="form-control" value="${payload.author_name || ''}" required maxlength="120" placeholder="e.g. Ananya Verma">
          </div>
          <div class="form-group col-md-4 text-left">
            <label>Author Role <small class="text-muted">(optional)</small></label>
            <input name="author_role" type="text" class="form-control" value="${payload.author_role || ''}" maxlength="120" placeholder="e.g. Startup Founder">
          </div>
          <div class="form-group col-md-4 text-left">
            <label>Author Location <small class="text-muted">(optional)</small></label>
            <input name="author_location" type="text" class="form-control" value="${payload.author_location || ''}" maxlength="120" placeholder="e.g. India">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4 text-left">
            <label class="custom-switch mt-2">
              <input type="checkbox" name="is_active" class="custom-switch-input" ${payload.is_active ? 'checked' : ''}>
              <span class="custom-switch-indicator"></span>
              <span class="custom-switch-description">Active</span>
            </label>
          </div>
          <div class="form-group col-md-4 text-left">
            <label>Sort Order</label>
            <input name="sort_order" type="number" class="form-control" value="${(payload.sort_order ?? '')}" min="0" max="1000000" placeholder="Auto if empty">
            <small class="form-hint">Lower numbers show earlier.</small>
          </div>
        </div>
      </form>
    `;

    const result = await Swal.fire({
      title: id ? 'Edit FAQ/Testimonial' : 'Add FAQ/Testimonial',
      html, focusConfirm:false, showCancelButton:true, width:'720px',
      confirmButtonText:'Save',
      preConfirm: () => {
        const $f = $('#frmFaq');
        const data = {
          id: $f.find('[name="id"]').val(),
          quote: $f.find('[name="quote"]').val()?.trim(),
          author_name: $f.find('[name="author_name"]').val()?.trim(),
          author_role: $f.find('[name="author_role"]').val()?.trim(),
          author_location: $f.find('[name="author_location"]').val()?.trim(),
          is_active: $f.find('[name="is_active"]').is(':checked') ? 1 : 0,
          sort_order: $f.find('[name="sort_order"]').val()
        };
        if (!data.quote || data.quote.length < 10) { Swal.showValidationMessage('Please enter a longer quote (min 10 chars).'); return false; }
        if (!data.author_name) { Swal.showValidationMessage('Please enter the author name.'); return false; }
        return data;
      }
    });
    if (!result.isConfirmed) return;

    try{
      await POST(ROUTES.save, result.value);
      Swal.fire({icon:'success', title:'Saved'});
      await loadTable();
    }catch(xhr){ onErr(xhr, 'Failed to save'); }
  }

  // Events
  $('#btnAddFaq').on('click', ()=>openFaqForm());
  $('#fltActiveOnly').on('change', loadTable);

  $('#tblFaqs').on('click', '.btnEditFaq', function(){ openFaqForm($(this).data('id')); });

  $('#tblFaqs').on('click', '.btnDelFaq', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete this entry?', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete'})
      .then(async r=>{
        if (!r.isConfirmed) return;
        try{
          await DEL(`${ROUTES.del}/${id}`);
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
