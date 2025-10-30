{{-- resources/views/forum_show.blade.php --}}
@include('common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('rebuildfrontend/css/forumdetails.css') }}">

<style>
  /* Toast (theme) */
  .uh-toast {
    position: fixed; right: 18px; bottom: 18px; z-index: 9999;
    display: flex; align-items: center; gap: 10px;
    background: #0b0b0b; border: 1px solid var(--uh-border,#1a1a1a);
    color: #fff; padding: 10px 14px; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.6);
    transform: translateY(20px); opacity: 0; pointer-events: none; transition: .2s ease;
    font-family:'Roboto Slab',serif;
  }
  .uh-toast.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
  .uh-toast .uh-dot { width:10px; height:10px; border-radius:50%; background: var(--uh-neon,#CEFF1B); box-shadow:0 0 12px rgba(206,255,27,.6); }
  .uh-toast.error .uh-dot { background:#ff4d4f; box-shadow:0 0 12px rgba(255,77,79,.6); }

  /* Make the reply editor contenteditable look like your textarea */
  .uhp-reply .uhp-editor {
    min-height: 100px;
    background:#101010; border:1px solid var(--uh-border); color:#e5e5e5;
    border-radius:12px; padding:10px 12px; outline:none; overflow:auto;
  }
  .uhp-reply .uhp-editor:focus { box-shadow:0 0 0 2px var(--accent,#CEFF1B); }
  .uhp-reply .uhp-toolbar button.active { box-shadow:0 0 0 2px var(--accent,#CEFF1B); }

  /* Poll bars (results unchanged) */
  .uhp-poll { display:grid; gap:10px; }
  .uhp-poll .opt { display:grid; gap:6px; }
  .uhp-pbar { height: 10px; border-radius: 999px; background:#111; border:1px solid var(--uh-border); overflow:hidden; }
  .uhp-pfill { height:100%; background: var(--uh-neon,#CEFF1B); box-shadow: 0 0 12px rgba(206,255,27,.4) inset; width:0%; transition:width .25s; }
  .uhp-meta { font-size:12px; color:#aaa; }

  /* Pre-submit poll enhancements (highlight/disable) */
  .uhp-poll .opt.pre { display:flex; gap:10px; align-items:center; padding:8px 10px; border:1px solid var(--uh-border); border-radius:10px; background:#0b0b0b; cursor:pointer; }
  .uhp-poll .opt.pre input{ accent-color:#000; }
  .uhp-poll .opt.pre.active{ box-shadow:0 0 0 2px var(--uh-neon); background:#101010; 
  border: 1px solid var(--accent);
  }

    .uhp-poll .opt.pre.active input{ accent-color: var(--accent);
  }

  /* Simple link look for breadcrumb */
  .uhp-breadcrumb a { color: #e5e5e5; text-decoration:none; }
  .uhp-breadcrumb a:hover { color: var(--uh-neon); text-shadow: 0 0 8px rgba(206,255,27,.3); }

  /* Report modal (theme) */
  .uh-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:10000}
  .uh-modal{width:min(520px,90vw);background:#0b0b0b;border:1px solid var(--uh-border);border-radius:16px;padding:20px}
  .uh-modal h4{margin:0 0 8px;font-family:'Montserrat',sans-serif}
  .uh-modal textarea{width:100%;min-height:90px;background:#101010;border:1px solid var(--uh-border);color:#e5e5e5;border-radius:12px;padding:10px;resize:vertical}
  .uh-modal .row{display:flex;gap:8px;justify-content:flex-end;margin-top:10px}

  /* Highlight a specific comment (#c123) when deep-linked */
  .uhp-highlight{ outline:2px solid var(--accent,#CEFF1B); box-shadow:0 0 0 2px var(--accent,#CEFF1B), 0 0 18px rgba(206,255,27,.5); border-radius:12px; }
  @keyframes uhPulse { 0%{ box-shadow:0 0 0 0 rgba(206,255,27,.6);} 50%{ box-shadow:0 0 0 6px rgba(206,255,27,.15);} 100%{ box-shadow:0 0 0 0 rgba(206,255,27,0);} }

  /* Small helpers */
  ._hide { display:none !important; }
</style>

<!-- Report Modal -->
<div id="uhReportModal" class="uh-modal-backdrop">
  <div class="uh-modal">
    <h4>Report this post</h4>
    <div class="meta" style="color:#aaa;font-size:12px;margin-bottom:6px">Tell us what’s wrong. Our team will review.</div>
    <textarea id="uhReportReason" placeholder="Reason (optional)"></textarea>
    <div class="row">
      <button class="uhp-ghost" id="uhReportCancel">Cancel</button>
      <button class="uhp-accent" id="uhReportSubmit">Submit</button>
    </div>
  </div>
</div>

<!-- ============== ULTRA HUSTLE — POST THREAD PAGE ============== -->
<section id="postThreadPage" class="uhp-root">
  <div class="uhp-wrap">

    <!-- Top nav / breadcrumb -->
    <div class="uhp-topnav">
      <div class="uhp-topnav-inner">
        <div class="uhp-left">
          <button class="uhp-back" id="uhpBackBtn" aria-label="Back">
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <nav class="uhp-breadcrumb" id="uhpBreadcrumb"></nav>
        </div>
        <div class="uhp-right">
          <button class="uhp-ghost" title="Save" aria-label="Save" id="uhpSaveTop"><i class="fa-regular fa-bookmark"></i></button>
          <button class="uhp-ghost" title="Share" aria-label="Share" id="uhpShareTop"><i class="fa-solid fa-share-nodes"></i></button>
          <button class="uhp-ghost" title="Report" aria-label="Report" id="uhpReportTop"><i class="fa-regular fa-flag"></i></button>
        </div>
      </div>
    </div>

    <!-- 2-col layout -->
    <div class="uhp-grid container">
      <!-- Main -->
      <div class="uhp-main">

        <!-- Post card -->
        <article class="uhp-card" id="uhpPostCard">
          <!-- author row -->
          <div class="uhp-authorrow">
            <div class="uhp-author-left">
              <div class="uhp-avatar" id="uhpAuthorAva"><i class="fa-regular fa-user"></i></div>
              <div class="uhp-author-meta" id="uhpAuthorMeta"></div>
            </div>
            <div class="uhp-author-right">
              <span class="uhp-follow" id="uhpFollowBtn">Follow</span>
            </div>
          </div>

          <!-- title -->
          <h1 class="uhp-title" id="uhpTitle"></h1>

          <!-- tags -->
          <div class="uhp-tags" id="uhpTags"></div>

          <!-- media -->
          <div class="uhp-media _hide" id="uhpMediaWrap">
            <button class="uhp-media-btn" id="uhpMediaBtn" aria-label="Open media">
              <img id="uhpMediaImg" alt="Post visual">
              <video id="uhpMediaVideo" class="_hide" controls playsinline></video>
            </button>
          </div>

          <!-- poll (dynamic) -->
          <div class="uhp-poll _hide" id="uhpPoll"></div>

          <!-- body -->
          <div class="uhp-body prose" id="uhpBody"></div>

          <!-- tip -->
          <div class="uhp-tip _hide" id="uhpTip">
            <div class="uhp-tip-title">Pro Tip</div>
            <p class="uhp-tip-text">Try a subtle split-tone: cool shadows, warm highlights. Adds depth without plastic skin.</p>
          </div>

          <!-- cta -->
          <div class="uhp-cta" id="uhpCta"></div>

          <!-- reactions -->
          <div class="uhp-reactions" id="uhpReactions"></div>
        </article>

        <!-- Comments -->
        <section class="uhp-card" aria-label="Comments">
          <header class="uhp-sec-head">
            <div class="uhp-sec-title">Comments</div>
            <div class="uhp-sec-sub" id="uhpCommentsTotal"></div>
          </header>

          <div class="uhp-sec-body" id="uhpCommentsArea">
            <!-- reply box -->
            <div class="uhp-reply">
              <div class="uhp-avatar sm"><i class="fa-regular fa-user"></i></div>
              <div class="uhp-reply-box">
                <div class="uhp-toolbar">
                  <div class="uhp-tools">
                    <button class="uhp-tbtn" data-cmd="bold" title="Bold"><i class="fa-solid fa-b"></i></button>
                    <button class="uhp-tbtn" data-cmd="italic" title="Italic"><i class="fa-solid fa-italic"></i></button>
                    <button class="uhp-tbtn" data-cmd="strikeThrough" title="Strikethrough"><i class="fa-solid fa-strikethrough"></i></button>
                    <button class="uhp-tbtn" data-cmd="superscript" title="Superscript"><span> x<sup>2</sup> </span></button>
                    <span class="uhp-divider"></span>
                    <button class="uhp-tbtn" data-cmd="link" title="Link"><i class="fa-solid fa-link"></i></button>
                    <button class="uhp-tbtn" data-cmd="insertUnorderedList" title="Bulleted list"><i class="fa-solid fa-list-ul"></i></button>
                    <button class="uhp-tbtn" data-cmd="insertOrderedList" title="Numbered list"><i class="fa-solid fa-list-ol"></i></button>
                    <button class="uhp-tbtn" data-cmd="blockquote" title="Quote"><i class="fa-solid fa-quote-left"></i></button>
                    <button class="uhp-tbtn" data-cmd="code" title="Code"><i class="fa-solid fa-code"></i></button>
                  </div>
                </div>

                <!-- WYSIWYG editor surface -->
                <div class="uhp-editor" id="uhpEditor" contenteditable="true" spellcheck="true"
                     placeholder="Join Conversation — Start your reply…"></div>
                <textarea id="uhpReplyText" class="_hide"></textarea> {{-- kept for form compatibility --}}

                <div class="uhp-reply-footer">
                  <div class="uhp-rleft">
                    {{-- No image upload as requested --}}
                    <div></div>
                    <div></div>
                  </div>
                  <div class="uhp-rright">
                    <button class="uhp-ghost" id="uhpCancelReply">Cancel</button>
                    <button class="uhp-accent" id="uhpSubmitReply">Comment</button>
                  </div>
                </div>
                <div class="_hide" id="uhpReplyingTo" style="margin-top:6px;color:#aaa;font-size:12px"></div>
              </div>
            </div>

            <!-- threaded comments -->
            <ul class="uhp-clist" id="uhpComments"></ul>

            <div class="uhp-center">
              <button class="uhp-ghost" id="uhpLoadMore"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Load more</span></button>
            </div>
          </div>
        </section>
      </div>

      <!-- Sidebar -->
      <aside class="uhp-side">
        <div class="uhp-card">
          <div class="uhp-sec-title">Post Creator</div>
          <div class="uhp-side-row">
            <div class="uhp-avatar lg" id="uhpSideAva"><i class="fa-regular fa-user"></i></div>
            <div>
              <div class="uhp-authorname" id="uhpSideAuthor"></div>
              <div class="uhp-muted xs">Making playbooks for builders</div>
            </div>
          </div>
          <a id="uhpViewProfile" class="uhp-accent uhp-block" href="#">View Profile</a>

        </div>

        <div class="uhp-card">
          <div class="uhp-sec-title">Related Tags</div>
          <div class="uhp-tags" id="uhpRelatedTags"></div>
        </div>

        <div class="uhp-card">
          <div class="uhp-sec-title">Trending Discussions</div>
          <ul class="uhp-trending" id="uhpTrending"></ul>
        </div>

        <div class="uhp-card">
          <div class="uhp-sec-title">Safety</div>
          <button class="uhp-link" id="uhpReportSide">Report this Post</button>
        </div>
      </aside>
    </div>
  </div>

  <!-- Lightbox -->
  <div class="uhp-lightbox" id="uhpLightbox" aria-hidden="true">
    <button class="uhp-lightbox-close" id="uhpLightboxClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    <img id="uhpLightboxImg" alt="zoom">
  </div>
</section>
<!-- ============== /ULTRA HUSTLE — POST THREAD PAGE ============== -->

<!-- Toast -->
<div id="uhToast" class="uh-toast"><span class="uh-dot"></span><span class="uh-txt">Saved</span></div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const THREAD_ID = {{ (int)($threadId ?? request()->route('thread')?->id ?? 0) }};

const ROUTES = {
  data:     '{{ url('/forum') }}/'+THREAD_ID+'/data',
  like:     '{{ url('/forum') }}/'+THREAD_ID+'/like',
  save:     '{{ url('/forum') }}/'+THREAD_ID+'/save',
  share:    '{{ url('/forum') }}/'+THREAD_ID+'/share',
  report:   '{{ url('/forum') }}/'+THREAD_ID+'/report',
  follow:   (uid)=> '{{ url('/forum/follow') }}/'+uid,
  pollVote: (pid)=> '{{ url('/forum/poll') }}/'+pid+'/vote',
  comments: '{{ url('/forum') }}/'+THREAD_ID+'/comments',
  commentStore: '{{ url('/forum') }}/'+THREAD_ID+'/comments',
  commentLike: (cid)=> '{{ url('/forum/comments') }}/'+cid+'/like',
  forumHome: '{{ route('forum') }}',
};

function toast(msg, type='ok'){
  const t = document.getElementById('uhToast');
  t.querySelector('.uh-txt').textContent = msg;
  t.classList.toggle('error', type==='error');
  t.classList.add('show');
  clearTimeout(t._h);
  t._h = setTimeout(()=> t.classList.remove('show'), 1800);
}
function needLogin(){ if(typeof goHomeAndOpenLogin==='function'){ goHomeAndOpenLogin(); } else { toast('Login required','error'); } }

/* ================= Helpers ================= */
const $ = (s, r=document)=>r.querySelector(s);
const $all = (s, r=document)=>Array.from(r.querySelectorAll(s));
const fmt = (n)=> n>=1_000_000? (n/1_000_000).toFixed(n%1_000_000?1:0)+"M"
                : n>=1_000? (n/1_000).toFixed(n%1_000?1:0)+"K" : String(n);
const initial = (name)=> (name?.replace(/[^a-zA-Z0-9]/g,"").trim()[0] || "U").toUpperCase();
function setAvatar(el, url, name='U', size='32px'){
  if(!el) return;
  if(url){
    el.innerHTML = `<img src="${url}" alt="${name}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
  }else{
    el.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#2A3A00;color:var(--accent,#CEFF1B);font-weight:700;border-radius:50%">${initial(name)}</div>`;
  }
}


/* ================= State ================= */
let POST = null;
let COMMENTS_NEXT = null;
let REPLY_PARENT = null; // for nested reply
let _scrollHashTried = false; // guard to avoid loops

/* ================= Breadcrumb ================= */
function renderBreadcrumb(){
  const bc = $("#uhpBreadcrumb");
  if(!POST) return;
  const cat = POST.community || 'Forum';
  bc.innerHTML = [
    `<a href="${ROUTES.forumHome}">Forum</a>`,
    `<span class="sep">›</span>`,
    `<a href="${ROUTES.forumHome}?category=${encodeURIComponent(cat)}">${cat}</a>`,
    `<span class="sep">›</span>`,
    `<span class="uhp-bc-item ellip" title="${POST.title}">${POST.title}</span>`,
  ].join("");
}

/* ================= Post Render ================= */
function renderPost(){
  if(!POST) return;

  // Author line
  setAvatar($("#uhpAuthorAva"), POST.author.avatar, POST.author.name);
  $("#uhpAuthorMeta").innerHTML = `
    <div class="uhp-cmeta">
      <span class="uhp-cauthor">${POST.author.name}</span>
      <span class="muted">• ${POST.time}</span>
      <span class="muted">• ${POST.community}</span>
    </div>
  `;

  $("#uhpTitle").textContent = POST.title;
  $("#uhpTags").innerHTML = (POST.tags||[]).map(t=>`<span class="uhp-tag">#${t}</span>`).join("");
  $("#uhpBody").innerHTML = POST.bodyHtml || '';

  // Media (image or video)
  const mw = $("#uhpMediaWrap");
  const img = $("#uhpMediaImg");
  const vid = $("#uhpMediaVideo");
  if (POST.media?.type === 'image' && POST.media.url) {
    mw.classList.remove('_hide');
    img.src = POST.media.url; img.classList.remove('_hide');
    vid.classList.add('_hide');
  } else if (POST.media?.type === 'video' && POST.media.url) {
    mw.classList.remove('_hide');
    img.classList.add('_hide');
    vid.classList.remove('_hide');
    vid.src = POST.media.url;
    if (POST.media.poster) vid.setAttribute('poster', POST.media.poster);
  } else {
    mw.classList.add('_hide');
  }

  // Poll (if any)
  renderPoll();

  // CTA / Tip
  $("#uhpCta").textContent = POST.cta || '';
  $("#uhpTip").classList.toggle('_hide', !POST.cta); // keep or hide

  // Reactions row
  renderReactions();

  // Sidebar
  setAvatar($("#uhpSideAva"), POST.author.avatar, POST.author.name);
  $("#uhpSideAuthor").textContent = POST.author.name;
  $("#uhpRelatedTags").innerHTML = [...(POST.tags||[]), "Photography", "Prompting"].map(t=>`<span class="uhp-tag compact">#${t}</span>`).join("");

  // Build the user profile URL using Laravel's route stub and the author id
const viewProfile = document.getElementById('uhpViewProfile');
if (viewProfile && POST?.author?.id) {
  // Blade generates /user/__UID__, then we replace the placeholder in JS
  const userRoute = `{{ route('user.details', ['id' => '__UID__']) }}`.replace('__UID__', POST.author.id);
  viewProfile.href = userRoute;
}


  const trend = (POST.trending && POST.trending.length) ? POST.trending
                : ["Best phone camera LUTs","Client agreed to $5k retainer","Quick win: LinkedIn carousels"].map((t,i)=>({id:i+1,title:t}));
  $("#uhpTrending").innerHTML = trend.slice(0,5).map(t=>`<li>• <a href="{{ url('/forum') }}/${t.id}" style="color:#e5e5e5;text-decoration:none">${t.title}</a></li>`).join("");

  // Follow initial state
  const fbtn = $("#uhpFollowBtn");
  if (typeof POST.author_followed !== 'undefined') {
    fbtn.textContent = POST.author_followed ? 'Followed' : 'Follow';
    fbtn.classList.toggle('active', !!POST.author_followed);
  }

  // Topbar save state
  syncTopbarIcons();
}

/* ================= Poll ================= */
function renderPoll(){
  const host = $("#uhpPoll"); host.innerHTML = ''; host.classList.add('_hide');
  if (!POST?.poll) return;

  const poll = POST.poll;
  host.classList.remove('_hide');

  if (!poll.voted) {
    const type = poll.multiple ? 'checkbox' : 'radio';
    host.innerHTML = `
      <div class="uhp-meta">Poll — ${poll.multiple ? 'Choose multiple' : 'Choose one'}</div>
      <form id="uhpPollForm" style="display:grid; gap:8px">
        ${poll.options.map(o=>`
          <label class="opt pre" data-id="${o.id}">
            <input type="${type}" name="opt" value="${o.id}">
            <span>${o.label}</span>
          </label>
        `).join('')}
        <div style="display:flex; gap:8px; justify-content:space-between; align-items:center">
          <div class="uhp-meta" id="uhpPollCount">0 selected</div>
          <div>
            <button type="button" class="uhp-ghost" id="uhpPollCancel">Cancel</button>
            <button type="submit" class="uhp-accent" id="uhpPollSubmit" disabled>Vote</button>
          </div>
        </div>
      </form>
    `;
    const form = $("#uhpPollForm");
    const submit = $("#uhpPollSubmit");
    const counter = $("#uhpPollCount");
    const updateState = ()=>{
      const checked = $all('input[name="opt"]:checked', form);
      counter.textContent = `${checked.length} selected`;
      submit.disabled = checked.length===0;
      const ids = new Set(checked.map(c=>c.value));
      $all('.opt.pre', form).forEach(l=> l.classList.toggle('active', ids.has(l.dataset.id)));
    };
    form.addEventListener('change', updateState); updateState();
    $("#uhpPollCancel")?.addEventListener('click', ()=> renderPoll());
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const ids = $all('input[name="opt"]:checked', form).map(i=>Number(i.value));
      if (!ids.length) { toast('Select at least one','error'); return; }
      try{
        const res = await fetch(ROUTES.pollVote(poll.id), {
          method:'POST',
          headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'},
          body: JSON.stringify({ option_ids: ids })
        });
        if (!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('vote failed'); }
        const json = await res.json();
        POST.poll.voted = true;
        POST.poll.total = json.total;
        POST.poll.options = json.options;
        renderPoll();
        toast('Vote recorded');
      }catch(err){ console.error(err); toast('Failed to vote','error'); }
    });
  } else {
    host.innerHTML = `
      <div class="uhp-meta">Poll results — ${POST.poll.total} votes</div>
      ${POST.poll.options.map(o=>{
        const pct = POST.poll.total ? Math.round((o.votes / POST.poll.total) * 100) : 0;
        return `
          <div class="opt">
            <div style="display:flex; justify-content:space-between; font-size:14px">
              <span>${o.label}</span>
              <span>${pct}%</span>
            </div>
            <div class="uhp-pbar"><div class="uhp-pfill" style="width:${pct}%"></div></div>
          </div>
        `;
      }).join('')}
    `;
  }
}

/* ================= Reactions ================= */
function renderReactions(){
  const host = $("#uhpReactions");
  const likedCls = POST.liked ? ' active' : '';
  const savedSolid = POST.saved ? 'fa-solid' : 'fa-regular';

  host.innerHTML = `
    <button class="uhp-chipbtn uhp-like ${likedCls}" id="uhpLikeBtn" data-base="${POST.upvotes}">
      <i class="${POST.liked?'fa-solid':'fa-regular'} fa-heart"></i><span class="txt">Like</span><span class="num">${fmt(POST.upvotes)}</span>
    </button>
    <div class="uhp-chipbtn" id="uhpJumpComments"><i class="fa-regular fa-message"></i><span>Comment</span>${POST.comments}</div>
    <button class="uhp-ghost" id="uhpShareBtn"><i class="fa-solid fa-share-nodes"></i><span>Share</span></button>
    <button class="uhp-chipbtn" id="uhpSaveBtn"><i class="${savedSolid} fa-bookmark"></i><span>Save</span></button>
  `;

  $("#uhpLikeBtn")?.addEventListener('click', async ()=>{
    try{
      const res = await fetch(ROUTES.like, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}});
      if(!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('like failed'); }
      const json = await res.json();
      POST.liked = !!json.liked;
      POST.upvotes = json.count;
      const btn = $("#uhpLikeBtn");
      btn.classList.toggle('active', POST.liked);
      const icon = btn.querySelector('i'); icon.className = (POST.liked?'fa-solid':'fa-regular')+' fa-heart';
      btn.querySelector('.num').textContent = fmt(POST.upvotes);
    }catch(e){ toast('Failed to like','error'); }
  });

  // SAVE (bottom button uses the shared handler)
  $("#uhpSaveBtn")?.addEventListener('click', toggleSave);

  $("#uhpShareBtn")?.addEventListener('click', shareLink);
  $("#uhpJumpComments")?.addEventListener('click', ()=>{ $("#uhpCommentsArea")?.scrollIntoView({behavior:'smooth', block:'start'}); });
}

function syncTopbarIcons(){
  const saveIcon = $("#uhpSaveTop i");
  if (saveIcon) saveIcon.className = (POST.saved?'fa-solid':'fa-regular')+' fa-bookmark';
}
document.getElementById('uhpSaveTop')?.addEventListener('click', toggleSave);


async function toggleSave(){
  try{
    const res = await fetch(ROUTES.save, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}
    });
    if(!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('save failed'); }
    const json = await res.json();
    POST.saved = !!json.saved;

    // update both icons (bottom + top)
    const bottomIcon = $("#uhpSaveBtn i");
    if (bottomIcon) bottomIcon.className = (POST.saved?'fa-solid':'fa-regular')+' fa-bookmark';
    syncTopbarIcons();

    toast(POST.saved ? 'Saved' : 'Removed from saved');
  }catch(e){ toast('Failed to save','error'); }
}


/* ================= Share (copy + Web Share API) ================= */
async function shareOut(url, title) {
  let shared = false;
  if (navigator.share) {
    try {
      await navigator.share({ title: title || document.title, url });
      shared = true;
    } catch (_) { /* user canceled or not supported; continue to copy */ }
  }
  try { await navigator.clipboard.writeText(url); } catch {}
  toast(shared ? 'Shared & link copied' : 'Link copied');
}

// update post share to use helper
async function shareLink(){
  const url = location.href;
  await shareOut(url, POST?.title || document.title);
  try {
    await fetch(ROUTES.share, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
      body:new URLSearchParams({channel: (navigator.share?'web_share':'copy_link')})
    });
  } catch {}
}
$("#uhpShareTop")?.addEventListener('click', shareLink);
$("#uhpShareBtn")?.addEventListener('click', shareLink);

/* Report Modal flow */
const reportModal = document.getElementById('uhReportModal');
const reportReason = document.getElementById('uhReportReason');
document.getElementById('uhpReportTop')?.addEventListener('click', ()=>{ reportModal.style.display='flex'; });
document.getElementById('uhpReportSide')?.addEventListener('click', ()=>{ reportModal.style.display='flex'; });
document.getElementById('uhReportCancel')?.addEventListener('click', ()=>{ reportModal.style.display='none'; reportReason.value=''; });
document.getElementById('uhReportSubmit')?.addEventListener('click', async ()=>{
  const reason = reportReason.value || '';
  try{
    const res = await fetch(ROUTES.report, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'}, body: JSON.stringify({reason, notes:''})});
    if(!res.ok){ if(res.status===401){ reportModal.style.display='none'; needLogin(); return; } throw new Error('report failed'); }
    toast('Reported'); reportModal.style.display='none'; reportReason.value='';
  }catch{ toast('Failed to report','error'); }
});

async function followToggle(){
  try{
    const res = await fetch(ROUTES.follow(POST.author.id), {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}});
    if(!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('follow failed'); }
    const json = await res.json();
    const followed = !!json.followed;
    const el = $("#uhpFollowBtn");
    el.textContent = followed ? 'Followed' : 'Follow';
    el.classList.toggle('active', followed);
    POST.author_followed = followed;
  }catch{ toast('Failed to follow','error'); }
}
$("#uhpFollowBtn")?.addEventListener('click', followToggle);

/* ================= Lightbox ================= */
function wireLightbox(){
  const open = ()=>{ $("#uhpLightbox").classList.add("open"); };
  const close = ()=>{ $("#uhpLightbox").classList.remove("open"); };
  $("#uhpMediaBtn")?.addEventListener("click", ()=>{
    if(POST.media?.type==='image' && POST.media.url){
      $("#uhpLightboxImg").src = POST.media.url;
      open();
    }
  });
  $("#uhpLightboxClose")?.addEventListener("click", close);
  $("#uhpLightbox")?.addEventListener("click", (e)=>{ if(e.target.id==="uhpLightbox") close(); });
  document.addEventListener("keydown", (e)=>{ if(e.key==="Escape") $("#uhpLightbox").classList.remove("open"); });
}
wireLightbox();

/* ================= WYSIWYG (execCommand) ================= */
(function wysiwyg(){
  const ed = $("#uhpEditor");
  const toolbar = $all(".uhp-tbtn");
  document.execCommand('defaultParagraphSeparator', false, 'p');

  function apply(cmd){
    switch(cmd){
      case 'link':
        const url = prompt('Enter URL (https://...)');
        if(!url) return;
        document.execCommand('createLink', false, url);
        break;
      case 'blockquote':
        document.execCommand('formatBlock', false, 'blockquote');
        break;
      case 'code': {
        const sel = window.getSelection();
        if(!sel.rangeCount) return;
        const range = sel.getRangeAt(0);
        const span = document.createElement('code');
        span.textContent = range.toString();
        range.deleteContents();
        range.insertNode(span);
        range.setStartAfter(span); range.setEndAfter(span);
        sel.removeAllRanges(); sel.addRange(range);
        break;
      }
      default:
        document.execCommand(cmd, false, null);
    }
    ed.focus();
  }

  toolbar.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const cmd = btn.dataset.cmd;
      apply(cmd);
    });
  });

  $("#uhpSubmitReply")?.addEventListener('click', ()=> {
    $("#uhpReplyText").value = ed.innerHTML;
  });
  $("#uhpCancelReply")?.addEventListener('click', ()=>{
    ed.innerHTML = ''; $("#uhpReplyText").value = '';
    REPLY_PARENT = null; $("#uhpReplyingTo").classList.add('_hide');
  });
})();

/* ================= Comments ================= */
function commentRow(n){
  return `
    <li class="uhp-comment" data-id="${n.id}" id="c${n.id}">
      <div class="uhp-avatar sm">${n.avatar ? `<img src="${n.avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">` : `<i class="fa-regular fa-user"></i>`}</div>
      <div class="uhp-cbox">
        <div class="uhp-cmeta"><span class="uhp-cauthor">${n.author}</span><span>• ${n.time}</span></div>
        <div class="uhp-cbody">${n.content}</div>
        <div class="uhp-cactions">
          <button class="uhp-chipbtn uhp-likechip" data-id="${n.id}" data-base="${n.reactions.up}">
            <i class="fa-regular fa-heart"></i><span>Like</span><span class="num">${n.reactions.up}</span>
          </button>
          <button class="uhp-ghost uhp-replyto"><i class="fa-regular fa-message"></i><span class="xs">Reply</span></button>
          <button class="uhp-ghost uhp-sharecmt"><i class="fa-solid fa-share-nodes"></i><span class="xs">Share</span></button>
          <button class="uhp-ghost uhp-savecmt" disabled><i class="fa-regular fa-bookmark"></i><span class="xs">Save</span></button>
          ${emojiRow(n.reactions.emojis)}
        </div>

        ${
          n.has_children
          ? `
            <div class="uhp-threaded" style="margin-top:8px">
              <ul class="uhp-clist" id="replies-${n.id}"></ul>
              <div class="uhp-center">
                <button class="uhp-ghost uhp-load-replies" data-id="${n.id}" data-next=""
                  title="Load replies"><i class="fa-solid fa-arrow-turn-down"></i> <span>View replies</span></button>
              </div>
            </div>
          `
          : ``
        }
      </div>
    </li>
  `;
}

function renderReplies(parentId, list, append=true){
  const host = document.getElementById(`replies-${parentId}`);
  if(!host) return;
  const html = list.map(commentRow).join("");
  if(append) host.insertAdjacentHTML('beforeend', html); else host.innerHTML = html;
  // Make the freshly inserted replies interactive too:
  wireCommentActions(host);
}


function emojiRow(map){
  const entries = Object.entries(map||{});
  if(!entries.length) return "";
  return `<div class="uhp-emojis">${entries.map(([k,v])=>`<span class="uhp-chipbtn" style="padding:4px 8px;font-size:12px;">${k} ${v}</span>`).join("")}</div>`;
}
function wireCommentActions(host){
  $all('.uhp-likechip', host).forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = Number(btn.dataset.id);
      try{
        const res = await fetch(ROUTES.commentLike(id), {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}});
        if(!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('c like failed'); }
        const json = await res.json();
        const num = btn.querySelector('.num');
        num.textContent = String(json.count);
        btn.classList.toggle('active', !!json.liked);
        const icon = btn.querySelector('i'); icon.className = (json.liked?'fa-solid':'fa-regular')+' fa-heart';
      }catch{ toast('Failed to like comment','error'); }
    });
  });

  // lazy load replies per comment (facebook-like)
$all('.uhp-load-replies', host).forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const parentId = Number(btn.dataset.id);
    // If we already have a next page stored, use it; else query first page:
    let url = btn.dataset.next || (ROUTES.comments + `?parent_id=${parentId}&per=5`);

    try{
      const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(!r.ok) throw new Error('replies failed');
      const j = await r.json();

      // shape: {data:[...], next:url|null}
      renderReplies(parentId, j.data || [], true);

      if (j.next) {
        btn.dataset.next = j.next;
        btn.querySelector('span').textContent = 'Load more replies';
      } else {
        // no more pages -> hide the button
        btn.closest('.uhp-center')?.classList.add('_hide');
      }
    }catch(e){
      console.error(e);
      toast('Failed to load replies','error');
    }
  });
});


  $all('.uhp-replyto', host).forEach(b=>{
    b.addEventListener('click', (e)=>{
      const li = e.currentTarget.closest('.uhp-comment');
      const cid = Number(li?.dataset.id||0);
      REPLY_PARENT = cid || null;
      const name = li?.querySelector('.uhp-cauthor')?.textContent || '';
      const inf = $("#uhpReplyingTo");
      inf.textContent = REPLY_PARENT ? `Replying to ${name}` : '';
      inf.classList.toggle('_hide', !REPLY_PARENT);
      $("#uhpEditor").focus();
      $("#uhpCommentsArea")?.scrollIntoView({behavior:'smooth', block:'start'});
    });
  });

  // comment share -> web share + copy; includes #c{id}
  $all('.uhp-sharecmt', host).forEach(b=>{
    b.addEventListener('click', async (e)=>{
      const li = e.currentTarget.closest('.uhp-comment');
      const cid = Number(li?.dataset.id||0);
      const name = li?.querySelector('.uhp-cauthor')?.textContent || 'Comment';
      const base = location.href.split('#')[0]; // drop any existing hash
      const link = `${base}#c${cid}`;
      await shareOut(link, `Comment by ${name} on ${POST?.title || document.title}`);
    });
  });
}
function renderComments(list, append=false){
  const host = $("#uhpComments");
  const html = list.map(commentRow).join("");
  if(append){
    host.insertAdjacentHTML('beforeend', html);
    wireCommentActions(host);
  } else {
    host.innerHTML = html;
    wireCommentActions(host);
  }
}

/* ================= Comments Fetch / Submit ================= */
async function loadInitial(){
  const res = await fetch(ROUTES.data, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  if(!res.ok) throw new Error('load failed');
  const json = await res.json();
  POST = json.post || {};
  if (json.trending) POST.trending = json.trending;

  renderBreadcrumb();
  renderPost();

  $("#uhpCommentsTotal").textContent = `${POST.comments} total`;
  renderComments(json.comments || []);
  COMMENTS_NEXT = json.comments_next;

  // Load more: handle both /comments and /data pagers
  $("#uhpLoadMore")?.addEventListener('click', handleLoadMore);
  if(!COMMENTS_NEXT) $("#uhpLoadMore").classList.add('_hide');

  // if landed on a #c{id} link, try to scroll to it (may need to load more pages)
  maybeScrollToHash();
}

async function loadMoreOnce(){
  if(!COMMENTS_NEXT) return false;
  const r = await fetch(COMMENTS_NEXT, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  if(!r.ok) throw new Error('more failed');
  const j = await r.json();

  if (Array.isArray(j.data)) {
    renderComments(j.data || [], true);
    COMMENTS_NEXT = j.next || null;
  } else if (Array.isArray(j.comments)) {
    renderComments(j.comments || [], true);
    COMMENTS_NEXT = j.comments_next || null;
  } else {
    COMMENTS_NEXT = null;
  }
  $("#uhpLoadMore").classList.toggle('_hide', !COMMENTS_NEXT);
  return !!COMMENTS_NEXT;
}

async function handleLoadMore(){
  try{ await loadMoreOnce(); }
  catch{ toast('Failed to load more','error'); }
}

// deep-link: ensure #c{id} visible, load more pages until found or exhausted
async function ensureCommentVisible(cid){
  const targetId = 'c' + cid;
  for (let i=0; i<30; i++){ // safety cap
    const el = document.getElementById(targetId);
    if (el) {
      el.scrollIntoView({behavior:'smooth', block:'center'});
      el.classList.add('uhp-highlight');
      el.style.animation = 'uhPulse 1.2s ease 2';
      setTimeout(()=>{ el.classList.remove('uhp-highlight'); el.style.animation='none'; }, 2600);
      return true;
    }
    if (!COMMENTS_NEXT) return false;
    try { await loadMoreOnce(); } catch { return false; }
  }
  return false;
}

function maybeScrollToHash(){
  if (_scrollHashTried) return;
  const m = String(location.hash||'').match(/^#c(\d+)$/);
  if (!m) return;
  _scrollHashTried = true;
  const cid = Number(m[1]);
  // slight delay to ensure first render committed
  setTimeout(()=>{ ensureCommentVisible(cid); }, 60);
}

// also react when hash changes after load
window.addEventListener('hashchange', ()=>{ _scrollHashTried = false; maybeScrollToHash(); });

// Submit comment
$("#uhpSubmitReply")?.addEventListener('click', async ()=>{
  const html = $("#uhpEditor").innerHTML.trim();
  if(!html){ toast('Write something','error'); return; }
  try{
    const body = { body_html: html }; if(REPLY_PARENT) body.parent_id = REPLY_PARENT;
    const res = await fetch(ROUTES.commentStore, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    if(!res.ok){ if(res.status===401){ needLogin(); return; } throw new Error('cmt failed'); }
    $("#uhpEditor").innerHTML=''; $("#uhpReplyText").value='';
    REPLY_PARENT = null; $("#uhpReplyingTo").classList.add('_hide');
    toast('Comment posted');

    // Reload first page via comments endpoint for freshness
    const r = await fetch(ROUTES.comments+'?per=10', {headers:{'X-Requested-With':'XMLHttpRequest'}});
    if(r.ok){
      const j = await r.json();
      renderComments(j.data||[], false);
      COMMENTS_NEXT = j.next || null;
      $("#uhpLoadMore").classList.toggle('_hide', !COMMENTS_NEXT);
      POST.comments += 1;
      renderReactions();
      $("#uhpCommentsTotal").textContent = `${POST.comments} total`;
    }
  }catch{ toast('Failed to comment','error'); }
});

$("#uhpCancelReply")?.addEventListener('click', ()=>{
  $("#uhpEditor").innerHTML=''; $("#uhpReplyText").value='';
  REPLY_PARENT = null; $("#uhpReplyingTo").classList.add('_hide');
});

/* ================= Back btn ================= */
$("#uhpBackBtn")?.addEventListener("click", ()=> history.back());

/* ================= Init ================= */
(async function init(){
  try{
    await loadInitial();
  }catch(e){
    console.error(e);
    toast('Failed to load post','error');
  }
})();
</script>

@include('common.footer')
