import Echo from 'laravel-echo'
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

// Main Echo instance (Reverb only)
window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  withCredentials: true,
  authEndpoint: '/broadcasting/auth',
  auth: { headers: { 'X-CSRF-TOKEN': csrf } },
})

// -------------------------------
// Chat-specific helper functions
// -------------------------------
window.ChatEcho = {
  subscribeConversation(convId, handlers = {}) {
    const ch = window.Echo.private(`conversation.${convId}`)
    ch.listen('.message.new', (e) => handlers.onNew && handlers.onNew(e))
    ch.listen('.message.delivered', (e) => handlers.onDelivered && handlers.onDelivered(e))
    ch.listen('.message.seen', (e) => handlers.onSeen && handlers.onSeen(e))
    return ch
  },

  subscribePresence(convId, handlers = {}) {
    const ch = window.Echo.join(`presence.conversation.${convId}`)
      .here((members) => handlers.onHere && handlers.onHere(members))
      .joining((m) => handlers.onJoin && handlers.onJoin(m))
      .leaving((m) => handlers.onLeave && handlers.onLeave(m))
      .listenForWhisper('typing', (payload) => handlers.onTyping && handlers.onTyping(payload))
    return ch
  },

  subscribeUserPresence(userId, handlers = {}) {
    const ch = window.Echo.join(`presence.user.${userId}`)
      .here((members) => handlers.onOnlineChange && handlers.onOnlineChange(members.length > 0))
      .joining(() => handlers.onOnlineChange && handlers.onOnlineChange(true))
      .leaving((members) => handlers.onOnlineChange && handlers.onOnlineChange(members.length > 0))
    return ch
  },
}
