{{-- resources/views/UserAdmin/team_form.blade.php --}}
@include('UserAdmin.common.header')
@section('title', $mode === 'edit' ? 'Edit Team' : 'Create Team')
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Summernote (Bootstrap 4 build) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css" integrity="sha512-p7eSg7wQK5bR3v2mD4n2l4v8o3mTQKwgGmZ4hN6x7jWc5q2d8pR6+0k1lZVe0oGqB4KX5i8x2d0kWwQdQwq+0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.js" integrity="sha512-0+H3zQf1C6b8HqCk9jip1c3qk0lHzs7tG6O1qfQFf7E2l9l+o0G3Xwqv0f7gqC7aH7lZ9o0q9n6mH2XKJk6xgA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>window.LaravelStorageBase = "{{ url('media') }}";</script>

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>{{ $mode === 'edit' ? 'Edit Team' : 'Create Team' }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('user.admin.myteam') }}">My Teams</a></div>
        <div class="breadcrumb-item active">{{ $mode === 'edit' ? 'Edit' : 'Create' }}</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">

          {{-- Team Form --}}
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="m-0">{{ $mode === 'edit' ? 'Team Information' : 'New Team' }}</h4>
              <div>
                @if($mode === 'edit')
                  <a class="btn btn-outline-secondary" href="{{ route('user.admin.myteam.portfolio', $team->id) }}">Portfolio</a>
                  <form id="formDeleteTeam" class="d-inline" method="POST" action="{{ route('teams.delete', $team->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger"
                      onclick="return confirm('Delete this team? This cannot be undone.')">Delete Team</button>
                  </form>
                @endif
              </div>
            </div>

            <div class="card-body">
              <form id="formTeam" enctype="multipart/form-data"
                    method="POST"
                    action="{{ $mode === 'edit' ? route('teams.update', $team->id) : route('teams.store') }}">
                @csrf

                <div class="form-group">
                  <label>Team Name *</label>
                  <input type="text" name="team_name" class="form-control" required
                         value="{{ old('team_name', $team->team_name) }}">
                </div>

                <div class="form-group">
                  <label>About / Details</label>
                  <textarea id="aboutEditor" name="about" class="form-control summernote" rows="6">{{ old('about', $team->about) }}</textarea>
                </div>

                <div class="form-group">
                  <label>Profile Image</label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/*">
                    <label class="custom-file-label" for="profile_image">Choose file</label>
                  </div>
                  @if($mode === 'edit' && $team->profile_image)
                    <div class="mt-3">
                      <img src="{{ url('media/'.$team->profile_image) }}" alt="Team" style="max-height:120px;border-radius:8px;object-fit:cover;">
                    </div>
                  @endif
                </div>

                <button type="submit" class="btn btn-primary">
                  {{ $mode === 'edit' ? 'Save Changes' : 'Create Team' }}
                </button>
                <a href="{{ route('user.admin.myteam') }}" class="btn btn-outline-secondary">Back</a>
              </form>
            </div>
          </div>
          {{-- /Team Form --}}
        </div>

        @if($mode === 'edit')
        {{-- Projects Manager (only in edit mode) --}}
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="m-0">Projects</h4>
              <button class="btn btn-primary" data-toggle="modal" data-target="#modalAddProject" type="button">Add Project</button>
            </div>
            <div class="card-body">
              <div id="projectsList" class="row"><!-- filled by JS --></div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </section>
</div>

{{-- Add Project Modal (single) --}}
<div class="modal fade" id="modalAddProject" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Project</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="formAddProject" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label>Images (select multiple)</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="project_images" name="images[]" multiple accept="image/*">
              <label class="custom-file-label" for="project_images">Choose images</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button id="btnSaveProject" class="btn btn-primary" type="button">Save Project</button>
        <button id="btnSaveProjectAddAnother" class="btn btn-outline-primary" type="button">Save & Add Another</button>
        <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Edit Project Modal --}}
<div class="modal fade" id="modalEditProject" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Project</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="formEditProject">
          @csrf
          <input type="hidden" name="project_id" id="edit_project_id">
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" id="edit_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
          </div>
        </form>

        <hr>
        <h6>Images</h6>
        <form id="formAddImages" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="project_id" id="addimg_project_id">
          <div class="custom-file mb-3">
            <input type="file" class="custom-file-input" id="edit_project_images" name="images[]" multiple accept="image/*">
            <label class="custom-file-label" for="edit_project_images">Add more images</label>
          </div>
          <button id="btnAddImages" class="btn btn-outline-primary" type="button">Upload Images</button>
        </form>

        <div id="edit_images_grid" class="row mt-3"><!-- filled by JS --></div>
      </div>
      <div class="modal-footer">
        <button id="btnUpdateProject" class="btn btn-primary" type="button">Update</button>
        <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
      </div>
    </div>
  </div>
</div>

@include('UserAdmin.common.footer')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function($){
  // guard
  if (typeof $ === 'undefined') { console.error('jQuery missing'); return; }

  window.LaravelStorageBase = "{{ url('media') }}";
  const TEAM_ID = {{ $mode === 'edit' ? (int)$team->id : 'null' }};

  // Summernote + file label helpers
  $(function(){
    $('.summernote').summernote({height: 220});
    $(document).on('change', '.custom-file-input', function() {
      const fileName = Array.from(this.files || []).map(f => f.name).join(', ');
      $(this).next('.custom-file-label').text(fileName || 'Choose file');
    });
  });

  @if($mode === 'edit')
  // ===== Helpers =====
  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}

  // ===== API =====
  function fetchProjects() {
    $.get(`{{ route('teams.projects.index', $team->id) }}`)
      .done(res => {
        const list = $('#projectsList').empty();
        (res.data || []).forEach(p => list.append(renderProjectCard(p)));
      })
      .fail(xhr => Swal.fire({icon:'error', title: xhr.responseJSON?.message || 'Failed to load projects'}));
  }

  // expose for inline onclicks
  window.openEditProjectById = function(id){
    $.get(`{{ route('teams.projects.index', $team->id) }}`, function(res){
      const p = (res.data||[]).find(x => x.id == id);
      if (p) openEditProject(p);
    });
  };

// ✅ SweetAlert confirm – delete a whole project
window.deleteProject = function(id){
  Swal.fire({
    title: 'Delete this project?',
    text: 'All images for this project will be removed.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: `{{ url('/user-admin/teams/'.$team->id) }}/projects/${id}`,
      method: 'DELETE',
      data: {_token: $('meta[name="csrf-token"]').attr('content')}
    })
    .done(() => {
      fetchProjects();
      Swal.fire({icon:'success',title:'Project deleted',timer:1100,showConfirmButton:false});
    })
    .fail(xhr => Swal.fire({icon:'error',title:xhr.responseJSON?.message || 'Failed'}));
  });
};

// ✅ SweetAlert confirm – delete a single image
window.deleteImage = function(projectId, imageId, reopen=false){
  Swal.fire({
    title: 'Remove this image?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, remove',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: `{{ url('/user-admin/teams/'.$team->id) }}/projects/${projectId}/images/${imageId}`,
      method: 'DELETE',
      data: {_token: $('meta[name="csrf-token"]').attr('content')}
    })
    .done(() => {
      Swal.fire({icon:'success',title:'Image removed',timer:1100,showConfirmButton:false});
      fetchProjects();
      if (reopen) window.openEditProjectById(projectId);
    })
    .fail(xhr => Swal.fire({icon:'error',title:xhr.responseJSON?.message || 'Failed'}));
  });
};

  function openEditProject(p) {
    $('#edit_project_id').val(p.id);
    $('#edit_title').val(p.title);
    $('#edit_description').val(p.description || '');
    $('#addimg_project_id').val(p.id);
    const grid = $('#edit_images_grid').empty();
    (p.images || []).forEach(i => {
      grid.append(`
        <div class="col-6 col-md-3 mb-3">
          <div class="position-relative">
            <img src="{{ url('media') }}/${i.image_path}" class="img-fluid rounded" style="height:120px;object-fit:cover;">
            <button class="btn btn-sm btn-danger position-absolute" style="top:6px; right:6px;"
              onclick="deleteImage(${p.id}, ${i.id}, true)">&times;</button>
          </div>
        </div>
      `);
    });
    $('#modalEditProject').modal('show');
  }

  function renderProjectCard(p) {
    const imgs = (p.images || []).map(i => `
      <div class="col-6 col-md-3 mb-3">
        <div class="position-relative">
          <img src="{{ url('media') }}/${i.image_path}" class="img-fluid rounded" style="height:120px;object-fit:cover;">
          <button class="btn btn-sm btn-danger position-absolute" style="top:6px; right:6px;"
            onclick="deleteImage(${p.id}, ${i.id})">&times;</button>
        </div>
      </div>
    `).join('');

    return `
      <div class="col-12 col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="m-0">${escapeHtml(p.title)}</h4>
            <div>
              <button class="btn btn-outline-primary btn-sm" type="button" onclick="openEditProjectById(${p.id})">Edit</button>
              <button class="btn btn-danger btn-sm" type="button" onclick="deleteProject(${p.id})">Delete</button>
            </div>
          </div>
          <div class="card-body">
            ${p.description ? `<p>${escapeHtml(p.description)}</p>` : ''}
            <div class="row">${imgs || '<div class="col-12 text-muted">No images</div>'}</div>
          </div>
        </div>
      </div>
    `;
  }

  // ===== Create / Update / Add Images =====
  function createProject(closeAfter = true){
    const fd = new FormData(document.getElementById('formAddProject')); // includes _token
    $.ajax({
      url: `{{ route('teams.projects.store', $team->id) }}`,
      method: 'POST',
      data: fd, processData: false, contentType: false
    }).done(function(){
      if (closeAfter) {
        $('#modalAddProject').modal('hide');
      } else {
        $('#formAddProject')[0].reset();
        $('#project_images').next('.custom-file-label').text('Choose images');
        $('#formAddProject input[name="title"]').focus();
      }
      fetchProjects();
      Swal.fire({icon:'success',title:'Project saved',timer:1100,showConfirmButton:false});
    }).fail(function(xhr){
      const msg = xhr.responseJSON?.message || 'Failed to save project';
      const errors = xhr.responseJSON?.errors;
      if (errors) {
        let first = Object.values(errors)[0];
        if (Array.isArray(first)) first = first[0];
        Swal.fire({icon:'error',title:first || msg});
      } else {
        Swal.fire({icon:'error',title:msg});
      }
    });
  }

  function updateProject(){
    const id = $('#edit_project_id').val();
    const fd = new FormData(document.getElementById('formEditProject')); // includes _token
    $.ajax({
      url: `{{ url('/user-admin/teams/'.$team->id) }}/projects/${id}`,
      method: 'POST',
      data: fd, processData: false, contentType: false
    }).done(function(){
      $('#modalEditProject').modal('hide');
      fetchProjects();
      Swal.fire({icon:'success',title:'Project updated',timer:1100,showConfirmButton:false});
    }).fail(xhr => Swal.fire({icon:'error',title:xhr.responseJSON?.message || 'Failed'}));
  }

  function addImages(){
    const id = $('#addimg_project_id').val();
    const fd = new FormData(document.getElementById('formAddImages')); // includes _token
    $.ajax({
      url: `{{ url('/user-admin/teams/'.$team->id) }}/projects/${id}/images`,
      method: 'POST',
      data: fd, processData: false, contentType: false
    }).done(function(){
      Swal.fire({icon:'success',title:'Images added',timer:1100,showConfirmButton:false});
      $('#edit_project_images').val('').next('.custom-file-label').text('Add more images');
      fetchProjects();
    }).fail(xhr => Swal.fire({icon:'error',title:xhr.responseJSON?.message || 'Failed'}));
  }

  // ===== Delegated click handlers (always work) =====
  $(document).on('click', '#btnSaveProject',            function(e){ e.preventDefault(); createProject(true);  });
  $(document).on('click', '#btnSaveProjectAddAnother',  function(e){ e.preventDefault(); createProject(false); });
  $(document).on('click', '#btnUpdateProject',          function(e){ e.preventDefault(); updateProject();      });
  $(document).on('click', '#btnAddImages',              function(e){ e.preventDefault(); addImages();          });

  // init
  $(document).ready(fetchProjects);
  @endif
})(jQuery);
</script>
