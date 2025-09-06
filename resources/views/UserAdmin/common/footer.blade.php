
    </div>
  </div>
  <!-- General JS Scripts -->
  <script src="{{ asset('assets/js/app.min.js') }}"></script>

  
<!-- jQuery NiceScroll (load local if present, else CDN fallback) -->
<script src="{{ asset('assets/bundles/jquery-nicescroll/jquery.nicescroll.min.js') }}"></script>
<script>
  if (!window.jQuery || !$.fn || !$.fn.niceScroll) {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"><\/script>');
  }
</script>
  <!-- JS Libraies -->
  <script src="{{ asset('assets/bundles/apexcharts/apexcharts.min.js') }}"></script>
  <!-- Page Specific JS File -->
  <script src="{{ asset('assets/js/page/index.js') }}"></script>
  <!-- Template JS File -->
  <script src="{{ asset('assets/js/scripts.js') }}"></script>
  <!-- Custom JS File -->
  <script src="{{ asset('assets/js/custom.js') }}"></script>
  <script src="{{ asset('assets/bundles/summernote/summernote-bs4.js') }}"></script>

 <script>
  (function () {
    // Remove any layout/theme tokens that can fight fullblack
    var body = document.body;
    var cls = (" " + (body.className || "") + " ")
      .replace(/\btheme-[a-z]+\b/gi, " ")
      .replace(/\b(light|dark|fullblack)\b/gi, " ")
      .replace(/\b(light-sidebar|dark-sidebar)\b/gi, " ")
      .replace(/\bbrand-inverse\b/gi, " ")
      .replace(/\s+/g, " ")
      .trim();

    // IMPORTANT: do NOT add "theme-white" here
    body.className = (cls + " fullblack dark-sidebar").trim();
  })();
</script>


<script>
(() => {
  // ===== Colors =====
  const TRACK_ON  = '#ffffff';
  const TRACK_OFF = '#3f4759';
  const KNOB_ON   = '#0d6efd';
  const KNOB_OFF  = '#9ca3af';

  const KEY = 'site_switch_state'; // 'on' | 'off'
  const $ = id => document.getElementById(id);

  // ===== Storage =====
  function readState() {
    let v = null;
    try { v = localStorage.getItem(KEY); } catch {}
    if (!v) {
      const m = document.cookie.match(/(?:^|; )site_switch_state=([^;]*)/);
      if (m) v = decodeURIComponent(m[1]);
    }
    return v === 'on' ? 'on' : 'off';
  }
  function saveState(on) {
    const v = on ? 'on' : 'off';
    try { localStorage.setItem(KEY, v); } catch {}
    document.cookie = KEY + '=' + encodeURIComponent(v) +
      '; expires=Fri, 31 Dec 2099 23:59:59 GMT; path=/';
  }

  // ===== One-time CSS injection (knob + mini layout) =====
  function injectCssOnce() {
    
    if (document.getElementById('switchDynamicCss')) return;
    const s = document.createElement('style');
    s.id = 'switchDynamicCss';
    s.textContent = `
/* knob colors */
#modeSwitchItem .custom-switch-indicator::before{background:${KNOB_OFF} !important;}
#modeSwitchItem #siteSwitch:checked + .custom-switch-indicator::before{background:${KNOB_ON} !important;}
#modeSwitchItem .custom-switch{padding-left:0 !important;}

/* mini sidebar layout: show only the switch, centered and compact */
#modeSwitchItem.is-mini .nav-link{
  justify-content:center !important;
  padding-left:0 !important; padding-right:0 !important; margin-left:0 !important;
}
#modeSwitchItem.is-mini i,
#modeSwitchItem.is-mini #siteSwitchText{display:none !important;}
#modeSwitchItem.is-mini .custom-switch-indicator{width:36px !important; height:18px !important;}
#modeSwitchItem.is-mini .custom-switch-indicator::before{width:14px !important; height:14px !important; top:2px !important; left:2px !important;}
#modeSwitchItem.is-mini #siteSwitch:checked + .custom-switch-indicator::before{left:20px !important;}

/* additionally force no padding when body is mini/gone (higher specificity) */
.sidebar-mini #sidebar-wrapper #modeSwitchItem .nav-link,
.sidebar-gone #sidebar-wrapper #modeSwitchItem .nav-link{
  justify-content:center !important;
  padding-left:0 !important; padding-right:0 !important; margin-left:0 !important;
}

/* remove the white vertical highlight on this row */
#modeSwitchItem .nav-link:after{display:none !important;}
`;
    document.head.appendChild(s);
  }

  // ===== Paint (text + track color) =====
  function paint(on) {
    const input = $('siteSwitch');
    const text  = $('siteSwitchText');
    const indicator = input ? input.nextElementSibling : null;

    if (input) input.checked = on;
    if (text)  text.textContent = on ? 'Switch to Client' : 'Switch to Creator';

    if (indicator) {
      const bg = on ? TRACK_ON : TRACK_OFF;
      indicator.style.setProperty('background', bg, 'important');
      indicator.style.setProperty('border-color', bg, 'important');
    }
  }

  // ===== Sync mini state (when sidebar collapsed) =====
  function isSidebarMini() {
    const b = document.body.classList;
    return b.contains('sidebar-mini') || b.contains('sidebar-gone') || b.contains('layout-2');
  }
  function syncMiniState() {
    const li = $('modeSwitchItem');
    if (!li) return;
    const mini = isSidebarMini();
    li.classList.toggle('is-mini', mini);

    // hard override padding inline (belt + suspenders)
    const nav = li.querySelector('.nav-link');
    if (nav) {
      if (mini) {
        nav.style.setProperty('padding-left', '0', 'important');
        nav.style.setProperty('padding-right', '0', 'important');
        nav.style.setProperty('margin-left', '0', 'important');
        nav.style.setProperty('justify-content', 'center', 'important');
      } else {
        nav.style.removeProperty('padding-left');
        nav.style.removeProperty('padding-right');
        nav.style.removeProperty('margin-left');
        nav.style.removeProperty('justify-content');
      }
    }
  }

  // ===== Init =====
  function init() {
    injectCssOnce();

    const input = $('siteSwitch');
    if (!input) return;

    // initial paint + mini sync
    paint(readState() === 'on');
    syncMiniState();

    // single light listener (no heavy observers)
    let reloading = false;
    input.addEventListener('change', () => {
      if (reloading) return;
      const isOn = !!input.checked;
      saveState(isOn);
      paint(isOn);
      reloading = true;
      setTimeout(() => location.reload(), 0); // clean reload, no URL change
    }, { passive: true });

    // update mini state when top toggle clicked or on resize
    document.querySelectorAll('[data-toggle="sidebar"]').forEach(el => {
      el.addEventListener('click', () => setTimeout(syncMiniState, 200), { passive: true });
    });
    window.addEventListener('resize', () => setTimeout(syncMiniState, 50), { passive: true });
    setTimeout(syncMiniState, 300); // resync after icons render
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
</script>
































@livewireScripts
</body>
</html>