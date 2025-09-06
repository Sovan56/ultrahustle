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

    @if($fromService)
      <button class="btn btn-outline-secondary btn-sm" title="Contract">
        <i class="fa fa-file-signature"></i>
      </button>
    @endif
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

  <div class="card-footer">
    @if($conversationId)
      <form wire:submit.prevent="send" class="d-flex align-items-center gap-2" id="lw-chat-form">
        <label class="btn btn-outline-secondary mb-0">
          <i class="fa fa-paperclip"></i>
          <input type="file" wire:model="file" class="d-none">
        </label>
        <input type="text" class="form-control" placeholder="Type a message"
               wire:model.live="body"
               oninput="window._chatWhisperTyping && window._chatWhisperTyping()"
               onfocus="window._chatMarkSeen && window._chatMarkSeen()"
               wire:keydown.enter.prevent="send">
        <button class="btn btn-primary" type="submit">
          <i class="far fa-paper-plane"></i>
        </button>
        @if($fromService)
          <button type="button" class="btn btn-outline-secondary" title="Contract">
            <i class="fa fa-file-signature"></i>
          </button>
        @endif
      </form>
      @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      @error('body') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    @endif
  </div>

  {{-- Fullscreen image modal (simple) --}}
  <div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark">
        <img id="imgModalSrc" src="" alt="" style="width:100%;height:auto">
      </div>
    </div>
  </div>

  {{-- Echo bindings for this conversation --}}
  <script>
    (() => {
      const convId   = @json($conversationId);
      const partnerId= @json($partner?->id);
      const chatBase = @json(url('/chat')); // <-- build URLs at runtime (no missing param)

      if (!convId) return;
      if (!window.ChatEcho) return;

      // cleanup previous subs if any
      if (window._convPriv) { try { window._convPriv.unsubscribe(); } catch(e){} }
      if (window._convPresence) { try { window._convPresence.unsubscribe(); } catch(e){} }
      if (window._userPresence) { try { window._userPresence.unsubscribe(); } catch(e){} }

      // private conversation events
      window._convPriv = window.ChatEcho.subscribeConversation(convId, {
        onNew: (e) => { @this.dispatch('chat-js:new-message', e) },
        onDelivered: (e) => { @this.dispatch('chat-js:delivered', e) },
        onSeen: (e) => { @this.dispatch('chat-js:seen', e) },
      });

      // presence (typing)
      window._convPresence = window.ChatEcho.subscribePresence(convId, {
        onTyping(payload) {
          @this.dispatch('chat-js:typing', payload);
        }
      });

      // whisper helper (throttled)
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

      // user presence to show online/offline
      if (partnerId) {
        window._userPresence = window.ChatEcho.subscribeUserPresence(partnerId, {
          onOnlineChange: (isOnline) => { @this.dispatch('chat-js:online', isOnline); }
        });
      }

      // mark seen helper from input focus — build URL here to avoid missing param
      window._chatMarkSeen = function(){
        fetch(`${chatBase}/${convId}/seen`, {
          method:'POST',
          headers:{'X-CSRF-TOKEN':document.querySelector("meta[name='csrf-token']").content}
        });
      };

      // (optional) mark delivered on mount in case receiver loaded late
      fetch(`${chatBase}/${convId}/delivered`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector("meta[name='csrf-token']").content}
      });

      // fullscreen image
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
