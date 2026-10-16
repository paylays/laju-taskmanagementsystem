import axios from 'axios'
import { auth } from '../stores/auth'

export const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

const unauthorizedListeners = new Set()

export function onUnauthorized(listener) {
  unauthorizedListeners.add(listener)
  return () => unauthorizedListeners.delete(listener)
}

http.interceptors.request.use((config) => {
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    // A 401 on the login call itself means wrong credentials, not an expired session.
    const isLogin = error.config?.url === '/auth/login'
    if (error.response?.status === 401 && !isLogin && auth.token) {
      auth.clear()
      unauthorizedListeners.forEach((listener) => listener())
    }
    return Promise.reject(error)
  },
)

export function errorMessage(error, fallback = 'Something went wrong. Try again.') {
  if (error?.response) {
    const errors = error.response.data?.errors
    if (errors) {
      return Object.values(errors)[0][0]
    }
    return error.response.data?.message || fallback
  }
  if (error?.code === 'ERR_NETWORK' || error?.request) {
    return 'Cannot reach the server. Check your connection and that the API is running.'
  }
  return fallback
}

export function fieldErrors(error) {
  return error?.response?.status === 422 ? (error.response.data.errors ?? {}) : {}
}
