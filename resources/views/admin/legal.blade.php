{{-- resources/views/admin/legal.blade.php --}}
@include('admin.common.header')
@section('title','Legal Pages')

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .ck-powered-by { display: none !important; }
  .ck.ck-toolbar .ck-file-dialog-button,
  .ck.ck-toolbar button[aria-label="Insert image"],
  .ck.ck-toolbar button[aria-label="Upload image"] { display: none !important; }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
      <h1 class="m-0">Legal Pages</h1>
      <div>
        <button id="btnRefresh" class="btn btn-outline-primary mx-1">Refresh</button>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        {{-- Terms --}}
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4 class="m-0">Terms &amp; Conditions</h4></div>
            <div class="card-body">
              <form id="formTerms">
                @csrf
                <input type="hidden" name="slug" value="terms">
                <input type="hidden" name="id">
                <div class="form-group">
                  <label>Title *</label>
                  <input type="text" name="title" class="form-control" placeholder="Terms & Conditions" required>
                </div>
                <div class="form-group">
                  <label>Content (HTML)</label>
                  <textarea id="ck-terms" name="content"></textarea>
                </div>
                <div class="d-flex">
                  <button class="btn btn-primary mr-2">Save</button>
                  <button id="btnDeleteTerms" type="button" class="btn btn-danger" style="display: none;">Delete</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        {{-- Privacy --}}
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4 class="m-0">Privacy Policy</h4></div>
            <div class="card-body">
              <form id="formPrivacy">
                @csrf
                <input type="hidden" name="slug" value="privacy">
                <input type="hidden" name="id">
                <div class="form-group">
                  <label>Title *</label>
                  <input type="text" name="title" class="form-control" placeholder="Privacy Policy" required>
                </div>
                <div class="form-group">
                  <label>Content (HTML)</label>
                  <textarea id="ck-privacy" name="content"></textarea>
                </div>
                <div class="d-flex">
                  <button class="btn btn-primary mr-2">Save</button>
                  <button id="btnDeletePrivacy" type="button" class="btn btn-danger" style="display: none;">Delete</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div><!-- /.row -->
    </div><!-- /.section-body -->
  </section>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<script>
(function(){
  // === CSRF setup (same pattern as your All Members page) ===
  function getCsrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.getAttribute) return m.getAttribute('content');
    const i = document.querySelector('input[name="_token"]');
    if (i) return i.value;
    return null;
  }
  const csrf = getCsrfToken();
  if (csrf && window.jQuery) $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

  // === CKEditor init (image buttons removed as per your preference) ===
  let edTerms = null, edPrivacy = null;
  function initEditors(){
    if(!edTerms){
      ClassicEditor.create(document.querySelector('#ck-terms'), {
        toolbar: { removeItems: ['insertImage','imageUpload'], shouldNotGroupWhenFull: true }
      }).then(e=> edTerms=e);
    }
    if(!edPrivacy){
      ClassicEditor.create(document.querySelector('#ck-privacy'), {
        toolbar: { removeItems: ['insertImage','imageUpload'], shouldNotGroupWhenFull: true }
      }).then(e=> edPrivacy=e);
    }
  }
  initEditors();

  // === Helpers ===
  function fillForm($form, data){
    $form.find('[name=id]').val(data?.id || '');
    $form.find('[name=title]').val(data?.title || '');
  }

  function fetchAll(){
    $('#btnRefresh').prop('disabled', true);
    $.getJSON("{{ route('admin.legal.fetch') }}", function(res){
      fillForm($('#formTerms'), res.terms);
      fillForm($('#formPrivacy'), res.privacy);
      const apply = () => {
        if(edTerms && edPrivacy){
          edTerms.setData(res.terms?.content || '');
          edPrivacy.setData(res.privacy?.content || '');
        } else { setTimeout(apply, 60); }
      };
      apply();
    })
    .fail(function(xhr){
      Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load', 'error');
    })
    .always(function(){
      $('#btnRefresh').prop('disabled', false);
    });
  }

  // Initial load + manual refresh
  fetchAll();
  $('#btnRefresh').on('click', fetchAll);

  // === Save Terms ===
  $('#formTerms').on('submit', function(e){
    e.preventDefault();
    if(edTerms) $('#ck-terms').val(edTerms.getData());
    const fd = new FormData(this);
    $.ajax({
      url: "{{ route('admin.legal.save') }}",
      method: "POST",
      data: fd,
      processData: false,
      contentType: false
    })
    .done(function(){
      Swal.fire('Saved', 'Terms updated', 'success').then(fetchAll);
    })
    .fail(function(xhr){
      Swal.fire('Error', xhr.responseJSON?.message || 'Save failed', 'error');
    });
  });

  // === Save Privacy ===
  $('#formPrivacy').on('submit', function(e){
    e.preventDefault();
    if(edPrivacy) $('#ck-privacy').val(edPrivacy.getData());
    const fd = new FormData(this);
    $.ajax({
      url: "{{ route('admin.legal.save') }}",
      method: "POST",
      data: fd,
      processData: false,
      contentType: false
    })
    .done(function(){
      Swal.fire('Saved', 'Privacy updated', 'success').then(fetchAll);
    })
    .fail(function(xhr){
      Swal.fire('Error', xhr.responseJSON?.message || 'Save failed', 'error');
    });
  });

  // === Delete helpers ===
  function doDelete(id){
    return $.ajax({ url: "/admin/legal/"+id, type: "DELETE" });
  }

  $('#btnDeleteTerms').on('click', function(){
    const id = $('#formTerms [name=id]').val();
    if(!id) return Swal.fire('Info','Nothing to delete','info');
    Swal.fire({ title:'Delete Terms?', icon:'warning', showCancelButton:true })
      .then(function(r){
        if(r.isConfirmed){
          doDelete(id)
            .done(function(){ Swal.fire('Deleted','','success').then(fetchAll); })
            .fail(function(xhr){ Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed','error'); });
        }
      });
  });

  $('#btnDeletePrivacy').on('click', function(){
    const id = $('#formPrivacy [name=id]').val();
    if(!id) return Swal.fire('Info','Nothing to delete','info');
    Swal.fire({ title:'Delete Privacy?', icon:'warning', showCancelButton:true })
      .then(function(r){
        if(r.isConfirmed){
          doDelete(id)
            .done(function(){ Swal.fire('Deleted','','success').then(fetchAll); })
            .fail(function(xhr){ Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed','error'); });
        }
      });
  });
})();
</script>

@include('admin.common.footer')
