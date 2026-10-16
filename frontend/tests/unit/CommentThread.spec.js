import { flushPromises, mount } from '@vue/test-utils'
import MockAdapter from 'axios-mock-adapter'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { http } from '../../src/api/client'
import CommentThread from '../../src/components/CommentThread.vue'
import { auth } from '../../src/stores/auth'

const mock = new MockAdapter(http)
const comment = (id, text) => ({ id, task_id: 4, comment: text, user: { id: 1, name: 'Rina' }, created_at: new Date().toISOString() })

describe('CommentThread (component + API)', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.scrollIntoView = () => {}
    auth.set({ access_token: 't', expires_in: 3600, user: { id: 1, role: 'member' } })
  })

  afterEach(() => mock.reset())

  it('loads comments, then posts a new one', async () => {
    mock.onGet('/tasks/4/comments').reply(200, { data: [comment(1, 'First')] })
    mock.onPost('/tasks/4/comments', { comment: 'Second' }).reply(201, { data: comment(2, 'Second') })

    const wrapper = mount(CommentThread, { props: { taskId: 4 } })
    expect(wrapper.text()).toContain('Loading comments')
    await flushPromises()
    expect(wrapper.text()).toContain('First')

    await wrapper.find('textarea').setValue('  Second  ')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.findAll('ol li')).toHaveLength(2)
    expect(wrapper.find('textarea').element.value).toBe('')
  })

  it('ignores the live echo of a comment it already has', async () => {
    mock.onGet('/tasks/4/comments').reply(200, { data: [comment(1, 'First')] })
    const wrapper = mount(CommentThread, { props: { taskId: 4 } })
    await flushPromises()

    wrapper.vm.receive(comment(1, 'First'))
    wrapper.vm.receive(comment(5, 'From someone else'))
    wrapper.vm.receive({ ...comment(6, 'Other task'), task_id: 99 })
    await flushPromises()

    expect(wrapper.findAll('ol li').map((li) => li.find('.body').text())).toEqual(['First', 'From someone else'])
  })

  it('shows the empty state and refuses a blank comment', async () => {
    mock.onGet('/tasks/4/comments').reply(200, { data: [] })
    const wrapper = mount(CommentThread, { props: { taskId: 4 } })
    await flushPromises()

    expect(wrapper.text()).toContain('No comments yet.')
    await wrapper.find('form').trigger('submit')
    expect(wrapper.find('[role=alert]').text()).toBe('Write something before posting.')
  })

  it('shows an error with a retry when loading fails', async () => {
    mock.onGet('/tasks/4/comments').replyOnce(500, { message: 'Server Error' })
    const wrapper = mount(CommentThread, { props: { taskId: 4 } })
    await flushPromises()

    expect(wrapper.text()).toContain('Server Error')

    mock.onGet('/tasks/4/comments').reply(200, { data: [comment(1, 'Back')] })
    await wrapper.find('button').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Back')
  })
})
