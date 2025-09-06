@include('UserAdmin.common.header')

<!-- Main Content -->
<div class="main-content">
  <section class="section">

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
                $labels  = ['settings' => 'Settings', 'security' => 'Security', 'kyc' => 'KYC'];
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

    <!-- KPI cards (unchanged) -->
    <div class="row ">
      <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
          <div class="card-statistic-4">
            <div class="align-items-center justify-content-between">
              <div class="row ">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                  <div class="card-content">
                    <h5 class="font-15">New Booking</h5>
                    <h2 class="mb-3 font-18">258</h2>
                    <p class="mb-0"><span class="col-green">10%</span> Increase</p>
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

      <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
          <div class="card-statistic-4">
            <div class="align-items-center justify-content-between">
              <div class="row ">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                  <div class="card-content">
                    <h5 class="font-15"> Customers</h5>
                    <h2 class="mb-3 font-18">1,287</h2>
                    <p class="mb-0"><span class="col-orange">09%</span> Decrease</p>
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

      <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
          <div class="card-statistic-4">
            <div class="align-items-center justify-content-between">
              <div class="row ">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                  <div class="card-content">
                    <h5 class="font-15">New Project</h5>
                    <h2 class="mb-3 font-18">128</h2>
                    <p class="mb-0"><span class="col-green">18%</span> Increase</p>
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

      <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
        <div class="card">
          <div class="card-statistic-4">
            <div class="align-items-center justify-content-between">
              <div class="row ">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                  <div class="card-content">
                    <h5 class="font-15">Revenue</h5>
                    <h2 class="mb-3 font-18">$48,697</h2>
                    <p class="mb-0"><span class="col-green">42%</span> Increase</p>
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
    </div>

    <!-- Boost Analytics (replaces Assign Task Table) -->
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header"><h4>Last 30 Days — Views vs Clicks</h4></div>
          <div class="card-body">
            <canvas id="dailyChart" style="height: 320px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header"><h4>Top Products (30 days)</h4></div>
          <div class="card-body">
            <canvas id="topChart" style="height: 480px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Projects Payments (unchanged) -->
    <div class="row">
      <div class="col-md-6 col-lg-12 col-xl-6">
        <div class="card">
          <div class="card-header">
            <h4>Projects Payments</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Client Name</th>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>1</td><td>John Doe </td><td>11-08-2018</td><td>NEFT</td><td>$258</td></tr>
                  <tr><td>2</td><td>Cara Stevens</td><td>15-07-2018</td><td>PayPal</td><td>$125</td></tr>
                  <tr><td>3</td><td>Airi Satou</td><td>25-08-2018</td><td>RTGS</td><td>$287</td></tr>
                  <tr><td>4</td><td>Angelica Ramos</td><td>01-05-2018</td><td>CASH</td><td>$170</td></tr>
                  <tr><td>5</td><td>Ashton Cox</td><td>18-04-2018</td><td>NEFT</td><td>$970</td></tr>
                  <tr><td>6</td><td>John Deo</td><td>22-11-2018</td><td>PayPal</td><td>$854</td></tr>
                  <tr><td>7</td><td>Hasan Basri</td><td>07-09-2018</td><td>Cash</td><td>$128</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>      
    </div>
  </section>

  @include('UserAdmin.common.settingbar')
</div>

@include('UserAdmin.common.footer')

<!-- Chart.js + wiring for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const dailyUrl = @json(route('user.admin.analytics.boosted.daily'));
  const topUrl   = @json(route('user.admin.analytics.boosted.top'));

  function makeDailyChart(labels, views, clicks){
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          { label: 'Views',  data: views,  tension: 0.3 },
          { label: 'Clicks', data: clicks, tension: 0.3 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: { x: { display: true }, y: { display: true, beginAtZero: true } }
      }
    });
  }

  function makeTopChart(rows){
    const labels = rows.map(r => r.name);
    const views  = rows.map(r => r.views);
    const clicks = rows.map(r => r.clicks);
    const ctx = document.getElementById('topChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'Views',  data: views },
          { label: 'Clicks', data: clicks }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: { x: { beginAtZero: true } }
      }
    });
  }

  async function init(){
    try {
      const [dRes, tRes] = await Promise.all([fetch(dailyUrl), fetch(topUrl)]);
      const daily = await dRes.json();
      const top   = await tRes.json();

      makeDailyChart(daily.labels || [], daily.viewsData || [], daily.clicksData || []);
      makeTopChart(top.rows || []);
    } catch(e) {
      console.error('Analytics fetch failed:', e);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
</script>
