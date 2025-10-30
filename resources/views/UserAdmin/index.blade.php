@include('UserAdmin.common.header')
<style>
  .custombtn{
    background-color: #CEFF1B !important; color: black !important; border-radius: 999px !important; border:0 !important; cursor: pointer !important;font-weight: 700 !important;padding: 0 16px !important; height: 38px !important; display: inline-flex !important; gap: 8px !important;font-size: 16px !important;    align-items: center;justify-content: center; color: black !important;
  }
</style>
<!-- Main Content -->
<div class="main-content">
  <section class="section" style="margin-bottom: 20px;">

    {{-- ===== Profile meter (unchanged) ===== --}}
    <div class="row">
      <div class="col-12">
        @if(isset($profileMeter) && ($profileMeter['percent'] ?? 0) < 100)
          <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap" role="alert">
          <div class="flex-grow-1" style="min-width:260px;">
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <strong>Your profile is {{ $profileMeter['percent'] }}% complete</strong>
            </div>

            <div class="progress mb-2" style="height: 10px;">
              <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                role="progressbar"
                style="width: {{ $profileMeter['percent'] }}%;"
                aria-valuenow="{{ $profileMeter['percent'] }}" aria-valuemin="0" aria-valuemax="100">
              </div>
            </div>

            @php
            $missing = $profileMeter['missing_tabs'] ?? [];
            $labels = ['settings' => 'Settings', 'security' => 'Security', 'kyc' => 'KYC'];
            @endphp

            @if(!empty($missing))
            <div class="text-small">
              Please complete:
              @foreach($missing as $t)
              <span class="badge badge-warning mr-1">{{ $labels[$t] ?? ucfirst($t) }}</span>
              @endforeach
            </div>
            @endif
          </div>

          <div class="mt-2 mt-sm-0">
            <a href="{{ route('user.admin.profile', ['tab' => $profileMeter['first_tab'] ?? 'about']) }}"
              class="btn btn-warning">
              <i class="fas fa-user-cog mr-1"></i> Complete Profile
            </a>
          </div>
      </div>
      @endif
    </div>
</div>

{{-- ===== KPI cards (same structure; dynamic content per role) ===== --}}
<div class="row">
  {{-- Card 1 --}}
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
    <div class="card">
      <div class="card-statistic-4">
        <div class="align-items-center justify-content-between">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
              <div class="card-content">
                @creator
                <h5 class="font-15" style="color:#CEFF1B;">Active Listings</h5>
                <h2 class="mb-3 font-18">{{ number_format($kpi['creator']['activeProducts'] ?? 0) }}</h2>
                <p class="mb-0"><span class="col-green">—</span> Live</p>
                @endcreator
                @client
                <h5 class="font-15" style="color:#CEFF1B;">Active Projects</h5>
                <h2 class="mb-3 font-18">{{ number_format($kpi['client']['activeProjects'] ?? 0) }}</h2>
                <p class="mb-0"><span class="col-green">—</span> Ongoing</p>
                @endclient
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
              <div class="banner-img">
                <img src="{{ asset('assets/img/banner/1.png') }}" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Card 2 --}}
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
    <div class="card">
      <div class="card-statistic-4">
        <div class="align-items-center justify-content-between">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
              <div class="card-content">
                @creator
                <h5 class="font-15" style="color:#CEFF1B;">Earnings (30d)</h5>
                 <h2 class="mb-3 font-18">{{ $currency['symbol'] ?? '$' }} {{ number_format($kpi['creator']['earnings30d'] ?? 0, 2) }}</h2>
                <p class="mb-0"><span class="col-green">—</span> Net</p>
                @endcreator
                @client
                <h5 class="font-15" style="color:#CEFF1B;">Total Spend</h5>
                <h2 class="mb-3 font-18">{{ $currency['symbol'] ?? '$' }} {{ number_format($kpi['client']['spendTotal'] ?? 0, 2) }}</h2>
                <p class="mb-0"><span class="col-orange">—</span> Last 30 Days</p>
                @endclient

              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
              <div class="banner-img">
                <img src="{{ asset('assets/img/banner/2.png') }}" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Card 3 --}}
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
    <div class="card">
      <div class="card-statistic-4">
        <div class="align-items-center justify-content-between">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
              <div class="card-content">
                @creator
                <h5 class="font-15" style="color:#CEFF1B;">Orders in Progress</h5>
                <h2 class="mb-3 font-18">{{ number_format($kpi['creator']['ordersInProgress'] ?? 0) }}</h2>
                <p class="mb-0"><span class="col-green">—</span> Services</p>
                @endcreator
                @client
                <h5 class="font-15" style="color:#CEFF1B;">Open Milestones</h5>
                <h2 class="mb-3 font-18">{{ number_format($kpi['client']['openMilestones'] ?? 0) }}</h2>
                <p class="mb-0"><span class="col-green">—</span> In progress</p>
                @endclient
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
              <div class="banner-img">
                <img src="{{ asset('assets/img/banner/3.png') }}" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @creator
  {{-- Card 4 --}}
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
    <div class="card">
      <div class="card-statistic-4">
        <div class="align-items-center justify-content-between">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
              <div class="card-content">
                @creator
                <h5 class="font-15" style="color:#CEFF1B;">Avg. Rating / XP</h5>
                <h2 class="mb-3 font-18">
                  <i class="fas fa-star"></i>
                  {{ isset($kpi['creator']['avgRating']) ? number_format($kpi['creator']['avgRating'], 1) : '—' }}
                </h2>
                <p class="mb-0"><span class="col-green">—</span> Quality</p>
                @endcreator
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
              <div class="banner-img">
                <img src="{{ asset('assets/img/banner/4.png') }}" alt="">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endcreator
</div>



{{-- ================= CREATOR ================= --}}
@creator
{{-- 1) My Gigs / Orders --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4>My Gigs / Orders</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Client</th>
                <th>Status</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($creator['orders'] ?? []) as $o)
              @php
              $client = trim(($o->buyer->first_name ?? '').' '.($o->buyer->last_name ?? ''));
              $dueRaw = $o->milestones()->max('end_date');
              $due = $dueRaw ? \Carbon\Carbon::parse($dueRaw)->format('Y-m-d') : null;
              @endphp
              <tr>
                <td>{{ $o->product->name ?? '—' }}</td>
                <td>{{ $client !== '' ? $client : '—' }}</td>
                <td><span class="badge badge-{{ in_array($o->status,['approved_paid','in_progress']) ? 'info' : ($o->status==='completed'?'success':'secondary') }}">{{ Str::headline($o->status) }}</span></td>
                <td>{{ $due ?? '—' }}</td>
                <td>{{ ($o->currency_symbol ?? '') }}{{ number_format((float)($o->total_payable ?? $o->subtotal ?? 0), 2) }}</td>
                <td><a href="{{ route('service.contracts.show', $o->id) }}" class="btn btn-sm btn-primary">View</a></td>
              </tr>

              @empty
              <tr>
                <td colspan="6" class="text-center text-muted">No orders yet.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
      <div class="col-12">
         <div class="card">
      <div class="card-header">
        <h4>Boosts</h4>
      </div>
      <div class="card-body">
        <div class="row">
          @forelse(($creator['boosts'] ?? []) as $b)
          @php
          $img = null;
          $imgs = is_array($b->product->images ?? []) ? $b->product->images : (json_decode($b->product->images ?? '[]', true) ?: []);
          if (!empty($imgs)) $img = url('/media/' . ltrim($imgs[0], '/'));

          $endAt = $b->end_at ? \Carbon\Carbon::parse($b->end_at) : null;
          $daysLeft = $endAt ? now()->diffInDays($endAt, false) : 0;
          @endphp

          <div class="col-12">
            <div class="card card-primary">
              <div class="card-body d-flex">
                <img src="{{ $img }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;margin-right:12px;">
                <div>
                  <div class="font-weight-bold">{{ $b->product->name ?? '—' }}</div>
                  <div class="text-small text-muted">Active • {{ max(0,$daysLeft) }} days left</div>
                  <a href="{{ route('user.admin.marketplace.boosts') }}" class="btn btn-sm btn-outline-primary mt-2">Upgrade</a>
                </div>
              </div>
            </div>
          </div>
          @empty
          <div class="col-12 text-center text-muted">No active boosts.</div>
          @endforelse
        </div>
      </div>
    </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
<div class="mt-3 d-flex gap-2">
  <a href="{{ route('user.admin.marketplace') }}" class="custombtn w-100 mr-2 mb-2" style="color: black !important;">New Listing</a>
  <a href="{{ route('service.orders.index') }}" class="custombtn w-100 mr-2 mb-2" style="color: black !important;">Orders</a>
  <a href="{{ route('forum') }}" class="custombtn w-100 mb-2" style="color: black !important;">Forum</a>
</div>
      </div>
    </div>
{{-- Quick Actions --}}
    
@endcreator

{{-- ================= CLIENT ================= --}}
@client
{{-- My Projects --}}

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4>My Projects</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Project</th>
                <th>Creator</th>
                <th>State</th>
                <th>Budget</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($client['projects'] ?? []) as $o)
              @php
              $creator = trim(($o->seller->first_name ?? '').' '.($o->seller->last_name ?? ''));
              @endphp
              <tr>
                <td><a href="{{ route('service.contracts.show', $o->id) }}">{{ $o->product->name ?? '—' }}</a></td>
                <td>{{ $creator !== '' ? $creator : '—' }}</td>
                <td><span class="badge badge-{{ in_array($o->status,['approved_paid','in_progress']) ? 'info' : ($o->status==='completed'?'success':'secondary') }}">{{ Str::headline($o->status) }}</span></td>
                <td>{{ ($o->budget_symbol ?? '') }}{{ number_format((float)($o->budget_total ?? 0), 2) }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center text-muted">No projects yet.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
{{-- Milestones --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4>Milestones</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Project</th>
                <th>Milestone</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($client['milestones'] ?? []) as $m)
              @php
              $project = $m->order->product->name ?? '—';
              $status = $m->status;
              @endphp
              <tr>
                <td>{{ $project }}</td>
                <td>{{ $m->title ?? '—' }}</td>
                <td><span class="badge badge-{{ $status === 'submitted' ? 'warning' : ($status === 'released' ? 'success' : 'secondary') }}">{{ Str::headline($status) }}</span></td>
                <td>
                  @if($status === 'submitted')
                  <form method="POST" action="{{ route('service.milestones.release', $m->id) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-success">Approve</button>
                  </form>
                  <form method="POST" action="{{ route('service.milestones.request_cancel', $m->id) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">Request changes</button>
                  </form>
                  @else
                  <a href="{{ route('service.contracts.show', $m->order->id) }}" class="btn btn-sm btn-secondary">View</a>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center text-muted">No milestones yet.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Transactions (optional) --}}
{{-- Transactions --}}
<div class="row">
  <div class="col-12">
<div class="card">
  <div class="card-header"><h4>Transactions</h4></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Gateway</th>
            <th>Status</th>
            <th class="text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($client['transactions'] ?? collect()) as $t)
            <tr>
              <td>{{ optional($t->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                <span class="badge badge-{{ $t->type === 'credit' ? 'success' : 'warning' }}">
                  {{ ucfirst($t->type) }}
                </span>
              </td>
              <td>
                {{ strtoupper($t->gateway ?? '-') }}
                @if(!empty($t->gateway_ref))
                  <div class="text-muted small">Ref: {{ $t->gateway_ref }}</div>
                @endif
              </td>
              <td>
                <span class="badge badge-{{ $t->status === 'success' ? 'success' : ($t->status === 'failed' ? 'danger' : 'secondary') }}">
                  {{ ucfirst($t->status ?? '-') }}
                </span>
              </td>
              <td class="text-right">
                {{ $t->currency_symbol ?? '' }}{{ number_format((float)($t->amount ?? 0), 2) }}
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">No transactions yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
</div>

<div class="row">
  <div class="col-12">
    <div class="mt-3 d-flex gap-2">
  <a href="{{ route('marketplace') }}" class="custombtn w-100 mr-2 mb-2" style="color: black !important;">Browse marketplace</a>
  <a href="{{ route('forum') }}" class="custombtn w-100 mb-2" style="color: black !important;">Forum</a>
</div>
  </div>
</div>
{{-- Quick Actions --}}

@endclient


{{-- ===== Analytics (unchanged) ===== --}}
{{-- ===== Analytics (unchanged) ===== --}}
<div class="row" style="display: none;">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h4>Last 30 Days — Views vs Clicks</h4>
      </div>
      <div class="card-body">
        <canvas id="dailyChart" style="height: 320px;"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h4>Top Products (30 days)</h4>
      </div>
      <div class="card-body">
        <canvas id="topChart" style="height: 480px;"></canvas>
      </div>
    </div>
  </div>
</div>

</section>

@include('UserAdmin.common.settingbar')
</div>

@include('UserAdmin.common.footer')

{{-- Chart.js + analytics wiring (unchanged) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  (function() {
    const dailyUrl = @json(route('user.admin.analytics.boosted.daily'));
    const topUrl = @json(route('user.admin.analytics.boosted.top'));

    function makeDailyChart(labels, views, clicks) {
      const ctx = document.getElementById('dailyChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
              label: 'Views',
              data: views,
              tension: 0.3
            },
            {
              label: 'Clicks',
              data: clicks,
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true
            }
          },
          scales: {
            x: {
              display: true
            },
            y: {
              display: true,
              beginAtZero: true
            }
          }
        }
      });
    }

    function makeTopChart(rows) {
      const labels = rows.map(r => r.name);
      const views = rows.map(r => r.views);
      const clicks = rows.map(r => r.clicks);
      const ctx = document.getElementById('topChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
              label: 'Views',
              data: views
            },
            {
              label: 'Clicks',
              data: clicks
            }
          ]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              beginAtZero: true
            }
          }
        }
      });
    }

    async function init() {
      try {
        const [dRes, tRes] = await Promise.all([fetch(dailyUrl), fetch(topUrl)]);
        const daily = await dRes.json();
        const top = await tRes.json();
        makeDailyChart(daily.labels || [], daily.viewsData || [], daily.clicksData || []);
        makeTopChart(top.rows || []);
      } catch (e) {
        console.error('Analytics fetch failed:', e);
      }
    }
    document.addEventListener('DOMContentLoaded', init);
  })();
</script>