
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

<!-- views/UserAdmin/common/footer.blade.php -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modeToggle   = document.getElementById('modeToggle');
  if (!modeToggle) return;

  const userRadio    = document.getElementById('modeUser');
  const creatorRadio = document.getElementById('modeCreator');
  const csrf         = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const updateUrl    = modeToggle.getAttribute('data-update-url');

  // Initialize from server-provided state
  const initialState = parseInt(modeToggle.getAttribute('data-user-state') || '0', 10);
  applyMode(initialState);

  let inFlight = false;

  async function saveState(nextState) {
    if (inFlight || !updateUrl) return;
    inFlight = true;
    try {
      const res = await fetch(updateUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ state: Number(nextState) }) // 0 or 1
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data?.ok) {
        // HARD REFRESH so server-side (Blade) shows correct sections everywhere
        window.location.reload();
        return;
      }
      console.warn('Failed to update mode', data);
      if (typeof data.state === 'number') applyMode(data.state);
    } catch (err) {
      console.error('Mode update error:', err);
    } finally {
      inFlight = false;
    }
  }

  function applyMode(state) {
    if (state === 1) {
      creatorRadio && (creatorRadio.checked = true);
      userRadio && (userRadio.checked = false);
      document.documentElement.classList.add('creator-mode');
      document.documentElement.classList.remove('client-mode');
      console.log('Switched to Creator Mode');
    } else {
      userRadio && (userRadio.checked = true);
      creatorRadio && (creatorRadio.checked = false);
      document.documentElement.classList.add('client-mode');
      document.documentElement.classList.remove('creator-mode');
      console.log('Switched to User Mode');
    }
    modeToggle.setAttribute('data-user-state', String(state));
  }

  userRadio?.addEventListener('change', () => {
    if (!userRadio.checked) return;
    applyMode(0);     // instant local feel
    saveState(0);     // persist and reload
  });

  creatorRadio?.addEventListener('change', () => {
    if (!creatorRadio.checked) return;
    applyMode(1);     // instant local feel
    saveState(1);     // persist and reload
  });
});

// show full-screen loader
function showGlobalLoader() {
  $('#globalLoader').removeClass('hidden').attr('aria-hidden', 'false');
}

// hide full-screen loader (with a tiny delay for smoothness)
function hideGlobalLoader() {
  $('#globalLoader').addClass('hidden').attr('aria-hidden', 'true');
}

// Example: auto-hide when page fully loaded
$(window).on('load', function() {
  // keep loader visible a tiny bit to avoid flicker (optional)
  setTimeout(hideGlobalLoader, 180);
});

// Example usage for AJAX
// showGlobalLoader();
// $.ajax(...).always(hideGlobalLoader);

</script>



@livewireScripts
</body>
</html>


