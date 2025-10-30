<div class="card h-100">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      @if($partner)
        <img src="{{ \App\Support\Avatar::url($partner) }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover" onerror="this.src='https://placehold.co/36x36?text=U'">
        <div>
          <div class="fw-semibold">{{ $partner->name ?? trim(($partner->first_name ?? '').' '.($partner->last_name ?? '')) }}</div>
          <small class="text-muted">
            @if($partnerOnline)
              <span class="badge bg-success">online</span>
            @else
              <span class="badge bg-secondary">offline</span>
            @endif
            <span class="ms-2" x-data="{t: @entangle('partnerTyping')}">
              <span x-show="t" class="text-primary">typing…</span>
            </span>
          </small>
        </div>
      @else
        <div class="fw-semibold">Select a chat</div>
      @endif
    </div>
  </div>

  <div class="card-body" style="height:60vh;overflow:auto" id="chat-scroll">
    @if(!$conversationId)
      <div class="h-100 d-flex align-items-center justify-content-center text-muted">Pick a conversation from the left.</div>
    @else
      @foreach($messages as $m)
        <div class="d-flex mb-2 {{ $m['me'] ? 'justify-content-end' : 'justify-content-start' }}">
          <div class="d-flex {{ $m['me'] ? 'flex-row-reverse' : '' }} align-items-end gap-2" style="max-width:80%;">
            <img src="{{ $m['avatar'] }}" class="rounded-circle" style="width:28px;height:28px;object-fit:cover" onerror="this.src='https://placehold.co/28x28?text=U'">
            <div class="p-2 rounded {{ $m['me'] ? 'bg-primary text-white' : 'bg-dark' }}">
              @if($m['body'])
                <div style="color:white; white-space:pre-wrap;word-break:break-word;">{{ $m['body'] }}</div>
              @endif

              @if($m['file'])
                @if($m['file']['is_image'])
                  <a href="{{ $m['file']['url'] }}" target="_blank" class="d-inline-block mt-1 chat-image">
                    <img src="{{ $m['file']['url'] }}" alt="{{ $m['file']['name'] }}" style="max-width:240px;max-height:240px;border-radius:8px">
                  </a>
                @else
                  <div class="mt-1">
                    <a href="{{ $m['file']['url'] }}" download class="{{ $m['me'] ? 'text-white' : '' }} text-decoration-underline">
                      {{ $m['file']['name'] ?? 'Download file' }}
                    </a>
                    @if($m['file']['size'])
                      <small class="{{ $m['me'] ? 'text-white-50' : 'text-muted' }} ms-1">({{ number_format($m['file']['size']/1024/1024,2) }} MB)</small>
                    @endif
                  </div>
                @endif
              @endif

              <div class="d-flex justify-content-end mt-1">
                <small class="{{ $m['me'] ? 'text-white-50' : 'text-muted' }}">
                  {{ $m['time'] }}
                  @if($m['me'])
                    @if($m['status']==='sent')
                      <i class="far fa-clock ms-1" title="Sent"></i>
                    @elseif($m['status']==='delivered')
                      <i class="fa fa-check ms-1" title="Delivered"></i>
                    @elseif($m['status']==='seen')
                      <i class="fa fa-check-double ms-1" title="Seen"></i>
                    @endif
                  @endif
                </small>
              </div>
            </div>
          </div>
        </div>
      @endforeach
      <script>
        (function(){ const el = document.getElementById('chat-scroll'); el && (el.scrollTop = el.scrollHeight); })();
      </script>
    @endif
  </div>

  <div class="card-footer position-relative">
    {{-- ✅ Toast for errors --}}
    <div id="chat-error-toast"
         style="display:none;position:absolute;bottom:60px;left:50%;transform:translateX(-50%);
                background:black;color:red;padding:8px 16px;border-radius:6px;z-index:20;
                font-size:14px;">
    </div>

    @if($conversationId)
      <form wire:submit.prevent="send" class="d-flex align-items-center gap-2" id="lw-chat-form">
        <label class="btn btn-outline-secondary mb-0 position-relative">
          <i class="fa fa-paperclip"></i>
          <input type="file" wire:model="file" class="d-none" id="chat-file-input">
          {{-- ✅ Progress circle --}}
          <div id="upload-progress" style="display:none;position:absolute;top:-8px;right:-8px;
                width:22px;height:22px;border-radius:50%;border:3px solid #ccc;
                border-top-color:#007bff;animation:spin 1s linear infinite;"></div>
        </label>

        <input type="text" class="form-control" placeholder="Type a message"
               wire:model.live="body"
               oninput="window._chatWhisperTyping && window._chatWhisperTyping()"
               onfocus="window._chatMarkSeen && window._chatMarkSeen()"
               wire:keydown.enter.prevent="send">

        @if($partner)
          <a class="btn btn-outline-secondary" title="Contract"
             href="{{ route('service.contracts.create', [
                  'buyer' => $partner->id,
                  'product_id' => request()->query('product'),
                  'conversation_id' => $conversationId
             ]) }}">
            <i class="fa fa-file-signature"></i>
          </a>
        @endif

        <button class="btn btn-primary" type="submit">
          <i class="far fa-paper-plane"></i>
        </button>
      </form>

      {{-- keep normal validation text but JS toast also used --}}
      @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      @error('body') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    @endif
  </div>

  <div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark">
        <img id="imgModalSrc" src="" alt="" style="width:100%;height:auto">
      </div>
    </div>
  </div>

  <script>
    // --- Toast Error ---
    window.addEventListener('chat-error', e => {
      const el = document.getElementById('chat-error-toast');
      if (!el) return;
      el.textContent = e.detail;
      el.style.display = 'block';
      setTimeout(() => el.style.display = 'none', 3000);
    });

    // --- Upload Progress Simulation ---
    document.addEventListener('livewire-upload-start', () => {
      document.getElementById('upload-progress').style.display = 'block';
    });
    document.addEventListener('livewire-upload-finish', () => {
      document.getElementById('upload-progress').style.display = 'none';
    });
    document.addEventListener('livewire-upload-error', () => {
      document.getElementById('upload-progress').style.display = 'none';
    });

    // --- Add simple spin animation ---
    const style = document.createElement('style');
    style.innerHTML = `@keyframes spin {from{transform:rotate(0deg)}to{transform:rotate(360deg)}}`;
    document.head.appendChild(style);
  </script>

  {{-- Echo bindings remain unchanged --}}
  <script>
    (() => {
      const convId   = @json($conversationId);
      const partnerId= @json($partner?->id);
      const chatBase = @json(url('/chat'));

      if (!convId) return;
      if (!window.ChatEcho) return;

      if (window._convPriv) { try { window._convPriv.unsubscribe(); } catch(e){} }
      if (window._convPresence) { try { window._convPresence.unsubscribe(); } catch(e){} }
      if (window._userPresence) { try { window._userPresence.unsubscribe(); } catch(e){} }

      window._convPriv = window.ChatEcho.subscribeConversation(convId, {
        onNew: (e) => { @this.dispatch('chat-js:new-message', e) },
        onDelivered: (e) => { @this.dispatch('chat-js:delivered', e) },
        onSeen: (e) => { @this.dispatch('chat-js:seen', e) },
      });

      window._convPresence = window.ChatEcho.subscribePresence(convId, {
        onTyping(payload) {
          @this.dispatch('chat-js:typing', payload);
        }
      });

      let lastWhisper = 0;
      window._chatWhisperTyping = function(){
        const now = Date.now();
        if (now - lastWhisper < 1200) return;
        lastWhisper = now;
        try {
          window.Echo.join('presence.conversation.'+convId).whisper('typing', { typing: true });
          setTimeout(() => {
            window.Echo.join('presence.conversation.'+convId).whisper('typing', { typing: false });
          }, 1500);
        } catch(e){}
      };

      if (partnerId) {
        window._userPresence = window.ChatEcho.subscribeUserPresence(partnerId, {
          onOnlineChange: (isOnline) => { @this.dispatch('chat-js:online', isOnline); }
        });
      }

      window._chatMarkSeen = function(){
        fetch(`${chatBase}/${convId}/seen`, {
          method:'POST',
          headers:{'X-CSRF-TOKEN':document.querySelector("meta[name='csrf-token']").content}
        });
      };

      fetch(`${chatBase}/${convId}/delivered`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector("meta[name='csrf-token']").content}
      });

      document.querySelectorAll('.chat-image').forEach(a=>{
        a.addEventListener('click', (ev)=>{
          ev.preventDefault();
          const src = a.getAttribute('href');
          const img = document.getElementById('imgModalSrc');
          img.src = src;
          const m = new bootstrap.Modal(document.getElementById('imgModal'));
          m.show();
        });
      });
    })();
  </script>
</div>
