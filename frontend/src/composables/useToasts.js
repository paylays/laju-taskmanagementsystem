import { reactive, ref } from 'vue'

const toasts = reactive([])
let nextId = 1

// A modal <dialog> sits in the browser's top layer and makes the rest of the page inert,
// so while one is open the toasts must render inside it to stay visible and clickable.
const host = ref(null)

export function setToastHost(element) {
  host.value = element
}

export function clearToastHost(element) {
  if (host.value === element) host.value = null
}

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
  return { toasts, toast, dismissToast, host }
}
