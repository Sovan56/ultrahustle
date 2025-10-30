@include('UserAdmin.common.header')
    <link rel="stylesheet" href="{{ asset('customcss/adminmarketplace.css') }}">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<style>
  /* Hide any image/media buttons if present in the build */
.ck-toolbar [aria-label="Insert image"],
.ck-toolbar [aria-label="Upload image"],
.ck-toolbar [aria-label="Insert media"] {
  display: none !important;
}

.ck-toolbar [aria-label="Insert table"] { display: none !important; }
/* Hide CKEditor watermark badge if present */
.ck.ck-powered-by { display: none !important; }

.swal2-popup{
  background-color: #282828 !important;
}
.swal2-confirm{
  background-color: #CEFE1B !important; 
  color: black !important; 
  border: none !important;
}

.swal2-popup {
  border-radius: 16px !important;
  border: 1px solid #CEFE1B !important;
}
.swal2-title{
  color: #CEFE1B !important;
}
.swal2-cancel{
  background-color: black !important; 
  color: #CEFE1B !important; 
  border: 1px solid #CEFE1B !important;
}
.swal-url-row button{
  margin-top: 5px;
    width: 35px;
    background: transparent;
    border: 1px solid #cefe1b;
    color: #cefe1b;
    border-radius: 50%;
    height: 35px;
}

.swal2-add-url{
  background-color: #CEFE1B !important; 
  color: black !important;
  border: none !important;
    height: 35px !important;
    border-radius: 999px !important;
    width: 100% !important;
}
.swal2-input, .swal-url-input{
  height: 45px;
  padding: 5px 15px;
  border-radius: 999px !important;
  border: 1px solid #CEFE1B !important;
  color: white !important;
  background-color: #1E1E1E !important;
  outline: none !important;
}

.ck-editor__editable p{
  color: white !important;
}

.ck-toolbar, .ck-sticky-panel__content, .ck-toolbar__items, 
.ck.ck-list{
  background-color: #1E1E1E !important;
}

.ck-button{
  color: white !important;
}

#wizard_horizontal-t-1 .number,
#wizard_horizontal-t-2 .number,
#wizard_horizontal-t-0 .number{
  display: none !important; 
}

#wizard_horizontal-t-1,
#wizard_horizontal-t-2,
#wizard_horizontal-t-0{
  font-size: 20px;
}
.ck.ck-list__item:hover,
.ck-dropdown__button:hover{
  background-color: #1E1E1E !important;
}
.ck-sticky-panel__content{
  border: none !important;
}

#wizard_horizontal-p-2 label{
  color: #CEFE1B !important;
}

.btnRemoveFaq,
.btnRemoveHeading,
.btnAddFaq,
#btnAddHeading{
  background-color: #CEFE1B !important; 
  color: black !important; 
  border: 1px solid #CEFE1B !important;

}
</style>
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1 style="color:#CEFE1B;">My Listing</h1>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">

          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="m-0">Manage</h4>
              <div class="btn-group align-items-center">
                <button id="btnShowList" class="btn btn-outline-primary">View Products</button>
                <button id="btnShowCreate" class="btn btn-primary">New Product</button>
              </div>
            </div>

            <div class="card-body">

              {{-- LIST --}}
              <div id="areaList">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="m-0">Products</h4>
                  <div>
                    <input type="text" id="searchBox" class="form-control d-inline-block" placeholder="Search" style="width: 240px;">
                  </div>
                </div>

                <table class="table table-striped" id="tblProducts">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Sales</th>
                      <th>Revenue</th>
                      <th>Price</th>
                      <th>Status</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
                <div class="mt-3 text-right"><small id="totalCount">Totals 0</small></div>
              </div>

              {{-- CREATE / EDIT --}}
              <div id="areaCreate" class="hidden">
                <ul class="nav nav-pills" id="typeTabs" role="tablist"></ul>

                <div class="tab-content mt-3">
                  <div class="tab-pane fade show active" id="TypeTabDynamic" role="tabpanel">
                    <form id="formProduct" enctype="multipart/form-data">
                      @csrf
                      <div id="wizard_horizontal">

                        <h2>Details</h2>
                        <section>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label style="color:#CEFE1B;">Type</label>
                                <select class="form-control" name="product_type_id" id="product_type_id" required></select>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label style="color:#CEFE1B;">Subcategory</label>
                                <select class="form-control" name="product_subcategory_id" id="product_subcategory_id" required>
                                  <option value="">-- Select --</option>
                                </select>
                              </div>
                            </div>

                            <div class="col-md-12">
                              <div class="form-group">
                                <label style="color:#CEFE1B;">Product Name</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                              </div>
                            </div>

                            <div class="col-md-6">
                              <div class="form-group">
                                <label class="custom-switch mt-2">
                                  <input type="checkbox" name="uses_ai" class="custom-switch-input" id="uses_ai">
                                  <span class="custom-switch-indicator"></span>
                                  <span class="custom-switch-description" style="color:#CEFE1B;">AI Powered</span>
                                </label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label class="custom-switch mt-2">
                                  <input type="checkbox" name="has_team" class="custom-switch-input" id="has_team">
                                  <span class="custom-switch-indicator"></span>
                                  <span class="custom-switch-description" style="color:#CEFE1B;">I Have a Team</span>
                                </label>
                              </div>
                            </div>

                            <div class="col-md-12">
                              <div class="form-group">
                                <label style="color:#CEFE1B;">Description</label>
                                <textarea class="form-control" name="description" id="description" required></textarea>
                              </div>
                            </div>

                            {{-- Gallery (always) --}}
                            <div class="col-md-12" id="wrapGallery">
                              <div class="form-group mb-1">
                                <label style="color:#CEFE1B;">Gallery Images <small class="text-muted" style="color:white;">(required)</small></label><br>
                                <button type="button" id="btnAddImages" class="btn btn-sm">+ Add Images</button>
                                <small class="text-muted ml-2" id="imagesCount">No files selected</small>
                                <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-control-file d-none">
                              </div>
                              <small class="form-text text-muted">Up to 20 images, 12MB each.</small>
                              <div class="mt-2 gallery-grid" id="galleryPreview"></div>
                            </div>

                            {{-- Files (Digital Product) --}}
                            <div class="col-md-12" id="wrapFiles" style="display:none">
                              <div class="form-group mb-1">
                                <label style="color:#CEFE1B;">Digital Files <small class="text-muted" style="color:white;">(required for Digital Product)</small></label><br>
                                <button type="button" id="btnAddFiles" class="btn btn-sm">+ Add Files</button>
                                <small class="text-muted ml-2" id="filesCount">No files selected</small>
                                <input type="file" id="files" name="files[]" multiple class="form-control-file d-none">
                              </div>
                              <ul class="list-group mt-2" id="filesPreview"></ul>
                            </div>

                            {{-- URLs (Courses) --}}
                            <div class="col-md-12" id="wrapUrls" style="display:none">
                              <div class="form-group mb-1">
                                <label style="color:#CEFE1B;">Course URLs <small class="text-muted" style="color:white;">(required for Courses)</small></label><br>
                                <button type="button" id="btnAddUrl" class="btn btn-sm" style="background-color:#CEFE1B !important; color: black !important; border: none !important;">+ Add URL</button>
                                <small class="text-muted ml-2" id="urlsCount">0 URLs</small>
                              </div>
                              <ul class="list-group mt-2" id="urlsPreview"></ul>
                            </div>

                          </div>
                        </section>

                        <h2>Pricing</h2>
                        <section>
                          <div class="row">

                            {{-- Pricing: Basic --}}
                            <div class="col-md-4">
                              <div class="card">
                                <div class="card-body">
                                  <h4>Basic</h4>
                                  <input type="hidden" value="basic" name="pricings[0][tier]">
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Currency</label>
                                    <div class="form-control-plaintext"><b id="ccy_basic" style="color:white;">—</b></div>
                                    <input type="hidden" name="pricings[0][currency]" id="ccy_basic_input">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Price</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="pricings[0][price]" required>
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Delivery Time (Days)</label>
                                    <input type="number" min="0" class="form-control" name="pricings[0][delivery_days]" required>
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Details</label>
                                    <textarea class="form-control" name="pricings[0][details]"></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>

                            {{-- Pricing: Standard --}}
                            <div class="col-md-4">
                              <div class="card">
                                <div class="card-body">
                                  <h4>Standard</h4>
                                  <input type="hidden" value="standard" name="pricings[1][tier]">
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Currency</label>
                                    <div class="form-control-plaintext"><b id="ccy_standard" style="color:white;">—</b></div>
                                    <input type="hidden" name="pricings[1][currency]" id="ccy_standard_input">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Price</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="pricings[1][price]">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Delivery Time (Days)</label>
                                    <input type="number" min="0" class="form-control" name="pricings[1][delivery_days]">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Details</label>
                                    <textarea class="form-control" name="pricings[1][details]"></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>

                            {{-- Pricing: Premium --}}
                            <div class="col-md-4">
                              <div class="card">
                                <div class="card-body">
                                  <h4>Premium</h4>
                                  <input type="hidden" value="premium" name="pricings[2][tier]">
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Currency</label>
                                    <div class="form-control-plaintext"><b id="ccy_premium" style="color:white;">—</b></div>
                                    <input type="hidden" name="pricings[2][currency]" id="ccy_premium_input">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Price</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="pricings[2][price]">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Delivery Time (Days)</label>
                                    <input type="number" min="0" class="form-control" name="pricings[2][delivery_days]">
                                  </div>
                                  <div class="form-group">
                                    <label style="color:#CEFE1B;">Details</label>
                                    <textarea class="form-control" name="pricings[2][details]"></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>

                          </div>
                        </section>

                        <h2>FAQ</h2>
                        <section>
                          <div id="faqList"></div>
                          <button type="button" class="btn btn-outline-primary" id="btnAddHeading">Add Heading</button>
                          <input type="hidden" id="edit_id" value="">
                        </section>

                      </div>
                    </form>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  @include('UserAdmin.common.settingbar')
</div>

@include('UserAdmin.common.footer')

<script src="{{ asset('assets/bundles/jquery-validation/dist/jquery.validate.min.js') }}"></script>
<script src="{{ asset('assets/bundles/jquery-steps/jquery.steps.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(function() {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

let _ckeDesc = null;

const _ckeConfig = {
  toolbar: [
    'heading',
    '|',
    'bold', 'italic', 'underline', 'link',
    '|',
    'bulletedList', 'numberedList',
    '|',
    'blockQuote',
    '|',
    'undo', 'redo'
  ],

  // IMPORTANT: also remove CKBox/CKBoxUploadAdapter (this is what’s causing your error)
  removePlugins: [
    // Images/media & autos
    'Image', 'ImageUpload', 'AutoImage', 'ImageInsert', 'EasyImage', 'MediaEmbed',
    'PictureEditing', 'ImageToolbar', 'ImageCaption', 'ImageStyle', 'ImageResize', 'LinkImage',

    // Cloud/CKFinder/CKBox
    'CloudServices', 'CKFinder', 'CKFinderUploadAdapter',
    'CKBox', 'CKBoxUploadAdapter',

    'Table', 'TableToolbar', 'TableCaption',
    'TableProperties', 'TableCellProperties', 'TableColumnResize',
    // Keep content simple
    'HtmlEmbed'
  ],

  toolbar: { shouldNotGroupWhenFull: true }
};

// Add this once, before ensureCkeMounted() is used
(function hardenCkRemovePlugins() {
  const UNWANTED = [
    'CKBox', 'CKBoxUploadAdapter',
    'EasyImage',
    'Image', 'ImageUpload', 'AutoImage', 'ImageInsert',
    'PictureEditing', 'ImageToolbar', 'ImageCaption', 'ImageStyle', 'ImageResize', 'LinkImage',
    'MediaEmbed',
    'CKFinder', 'CKFinderUploadAdapter',
    'CloudServices',
    'HtmlEmbed'
  ];

  const builtins = (ClassicEditor.builtinPlugins || []).map(p => p && p.pluginName).filter(Boolean);
  const presentAndUnwanted = UNWANTED.filter(n => builtins.includes(n));
  _ckeConfig.removePlugins = Array.from(new Set([...( _ckeConfig.removePlugins || [] ), ...presentAndUnwanted]));
})();


async function ensureCkeMounted() {
  if (_ckeDesc) return _ckeDesc;
  const el = document.querySelector('#description');
  if (!el) return null;
  try {
    _ckeDesc = await ClassicEditor.create(el, _ckeConfig);
    return _ckeDesc;
  } catch (e) {
    console.error('CKE init failed:', e);
    _ckeDesc = null;
    return null;
  }
}


// Optional: destroy when needed (we reuse instance, but helper if you ever need it)
async function destroyCke() {
  if (_ckeDesc && _ckeDesc.destroy) {
    try { await _ckeDesc.destroy(); } catch(_) {}
    _ckeDesc = null;
  }
}


 

    const GET = (url, data = {}) => $.ajax({
      url,
      data,
      method: 'GET',
      dataType: 'json',
      cache: false
    });
    const POST = (url, data, isFD = false) => $.ajax({
      url,
      method: 'POST',
      data,
      dataType: 'json',
      processData: !isFD,
      contentType: isFD ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const onErr = (xhr, msg = 'Something went wrong') => Swal.fire({
      icon: 'error',
      title: 'Error',
      text: xhr?.responseJSON?.message || msg
    });

    const showCreate = () => { $('#areaCreate').show();
       $('#areaList').hide(); 
      ensureCkeMounted(); 
      };
    const showList   = () => { $('#areaCreate').hide(); $('#areaList').show(); };

    $('#btnShowList').on('click', () => { showList(); loadProducts(); });
    $('#btnShowCreate').on('click', function(){  destroyCke(); hardResetForm(); showCreate(); setTimeout(() => { ensureCkeMounted(); }, 0);});

    const $form = $('#formProduct');
    $form.validate({
      ignore: ':hidden, .ck *',
      errorClass: 'text-danger',
      errorPlacement: (err, el) => err.insertBefore(el)
    });
    $('#wizard_horizontal').steps({
  headerTag: 'h2',
  bodyTag: 'section',
  transitionEffect: 'fade',
  autoFocus: true,
  labels: { next: 'Next', previous: 'Previous', finish: 'Finish' },

  onInit: () => {
    // Steps just built the DOM; now it's safe to mount CKEditor.
    ensureCkeMounted();
  },

  onStepChanged: () => {
    // If Steps moved things around or the section was hidden, re-ensure.
    ensureCkeMounted();
  },

  onStepChanging: () => { $form.validate().settings.ignore = ':hidden, .ck *'; return $form.valid(); },
  onFinishing:   () => { $form.validate().settings.ignore = ':hidden, .ck *'; return $form.valid(); },
  onFinished:    () => { $form.trigger('submit'); }
});


    let _types = [], _subsByType = {}, _userMeta = { currency: 'USD' };
    let _currentTypeSlug = '', _currentTypeName = '';

    function setTypeTabActive(id) {
      $('#typeTabs .nav-link').removeClass('active');
      $(`#typeTabs .nav-link[data-type="${id}"]`).addClass('active');
    }
    function isDigitalFromCurrent() {
      const s = (_currentTypeSlug||'').toLowerCase(), n = (_currentTypeName||'').toLowerCase();
      return s.includes('digital') || n.includes('digital');
    }
    function isCoursesFromCurrent() {
      const s = (_currentTypeSlug||'').toLowerCase(), n = (_currentTypeName||'').toLowerCase();
      return s.includes('course') || n.includes('course');
    }
    function updateTypeMeta() {
      const id = Number($('#product_type_id').val());
      const t = _types.find(x => x.id == id);
      _currentTypeSlug = (t?.slug||''); _currentTypeName = (t?.name||'');
    }
    function toggleMediaBlocks() {
      $('#wrapGallery').show();
      if (isDigitalFromCurrent()) $('#wrapFiles').show(); else $('#wrapFiles').hide();
      if (isCoursesFromCurrent()) $('#wrapUrls').show(); else $('#wrapUrls').hide();
    }

    async function loadUserMeta() {
      try { _userMeta = await GET('/user-admin/marketplace/user-meta'); }
      catch(e){ _userMeta = { currency: 'USD' }; }
      ['basic','standard','premium'].forEach(t=>{
        $('#ccy_'+t).text(_userMeta.currency||'USD');
        $('#ccy_'+t+'_input').val(_userMeta.currency||'USD');
      });
    }

    async function loadTypes(selectedId=null) {
      _types = await GET('/user-admin/marketplace/types');
      const $sel = $('#product_type_id').empty(), $tabs = $('#typeTabs').empty();
      _types.forEach((t,i)=>{
        $sel.append(`<option value="${t.id}" data-slug="${t.slug||''}" data-name="${t.name||''}">${t.name}</option>`);
        $tabs.append(`<li class="nav-item"><a class="nav-link ${selectedId?(t.id==selectedId?'active':''):(i===0?'active':'')}" data-type="${t.id}" href="javascript:void(0)">${t.name}</a></li>`);
      });
      const id = selectedId || (_types[0]?.id || null);
      if (id) {
        $('#product_type_id').val(id);
        setTypeTabActive(id);
        updateTypeMeta();
        toggleMediaBlocks();
      }
      return id;
    }

    async function loadSubcategories(typeId, selectedSubId=null) {
      if (!_subsByType[typeId]) _subsByType[typeId] = await GET('/user-admin/marketplace/subcategories', { type_id: typeId });
      const list = _subsByType[typeId];
      const $sub = $('#product_subcategory_id').empty().append('<option value="">-- Select --</option>');
      list.forEach(s => $sub.append(`<option value="${s.id}">${s.name}</option>`));
      if (selectedSubId) $('#product_subcategory_id').val(selectedSubId);
    }

    $('#typeTabs').on('click', '.nav-link', async function(){
      const id = $(this).data('type');
      $('#product_type_id').val(id);
      setTypeTabActive(id);
      updateTypeMeta(); toggleMediaBlocks();
      await loadSubcategories(id, null);
      clearTempMediaOnTypeSwitch();
    });
    $('#product_type_id').on('change', async function(){
      const id = this.value;
      setTypeTabActive(id);
      updateTypeMeta(); toggleMediaBlocks();
      await loadSubcategories(id, null);
      clearTempMediaOnTypeSwitch();
    });

    /* ===== FAQs (with headings) ===== */
    const headingTemplate = i => `
    <div class="card mb-2 heading-item" data-idx="${i}">
      <div class="card-body">
        <div class="form-group">
          <label>Heading</label>
          <input type="text" name="faqs[${i}][title]" class="form-control">
        </div>
        <div class="faqs-sub-list"></div>
        <button type="button" class="btn-sm btnAddFaq" data-heading="${i}">Add Question</button>
        <button type="button" class="btn-sm btnRemoveHeading">Remove Heading</button>
      </div>
    </div>`;

    const faqTemplate = (h,j,question='',answer='') => `
    <div class="card mb-2 faq-sub-item" data-subidx="${j}">
      <div class="card-body">
        <div class="form-group">
          <label>Question</label>
          <input type="text" name="faqs[${h}][questions][${j}][question]" class="form-control" value="${question.replace(/"/g,'&quot;')}" required>
        </div>
        <div class="form-group">
          <label>Answer</label>
          <textarea name="faqs[${h}][questions][${j}][answer]" class="form-control">${answer}</textarea>
        </div>
        <button type="button" class=" btn-sm btnRemoveFaq">Remove</button>
      </div>
    </div>`;

    function setFaqRowsFromData(faqs){
      const $b = $('#faqList').empty();
      if (Array.isArray(faqs) && faqs.length){
        faqs.forEach((h,i)=>{
          const $head = $(headingTemplate(i));
          $head.find(`input[name="faqs[${i}][title]"]`).val(h?.title ?? '');
          const $subList = $head.find('.faqs-sub-list');
          (h.questions||[]).forEach((qa,j)=>{
            $subList.append(faqTemplate(i, j, qa?.question ?? '', qa?.answer ?? ''));
          });
          $b.append($head);
        });
      } else {
        const $head = $(headingTemplate(0));
        const $subList = $head.find('.faqs-sub-list');
        for (let j=0;j<3;j++) $subList.append(faqTemplate(0,j));
        $b.append($head);
      }
    }

    function renumberFaqs(){
      $('#faqList .heading-item').each(function(i){
        $(this).attr('data-idx', i);
        $(this).find('input[name^="faqs["][name$="[title]"]').attr('name', `faqs[${i}][title]`);
        $(this).find('.btnAddFaq').attr('data-heading', i);
        const $sub = $(this).find('.faqs-sub-list .faq-sub-item');
        $sub.each(function(j){
          $(this).attr('data-subidx', j);
          $(this).find('input[name^="faqs["][name$="[question]"]').attr('name', `faqs[${i}][questions][${j}][question]`);
          $(this).find('textarea[name^="faqs["][name$="[answer]"]').attr('name', `faqs[${i}][questions][${j}][answer]`);
        });
      });
    }

    $('#btnAddHeading').on('click', ()=>{ const i = $('#faqList .heading-item').length; $('#faqList').append(headingTemplate(i)); renumberFaqs(); });
    $('#faqList').on('click','.btnAddFaq', function(){ const h=$(this).data('heading'); const $sub=$(this).prev('.faqs-sub-list'); const j=$sub.find('.faq-sub-item').length; $sub.append(faqTemplate(h,j)); renumberFaqs(); });
    $('#faqList').on('click','.btnRemoveHeading', function(){ $(this).closest('.heading-item').remove(); if (!$('#faqList .heading-item').length) $('#faqList').append(headingTemplate(0)); renumberFaqs(); });
    $('#faqList').on('click','.btnRemoveFaq', function(){ $(this).closest('.faq-sub-item').remove(); renumberFaqs(); });

    /* ===== MEDIA: GALLERY ===== */
    $('#btnAddImages').on('click',()=>$('#images').click());
    let newGalleryFiles=[], removeExistingImgs=new Set();

    function redrawGalleryPreview(existingUrls=null){
      const $wrap=$('#galleryPreview').empty();
      const existing = existingUrls || (window._existingImagesUrls||[]);
      existing.forEach(url=>{
        if (removeExistingImgs.has(url)) return;
        $wrap.append(`<div class="gallery-item"><img src="${url}"><div class="remove" data-type="existing" data-url="${url}">×</div></div>`);
      });
      newGalleryFiles.forEach((f,i)=>{
        const rd=new FileReader();
        rd.onload=e=>$wrap.append(`<div class="gallery-item"><img src="${e.target.result}"><div class="remove" data-type="new" data-index="${i}">×</div></div>`);
        rd.readAsDataURL(f);
      });
      $('#imagesCount').text(newGalleryFiles.length ? `${newGalleryFiles.length} file(s) selected` : 'No files selected');
    }
    $('#images').on('change', function(){
      if (this.files?.length){ for (const f of this.files) if (f) newGalleryFiles.push(f); redrawGalleryPreview(); }
      this.value='';
    });
    $('#galleryPreview').on('click','.remove', function(){
      const t=$(this).data('type');
      if (t==='existing'){ removeExistingImgs.add($(this).data('url')); redrawGalleryPreview(); }
      else { const i=+$(this).data('index'); newGalleryFiles.splice(i,1); redrawGalleryPreview(); }
    });

    /* ===== MEDIA: FILES (DIGITAL) ===== */
    $('#btnAddFiles').on('click',()=>$('#files').click());
    let newFiles=[], removeExistingFiles=new Set();

    function redrawFilesPreview(existingUrls=null){
      const $ul=$('#filesPreview').empty();
      const existing = existingUrls || (window._existingFilesUrls||[]);
      existing.forEach(url=>{
        if (removeExistingFiles.has(url)) return;
        $ul.append(`<li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:75%">${url.split('/').pop()}</span>
          <span class="badge badge-danger pointer" data-type="existing" data-url="${url}">Remove</span>
        </li>`);
      });
      newFiles.forEach((f,i)=>{
        $ul.append(`<li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:75%">${f.name}</span>
          <span class="badge badge-danger pointer" data-type="new" data-index="${i}">Remove</span>
        </li>`);
      });
      $('#filesCount').text(newFiles.length ? `${newFiles.length} file(s) selected` : 'No files selected');
    }
    $('#files').on('change', function(){
      if (this.files?.length){ for (const f of this.files) if (f) newFiles.push(f); redrawFilesPreview(); }
      this.value='';
    });
    $('#filesPreview').on('click','.badge-danger', function(){
      const t=$(this).data('type');
      if (t==='existing'){ removeExistingFiles.add($(this).data('url')); redrawFilesPreview(); }
      else { const i=+$(this).data('index'); newFiles.splice(i,1); redrawFilesPreview(); }
    });

    /* ===== MEDIA: URLS (COURSES) ===== */
   $('#btnAddUrl').on('click', () => {
  Swal.fire({
    title: 'Add URLs',
    showCancelButton: true,
    confirmButtonText: 'Save',
    width: 600,
    html: '<div id="swal-url-container" style="max-height:300px;overflow:auto;padding-top:6px"></div>',
    didOpen: () => {
      const container = Swal.getHtmlContainer().querySelector('#swal-url-container');

      // helper to append a single input row
      const appendRow = (value = '') => {
        const row = document.createElement('div');
        row.className = 'swal-url-row';
        row.style.display = 'flex';
        row.style.gap = '8px';
        row.style.marginBottom = '8px';

        const input = document.createElement('input');
        input.type = 'url';
        input.className = 'swal-url-input';
        input.placeholder = 'https://example.com';
        input.style.flex = '1';
        input.value = value;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerText = '✕';
        removeBtn.title = 'Remove';
        removeBtn.style.minWidth = '36px';
        removeBtn.onclick = () => row.remove();

        row.appendChild(input);
        row.appendChild(removeBtn);
        container.appendChild(row);

        // focus the newly added input for quick typing
        input.focus();
      };

      // add initial single input
      appendRow();

      // add "Add URL" + small instructions below the inputs
      const controls = document.createElement('div');
      controls.style.display = 'flex';
      controls.style.justifyContent = 'space-between';
      controls.style.alignItems = 'center';
      controls.style.marginTop = '10px';
      controls.style.flexDirection = 'column';

      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'swal2-add-url';
      addBtn.innerText = 'Add URL';
      addBtn.onclick = () => appendRow();

      const hint = document.createElement('small');
      hint.innerText = 'You can add multiple URLs. Click Save when done.';
      hint.style.opacity = '0.85';
      hint.style.marginTop = '10px';
      hint.style.color = 'white';

      controls.appendChild(addBtn);
      controls.appendChild(hint);
      container.parentNode.appendChild(controls); // append after container
    },
    preConfirm: () => {
      // collect values, validate
      const container = Swal.getHtmlContainer().querySelector('#swal-url-container');
      const inputs = Array.from(container.querySelectorAll('.swal-url-input'));
      const values = inputs.map(i => i.value.trim()).filter(v => v !== '');

      if (values.length === 0) {
        Swal.showValidationMessage('Please enter at least one URL.');
        return false;
      }

      // validate each URL using the URL constructor
      for (let v of values) {
        try {
          // allow protocol-relative? we expect full URL so require protocol
          const parsed = new URL(v);
          // optional: require http or https
          if (!['http:', 'https:'].includes(parsed.protocol)) {
            throw new Error('Invalid protocol');
          }
        } catch (err) {
          Swal.showValidationMessage(`Invalid URL: ${v}`);
          return false;
        }
      }

      // return the array of validated URLs to then()
      return values;
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then(result => {
    if (result.isConfirmed && Array.isArray(result.value)) {
      // result.value is an array of validated URLs
      // push them into your newUrls array and redraw preview
      newUrls.push(...result.value);
      redrawUrlsPreview();
    }
  });
});

    let newUrls=[], removeExistingUrls=new Set();

    function redrawUrlsPreview(existingUrls=null){
      const $ul=$('#urlsPreview').empty();
      const existing = existingUrls || (window._existingUrls||[]);
      existing.forEach(url=>{
        if (removeExistingUrls.has(url)) return;
        $ul.append(`<li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:75%">${url}</span>
          <span class="badge badge-danger pointer" data-type="existing" data-url="${url}">Remove</span>
        </li>`);
      });
      newUrls.forEach((url,i)=>{
        $ul.append(`<li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:75%">${url}</span>
          <span class="badge badge-danger pointer" data-type="new" data-index="${i}">Remove</span>
        </li>`);
      });
      const total = (existing.filter(u=>!removeExistingUrls.has(u)).length) + newUrls.length;
      $('#urlsCount').text(`${total} URL${total!==1?'s':''}`);
    }
    $('#urlsPreview').on('click','.badge-danger', function(){
      const t=$(this).data('type');
      if (t==='existing'){ removeExistingUrls.add($(this).data('url')); redrawUrlsPreview(); }
      else { const i=+$(this).data('index'); newUrls.splice(i,1); redrawUrlsPreview(); }
    });

    function extractRel(u){
      if (!u) return u;
      const m = u.match(/\/(?:media|storage)\/(.+)$/);
      return m ? m[1] : u;
    }

    function clearTempMediaOnTypeSwitch(){
      newGalleryFiles=[]; redrawGalleryPreview();
      newFiles=[]; redrawFilesPreview();
      newUrls=[]; removeExistingUrls=new Set(); redrawUrlsPreview();
      if (!isDigitalFromCurrent()) $('#wrapFiles').hide();
      if (!isCoursesFromCurrent()) $('#wrapUrls').hide();
    }

    function fillPricingTiers(pricingByTier){
      const map={ basic:0, standard:1, premium:2 };
      ['basic','standard','premium'].forEach(t=>{
        const idx=map[t], pr=(pricingByTier&&pricingByTier[t])?pricingByTier[t]:{};
        $(`input[name="pricings[${idx}][price]"]`).val(pr.price ?? '');
        $(`input[name="pricings[${idx}][delivery_days]"]`).val(pr.delivery_days ?? '');
        $(`textarea[name="pricings[${idx}][details]"]`).val(pr.details ?? '');
      });
    }

    /* ===== SUBMIT ===== */
    $('#formProduct').on('submit', function(e){
      e.preventDefault();
      const editId = $('#edit_id').val();
      const url = editId ? `/user-admin/marketplace/products/${editId}` : '/user-admin/marketplace/products';

      if (_ckeDesc) {
  $('#description').val(_ckeDesc.getData());
}


      const isDigital = isDigitalFromCurrent();
      const isCourses = isCoursesFromCurrent();
      const totalExistingImgs = (window._existingImagesUrls||[]).filter(u=>!removeExistingImgs.has(u)).length;
      const totalNewImgs = newGalleryFiles.length;
      const totalExistingFiles = (window._existingFilesUrls||[]).filter(u=>!removeExistingFiles.has(u)).length;
      const totalNewFiles = newFiles.length;
      const totalExistingUrls = (window._existingUrls||[]).filter(u=>!removeExistingUrls.has(u)).length;
      const totalNewUrls = newUrls.length;

      if ((totalExistingImgs + totalNewImgs) < 1) return Swal.fire({icon:'error', title:'Please add at least one image.'});
      if (isDigital && (totalExistingFiles + totalNewFiles) < 1) return Swal.fire({icon:'error', title:'Please add at least one file for Digital Product.'});
      if (isCourses && (totalExistingUrls + totalNewUrls) < 1) return Swal.fire({icon:'error', title:'Please add at least one URL for Courses.'});

      const fd = new FormData(this);
      fd.set('uses_ai', $('#uses_ai').is(':checked') ? 1 : 0);
      fd.set('has_team', $('#has_team').is(':checked') ? 1 : 0);
      try { fd.delete('images[]'); } catch(e){}
      try { fd.delete('files[]'); } catch(e){}

      newGalleryFiles.forEach(f=>fd.append('images[]', f));
      newFiles.forEach(f=>fd.append('files[]', f));
      removeExistingImgs.forEach(u=>fd.append('remove_images[]', extractRel(u)));
      removeExistingFiles.forEach(u=>fd.append('remove_files[]', extractRel(u)));

      newUrls.forEach(u=>fd.append('urls[]', u));
      removeExistingUrls.forEach(u=>fd.append('remove_urls[]', u));

      POST(url, fd, true).then(res=>{
        Swal.fire({icon:'success', title: res.message || 'Saved'});
        hardResetForm();
        showList();
        loadProducts();
      }).catch(xhr=>onErr(xhr, 'Validation error'));
    });

    function resetWizardToFirst(){
      try {
        let idx;
        try { idx = $('#wizard_horizontal').steps('getCurrentIndex'); } catch(e) { idx = $('#wizard_horizontal').steps('currentIndex'); }
        idx = Number(idx)||0;
        for(let i=0;i<idx;i++) $('#wizard_horizontal').steps('previous');
      } catch(e){}
    }

    function hardResetForm(){
      $('#formProduct')[0].reset();
      $('#edit_id').val('');
      if (_ckeDesc) _ckeDesc.setData('');
else $('#description').val('');

      window._existingImagesUrls=[]; window._existingFilesUrls=[]; window._existingUrls=[];
      newGalleryFiles=[]; removeExistingImgs=new Set(); $('#galleryPreview').empty(); $('#imagesCount').text('No files selected');
      newFiles=[]; removeExistingFiles=new Set(); $('#filesPreview').empty(); $('#filesCount').text('No files selected');
      newUrls=[]; removeExistingUrls=new Set(); $('#urlsPreview').empty(); $('#urlsCount').text('0 URLs');

      setFaqRowsFromData([]);
      if (_types.length){
        const firstId=_types[0].id;
        $('#product_type_id').val(firstId);
        setTypeTabActive(firstId);
        updateTypeMeta(); toggleMediaBlocks();
        loadSubcategories(firstId, null);
      }
      resetWizardToFirst();
    }

    /* ===== PRODUCTS LIST ===== */
    const rowHtml = (x) => {
      const toggleText = x.status === 'published' ? 'Unpublish' : 'Publish';
      return `<tr data-id="${x.id}">
      <td><img src="${x.thumbnail_url}" class="mr-2" style="width:36px;height:36px;object-fit:cover;border-radius:4px;"><strong>${x.name}</strong></td>
      <td>${x.sales}</td><td>${x.revenue}</td><td>${x.price}</td>
      <td><span class="badge ${x.status_badge||'badge-light'}">${x.status}</span></td>
      <td class="text-right"><div class="dropdown">
        <a href="#" data-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item btnEdit" href="javascript:void(0)">Edit</a>
          <a class="dropdown-item btnDuplicate" href="javascript:void(0)">Duplicate</a>
          <a class="dropdown-item btnTogglePublish" href="javascript:void(0)">${toggleText}</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger btnDelete" href="javascript:void(0)">Delete</a>
        </div></div>
      </td></tr>`;
    };

    async function loadProducts(){
      try{
        const search = $('#searchBox').val() || '';
        const list = await GET('/user-admin/marketplace/products', { search });
        const $tb = $('#tblProducts tbody').empty();
        if (!list.length) $tb.append('<tr><td colspan="6" class="text-center text-muted">No products yet</td></tr>');
        else list.forEach(x => $tb.append(rowHtml(x)));
        $('#totalCount').text(`Totals ${list.length}`);
      } catch(xhr){ onErr(xhr,'Failed to load products'); }
    }
    $('#searchBox').on('keyup', loadProducts);

    // EDIT
// EDIT (replace your entire handler with this)
$('#tblProducts').on('click', '.btnEdit', async function () {
  const id = $(this).closest('tr').data('id');
  if (!id) {
    return Swal.fire({ icon: 'error', title: 'Error', text: 'Missing product id' });
  }

  try {
    const p = await GET(`/user-admin/marketplace/products/${encodeURIComponent(id)}`);

    // Reset wizard to first step before we populate anything
    resetWizardToFirst();

    // Load dropdown data
    const typeId = await loadTypes(p.product_type_id);
    await loadSubcategories(typeId, p.product_subcategory_id || null);

    // Simple fields
    $('#edit_id').val(p.id);
    $('#name').val(p.name);
    $('#uses_ai').prop('checked', !!p.uses_ai);
    $('#has_team').prop('checked', !!p.has_team);

    // Make the Create area visible first, then mount CKEditor
    showCreate();
    await ensureCkeMounted();

    // Description (CKEditor first, fallback to textarea if needed)
    if (_ckeDesc) _ckeDesc.setData(p.description || '');
    else $('#description').val(p.description || '');

    // Media (existing)
    window._existingImagesUrls = Array.isArray(p.images_urls) ? p.images_urls : [];
    window._existingFilesUrls  = Array.isArray(p.files_urls)  ? p.files_urls  : [];
    window._existingUrls       = Array.isArray(p.urls)        ? p.urls        : [];

    // Media (temp state reset)
    removeExistingImgs  = new Set(); newGalleryFiles = [];
    removeExistingFiles = new Set(); newFiles        = [];
    removeExistingUrls  = new Set(); newUrls         = [];

    // Redraw previews
    redrawGalleryPreview(window._existingImagesUrls);
    redrawFilesPreview(window._existingFilesUrls);
    redrawUrlsPreview(window._existingUrls);

    // Pricing + FAQs
    fillPricingTiers(p.pricings_by_tier || {});
    setFaqRowsFromData(p.faqs || []);

    // Type-dependent UI
    updateTypeMeta();
    toggleMediaBlocks();

    // Scroll to top of form
    $('html, body').animate({ scrollTop: 0 }, 200);

  } catch (xhr) {
    onErr(xhr, 'Failed to fetch product');
  }
});


    // Duplicate
    $('#tblProducts').on('click','.btnDuplicate', function(){
      const id = $(this).closest('tr').data('id');
      POST(`/user-admin/marketplace/products/${id}/duplicate`, {})
        .done(()=>{ Swal.fire({icon:'success',title:'Duplicated (unlisted)'}); loadProducts(); })
        .fail(xhr=>onErr(xhr,'Failed to duplicate'));
    });

    // Delete
    $('#tblProducts').on('click','.btnDelete', function(){
      const id = $(this).closest('tr').data('id');
      Swal.fire({
        title:'Delete product?',
        text:'This will permanently delete the product.',
        icon:'warning', showCancelButton:true, confirmButtonText:'Yes, delete'
      }).then(r=>{
        if (!r.isConfirmed) return;
        $.ajax({ url:`/user-admin/marketplace/products/${id}`, method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} })
          .done(()=>{ Swal.fire({icon:'success',title:'Deleted'}); loadProducts(); })
          .fail(xhr=>onErr(xhr,'Failed to delete'));
      });
    });

    // Publish toggle
    $('#tblProducts').on('click','.btnTogglePublish', function(){
      const id = $(this).closest('tr').data('id');
      POST(`/user-admin/marketplace/products/${id}/publish-toggle`, {})
        .done(res=>{ Swal.fire({icon:'success',title:res.message}); loadProducts(); })
        .fail(xhr=>onErr(xhr,'Failed to update status'));
    });

    (async function init(){
      try{
        await loadUserMeta();
        const typeId = await loadTypes(null);
        await loadSubcategories(typeId, null);
        setFaqRowsFromData([]);
        await loadProducts();
        toggleMediaBlocks();
      } catch(e){}
    })();

    showList();
    
  });
</script>
