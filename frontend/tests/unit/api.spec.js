import { AxiosError } from 'axios'
import MockAdapter from 'axios-mock-adapter'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '../../src/api'
import { errorMessage, fieldErrors, http, onUnauthorized } from '../../src/api/client'
import { auth } from '../../src/stores/auth'

const mock = new MockAdapter(http)

describe('API client', () => {
  beforeEach(() => {
    localStorage.clear()
    auth.set({ access_token: 'token-123', expires_in: 3600, user: { id: 1, role: 'admin' } })
  })

  afterEach(() => mock.reset())

  it('sends the bearer token and passes filters as query params', async () => {
    mock.onGet('/tasks').reply((config) => {
      expect(config.headers.Authorization).toBe('Bearer token-123')
      expect(config.params).toEqual({ status: 'todo', search: 'keys', per_page: 25 })
      return [200, { data: [{ id: 1 }], meta: { total: 1 } }]
    })

    const result = await api.listTasks({ status: 'todo', search: 'keys', per_page: 25 })

    expect(result.meta.total).toBe(1)
  })

  it('unwraps resource responses', async () => {
    mock.onPut('/tasks/5', { status: 'done' }).reply(200, { data: { id: 5, status: 'done' } })

    expect(await api.updateTask(5, { status: 'done' })).toEqual({ id: 5, status: 'done' })
  })

  it('sends uploads as multipart form data', async () => {
    mock.onPost('/tasks/9/attachments').reply((config) => {
      expect(config.data).toBeInstanceOf(FormData)
      expect(config.data.get('file').name).toBe('spec.pdf')
      return [201, { data: { id: 3, scan_status: 'pending' } }]
    })

    const attachment = await api.uploadAttachment(9, new File(['%PDF'], 'spec.pdf'))

    expect(attachment.scan_status).toBe('pending')
  })

  it('signs out and notifies listeners when the session is rejected', async () => {
    const listener = vi.fn()
    const stop = onUnauthorized(listener)
    mock.onGet('/tasks').reply(401, { message: 'Unauthenticated.' })

    await expect(api.listTasks({})).rejects.toThrow()

    expect(auth.token).toBeNull()
    expect(listener).toHaveBeenCalledOnce()
    stop()
  })

  it('does not treat a failed login as an expired session', async () => {
    const listener = vi.fn()
    const stop = onUnauthorized(listener)
    mock.onPost('/auth/login').reply(401, { message: 'Email or password is incorrect.' })

    const error = await api.login('a@b.c', 'x').catch((e) => e)

    expect(errorMessage(error)).toBe('Email or password is incorrect.')
    expect(listener).not.toHaveBeenCalled()
    stop()
  })

  it('extracts validation errors', async () => {
    mock.onPost('/tasks').reply(422, { message: 'The title field is required.', errors: { title: ['The title field is required.'] } })

    const error = await api.createTask({}).catch((e) => e)

    expect(fieldErrors(error)).toEqual({ title: ['The title field is required.'] })
    expect(errorMessage(error)).toBe('The title field is required.')
  })

  it('explains network failures', async () => {
    // Shaped like the error axios's XHR adapter raises (mock-adapter's networkError() omits the code).
    mock.onGet('/users').reply(async (config) => {
      throw new AxiosError('Network Error', AxiosError.ERR_NETWORK, config, {})
    })

    const error = await api.users().catch((e) => e)

    expect(errorMessage(error)).toMatch(/Cannot reach the server/)
  })
})
