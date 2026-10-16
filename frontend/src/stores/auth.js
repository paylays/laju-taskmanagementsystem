import { reactive } from 'vue'

const STORAGE_KEY = 'lajur.session'

function readStorage() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
  } catch {
    return null
  }
}

export const auth = reactive({
  token: null,
  user: null,
  expiresAt: 0,

  get isSignedIn() {
    return Boolean(this.token) && Date.now() < this.expiresAt
  },

  get isAdmin() {
    return this.user?.role === 'admin'
  },

  load() {
    const saved = readStorage()
    if (saved?.token && Date.now() < saved.expiresAt) {
      Object.assign(this, saved)
    } else {
      this.clear()
    }
  },

  set({ access_token, expires_in, user }) {
    this.token = access_token
    this.user = user
    this.expiresAt = Date.now() + expires_in * 1000
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ token: this.token, user, expiresAt: this.expiresAt }))
  },

  clear() {
    this.token = null
    this.user = null
    this.expiresAt = 0
    localStorage.removeItem(STORAGE_KEY)
  },

  canEdit(task) {
    return this.isAdmin || task.created_by === this.user?.id || task.assigned_user_id === this.user?.id
  },

  canDelete(task) {
    return this.isAdmin || task.created_by === this.user?.id
  },
})
