import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import ServiceCard from '../ServiceCard.vue'
import type { ServiceListItem } from '@/types/catalog.types'

function createTestRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div />' } },
      {
        path: '/catalog/:id',
        name: 'catalog-service',
        component: { template: '<div />' },
      },
    ],
  })
}

const service: ServiceListItem = {
  id: 'svc-1',
  name: 'Стрижка',
  priceAmount: 250000,
  priceCurrency: 'RUB',
  type: 'time_slot',
  categoryName: 'Красота',
  subcategoryName: 'Парикмахерская',
  primaryImage: null,
  isActive: true,
}

describe('ServiceCard', () => {
  it('renders service name, category and formatted price', async () => {
    const router = createTestRouter()
    const wrapper = mount(ServiceCard, {
      props: { service },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Стрижка')
    expect(wrapper.text()).toContain('Красота')
    expect(wrapper.text()).toContain('Слот')
    expect(wrapper.text()).toMatch(/2[\s\u00A0]?500/)
  })

  it('builds correct router-link to detail route', () => {
    const router = createTestRouter()
    const wrapper = mount(ServiceCard, {
      props: { service },
      global: { plugins: [router] },
    })

    const link = wrapper.find('[data-test-id="catalog-service-card-link"]')
    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('/catalog/svc-1')
  })

  it('renders quantity badge for quantity-type services', () => {
    const router = createTestRouter()
    const wrapper = mount(ServiceCard, {
      props: { service: { ...service, type: 'quantity' } },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Количество')
  })

  it('shows fallback when primary image is missing', () => {
    const router = createTestRouter()
    const wrapper = mount(ServiceCard, {
      props: { service },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Нет изображения')
    expect(wrapper.find('img').exists()).toBe(false)
  })
})
