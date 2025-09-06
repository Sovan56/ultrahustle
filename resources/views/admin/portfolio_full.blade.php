{{-- resources/views/admin/portfolio_full.blade.php --}}
@include('admin.common.header')
@section('title', 'Team Portfolio')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script> window.LaravelStorageBase = "{{ url('media') }}"; </script>

<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Portfolio: {{ $team->team_name }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.teams.page') }}">My Teams</a></div>
        <div class="breadcrumb-item active">Portfolio</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        {{-- LEFT: Summary --}}
        <div class="col-12 col-md-4">
          <div class="card">
            <div class="card-body text-center">
              <img
                src="{{ $team->resolved_profile }}"
                alt="Team"
                class="img-fluid rounded mb-3"
                style="max-height:160px;object-fit:cover;"
                onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($team->team_name ?? 'Team') }}&background=random&size=300';"
              >
              <h4 class="mb-2">{{ $team->team_name }}</h4>
              <div class="text-muted mb-3">Members: {{ $memberCount }}</div>

              @if($team->about)
                <div class="text-left">{!! $team->about !!}</div>
              @else
                <p class="text-muted">No details provided.</p>
              @endif
            </div>
          </div>
        </div>

        {{-- RIGHT: Members + Projects --}}
        <div class="col-12 col-md-8">

          {{-- Members --}}
          <div class="card">
            <div class="card-header">
              <h4 class="m-0">Members</h4>
            </div>
            <div class="card-body">
              @if($team->members->isEmpty())
                <p class="text-muted">No members yet.</p>
              @else
                <div class="row">
                  @foreach($team->members as $m)
                    <div class="col-12 col-md-6 mb-3">
                      <div class="d-flex align-items-center">
                        <img
                          src="{{ $m->resolved_avatar }}"
                          class="rounded-circle mr-3"
                          style="width:48px;height:48px;object-fit:cover;"
                          onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($m->member_name ?? ($m->member_email ?? ($m->member_id ?? 'User'))) }}&background=random&size=128';"
                        >
                        <div>
                          <div class="font-weight-bold">
                            {{ $m->member_name ?? $m->member_email ?? $m->member_id }}
                            <span class="badge badge-{{ ($m->role ?? '') === 'admin' ? 'primary' : 'secondary' }} ml-2">
                              {{ ucfirst($m->role ?? 'member') }}
                            </span>
                          </div>
                          <div class="small text-muted">
                            position: {{ $m->positions ?: '—' }}
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          {{-- Projects --}}
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="m-0">Projects</h4>
            </div>
            <div class="card-body">
              @php $projects = $team->projects; @endphp
              @if($projects->isEmpty())
                <p class="text-muted">No projects yet.</p>
              @else
                <div class="row">
                  @foreach($projects as $p)
                    <div class="col-12 col-md-6 mb-4">
                      <div class="card">
                        <div class="card-header">
                          <h4 class="m-0">{{ $p->title ?? $p->project_title ?? 'Project' }}</h4>
                        </div>
                        <div class="card-body">
                          @php $desc = $p->description ?? $p->project_desc ?? ''; @endphp
                          @if($desc)
                            <p>{!! $desc !!}</p>
                          @endif
                          <div class="row">
                            @php $imgs = $p->resolved_images ?? []; @endphp
                            @forelse($imgs as $img)
                              <div class="col-6 mb-3">
                                <img
                                  src="{{ $img }}"
                                  class="img-fluid rounded"
                                  style="height:120px;object-fit:cover;"
                                  onerror="this.onerror=null;this.src='https://placehold.co/300x180?text=No+Image';"
                                >
                              </div>
                            @empty
                              <div class="col-12 text-muted">No images.</div>
                            @endforelse
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

@include('admin.common.footer')
