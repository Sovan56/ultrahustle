{{-- resources/views/UserAdmin/myteam.blade.php --}}
@include('UserAdmin.common.header')

<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
  // Make storage base available to JS for image URLs
  window.LaravelStorageBase = "{{ url('media') }}";
</script>

<div class="main-content">
  <section class="section">
    <div class="section-body">

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body d-flex gap-2">
              {{-- Create Team now goes to a separate page --}}
              <button type="button" class="btn btn-primary mx-2"
                      onclick="window.location='{{ route('user.admin.myteam.create') }}'">
                Create Team
              </button>

              {{-- Add Member stays as a modal, unchanged --}}
              <button type="button" class="btn btn-primary mx-2" data-toggle="modal" data-target="#addmember">
                Add Member
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        {{-- LEFT: Teams --}}
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
          <div class="card">
            <div class="body">
              <div class="card-header">
                <h4>My Teams</h4>
              </div>
              <div id="plist" class="people-list">
                <div class="chat-search">
                  <input id="teamSearch" type="text" class="form-control" placeholder="Search..." />
                </div>
                <div class="m-b-20">
                  <div id="chat-scroll">
                    <ul id="teamsList" class="chat-list list-unstyled m-b-0"><!-- filled by JS --></ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- RIGHT: Selected team + members --}}
        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
          <div class="card">
            <div class="body">
              <div id="plist" class="people-list">
                <div class="card-header">
                  <h4 id="selectedTeamTitle">Team</h4>
                </div>
                <div class="chat-search">
                  <input id="memberSearch" type="text" class="form-control" placeholder="Search..." />
                </div>
                <div class="m-b-20">
                  <div id="chat-scroll">
                    <ul id="membersList" class="chat-list list-unstyled m-b-0"><!-- filled by JS --></ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </section>

  {{-- Otika right settings panel (unchanged) --}}
  <div class="settingSidebar">
    <a href="javascript:void(0)" class="settingPanelToggle">
      <i class="fa fa-spin fa-cog"></i>
    </a>
    <div class="settingSidebar-body ps-container ps-theme-default">
      <div class=" fade show active">
        <div class="setting-panel-header">Setting Panel</div>

        <div class="p-15 border-bottom">
          <h6 class="font-medium m-b-10">Select Layout</h6>
          <div class="selectgroup layout-color w-50">
            <label class="selectgroup-item">
              <input type="radio" name="value" value="1" class="selectgroup-input-radio select-layout" checked>
              <span class="selectgroup-button">Light</span>
            </label>
            <label class="selectgroup-item">
              <input type="radio" name="value" value="2" class="selectgroup-input-radio select-layout">
              <span class="selectgroup-button">Dark</span>
            </label>
          </div>
        </div>

        <div class="p-15 border-bottom">
          <h6 class="font-medium m-b-10">Sidebar Color</h6>
          <div class="selectgroup selectgroup-pills sidebar-color">
            <label class="selectgroup-item">
              <input type="radio" name="icon-input" value="1" class="selectgroup-input select-sidebar">
              <span class="selectgroup-button selectgroup-button-icon" data-toggle="tooltip"
                    data-original-title="Light Sidebar"><i class="fas fa-sun"></i></span>
            </label>
            <label class="selectgroup-item">
              <input type="radio" name="icon-input" value="2" class="selectgroup-input select-sidebar" checked>
              <span class="selectgroup-button selectgroup-button-icon" data-toggle="tooltip"
                    data-original-title="Dark Sidebar"><i class="fas fa-moon"></i></span>
            </label>
          </div>
        </div>

        <div class="p-15 border-bottom">
          <h6 class="font-medium m-b-10">Color Theme</h6>
          <div class="theme-setting-options">
            <ul class="choose-theme list-unstyled mb-0">
              <li title="white" class="active"><div class="white"></div></li>
              <li title="cyan"><div class="cyan"></div></li>
              <li title="black"><div class="black"></div></li>
              <li title="purple"><div class="purple"></div></li>
              <li title="orange"><div class="orange"></div></li>
              <li title="green"><div class="green"></div></li>
              <li title="red"><div class="red"></div></li>
            </ul>
          </div>
        </div>

        <div class="p-15 border-bottom">
          <div class="theme-setting-options">
            <label class="m-b-0">
              <input type="checkbox" class="custom-switch-input" id="mini_sidebar_setting">
              <span class="custom-switch-indicator"></span>
              <span class="control-label p-l-10">Mini Sidebar</span>
            </label>
          </div>
        </div>

        <div class="p-15 border-bottom">
          <div class="theme-setting-options">
            <label class="m-b-0">
              <input type="checkbox" class="custom-switch-input" id="sticky_header_setting">
              <span class="custom-switch-indicator"></span>
              <span class="control-label p-l-10">Sticky Header</span>
            </label>
          </div>
        </div>

        <div class="mt-4 mb-4 p-3 align-center rt-sidebar-last-ele">
          <a href="#" class="btn btn-icon icon-left btn-primary btn-restore-theme">
            <i class="fas fa-undo"></i> Restore Default
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add member modal (unchanged) --}}
<div class="modal fade" id="addmember" tabindex="-1" role="dialog" aria-labelledby="addmemberTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addmemberTitle">Add Member</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="addMemberForm">
        <div class="modal-body">
          <div class="form-group">
            <label>Select Team Name</label>
            <select id="addMemberTeamSelect" class="form-control select2" required>
              <option value="">Select Team</option>
            </select>
          </div>

          <div id="emailPairs"></div>

          <div class="d-flex justify-content-end">
            <button id="btnAddPair" type="button" class="btn btn-outline-primary">Add</button>
          </div>
        </div>

        <div class="modal-footer bg-whitesmoke br">
          <button id="btnSaveMembers" type="button" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </form>

    </div>
  </div>
</div>

@include('UserAdmin.common.footer')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  (function() {
    if (typeof $ === 'undefined') {
      alert('jQuery missing');
      return;
    }
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': CSRF } });

    let selectedTeamId = null;

    // Toasts
    const toastOk = (msg) => Swal.fire({ icon: 'success', title: msg, timer: 1400, showConfirmButton: false });
    const toastErr = (msg) => Swal.fire({ icon: 'error', title: msg || 'Something went wrong' });

    const safeText = (t) => $('<div>').text(t ?? '').html();

    // storage URL helpers
    function storageUrl(p) {
      if (!p) return '';
      if (/^https?:\/\//i.test(p)) return p;
      p = String(p).replace(/^\/+/, '');
      if (!p.startsWith('teams/')) p = 'teams/' + p;
      const base = (window.LaravelStorageBase || '/storage').replace(/\/+$/, '');
      return base + '/' + p;
    }
    function storageUrlProfile(p) {
      if (!p) return '';
      if (/^https?:\/\//i.test(p)) return p;
      p = String(p).replace(/^\/+/, '');
      if (!p.startsWith('profile/')) p = 'profile/' + p;
      const base = (window.LaravelStorageBase || '/storage').replace(/\/+$/, '');
      return base + '/' + p;
    }

    // loading states
    function setLoading($btn, isLoading) {
      if (!$btn || !$btn.length) return;
      if (isLoading) $btn.addClass('disabled btn-progress').attr('disabled', true);
      else $btn.removeClass('disabled btn-progress').attr('disabled', false);
    }

    // ===== RENDERERS =====
    function renderTeams(teams) {
      const $list = $('#teamsList').empty();
      if (!teams.length) {
        $list.append('<li class="text-center text-muted py-3">No teams yet</li>');
        $('#selectedTeamTitle').text('Team');
        $('#membersList').empty();
        return;
      }
      teams.forEach(t => {
        const img = t.profile_image ? storageUrl(t.profile_image) : '{{ asset('assets/img/users/user-4.png') }}';
        const created = t.created_at ? new Date(t.created_at).toLocaleDateString() : '';

        // Buttons: Edit (navigate), Portfolio (navigate), Delete (AJAX)
        const ownerButtons = t.is_owner ? `
          <div class="buttons d-flex">
            <a href="{{ route('user.admin.myteam.edit', 0) }}".replace('/0/edit','/${t.id}/edit') 
               class="btn btn-icon btn-primary mr-2" title="Edit"
               onclick="event.preventDefault(); window.location='{{ route('user.admin.myteam.edit', 0) }}'.replace('/0/edit','/${t.id}/edit');">
              <i class="far fa-edit"></i>
            </a>
            <a href="{{ route('user.admin.myteam.portfolio', 0) }}".replace('/0/portfolio','/${t.id}/portfolio')
               class="btn btn-icon btn-outline-secondary mr-2" title="Portfolio"
               onclick="event.preventDefault(); window.location='{{ route('user.admin.myteam.portfolio', 0) }}'.replace('/0/portfolio','/${t.id}/portfolio');">
              <i class="far fa-folder-open"></i>
            </a>
            <a href="#" class="btn btn-icon btn-danger btn-del-team" data-id="${t.id}" title="Delete">
              <i class="fas fa-times"></i>
            </a>
          </div>` : `
          <div class="buttons d-flex">
            <a href="{{ route('user.admin.myteam.portfolio', 0) }}".replace('/0/portfolio','/${t.id}/portfolio')
               class="btn btn-icon btn-outline-secondary" title="Portfolio"
               onclick="event.preventDefault(); window.location='{{ route('user.admin.myteam.portfolio', 0) }}'.replace('/0/portfolio','/${t.id}/portfolio');">
              <i class="far fa-folder-open"></i>
            </a>
          </div>`;

        $list.append(`
          <div class="d-flex justify-content-between align-items-center team-row py-2"
               data-id="${t.id}"
               data-name="${safeText(t.team_name)}"
               style="cursor:pointer;">
            <li class="clearfix m-0">
              <img src="${img}" alt="avatar" class="rounded-circle"
                   style="width:45px; height:45px; object-fit:cover;"
                   onerror="this.onerror=null;this.src='{{ asset('assets/img/users/user-4.png') }}';">
              <div class="about">
                <div class="name">${safeText(t.team_name)}</div>
                <div class="status">created_at ${created}</div>
              </div>
            </li>
            ${ownerButtons}
          </div>
        `);
      });
    }

    function renderMembers(payload) {
      const team = payload.team;
      const members = payload.members;
      const counts = payload.counts || { joined: 0, pending: 0 };
      const canManage = payload.canManage === true;

      $('#selectedTeamTitle').text(
        canManage ? `${team.team_name} (Joined: ${counts.joined} | Pending: ${counts.pending})`
                  : `${team.team_name} (Joined: ${counts.joined})`
      );

      const $list = $('#membersList').empty();
      if (!members.length) {
        $list.append('<li class="text-center text-muted py-3">No members</li>');
        return;
      }

      members.forEach(m => {
        const email = m.member_email ?? m.member_id ?? '';
        const status = m.status;
        const pos = m.positions || '';
        const avatar = m.profile_picture ? storageUrlProfile(m.profile_picture) : '{{ asset('assets/img/users/user-2.png') }}';

        $list.append(`
          <div class="d-flex justify-content-between align-items-center member-row py-2">
            <li class="clearfix m-0">
              <img src="${avatar}" alt="avatar" class="rounded-circle"
                   style="width:45px;height:45px;object-fit:cover;"
                   onerror="this.onerror=null;this.src='{{ asset('assets/img/users/user-2.png') }}'">
              <div class="about">
                <div class="name">${safeText(email)}</div>
                <div class="status">
                  ${safeText(pos)} (${safeText(m.role)})
                  ${canManage && status!=='accepted'
                    ? `<span class="badge badge-warning ml-2 text-uppercase">${safeText(status)}</span>`
                    : ''
                  }
                </div>
              </div>
            </li>
            <div class="buttons d-flex">
              ${canManage && status==='pending'
                ? `<a href="#" class="btn btn-icon btn-primary mr-2 btn-resend" data-id="${m.id}" title="Resend Invite"><i class="far fa-paper-plane"></i></a>`
                : ''
              }
              ${canManage
                ? `<a href="#" class="btn btn-icon btn-danger btn-remove" data-id="${m.id}" title="Remove"><i class="fas fa-times"></i></a>`
                : ''
              }
            </div>
          </div>
        `);
      });
    }

    // ===== LOADERS =====
    function loadTeams(q = '') {
      return $.get(`{{ route('teams.index') }}`, { q })
        .done(res => {
          const teams = res?.data || [];
          renderTeams(teams);

          const $sel = $('#addMemberTeamSelect').empty().append('<option value="">Select Team</option>');
          teams.forEach(t => $sel.append(`<option value="${t.id}">${safeText(t.team_name)}</option>`));

          if (teams.length) {
            if (!selectedTeamId || !teams.find(t => t.id == selectedTeamId)) {
              selectedTeamId = teams[0].id;
            }
            loadMembers(selectedTeamId);
          } else {
            selectedTeamId = null;
            $('#selectedTeamTitle').text('Team');
            $('#membersList').empty();
          }
        })
        .fail(err => {
          console.error('teams.index failed', err);
          toastErr();
        });
    }

    function loadMembers(teamId, q = '') {
      selectedTeamId = teamId;
      return $.get(`{{ url('/user-admin/teams') }}/${teamId}/members`, { q })
        .done(res => renderMembers(res.data))
        .fail(err => {
          console.error('teams.members failed', err);
          toastErr();
        });
    }

    // ===== INIT =====
    $(document).ready(function() {
      loadTeams();
    });

    // ===== SEARCH =====
    $('#teamSearch').on('input', function() {
      loadTeams(this.value);
    });
    $('#memberSearch').on('input', function() {
      if (selectedTeamId) loadMembers(selectedTeamId, this.value);
    });

    // ===== SELECT TEAM =====
    $(document).on('click', '.team-row', function(e) {
      if ($(e.target).closest('.buttons').length) return; // clicking actions shouldn't reselect
      loadMembers($(this).data('id'));
    });

    // ===== DELETE TEAM (AJAX, unchanged) =====
    $(document).on('click', '.btn-del-team', function(e) {
      e.preventDefault();
      const teamId = $(this).data('id');
      Swal.fire({
        title: 'Delete team?',
        text: 'This will remove all members and related data.',
        icon: 'warning',
        showCancelButton: true
      }).then(ans => {
        if (!ans.isConfirmed) return;
        const $btn = $(e.currentTarget);
        setLoading($btn, true);
        $.ajax({
          url: `{{ url('/user-admin/teams') }}/${teamId}`,
          method: 'DELETE'
        })
        .done(() => {
          toastOk('Deleted');
          loadTeams();
        })
        .fail(xhr => {
          console.error('deleteTeam failed', xhr);
          toastErr('Delete failed');
        })
        .always(() => setLoading($btn, false));
      });
    });

    // ===== ADD MEMBER MODAL: dynamic pairs =====
    function addPairRow(email = '', position = '', roleVal = 'user') {
      $('#emailPairs').append(`
        <div class="form-row align-items-end pair-row">
          <div class="form-group col-md-4">
            <label>Member Email</label>
            <input type="email" class="form-control pair-email" placeholder="Enter Member Email" value="${email}">
          </div>
          <div class="form-group col-md-3">
            <label>Member Position</label>
            <input type="text" class="form-control pair-position" placeholder="Enter Member Position" value="${position}">
          </div>
          <div class="form-group col-md-3">
            <label>Role</label>
            <select class="form-control pair-role">
              <option value="user"  ${roleVal === 'user'  ? 'selected' : ''}>User</option>
              <option value="admin" ${roleVal === 'admin' ? 'selected' : ''}>Admin</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <button type="button" class="btn btn-danger btn-block btn-remove-pair">Remove</button>
          </div>
        </div>
      `);
    }

    $('#addmember').on('shown.bs.modal', function() {
      $('#emailPairs').empty();
      addPairRow();
      // preselect current team if any
      if (selectedTeamId) $('#addMemberTeamSelect').val(String(selectedTeamId));
    });

    $('#btnAddPair').on('click', function(e) {
      e.preventDefault();
      addPairRow();
    });
    $(document).on('click', '.btn-remove-pair', function() {
      $(this).closest('.pair-row').remove();
    });

    // ===== SAVE MEMBERS (send invites) =====
    $('#btnSaveMembers').off('click').on('click', function(e){
      e.preventDefault();
      const $btn = $(this);
      const teamId = $('#addMemberTeamSelect').val();
      if (!teamId) return toastErr('Select a team');

      const fd = new FormData();
      fd.append('_token', CSRF);

      let idx = 0;
      $('#emailPairs .pair-row').each(function(){
        const email    = $(this).find('.pair-email').val()?.trim();
        const position = $(this).find('.pair-position').val()?.trim();
        const role     = $(this).find('.pair-role').val();

        if (email) {
          fd.append(`pairs[${idx}][email]`, email);
          fd.append(`pairs[${idx}][position]`, position || '');
          fd.append(`pairs[${idx}][role]`, role || 'user');
          idx++;
        }
      });

      if (idx === 0) return toastErr('Add at least one email');

      setLoading($btn, true);
      $.ajax({
        url: `{{ url('/user-admin/teams') }}/${teamId}/members`,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
      })
      .done(()=>{
        toastOk('Invites sent');
        $('#addmember').modal('hide');
        loadMembers(teamId);
      })
      .fail(xhr=>{
        console.error('addMembers failed', xhr);
        toastErr(xhr.responseJSON?.message || 'Failed to invite');
      })
      .always(()=> setLoading($btn, false));
    });

    // ===== REMOVE MEMBER =====
    $(document).on('click', '.btn-remove', function(e) {
      e.preventDefault();
      const memberId = $(this).data('id');
      const teamId = selectedTeamId;
      if (!teamId) return toastErr('Select a team first');

      Swal.fire({
        title: 'Remove this member?',
        icon: 'warning',
        showCancelButton: true
      }).then(ans => {
        if (!ans.isConfirmed) return;
        const $btn = $(e.currentTarget);
        setLoading($btn, true);
        $.ajax({
          url: `{{ url('/user-admin/teams') }}/${teamId}/members/${memberId}`,
          method: 'DELETE'
        })
        .done(() => {
          toastOk('Removed');
          loadMembers(teamId);
        })
        .fail(xhr => {
          console.error('removeMember failed', xhr);
          toastErr();
        })
        .always(() => setLoading($btn, false));
      });
    });

    // ===== RESEND INVITE =====
    $(document).on('click', '.btn-resend', function(e) {
      e.preventDefault();
      const memberId = $(this).data('id');
      const teamId = selectedTeamId;
      const $btn = $(this);
      setLoading($btn, true);
      $.post(`{{ url('/user-admin/teams') }}/${teamId}/members/${memberId}/resend`)
        .done(() => toastOk('Invite resent'))
        .fail(xhr => {
          console.error('resendInvite failed', xhr);
          toastErr();
        })
        .always(() => setLoading($btn, false));
    });

  })();
</script>
