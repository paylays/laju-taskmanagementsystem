import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ToastStack from '../../src/components/ToastStack.vue'
import { useToasts } from '../../src/composables/useToasts'

const { toasts, toast } = useToasts()

describe('ToastStack', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => {
    toasts.splice(0)
    vi.useRealTimers()
  })

  it('labels each kind in words, not only by color', async () => {
    const wrapper = mount(ToastStack)
    toast('Changes saved.', 'success')
    toast('Uploading report.pdf', 'info')
    toast('Could not save the task.', 'error')
    await wrapper.vm.$nextTick()

    const items = wrapper.findAll('.toast')
    expect(items.map((t) => t.find('.label').text())).toEqual(['Success', 'Info', 'Error'])
    expect(items[0].attributes('role')).toBe('status')
    expect(items[2].attributes('role')).toBe('alert')
  })

  it('closes success and info by itself, but keeps errors until closed', async () => {
    const wrapper = mount(ToastStack)
    toast('Changes saved.', 'success')
    toast('Could not save the task.', 'error')
    await wrapper.vm.$nextTick()

    vi.advanceTimersByTime(4000)
    await wrapper.vm.$nextTick()
    expect(wrapper.findAll('.toast')).toHaveLength(1)

    await wrapper.find('.close').trigger('click')
    expect(wrapper.findAll('.toast')).toHaveLength(0)
  })
})
