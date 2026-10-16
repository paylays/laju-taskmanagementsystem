import { beforeEach, describe, expect, it, vi } from 'vitest'
import { auth } from '../../src/stores/auth'

const member = { id: 2, name: 'Rina', role: 'member' }

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear()
    auth.clear()
  })

  it('persists the session and restores it', () => {
    auth.set({ access_token: 'abc', expires_in: 3600, user: member })
    // Simulate a page reload: memory is empty, storage is not.
    Object.assign(auth, { token: null, user: null, expiresAt: 0 })

    auth.load()

    expect(auth.token).toBe('abc')
    expect(auth.user.name).toBe('Rina')
    expect(auth.isSignedIn).toBe(true)
  })

  it('drops an expired session on load', () => {
    vi.useFakeTimers()
    auth.set({ access_token: 'abc', expires_in: 60, user: member })
    vi.advanceTimersByTime(61_000)

    auth.load()

    expect(auth.isSignedIn).toBe(false)
    expect(localStorage.getItem('lajur.session')).toBeNull()
    vi.useRealTimers()
  })

  it('mirrors the server rules for editing and deleting', () => {
    auth.set({ access_token: 't', expires_in: 60, user: member })

    expect(auth.canEdit({ created_by: 2, assigned_user_id: null })).toBe(true)
    expect(auth.canEdit({ created_by: 1, assigned_user_id: 2 })).toBe(true)
    expect(auth.canEdit({ created_by: 1, assigned_user_id: 3 })).toBe(false)
    expect(auth.canDelete({ created_by: 1, assigned_user_id: 2 })).toBe(false)

    auth.user = { ...member, role: 'admin' }
    expect(auth.canDelete({ created_by: 1, assigned_user_id: 3 })).toBe(true)
  })
})
