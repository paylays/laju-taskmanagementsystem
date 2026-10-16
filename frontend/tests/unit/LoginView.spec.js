import { flushPromises, mount } from '@vue/test-utils'
import MockAdapter from 'axios-mock-adapter'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { http } from '../../src/api/client'
import { auth } from '../../src/stores/auth'
import LoginView from '../../src/views/LoginView.vue'

const mock = new MockAdapter(http)

async function mountLogin(query = '') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', component: LoginView },
      { path: '/', component: { template: '<p>board</p>' } },
      { path: '/tasks/:id', component: { template: '<p>task</p>' } },
    ],
  })
  router.push(`/login${query}`)
  await router.isReady()
  return { wrapper: mount(LoginView, { global: { plugins: [router] } }), router }
}

describe('LoginView', () => {
  beforeEach(() => {
    localStorage.clear()
    auth.clear()
  })

  afterEach(() => mock.reset())

  it('signs in and goes to the requested page', async () => {
    mock.onPost('/auth/login', { email: 'rina@lajur.test', password: 'password' }).reply(200, {
      access_token: 'jwt',
      token_type: 'Bearer',
      expires_in: 7200,
      user: { id: 2, name: 'Rina', role: 'member' },
    })
    const { wrapper, router } = await mountLogin('?next=/tasks/4')

    await wrapper.find('#email').setValue(' rina@lajur.test ')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(auth.token).toBe('jwt')
    expect(router.currentRoute.value.fullPath).toBe('/tasks/4')
  })

  it('ignores a next parameter pointing off-site', async () => {
    mock.onPost('/auth/login').reply(200, { access_token: 'jwt', expires_in: 60, user: { id: 1 } })
    const { wrapper, router } = await mountLogin('?next=https://evil.example')

    await wrapper.find('#email').setValue('a@b.c')
    await wrapper.find('#password').setValue('x')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(router.currentRoute.value.fullPath).toBe('/')
  })

  it('shows the server message on wrong credentials', async () => {
    mock.onPost('/auth/login').reply(401, { message: 'Email or password is incorrect.' })
    const { wrapper } = await mountLogin()

    await wrapper.find('#email').setValue('a@b.c')
    await wrapper.find('#password').setValue('nope')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[role=alert]').text()).toBe('Email or password is incorrect.')
    expect(auth.token).toBeNull()
  })

  it('validates empty fields without calling the API', async () => {
    const { wrapper } = await mountLogin()

    await wrapper.find('form').trigger('submit')

    expect(wrapper.find('[role=alert]').text()).toBe('Enter your email and password, or pick a demo account.')
    expect(mock.history.post).toHaveLength(0)
  })

  it('fills the form from a demo account and signs in with it', async () => {
    mock.onPost('/auth/login', { email: 'sari@lajur.test', password: 'password' }).reply(200, {
      access_token: 'jwt',
      expires_in: 7200,
      user: { id: 6, name: 'Sari Wulandari', role: 'member' },
    })
    const { wrapper, router } = await mountLogin()

    const sari = wrapper.findAll('.account').find((b) => b.text().includes('sari@lajur.test'))
    await sari.trigger('click')

    expect(wrapper.find('#email').element.value).toBe('sari@lajur.test')
    expect(wrapper.find('#password').element.value).toBe('password')
    expect(sari.attributes('aria-pressed')).toBe('true')
    expect(sari.text()).toContain('Selected')
    expect(wrapper.findAll('.account')).toHaveLength(6)

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(auth.token).toBe('jwt')
    expect(router.currentRoute.value.fullPath).toBe('/')
  })

  it('explains why the user was sent back to sign in', async () => {
    const { wrapper } = await mountLogin('?expired=1')
    expect(wrapper.find('[role=alert]').text()).toContain('Your session ended')
  })
})
