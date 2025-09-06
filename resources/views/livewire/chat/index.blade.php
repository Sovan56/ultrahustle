<div class="row">
  <div class="col-12 col-md-4 col-lg-3 mb-3">
    @livewire('chat.sidebar', key('chat-sidebar'))
  </div>

  <div class="col-12 col-md-8 col-lg-6 mb-3">
    @livewire('chat.chat-window', ['conversationId' => $openConversationId], key('chat-window-'.$openConversationId))
  </div>

  <div class="col-12 col-lg-3 mb-3">
    @livewire('chat.profile-panel', ['conversationId' => $openConversationId], key('chat-profile-'.$openConversationId))
  </div>
</div>
