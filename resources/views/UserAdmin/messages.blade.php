@include('UserAdmin.common.header')
@section('title','Messages')
<meta name="csrf-token" content="{{ csrf_token() }}">
@vite(['resources/js/app.js'])

<style>
  /* WhatsApp-like bubbles */
  .chat-bubble {
    display: inline-block;
    max-width: 80%;
    padding: .5rem .75rem;
    border-radius: 12px;
    word-break: break-word
  }

  .chat-bubble.mine {
    background: #dcf8c6;
    color: #111;
    border: 1px solid #ccebb5
  }

  .chat-bubble.theirs {
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #111
  }

  .chat-row {
    margin-bottom: .5rem
  }

  .chat-row.mine {
    text-align: right
  }

  .chat-row.theirs {
    text-align: left
  }

  .chat-row.mine .chat-bubble {
    margin-left: auto
  }

  .chat-row.theirs .chat-bubble {
    margin-right: auto
  }

  .status {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px
  }

  .chat-row.mine .status {
    text-align: right
  }

  .chat-row.theirs .status {
    text-align: left
  }

  .chat-img {
    max-width: 240px;
    border-radius: 10px
  }

  /* Make buttons readable on dark themes */
  .btn,
  .btn-outline-secondary,
  .btn-primary {
    color: #e5e7eb !important
  }

  .btn-outline-secondary {
    border-color: #cdd3dd !important
  }

  .btn-primary {
    background: #2563eb;
    border-color: #2563eb
  }

  /* Presence dot (header + details) */
  .presence-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 .35rem;
    vertical-align: middle;
    background: #9ca3af
  }

  .presence-dot.online {
    background: #22c55e
  }

  /* Small screen flow (like WhatsApp): only one pane at a time */
  @media (max-width: 767.98px) {

    #listCol,
    #midCol,
    #detailsCol {
      display: none !important
    }

    #chatApp.state-list #listCol {
      display: block !important
    }

    #chatApp.state-chat #midCol {
      display: block !important
    }

    #chatApp.state-details #detailsCol {
      display: block !important
    }

    .sm-only {
      display: inline-flex !important
    }

    .md-up {
      display: none !important
    }

    #convCard {
      height: calc(100vh - 220px);
      display: flex;
      flex-direction: column
    }

    #convCard .card-body {
      flex: 1 1 auto
    }
  }

  @media (min-width: 768px) {
    .sm-only {
      display: none !important
    }

    .md-up {
      display: inline-flex !important
    }

    #listCol {
      display: block !important
    }

    #midCol {
      display: block !important
    }
  }

  /* Header look */
  .btn-oval {
    border-radius: 9999px;
    padding: .25rem .8rem;
    line-height: 1.25rem;
    align-items: center;
    gap: .35rem
  }

  .card-header {
    border-bottom: 1px solid rgba(148, 163, 184, .25)
  }

  /* Guarantee list text is visible across themes */
  #chatList .fw-semibold {
    color: #e5e7eb
  }

  #chatList .small.text-muted {
    color: #9ca3af !important
  }
</style>

<div class="main-content" id="chatApp"
  data-open-conversation-id="{{ $openConversationId ?? '' }}"
  data-user-id="{{ auth()->id() }}">
  <section class="section">
    <div class="section-header">
      <h1>Messages</h1>
    </div>

    <div class="section-body">
      <div class="row">
        <!-- Left: chat list -->
        <div class="col-md-3" id="listCol">
          <div class="card">
            <div class="card-body p-0">
              <div class="p-2 border-bottom d-flex align-items-center gap-2">
                <!-- Small-screen title -->
                <input id="chatSearch" type="text" class="form-control" placeholder="Search...">
              </div>
              <ul id="chatList" class="list-group list-group-flush" style="max-height:70vh;overflow:auto">
                <!-- loaded by JS -->
              </ul>
            </div>
          </div>
        </div>

        <!-- Middle: conversation -->
        <div class="col-md-6" id="midCol">
          <div class="card" id="convCard" style="display:none">
            <div class="card-header d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <!-- Back (small only) -->
                <button class="btn btn-outline-secondary btn-oval sm-only" style="    width: 20px;
    align-items: center;
    justify-content: center;
    margin-right: 10px;" id="btnBackToList">←</button>

                <img id="hdrAvatar" src="" class="rounded-circle" style="width:36px;height:36px;object-fit:cover" onerror="this.src='https://placehold.co/36x36?text=U'">
                <div class="d-flex flex-column">
                  <div class="fw-semibold" id="hdrName">—</div>
                  <div class="small text-muted">
                    <span id="hdrDot" class="presence-dot"></span>
                    <span id="hdrStatus">Offline</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <!-- Desktop/tablet: toggle details (only md+) -->
                <button class="btn btn-outline-secondary btn-sm btn-oval md-up" id="btnToggleDetails">Details</button>
                <!-- Mobile: navigate to details (only sm) -->
                <button class="btn btn-outline-secondary btn-sm btn-oval sm-only" id="btnGoToDetails">Details →</button>
              </div>
            </div>
            <div id="chatScroll" class="card-body" style="height:60vh;overflow:auto;background:#f8fafc">
              <div id="messages"></div>
              <div id="typingRow" class="mt-2 small" style="display:none; color:#CEFF1B !important;">typing…</div>
            </div>
            <div class="card-footer">
              <form id="sendForm" class="d-flex align-items-center gap-2">
                <input id="msgInput" class="form-control" placeholder="Type a message">
                <input id="fileInput" type="file" style="display:none">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('fileInput').click()"><i class="fa fa-paperclip"></i></button>
                <button class="btn btn-primary"><i class="far fa-paper-plane"></i></button>
              </form>
              <div id="sendMeta" class="small text-muted mt-1"></div>
            </div>
          </div>
          <div id="noConvCard" class="card">
            <div class="card-body text-center text-muted">Select a conversation</div>
          </div>
        </div>

        <!-- Right: details -->
        <div class="col-md-3" id="detailsCol" style="display:none">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold md-up">Details</div>
                <!-- Back from details (small only) -->
                <button class="btn btn-outline-secondary btn-sm btn-oval sm-only" id="btnBackToChat">← Back</button>
              </div>
              <div class="text-center">
                <img id="dtAvatar" src="" class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover" onerror="this.src='https://placehold.co/80x80?text=U'">
                <h6 id="dtName" class="mb-1">—</h6>
                <div class="small text-muted">
                  <span id="dtDot" class="presence-dot"></span>
                  <span id="dtOnline">Offline</span>
                </div>
              </div>
              <hr>
              <div class="small">
                <div><b>Joined:</b> <span id="dtJoined">—</span></div>
                <div><b>Country:</b> <span id="dtCountry">—</span></div>
                <div><b>Avg response:</b> <span id="dtAvgResp">—</span></div>
                <div class="mt-2">
                  <b>Bio:</b>
                  <div id="dtBio" class="text-wrap">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /row -->
    </div>
  </section>
</div>



@include('UserAdmin.common.footer')
@php
$pingUrl = \Illuminate\Support\Facades\Route::has('ping') ? route('ping') : '/ping';
@endphp

<script>
  const CSRF = '{{ csrf_token() }}';

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

  let currentPresence = null,
    currentPresenceChan = null,
    presenceActive = false;
  let presenceMembers = new Set();
  const meId = String(document.getElementById('chatApp').dataset.userId || '');
  let activeConvId = String(document.getElementById('chatApp').dataset.openConversationId || '');
  let activePartnerId = null;
  let chatItemsCache = [];
  let typingTimer = null;
  let typingState = false;
  let lastMsgId = 0;
  let pollTimer = null;
  let apiPartnerOnline = false;
  let typingVisibleUntil = 0,
    lastWhisperAt = 0;

  /* small-screen state */
  function setState(state) {
    const r = document.getElementById('chatApp');
    r.classList.remove('state-list', 'state-chat', 'state-details');
    r.classList.add(`state-${state}`);
  }
  const isSmall = () => window.matchMedia('(max-width: 767.98px)').matches;
  /* keep state sane on resize */
  function normalizeLayoutBySize() {
    if (isSmall()) {
      if (activeConvId) setState('chat');
      else setState('list');
      document.getElementById('detailsCol').style.display = ''; // mobile uses states, not inline display
    } else {
      // desktop shows list+chat; details toggled by button
      document.getElementById('listCol').style.display = 'block';
      document.getElementById('midCol').style.display = 'block';
      // if details was open on mobile, don't force it open on desktop; keep whatever inline style it had
    }
  }

  /* optional per-user presence (kept for future; list no longer shows dot) */
  const userPresence = new Map();
  const userPresenceChans = new Map();

  function subscribeUserPresence(userId) {
    if (!hasEcho()) return;
    const key = String(userId);
    if (!key || userPresenceChans.has(key)) return;
    const chan = window.Echo.join(`user.${key}`)
      .here(users => {
        userPresence.set(key, (users || []).length > 0)
      })
      .joining(() => {
        userPresence.set(key, true)
      })
      .leaving(() => {})
      .error(() => {});
    userPresenceChans.set(key, chan);
  }

  /* helpers */
  const escapeHtml = s => (s || '').replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  } [m]));
  const stripTags = s => (s || '').replace(/<\/?[^>]+(>|$)/g, '').trim();
  const scrollBottom = () => {
    const sc = document.getElementById('chatScroll');
    sc.scrollTop = sc.scrollHeight
  };
  const isoToLocal = iso => {
    try {
      return new Date(iso).toLocaleString()
    } catch (_) {
      return iso || ''
    }
  };
  const fmtBytes = x => {
    const n = Number(x || 0);
    if (n < 1024) return n + ' B';
    if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
    if (n < 1073741824) return (n / 1048576).toFixed(1) + ' MB';
    return (n / 1073741824).toFixed(2) + ' GB'
  };

  function sameOriginUrl(u) {
    try {
      const p = new URL(u, window.location.href);
      if (p.origin !== window.location.origin) {
        return window.location.origin + p.pathname + p.search + p.hash
      }
      return p.href
    } catch (_) {
      return u
    }
  }
  const jsonFetch = (url, opts = {}) => {
    const headers = {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      'X-Requested-With': 'XMLHttpRequest',
      ...(opts.headers || {})
    };
    return fetch(sameOriginUrl(url), {
      credentials: 'same-origin',
      ...opts,
      headers
    });
  };

  function setOnlineUI(isOnline) {
    const hdrDot = document.getElementById('hdrDot');
    const dtDot = document.getElementById('dtDot');
    const label = isOnline ? 'Online' : 'Offline';
    document.getElementById('hdrStatus').textContent = label;
    hdrDot?.classList.toggle('online', !!isOnline);
    const dt = document.getElementById('dtOnline');
    if (dt) dt.textContent = label;
    dtDot?.classList.toggle('online', !!isOnline);
  }

  function updateOnlineLabel() {
    if (presenceActive) {
      setOnlineUI(presenceMembers.has(String(activePartnerId)));
    } else {
      setOnlineUI(!!apiPartnerOnline);
    }
  }

  function zeroUnreadFor(convId) {
    const li = document.querySelector(`#chatList li[data-conversation-id="${convId}"]`);
    if (li) {
      const b = li.querySelector('.badge.bg-primary');
      if (b) b.remove();
    }
    const idx = chatItemsCache.findIndex(x => String(x.id) === String(convId));
    if (idx >= 0) chatItemsCache[idx].unread = 0;
  }

  function bubble(msg) {
    const mine = String(msg.sender_id) === meId;
    const wrap = document.createElement('div');
    wrap.className = `chat-row ${mine?'mine':'theirs'}`;
    wrap.dataset.msgId = msg.id || '';
    let html = '';
    const cls = 'chat-bubble ' + (mine ? 'mine' : 'theirs');
    if (msg.body) html += `<div class="${cls}">${escapeHtml(msg.body)}</div>`;
    if (msg.file && msg.file.url) {
      const name = escapeHtml(msg.file.name || 'file');
      const isImg = !!msg.file.is_image;
      if (isImg) {
        html += `<div class="mt-1"><a href="${msg.file.url}" target="_blank" title="${name}"><img class="chat-img" src="${msg.file.url}" onerror="this.src='https://placehold.co/220x160?text=Img'"></a></div>`;
      } else {
        html += `<div class="mt-1"><a class="btn btn-sm btn-outline-secondary" href="${msg.file.url}" target="_blank" download><i class="fa fa-paperclip me-1"></i>${name}<span class="ms-1 small text-muted">(${fmtBytes(msg.file.size)})</span></a></div>`;
      }
    }
    if (mine) {
      let st = msg.status || '';
      if (!st && msg.seen_at) st = 'seen';
      else if (!st && msg.delivered_at) st = 'delivered';
      html += `<div class="status js-status">${st||''}</div>`;
    } else {
      html += `<div class="status">${isoToLocal(msg.created_at)}</div>`;
    }
    wrap.innerHTML = html;
    return wrap;
  }

  function renderList(items) {
    chatItemsCache = items.slice(0);
    const ul = document.getElementById('chatList');
    ul.innerHTML = '';
    items.forEach(it => {
      const convId = String(it.id);
      const preview = it.last?.preview || '';
      const li = document.createElement('li');
      li.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
      li.style.cursor = 'pointer';
      li.dataset.conversationId = convId;
      li.dataset.partnerId = it.partner?.id != null ? String(it.partner.id) : '';
      const unread = it.unread ? `<span class="badge bg-primary ms-auto">${it.unread}</span>` : '';
      const avatar = it.partner.avatar ? it.partner.avatar : 'https://placehold.co/36x36?text=U';
      li.innerHTML = `
        <img src="${avatar}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;margin-right: 8px;" onerror="this.src='https://placehold.co/36x36?text=U'">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center">
            <div class="fw-semibold me-2" style="color:#CEFF1B !important;">${escapeHtml(it.partner.name || 'User')}</div>
          </div>
          <div class="small text-muted text-truncate" style="max-width:240px">${escapeHtml(preview)}</div>
        </div>
        ${unread}
      `;
      li.addEventListener('click', () => selectConv(convId));
      ul.appendChild(li);
      if (it.partner && it.partner.id != null) subscribeUserPresence(it.partner.id);
    });
  }

  function markActiveInList(convId) {
    [...document.querySelectorAll('#chatList .list-group-item')].forEach(el => {
      if (String(el.dataset.conversationId) === String(convId)) el.classList.add('active');
      else el.classList.remove('active');
    });
  }

  async function loadList() {
    try {
      const res = await jsonFetch(CHAT_ROUTES.list);
      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (!res.ok || !ct.includes('application/json')) throw new Error('list not json');
      const json = await res.json();
      if (json.ok) {
        renderList(json.data || []);
        if (activeConvId) {
          const it = (json.data || []).find(x => String(x.id) === String(activeConvId));
          apiPartnerOnline = !!(it && it.partner && it.partner.online);
          if (!presenceActive) updateOnlineLabel();
        }
      }
    } catch (e) {
      console.error('list', e);
    }
  }

  async function selectConv(id) {
    if (!id) return;
    activeConvId = String(id);
    lastMsgId = 0;
    stopFallbackPoll();
    markActiveInList(activeConvId);
    document.getElementById('convCard').style.display = 'block';
    document.getElementById('noConvCard').style.display = 'none';
    if (isSmall()) setState('chat');

    if (hasEcho() && currentPresenceChan) {
      try {
        window.Echo.leave(currentPresence);
      } catch (_) {}
      currentPresence = null;
      currentPresenceChan = null;
      presenceActive = false;
      presenceMembers = new Set();
    }

    await loadConversation(id);
    subscribePresence(id);
    await markDelivered();
    if (document.hasFocus()) await markSeen();
    if (!hasEcho()) startFallbackPoll();
  }

  function normalizeMemberId(u) {
    if (u == null) return null;
    if (u.id != null) return String(u.id);
    if (u.user_id != null) return String(u.user_id);
    return null;
  }

  async function loadConversation(id) {
    try {
      const url = r(CHAT_ROUTES.conversation, id);
      const res = await jsonFetch(url);
      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (res.status === 404) {
        await loadList();
        return;
      }
      if (!res.ok || !ct.includes('application/json')) {
        const txt = await res.text();
        console.error('conversation load failed', res.status, txt.slice(0, 200));
        throw new Error('Failed to load conversation JSON');
      }

      const j = await res.json();
      if (!j.ok) return;
      const p = j.partner || {};
      activePartnerId = p.id != null ? String(p.id) : null;
      apiPartnerOnline = !!p.online;
      updateOnlineLabel();

      document.getElementById('hdrName').textContent = p.name || 'User';
      document.getElementById('hdrAvatar').src = p.avatar || 'https://placehold.co/36x36?text=U';
      document.getElementById('detailsCol').style.display = isSmall() ? 'none' : 'block';
      document.getElementById('dtName').textContent = p.name || 'User';
      document.getElementById('dtAvatar').src = p.avatar || 'https://placehold.co/80x80?text=U';
      document.getElementById('dtJoined').textContent = p.joined_at || j.partner?.joined || '—';
      document.getElementById('dtCountry').textContent = p.country || '—';
      document.getElementById('dtBio').textContent = stripTags(p.bio) || '—';
      const avgEl = document.getElementById('dtAvgResp');
      if (avgEl) avgEl.textContent = p.avg_response || '—';

      const box = document.getElementById('messages');
      box.innerHTML = '';
      (j.messages || []).forEach(m => {
        box.appendChild(bubble(m));
        if (m.id && m.id > lastMsgId) lastMsgId = m.id;
      });
      setTimeout(scrollBottom, 50);
    } catch (e) {
      console.error('conversation', e);
    }
  }

  function subscribePresence(convId) {
    const channelName = `chat.conversation.${convId}`;
    if (!hasEcho()) {
      startFallbackPoll();
      return;
    }
    currentPresence = channelName;
    currentPresenceChan = window.Echo.join(channelName)
      .here(users => {
        presenceActive = true;
        presenceMembers = new Set((users || []).map(normalizeMemberId).filter(Boolean));
        stopFallbackPoll();
        updateOnlineLabel();
      })
      .joining(user => {
        const id = normalizeMemberId(user);
        if (id) presenceMembers.add(id);
        updateOnlineLabel();
      })
      .leaving(user => {
        const id = normalizeMemberId(user);
        if (id) presenceMembers.delete(id);
        updateOnlineLabel();
      })
      .error(e => {
        console.warn('[chat] presence error', e);
        presenceActive = false;
        presenceMembers = new Set();
        updateOnlineLabel();
        startFallbackPoll();
      })
      .listen('.chat.new', e => {
        const msg = {
          id: e.id,
          sender_id: e.sender_id,
          body: e.body,
          file: e.file,
          created_at: e.created_at,
          status: 'delivered'
        };
        document.getElementById('messages').appendChild(bubble(msg));
        if (msg.id && msg.id > lastMsgId) lastMsgId = msg.id;
        jsonFetch(r(CHAT_ROUTES.delivered, convId), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': CSRF
          }
        });
        if (document.hasFocus()) {
          jsonFetch(r(CHAT_ROUTES.seen, convId), {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': CSRF
            }
          });
          zeroUnreadFor(convId);
        }
        scrollBottom();
        loadList();
      })
      .listen('.chat.delivered', e => {
        const last = Number(e.last_delivered_id || e.message_id || 0);
        if (last) applyDeliveredUpTo(last);
        else applyDeliveredToMyLast();
      })
      .listen('.chat.seen', e => {
        const last = Number(e.last_seen_id || 0);
        if (last) applySeenUpTo(last);
      })
      .listen('.chat.typing', e => {
        if (String(e.user_id) === meId) return;
        setTypingRowSticky(!!e.typing);
      })
      .listenForWhisper('typing', e => {
        if (String(e.user_id) === meId) return;
        setTypingRowSticky(!!e.typing);
      });
  }

  function applyDeliveredToMyLast() {
    const nodes = [...document.querySelectorAll('#messages > .chat-row')];
    for (let i = nodes.length - 1; i >= 0; i--) {
      const el = nodes[i];
      if (el.classList.contains('mine')) {
        const s = el.querySelector('.js-status');
        if (s && s.textContent !== 'seen') s.textContent = 'delivered';
        break;
      }
    }
  }

  function applyDeliveredUpTo(lastId) {
    if (!lastId) return;
    const rows = [...document.querySelectorAll('#messages > .chat-row.mine')];
    rows.forEach(el => {
      const id = Number(el.dataset.msgId || 0);
      if (id && id <= lastId) {
        const s = el.querySelector('.js-status');
        if (s && s.textContent !== 'seen') s.textContent = 'delivered';
      }
    });
  }

  function applySeenUpTo(lastId) {
    if (!lastId) return;
    const rows = [...document.querySelectorAll('#messages > .chat-row.mine')];
    rows.forEach(el => {
      const id = Number(el.dataset.msgId || 0);
      if (id && id <= lastId) {
        const s = el.querySelector('.js-status');
        if (s) s.textContent = 'seen';
      }
    });
  }

  function setTypingRowSticky(show) {
    const row = document.getElementById('typingRow');
    if (!row) return;
    if (show) {
      typingVisibleUntil = Date.now() + 4000;
      row.style.display = 'block';
      if (!row._keeper) {
        row._keeper = setInterval(() => {
          if (Date.now() > typingVisibleUntil) row.style.display = 'none';
        }, 500);
      }
    } else {
      if (Date.now() > typingVisibleUntil) row.style.display = 'none';
    }
  }

  function setTyping(on) {
    if (!activeConvId) return;
    if (typingState === on) return;
    typingState = on;
    jsonFetch(r(CHAT_ROUTES.typing, activeConvId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        typing: on
      })
    }).catch(() => {});
    if (presenceActive && currentPresenceChan && typeof currentPresenceChan.whisper === 'function') {
      const now = Date.now();
      if (now - lastWhisperAt > 600 || on === false) {
        try {
          currentPresenceChan.whisper('typing', {
            user_id: meId,
            typing: !!on
          });
          lastWhisperAt = now;
        } catch (_) {}
      }
    }
  }

  async function markDelivered() {
    if (!activeConvId) return;
    try {
      await jsonFetch(r(CHAT_ROUTES.delivered, activeConvId), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF
        }
      });
    } catch (_) {}
  }
  async function markSeen() {
    if (!activeConvId) return;
    try {
      await jsonFetch(r(CHAT_ROUTES.seen, activeConvId), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF
        }
      });
      zeroUnreadFor(activeConvId);
    } catch (_) {}
  }

  function startFallbackPoll() {
    stopFallbackPoll();
    if (!activeConvId) return;
    pollTimer = setInterval(async () => {
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
          document.getElementById('messages').appendChild(bubble(m));
          lastMsgId = Math.max(lastMsgId, m.id);
          appended = true;
        }
        if (appended) {
          applyDeliveredToMyLast();
          scrollBottom();
          loadList();
        }
        apiPartnerOnline = !!(j.partner && j.partner.online);
        if (!presenceActive) updateOnlineLabel();
      } catch (_) {}
    }, 3000);
  }

  function stopFallbackPoll() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    normalizeLayoutBySize();
    window.addEventListener('resize', normalizeLayoutBySize);

    document.getElementById('btnToggleDetails')?.addEventListener('click', () => {
      /* desktop toggle only */
      if (isSmall()) return;
      const col = document.getElementById('detailsCol');
      col.style.display = (col.style.display === 'none' ? 'block' : 'none');
    });
    document.getElementById('btnGoToDetails')?.addEventListener('click', () => {
      if (isSmall()) setState('details');
    });
    document.getElementById('btnBackToList')?.addEventListener('click', () => {
      if (isSmall()) setState('list');
    });
    document.getElementById('btnBackToChat')?.addEventListener('click', () => {
      if (isSmall()) setState('chat');
    });

    loadList();
    if (activeConvId) selectConv(activeConvId);

    document.getElementById('sendForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!activeConvId) return;
      const input = document.getElementById('msgInput');
      const file = document.getElementById('fileInput').files[0] || null;
      const body = (input.value || '').trim();
      if (!body && !file) return;
      setTyping(false);
      const fd = new FormData();
      if (body) fd.append('body', body);
      if (file) fd.append('file', file);
      try {
        const res = await jsonFetch(r(CHAT_ROUTES.send, activeConvId), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': CSRF
          },
          body: fd
        });
        const json = await res.json();
        if (!json.ok) {
          document.getElementById('sendMeta').textContent = json.message || 'Failed';
          return;
        }
        const msg = json.message;
        msg.sender_id = meId;
        msg.status = msg.status || 'sent';
        document.getElementById('messages').appendChild(bubble(msg));
        if (msg.id && msg.id > lastMsgId) lastMsgId = msg.id;
        input.value = '';
        document.getElementById('fileInput').value = '';
        document.getElementById('sendMeta').textContent = '';
        scrollBottom();
        loadList();
      } catch (err) {
        console.error(err);
        document.getElementById('sendMeta').textContent = 'Failed';
      }
    });

    document.getElementById('msgInput').addEventListener('input', () => {
      if (!activeConvId) return;
      if (!typingState) setTyping(true);
      if (typingTimer) clearTimeout(typingTimer);
      typingTimer = setTimeout(() => setTyping(false), 900);
    });
    window.addEventListener('blur', () => setTyping(false));
    window.addEventListener('focus', () => {
      markSeen();
    });
    document.getElementById('chatScroll').addEventListener('scroll', (e) => {
      const el = e.target;
      if (el.scrollTop + el.clientHeight >= el.scrollHeight - 8) markSeen();
    });

    document.getElementById('chatSearch').addEventListener('input', (e) => {
      const q = (e.target.value || '').toLowerCase();
      const filtered = chatItemsCache.filter(it => (it.partner?.name || '').toLowerCase().includes(q) || (it.last?.preview || '').toLowerCase().includes(q));
      renderList(filtered);
      markActiveInList(activeConvId);
    });

    const PING_URL = @json($pingUrl);
    setInterval(() => {
      if (!document.hidden) jsonFetch(PING_URL).catch(() => {});
    }, 60000);
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        jsonFetch(PING_URL).catch(() => {});
        loadList();
        if (activeConvId) markSeen();
      }
    });
    window.addEventListener('beforeunload', () => {
      try {
        navigator.sendBeacon(PING_URL, new Blob([], {
          type: 'text/plain'
        }));
      } catch (_) {}
    });
    setInterval(() => {
      if (!document.hidden) loadList();
    }, 25000);
  });
</script>

<script>
  // Small debug helper (works only if Echo is initialized)
  (function() {
    if (!window.__echoDebugJoin && window.Echo && typeof window.Echo.join === 'function') {
      window.__echoDebugJoin = function(channel) {
        console.log('[echo] joining test channel:', channel);
        return window.Echo.join(channel)
          .here((users) => console.log('[echo] here', users))
          .joining((u) => console.log('[echo] joining', u))
          .leaving((u) => console.log('[echo] leaving', u))
          .error((e) => console.warn('[echo] error', e))
          .listenForWhisper('typing', (e) => console.log('[echo] whisper typing', e));
      };
      console.log('[echo] __echoDebugJoin helper is available');
    }
  })();
</script>