import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ToastStack from '../../src/components/ToastStack.vue'
import { clearToastHost, setToastHost, useToasts } from '../../src/composables/useToasts'

const { toasts, toast } = useToasts()

// Render the teleported toasts inline so the wrapper can find them.
const inline = { global: { stubs: { teleport: true } } }

describe('ToastStack', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => {
    toasts.splice(0)
    vi.useRealTimers()
  })

  it('labels each kind in words, not only by color', async () => {
    const wrapper = mount(ToastStack, inline)
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
    const wrapper = mount(ToastStack, inline)
    toast('Changes saved.', 'success')
    toast('Could not save the task.', 'error')
    await wrapper.vm.$nextTick()

    vi.advanceTimersByTime(4000)
    await wrapper.vm.$nextTick()
    expect(wrapper.findAll('.toast')).toHaveLength(1)

    await wrapper.find('.close').trigger('click')
    expect(wrapper.findAll('.toast')).toHaveLength(0)
  })

  it('moves into an open dialog so the dialog cannot cover it, then back to the page', async () => {
    const dialog = document.createElement('dialog')
    document.body.appendChild(dialog)
    const wrapper = mount(ToastStack, { attachTo: document.body })
    toast('Changes saved.', 'success')
    await nextTick()
    expect(dialog.querySelector('.toast')).toBeNull()

    setToastHost(dialog)
    await nextTick()
    expect(dialog.querySelector('.toast').textContent).toContain('Changes saved.')

    clearToastHost(dialog)
    await nextTick()
    expect(dialog.querySelector('.toast')).toBeNull()
    expect(document.body.querySelector('.toasts .toast')).not.toBeNull()

    wrapper.unmount()
    dialog.remove()
  })

  it('ignores a dialog that is no longer the host', () => {
    const first = document.createElement('dialog')
    const second = document.createElement('dialog')
    const { host } = useToasts()

    setToastHost(first)
    setToastHost(second)
    clearToastHost(first)

    expect(host.value).toBe(second)
    clearToastHost(second)
  })
})
