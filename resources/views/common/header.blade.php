@livewireStyles
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ultra Hustle</title>

  <!-- CSRF for JS fetch -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Roboto:wght@300;400;500;700;800&display=swap"
    rel="stylesheet" />
  <!-- Font Awesome 6 -->
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('rebuildfrontend/css/home.css') }}">
  <link rel="shortcut icon" href="{{ asset('images/logo/favicon.png') }}" />
  <link rel="apple-touch-icon-precomposed" href="{{ asset('images/logo/favicon.png') }}" />
   @include('partials.gtm-head')
</head>

<body>
  @include('partials.gtm-body') 
  <!-- =================== HEADER =================== -->
  <header class="header">
    <div class="container nav">
      <div class="brand">
        <a href="/" aria-label="SquareUp home">
          <img class="brand-logo" src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="logo" />
        </a>
      </div>

      <!-- Desktop menu -->
      <ul class="menu" role="menubar">
        <li>
          <a href="{{ route('home') }}">Home</a>
        </li>
        <li><a href="{{ route('marketplace') }}">Marketplace</a></li>
        <li><a href="{{ route('forum') }}">Forum</a></li>
      </ul>

      <!-- Desktop search -->
      <div class="searchbar">
        <input id="topSearchInput" placeholder="Search product" />
        <button class="sbtn" aria-label="Search">
          <i class="fa-solid fa-search"></i>
        </button>
      </div>

      <!-- Right icons -->
      <div class="nav-right">

@if(auth()->check() || session('user_id'))
        <div class="icon" id="openLoginDesktop" title="Profile">
            <i class="fa-regular fa-user fa-lg"></i>
        </div>
@else
    {{-- two plain buttons side-by-side (no Bootstrap) --}}
    <div class="simple-auth-row" role="group" aria-label="Authentication">
        <a href="{{ route('login') ?? url('/login') }}" class="simple-btn" id="openLoginbtn">Login</a>
        <a href="#" class="simple-btn primary" id="openSignupfrombtn">Sign Up</a>
    </div>
@endif


@if(auth()->check() || session('user_id'))
        <div class="icon" title="Wishlist" id="openWishlistDesktop">
          <i class="fa-regular fa-heart"></i>
        </div>
@endif
      </div>

      <!-- Mobile controls -->
      <div class="only-mobile">
        <button class="hamburger" id="openSidebar" aria-label="Open menu">
          <i class="fa-solid fa-bars"></i>
        </button>
        <button class="icon" id="openSearch" aria-label="Search">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <button class="icon" aria-label="Login">
          <i class="fa-regular fa-user"></i>
        </button>
        <button class="icon" aria-label="Wishlist" id="openWishlistMobile">
          <i class="fa-regular fa-heart"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Search docking area (we move the existing header .searchbar into here on mobile) -->
  <div id="searchDock" class="search-dock" aria-hidden="true"></div>

  <!-- =================== Floating Search Drawer (all screens) =================== -->
  <div class="search-mask" id="searchMask"></div>
  <div class="search-flyout" id="searchFlyout" aria-hidden="true">
    <!-- own search bar for mobile or when focusing -->
    <div class="searchbar" style="max-width: none">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input id="flySearch" placeholder="Search product" />
      <button class="sbtn" aria-label="Search">
        <i class="fa-solid fa-search"></i>
      </button>
    </div>
    <div class="search-results" id="searchResults">
      <div class="sr-empty">Product not found.</div>
    </div>
  </div>

  <!-- =================== SIDEBAR (mobile) =================== -->
  <aside class="sidebar" id="sidebar">
    <div class="side-head">
      <div class="brand">
        <a href="/" aria-label="SquareUp home">
          <img class="brand-logo" src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="logo" />
        </a>
      </div>
      <button class="icon close" id="closeSidebar" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="side-body">
        <a class="btn" style="width: 100%; margin: 10px 0" href="{{ route('home') }}"><i class="fa-solid fa-house"></i> Home</a>

      <a class="btn" style="width: 100%; margin: 10px 0" href="{{ route('marketplace') }}"><i class="fa-solid fa-store"></i> Marketplace</a>

      <a class="btn" style="width: 100%; margin: 10px 0" href="{{ route('forum') }}"><i class="fa-solid fa-comments"></i> Forum</a>

      <div class="side-item" id="s-email" aria-expanded="false">
        <div class="side-title">
          Sign Up for Email <i class="fa-solid fa-angle-down"></i>
        </div>
        <div class="side-panel">
          <input class="input" id="sideNewsletterEmail" placeholder="Enter your email..." />
          <button class="btn btn-accent" id="sideNewsletterSubmit" style="margin-top: 10px; width: 100%">
            Subscribe <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </button>
        </div>
      </div>
    </div>
    <button class="btn btn-accent side-login" id="sidebarLoginBtn">
      <i class="fa-regular fa-user"></i> Login
    </button>
  </aside>

  <!-- ====== WISHLIST DRAWER ====== -->
  <aside class="drawer" id="wishlistDrawer" aria-hidden="true">
    <div class="drawer-head">
      <strong>Wishlist</strong>
      <button class="icon close" id="closeWishlist" style="margin-left: auto" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="drawer-body" id="wishlistBody">
      <!-- will be loaded via JS -->
    </div>
    @if(auth()->check() || session('user_id'))
  <div class="drawer-foot" style="display:flex;justify-content:flex-end;gap:8px;padding:12px;border-top:1px solid var(--border,#2a2a2a);">
    <a href="{{ route('wishlist.page') }}" class="btn btn-accent" style="margin-left:auto;">
      <i class="fa-regular fa-heart"></i> View all
    </a>
  </div>
@endif

  </aside>

  <!-- ====== SEARCH DRAWERS kept (unused now) ====== -->
  <aside class="drawer" id="searchDrawer" aria-hidden="true">
    <div class="drawer-head">
      <strong>Search</strong>
      <button class="icon close" id="closeSearchDrawer" style="margin-left: auto" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="drawer-body">
      <div class="searchbar" style="max-width: none; margin-bottom: 12px">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input placeholder="Search product" />
        <button class="sbtn" aria-label="Search">
          <i class="fa-solid fa-search"></i>
        </button>
      </div>
      <div class="wl-item">
        <div class="wl-thumb"></div>
        <div>
          <div class="wl-name">Result: Portfolio Theme</div>
          <div class="wl-meta">$39.00 • <span class="stars">★★★★☆</span></div>
        </div>
      </div>
      <div class="wl-item">
        <div class="wl-thumb"></div>
        <div>
          <div class="wl-name">Result: Dashboard UI</div>
          <div class="wl-meta">$49.00 • <span class="stars">★★★★★</span></div>
        </div>
      </div>
    </div>
  </aside>

  <aside class="top-drawer" id="searchTopDrawer" aria-hidden="true">
    <div class="drawer-head">
      <strong>Search</strong>
      <button class="icon close" id="closeSearchTop" style="margin-left: auto" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="drawer-body">
      <div class="searchbar" style="max-width: none; margin-bottom: 12px">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input placeholder="Search product" />
        <button class="sbtn" aria-label="Search">
          <i class="fa-solid fa-search"></i>
        </button>
      </div>
      <div class="wl-item">
        <div class="wl-thumb"></div>
        <div>
          <div class="wl-name">Popular: Ultra Hustle Hoodie</div>
          <div class="wl-meta">$59.00 • <span class="stars">★★★★★</span></div>
        </div>
      </div>
      <div class="wl-item">
        <div class="wl-thumb"></div>
        <div>
          <div class="wl-name">Suggested: Glow Shoes</div>
          <div class="wl-meta">$89.00 • <span class="stars">★★★★☆</span></div>
        </div>
      </div>
    </div>
  </aside>

  <script>
  (function(){
    const WL_COUNT_URL = @json(route('wishlist.count'));

    function ensureWishlistBadges(){
      const spots = [document.getElementById('openWishlistDesktop'), document.getElementById('openWishlistMobile')];
      spots.forEach((holder)=>{
        if (!holder) return;
        holder.style.position = 'relative';
        if (!holder.querySelector('.wl-badge')){
          const b = document.createElement('span');
          b.className = 'wl-badge';
          b.style.cssText = `
            position:absolute; right:-6px; top:-6px;
            min-width:18px; height:18px; padding:0 5px;
            border-radius:9px; background:#ceff1b; color:#0b0b0b;
            font:700 11px/18px Roboto, system-ui, -apple-system, Segoe UI, Arial;
            display:inline-grid; place-items:center;
          `;
          b.textContent = '0';
          holder.appendChild(b);
        }
      });
    }

    async function refreshWishlistBadge(){
      try{
        const res = await fetch(WL_COUNT_URL, { headers:{ 'Accept':'application/json' } });
        const js  = await res.json();
        const count = (js && typeof js.count === 'number') ? js.count : 0;
        ensureWishlistBadges();
        document.querySelectorAll('#openWishlistDesktop .wl-badge, #openWishlistMobile .wl-badge')
          .forEach(el => el.textContent = count);
      } catch(e) {
        // silently ignore
      }
    }
    
    @php
      $uid = auth()->id() ?? session('user_id');
      $initial = 0;
      if ($uid) {
          try { $initial = \App\Models\Wishlist::where('user_id', $uid)->count(); } catch(\Throwable $e) {}
      }
    @endphp
    const INITIAL_WL_COUNT = {{ (int)($initial ?? 0) }};
    document.addEventListener('DOMContentLoaded', () => {
      ensureWishlistBadges();
      document.querySelectorAll('#openWishlistDesktop .wl-badge, #openWishlistMobile .wl-badge')
        .forEach(el => el.textContent = INITIAL_WL_COUNT);
      // then sync with live server count
      refreshWishlistBadge();
      // Expose globally so other pages/components can call after toggles
      window.refreshWishlistBadge = refreshWishlistBadge;
    });
  })();
</script>
