import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TaskRow from '../../src/components/TaskRow.vue'

const task = (overrides = {}) => ({
  id: 7,
  title: 'Rotate S3 access keys',
  status: 'todo',
  priority: 'high',
  due_date: null,
  assignee: { id: 3, name: 'Bagus Santoso' },
  comments_count: 2,
  attachments_count: 1,
  ...overrides,
})

describe('TaskRow', () => {
  it('shows title, priority as text, assignee and counts', () => {
    const wrapper = mount(TaskRow, { props: { task: task() } })

    expect(wrapper.text()).toContain('Rotate S3 access keys')
    expect(wrapper.find('.priority').text()).toBe('High')
    expect(wrapper.find('.priority').classes()).toContain('is-high')
    expect(wrapper.text()).toContain('Bagus Santoso')
    expect(wrapper.text()).toContain('2 comments, 1 file')
  })

  it('says Unassigned and hides empty counts', () => {
    const wrapper = mount(TaskRow, { props: { task: task({ assignee: null, comments_count: 0, attachments_count: 0 }) } })

    expect(wrapper.text()).toContain('Unassigned')
    expect(wrapper.text()).not.toContain('comment')
  })

  it('marks overdue tasks in text, but not once they are done', () => {
    const overdue = mount(TaskRow, { props: { task: task({ due_date: '2020-01-01' }) } })
    expect(overdue.find('.danger').text()).toMatch(/^Overdue by \d+ days$/)

    const done = mount(TaskRow, { props: { task: task({ due_date: '2020-01-01', status: 'done' }) } })
    expect(done.text()).not.toContain('Overdue')
  })

  it('emits open with the task id', async () => {
    const wrapper = mount(TaskRow, { props: { task: task() } })
    await wrapper.find('button.open').trigger('click')
    expect(wrapper.emitted('open')).toEqual([[7]])
  })

  it('only offers a checkbox when the task is selectable', async () => {
    expect(mount(TaskRow, { props: { task: task() } }).find('input[type=checkbox]').exists()).toBe(false)

    const wrapper = mount(TaskRow, { props: { task: task(), selectable: true } })
    await wrapper.find('input[type=checkbox]').trigger('change')
    expect(wrapper.emitted('toggle')).toEqual([[7]])
    expect(wrapper.find('label').text()).toContain('Select "Rotate S3 access keys"')
  })
})
