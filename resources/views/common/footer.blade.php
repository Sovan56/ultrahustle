<!-- footer -->
<style>
  /* OTP boxes: clean, consistent, tappable */
  #verifyOtp .otp-box {
    width: 48px;
    height: 54px;
    border-radius: 10px;
    border: 1px solid #ddd;
    font-size: 20px;
    font-weight: 600;
    letter-spacing: 2px;
    outline: none;
  }

  #verifyOtp .otp-box:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
  }

  @media (max-width: 576px) {
    #verifyOtp .otp-box {
      width: 44px;
      height: 50px;
    }
  }

  .modal.modalCentered .modal-dialog {
    margin: auto !important;
    /* Always center in both axes */
    display: flex !important;
    align-items: center !important;
    min-height: 100vh;
    /* Full viewport height for proper centering */
  }

  @media (max-width: 767.98px) {
    .modal.modalCentered .modal-dialog {
      max-width: 95%;
      /* Keep mobile width nice */
    }
  }
</style>
<footer id="footer" class="footer md-pb-70">
  <div class="footer-wrap">
    <div class="footer-body">
      <div class="container">
        <div class="row">
          <div class="col-xl-3 col-md-6 col-12">
            <div class="footer-infor">
              <div class="footer-logo">
                <a href="index.html">
                  <img src="{{ asset('images/logo/logo.svg') }}" alt="" />
                </a>
              </div>
              <ul>
                <li>
                  <p>
                    Address: 1234 Fashion Street, Suite 567, <br />
                    New York, NY 10001
                  </p>
                </li>
                <li>
                  <p>Email: <a href="#">info@fashionshop.com</a></p>
                </li>
                <li>
                  <p>Phone: <a href="#">(212) 555-1234</a></p>
                </li>
              </ul>
              <a href="contact-1.html" class="tf-btn btn-line">Get direction<i class="icon icon-arrow1-top-left"></i></a>
              <ul class="tf-social-icon d-flex gap-10">
                <li>
                  <a
                    href="#"
                    class="box-icon w_34 round social-facebook social-line"><i class="icon fs-14 icon-fb"></i></a>
                </li>
                <li>
                  <a
                    href="#"
                    class="box-icon w_34 round social-twiter social-line"><i class="icon fs-12 icon-Icon-x"></i></a>
                </li>
                <li>
                  <a
                    href="#"
                    class="box-icon w_34 round social-instagram social-line"><i class="icon fs-14 icon-instagram"></i></a>
                </li>
                <li>
                  <a
                    href="#"
                    class="box-icon w_34 round social-tiktok social-line"><i class="icon fs-14 icon-tiktok"></i></a>
                </li>
                <li>
                  <a
                    href="#"
                    class="box-icon w_34 round social-pinterest social-line"><i class="icon fs-14 icon-pinterest-1"></i></a>
                </li>
              </ul>
            </div>
          </div>

@php
  $isLogged = auth()->check() || session('user_id');
@endphp

          <div class="col-xl-3 col-md-6 col-12 footer-col-block">
            <div class="footer-heading footer-heading-desktop">
              <h6>Help</h6>
            </div>
            <div class="footer-heading footer-heading-moblie">
              <h6>Help</h6>
            </div>
            <ul class="footer-menu-list tf-collapse-content">
              <li>
                 <a href="{{ route('legal.privacy') }}" class="footer-menu_item">Privacy Policy</a>
              </li>
              <li>
                <a href="delivery-return.html" class="footer-menu_item">
                  Returns + Exchanges
                </a>
              </li>
              <li>
                <a href="shipping-delivery.html" class="footer-menu_item">Shipping</a>
              </li>
              <li>
                <a href="{{ route('legal.terms') }}" class="footer-menu_item">Terms &amp; Conditions</a>
              </li>
              <li>
                <a href="faq-1.html" class="footer-menu_item">FAQ’s</a>
              </li>
              <li>
                <a href="compare.html" class="footer-menu_item">Compare</a>
              </li>
              <li>
                <a
      href="{{ $isLogged
        ? route('wishlist.page')
        : route('login') . '?redirect=' . urlencode(route('wishlist.page')) }}"
      class="footer-menu_item">
      My Wishlist
    </a>
              </li>
            </ul>
          </div>
          <div class="col-xl-3 col-md-6 col-12 footer-col-block">
            <div class="footer-heading footer-heading-desktop">
              <h6>About us</h6>
            </div>
            <div class="footer-heading footer-heading-moblie">
              <h6>About us</h6>
            </div>
            <ul class="footer-menu-list tf-collapse-content">
              <li>
                <a href="about-us.html" class="footer-menu_item">Our Story</a>
              </li>
              <li>
                <a href="our-store.html" class="footer-menu_item">Visit Our Store</a>
              </li>
              <li>
                <a href="contact-1.html" class="footer-menu_item">Contact Us</a>
              </li>
              <li>
                <a
      href="{{ $isLogged ? route('user.admin.profile') : route('login') }}"
      class="footer-menu_item">
      Account
    </a>
              </li>
            </ul>
          </div>


          <div class="col-xl-3 col-md-6 col-12">
            <div class="footer-newsletter footer-col-block">
              <div class="footer-heading footer-heading-desktop">
                <h6>Sign Up for Email</h6>
              </div>
              <div class="footer-heading footer-heading-moblie">
                <h6>Sign Up for Email</h6>
              </div>
              <div class="tf-collapse-content">
                <div class="footer-menu_item">
                  Sign up to get first dibs on new arrivals, sales,
                  exclusive content, events and more!
                </div>

                <form
                  id="footer-newsletter-form"
                  class="js-newsletter-form" {{-- <-- not "form-newsletter" --}}
                  data-no-ajax="1" {{-- <-- we’ll use this to block theme listeners --}}
                  action="{{ route('newsletter.subscribe') }}"
                  method="POST"
                  accept-charset="utf-8">
                  @csrf
                  <div id="subscribe-content">
                    <fieldset class="email">
                      <input
                        type="email"
                        name="email"
                        id="subscribe-email"
                        placeholder="Enter your email...."
                        tabindex="0"
                        required />
                    </fieldset>
                    <div class="button-submit">
                      <button
                        id="footer-newsletter-submit" {{-- <-- not "subscribe-button" --}}
                        class="tf-btn btn-sm btn-fill btn-icon animate-hover-btn"
                        type="submit">
                        Subscribe<i class="icon icon-arrow1-top-left"></i>
                      </button>
                    </div>
                  </div>

                  @error('email')
                  <div class="alert alert-danger mt-2">{{ $message }}</div>
                  @enderror
                </form>
                <script>
                  (function() {
                    // 1) Capture-phase blockers for any theme handlers bound on document/window
                    window.addEventListener('submit', function(e) {
                      const form = e.target;
                      if (form && form.matches && form.matches('#footer-newsletter-form[data-no-ajax="1"]')) {
                        e.stopImmediatePropagation(); // kill theme handler chain
                        // do NOT preventDefault so the browser does a normal POST
                      }
                    }, true); // <-- capture

                    window.addEventListener('click', function(e) {
                      const btn = e.target.closest('#footer-newsletter-submit');
                      if (btn && btn.form && btn.form.matches('#footer-newsletter-form[data-no-ajax="1"]')) {
                        e.stopImmediatePropagation(); // kill theme click handlers that show 'Connection error'
                        // allow default click so form submits normally
                      }
                    }, true); // <-- capture

                    // 2) Best-effort unbind common jQuery hooks used by themes
                    if (window.jQuery) {
                      const $ = window.jQuery;
                      $(document).off('submit', '.form-newsletter'); // old class hook
                      $(document).off('click', '#subscribe-button'); // old id hook
                      $('#footer-newsletter-form').off('submit'); // local
                      $('#footer-newsletter-submit').off('click'); // local
                    }
                  })();
                </script>


              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div
              class="footer-bottom-wrap d-flex gap-20 flex-wrap justify-content-between align-items-center">
              <div class="footer-menu_item">
                © 2025 Ecomus Store. All Rights Reserved
              </div>
              <div class="tf-payment">
                <img src="{{ asset('images/payments/visa.png') }}" alt="" />
                <img src="{{ asset('images/payments/img-1.png') }}" alt="" />
                <img src="{{ asset('images/payments/img-2.png') }}" alt="" />
                <img src="{{ asset('images/payments/img-3.png') }}" alt="" />
                <img src="{{ asset('images/payments/img-4.png') }}" alt="" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>
<!-- /footer -->
</div>

<!-- gotop -->
<button id="goTop">
  <span class="border-progress"></span>
  <span class="icon icon-arrow-up"></span>
</button>
<!-- /gotop -->


<!-- mobile menu -->
<div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu">
  <span
    class="icon-close icon-close-popup"
    data-bs-dismiss="offcanvas"
    aria-label="Close"></span>
  <div class="mb-canvas-content">
    <div class="mb-body">
      <ul class="nav-ul-mb" id="wrapper-menu-navigation">
        <li class="nav-mb-item">
          <a
            href="{{ route('home') }}">
            <span>Home</span>
            <span class="btn-open-sub"></span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a
            href="{{ route('marketplace') }}">
            <span>Marketplace</span>
            <span class="btn-open-sub"></span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a
            href="#">
            <span>Forum</span>
            <span class="btn-open-sub"></span>
          </a>
        </li>
        <!-- <li class="nav-mb-item">
              <a
                href="#dropdown-menu-five"
              >
                <span>Blog</span>
                <span class="btn-open-sub"></span>
              </a>
            </li> -->
      </ul>
      <div class="mb-other-content mt-5">
        <div class="mb-notice">
          <a href="contact-1.html" class="text-need">Need help ?</a>
        </div>
        <ul class="mb-info">
          <li>
            Address: 1234 Fashion Street, Suite 567, <br />
            New York, NY 10001
          </li>
          <li>Email: <b>info@fashionshop.com</b></li>
          <li>Phone: <b>(212) 555-1234</b></li>
        </ul>
      </div>
    </div>
    <div class="mb-bottom">
      @guest
      <a href="#" class="site-nav-icon js-open-login">
        <i class="icon icon-account"></i>Login
      </a>
      @else
      <form method="POST" action="{{ route('auth.logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="site-nav-icon btn btn-link p-0">
          <i class="icon icon-account"></i>Logout
        </button>
      </form>
      @endguest
    </div>

  </div>
</div>
<!-- /mobile menu -->

<!-- canvasSearch -->
<div class="offcanvas offcanvas-end canvas-search" id="canvasSearch">
  <div class="canvas-wrapper">
    <header class="tf-search-head">
      <div class="title fw-5">
        Search our site
        <div class="close">
          <span
            class="icon-close icon-close-popup"
            data-bs-dismiss="offcanvas"
            aria-label="Close"></span>
        </div>
      </div>
      <div class="tf-search-sticky">
        <form class="tf-mini-search-frm">
          <fieldset class="text">
            <input
              type="text"
              placeholder="Search"
              class=""
              name="text"
              tabindex="0"
              value=""
              aria-required="true"
              required="" />
          </fieldset>
          <button class="" type="submit">
            <i class="icon-search"></i>
          </button>
        </form>
      </div>
    </header>
  </div>
</div>
<!-- /canvasSearch -->

<script>
(function(){
  const SUGGEST_URL = @json(route('search.suggest'));

  // ---------- helpers ----------
  const debounce = (fn, ms=250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
  const esc = (s) => (s??'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));
  const priceText = (p) => (p && p !== 'N/A') ? p : 'Price N/A';

  function section(title){ return `<li class="section-label">${esc(title)}</li>`; }
  function divider(){ return `<li class="section-divider"></li>`; }

  function itemHTML(c){
    const badge = c.is_boosted ? `<span class="popular-badge">Popular</span>` : '';
    const rating = (Number(c.rating) || 0).toFixed(1);
    const reviews = Number(c.reviews) || 0;
    return `
      <li>
        <a class="search-result-item" href="${c.url}">
          <div class="img-box">
            <img src="${c.cover}" alt="">
          </div>
          <div class="box-content">
            <p class="title link">${esc(c.name)} ${badge}</p>
            <div class="rating">
              <span class="star">★</span> ${rating} (${reviews})
            </div>
            <div class="price">${esc(priceText(c.price))}</div>
          </div>
        </a>
      </li>`;
  }

 function renderResults(rootInnerEl, payload, opts){
  if (!rootInnerEl) return;
  const q = (opts?.query || '').trim();

  const boosted = Array.isArray(payload?.boosted) ? payload.boosted : [];
  const items   = Array.isArray(payload?.items)   ? payload.items   : [];

  let html = '<ul>';

  if (q) {
    // 🔎 User is typing: show ONLY matches; no boosted section
    if (items.length) {
      html += items.map(itemHTML).join('');
    } else {
      html += `<li class="empty-row">Product not found.</li>`;
    }
  } else {
    // 🏷️ Empty query: show boosted (and recent/all results if you return them)
    if (boosted.length) {
      html += section('Popular now');
      html += boosted.map(itemHTML).join('');
    }
    if (items.length) {
      if (boosted.length) html += divider();
      html += section('Results');
      html += items.map(itemHTML).join('');
    }
    if (!boosted.length && !items.length) {
      html += `<li class="empty-row">Product not found.</li>`;
    }
  }

  html += '</ul>';
  rootInnerEl.innerHTML = html;

  openContainer(opts.wrapperEl, rootInnerEl);

  if (opts.fromCanvas) {
    rootInnerEl.querySelectorAll('a.search-result-item').forEach(a=>{
      a.addEventListener('click', () => {
        try {
          const oc = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('canvasSearch'));
          oc.hide();
        } catch(_){}
      });
    });
  }
}

  async function fetchSuggest(q){
    const url = new URL(SUGGEST_URL, window.location.origin);
    if (q) url.searchParams.set('q', q);
    const res = await fetch(url, { headers: { 'Accept':'application/json' }});
    return await res.json();
  }

  function openContainer(wrapperEl, innerEl){
    // Make sure parent wrapper stays open (overrides hover-only CSS)
    if (wrapperEl) wrapperEl.classList.add('is-open');
    const container = innerEl.closest('.search-suggests-results');
    if (container) {
      container.style.display   = 'block';
      container.style.opacity   = '1';
      container.style.visibility= 'visible';
    }
  }
  function closeContainer(wrapperEl, innerEl){
    if (wrapperEl) wrapperEl.classList.remove('is-open');
    const container = innerEl.closest('.search-suggests-results');
    if (container) {
      container.style.display   = 'none';
      container.style.opacity   = '';
      container.style.visibility= '';
    }
  }

  function wireSearch({inputEl, innerResultsEl, wrapperEl, fromCanvas=false}){
    if (!inputEl || !innerResultsEl) return;

    // show boosted on focus or when field is empty and user clicks in
    const showBoosted = async () => {
  const q = (inputEl.value || '').trim();
  try {
    const payload = await fetchSuggest(q ? q : '');
    renderResults(innerResultsEl, payload, {wrapperEl, fromCanvas, query: q});
  } catch(_){}
};

    // OPEN on focus immediately (and load boosted if empty)
    inputEl.addEventListener('focus', showBoosted);

    // Fetch on keyup / input (debounced)
    const doQuery = debounce(async () => {
  const q = (inputEl.value || '').trim();
  try {
    const payload = await fetchSuggest(q);
    renderResults(innerResultsEl, payload, {wrapperEl, fromCanvas, query: q});
  } catch(_){}
}, 220);

    inputEl.addEventListener('input', doQuery);
    inputEl.addEventListener('keyup', doQuery);

    // Click-outside to close
    document.addEventListener('click', (e) => {
      const inBox   = inputEl.contains(e.target);
      const inPanel = innerResultsEl.closest('.search-suggests-results')?.contains(e.target);
      if (!inBox && !inPanel) closeContainer(wrapperEl, innerResultsEl);
    });

    // Prevent form submit navigation
    const form = inputEl.closest('form');
    if (form) form.addEventListener('submit', e => e.preventDefault());
  }

  document.addEventListener('DOMContentLoaded', () => {
    // ----- Header mini-search -----
    const headWrapper = document.querySelector('.tf-form-search'); // we toggle .is-open here
    const headInput   = document.querySelector('.tf-form-search .search-box input');
    const headInner   = document.querySelector('.tf-form-search .search-suggests-results .search-suggests-results-inner');
    if (headInput && headInner) {
      wireSearch({inputEl: headInput, innerResultsEl: headInner, wrapperEl: headWrapper, fromCanvas:false});
    }

    // ----- Offcanvas search -----
    const stickyWrap  = document.querySelector('#canvasSearch .tf-search-sticky'); // we toggle .is-open here
    const canvasInput = document.querySelector('#canvasSearch .tf-mini-search-frm input[name="text"]');

    // create a results container inside the canvas if it doesn't exist yet
    let canvasInner = document.querySelector('#canvasSearch .search-suggests-results .search-suggests-results-inner');
    if (!canvasInner) {
      const wrap = document.createElement('div');
      wrap.className = 'search-suggests-results';
      wrap.style.display = 'none';
      wrap.innerHTML = `<div class="search-suggests-results-inner"></div>`;
      const sticky = document.querySelector('#canvasSearch .tf-search-sticky');
      if (sticky) sticky.appendChild(wrap);
      canvasInner = wrap.querySelector('.search-suggests-results-inner');
    }
    if (canvasInput && canvasInner) {
      wireSearch({inputEl: canvasInput, innerResultsEl: canvasInner, wrapperEl: stickyWrap, fromCanvas:true});
    }
  });
})();
</script>

<script>
(function(){
  const wrap  = document.querySelector('.tf-form-search');
  if (!wrap) return;

  const input = wrap.querySelector('input[type="text"]');
  const panel = wrap.querySelector('.search-suggests-results');

  // 1) Open when focusing/typing; never rely on :hover
  function openPanel(){ panel.classList.add('open'); }
  function closePanel(){ panel.classList.remove('open'); }

  input.addEventListener('focus', openPanel);
  input.addEventListener('input', openPanel);    // keeps it open while typing

  // 2) Don’t close on blur if we’re clicking inside the panel
  let clickingPanel = false;
  panel.addEventListener('pointerdown', () => { clickingPanel = true; });
  input.addEventListener('blur', () => {
    // If blur was caused by clicking the panel, wait for the click to finish
    if (clickingPanel) {
      setTimeout(() => { clickingPanel = false; }, 0);
      return;
    }
    closePanel();
  });

  // 3) Close on outside click or Escape
  document.addEventListener('pointerdown', (e) => {
    if (!wrap.contains(e.target)) closePanel();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePanel();
  });

  // 4) Ensure clicking a suggestion always navigates
  panel.addEventListener('click', (e) => {
    const a = e.target.closest('a.search-result-item');
    if (a) {
      // If your items are anchors, this is enough. If some rows are divs, force redirect:
      window.location.href = a.href;
    }
  });

  panel.addEventListener('mouseenter', openPanel);
})();
</script>

<!-- toolbarShopmb -->
<div
  class="offcanvas offcanvas-start canvas-mb toolbar-shop-mobile"
  id="toolbarShopmb">
  <span
    class="icon-close icon-close-popup"
    data-bs-dismiss="offcanvas"
    aria-label="Close"></span>
  <div class="mb-canvas-content">
    <div class="mb-body">
      <ul class="nav-ul-mb" id="wrapper-menu-navigation">
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate1.jpg') }}" alt="" />
            </div>
            <span>Accessories</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate2.jpg') }}" alt="" />
            </div>
            <span>Dog</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate3.jpg') }}" alt="" />
            </div>
            <span>Grocery</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate4.png') }}" alt="" />
            </div>
            <span>Handbag</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a
            href="#cate-menu-one"
            class="tf-category-link has-children collapsed mb-menu-link"
            data-bs-toggle="collapse"
            aria-expanded="true"
            aria-controls="cate-menu-one">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate5.jpg') }}" alt="" />
            </div>
            <span>Fashion</span>
            <span class="btn-open-sub"></span>
          </a>
          <div id="cate-menu-one" class="collapse list-cate">
            <ul class="sub-nav-menu" id="cate-menu-navigation">
              <li>
                <a
                  href="#cate-shop-one"
                  class="tf-category-link has-children sub-nav-link collapsed"
                  data-bs-toggle="collapse"
                  aria-expanded="true"
                  aria-controls="cate-shop-one">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate6.jpg') }}" alt="" />
                  </div>
                  <span>Mens</span>
                  <span class="btn-open-sub"></span>
                </a>
                <div id="cate-shop-one" class="collapse">
                  <ul class="sub-nav-menu sub-menu-level-2">
                    <li>
                      <a
                        href="shop-default.html"
                        class="tf-category-link sub-nav-link">
                        <div class="image">
                          <img src="{{ asset('images/shop/cate/cate1.jpg') }}" alt="" />
                        </div>
                        <span>Accessories</span>
                      </a>
                    </li>
                    <li>
                      <a
                        href="shop-default.html"
                        class="tf-category-link sub-nav-link">
                        <div class="image">
                          <img src="{{ asset('images/shop/cate/cate8.jpg') }}" alt="" />
                        </div>
                        <span>Shoes</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li>
                <a
                  href="#cate-shop-two"
                  class="tf-category-link has-children sub-nav-link collapsed"
                  data-bs-toggle="collapse"
                  aria-expanded="true"
                  aria-controls="cate-shop-two">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate9.jpg') }}" alt="" />
                  </div>
                  <span>Womens</span>
                  <span class="btn-open-sub"></span>
                </a>
                <div id="cate-shop-two" class="collapse">
                  <ul class="sub-nav-menu sub-menu-level-2">
                    <li>
                      <a
                        href="shop-default.html"
                        class="tf-category-link sub-nav-link">
                        <div class="image">
                          <img src="{{ asset('images/shop/cate/cate4.png') }}" alt="" />
                        </div>
                        <span>Handbag</span>
                      </a>
                    </li>
                    <li>
                      <a
                        href="shop-default.html"
                        class="tf-category-link sub-nav-link">
                        <div class="image">
                          <img src="{{ asset('images/shop/cate/cate7.jpg') }}" alt="" />
                        </div>
                        <span>Tee</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-mb-item">
          <a
            href="#cate-menu-two"
            class="tf-category-link has-children collapsed mb-menu-link"
            data-bs-toggle="collapse"
            aria-expanded="true"
            aria-controls="cate-menu-two">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate6.jpg') }}" alt="" />
            </div>
            <span>Men</span>
            <span class="btn-open-sub"></span>
          </a>
          <div id="cate-menu-two" class="collapse list-cate">
            <ul class="sub-nav-menu" id="cate-menu-navigation1">
              <li>
                <a
                  href="shop-default.html"
                  class="tf-category-link sub-nav-link">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate1.jpg') }}" alt="" />
                  </div>
                  <span>Accessories</span>
                </a>
              </li>
              <li>
                <a
                  href="shop-default.html"
                  class="tf-category-link sub-nav-link">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate8.jpg') }}" alt="" />
                  </div>
                  <span>Shoes</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate7.jpg') }}" alt="" />
            </div>
            <span>Tee</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a href="shop-default.html" class="tf-category-link mb-menu-link">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate8.jpg') }}" alt="" />
            </div>
            <span>Shoes</span>
          </a>
        </li>
        <li class="nav-mb-item">
          <a
            href="#cate-menu-three"
            class="tf-category-link has-children collapsed mb-menu-link"
            data-bs-toggle="collapse"
            aria-expanded="true"
            aria-controls="cate-menu-three">
            <div class="image">
              <img src="{{ asset('images/shop/cate/cate9.jpg') }}" alt="" />
            </div>
            <span>Women</span>
            <span class="btn-open-sub"></span>
          </a>
          <div id="cate-menu-three" class="collapse list-cate">
            <ul class="sub-nav-menu" id="cate-menu-navigation2">
              <li>
                <a
                  href="shop-default.html"
                  class="tf-category-link sub-nav-link">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate4.png') }}" alt="" />
                  </div>
                  <span>Handbag</span>
                </a>
              </li>
              <li>
                <a
                  href="shop-default.html"
                  class="tf-category-link sub-nav-link">
                  <div class="image">
                    <img src="{{ asset('images/shop/cate/cate7.jpg') }}" alt="" />
                  </div>
                  <span>Tee</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
      </ul>
    </div>
    <div class="mb-bottom">
      <a href="shop-default.html" class="tf-btn fw-5 btn-line">View all collection<i class="icon icon-arrow1-top-left"></i></a>
    </div>
  </div>
</div>
<!-- /toolbarShopmb -->





















{{-- LOGIN MODAL --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="login" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Log in</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal" role="button" aria-label="Close"></span>
      </div>
      <div class="tf-login-form">
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}">
          @csrf
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email') }}" required />
            <label class="tf-field-label">Email *</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" required />
            <label class="tf-field-label">Password *</label>
          </div>

          @error('email')
          <div class="text-danger small mb-2">{{ $message }}</div>
          @enderror

          <div>
            <a href="#forgotPassword" data-bs-toggle="modal" data-bs-target="#forgotPassword" class="btn-link link">Forgot your password?</a>
          </div>
          <div class="mt-2">
            <a href="#loginRecovery" data-bs-toggle="modal" data-bs-target="#loginRecovery" class="btn-link link">Use a recovery code</a>
          </div>

          <div class="bottom">
            <div class="w-100">
              <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                <span>Log in</span>
              </button>
            </div>
            <div class="w-100">
              <a href="#register" data-bs-toggle="modal" data-bs-target="#register" class="btn-link fw-6 w-100 link">
                New customer? Create your account <i class="icon icon-arrow1-top-left"></i>
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


{{-- LOGIN WITH RECOVERY CODE --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="loginRecovery" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="header">
        <div class="demo-title">Login with recovery code</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal" role="button" aria-label="Close"></span>
      </div>

      <div class="tf-login-form">
        @if (session('success') && session('openModal')==='loginRecovery')
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('auth.login.recovery') }}">
          @csrf
          <p class="mb-3 small">
            Use one of your 2FA recovery codes to sign in. Each code can be used <strong>once</strong>.
          </p>

          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email') }}" required />
            <label class="tf-field-label">Email *</label>
          </div>

          <div class="tf-field style-1">
            <input class="tf-field-input tf-input recovery-input" placeholder=" " type="text" name="recovery_code" value="{{ old('recovery_code') }}" required />
            <label class="tf-field-label">Recovery code *</label>
          </div>

          @if ($errors->any() && session('openModal')==='loginRecovery')
            <div class="alert alert-danger mb-2">{{ $errors->first() }}</div>
          @endif

          <div class="bottom d-flex flex-column gap-2">
            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
              <span>Login</span>
            </button>
            <a href="#login" data-bs-toggle="modal" data-bs-target="#login" class="btn-link fw-6 w-100 link text-center">
              Back to normal login
            </a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>



{{-- FORGOT PASSWORD (SEND CODE) --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="forgotPassword" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Reset your password</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal" role="button" aria-label="Close"></span>
      </div>
      <div class="tf-login-form">
        <form method="POST" action="{{ route('password.forgot.send') }}">
          @csrf
          <p class="mb-3">Enter your email address and we’ll send you a 6-digit code to reset your password.</p>

          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email"
              value="{{ old('email', session('emailForReset')) }}" required />
            <label class="tf-field-label">Email *</label>
          </div>
          @error('email')
          <div class="text-danger small mb-2">{{ $message }}</div>
          @enderror

          <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="#login" data-bs-toggle="modal" data-bs-target="#login" class="btn-link link">Cancel</a>
          </div>

          <div class="bottom">
            <div class="w-100">
              <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                <span>Send code</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


{{-- REGISTER MODAL --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="register" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Register</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
      </div>

      <div class="tf-login-form">
        {{-- Flash / validation --}}
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
        <div class="alert alert-danger mb-2">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('auth.register') }}">
          @csrf
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="text" name="first_name" value="{{ old('first_name') }}" required>
            <label class="tf-field-label">First name</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="text" name="last_name" value="{{ old('last_name') }}" required>
            <label class="tf-field-label">Last name</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="text" name="phone_number" value="{{ old('phone_number') }}">
            <label class="tf-field-label">Phone number</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email') }}" required>
            <label class="tf-field-label">Email *</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" required>
            <label class="tf-field-label">Password *</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" required>
            <label class="tf-field-label">Confirm Password *</label>
          </div>

          <div class="bottom">
            {{-- Terms & Privacy consent --}}
            <div class="tf-field style-1 d-flex align-items-start mb-3">
              <input id="agree" name="agree" type="checkbox" class="m-2" required>
              <label for="agree" class="tf-field-label" style="margin-top: 3px;position:static;transform:none;opacity:1;">
                I accept the
                <a href="{{ route('legal.terms') }}" target="_blank" class="link">Terms &amp; Conditions</a>
                and
                <a href="{{ route('legal.privacy') }}" target="_blank" class="link">Privacy Policy</a>.
              </label>
            </div>
            @error('agree')
            <div class="alert alert-danger mb-2">{{ $message }}</div>
            @enderror

            <div class="w-100">
              <button id="regSubmit" type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center" disabled>
                <span>Register</span>
              </button>
            </div>
            <div class="w-100 mt-2">
              <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">
                Already have an account? Log in here
                <i class="icon icon-arrow1-top-left"></i>
              </a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<script>
  (function() {
    const agree = document.getElementById('agree');
    const submit = document.getElementById('regSubmit');

    function sync() {
      submit.disabled = !agree.checked;
    }
    if (agree && submit) {
      agree.addEventListener('change', sync);
      sync();
    }
  })();
</script>


{{-- VERIFY OTP (NO NESTED FORMS) --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="verifyOtp" tabindex="-1" aria-hidden="true" aria-labelledby="verifyOtpLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="header d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill bg-primary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="fas fa-lock"></i>
          </span>
          <div class="demo-title h5 mb-0" id="verifyOtpLabel">Enter verification code</div>
        </div>
        <button type="button" class="icon-close icon-close-popup btn btn-link p-0" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="tf-login-form px-3 pb-3 pt-2">

        {{-- MAIN VERIFY FORM --}}
        <form method="POST" action="{{ route('password.otp.verify') }}" id="otpVerifyForm" novalidate>
          @csrf
          <input type="hidden" name="email" value="{{ session('emailForReset') }}">
          <input type="hidden" name="code" id="otpHidden">

          <p class="mb-2 small">
            We sent a 6-digit code to
            <strong>
              @php
              $__em = session('emailForReset');
              if ($__em) {
              $parts = explode('@', $__em);
              $left = $parts[0] ?? '';
              $masked = strlen($left) > 2 ? substr($left,0,2) . str_repeat('•', max(0, strlen($left)-2)) : $left;
              echo e($masked . '@' . ($parts[1] ?? ''));
              }
              @endphp
            </strong>. Enter it below.
          </p>

          {{-- OTP inputs --}}
          <div class="otp-grid d-flex justify-content-center gap-2 mb-2">
            <input aria-label="Digit 1" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
            <input aria-label="Digit 2" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
            <input aria-label="Digit 3" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
            <input aria-label="Digit 4" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
            <input aria-label="Digit 5" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
            <input aria-label="Digit 6" inputmode="numeric" maxlength="1" class="otp-box tf-field-input tf-input text-center" />
          </div>

          {{-- error slot for code --}}
          @error('code')
          <div class="text-danger small mb-2 text-center">{{ $message }}</div>
          @enderror

          {{-- helper row: timer + change email --}}
          <div class="d-flex align-items-center justify-content-between mt-2 mb-3">
            <div class="small">
              <span id="otpTimer">01:00</span> to request a new code
            </div>
            <a href="#forgotPassword" data-bs-toggle="modal" data-bs-target="#forgotPassword" class="btn-link link small">Change email</a>
          </div>

          <div class="bottom">
            <div class="w-100">
              <button id="otpSubmitBtn" type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center" disabled>
                <span>Verify</span>
              </button>
            </div>
          </div>
        </form>

        {{-- RESEND FORM (separate) --}}
        <form method="POST" action="{{ route('password.forgot.send') }}" class="text-center mt-3">
          @csrf
          <input type="hidden" name="email" value="{{ session('emailForReset') }}">
          <button id="resendBtn" type="submit" class="btn btn-link p-0" disabled>Resend code</button>
        </form>

      </div>
    </div>
  </div>
</div>

{{-- NEWSLETTER MODAL --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="newsletter" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Newsletter </div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal" role="button" aria-label="Close"></span>
      </div>

      <div class="tf-login-form">
        <p class="mb-3">Get product updates, deals, and tips. Enter your email to subscribe.</p>

        {{-- Flash --}}
        @if (session('newsletter_error'))
        <div class="alert alert-danger">{{ session('newsletter_error') }}</div>
        @endif

        <form method="POST" action="{{ route('newsletter.subscribe') }}">
          @csrf
          <div class="tf-field style-1">
            <input
              class="tf-field-input tf-input"
              placeholder=" "
              type="email"
              name="email"
              value="{{ old('email', session('prefill_email')) }}"
              required />
            <label class="tf-field-label">Email *</label>
          </div>
          @error('email')
          <div class="text-danger small mb-2">{{ $message }}</div>
          @enderror

          <div class="bottom d-flex flex-column gap-2">
            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
              <span>Subscribe</span>
            </button>

            {{-- Skip -> open Login modal --}}
            <button type="button" id="nl-skip" class="tf-btn btn-line radius-3 w-100 justify-content-center">
              Skip and log in <i class="icon icon-arrow1-top-left"></i>
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>


{{-- RESET PASSWORD --}}
<div class="modal modalCentered fade form-sign-in modal-part-content" id="resetPassword" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Create a new password</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal" role="button" aria-label="Close"></span>
      </div>
      <div class="tf-login-form">
        <form method="POST" action="{{ route('password.reset') }}">
          @csrf
          <input type="hidden" name="email" value="{{ session('emailForReset') }}">
          <input type="hidden" name="code" value="{{ session('otpForReset') }}">

          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" required />
            <label class="tf-field-label">New password *</label>
          </div>
          <div class="tf-field style-1">
            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" required />
            <label class="tf-field-label">Confirm password *</label>
          </div>

          @if ($errors->any() && session('openModal')==='resetPassword')
          <div class="text-danger small mb-2">{{ $errors->first() }}</div>
          @endif

          <div class="bottom">
            <div class="w-100">
              <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                <span>Update password</span>
              </button>
            </div>
            <div class="w-100 text-center mt-2">
              <a href="#login" data-bs-toggle="modal" data-bs-target="#login" class="btn-link fw-6 link">Back to login</a>
            </div>
          </div>
        </form>

        @if (session('success') && session('openModal')==='resetPassword')
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- modal login -->

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

    // ========= Open specific modal from server (validation/flash) =========
    const modalToOpen = @json(
      session('openModal') ??
      (session('showVerifyOtp') ? 'verifyOtp' : null) ??
      (session('showResetPassword') ? 'resetPassword' : null)
    );
    if (modalToOpen) {
      const el = document.getElementById(modalToOpen);
      if (el) bootstrap.Modal.getOrCreateInstance(el).show();
    }

    (function() {
      const skipBtn = document.getElementById('nl-skip');
      const nlModalEl = document.getElementById('newsletter');
      const loginEl = document.getElementById('login');
      if (!skipBtn || !nlModalEl || !loginEl) return;

      const nlModal = bootstrap.Modal.getOrCreateInstance(nlModalEl);
      const loginModal = bootstrap.Modal.getOrCreateInstance(loginEl);

      skipBtn.addEventListener('click', function() {
        nlModal.hide();
        setTimeout(() => loginModal.show(), 250);
      });
    })();

    // ========= Off-canvas: "Login" link closes drawer then opens modal =====
    document.addEventListener('click', function(e) {
      const trigger = e.target.closest('.js-open-login'); // add this class to any Login link
      if (!trigger) return;
      e.preventDefault();

      const ocEl = document.getElementById('mobileMenu');
      const loginEl = document.getElementById('login');
      if (!loginEl) return;

      if (ocEl) {
        const oc = bootstrap.Offcanvas.getOrCreateInstance(ocEl);
        oc.hide();
        setTimeout(() => bootstrap.Modal.getOrCreateInstance(loginEl).show(), 250);
      } else {
        bootstrap.Modal.getOrCreateInstance(loginEl).show();
      }
    });

    // ========= OTP Enhancements (your existing behavior, kept intact) =====
    (function setupOtp() {
      const otpModal = document.getElementById('verifyOtp');
      if (!otpModal) return;

      const inputs = Array.from(otpModal.querySelectorAll('.otp, .otp-box'));
      const hidden = document.getElementById('otpHidden');
      const submitBtn = document.getElementById('otpSubmitBtn'); // optional
      const resendBtn = document.getElementById('resendBtn'); // optional
      const timerEl = document.getElementById('otpTimer'); // optional

      if (!inputs.length || !hidden) return;

      const getCode = () => inputs.map(i => (i.value || '').replace(/\D/g, '')).join('').slice(0, 6);
      const updateSubmitState = () => {
        const full = getCode();
        hidden.value = full;
        if (submitBtn) submitBtn.disabled = (full.length !== 6);
      };

      inputs.forEach((inp, idx) => {
        inp.addEventListener('input', () => {
          inp.value = inp.value.replace(/\D/g, '').slice(0, 1);
          if (inp.value && idx < inputs.length - 1) inputs[idx + 1].focus();
          updateSubmitState();
        });
        inp.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && !inp.value && idx > 0) inputs[idx - 1].focus();
        });
      });

      // Paste full code anywhere in the modal
      otpModal.addEventListener('paste', (e) => {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const clean = (text || '').replace(/\D/g, '').slice(0, 6);
        if (!clean) return;
        e.preventDefault();
        inputs.forEach((i, n) => i.value = clean[n] || '');
        updateSubmitState();
        const last = Math.min(clean.length, inputs.length) - 1;
        if (last >= 0) inputs[last].focus();
      });

      // Focus first box on open
      otpModal.addEventListener('shown.bs.modal', () => {
        inputs[0] && inputs[0].focus();
      });

      // Optional resend cooldown UI
      if (resendBtn && timerEl) {
        let seconds = 60;
        const renderTime = () => {
          const m = String(Math.floor(seconds / 60)).padStart(2, '0');
          const s = String(seconds % 60).padStart(2, '0');
          timerEl.textContent = `${m}:${s}`;
        };
        resendBtn.disabled = true;
        renderTime();
        const tick = setInterval(() => {
          seconds--;
          renderTime();
          if (seconds <= 0) {
            clearInterval(tick);
            resendBtn.disabled = false;
            timerEl.textContent = '00:00';
          }
        }, 1000);
      }

      // Prefill on error (server may send old('code') or session('otpPrefill'))
      const prefill = @json(old('code') ?? session('otpPrefill'));
      if (typeof prefill === 'string' && prefill.length) {
        const clean = prefill.replace(/\D/g, '').slice(0, 6);
        inputs.forEach((i, n) => i.value = clean[n] || '');
      }
      updateSubmitState();
    })();
  });
</script>

<!-- /modal login -->


{{-- WISHLIST MODAL --}}
<div class="modal fullRight fade modal-shopping-cart" id="wishlistModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="header">
        <div class="title fw-5">My Wishlist</div>
        <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
      </div>

      <div class="wrap">
        <div class="tf-mini-cart-wrap">
          <div class="tf-mini-cart-main">
            <div class="tf-mini-cart-sroll">
              <div id="wishlistItems" class="tf-mini-cart-items">
                <div class="text-center text-white py-4">Loading…</div>
              </div>
            </div>
          </div>

          <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <a href="{{ route('wishlist.page') }}" class="tf-btn fw-6 btn-fill radius-3">
              <span>Open Wishlist</span>
            </a>
            <button class="tf-mini-cart-tool-primary text-center fw-6 tf-mini-cart-tool-close btn btn-light"
                    data-bs-dismiss="modal">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Wishlist modal + header badge JS --}}
<script>
(function(){
  async function refreshWishlistCount(){
    try{
      const res = await fetch(@json(route('wishlist.count')));
      const js  = await res.json();
      const n   = (js && typeof js.count === 'number') ? js.count : 0;
      const el  = document.getElementById('wishlistCountBadge');
      if (el) el.textContent = n;
    }catch(_){}
  }

  async function loadWishlistItems(){
    const box = document.getElementById('wishlistItems');
    if (!box) return;
    box.innerHTML = '<div class="text-center text-white py-4">Loading…</div>';

    try{
      const res = await fetch(@json(route('wishlist.items')));
      const js  = await res.json();

      if (!js.ok) {
        box.innerHTML = `
          <div class="text-center py-5">
            <div class="mb-2"><i class="icon icon-heart"></i></div>
            <div class="mb-3">Please log in to see your wishlist.</div>
            <a class="tf-btn fw-6 btn-fill radius-3" href="{{ route('login') }}">Log in</a>
          </div>`;
        return;
      }

      const items = js.items || [];
      if (!items.length){
        box.innerHTML = `
          <div class="text-center text-white py-5">
            <i class="icon icon-heart-o" style="font-size:28px;"></i>
            <div class="mt-2">Your wishlist is empty.</div>
          </div>`;
        return;
      }

      let html = '';
      for (const it of items){
        const priceHtml = (it.price_from != null)
          ? `<div class="price fw-6">${it.symbol}${Number(it.price_from).toFixed(2)}</div>`
          : `<div class="price text-white">Price NA</div>`;

        html += `
          <div class="tf-mini-cart-item">
            <div class="tf-mini-cart-image">
              <a href="${it.url}">
                <img src="${it.image}" alt="" onerror="this.src='https://placehold.co/120x90?text=Img'"/>
              </a>
            </div>
            <div class="tf-mini-cart-info">
              <a class="title link" href="${it.url}">${escapeHtml(it.name || 'Product')}</a>
              <div class="meta-variant small">
                <span class="text-warning"><i class="fa fa-star"></i></span>
                <span>${escapeHtml(it.rating || '0.0')} (${Number(it.reviews || 0)})</span>
              </div>
              ${priceHtml}
              <div class="tf-mini-cart-btns">
                <div class="tf-mini-cart-remove" onclick="toggleWishlistModal(${it.product_id})">Remove</div>
              </div>
            </div>
          </div>`;
      }
      box.innerHTML = html;
    }catch(e){
      box.innerHTML = `<div class="text-center text-danger py-4">Login to make wishlist.</div>`;
    }
  }

  window.toggleWishlistModal = async function(productId){
    try{
      await fetch(@json(route('wishlist.toggle')), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Content-Type': 'application/json'},
        body: JSON.stringify({ product_id: productId })
      });
    }catch(_){}
    // refresh both list and count
    loadWishlistItems();
    refreshWishlistCount();
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  // open → load
  document.addEventListener('shown.bs.modal', function(ev){
    if (ev.target && ev.target.id === 'wishlistModal') loadWishlistItems();
  });

  // initial badge
  document.addEventListener('DOMContentLoaded', refreshWishlistCount);
})();
</script>


<!-- modal compare -->
<div class="offcanvas offcanvas-bottom canvas-compare" id="compare">
  <div class="canvas-wrapper">
    <header class="canvas-header">
      <div class="close-popup">
        <span
          class="icon-close icon-close-popup"
          data-bs-dismiss="offcanvas"
          aria-label="Close"></span>
      </div>
    </header>
    <div class="canvas-body">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="tf-compare-list">
              <div class="tf-compare-head">
                <div class="title">Compare Products</div>
              </div>
              <div class="tf-compare-offcanvas">
                <div class="tf-compare-item">
                  <div class="position-relative">
                    <div class="icon">
                      <i class="icon-close"></i>
                    </div>
                    <a href="product-detail.html">
                      <img
                        class="radius-3"
                        src="{{ asset('images/products/orange-1.jpg') }}"
                        alt="" />
                    </a>
                  </div>
                </div>
                <div class="tf-compare-item">
                  <div class="position-relative">
                    <div class="icon">
                      <i class="icon-close"></i>
                    </div>
                    <a href="product-detail.html">
                      <img
                        class="radius-3"
                        src="{{ asset('images/products/pink-1.jpg') }}"
                        alt="" />
                    </a>
                  </div>
                </div>
              </div>
              <div class="tf-compare-buttons">
                <div class="tf-compare-buttons-wrap">
                  <a
                    href="compare.html"
                    class="tf-btn radius-3 btn-fill justify-content-center fw-6 fs-14 flex-grow-1 animate-hover-btn">Compare</a>
                  <div class="tf-compapre-button-clear-all link">
                    Clear All
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /modal compare -->

<!-- modal quick_add -->
<div class="modal fade modalDemo popup-quickadd" id="quick_add">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <span
          class="icon-close icon-close-popup"
          data-bs-dismiss="modal"></span>
      </div>
      <div class="wrap">
        <div class="tf-product-info-item">
          <div class="image">
            <img src="{{ asset('images/products/orange-1.jpg') }}" alt="" />
          </div>
          <div class="content">
            <a href="product-detail.html">Ribbed Tank Top</a>
            <div class="tf-product-info-price">
              <!-- <div class="price-on-sale">$8.00</div>
                                <div class="compare-at-price">$10.00</div>
                                <div class="badges-on-sale"><span>20</span>% OFF</div> -->
              <div class="price">$18.00</div>
            </div>
          </div>
        </div>
        <div class="tf-product-info-variant-picker mb_15">
          <div class="variant-picker-item">
            <div class="variant-picker-label">
              Color:
              <span class="fw-6 variant-picker-label-value">Orange</span>
            </div>
            <div class="variant-picker-values">
              <input id="values-orange" type="radio" name="color" checked />
              <label
                class="hover-tooltip radius-60"
                for="values-orange"
                data-value="Orange">
                <span class="btn-checkbox bg-color-orange"></span>
                <span class="tooltip">Orange</span>
              </label>
              <input id="values-black" type="radio" name="color" />
              <label
                class="hover-tooltip radius-60"
                for="values-black"
                data-value="Black">
                <span class="btn-checkbox bg-color-black"></span>
                <span class="tooltip">Black</span>
              </label>
              <input id="values-white" type="radio" name="color" />
              <label
                class="hover-tooltip radius-60"
                for="values-white"
                data-value="White">
                <span class="btn-checkbox bg-color-white"></span>
                <span class="tooltip">White</span>
              </label>
            </div>
          </div>
          <div class="variant-picker-item">
            <div class="variant-picker-label">
              Size: <span class="fw-6 variant-picker-label-value">S</span>
            </div>
            <div class="variant-picker-values">
              <input type="radio" name="size" id="values-s" checked />
              <label class="style-text" for="values-s" data-value="S">
                <p>S</p>
              </label>
              <input type="radio" name="size" id="values-m" />
              <label class="style-text" for="values-m" data-value="M">
                <p>M</p>
              </label>
              <input type="radio" name="size" id="values-l" />
              <label class="style-text" for="values-l" data-value="L">
                <p>L</p>
              </label>
              <input type="radio" name="size" id="values-xl" />
              <label class="style-text" for="values-xl" data-value="XL">
                <p>XL</p>
              </label>
            </div>
          </div>
        </div>
        <div class="tf-product-info-quantity mb_15">
          <div class="quantity-title fw-6">Quantity</div>
          <div class="wg-quantity">
            <span class="btn-quantity minus-btn">-</span>
            <input type="text" name="number" value="1" />
            <span class="btn-quantity plus-btn">+</span>
          </div>
        </div>
        <div class="tf-product-info-buy-button">
          <form class="">
            <a
              href="#"
              class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart"><span>Add to cart -&nbsp;</span><span class="tf-qty-price">$18.00</span></a>
            <div class="tf-product-btn-wishlist btn-icon-action">
              <i class="icon-heart"></i>
              <i class="icon-delete"></i>
            </div>
            <a
              href="#compare"
              data-bs-toggle="offcanvas"
              aria-controls="offcanvasLeft"
              class="tf-product-btn-wishlist box-icon bg_white compare btn-icon-action">
              <span class="icon icon-compare"></span>
              <span class="icon icon-check"></span>
            </a>
            <div class="w-100">
              <a href="#" class="btns-full">Buy with <img src="{{ asset('images/payments/paypal.png') }}" alt="" /></a>
              <a href="#" class="payment-more-option">More payment options</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /modal quick_add -->

<!-- modal quick_view -->
<div class="modal fade modalDemo popup-quickview" id="quick_view">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <span
          class="icon-close icon-close-popup"
          data-bs-dismiss="modal"></span>
      </div>
      <div class="wrap">
        <div class="tf-product-media-wrap">
          <div dir="ltr" class="swiper tf-single-slide">
            <div class="swiper-wrapper">
              <div class="swiper-slide">
                <div class="item">
                  <img src="{{ asset('images/products/orange-1.jpg') }}" alt="" />
                </div>
              </div>
              <div class="swiper-slide">
                <div class="item">
                  <img src="{{ asset('images/products/pink-1.jpg') }}" alt="" />
                </div>
              </div>
            </div>
            <div
              class="swiper-button-next button-style-arrow single-slide-prev"></div>
            <div
              class="swiper-button-prev button-style-arrow single-slide-next"></div>
          </div>
        </div>
        <div class="tf-product-info-wrap position-relative">
          <div class="tf-product-info-list">
            <div class="tf-product-info-title">
              <h5>
                <a class="link" href="product-detail.html">Ribbed Tank Top</a>
              </h5>
            </div>
            <div class="tf-product-info-badges">
              <div class="badges text-uppercase">Best seller</div>
              <div class="product-status-content">
                <i class="icon-lightning"></i>
                <p class="fw-6">
                  Selling fast! 48 people have this in their carts.
                </p>
              </div>
            </div>
            <div class="tf-product-info-price">
              <div class="price">$18.00</div>
            </div>
            <div class="tf-product-description">
              <p>
                Nunc arcu faucibus a et lorem eu a mauris adipiscing conubia
                ac aptent ligula facilisis a auctor habitant parturient a
                a.Interdum fermentum.
              </p>
            </div>
            <div class="tf-product-info-variant-picker">
              <div class="variant-picker-item">
                <div class="variant-picker-label">
                  Color:
                  <span class="fw-6 variant-picker-label-value">Orange</span>
                </div>
                <div class="variant-picker-values">
                  <input
                    id="values-orange-1"
                    type="radio"
                    name="color-1"
                    checked />
                  <label
                    class="hover-tooltip radius-60"
                    for="values-orange-1"
                    data-value="Orange">
                    <span class="btn-checkbox bg-color-orange"></span>
                    <span class="tooltip">Orange</span>
                  </label>
                  <input id="values-black-1" type="radio" name="color-1" />
                  <label
                    class="hover-tooltip radius-60"
                    for="values-black-1"
                    data-value="Black">
                    <span class="btn-checkbox bg-color-black"></span>
                    <span class="tooltip">Black</span>
                  </label>
                  <input id="values-white-1" type="radio" name="color-1" />
                  <label
                    class="hover-tooltip radius-60"
                    for="values-white-1"
                    data-value="White">
                    <span class="btn-checkbox bg-color-white"></span>
                    <span class="tooltip">White</span>
                  </label>
                </div>
              </div>
              <div class="variant-picker-item">
                <div
                  class="d-flex justify-content-between align-items-center">
                  <div class="variant-picker-label">
                    Size:
                    <span class="fw-6 variant-picker-label-value">S</span>
                  </div>
                  <div class="find-size btn-choose-size fw-6">
                    Find your size
                  </div>
                </div>
                <div class="variant-picker-values">
                  <input
                    type="radio"
                    name="size-1"
                    id="values-s-1"
                    checked />
                  <label class="style-text" for="values-s-1" data-value="S">
                    <p>S</p>
                  </label>
                  <input type="radio" name="size-1" id="values-m-1" />
                  <label class="style-text" for="values-m-1" data-value="M">
                    <p>M</p>
                  </label>
                  <input type="radio" name="size-1" id="values-l-1" />
                  <label class="style-text" for="values-l-1" data-value="L">
                    <p>L</p>
                  </label>
                  <input type="radio" name="size-1" id="values-xl-1" />
                  <label
                    class="style-text"
                    for="values-xl-1"
                    data-value="XL">
                    <p>XL</p>
                  </label>
                </div>
              </div>
            </div>
            <div class="tf-product-info-quantity">
              <div class="quantity-title fw-6">Quantity</div>
              <div class="wg-quantity">
                <span class="btn-quantity minus-btn">-</span>
                <input type="text" name="number" value="1" />
                <span class="btn-quantity plus-btn">+</span>
              </div>
            </div>
            <div class="tf-product-info-buy-button">
              <form class="">
                <a
                  href="#"
                  class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart"><span>Add to cart -&nbsp;</span><span class="tf-qty-price">$8.00</span></a>
                <a
                  href="#"
                  class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action">
                  <span class="icon icon-heart"></span>
                  <span class="tooltip">Add to Wishlist</span>
                  <span class="icon icon-delete"></span>
                </a>
                <a
                  href="#compare"
                  data-bs-toggle="offcanvas"
                  aria-controls="offcanvasLeft"
                  class="tf-product-btn-wishlist hover-tooltip box-icon bg_white compare btn-icon-action">
                  <span class="icon icon-compare"></span>
                  <span class="tooltip">Add to Compare</span>
                  <span class="icon icon-check"></span>
                </a>
                <div class="w-100">
                  <a href="#" class="btns-full">Buy with <img src="{{ asset('images/payments/paypal.png') }}" alt="" /></a>
                  <a href="#" class="payment-more-option">More payment options</a>
                </div>
              </form>
            </div>
            <div>
              <a href="product-detail.html" class="tf-btn fw-6 btn-line">View full details<i class="icon icon-arrow1-top-left"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /modal quick_view -->

<!-- modal find_size -->
<div
  class="modal fade modalDemo tf-product-modal popup-findsize"
  id="find_size">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="header">
        <div class="demo-title">Size chart</div>
        <span
          class="icon-close icon-close-popup"
          data-bs-dismiss="modal"></span>
      </div>
      <div class="tf-rte">
        <div class="tf-table-res-df">
          <h6>Size guide</h6>
          <table class="tf-sizeguide-table">
            <thead>
              <tr>
                <th>Size</th>
                <th>US</th>
                <th>Bust</th>
                <th>Waist</th>
                <th>Low Hip</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>XS</td>
                <td>2</td>
                <td>32</td>
                <td>24 - 25</td>
                <td>33 - 34</td>
              </tr>
              <tr>
                <td>S</td>
                <td>4</td>
                <td>34 - 35</td>
                <td>26 - 27</td>
                <td>35 - 26</td>
              </tr>
              <tr>
                <td>M</td>
                <td>6</td>
                <td>36 - 37</td>
                <td>28 - 29</td>
                <td>38 - 40</td>
              </tr>
              <tr>
                <td>L</td>
                <td>8</td>
                <td>38 - 29</td>
                <td>30 - 31</td>
                <td>42 - 44</td>
              </tr>
              <tr>
                <td>XL</td>
                <td>10</td>
                <td>40 - 41</td>
                <td>32 - 33</td>
                <td>45 - 47</td>
              </tr>
              <tr>
                <td>XXL</td>
                <td>12</td>
                <td>42 - 43</td>
                <td>34 - 35</td>
                <td>48 - 50</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="tf-page-size-chart-content">
          <div>
            <h6>Measuring Tips</h6>
            <div class="title">Bust</div>
            <p>Measure around the fullest part of your bust.</p>
            <div class="title">Waist</div>
            <p>Measure around the narrowest part of your torso.</p>
            <div class="title">Low Hip</div>
            <p class="mb-0">
              With your feet together measure around the fullest part of
              your hips/rear.
            </p>
          </div>
          <div>
            <img
              class="sizechart lazyload"
              data-src="{{ asset('images/shop/products/size_chart2.jpg') }}"
              src="{{ asset('images/shop/products/size_chart2.jpg') }}"
              alt="" />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /modal find_size -->

<!-- Javascript -->
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('js/carousel.js') }}"></script>
<script src="{{ asset('js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('js/lazysize.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('js/count-down.js') }}"></script>
<script src="{{ asset('js/wow.min.js') }}"></script>
<script src="{{ asset('js/multiple-modal.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>

@livewireScripts
</body>

</html>