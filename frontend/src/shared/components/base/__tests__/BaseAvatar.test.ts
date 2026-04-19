import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseAvatar from '../BaseAvatar.vue'

describe('BaseAvatar', () => {
  it('renders initials from alt when no src', () => {
    const wrapper = mount(BaseAvatar, { props: { alt: 'Ivan Petrov' } })
    expect(wrapper.find('[data-test-id="base-avatar-fallback"]').text()).toBe('IP')
  })

  it('uses explicit fallback when provided', () => {
    const wrapper = mount(BaseAvatar, { props: { alt: 'x', fallback: 'ab' } })
    expect(wrapper.find('[data-test-id="base-avatar-fallback"]').text()).toBe('AB')
  })

  it('renders img when src provided', () => {
    const wrapper = mount(BaseAvatar, {
      props: { src: 'http://example.com/a.png', alt: 'User' },
    })
    expect(wrapper.find('[data-test-id="base-avatar-img"]').exists()).toBe(true)
  })

  it('falls back to initials on img error', async () => {
    const wrapper = mount(BaseAvatar, {
      props: { src: 'http://example.com/broken.png', alt: 'Foo Bar' },
    })
    const img = wrapper.find('[data-test-id="base-avatar-img"]')
    await img.trigger('error')
    expect(wrapper.find('[data-test-id="base-avatar-fallback"]').exists()).toBe(true)
    expect(wrapper.find('[data-test-id="base-avatar-fallback"]').text()).toBe('FB')
  })
})
