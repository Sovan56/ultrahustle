{{-- resources/views/UserAdmin/team_portfolio.blade.php --}}
@include('UserAdmin.common.header')
@section('title', 'Team Portfolio')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
    window.LaravelStorageBase = "{{ url('media') }}";
</script>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Portfolio: {{ $team->team_name }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('user.admin.myteam') }}">My Teams</a></div>
                <div class="breadcrumb-item active">Portfolio</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                {{-- LEFT: Summary --}}
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <img src="{{ $team->profile_image ? url('media/'.$team->profile_image) : asset('assets/img/users/user-4.png') }}"
                                alt="Team" class="img-fluid rounded mb-3" style="max-height:160px;object-fit:cover;">
                            <h4 class="mb-2">{{ $team->team_name }}</h4>
                            <div class="text-muted mb-3">Members: {{ $memberCount }}</div>

                            @if($team->about)
                            <div class="text-left">{!! $team->about !!}</div>
                            @else
                            <p class="text-muted">No details provided.</p>
                            @endif

                            @if($canManage)
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('user.admin.myteam.edit', $team->id) }}" class="btn btn-primary">Edit Team</a>
                                {{-- any other manage buttons you have go here --}}
                            </div>
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
                                        <img src="{{ asset('assets/img/users/user-2.png') }}" class="rounded-circle mr-3" style="width:48px;height:48px;">
                                        <div>
                                            <div class="font-weight-bold">
                                                {{ $m->member_id ?? $m->member_email }}
                                                <span class="badge badge-{{ $m->role === 'admin' ? 'primary' : 'secondary' }} ml-2">{{ ucfirst($m->role) }}</span>
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
                            @if($canManage)
                            <a href="{{ route('user.admin.myteam.edit', $team->id) }}" class="btn btn-outline-primary btn-sm">Manage</a>
                            @endif
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
                                            <h4 class="m-0">{{ $p->title }}</h4>
                                        </div>
                                        <div class="card-body">
                                            @if($p->description)
                                            <p>{{ $p->description }}</p>
                                            @endif
                                            <div class="row">
                                                @forelse($p->images as $img)
                                                <div class="col-6 mb-3">
                                                    <img src="{{ url('media/'.$img->image_path) }}" class="img-fluid rounded"
                                                        style="height:120px;object-fit:cover;">
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

@include('UserAdmin.common.footer')