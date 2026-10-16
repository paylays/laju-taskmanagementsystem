import { reactive } from 'vue'

const toasts = reactive([])
let nextId = 1

export function dismissToast(id) {
  const index = toasts.findIndex((t) => t.id === id)
  if (index !== -1) toasts.splice(index, 1)
}

/**
 * kind: 'success' | 'info' | 'error'. Errors stay until dismissed, since the reader may need time to act on them.
 */
export function toast(message, kind = 'info', timeout = 4000) {
  const id = nextId++
  toasts.push({ id, message, kind })
  if (kind !== 'error' && timeout > 0) {
    setTimeout(() => dismissToast(id), timeout)
  }
  return id
}

export function useToasts() {
  return { toasts, toast, dismissToast }
}
