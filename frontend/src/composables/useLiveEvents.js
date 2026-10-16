import { onBeforeUnmount, onMounted, ref } from 'vue'
import { auth } from '../stores/auth'

const EVENT_TYPES = [
  'task.created',
  'task.updated',
  'task.deleted',
  'comment.created',
  'attachment.created',
  'attachment.updated',
  'attachment.deleted',
]

// The server closes each SSE response quickly and the browser reconnects, so a single
// error is routine. Only report "reconnecting" if no connection opens within this window.
const GRACE_MS = 6000

/**
 * Subscribes to /api/stream for the lifetime of the calling component.
 * `handler(type, payload)` receives every event; `status` is 'connecting' | 'live' | 'reconnecting'.
 */
export function useLiveEvents(handler) {
  const status = ref('connecting')
  let source = null
  let graceTimer = null
  let retryTimer = null

  function connect() {
    if (!auth.token) return

    source = new EventSource(`/api/stream?token=${encodeURIComponent(auth.token)}`)

    source.onopen = () => {
      clearTimeout(graceTimer)
      graceTimer = null
      status.value = 'live'
    }

    source.onerror = () => {
      if (source.readyState === EventSource.CLOSED) {
        // The browser gave up (e.g. a 401 or the server is down). Retry on our own schedule.
        status.value = 'reconnecting'
        retryTimer = setTimeout(connect, GRACE_MS)
        return
      }
      graceTimer ??= setTimeout(() => (status.value = 'reconnecting'), GRACE_MS)
    }

    for (const type of EVENT_TYPES) {
      source.addEventListener(type, (event) => handler(type, JSON.parse(event.data)))
    }
  }

  function disconnect() {
    clearTimeout(graceTimer)
    clearTimeout(retryTimer)
    source?.close()
    source = null
  }

  onMounted(connect)
  onBeforeUnmount(disconnect)

  return { status }
}
