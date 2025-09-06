@include('common.header')
<!-- slider -->
<div
  class="tf-slideshow slider-effect-fade slider-baby flat-spacing-25 position-relative pb_0">
  <div class="container">
    <div
      dir="ltr"
      class="swiper tf-sw-slideshow radius-10"
      data-preview="1"
      data-tablet="1"
      data-mobile="1"
      data-centered="false"
      data-space="0"
      data-loop="false"
      data-auto-play="false"
      data-delay="2000"
      data-speed="1000">
      <div class="swiper-wrapper">
        <div class="swiper-slide" lazy="true">
          <div class="wrap-slider">
            <img
              class="lazyload"
              data-src="{{ asset('images/slider/baby-slide1.jpg') }}"
              src="{{ asset('images/slider/baby-slide1.jpg') }}"
              alt="hp-slideshow-01" />
            <div class="box-content">
              <div class="container">
                <p
                  class="fade-item fade-item-1 fw-6 d-block subheading text_white">
                  Summer Fashion
                </p>
                <h2 class="fade-item fade-item-2 fw-6 heading text_white">
                  Further Reductions
                  <!-- </h2>
                      <div class="fade-item fade-item-3">
                        <a
                          href="shop-default.html"
                          class="tf-btn btn-light-icon animate-hover-btn btn-xl"
                          ><span>Shop now</span
                          ><i class="icon icon-arrow-right"></i
                        ></a>
                      </div> -->
              </div>
            </div>
          </div>
        </div>
        <div class="swiper-slide" lazy="true">
          <div class="wrap-slider row-end">
            <img
              class="lazyload"
              data-src="{{ asset('images/slider/baby-slide2.jpg') }}"
              src="{{ asset('images/slider/baby-slide2.jpg') }}"
              alt="hp-slideshow-02" />
            <div class="box-content">
              <div class="container">
                <p
                  class="fade-item fade-item-1 fw-6 d-block subheading text_white">
                  Winter Fashion
                </p>
                <h2 class="fade-item fade-item-2 fw-6 heading text_white">
                  Discover the latest items
                </h2>
                <div class="fade-item fade-item-3">
                  <a
                    href="shop-default.html"
                    class="tf-btn btn-light-icon animate-hover-btn btn-xl"><span>Shop now</span><i class="icon icon-arrow-right"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="swiper-slide" lazy="true">
          <div class="wrap-slider">
            <img
              class="lazyload"
              data-src="{{ asset('images/slider/baby-slide3.jpg') }}"
              src="{{ asset('images/slider/baby-slide3.jpg') }}"
              alt="hp-slideshow-03" />
            <div class="box-content">
              <div class="container">
                <p
                  class="fade-item fade-item-1 fw-6 d-block subheading text_white">
                  Spring Fashion
                </p>
                <h2 class="fade-item fade-item-2 fw-6 heading text_white">
                  Special Editions
                </h2>
                <div class="fade-item fade-item-3">
                  <a
                    href="shop-default.html"
                    class="tf-btn btn-light-icon animate-hover-btn btn-xl"><span>Shop now</span><i class="icon icon-arrow-right"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="wrap-pagination">
        <div class="container">
          <div
            class="sw-dots sw-pagination-slider justify-content-center"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /slider -->

<!-- <div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-chalkboard-teacher"></i>
        <h5 class="card-title">Programming & Tech</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-paint-brush"></i>
        <h5 class="card-title">Graphics & Design</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-bullhorn"></i>
        <h5 class="card-title">Digital Marketing</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-pen"></i>
        <h5 class="card-title">Writing & Translation</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-video"></i>
        <h5 class="card-title">Video & Animation</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-robot"></i>
        <h5 class="card-title">AI Services</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-music"></i>
        <h5 class="card-title">Music & Audio</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-users"></i>
        <h5 class="card-title">Business</h5>
      </div>
    </div>
    <div class="col-auto">
      <div class="service-card">
        <i class="fas fa-user-tie"></i>
        <h5 class="card-title">Consulting</h5>
      </div>
    </div>
  </div>
</div> -->
{{-- resources/views/welcome.blade.php (Flash sale section REPLACEMENT) --}}


@php
$subs = \App\Models\ProductSubcategory::with('type')
    ->where('is_active', 1)->orderBy('name')->get();
@endphp

<div class="container mt-5">
  <div class="row justify-content-start">
    @foreach($subs as $sc)
      <div class="col-auto mb-3">
        <a href="{{ route('marketplace', ['type_id' => $sc->product_type_id, 'sub_id' => $sc->id]) }}"
           class="text-decoration-none text-reset">
          <div class="service-card text-center p-3 border rounded">
            @if($sc->icon_class)
              <i class="{{ $sc->icon_class }}"></i>
            @endif
            <h5 class="card-title mt-2">{{ $sc->name }}</h5>
          </div>
        </a>
      </div>
    @endforeach
  </div>
</div>


<meta name="csrf-token" content="{{ csrf_token() }}">

<section class="flat-spacing-17 flat-flash-sale">
  <div class="container">
    <div class="tf-flash-sale">
      <div class="flat-title flex-row justify-content-center">
        <span class="title fw-6">Popular Gigs</span>
      </div>

      @php
      /** @var \Illuminate\Support\Collection $boostedCards */
      $cards = $boostedCards ?? collect();
      $boostedCount = (int)($boostedCount ?? $cards->count());
      @endphp

      @if($cards->isEmpty())
      <p class="text-center text-muted">No boosted products yet.</p>
      @else
      {{-- If more than 6, show a slider-style row; otherwise a simple grid --}}
      @if($boostedCount > 6)
      <div class="swiper tf-sw-product-sell"
        data-preview="4" data-tablet="2" data-mobile="1"
        data-space-lg="30" data-space-md="15" data-pagination="1">
        <div class="swiper-wrapper">
          @foreach($cards as $c)
          <div class="swiper-slide">
            <a href="{{ $c['url'] ?? route('product.details',['id'=>$c['id']]) }}"
              class="text-decoration-none text-reset product-card boosted-card"
              data-id="{{ $c['id'] }}">
              <div class="card h-100 position-relative product-card-wrap">
                {{-- wishlist heart --}}
                <button type="button"
                  class="wishlist-toggle btn-wishlist"
                  data-product-id="{{ $c['id'] }}"
                  data-wished="0"
                  aria-label="Toggle wishlist">
                  <i class="far fa-heart"></i>
                </button>

                <img src="{{ $c['cover'] }}" class="card-img-top" alt="{{ $c['name'] }}">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-2 gap-2">
                    <img src="{{ $c['avatar'] }}" class="rounded-circle" width="40" height="40" alt="User">
                    <span class="username fw-6">{{ $c['seller'] }}</span>
                    <span class="text-muted small d-flex align-items-center gap-1 ms-auto">
                      <i class="fa fa-star text-warning"></i>
                      {{ $c['rating'] }} ({{ $c['reviews'] }})
                    </span>
                  </div>
                  <h6 class="card-title">{{ \Illuminate\Support\Str::limit($c['name'], 86) }}</h6>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="badge bg-light border">Verified</div>
                    <div class="price">
                      @if(!empty($c['price']))
                      From {{ $c['price'] }}
                      @else
                      <span class="text-muted">Price N/A</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          @endforeach
        </div>
        <div class="sw-dots style-2 sw-pagination-product justify-content-center"></div>
      </div>
      @else
      <div class="container py-4">
        <div class="row g-4">
          @foreach($cards as $c)
          <div class="col-md-4">
            <a href="{{ $c['url'] ?? route('product.details',['id'=>$c['id']]) }}"
              class="text-decoration-none text-reset product-card boosted-card"
              data-id="{{ $c['id'] }}">
              <div class="card h-100 position-relative product-card-wrap">
                {{-- wishlist heart --}}
                <button type="button"
                  class="wishlist-toggle btn-wishlist"
                  data-product-id="{{ $c['id'] }}"
                  data-wished="0"
                  aria-label="Toggle wishlist">
                  <i class="far fa-heart"></i>
                </button>

                <img src="{{ $c['cover'] }}" class="card-img-top" alt="{{ $c['name'] }}">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-2 gap-2">
                    <img src="{{ $c['avatar'] }}" class="rounded-circle" width="40" height="40" alt="User">
                    <span class="username fw-6">{{ $c['seller'] }}</span>
                    <span class="text-muted small d-flex align-items-center gap-1 ms-auto">
                      <i class="fa fa-star text-warning"></i>
                      {{ $c['rating'] }} ({{ $c['reviews'] }})
                    </span>
                  </div>
                  <h6 class="card-title">{{ \Illuminate\Support\Str::limit($c['name'], 86) }}</h6>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="badge bg-light border">Verified</div>
                    <div class="price">
                      @if(!empty($c['price']))
                      From {{ $c['price'] }}
                      @else
                      <span class="text-muted">Price N/A</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          @endforeach
        </div>
      </div>
      @endif
      @endif
    </div>
  </div>
</section>

{{-- ===== Wishlist styles (overlay heart, responsive) ===== --}}
<style>
  .product-card-wrap {
    overflow: hidden;
  }

  .product-card-wrap .card-img-top {
    width: 100%;
    height: 200px;
    object-fit: cover;
  }

  .wishlist-toggle {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 36px;
    height: 36px;
    border: 0;
    outline: none;
    border-radius: 50%;
    background: rgba(0, 0, 0, .45);
    display: inline-flex
;
    align-items: center;
    justify-content: center;
    z-index: 2;
  }

  .wishlist-toggle i {
    font-size: 18px;
    line-height: 1;
  }

  .wishlist-toggle:focus {
    outline: none;
  }

  @media (max-width: 575.98px) {
    .product-card-wrap .card-img-top {
      height: 170px;
    }

    .wishlist-toggle {
      width: 38px;
      height: 38px;
    }

    .wishlist-toggle i {
      font-size: 19px;
    }
  }

  .btn-wishlist i {
    color: #CEFF1B !important;
  }
</style>

{{-- ===== Analytics: clicks + in-viewport views for boosted cards ===== --}}
<script>
  (function() {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    // CLICK -> product_clicks + insights.clicks
    document.addEventListener('click', function(e) {
      const a = e.target.closest('a.product-card');
      if (!a) return;
      // ignore if the wishlist heart was clicked (we stop it below too)
      if (e.target.closest('.btn-wishlist')) return;

      const id = +a.dataset.id || 0;
      if (!id) return;
      fetch("{{ route('analytics.product.click') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token
        },
        body: JSON.stringify({
          product_id: id,
          source: "welcome"
        }),
        keepalive: true
      }).catch(() => {});
    }, {
      passive: true
    });

    // VIEW -> product_views + insights.views (boosted cards only here)
    const seen = new Set();
    const queue = [];
    let flushTimer = null;

    function flushNow() {
      if (!queue.length) return;
      const items = queue.splice(0, queue.length);
      fetch("{{ route('analytics.list.view') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token
        },
        body: JSON.stringify({
          items,
          source: "welcome"
        }),
        keepalive: true
      }).catch(() => {});
    }

    function enqueue(id) {
      if (seen.has(id)) return;
      seen.add(id);
      queue.push(id);
      clearTimeout(flushTimer);
      flushTimer = setTimeout(flushNow, 800);
    }

    const cards = document.querySelectorAll('.boosted-card[data-id]');
    if ('IntersectionObserver' in window && cards.length) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const id = +entry.target.dataset.id || 0;
            if (id) enqueue(id);
          }
        });
      }, {
        root: null,
        threshold: 0.35
      });
      cards.forEach(c => io.observe(c));
    } else {
      // fallback: count once for all on load
      cards.forEach(c => {
        const id = +c.dataset.id || 0;
        if (id) enqueue(id);
      });
      flushNow();
    }
  })();
</script>

{{-- ===== Wishlist toggle (no alerts, updates header badge) ===== --}}
{{-- ===== Wishlist toggle (no alerts, updates header badge, hydrates on load) ===== --}}
<script>
  (function() {
    const TOGGLE_URL = @json(route('wishlist.toggle'));
    const COUNT_URL  = @json(route('wishlist.count'));
    const ITEMS_URL  = @json(route('wishlist.items'));
    const LOGIN_URL  = @json(route('login')) + '?redirect=' + encodeURIComponent(location.href);
    const csrf       = document.querySelector('meta[name="csrf-token"]').content;
    const IS_LOGGED_IN = {{ auth()->check() || session('user_id') ? 'true' : 'false' }};

    // Try to open a login modal on this page; fallback to redirect
    function openLoginOrRedirect() {
      const modalEl =
        document.getElementById('authModal') ||
        document.getElementById('loginModal') ||
        document.querySelector('.modal#login') ||
        document.querySelector('[data-auth-modal]') ||
        null;

      if (modalEl && window.bootstrap && typeof bootstrap.Modal === 'function') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        return;
      }

      // No modal present -> redirect, and let that page auto-open the modal
      const url = new URL(@json(route('login')), window.location.origin);
      url.searchParams.set('redirect', window.location.href);
      url.searchParams.set('login', '1'); // optional flag
      window.location.href = url.toString();
    }

    // If we got here via ?login=1, auto-open modal (and clean URL)
    (function maybeAutoOpenLogin(){
      const sp = new URLSearchParams(location.search);
      if (sp.get('login') === '1') {
        openLoginOrRedirect();
        try { history.replaceState({}, '', location.pathname + location.hash); } catch(_){}
      }
    })();

    // helper to flip icon state and force lime color
    function updateHeart(btn, wished) {
      if (!btn) return;
      btn.dataset.wished = wished ? '1' : '0';
      const icon = btn.querySelector('i');
      if (!icon) return;

      // Solid vs Regular
      icon.classList.toggle('fas', wished);
      icon.classList.toggle('fa-solid', wished);
      icon.classList.toggle('far', !wished);
      icon.classList.toggle('fa-regular', !wished);

      // Force lime color with !important (both states)
      icon.style.setProperty('color', '#CEFF1B', 'important');
    }

    // click to toggle (no SweetAlert)
    document.addEventListener('click', async function(e) {
      const btn = e.target.closest('.btn-wishlist');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      // Not logged in? Open login modal here on Welcome (or redirect if none)
      if (!IS_LOGGED_IN) {
        openLoginOrRedirect();
        return;
      }

      const pid = parseInt(btn.dataset.productId || '0', 10);
      if (!pid) return;

      try {
        const res = await fetch(TOGGLE_URL, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ product_id: pid })
        });

        if (res.status === 403 || res.status === 401) {
          // If the session died mid-click, show modal or redirect
          openLoginOrRedirect();
          return;
        }

        const js = await res.json().catch(() => ({}));
        if (!js || !js.ok) return;

        updateHeart(btn, !!js.wished);

        // refresh header badge
        try {
          const cRes = await fetch(COUNT_URL);
          const cJs  = await cRes.json();
          const b    = document.getElementById('wishlistCountBadge') ||
                       document.querySelector('.nav-wishlist .count-box');
          if (b && cJs && typeof cJs.count === 'number') {
            b.textContent = cJs.count;
            b.style.transition = 'transform .15s ease';
            b.style.transform = 'scale(1.2)';
            setTimeout(() => b.style.transform = 'scale(1)', 150);
          }
        } catch (_) {}
      } catch (_) {}
    }, false);

    // HYDRATE: mark hearts solid for products already in wishlist
    document.addEventListener('DOMContentLoaded', async function() {
      try {
        const res = await fetch(ITEMS_URL, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const js = await res.json().catch(() => ({ ok: false, items: [] }));
        if (!js.ok || !Array.isArray(js.items)) return;

        const wishedSet = new Set(js.items.map(it => Number(it.product_id)));

        document.querySelectorAll('.btn-wishlist[data-product-id]').forEach(btn => {
          const pid = Number(btn.dataset.productId || 0);
          updateHeart(btn, wishedSet.has(pid)); // sets solid/regular and lime color
        });

        // Ensure header badge shows correct count
        try {
          const b = document.getElementById('wishlistCountBadge') ||
                    document.querySelector('.nav-wishlist .count-box');
          if (b) b.textContent = wishedSet.size;
        } catch (_) {}
      } catch (_) {}
    });
  })();
</script>







<div class="container py-3">
  <div class="flat-title flex-row justify-content-center">
    <span class="title fw-6 wow fadeInUp" data-wow-delay="0s">About Us</span>
  </div>
  <div class="row align-items-center">
    <!-- Left text section -->
    <div class="col-md-6 mb-4 mb-md-0 text-center text-md-start">
      <p class="text-section">
        Fiverr operates all over the world with freelancers and businesses
        spanning an estimated 160 countries. The company was founded in
        2010, is headquartered in Tel Aviv, and has satellite offices in
        New York, London, Kyiv, Berlin, and Orlando.
      </p>
    </div>
    <div class="col-md-6 text-center">
      <img
        src="{{ asset('images/slider/baby-slide1.jpg') }}"
        alt="Team Working"
        class="rounded-img shadow" />
    </div>
  </div>
</div>
<div class="container">
  <div class="line"></div>
</div>
<section class="flat-testimonial-v2 flat-spacing-24">
  <div class="container">
    <div class="wrapper-thumbs-testimonial-v2 flat-thumbs-testimonial">
      <div class="box-left">
        <div dir="ltr" class="swiper tf-sw-tes-2" data-preview="1" data-space-lg="40"
          data-space-md="30">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <div class="testimonial-item lg lg-2">
                <h4 class="mb_40">Our customer’s reviews</h4>
                <div class="icon">
                  <img class="lazyload" data-src="{{ asset('images/item/quote.svg') }}" alt=""
                    src="{{ asset('images/item/quote.svg') }}" height="300px">
                </div>
                <div class="rating">
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                </div>
                <p class="text">
                  "I have been shopping with this web fashion site for over a year now and I
                  can confidently say it is the best online fashion site out there.The
                  shipping is always fast and the customer service team is friendly and
                  helpful. I highly recommend this site to anyone looking for affordable
                  clothing."
                </p>
                <div class="author box-author">
                  <div class="box-img d-md-none rounded-0">
                    <img class="lazyload img-product" data-src="{{ asset('images/item/tets3.jpg') }}"
                      src="{{ asset('images/item/tets3.jpg') }}" alt="image-product">
                  </div>
                  <div class="content">
                    <div class="name">Robert smith</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="testimonial-item lg lg-2">
                <h4 class="mb_40">Our customer’s reviews</h4>
                <div class="icon">
                  <img class="lazyload" data-src="{{ asset('images/item/quote.svg') }}" alt=""
                    src="{{ asset('images/item/quote.svg') }}">
                </div>
                <div class="rating">
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                  <i class="icon-star"></i>
                </div>
                <p class="text">
                  "I have been shopping with this web fashion site for over a year now and I
                  can confidently say it is the best online fashion site out there.The
                  shipping is always fast and the customer service team is friendly and
                  helpful. I highly recommend this site to anyone looking for affordable
                  clothing."
                </p>
                <div class="author box-author">
                  <div class="box-img d-md-none rounded-0">
                    <img class="lazyload img-product" data-src="{{ asset('images/item/tets4.jpg') }}"
                      src="{{ asset('images/item/tets4.jpg') }}" alt="image-product">
                  </div>
                  <div class="content">
                    <div class="name">Jenifer Unix</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="d-md-flex d-none box-sw-navigation">
          <div class="nav-sw nav-next-slider nav-next-tes-2"><span
              class="icon icon-arrow-left"></span></div>
          <div class="nav-sw nav-prev-slider nav-prev-tes-2"><span
              class="icon icon-arrow-right"></span></div>
        </div>
        <div class="d-md-none sw-dots style-2 sw-pagination-tes-2"></div>
      </div>
      <div class="box-right">
        <div dir="ltr" class="swiper tf-thumb-tes" data-preview="1" data-space="30">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <div class="img-sw-thumb">
                <img class="lazyload img-product" data-src="{{ asset('images/item/tets3.jpg') }}"
                  src="{{ asset('images/item/tets3.jpg') }}" alt="image-product">
              </div>
            </div>
            <div class="swiper-slide">
              <div class="img-sw-thumb">
                <img class="lazyload img-product" data-src="{{ asset('images/item/tets4.jpg') }}"
                  src="{{ asset('images/item/tets4.jpg') }}" alt="image-product">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- /Testimonial -->

@include('common.footer')




<script>
  (function() {
    const TOGGLE_URL = @json(route('wishlist.toggle'));
    const COUNT_URL = @json(route('wishlist.count'));
    const ITEMS_URL = @json(route('wishlist.items'));
    const LOGIN_URL = @json(route('login')) + '?redirect=' + encodeURIComponent(location.href);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // helper to flip icon state and force color
    function updateHeart(btn, wished) {
      if (!btn) return;
      btn.dataset.wished = wished ? '1' : '0';
      const icon = btn.querySelector('i');
      if (!icon) return;

      // Solid vs Regular
      icon.classList.toggle('fas', wished);
      icon.classList.toggle('fa-solid', wished);
      icon.classList.toggle('far', !wished);
      icon.classList.toggle('fa-regular', !wished);

      // Force lime color with !important
      icon.style.setProperty('color', '#CEFF1B', 'important');
    }

    // click to toggle (no SweetAlert)
    document.addEventListener('click', async function(e) {
      const btn = e.target.closest('.btn-wishlist');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const pid = parseInt(btn.dataset.productId || '0', 10);
      if (!pid) return;

      try {
        const res = await fetch(TOGGLE_URL, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            product_id: pid
          })
        });

        if (res.status === 403) {
          window.location.href = LOGIN_URL;
          return;
        }

        const js = await res.json().catch(() => ({}));
        if (!js || !js.ok) return;

        updateHeart(btn, !!js.wished);

        // refresh header badge
        try {
          const cRes = await fetch(COUNT_URL);
          const cJs = await cRes.json();
          const b = document.getElementById('wishlistCountBadge') ||
            document.querySelector('.nav-wishlist .count-box');
          if (b && cJs && typeof cJs.count === 'number') {
            b.textContent = cJs.count;
            b.style.transition = 'transform .15s ease';
            b.style.transform = 'scale(1.2)';
            setTimeout(() => b.style.transform = 'scale(1)', 150);
          }
        } catch (_) {}
      } catch (_) {}
    }, false);

    // HYDRATE: mark hearts solid for products already in wishlist
    document.addEventListener('DOMContentLoaded', async function() {
      try {
        const res = await fetch(ITEMS_URL, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const js = await res.json().catch(() => ({
          ok: false,
          items: []
        }));
        if (!js.ok || !Array.isArray(js.items)) return;

        const wishedSet = new Set(js.items.map(it => Number(it.product_id)));

        document.querySelectorAll('.btn-wishlist[data-product-id]').forEach(btn => {
          const pid = Number(btn.dataset.productId || 0);
          updateHeart(btn, wishedSet.has(pid)); // sets solid/regular and lime color
        });

        // Ensure header badge shows correct count
        try {
          const b = document.getElementById('wishlistCountBadge') ||
            document.querySelector('.nav-wishlist .count-box');
          if (b) b.textContent = wishedSet.size;
        } catch (_) {}
      } catch (_) {}
    });
  })();
</script>