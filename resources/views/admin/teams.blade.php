@include('admin.common.header')
@section('title','My Team (Admin)')

<div class="main-content">
  <section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
      <h1 class="m-0">My Team</h1>
      <div class="btn-group">
        <a id="t-export-csv" href="javascript:void(0)" class="btn btn-outline-primary mx-1">Export CSV</a>
        <a id="t-export-pdf" href="javascript:void(0)" class="btn btn-primary mx-1">Export PDF</a>
        <a id="t-view-full" href="javascript:void(0)" class="btn btn-info mx-1" style="display:none;">View Full Portfolio</a>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <!-- Left: Teams -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <div class="input-group">
                <input id="t-search-teams" type="text" class="form-control" placeholder="Search teams...">
                <div class="input-group-append">
                  <button id="t-btn-search-teams" class="btn btn-primary">Search</button>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <ul id="t-teams" class="list-group list-group-flush">
                <li class="list-group-item text-center text-muted">Loading teams...</li>
              </ul>
            </div>
            <div class="card-footer d-flex justify-content-end">
              <ul id="t-teams-pager" class="pagination mb-0"></ul>
            </div>
          </div>
        </div>

        <!-- Right: Members & Portfolio -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header d-flex align-items-center">
              <h4 id="t-team-title" class="m-0">Select a team</h4>
              <div class="ml-auto d-flex">
                <input id="t-search-members" class="form-control mr-2" type="text" placeholder="Search members...">
                <button id="t-btn-search-members" class="btn btn-outline-primary">Search</button>
              </div>
            </div>
            <div class="card-body">

              <h6 class="text-muted mb-2">Members</h6>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Image</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Positions</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="t-members">
                    <tr><td colspan="6" class="text-center text-muted">Select a team</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="d-flex justify-content-end">
                <ul id="t-members-pager" class="pagination mb-0"></ul>
              </div>

              <hr>

              <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-muted mb-2">Team Portfolio</h6>
                <div>
                  <a id="t-export-portfolio-csv" href="javascript:void(0)" class="btn btn-outline-secondary btn-sm mx-1">Export Portfolio CSV</a>
                  <a id="t-export-portfolio-pdf" href="javascript:void(0)" class="btn btn-secondary btn-sm mx-1">Export Portfolio PDF</a>
                </div>
              </div>

              {{-- Preview grid --}}
              <div id="t-portfolio" class="thumb-grid">
                <div class="text-muted">Select a team</div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  .avatar-img {
    width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #eee;
  }
  .team-img {
    width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #eee; margin-right:8px;
  }
  .thumb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    grid-gap: 12px;
  }
  .thumb {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    padding: 6px;
    text-align: center;
    background: #fff;
  }
  .thumb img { width:100%; height:120px; object-fit:cover; border-radius:6px; }
  .thumb .title { font-size: 13px; margin-top: 6px; font-weight: 600; }
  .thumb .desc { font-size: 12px; color:#666; margin-top: 4px; height:36px; overflow:hidden; }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

<script>
(function(){
  function getCsrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    if (m && m.getAttribute) return m.getAttribute('content');
    const i = document.querySelector('input[name="_token"]');
    if (i) return i.value;
    return null;
  }
  const csrf = getCsrfToken();
  if (csrf && window.jQuery) $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf }});

  // Hosted fallbacks (unique per item; no local assets needed)
  function teamPlaceholder(name){
    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name || 'Team') + '&background=random&size=128';
  }
  function memberPlaceholder(name, email, id){
    const label = (name && name.trim()) || (email && email.trim()) || (id ? ('ID'+id) : 'User');
    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(label) + '&background=random&size=128';
  }
  const projectPlaceholder = 'https://placehold.co/300x180?text=No+Image';

  const $teams = $('#t-teams'), $teamsPager = $('#t-teams-pager');
  const $teamsQ = $('#t-search-teams');
  const $btnTeamsQ = $('#t-btn-search-teams');

  const $members = $('#t-members'), $membersPager = $('#t-members-pager');
  const $membersQ = $('#t-search-members'), $btnMembersQ = $('#t-btn-search-members');

  const $portfolio = $('#t-portfolio');
  const $title = $('#t-team-title');
  const $viewFull = $('#t-view-full');

  let teamState   = { page: 1, q: '', per_page: 10 };
  let memberState = { page: 1, q: '', per_page: 10, team_id: null, team_name: '' };

  function loadTeams() {
    $teams.html('<li class="list-group-item text-center text-muted">Loading...</li>');
    $.getJSON('{{ route('admin.teams.list') }}', teamState, function(res){
      if (!res || !res.ok) return $teams.html('<li class="list-group-item text-center text-danger">Failed to load</li>');
      renderTeams(res.data || []);
      renderTeamsPager(res.meta || {});
    }).fail(function(){
      $teams.html('<li class="list-group-item text-center text-danger">Failed to load</li>');
    });
  }

  function renderTeams(rows) {
    if (!rows.length) {
      $teams.html('<li class="list-group-item text-center text-muted">No teams</li>');
      return;
    }
    let html = '';
    rows.forEach(r => {
      const fallback = teamPlaceholder(r.team_name);
      const img = (r.profile_img && String(r.profile_img).trim()) ? r.profile_img : fallback;
      html += `
        <li class="list-group-item d-flex align-items-center team-item" data-id="${r.id}" data-name="${esc(r.team_name)}" style="cursor:pointer;">
          <img src="${img}" class="team-img" onerror="this.onerror=null;this.src='${fallback}';">
          <div>
            <div class="font-weight-600">${esc(r.team_name)}</div>
            <div class="text-muted small">${esc((r.about||'').slice(0,60))}</div>
          </div>
        </li>`;
    });
    $teams.html(html);
  }

  function renderTeamsPager(meta) {
    let html = '';
    const cp = meta.current_page || 1, lp = meta.last_page || 1;
    function li(p, label, active, disabled) {
      return `<li class="page-item ${active?'active':''} ${disabled?'disabled':''}">
                <a class="page-link" href="#" data-p="${p}">${label}</a>
              </li>`;
    }
    html += li(cp-1, '&laquo;', false, cp<=1);
    for (let p = Math.max(1, cp-2); p <= Math.min(lp, cp+2); p++) html += li(p, p, cp===p, false);
    html += li(cp+1, '&raquo;', false, cp>=lp);
    $teamsPager.html(html);
  }

  $teamsPager.on('click','a.page-link', function(e){
    e.preventDefault();
    const p = parseInt(this.dataset.p, 10);
    if (Number.isFinite(p)) { teamState.page = p; loadTeams(); }
  });

  $btnTeamsQ.on('click', function(){
    teamState.q = $teamsQ.val().trim();
    teamState.page = 1;
    loadTeams();
  });

  $teams.on('click','.team-item', function(){
    const id   = this.dataset.id;
    const name = this.dataset.name || ('Team #'+id);
    memberState.team_id = id;
    memberState.team_name = name;
    memberState.page = 1;
    $title.text(name);

    $viewFull.show().off('click').on('click', function(){
      window.location.href = `{{ url('/admin/teams') }}/${id}/portfolio/view`;
    });

    loadMembers();
    loadPortfolio();

    $('#t-export-csv').off('click').on('click', function(){
      download(`{{ url('/admin/teams') }}/${id}/export?type=csv&scope=members&q=${encodeURIComponent(memberState.q||'')}`);
    });
    $('#t-export-pdf').off('click').on('click', function(){
      download(`{{ url('/admin/teams') }}/${id}/export?type=pdf&scope=members&q=${encodeURIComponent(memberState.q||'')}`);
    });
    $('#t-export-portfolio-csv').off('click').on('click', function(){
      download(`{{ url('/admin/teams') }}/${id}/export?type=csv&scope=portfolio`);
    });
    $('#t-export-portfolio-pdf').off('click').on('click', function(){
      download(`{{ url('/admin/teams') }}/${id}/export?type=pdf&scope=portfolio`);
    });
  });

  function loadMembers() {
    if (!memberState.team_id) return;
    $members.html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    $.getJSON(`{{ url('/admin/teams') }}/${memberState.team_id}/members`, {
      q: memberState.q, per_page: memberState.per_page, page: memberState.page
    }, function(res){
      if (!res || !res.ok) return $members.html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
      $title.text((res.team && res.team.name) ? res.team.name : ('Team #'+memberState.team_id));
      renderMembers(res.data || []);
      renderMembersPager(res.meta || {});
    }).fail(function(){
      $members.html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
    });
  }

  function renderMembers(rows) {
    if (!rows.length) {
      $members.html('<tr><td colspan="6" class="text-center text-muted">No members</td></tr>');
      return;
    }
    let html = '';
    rows.forEach(r => {
      const fallback = memberPlaceholder(r.name, r.email, r.id);
      const img = (r.avatar && String(r.avatar).trim()) ? r.avatar : fallback;
      html += `
        <tr>
          <td><img src="${img}" class="avatar-img" onerror="this.onerror=null;this.src='${fallback}';"></td>
          <td>${esc(r.name||'')}</td>
          <td>${esc(r.email||'')}</td>
          <td>${esc(r.role||'')}</td>
          <td>${esc(r.positions||'')}</td>
          <td>${esc(r.status||'')}</td>
        </tr>`;
    });
    $members.html(html);
  }

  function renderMembersPager(meta) {
    let html = '';
    const cp = meta.current_page || 1, lp = meta.last_page || 1;
    function li(p, label, active, disabled) {
      return `<li class="page-item ${active?'active':''} ${disabled?'disabled':''}">
                <a class="page-link" href="#" data-p="${p}">${label}</a>
              </li>`;
    }
    html += li(cp-1, '&laquo;', false, cp<=1);
    for (let p = Math.max(1, cp-2); p <= Math.min(lp, cp+2); p++) html += li(p, p, cp===p, false);
    html += li(cp+1, '&raquo;', false, cp>=lp);
    $membersPager.html(html);
  }

  $membersPager.on('click','a.page-link', function(e){
    e.preventDefault();
    const p = parseInt(this.dataset.p, 10);
    if (Number.isFinite(p)) { memberState.page = p; loadMembers(); }
  });

  $btnMembersQ.on('click', function(){
    memberState.q = $membersQ.val().trim();
    memberState.page = 1;
    loadMembers();
  });

  function loadPortfolio() {
    if (!memberState.team_id) return;
    $portfolio.html('<div class="text-muted">Loading portfolio...</div>');
    $.getJSON(`{{ url('/admin/teams') }}/${memberState.team_id}/portfolio`, {}, function(res){
      if (!res || !res.ok) return $portfolio.html('<div class="text-danger">Failed to load</div>');
      renderPortfolio(res.data || []);
    }).fail(function(){
      $portfolio.html('<div class="text-danger">Failed to load</div>');
    });
  }

  function renderPortfolio(items) {
    if (!items.length) { $portfolio.html('<div class="text-muted">No projects</div>'); return; }
    let html = '';
    items.forEach(p => {
      const first = (p.images && p.images.length) ? p.images[0] : '';
      const img = first || projectPlaceholder;
      html += `
        <div class="thumb">
          <img src="${img}" onerror="this.onerror=null;this.src='${projectPlaceholder}';">
          <div class="title">${esc((p.title||'Project'))}</div>
          <div class="desc">${esc((p.description||'').slice(0,80))}</div>
        </div>`;
    });
    $portfolio.html(html);
  }

  function download(url){
    const a = document.createElement('a'); a.href = url; a.style.display='none';
    document.body.appendChild(a); a.click(); a.remove();
  }

  function esc(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

  loadTeams();
})();
</script>

@include('admin.common.footer')
