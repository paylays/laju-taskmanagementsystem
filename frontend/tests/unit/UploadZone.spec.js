import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import UploadZone from '../../src/components/UploadZone.vue'

const files = () => [new File(['a'], 'a.png', { type: 'image/png' }), new File(['b'], 'b.pdf', { type: 'application/pdf' })]

describe('UploadZone', () => {
  it('highlights while dragging and emits dropped files', async () => {
    const wrapper = mount(UploadZone)
    const zone = wrapper.find('[data-testid=upload-zone]')

    await zone.trigger('dragenter')
    expect(zone.classes()).toContain('is-over')
    expect(zone.text()).toContain('Release to upload')

    const dropped = files()
    await zone.trigger('drop', { dataTransfer: { files: dropped } })

    expect(zone.classes()).not.toContain('is-over')
    expect(wrapper.emitted('files')[0][0].map((f) => f.name)).toEqual(['a.png', 'b.pdf'])
  })

  it('keeps the highlight while moving over child elements', async () => {
    const zone = mount(UploadZone).find('[data-testid=upload-zone]')

    await zone.trigger('dragenter') // the zone
    await zone.trigger('dragenter') // a child
    await zone.trigger('dragleave') // leaving the child
    expect(zone.classes()).toContain('is-over')

    await zone.trigger('dragleave')
    expect(zone.classes()).not.toContain('is-over')
  })

  it('emits files picked with the button', async () => {
    const wrapper = mount(UploadZone)
    const input = wrapper.find('input[type=file]')
    Object.defineProperty(input.element, 'files', { value: files() })

    await input.trigger('change')

    expect(wrapper.emitted('files')[0][0]).toHaveLength(2)
  })

  it('ignores drops when disabled', async () => {
    const wrapper = mount(UploadZone, { props: { disabled: true } })
    await wrapper.find('[data-testid=upload-zone]').trigger('drop', { dataTransfer: { files: files() } })

    expect(wrapper.emitted('files')).toBeUndefined()
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })
})
