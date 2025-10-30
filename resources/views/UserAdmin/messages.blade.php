@include('UserAdmin.common.header')
@section('title','Messages')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Roboto+Slab:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <link rel="stylesheet" href="{{ asset('rebuildfrontend/css/messages.css') }}">

  <style>
/* For my messages */
.ultramsg-bubble.ultramsg-mine > div > a.ultramsg-btn.ultramsg-btn-ghost,
.ultramsg-bubble.ultramsg-mine > div > a.ultramsg-btn.ultramsg-btn-ghost *,
.ultramsg-bubble.ultramsg-mine > div > a.ultramsg-btn.ultramsg-btn-ghost i::before {
  color: black !important;
  fill: currentColor !important;
  -webkit-text-fill-color: black !important;
}

/* For their messages */
.ultramsg-bubble.ultramsg-theirs > div > a.ultramsg-btn.ultramsg-btn-ghost,
.ultramsg-bubble.ultramsg-theirs > div > a.ultramsg-btn.ultramsg-btn-ghost *,
.ultramsg-bubble.ultramsg-theirs > div > a.ultramsg-btn.ultramsg-btn-ghost i::before {
  color: white !important;
  fill: currentColor !important;
  -webkit-text-fill-color: white !important;
}

  </style>

  <!-- Laravel assets / Echo -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/js/app.js'])
</head>
<div class="main-content">
<body class="ultramsg-mode-list"
      data-accent="#CEFF1B"
      data-user-id="{{ auth()->id() }}"
      data-open-conversation-id="{{ $openConversationId ?? '' }}">

  <div class="ultramsg-container">
    <!-- LIST COLUMN -->
    <aside class="ultramsg-card ultramsg-section ultramsg-list-col" id="listCol">
      <div class="ultramsg-border-b" style="padding:10px 12px">
        <div class="ultramsg-search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input id="searchInput" placeholder="Search messages" />
        </div>
      </div>
      <div class="ultramsg-list" id="chatList"></div>
    </aside>

    <!-- CHAT COLUMN -->
    <section class="ultramsg-card ultramsg-section ultramsg-chat-col" id="chatCol">
      <div class="ultramsg-chat-header ultramsg-border-b">
        <button class="ultramsg-btn ultramsg-btn-ghost ultramsg-back" id="backToList">◀ Back</button>
        <div class="ultramsg-title">
          <div class="ultramsg-avatar" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <div>
            <div class="ultramsg-font-display" id="chatTitle">—</div>
            <div class="ultramsg-muted" id="chatPresence"><span class="ultramsg-offline-dot"></span> Offline</div>
          </div>
        </div>
        <div class="ultramsg-row">
          <a class="ultramsg-btn ultramsg-btn-ghost" id="startContractBtn" href="#" style="text-decoration:none">
            <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
            Contract
          </a>
          <button class="ultramsg-btn ultramsg-btn-ghost" id="openDetails">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            Details
          </button>
        </div>
      </div>

      <div class="ultramsg-chat-scroll" id="chatScroll">
        <!-- messages injected here -->
        <div id="typingRow" style="display:none">
          <span class="ultramsg-chip">typing
            <span class="ultramsg-blink">•</span>
            <span class="ultramsg-blink" style="animation-delay:.2s">•</span>
            <span class="ultramsg-blink" style="animation-delay:.4s">•</span>
          </span>
        </div>
      </div>

      <div class="ultramsg-composer">
        <div class="ultramsg-composer-row">
          <button class="ultramsg-btn ultramsg-btn-ghost" id="attachBtn" title="Attach">📎</button>
          <input id="fileInput" type="file" style="display:none" />
          <textarea id="composerInput" placeholder="Write a message"></textarea>
          <button class="ultramsg-btn" id="sendBtn" title="Send">➤</button>
        </div>
        <div class="ultramsg-muted" id="sendMeta" style="margin-top:4px">Press Enter to send • Shift+Enter for new line</div>
      </div>
    </section>

    <!-- DETAILS COLUMN -->
    <aside class="ultramsg-card ultramsg-section ultramsg-details-col" id="detailsCol">
      <div class="ultramsg-details-header ultramsg-border-b">
        <button class="ultramsg-btn ultramsg-btn-ghost ultramsg-back" id="backToChat">◀ Back</button>
        <div class="ultramsg-row">
          <div class="ultramsg-avatar" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <div>
            <div class="ultramsg-font-display" id="detailsName">—</div>
            <div class="ultramsg-muted" id="detailsPresence">—</div>
          </div>
        </div>
        <div class="ultramsg-spacer"></div>
      </div>
      <div class="ultramsg-details">
        <div class="ultramsg-grid">
          <div class="ultramsg-meta-card"><div class="ultramsg-muted">Joined</div><div id="joinedVal">—</div></div>
          <div class="ultramsg-meta-card"><div class="ultramsg-muted">Country</div><div id="countryVal">—</div></div>
          <div class="ultramsg-meta-card"><div class="ultramsg-muted">Avg Response</div><div id="respVal">—</div></div>
          <div class="ultramsg-meta-card"><div class="ultramsg-muted">Orders</div><div id="ordersVal">—</div></div>
        </div>
        <div class="ultramsg-meta-card" style="margin-top:12px" id="bioVal">—</div>
        <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap">
          <a class="ultramsg-btn ultramsg-btn-ghost" id="viewProfileBtn" href="#" target="_blank" rel="noopener">View Profile</a>
          <button class="ultramsg-btn" id="messageBtn">Message</button>
        </div>
      </div>
    </aside>
  </div>
  </div>
@include('UserAdmin.common.footer')

  @php
    $pingUrl = \Illuminate\Support\Facades\Route::has('ping') ? route('ping') : '/ping';
  @endphp

  <script>
    // ====== CONFIG / ROUTES ======
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const BODY = document.body;
    const meId = String(BODY.dataset.userId || '');
    let activeConvId = String(BODY.dataset.openConversationId || '');

    const CHAT_ROUTES = {
      list: @json(route('chat.conversations')),
      conversation: @json(route('chat.conversation', ['conversation' => '__ID__'])),
      send: @json(route('chat.send', ['conversation' => '__ID__'])),
      delivered: @json(route('chat.delivered', ['conversation' => '__ID__'])),
      seen: @json(route('chat.seen', ['conversation' => '__ID__'])),
      typing: @json(route('chat.typing', ['conversation' => '__ID__'])),
    };
    const r = (tpl, id) => tpl.replace('__ID__', String(id));
    const hasEcho = () => !!(window.Echo && typeof window.Echo.join === 'function');

    const CONTRACT_CREATE_BASE = @json(route('service.contracts.create'));
    @if(request()->filled('product'))
      const CONTRACT_HINT_PRODUCT = {{ (int) request('product') }};
    @else
      const CONTRACT_HINT_PRODUCT = null;
    @endif
    function buildContractUrl(buyerId, convId) {
      let url = `${CONTRACT_CREATE_BASE}?buyer=${encodeURIComponent(String(buyerId))}&conversation_id=${encodeURIComponent(String(convId))}`;
      if (CONTRACT_HINT_PRODUCT) url += `&product_id=${encodeURIComponent(String(CONTRACT_HINT_PRODUCT))}`;
      return url;
    }

    const PING_URL = @json($pingUrl);

    // ====== ELEMENTS ======
    const listEl = document.getElementById('chatList');
    const searchInput = document.getElementById('searchInput');

    const chatTitle = document.getElementById('chatTitle');
    const chatPresence = document.getElementById('chatPresence');
    const chatScroll = document.getElementById('chatScroll');
    const typingRow = document.getElementById('typingRow');

    const composerInput = document.getElementById('composerInput');
    const sendBtn = document.getElementById('sendBtn');
    const attachBtn = document.getElementById('attachBtn');
    const fileInput = document.getElementById('fileInput');
    const sendMeta = document.getElementById('sendMeta');

    const startContractBtn = document.getElementById('startContractBtn');
    const openDetailsBtn = document.getElementById('openDetails');
    const backToListBtn = document.getElementById('backToList');
    const backToChatBtn = document.getElementById('backToChat');

    const detailsName = document.getElementById('detailsName');
    const detailsPresence = document.getElementById('detailsPresence');
    const joinedVal = document.getElementById('joinedVal');
    const countryVal = document.getElementById('countryVal');
    const respVal = document.getElementById('respVal');
    const ordersVal = document.getElementById('ordersVal');
    const bioVal = document.getElementById('bioVal');
    const viewProfileBtn = document.getElementById('viewProfileBtn');
    const messageBtn = document.getElementById('messageBtn');

    // ====== STATE ======
    let chatItemsCache = [];
    let activePartnerId = null;
    let apiPartnerOnline = false;
    let presenceActive = false;
    let presenceMembers = new Set();
    let currentPresence = null, currentPresenceChan = null;
    let pollTimer = null;
    let typingTimer = null;
    let typingState = false;
    let lastMsgId = 0;
    let typingVisibleUntil = 0, lastWhisperAt = 0;

    // ====== HELPERS ======
    const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;
    function goMode(mode){
      if (isDesktop()) return; // desktop shows all
      BODY.classList.remove('ultramsg-mode-list','ultramsg-mode-chat','ultramsg-mode-details');
      BODY.classList.add('ultramsg-mode-'+mode);
    }
    const escapeHtml = s => (s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const stripTags = s => (s || '').replace(/<\/?[^>]+(>|$)/g, '').trim();
    const isoToLocal = iso => { try { return new Date(iso).toLocaleString(); } catch(_) { return iso || ''; } };
    function formatTime(tsIso){ return isoToLocal(tsIso).replace(',', ''); }
    function fmtBytes(x){ const n=Number(x||0); if(n<1024) return n+' B'; if(n<1048576) return (n/1024).toFixed(1)+' KB'; if(n<1073741824) return (n/1048576).toFixed(1)+' MB'; return (n/1073741824).toFixed(2)+' GB'; }
    function jsonFetch(url, opts={}){
      const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With':'XMLHttpRequest', ...(opts.headers||{}) };
      return fetch(url, { credentials:'same-origin', ...opts, headers });
    }
    function setPresenceLabel(isOnline){
      chatPresence.innerHTML = isOnline ? '<span class="ultramsg-online-dot"></span> Online' : '<span class="ultramsg-offline-dot"></span> Offline';
      if (detailsPresence) {
        detailsPresence.textContent = (detailsPresence.textContent.split('•')[0] || '').trim() + (isOnline ? ' • Online' : ' • Offline');
      }
    }

    function setListPresence(convId, isOnline){
  const li = listEl.querySelector(`[data-conversation-id="${convId}"]`);
  if (!li) return;
  const chip = li.querySelector('.ultramsg-row .ultramsg-chip');
  if (!chip) return;
  chip.innerHTML = isOnline
    ? '<span class="ultramsg-online-dot"></span> Online'
    : '<span class="ultramsg-offline-dot"></span> Offline';
}

   function updateOnlineLabel(){
  const onlineNow = presenceActive
    ? presenceMembers.has(String(activePartnerId))
    : !!apiPartnerOnline;

  setPresenceLabel(onlineNow);         // header/details
  setListPresence(activeConvId, onlineNow); // list row for active conv
}

    function zeroUnreadFor(convId){
      const li = listEl.querySelector(`[data-conversation-id="${convId}"]`);
      if (li) { const b = li.querySelector('.ultramsg-chip[data-badge="1"]'); if (b) b.remove(); }
      const idx = chatItemsCache.findIndex(x => String(x.id) === String(convId));
      if (idx >= 0) chatItemsCache[idx].unread = 0;
    }
    function sameOriginUrl(u){ try{ const p=new URL(u,window.location.href); if(p.origin!==window.location.origin){ return window.location.origin+p.pathname+p.search+p.hash } return p.href }catch(_){ return u } }

    // ====== RENDERERS ======
  function renderList(items){
  listEl.innerHTML = '';
  items.forEach(it=>{
    const convId = String(it.id);
    const isActive = convId === String(activeConvId);
    // If presence is active, trust it for the active conversation; otherwise use API
    const online = isActive
      ? (presenceActive ? presenceMembers.has(String(it.partner?.id || '')) : !!(it.partner?.online ?? apiPartnerOnline))
      : !!(it.partner?.online);

    const row = document.createElement('div');
    row.className = 'ultramsg-list-item' + (isActive ? ' ultramsg-active':'');
    row.dataset.conversationId = convId;
    row.dataset.partnerId = it.partner?.id != null ? String(it.partner.id) : '';
    row.innerHTML = `
      <div class="ultramsg-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
        </svg>
      </div>
      <div style="min-width:0; flex:1">
        <div class="ultramsg-row" style="justify-content:space-between">
          <div class="ultramsg-name">${escapeHtml(it.partner?.name || 'User')}</div>
          <span class="ultramsg-chip">${online
            ? '<span class="ultramsg-online-dot"></span> Online'
            : '<span class="ultramsg-offline-dot"></span> Offline'}</span>
        </div>
        <div class="ultramsg-last">${escapeHtml(it.last?.preview || '')}</div>
      </div>
      ${it.unread ? `<span class="ultramsg-chip" data-badge="1">${it.unread} new</span>` : ''}`;
    row.addEventListener('click', ()=> selectConv(convId));
    listEl.appendChild(row);
  });
}

    function bubble(msg){
      const mine = String(msg.sender_id) === meId;
      const wrap = document.createElement('div');
      wrap.style.display = 'flex';
      wrap.style.justifyContent = mine ? 'flex-end' : 'flex-start';
      wrap.dataset.msgId = msg.id || '';
      const b = document.createElement('div');
      b.className = 'ultramsg-bubble ' + (mine ? 'ultramsg-mine' : 'ultramsg-theirs');

      let inner = '';
      if (msg.body) inner += `<div>${escapeHtml(msg.body)}</div>`;

      if (msg.file && msg.file.url) {
        const name = escapeHtml(msg.file.name || 'file');
        const isImg = !!msg.file.is_image;
        if (isImg) inner += `<div style="margin-top:6px"><a href="${sameOriginUrl(msg.file.url)}" target="_blank" title="${name}"><img src="${sameOriginUrl(msg.file.url)}" alt="${name}" style="max-width:240px;border-radius:12px" onerror="this.src='https://placehold.co/220x160?text=Img'"></a></div>`;
        else inner += `<div style="margin-top:6px"><a class="ultramsg-btn ultramsg-btn-ghost" href="${sameOriginUrl(msg.file.url)}" target="_blank" download><i class="fa fa-paperclip" style="margin-right:6px"></i>${name}<span class="ultramsg-muted" style="margin-left:6px">(${fmtBytes(msg.file.size)})</span></a></div>`;
      }

      if (mine) {
        let st = msg.status || '';
        if (!st && msg.seen_at) st = 'seen';
        else if (!st && msg.delivered_at) st = 'delivered';
        inner += `<div class="ultramsg-meta">${formatTime(msg.created_at)} • ${st || 'sent'}</div>`;
      } else {
        inner += `<div class="ultramsg-meta">${formatTime(msg.created_at)}</div>`;
      }

      b.innerHTML = inner;
      wrap.appendChild(b);
      return wrap;
    }

    function renderMessages(msgs){
      chatScroll.innerHTML = '';
      let lastDay = '';
      for (const m of (msgs || [])) {
        const day = new Date(m.created_at || Date.now()).toDateString();
        if (day !== lastDay) {
          const d = document.createElement('div');
          d.style.cssText = 'text-align:center;color:rgba(255,255,255,.6);font-size:11px;margin:8px 0';
          d.textContent = day;
          chatScroll.appendChild(d);
          lastDay = day;
        }
        chatScroll.appendChild(bubble(m));
        if (m.id && m.id > lastMsgId) lastMsgId = m.id;
      }
      chatScroll.appendChild(typingRow); // keep typing row at bottom
      scrollBottom();
    }

    function scrollBottom(){ chatScroll.scrollTop = chatScroll.scrollHeight; }

    // ====== LOADERS ======
    async function loadList(){
      try {
        const res = await jsonFetch(CHAT_ROUTES.list);
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!res.ok || !ct.includes('application/json')) throw new Error('list not json');
        const j = await res.json();
        if (j.ok) {
          chatItemsCache = (j.data || []).slice(0);
          renderList(chatItemsCache);
          if (activeConvId) {
            const it = (j.data || []).find(x => String(x.id) === String(activeConvId));
            apiPartnerOnline = !!(it && it.partner && it.partner.online);
            if (!presenceActive) updateOnlineLabel();
          }
        }
      } catch (e) { console.error('[chat] list', e); }
    }

const USER_DETAILS_ROUTE = @json(route('user.details', ['id' => '__ID__']));

    async function loadConversation(id){
      try {
        const url = r(CHAT_ROUTES.conversation, id);
        const res = await jsonFetch(url);
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (res.status === 404) { await loadList(); return; }
        if (!res.ok || !ct.includes('application/json')) {
          console.error('conversation load failed', res.status, await res.text());
          return;
        }
        const j = await res.json();
        if (!j.ok) return;

        const p = j.partner || {};
        activePartnerId = p.id != null ? String(p.id) : null;
        apiPartnerOnline = !!p.online;

        chatTitle.textContent = p.name || 'User';
        setPresenceLabel(!!p.online);

        detailsName.textContent = p.name || 'User';
        detailsPresence.textContent = `${(p.country || '—')} • ${(p.online ? 'Online' : 'Offline')}`;
        joinedVal.textContent = p.joined_at || p.joined || '—';
        countryVal.textContent = p.country || '—';
        respVal.textContent = p.avg_response || '—';
        ordersVal.textContent = (p.orders != null ? p.orders : '—');
        bioVal.textContent = stripTags(p.bio) || '—';
        if (p.id != null) {
  viewProfileBtn.href = USER_DETAILS_ROUTE.replace('__ID__', String(p.id));
} else {
  viewProfileBtn.href = '#';
}

        // Contract button
        if (activePartnerId && activeConvId) {
          startContractBtn.href = buildContractUrl(activePartnerId, activeConvId);
          startContractBtn.style.pointerEvents = 'auto';
          startContractBtn.style.opacity = '1';
        } else {
          startContractBtn.href = '#';
          startContractBtn.style.pointerEvents = 'none';
          startContractBtn.style.opacity = '.6';
        }

        renderMessages(j.messages || []);
      } catch (e) { console.error('[chat] conversation', e); }
    }

    // ====== SELECT CONVERSATION ======
    async function selectConv(id){
      if (!id) return;
      activeConvId = String(id);
      lastMsgId = 0;

      stopFallbackPoll();
      markActiveInList(activeConvId);
      if (!isDesktop()) goMode('chat');

      // leave previous presence
      if (hasEcho() && currentPresenceChan) {
        try { window.Echo.leave(currentPresence); } catch(_) {}
        currentPresence = null; currentPresenceChan = null; presenceActive = false; presenceMembers = new Set();
      }

      await loadConversation(id);
      updateOnlineLabel();
      subscribePresence(id);
      await markDelivered();
      if (document.hasFocus()) await markSeen();
      if (!hasEcho()) startFallbackPoll();
    }

    function markActiveInList(convId){
      [...listEl.querySelectorAll('.ultramsg-list-item')].forEach(el=>{
        if (String(el.dataset.conversationId) === String(convId)) el.classList.add('ultramsg-active');
        else el.classList.remove('ultramsg-active');
      });
    }

    // ====== PRESENCE / REALTIME ======
    function subscribePresence(convId){
      const channelName = `chat.conversation.${convId}`;
      if (!hasEcho()) { startFallbackPoll(); return; }

      currentPresence = channelName;
      currentPresenceChan = window.Echo.join(channelName)
        .here(users => {
          presenceActive = true;
          presenceMembers = new Set((users || []).map(u => (u?.id != null ? String(u.id) : (u?.user_id != null ? String(u.user_id) : null))).filter(Boolean));
          stopFallbackPoll();
          updateOnlineLabel();
        })
        .joining(user => { const id = (user?.id != null ? String(user.id) : (user?.user_id != null ? String(user.user_id) : null)); if (id) presenceMembers.add(id); updateOnlineLabel(); })
        .leaving(user => { const id = (user?.id != null ? String(user.id) : (user?.user_id != null ? String(user.user_id) : null)); if (id) presenceMembers.delete(id); updateOnlineLabel(); })
        .error(e => { console.warn('[chat] presence error', e); presenceActive = false; presenceMembers = new Set(); updateOnlineLabel(); startFallbackPoll(); })
        .listen('.chat.new', e => {
          const msg = { id:e.id, sender_id:e.sender_id, body:e.body, file:e.file, created_at:e.created_at, status:'delivered' };
          chatScroll.insertBefore(bubble(msg), typingRow);
          if (msg.id && msg.id > lastMsgId) lastMsgId = msg.id;
          jsonFetch(r(CHAT_ROUTES.delivered, convId), { method:'POST' });
          if (document.hasFocus()) {
            jsonFetch(r(CHAT_ROUTES.seen, convId), { method:'POST' });
            zeroUnreadFor(convId);
          }
          scrollBottom();
          loadList();
        })
        .listen('.chat.delivered', e => {
          const last = Number(e.last_delivered_id || e.message_id || 0);
          if (last) applyDeliveredUpTo(last); else applyDeliveredToMyLast();
        })
        .listen('.chat.seen', e => {
          const last = Number(e.last_seen_id || 0);
          if (last) applySeenUpTo(last);
        })
        .listen('.chat.typing', e => { if (String(e.user_id) === meId) return; setTypingRowSticky(!!e.typing); })
        .listenForWhisper('typing', e => { if (String(e.user_id) === meId) return; setTypingRowSticky(!!e.typing); });
    }

    function applyDeliveredToMyLast(){
      const nodes = [...chatScroll.querySelectorAll('[data-msg-id]')];
      for (let i = nodes.length - 1; i >= 0; i--) {
        const el = nodes[i];
        const mine = (el.querySelector('.ultramsg-bubble')?.classList.contains('ultramsg-mine'));
        if (mine) {
          const meta = el.querySelector('.ultramsg-meta');
          if (meta && !meta.textContent.includes('seen')) meta.textContent = meta.textContent.replace(/(sent|delivered)?$/,'delivered');
          break;
        }
      }
    }
    function applyDeliveredUpTo(lastId){
      [...chatScroll.querySelectorAll('[data-msg-id]')].forEach(el=>{
        const id = Number(el.dataset.msgId || 0);
        if (id && id <= lastId) {
          const mine = (el.querySelector('.ultramsg-bubble')?.classList.contains('ultramsg-mine'));
          if (mine) {
            const meta = el.querySelector('.ultramsg-meta');
            if (meta && !meta.textContent.includes('seen')) meta.textContent = meta.textContent.replace(/(sent|delivered)?$/,'delivered');
          }
        }
      });
    }
    function applySeenUpTo(lastId){
      [...chatScroll.querySelectorAll('[data-msg-id]')].forEach(el=>{
        const id = Number(el.dataset.msgId || 0);
        if (id && id <= lastId) {
          const mine = (el.querySelector('.ultramsg-bubble')?.classList.contains('ultramsg-mine'));
          if (mine) {
            const meta = el.querySelector('.ultramsg-meta');
            if (meta) meta.textContent = meta.textContent.replace(/(sent|delivered)?$/,'seen');
          }
        }
      });
    }

    // Typing UX
    function setTypingRowSticky(show){
      if (!typingRow) return;
      if (show) {
        typingVisibleUntil = Date.now() + 4000;
        typingRow.style.display = 'block';
        if (!typingRow._keeper) {
          typingRow._keeper = setInterval(()=>{ if (Date.now() > typingVisibleUntil) typingRow.style.display = 'none'; }, 500);
        }
      } else {
        if (Date.now() > typingVisibleUntil) typingRow.style.display = 'none';
      }
      scrollBottom();
    }
    function setTyping(on){
      if (!activeConvId) return;
      if (typingState === on) return;
      typingState = on;
      jsonFetch(r(CHAT_ROUTES.typing, activeConvId), {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({ typing: !!on })
      }).catch(()=>{});
      if (presenceActive && currentPresenceChan && typeof currentPresenceChan.whisper === 'function') {
        const now = Date.now();
        if (now - lastWhisperAt > 600 || on === false) {
          try { currentPresenceChan.whisper('typing', { user_id: meId, typing: !!on }); lastWhisperAt = now; } catch(_) {}
        }
      }
    }

    // Fallback poll when Echo missing
    function startFallbackPoll(){
      stopFallbackPoll();
      if (!activeConvId) return;
      pollTimer = setInterval(async ()=>{
        if (document.hidden) return;
        if (presenceActive) return;
        try {
          const res = await jsonFetch(r(CHAT_ROUTES.conversation, activeConvId));
          if (!res.ok) return;
          const j = await res.json();
          if (!j.ok) return;
          const msgs = j.messages || [];
          let appended = false;
          for (const m of msgs) {
            if (!m.id || m.id <= lastMsgId) continue;
            chatScroll.insertBefore(bubble(m), typingRow);
            lastMsgId = Math.max(lastMsgId, m.id);
            appended = true;
          }
          if (appended) { applyDeliveredToMyLast(); scrollBottom(); loadList(); }
          apiPartnerOnline = !!(j.partner && j.partner.online);
          if (!presenceActive) updateOnlineLabel();
        } catch(_) {}
      }, 3000);
    }
    function stopFallbackPoll(){ if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    // ====== ACTIONS ======
    async function markDelivered(){ if (!activeConvId) return; try { await jsonFetch(r(CHAT_ROUTES.delivered, activeConvId), { method:'POST' }); } catch(_){} }
    async function markSeen(){ if (!activeConvId) return; try { await jsonFetch(r(CHAT_ROUTES.seen, activeConvId), { method:'POST' }); zeroUnreadFor(activeConvId); } catch(_){} }

    // ====== EVENTS / WIRING ======
    searchInput.addEventListener('input', e=>{
      const q = (e.target.value || '').toLowerCase();
      const filtered = chatItemsCache.filter(it =>
        (it.partner?.name || '').toLowerCase().includes(q) ||
        (it.last?.preview || '').toLowerCase().includes(q)
      );
      renderList(filtered);
      markActiveInList(activeConvId);
    });

    backToListBtn.addEventListener('click', ()=>goMode('list'));
    openDetailsBtn.addEventListener('click', ()=>goMode('details'));
    backToChatBtn.addEventListener('click', ()=>goMode('chat'));

    attachBtn.addEventListener('click', ()=> fileInput.click());

    composerInput.addEventListener('input', ()=>{
      if (!activeConvId) return;
      if (!typingState) setTyping(true);
      if (typingTimer) clearTimeout(typingTimer);
      typingTimer = setTimeout(()=> setTyping(false), 900);
    });
    window.addEventListener('blur', ()=> setTyping(false));
    window.addEventListener('focus', ()=> { markSeen(); });

    chatScroll.addEventListener('scroll', e=>{
      const el = e.target;
      if (el.scrollTop + el.clientHeight >= el.scrollHeight - 8) markSeen();
    });

    sendBtn.addEventListener('click', send);
    composerInput.addEventListener('keydown', e=>{
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });

    async function send(){
      if (!activeConvId) return;
      const body = (composerInput.value || '').trim();
      const file = fileInput.files[0] || null;
      if (!body && !file) return;
      setTyping(false);
      const fd = new FormData();
      if (body) fd.append('body', body);
      if (file) fd.append('file', file);
      try {
        const res = await jsonFetch(r(CHAT_ROUTES.send, activeConvId), { method:'POST', body: fd });
        const j = await res.json();
        if (!j.ok) { sendMeta.textContent = j.message || 'Failed'; return; }
        const msg = j.message;
        msg.sender_id = meId;
        msg.status = msg.status || 'sent';
        chatScroll.insertBefore(bubble(msg), typingRow);
        if (msg.id && msg.id > lastMsgId) lastMsgId = msg.id;
        composerInput.value = ''; fileInput.value = '';
        sendMeta.textContent = 'Press Enter to send • Shift+Enter for new line';
        scrollBottom(); loadList();
      } catch (err) {
        console.error(err);
        sendMeta.textContent = 'Failed';
      }
    }

    // Details quick actions
    messageBtn.addEventListener('click', ()=> goMode('chat'));

    // ====== KEEPALIVE / VISIBILITY ======
    setInterval(()=>{ if (!document.hidden) jsonFetch(PING_URL).catch(()=>{}); }, 60000);
    document.addEventListener('visibilitychange', ()=>{
      if (document.visibilityState === 'visible') {
        jsonFetch(PING_URL).catch(()=>{});
        loadList();
        if (activeConvId) markSeen();
      }
    });
    window.addEventListener('beforeunload', ()=>{
      try { navigator.sendBeacon(PING_URL, new Blob([], { type: 'text/plain' })); } catch(_) {}
    });
    setInterval(()=>{ if (!document.hidden) loadList(); }, 25000);

    // ====== INIT ======
    (async function init(){
      await loadList();
      // auto-open if a conversation id came from server
      if (activeConvId) selectConv(activeConvId);
      else {
        // pick first item if exists
        const first = chatItemsCache[0]?.id;
        if (first) selectConv(first);
        else goMode('list');
      }
      // start in list on mobile; desktop shows all automatically
      BODY.classList.add('ultramsg-mode-list');
    })();
  </script>
</body>
</html>
