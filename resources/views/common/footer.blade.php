  <!-- =================== FOOTER =================== -->
  <footer>
    <div class="footer-top" style="background: #0e0e0e url('{{ asset('rebuildfrontend/images/8.jpeg') }}') top/cover no-repeat;">
      <div class="container fgrid f-accordion" id="footerAccordion">
        <div class="fcol address">
          <div class="footer_logo_div">
            <a href="/" aria-label="SquareUp home">
              <img class="footer_logo" src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="SquareUp" />
            </a>
          </div>
          <div class="ftitle">Address</div>
          <div class="panel">
            <p class="subtle">
              1234 Fashion Street, Suite 567,<br />New York, NY 10001
            </p>
            <p class="subtle">
              Email: info@fashionshop.com<br />Phone: (212) 555-1234
            </p>
            <div class="social">
              <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
              <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
              <a href="#"><i class="fa-brands fa-instagram"></i></a>
              <a href="#"><i class="fa-brands fa-tiktok"></i></a>
              <a href="#"><i class="fa-brands fa-pinterest-p"></i></a>
            </div>
          </div>
        </div>

        <div class="fcol">
          <div class="ftitle" data-collapsible="help" role="button" aria-expanded="false">
            Help <i class="fa-solid fa-angle-down"></i>
          </div>
          <div class="panel collapsed">
            <ul>
              <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
              <li><a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a></li>
              <li>
                <a
                  href="{{ auth()->check() || session('user_id') ? route('wishlist.page') : route('login') . '?redirect=' . urlencode(route('wishlist.page')) }}">
                  My Wishlist
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="fcol">
          <div class="ftitle" data-collapsible="about" role="button" aria-expanded="false">
            About us <i class="fa-solid fa-angle-down"></i>
          </div>
          <div class="panel collapsed">
            <ul>
              <li><a href="#">Our Story</a></li>
              <!-- <li><a href="#">Contact Us</a></li> -->
              <li>
                <a href="{{ auth()->check() || session('user_id') ? route('user.admin.profile') : route('login') }}">
                  Account
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="fcol">
          <div class="ftitle" data-collapsible="email" role="button" aria-expanded="false">
            Sign Up for Email <i class="fa-solid fa-angle-down"></i>
          </div>
          <div class="panel collapsed">
            <p class="subtle">
              Get first dibs on new arrivals, sales, exclusive content and
              more!
            </p>
            <div class="subscribe" style="display: flex; gap: 10px">
              <input class="input" id="footerNewsletterEmail" placeholder="Enter your email..." type="email" />
              <button class="btn btn-accent" id="openNewsletter">
                Subscribe
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="container footer-bottom">
        <div>© 2025 Ecomus Store. All Rights Reserved</div>
        <div class="pay">
          <div class="badge"><i class="fa-brands fa-cc-visa"></i> VISA</div>
          <div class="badge">
            <i class="fa-brands fa-cc-paypal"></i> PayPal
          </div>
          <div class="badge">
            <i class="fa-brands fa-cc-mastercard"></i> MasterCard
          </div>
          <div class="badge"><i class="fa-brands fa-cc-amex"></i> AMEX</div>
          <div class="badge">
            <i class="fa-brands fa-cc-discover"></i> Discover
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- Floating helpers -->
  <button class="backtop" id="backTop" title="Back to top">
    <i class="fa-solid fa-arrow-up"></i>
  </button>

  <!-- =================== DIALOG: SEARCH (kept) =================== -->
  <dialog id="searchDialog" style="
        border: 0;
        border-radius: 16px;
        padding: 0;
        background: #0e0e0e;
        color: #fff;
        width: min(720px, 92vw);
      ">
    <form method="dialog" style="
          padding: 16px;
          border-bottom: 1px solid var(--border);
          display: flex;
          justify-content: space-between;
          align-items: center;
        ">
      <strong>Search</strong>
      <button class="icon" aria-label="Close">
        <i class="fa-solid fa-xmark" style="color: #fff"></i>
      </button>
    </form>
    <div style="padding: 16px">
      <div class="searchbar" style="max-width: none">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input placeholder="Search product" />
        <button class="sbtn" aria-label="Search">
          <i class="fa-solid fa-search"></i>
        </button>
      </div>
    </div>
  </dialog>

  <!-- =================== AUTH MODALS =================== -->
  <!-- Login -->
  <div class="modal" id="loginModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeLogin">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Create an account</div>
        <div class="auth-sub">Let's get started!</div>

        <label class="auth-field">
          <span class="auth-label">Email</span>
          <input id="loginEmail" type="email" placeholder="hello@reallygreatsite.com" />
        </label>
        <label class="auth-field">
          <span class="auth-label">Password</span>
          <input id="loginPassword" type="password" placeholder="••••••••" />
        </label>

        <div class="auth-actions">
          <button class="auth-btn primary" id="loginSubmit">Login</button>
          <button class="auth-btn secondary" id="useRecovery">
            Use a recovery code
          </button>
        </div>

        <div class="auth-links">
          <a href="#" id="openForgot">Forgot password?</a>
          &nbsp;&nbsp;Already have an account?
          <a href="#" id="openSignup">Sign up</a>
        </div>
      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- Signup -->
  <div class="modal" id="signupModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeSignup">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Create an account</div>
        <div class="auth-sub">Let's get started!</div>

        <div class="row-2-auth">
          <label class="auth-field"><span class="auth-label">First name</span><input id="regFirst" type="text"
              placeholder="Olivia" /></label>
          <label class="auth-field"><span class="auth-label">Last name</span><input id="regLast" type="text"
              placeholder="Wilson" /></label>
        </div>
        <label class="auth-field"><span class="auth-label">Email</span><input id="regEmail" type="email"
            placeholder="hello@reallygreatsite.com" /></label>
        <label class="auth-field"><span class="auth-label">Phone number</span><input id="regPhone" type="tel"
            placeholder="+1 555 000 1234" /></label>
        <div class="row-2-auth">
          <label class="auth-field"><span class="auth-label">Password</span><input id="regPass" type="password"
              placeholder="••••••••" /></label>
          <label class="auth-field"><span class="auth-label">Confirm password</span><input id="regPass2" type="password"
              placeholder="••••••••" /></label>
        </div>
        <label class="chk" style="background: transparent; border-color: #6a6a6a">
          <input id="regAgree" type="checkbox" />
          <span>I accept the <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.</span>
        </label>

        <div class="auth-actions">
          <button class="auth-btn primary" id="signupSubmit">Sign up</button>
        </div>
        <div class="auth-links">
          Already have an account?
          <a href="#" id="backToLoginFromSignup">Log in</a>
        </div>
      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- Forgot password -->
  <div class="modal" id="forgotModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeForgot">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Reset your password</div>
        <div class="auth-sub">Enter your email to receive a code</div>
        <label class="auth-field"><span class="auth-label">Email</span><input id="fpEmail" type="email"
            placeholder="you@example.com" /></label>
        <div class="auth-actions">
          <!-- opens OTP only after backend success -->
          <button class="auth-btn primary" id="sendReset">Send</button>
        </div>
      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- OTP code -->
  <div class="modal" id="otpModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeOtp">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Enter verification code</div>
        <div class="auth-sub">We sent a 6-digit code to your email</div>
        <div class="otp" id="otpGroup">
          <input maxlength="1" inputmode="numeric" />
          <input maxlength="1" inputmode="numeric" />
          <input maxlength="1" inputmode="numeric" />
          <input maxlength="1" inputmode="numeric" />
          <input maxlength="1" inputmode="numeric" />
          <input maxlength="1" inputmode="numeric" />
        </div>
        <div class="auth-actions">
          <button class="auth-btn primary" id="otpSubmit">Submit</button>
        </div>
        <div class="auth-links" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
  <span id="otpTimerText">You can resend a code in <strong id="otpTimer">60</strong>s</span>
  <button class="auth-btn secondary" id="otpResend" type="button" disabled
          style="height:36px;padding:0 14px;border-radius:999px;">
    Resend code
  </button>
</div>

      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- NEW PASSWORD (after OTP) -->
  <div class="modal" id="resetPasswordModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeResetPassword">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Create a new password</div>
        <div class="auth-sub">Enter and confirm your new password</div>

        <input type="hidden" id="rpEmail" />
        <input type="hidden" id="rpCode" />

        <label class="auth-field">
          <span class="auth-label">New password</span>
          <input id="rpPass" type="password" placeholder="••••••••" />
        </label>
        <label class="auth-field">
          <span class="auth-label">Confirm password</span>
          <input id="rpPass2" type="password" placeholder="••••••••" />
        </label>

        <div class="auth-actions">
          <button class="auth-btn primary" id="rpSubmit">Update password</button>
        </div>
        <div class="auth-links">
          <a href="#" id="rpBackToLogin">Back to login</a>
        </div>
      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- Recovery code -->
  <div class="modal" id="recoveryModal" aria-hidden="true">
    <div class="auth">
      <button class="icon auth-close" id="closeRecovery">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="auth-panel">
        <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="ULTRAHUSTLE" class="logo" />
        <div class="auth-title">Use a recovery code</div>
        <label class="auth-field"><span class="auth-label">Email</span><input id="rcEmail" type="email"
            placeholder="you@example.com" /></label>
        <label class="auth-field"><span class="auth-label">Recovery code</span><input id="rcCode" type="text"
            placeholder="XXXX-XXXX" /></label>
        <div class="auth-actions">
          <button class="auth-btn primary" id="rcSubmit">Submit</button>
        </div>
        <div class="auth-links">
          <a href="#" id="backToLoginFromRecovery">Back to login</a>
        </div>
      </div>
      <div class="auth-visual"></div>
    </div>
  </div>

  <!-- Newsletter -->
  <div class="modal" id="newsletterModal" aria-hidden="true">
    <div class="newsletter">
      <div class="newsletter-card">
        <div style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 10px;
            ">
          <h3 style="margin: 0">Subscribe to our newsletter</h3>
          <button class="icon" id="closeNewsletter">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <p class="subtle" style="margin-top: 0">
          Enter your email to get updates and offers.
        </p>
        <div class="searchbar" style="max-width: none">
          <i class="fa-solid fa-envelope"></i>
          <input id="nlEmail" placeholder="you@example.com" type="email" />
          <button class="sbtn" id="nlSubmit" aria-label="Subscribe">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- =================== TOASTS =================== -->
  <div id="toastHost" style="position:fixed;right:16px;bottom:16px;z-index:99999;display:flex;flex-direction:column;gap:10px;"></div>

  <!-- =================== JS =================== -->
  <script>
    // ---------- routes & helpers ----------
    const routes = {
      login: @json(route('auth.login')),
      register: @json(route('auth.register')),
      fpSend: @json(route('password.forgot.send')),
      otpVerify: @json(route('password.otp.verify')),
      pwReset: @json(route('password.reset')),
      loginRecovery: @json(route('auth.login.recovery')),
      newsletter: @json(route('newsletter.subscribe')),
      suggest: @json(route('search.suggest')),
      wlCount: @json(route('wishlist.count')),
      wlItems: @json(route('wishlist.items')),
      wlToggle: @json(route('wishlist.toggle')),
      userHome: @json(route('user.admin.index')),
      userProfile: @json(route('user.admin.profile')),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function debounce(fn, wait=200) {
  let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), wait); };
}

// ---------------- SUCCESS HELPERS (drop-in) ----------------
// strict: only green-light when backend explicitly says success
function isSuccessStrict(resOk, data) {
  if (!resOk || data == null) return false;
  if (typeof data === 'object') {
    if (data.success === true) return true;
    if (data.ok === true) return true;
    if (data.valid === true) return true;            // allow {valid:true} for OTP verify
    if (data.status && String(data.status).toLowerCase() === 'ok') return true;
  }
  return false;
}

// tolerant: any 2xx without explicit failure fields counts as success
// use for endpoints that might return plain 200/empty body (register, send OTP)
function isSuccessLenient(resOk, data) {
  if (!resOk) return false;
  if (data == null) return true;
  if (typeof data === 'object') {
    if (data.success === false) return false;
    if (data.error) return false;
    if (data.errors) return false;
  }
  return true;
}

// back-compat alias — if any old code still calls isSuccess(...)
const isSuccess = isSuccessStrict;

// extract a human message from typical backend shapes
function pickMessage(data, fallback) {
  if (!data) return fallback;
  if (typeof data === 'string') return data;
  if (data.message) return String(data.message);
  if (data.errors) {
    try { return (Object.values(data.errors).flat()[0]) || fallback; } catch { return fallback; }
  }
  return fallback;
}

// Back-compat wrapper so calls to isSuccess(...) still work
// function isSuccess(resOk, data) {
//   return isSuccessStrict(resOk, data);
// }

// Endpoint-specific helpers
function loginOk(ok, data) {
  // succeed if strict OR backend returned user, token or redirect
  return isSuccessStrict(ok, data) || !!(data && (data.user || data.token || data.redirect));
}
function otpOk(ok, data) {
  // succeed if strict OR backend returns explicit validity flags
  return isSuccessStrict(ok, data) || (data && (data.valid === true || data.verified === true));
}



    const j = (sel, ctx = document) => ctx.querySelector(sel);

    function toast(type, message) {
      const host = j('#toastHost');
      if (!host) return;
      const el = document.createElement('div');
      el.style.background = type === 'error' ? '#3a1212' : (type === 'warn' ? '#3a2e12' : '#123a1a');
      el.style.border = '1px solid ' + (type === 'error' ? '#803333' : (type === 'warn' ? '#806633' : '#33805a'));
      el.style.color = '#fff';
      el.style.padding = '12px 14px';
      el.style.borderRadius = '10px';
      el.style.boxShadow = '0 10px 30px rgba(0,0,0,.35)';
      el.style.font = '500 14px/1.3 Roboto, sans-serif';
      el.style.maxWidth = '360px';
      el.innerText = message || (type === 'error' ? 'Something went wrong.' : 'Done');
      host.appendChild(el);
      setTimeout(() => {
        el.style.transition = 'opacity .25s, transform .25s';
        el.style.opacity = '0';
        el.style.transform = 'translateY(6px)';
        setTimeout(() => el.remove(), 260);
      }, 3200);
    }

    // ---------------- FETCH HELPERS (replace this whole function) ----------------
async function postJSON(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      // ask Laravel to return JSON for withErrors/validation/OTP etc.
      'Accept': 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrf,
    },
    body: JSON.stringify(data || {})
  });

  // Try to parse JSON; if it fails, keep raw text so callers can decide.
  let payload = null, text = null, ctype = res.headers.get('content-type') || '';
  if (ctype.includes('application/json')) {
    try { payload = await res.json(); } catch { payload = null; }
  } else {
    try { text = await res.text(); } catch { text = null; }
  }

  // Normalize common Laravel validation shape
  // If it is a 422 and no explicit payload, synthesize an error block.
  if (!payload && res.status === 422 && text) {
    try { payload = JSON.parse(text); } catch { /* ignore */ }
  }

  return { ok: res.ok, status: res.status, redirected: res.redirected, url: res.url, data: payload, text, ctype };
}


    async function getJSON(url, params = {}) {
      const urlObj = new URL(url, window.location.origin);
      Object.entries(params || {}).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') urlObj.searchParams.set(k, v);
      });
      const res = await fetch(urlObj.toString(), { headers: { 'Accept': 'application/json' } });
      let payload = null;
      try { payload = await res.json(); } catch { payload = null; }
      return { ok: res.ok, status: res.status, data: payload };
    }

    // Modal helpers (keep structure)
    const open = (el) => el?.classList?.add('open');
    const close = (el) => el?.classList?.remove('open');

    // =================== NAV/GENERAL UI (unchanged visuals) ===================
    function toggleMenu(id) {
      const li = document.getElementById(id);
      if (!li) return;
      const state = li.getAttribute("aria-expanded") === "true";
      document.querySelectorAll(".menu>li[aria-expanded]").forEach((n) => n.setAttribute("aria-expanded", "false"));
      li.setAttribute("aria-expanded", String(!state));
      const btn = li.querySelector("button");
      if (btn) btn.setAttribute("aria-expanded", String(!state));
    }
    ["homeMenu", "aboutMenu", "emailMenu"].forEach((mid) => {
      const host = document.getElementById(mid);
      const btn = host?.querySelector("button");
      if (btn) btn.addEventListener("click", () => toggleMenu(mid));
    });
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".menu"))
        document.querySelectorAll(".menu>li[aria-expanded]").forEach((n) => n.setAttribute("aria-expanded", "false"));
    });

    const sidebar = document.getElementById("sidebar");
    document.getElementById("openSidebar")?.addEventListener("click", () => sidebar.classList.add("open"));
    document.getElementById("closeSidebar")?.addEventListener("click", () => sidebar.classList.remove("open"));
    sidebar.addEventListener("click", (e) => { if (e.target === sidebar) sidebar.classList.remove("open"); });
    sidebar.querySelectorAll(".side-item").forEach((it) => {
      it.querySelector(".side-title").addEventListener("click", () =>
        it.setAttribute("aria-expanded", it.getAttribute("aria-expanded") === "true" ? "false" : "true")
      );
    });

    const wlDrawer = document.getElementById("wishlistDrawer");
    const openWishlist = () => wlDrawer.classList.add("open");
    const closeWishlist = () => wlDrawer.classList.remove("open");
    document.getElementById("openWishlistDesktop")?.addEventListener("click", openWishlist);
    document.getElementById("openWishlistMobile")?.addEventListener("click", openWishlist);
    document.getElementById("closeWishlist")?.addEventListener("click", closeWishlist);

    document.getElementById("backTop").addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

    // =================== SEARCH (kept, works with backend suggest) ===================
    const searchFlyout = document.getElementById("searchFlyout");
    const searchMask = document.getElementById("searchMask");
    const flySearch = document.getElementById("flySearch");
    const searchResults = document.getElementById("searchResults");
    const topSearch = document.getElementById("topSearchInput");

    function renderSearch(items = [], boosted = [], q = '') {
      const list = (q ? items : (boosted.length ? boosted : items)) || [];
      if (!list.length) {
        searchResults.innerHTML = '<div class="sr-empty">Product not found.</div>';
        return;
      }
      const html = list.map((c) => {
        const price = (c.price && c.price !== 'N/A') ? c.price : 'Price N/A';
        const rating = (Number(c.rating) || 0);
        const reviews = (Number(c.reviews) || 0);
        const stars = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
        const safeName = (c.name || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
        return `
          <div class="sr-item">
            <div class="sr-img"><img src="${c.cover || ''}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px"/></div>
            <div>
              <div class="sr-name"><a href="${c.url || '#'}" class="sr-link">${safeName}</a></div>
              <div class="sr-meta">${price} • <span class="stars">${stars}</span> (${reviews})</div>
            </div>
          </div>`;
      }).join('');
      searchResults.innerHTML = html;
      searchResults.querySelectorAll('.sr-link').forEach(a => {
        a.addEventListener('click', () => {
          closeSearchFlyout();
          if (dockOpen) closeDock();
        });
      });
    }

    async function loadSuggest(q = '') {
      try {
        const { ok, data } = await getJSON(routes.suggest, q ? { q } : {});
        if (!ok) throw 0;
        renderSearch(data?.items || [], data?.boosted || [], q);
      } catch {
        renderSearch([], [], q);
      }
    }

    function openSearchFlyout(seedValue = "") {
      searchMask.classList.add("open");
      searchFlyout.classList.add("open");
      flySearch.value = seedValue || topSearch?.value || "";
      const q = (topSearch?.value || flySearch.value || '').trim();
      loadSuggest(q);
    }
    function closeSearchFlyout() {
      searchMask.classList.remove("open");
      searchFlyout.classList.remove("open");
    }

    // ---- KEYUP live suggestions (desktop) ----
    if (topSearch) {
      const onTopInput = debounce(() => {
        const q = topSearch.value.trim();
        if (!matchMedia("(min-width: 769px)").matches) return; // desktop only here
        if (!searchFlyout.classList.contains('open')) openSearchFlyout(topSearch.value);
        else loadSuggest(q);
      }, 200);

      topSearch.addEventListener("input", onTopInput);

      topSearch.addEventListener("focus", () => {
        if (matchMedia("(min-width: 769px)").matches) openSearchFlyout(topSearch.value);
      });
      topSearch.addEventListener("click", () => {
        if (matchMedia("(min-width: 769px)").matches) openSearchFlyout(topSearch.value);
      });
      topSearch.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          if (matchMedia("(min-width: 769px)").matches) openSearchFlyout(topSearch.value);
        }
      });
      const headerSearchBtn = document.querySelector("header .searchbar .sbtn");
      headerSearchBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        if (matchMedia("(min-width: 769px)").matches) openSearchFlyout(topSearch.value);
      });
    }

    // ---- KEYUP live suggestions (flyout/mobile input) ----
    flySearch.addEventListener("input", debounce((e) => loadSuggest(e.target.value.trim()), 200));
    flySearch.addEventListener("keydown", (e) => { if (e.key === "Enter") e.preventDefault(); });
    searchMask.addEventListener("click", closeSearchFlyout);
    document.getElementById("closeSearchDrawer").addEventListener("click", () => document.getElementById("searchDrawer").classList.remove("open"));
    document.getElementById("closeSearchTop").addEventListener("click", () => document.getElementById("searchTopDrawer").classList.remove("open"));

    // =================== Footer accordions (mobile only) ===================
    const acc = document.getElementById("footerAccordion");
    acc.querySelectorAll("[data-collapsible]").forEach((title) => {
      title.addEventListener("click", () => {
        const expanded = title.getAttribute("aria-expanded") === "true";
        if (matchMedia("(max-width: 768px)").matches) {
          acc.querySelectorAll('.ftitle[aria-expanded="true"]').forEach((t) => {
            if (t !== title) {
              t.setAttribute("aria-expanded", "false");
              t.nextElementSibling.classList.add("collapsed");
            }
          });
        }
        title.setAttribute("aria-expanded", String(!expanded));
        title.nextElementSibling.classList.toggle("collapsed");
      });
    });

    // =================== Mobile search dock (unchanged visuals) ===================
    const dock = document.getElementById('searchDock');
    const header = document.querySelector('header .container');
    const bar = document.querySelector('header .searchbar');
    const mobileIcon = document.getElementById('openSearch');
    let originalParent = null, originalNext = null, dockOpen = false;
    const isDesktop = () => matchMedia('(min-width: 769px)').matches;

    function openDock() {
      if (!bar || dock.contains(bar)) return;
      originalParent = bar.parentNode;
      originalNext = bar.nextSibling;
      dock.appendChild(bar);
      dock.classList.add('open');
      dock.setAttribute('aria-hidden', 'false');
      document.body.classList.add('has-search-dock');
      bar.querySelector('input')?.focus();
      openSearchFlyout(topSearch?.value || '');
      // sync suggestions as the user types in the dock
      topSearch?.addEventListener('input', syncMobileResults, { once: true });
      dockOpen = true;
    }
    function syncMobileResults() {
      const q = (topSearch?.value || '').trim();
      loadSuggest(q);
      if (dockOpen) topSearch?.addEventListener('input', syncMobileResults, { once: true });
    }
    function closeDock() {
      if (!bar) return;
      dock.classList.remove('open');
      dock.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('has-search-dock');
      if (originalParent) originalParent.insertBefore(bar, originalNext); else header.appendChild(bar);
      closeSearchFlyout();
      dockOpen = false;
    }
    mobileIcon?.addEventListener('click', (e) => {
      e.preventDefault();
      if (typeof closeSearchFlyout === 'function') closeSearchFlyout();
      if (isDesktop()) return;
      dockOpen ? closeDock() : openDock();
    });
    function handleResize() { if (isDesktop()) { if (dockOpen || dock.contains(bar)) closeDock(); } }
    window.addEventListener('resize', handleResize);
    handleResize();
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && dockOpen) closeDock(); });
    ['openSidebar', 'openWishlistMobile', 'openWishlistDesktop'].forEach(id => {
      document.getElementById(id)?.addEventListener('click', () => { if (dockOpen) closeDock(); });
    });
    let lastY = window.scrollY;
    window.addEventListener('scroll', () => {
      const dy = Math.abs(window.scrollY - lastY);
      lastY = window.scrollY;
      if (dy > 40 && dockOpen) closeDock();
    });
    (function wireDesktopFlyoutOnly() {
      const topSearch = document.getElementById('topSearchInput');
      const headerSearchBtn = document.querySelector('header .searchbar .sbtn');
      if (topSearch) {
        topSearch.addEventListener('focus', () => { if (isDesktop()) openSearchFlyout(topSearch.value); });
        topSearch.addEventListener('click', () => { if (isDesktop()) openSearchFlyout(topSearch.value); });
        topSearch.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); if (isDesktop()) openSearchFlyout(topSearch.value); } });
        topSearch.addEventListener('input', () => {
          if (isDesktop() && searchFlyout.classList.contains('open')) loadSuggest(topSearch.value.trim());
        });
      }
      headerSearchBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (isDesktop()) {
          const q = document.getElementById('topSearchInput')?.value || '';
          openSearchFlyout(q);
        }
      });
    })();

    // =================== AUTH FLOWS (FIXED) ===================
    const loginModal = document.getElementById("loginModal");
    const signupModal = document.getElementById("signupModal");
    const forgotModal = document.getElementById("forgotModal");
    const otpModal = document.getElementById("otpModal");
    const resetPasswordModal = document.getElementById("resetPasswordModal");
    const recoveryModal = document.getElementById("recoveryModal");
    const newsletterModal = document.getElementById("newsletterModal");
const isLoggedIn = Boolean(@json((session('user_id') ?? null)));
    // open login

  function handleLoginEntry() {
    if (isLoggedIn) {
      window.location.href = routes.userHome;
    } else {
      // not authenticated → open login modal
      open(loginModal);
    }
  }

  // Header user icon
  document.getElementById("openLoginDesktop")?.addEventListener("click", (e) => {
    e.preventDefault();
    handleLoginEntry();
  });

  // Sidebar login button
  document.getElementById("sidebarLoginBtn")?.addEventListener("click", (e) => {
    e.preventDefault();
    handleLoginEntry();
  });

    // close login
    document.getElementById("closeLogin")?.addEventListener("click", () => close(loginModal));

    // login -> forgot/signup/recovery
    document.getElementById("openForgot")?.addEventListener("click", (e) => { e.preventDefault(); close(loginModal); open(forgotModal); });
    document.getElementById("openSignup")?.addEventListener("click", (e) => { e.preventDefault(); close(loginModal); open(signupModal); });
    document.getElementById("useRecovery")?.addEventListener("click", (e) => { e.preventDefault(); close(loginModal); open(recoveryModal); });

       document.getElementById("openSignupfrombtn")?.addEventListener("click", (e) => { e.preventDefault(); close(loginModal); open(signupModal); });

    // signup modal controls
       document.getElementById("closeSignup")?.addEventListener("click", (e) => { e.preventDefault(); close(loginModal); close(signupModal); });
    document.getElementById("backToLoginFromSignup")?.addEventListener("click", (e) => { e.preventDefault(); close(signupModal); open(loginModal); });


// ---------- Login (strict check; redirect success ONLY if final URL is the dashboard) ----------
document.getElementById('loginSubmit')?.addEventListener('click', async () => {
  const email = document.getElementById('loginEmail')?.value?.trim();
  const password = document.getElementById('loginPassword')?.value || '';
  if (!email || !password) return toast('error', 'Enter email and password.');

  const btn = document.getElementById('loginSubmit');
  const original = btn.innerText; btn.disabled = true; btn.innerText = 'Signing in...';

  try {
    const res = await postJSON(routes.login, { email, password });

    // 1) JSON success from backend
    if (isSuccessStrict(res.ok, res.data)) {
      toast('ok', (res.data && res.data.message) || 'Logged in.');
      close(loginModal);
      const target = (res.data && typeof res.data.redirect === 'string' && res.data.redirect) || routes.userHome;
      return window.location.href = target;
    }

    // 2) Redirect-based result: success only if final URL is the dashboard
    if (res.redirected && typeof res.url === 'string' && res.url.indexOf(routes.userHome) !== -1) {
      toast('ok', 'Logged in.');
      close(loginModal);
      return window.location.href = routes.userHome;
    }

    // Anything else is a failure
    const msg =
      (res.data && res.data.message) ||
      (res.data && res.data.errors && Object.values(res.data.errors).flat()[0]) ||
      'Invalid credentials.';
    toast('error', msg);
  } catch {
    toast('error', 'Login failed. Try again.');
  } finally {
    btn.disabled = false; btn.innerText = original;
  }
});


// ---------- Forgot: send code (lenient; opens OTP on success) ----------
const sendResetBtn = document.getElementById("sendReset");
document.getElementById("closeForgot")?.addEventListener("click", () => close(forgotModal));

sendResetBtn?.addEventListener("click", async () => {
  const email = j('#fpEmail')?.value?.trim();
  if (!email) return toast('error', 'Enter your email.');

  const originalText = sendResetBtn.innerText;
  sendResetBtn.disabled = true;
  sendResetBtn.innerText = 'Sending...';

  try {
    const res = await postJSON(routes.fpSend, { email });

    // lenient: 2xx + no explicit errors -> accept (your backend is already sending e-mail)
    if (isSuccessLenient(res.ok, res.data) || res.redirected) {
      toast('ok', pickMessage(res.data, 'Verification code sent.'));
      sessionStorage.setItem('auth.email', email);
      close(forgotModal);
      open(otpModal); // open only after "send" succeeded
    } else {
      toast('error', pickMessage(res.data, 'Could not send code.'));
    }
  } catch {
    toast('error', 'Could not send code.');
  } finally {
    sendResetBtn.disabled = false;
    sendResetBtn.innerText = originalText;
  }
});


    // ---------- OTP UX (auto-advance, backspace, paste) ----------
    (function enhanceOTP() {
      const group = document.getElementById("otpGroup");
      if (!group) return;
      const inputs = Array.from(group.querySelectorAll("input"));
      inputs.forEach((inp, idx) => {
        inp.addEventListener("input", () => {
          const val = inp.value.replace(/\D/g, "").slice(0, 1);
          inp.value = val;
          if (val && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        inp.addEventListener("keydown", (e) => { if (e.key === "Backspace" && !inp.value && idx > 0) inputs[idx - 1].focus(); });
      });
      inputs[0].addEventListener("paste", (e) => {
        const text = (e.clipboardData || window.clipboardData).getData("text").replace(/\D/g, "").slice(0, inputs.length);
        if (!text) return;
        e.preventDefault();
        text.split("").forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
        const last = text.length >= inputs.length ? inputs[inputs.length - 1] : inputs[text.length];
        (last || inputs[inputs.length - 1]).focus();
      });
    })();


    // ===== OTP resend with 60s cooldown =====
(function setupOtpResend() {
  const resendBtn = document.getElementById('otpResend');
  const timerEl   = document.getElementById('otpTimer');
  const timerText = document.getElementById('otpTimerText');
  const otpModalEl = document.getElementById('otpModal');

  if (!resendBtn || !timerEl || !timerText || !otpModalEl) return;

  let cooldownTimer = null;
  let remaining = 0;

  function setResendState(disabled, textWhenDisabled) {
    resendBtn.disabled = !!disabled;
    if (disabled) {
      timerText.style.display = '';
      if (typeof textWhenDisabled === 'string') timerText.innerHTML = textWhenDisabled;
    } else {
      timerText.style.display = 'none';
    }
  }

  function startOtpCooldown(seconds = 60) {
    remaining = seconds;
    setResendState(true, `You can resend a code in <strong id="otpTimer">${remaining}</strong>s`);
    // refresh the <strong id="otpTimer"> reference (innerHTML replaced)
    const liveTimer = () => document.getElementById('otpTimer');

    if (cooldownTimer) clearInterval(cooldownTimer);
    cooldownTimer = setInterval(() => {
      remaining -= 1;
      const el = liveTimer();
      if (el) el.textContent = String(Math.max(remaining, 0));
      if (remaining <= 0) {
        clearInterval(cooldownTimer);
        cooldownTimer = null;
        setResendState(false);
      }
    }, 1000);
  }

  // Start cooldown whenever OTP modal is opened
  const observer = new MutationObserver(() => {
    if (otpModalEl.classList.contains('open')) {
      // default 60s after a successful "Send" from Forgot Password
      startOtpCooldown(60);
    } else {
      if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }
    }
  });
  observer.observe(otpModalEl, { attributes: true, attributeFilter: ['class'] });

  resendBtn.addEventListener('click', async () => {
    if (resendBtn.disabled) return;

    const email = sessionStorage.getItem('auth.email') || '';
    if (!email) return toast('error', 'Email not found. Start from Forgot Password.');

    // Some apps have a dedicated resend route — if not, fallback to send
    const resendUrl = (routes.fpResend || routes.fpSend);

    // Loading state
    const originalHTML = resendBtn.innerHTML;
    resendBtn.disabled = true;
    resendBtn.setAttribute('aria-busy', 'true');
    resendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const { ok, data } = await postJSON(resendUrl, { email });

    // restore button UI (cooldown below will disable again on success)
    resendBtn.removeAttribute('aria-busy');
    resendBtn.innerHTML = originalHTML;

    if (isSuccessLenient(ok, data)) {
      toast('ok', data?.message || 'Verification code re-sent.');
      startOtpCooldown(60);
    } else {
      // if your backend rate-limits and returns a "Please wait Xs" message, show it verbatim
      const firstError = data?.errors ? (Object.values(data.errors).flat()[0] || '') : '';
      toast('error', firstError || data?.message || 'Could not resend code.');
      // keep button enabled so the user can try again later; backend prevents abuse anyway
      resendBtn.disabled = false;
    }
  });

  // Also start the cooldown immediately when we land here *and* modal is already open
  if (otpModalEl.classList.contains('open')) {
    startOtpCooldown(60);
  }

  // Expose a helper so your "Send" handler can explicitly trigger cooldown after success, if desired
  window.__startOtpCooldown = startOtpCooldown;
})();



// ---------- OTP verify (JSON-only success; wrong code will NOT advance) ----------
document.getElementById('otpSubmit')?.addEventListener('click', async () => {
  const email = sessionStorage.getItem('auth.email') || '';
  if (!email) return toast('error', 'Email not found. Start from Forgot Password.');

  const code = Array.from(document.querySelectorAll('#otpGroup input'))
    .map(i => i.value.replace(/\D/g, '')).join('').slice(0, 6);
  if (code.length !== 6) return toast('error', 'Enter the 6-digit code.');

  const btn = document.getElementById('otpSubmit');
  const original = btn.innerText; btn.disabled = true; btn.innerText = 'Verifying...';

  try {
    const res = await postJSON(routes.otpVerify, { email, code, otp: code });

    // accept only explicit JSON success
    if (isSuccessStrict(res.ok, res.data)) {
      toast('ok', (res.data && res.data.message) || 'Code verified. Create a new password.');
      document.getElementById('rpEmail').value = email;
      document.getElementById('rpCode').value  = code;
      close(otpModal);
      return open(resetPasswordModal);
    }

    // otherwise it's a failure
    const msg =
      (res.data && res.data.message) ||
      (res.data && res.data.errors && (res.data.errors.code?.[0] || res.data.errors.email?.[0])) ||
      'Invalid or expired code.';
    toast('error', msg);
  } catch {
    toast('error', 'Verification failed. Try again.');
  } finally {
    btn.disabled = false; btn.innerText = original;
  }
});



    document.getElementById("closeOtp")?.addEventListener("click", () => close(otpModal));


    // ---------- Reset password (strict; supports non-JSON redirects) ----------
document.getElementById('rpSubmit')?.addEventListener('click', async () => {
  const email = document.getElementById('rpEmail').value || sessionStorage.getItem('auth.email') || '';
  const code  = document.getElementById('rpCode').value || '';
  const password = document.getElementById('rpPass').value || '';
  const password_confirmation = document.getElementById('rpPass2').value || '';

  if (!password || !password_confirmation) return toast('error', 'Enter both password fields.');
  if (password !== password_confirmation) return toast('error', 'Passwords do not match.');

  const btn = document.getElementById('rpSubmit');
  const original = btn.innerText; btn.disabled = true; btn.innerText = 'Updating...';

  try {
    const res = await postJSON(routes.pwReset, { email, code, otp: code, password, password_confirmation });

    // 1) JSON success
    if (isSuccessStrict(res.ok, res.data)) {
      toast('ok', pickMessage(res.data, 'Password updated. You can now log in.'));
      close(resetPasswordModal);
      return open(loginModal);
    }

    // 2) Redirect-based success (controller used back()->with('success',...)->with('openModal','login'))
    if (res.redirected || (res.ok && !res.data && (res.status >= 200 && res.status < 400))) {
      toast('ok', 'Password updated. You can now log in.');
      close(resetPasswordModal);
      return open(loginModal);
    }

    // 3) Fail
    toast('error', pickMessage(res.data, 'Could not update password.'));
  } catch {
    toast('error', 'Could not update password.');
  } finally {
    btn.disabled = false; btn.innerText = original;
  }
});


    document.getElementById('closeResetPassword')?.addEventListener('click', () => close(resetPasswordModal));
    document.getElementById('rpBackToLogin')?.addEventListener('click', (e) => { e.preventDefault(); close(resetPasswordModal); open(loginModal); });

// ---------- Login with recovery code (normalize input) ----------
document.getElementById('rcSubmit')?.addEventListener('click', async () => {
  const email = document.getElementById('rcEmail')?.value?.trim();

  // normalize => strip non-alphanumerics and uppercase
  let recovery_code_raw = document.getElementById('rcCode')?.value || '';
  const recovery_code = recovery_code_raw.replace(/[^A-Za-z0-9]/g, '').toUpperCase();

  if (!email || !recovery_code) return toast('error', 'Enter email and recovery code.');

  const btn = document.getElementById('rcSubmit');
  const original = btn.innerText; btn.disabled = true; btn.innerText = 'Signing in...';

  try {
    const res = await postJSON(routes.loginRecovery, { email, recovery_code });

    // explicit JSON success
    if (isSuccessStrict(res.ok, res.data)) {
      toast('ok', (res.data && res.data.message) || 'Logged in with recovery code.');
      close(recoveryModal);
      const target =
        (res.data && typeof res.data.redirect === 'string' && res.data.redirect) ||
        routes.userProfile || routes.userHome;
      return window.location.href = target;
    }

    // redirect-based success (if your controller redirects on success)
    if (res.redirected && typeof res.url === 'string') {
      if ((routes.userProfile && res.url.indexOf(routes.userProfile) !== -1) ||
          (routes.userHome    && res.url.indexOf(routes.userHome)    !== -1)) {
        toast('ok', 'Logged in with recovery code.');
        close(recoveryModal);
        return window.location.href = (routes.userProfile || routes.userHome);
      }
    }

    // else show server message
    const msg =
      (res.data && res.data.message) ||
      (res.data && res.data.errors && (res.data.errors.recovery_code?.[0] || res.data.errors.email?.[0])) ||
      'Login failed.';
    toast('error', msg);

  } catch {
    toast('error', 'Login failed. Try again.');
  } finally {
    btn.disabled = false; btn.innerText = original;
  }
});

    
    
    document.getElementById("closeRecovery")?.addEventListener("click", () => close(recoveryModal));
    document.getElementById("backToLoginFromRecovery")?.addEventListener("click", (e) => { e.preventDefault(); close(recoveryModal); open(loginModal); });

    // ---------- Signup (kept) ----------
document.getElementById('signupSubmit')?.addEventListener('click', async () => {
  const first_name = j('#regFirst')?.value?.trim();
  const last_name  = j('#regLast')?.value?.trim();
  const phone_number = j('#regPhone')?.value?.trim();
  const email = j('#regEmail')?.value?.trim();
  const password = j('#regPass')?.value || '';
  const password_confirmation = j('#regPass2')?.value || '';
  const agree = j('#regAgree')?.checked;

  if (!agree) return toast('warn', 'Please accept Terms & Privacy.');
  if (!first_name || !last_name || !email || !password || !password_confirmation)
    return toast('error', 'Fill all required fields.');
  if (password !== password_confirmation) return toast('error', 'Passwords do not match.');

  const btn = document.getElementById('signupSubmit');
  const original = btn.innerText; btn.disabled = true; btn.innerText = 'Creating...';

  try {
    const { ok, data } = await postJSON(routes.register, {
      first_name, last_name, phone_number, email, password, password_confirmation, agree: true
    });

    if (isSuccessLenient(ok, data)) {
      toast('ok', pickMessage(data, 'Account created.'));
      close(signupModal);
      const nlInput = document.getElementById('nlEmail');
    if (nlInput) nlInput.value = email || '';

    // Open newsletter modal (instead of login)
    open(newsletterModal);

    // optional: focus the input after the modal opens
    setTimeout(() => nlInput?.focus(), 120);
    } else {
      toast('error', pickMessage(data, 'Registration failed.'));
    }
  } catch {
    toast('error', 'Registration failed.');
  } finally {
    btn.disabled = false; btn.innerText = original;
  }
});


 // ---------- Newsletter (LENIENT + LOADING) ----------
const openNewsletterBtn = document.getElementById("openNewsletter");
const closeNewsletterBtn = document.getElementById("closeNewsletter");
openNewsletterBtn?.addEventListener("click", (e) => { e.preventDefault(); open(newsletterModal); });
closeNewsletterBtn?.addEventListener("click", () => close(newsletterModal));

// Modal submit
const nlBtn = document.getElementById('nlSubmit');
nlBtn?.addEventListener('click', async () => {
  const email = document.getElementById('nlEmail')?.value?.trim();
  if (!email) return toast('error', 'Enter your email.');

  // loading state
  const originalHTML = nlBtn.innerHTML;
  nlBtn.disabled = true;
  nlBtn.setAttribute('aria-busy', 'true');
  nlBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const res = await postJSON(routes.newsletter, { email });

  // restore
  nlBtn.disabled = false;
  nlBtn.removeAttribute('aria-busy');
  nlBtn.innerHTML = originalHTML;

  const data = res.data || null;

  // Prefer backend's {result, msg} contract
  if (data && typeof data === 'object' && typeof data.result === 'string') {
    if (data.result === 'success') {
      toast('ok', data.msg || 'Subscribed successfully.');
      return close(newsletterModal);
    } else {
      // Show the exact backend message (e.g., "This email is already subscribed.")
      return toast('error', data.msg || 'Subscription failed.');
    }
  }

  // Handle specific HTTP 409 (already subscribed) even if payload parsing differed
  if (res.status === 409 && data && data.msg) {
    return toast('error', data.msg);
  }

  // Validation-style errors (Laravel 422)
  if (data && data.errors) {
    const firstError = (Object.values(data.errors).flat()[0]) || '';
    return toast('error', firstError || data.message || 'Subscription failed.');
  }

  // Fallbacks
  if (res.ok) {
    toast('ok', (data && (data.msg || data.message)) || 'Subscribed successfully.');
    close(newsletterModal);
  } else {
    toast('error', (data && (data.msg || data.message)) || 'Subscription failed.');
  }
});


// Sidebar/other inline subscribe (if present)
const sideBtn = document.getElementById('sideNewsletterSubmit');
sideBtn?.addEventListener('click', async () => {
  const email = document.getElementById('sideNewsletterEmail')?.value?.trim();
  if (!email) return toast('error', 'Enter your email.');

  const originalTxt = sideBtn.innerText;
  sideBtn.disabled = true;
  sideBtn.setAttribute('aria-busy', 'true');
  sideBtn.innerText = 'Sending...';

  const res = await postJSON(routes.newsletter, { email });

  sideBtn.disabled = false;
  sideBtn.removeAttribute('aria-busy');
  sideBtn.innerText = originalTxt;

  const data = res.data || null;

  // Prefer backend's {result, msg} contract
  if (data && typeof data === 'object' && typeof data.result === 'string') {
    if (data.result === 'success') {
      return toast('ok', data.msg || 'Subscribed successfully.');
    } else {
      // Show the exact backend message (e.g., "This email is already subscribed.")
      return toast('error', data.msg || 'Subscription failed.');
    }
  }

  // Specific 409 (already subscribed)
  if (res.status === 409 && data && data.msg) {
    return toast('error', data.msg);
  }

  // Validation-style errors
  if (data && data.errors) {
    const firstError = (Object.values(data.errors).flat()[0]) || '';
    return toast('error', firstError || data.message || 'Subscription failed.');
  }

  // Fallbacks
  if (res.ok) {
    toast('ok', (data && (data.msg || data.message)) || 'Subscribed successfully.');
  } else {
    toast('error', (data && (data.msg || data.message)) || 'Subscription failed.');
  }
});

// Prefill from footer field if user typed there
openNewsletterBtn?.addEventListener('click', () => {
  const v = document.getElementById('footerNewsletterEmail')?.value || '';
  if (v) document.getElementById('nlEmail').value = v;
});


    // ---------- Wishlist (kept) ----------
    async function refreshWishlistCount() {
      try { await getJSON(routes.wlCount); } catch { }
    }
    async function loadWishlistItems() {
      const box = document.getElementById('wishlistBody');
      if (!box) return;
      box.innerHTML = '<div style="text-align:center;">Loading…</div>';
      try {
        const { ok, data } = await getJSON(routes.wlItems);
        if (!ok || !data?.ok) {
  box.innerHTML = `
    <div style="text-align:center;">Please log in to see your wishlist.</div>
    <div style="margin-top:8px; display:flex; justify-content:center !important;">
      <a class="btn btn-sm btn-accent" href="#" data-open-login="1">Log in</a>
    </div>
  `;
  return;
}

        const items = data.items || [];
        if (!items.length) {
          box.innerHTML = `
              <div style="text-align:center;">
                <div class="wl-name">Your wishlist is empty.</div>
              </div>`;
          return;
        }
        let html = '';
        for (const it of items) {
          const priceHtml = (it.price_from != null) ? `${it.symbol || '$'}${Number(it.price_from).toFixed(2)}` : `Price N/A`;
          const rating = String(it.rating || '0.0');
          const reviews = Number(it.reviews || 0);
          const stars = '★'.repeat(Math.round(Number(rating))) + '☆'.repeat(5 - Math.round(Number(rating)));
          html += `
            <div class="wl-item">
              <div class="wl-thumb">
                <a href="${it.url}"><img src="${it.image}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px"/></a>
              </div>
              <div>
                <div class="wl-name"><a href="${it.url}">${(it.name || 'Product')}</a></div>
                <div class="wl-meta">${priceHtml} • <span class="stars">${stars}</span> (${reviews})</div>
              </div>
              <div class="wl-actions">
                <button class="btn btn-sm btn-accent view-btn" data-url="${it.url}" style="margin-right: 8px;">View</button>
                <button class="btn btn-sm" data-remove="${it.product_id}">Remove</button>
              </div>
            </div>`;
        }
        box.innerHTML = html;

        box.querySelectorAll('button[data-remove]')?.forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = Number(btn.getAttribute('data-remove'));
            try {
              await fetch(routes.wlToggle, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: id })
              });
            } catch { }
            loadWishlistItems();
            refreshWishlistCount();
            // also update small header badges if available
            if (typeof window.refreshWishlistBadge === 'function') window.refreshWishlistBadge();
          });
        });
        box.querySelectorAll('button.view-btn')?.forEach(btn => {
          btn.addEventListener('click', () => {
            const url = btn.getAttribute('data-url') || '#';
            window.location.href = url;
          });
        });
      } catch {
        box.innerHTML = `<div style="text-align:center;">Login to make wishlist.</div>`;
      }
    }
    document.getElementById('wishlistDrawer')?.addEventListener('transitionend', (e) => {
      if (e.target.classList.contains('open')) loadWishlistItems();
    });

// Open login modal when any element marked data-open-login is clicked (works for injected HTML)
document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-open-login]');
  if (!trigger) return;
  e.preventDefault();
  try { wlDrawer?.classList?.remove('open'); } catch {}
  open(loginModal); // uses your existing open() helper + #loginModal
});


    document.getElementById('openWishlistDesktop')?.addEventListener('click', loadWishlistItems);
    document.getElementById('openWishlistMobile')?.addEventListener('click', loadWishlistItems);
    document.addEventListener('DOMContentLoaded', refreshWishlistCount);

    // ---------- Newsletter modal open/close ----------
    document.getElementById("openNewsletter")?.addEventListener("click", (e) => { e.preventDefault(); open(newsletterModal); });
    document.getElementById("closeNewsletter")?.addEventListener("click", () => close(newsletterModal));

    // ---------- Global ESC closes overlays ----------
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        [sidebar, wlDrawer].forEach((el) => el?.classList?.remove("open"));
        [loginModal, signupModal, forgotModal, otpModal, recoveryModal, newsletterModal, resetPasswordModal]
          .forEach((el) => el?.classList?.remove("open"));
        closeSearchFlyout();
      }
    });

    // ---------- HERO search → trigger header search UX ----------
    (function wireHeroSearchToHeader() {
      const heroInput = document.getElementById('welcomeHeroSearch');
      const heroBtn = document.getElementById('welcomeHeroGo');
      if (!heroInput) return;

      const openHeaderSearchWith = (val='') => {
        if (matchMedia('(min-width: 769px)').matches) {
          // desktop: fill header input and open flyout
          const top = document.getElementById('topSearchInput');
          if (top) {
            top.value = val;
            openSearchFlyout(val);
          }
        } else {
          // mobile: open dock (it will move header bar), then open flyout with same query
          const top = document.getElementById('topSearchInput');
          if (typeof closeSearchFlyout === 'function') closeSearchFlyout();
          const mobileIcon = document.getElementById('openSearch');
          if (mobileIcon) mobileIcon.click();
          if (top) {
            top.value = val;
            openSearchFlyout(val);
          }
        }
      };

      heroInput.addEventListener('focus', () => openHeaderSearchWith(heroInput.value));
      heroInput.addEventListener('click', () => openHeaderSearchWith(heroInput.value));
      heroInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          openHeaderSearchWith(heroInput.value);
        }
      });
      heroInput.addEventListener('input', debounce(() => openHeaderSearchWith(heroInput.value), 200));
      heroBtn?.addEventListener('click', (e) => { e.preventDefault(); openHeaderSearchWith(heroInput.value); });
    })();
  </script>


<script>
  // Call this anywhere to go home and open the login modal there
  function goHomeAndOpenLogin() {
    try { localStorage.setItem('__open_login_after_redirect', '1'); } catch(_) {}
    window.location.href = "{{ route('home', ['login' => 1]) }}";
  }

  // One function that actually opens the modal, supports Bootstrap/jQuery/Dialog/fallback
  function openLoginModal() {
    const el = document.getElementById('loginModal');
    if (!el) return;

    // Bootstrap 5
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      new bootstrap.Modal(el).show();
      return;
    }
    // jQuery + Bootstrap 4/5
    if (window.$ && typeof $('#loginModal').modal === 'function') {
      $('#loginModal').modal('show');
      return;
    }
    // <dialog> element
    if (typeof el.showModal === 'function') {
      el.showModal();
      return;
    }
    // Fallback: add a class your CSS turns into visible
    el.classList.add('open');
  }

  document.addEventListener('DOMContentLoaded', () => {
    const url = new URL(window.location.href);
    const hasLoginParam = url.searchParams.get('login') === '1';
    const fromFlash = "{{ session('openModal') === 'login' ? '1' : '' }}";
    let shouldOpen = false;

    try {
      if (localStorage.getItem('__open_login_after_redirect') === '1') {
        shouldOpen = true;
        localStorage.removeItem('__open_login_after_redirect');
      }
    } catch(_) {}

    if (hasLoginParam || fromFlash === '1' || shouldOpen) {
      openLoginModal();
    }
  });
</script>



  @livewireScripts
</body>
</html>
