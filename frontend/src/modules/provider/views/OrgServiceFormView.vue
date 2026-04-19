<script setup lang="ts">
/**
 * OrgServiceFormView — форма создания/редактирования услуги.
 *
 * Routes: `/o/:slug/services/new` и `/o/:slug/services/:id/edit`.
 * Различает create/edit по наличию `route.params.id`.
 *
 * Backend endpoints для POST/PUT пока stub — submit при ошибке 404/5xx
 * эмитит toast "Endpoint ещё не реализован" и остаётся на форме.
 */
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseTextarea from '@/shared/components/base/BaseTextarea.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseTabs from '@/shared/components/base/BaseTabs.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseFileUploader from '@/shared/components/base/BaseFileUploader.vue'
import * as servicesApi from '@/api/services.api'
import type { ServicePayload } from '@/api/services.api'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const serviceId = computed<string | null>(() => {
  const id = route.params.id
  return typeof id === 'string' ? id : null
})

const isEdit = computed<boolean>(() => serviceId.value !== null)

const activeTab = ref<'time_slot' | 'quantity'>('time_slot')
const name = ref<string>('')
const description = ref<string>('')
const categoryId = ref<string>('')
const price = ref<number | string>('')
const duration = ref<number | string>(60)
const quantity = ref<number | string>(1)
const photos = ref<File[]>([])
const isSubmitting = ref<boolean>(false)

const tabs = computed(() => [
  { id: 'time_slot', label: t('provider.serviceForm.tabTimeSlot') },
  { id: 'quantity', label: t('provider.serviceForm.tabQuantity') },
])

/**
 * Mock-категории. Реальный список подтянется из catalog.api когда
 * org-scoped catalog lookup будет реализован.
 */
const categoryOptions = [
  { value: 'beauty', label: 'Красота' },
  { value: 'health', label: 'Здоровье' },
  { value: 'stay', label: 'Жильё' },
  { value: 'work', label: 'Работа' },
  { value: 'rental', label: 'Прокат' },
  { value: 'consult', label: 'Консультации' },
]

const pageTitle = computed<string>(() =>
  isEdit.value
    ? t('provider.serviceForm.titleEdit')
    : t('provider.serviceForm.titleCreate'),
)

async function loadExistingService(): Promise<void> {
  if (!serviceId.value) return
  try {
    const envelope = await servicesApi.get(orgSlug.value, serviceId.value)
    if (!envelope.success || !envelope.data) return
    const svc = envelope.data
    name.value = svc.name
    description.value = svc.description
    categoryId.value = svc.categoryId
    price.value = Math.round(svc.priceAmount / 100)
    activeTab.value = svc.type
    if (svc.durationMinutes !== null) duration.value = svc.durationMinutes
    if (svc.totalQuantity !== null) quantity.value = svc.totalQuantity
  } catch {
    // backend stub — начинаем с пустой формы
  }
}

function buildPayload(): ServicePayload {
  const numericPrice = Number(price.value) || 0
  return {
    name: name.value,
    description: description.value,
    price_amount: numericPrice * 100,
    price_currency: 'RUB',
    type: activeTab.value,
    category_id: categoryId.value || 'beauty',
    is_active: true,
  }
}

async function handleSubmit(): Promise<void> {
  if (isSubmitting.value) return
  isSubmitting.value = true
  try {
    const payload = buildPayload()
    if (isEdit.value && serviceId.value) {
      await servicesApi.update(orgSlug.value, serviceId.value, payload)
      toast.success(t('provider.serviceForm.successUpdate'))
    } else {
      await servicesApi.create(orgSlug.value, payload)
      toast.success(t('provider.serviceForm.successCreate'))
    }
    await router.push(`/o/${orgSlug.value}/services`)
  } catch {
    toast.error(t('provider.serviceForm.stubError'))
  } finally {
    isSubmitting.value = false
  }
}

function onCancel(): void {
  void router.push(`/o/${orgSlug.value}/services`)
}

onMounted(() => {
  void loadExistingService()
})
</script>

<template>
  <section
    data-test-id="org-service-form-view"
    class="flex flex-col gap-4 max-w-3xl"
  >
    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-service-form-title"
    >
      {{ pageTitle }}
    </h1>

    <BaseCard padding="lg">
      <BaseTabs
        v-model="activeTab"
        :tabs="tabs"
        data-test-id="org-service-form-tabs"
      >
        <template #tab-time_slot>
          <form
            class="flex flex-col gap-4"
            novalidate
            data-test-id="org-service-form-time-slot"
            @submit.prevent="handleSubmit"
          >
            <BaseInput
              v-model="name"
              :label="t('provider.serviceForm.name')"
              :placeholder="t('provider.serviceForm.namePh')"
              required
              test-id="org-service-form-name-input"
            />
            <BaseTextarea
              v-model="description"
              :label="t('provider.serviceForm.description')"
              :placeholder="t('provider.serviceForm.descriptionPh')"
              test-id="org-service-form-description-input"
            />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <BaseSelect
                v-model="categoryId"
                :options="categoryOptions"
                :label="t('provider.serviceForm.category')"
                :placeholder="t('provider.serviceForm.category')"
                test-id="org-service-form-category-select"
              />
              <BaseInput
                v-model="price"
                type="number"
                :label="t('provider.serviceForm.price')"
                test-id="org-service-form-price-input"
              />
              <BaseInput
                v-model="duration"
                type="number"
                :label="t('provider.serviceForm.duration')"
                test-id="org-service-form-duration-input"
              />
            </div>
            <BaseFileUploader
              v-model="photos"
              :label="t('provider.serviceForm.photo')"
              :hint="t('provider.serviceForm.photoHint')"
              accept="image/*"
              :max-size-mb="5"
            />
            <div class="flex items-center justify-end gap-2">
              <BaseButton
                variant="ghost"
                test-id="org-service-form-cancel-btn"
                @click="onCancel"
              >
                {{ t('provider.serviceForm.cancel') }}
              </BaseButton>
              <BaseButton
                variant="primary"
                type="submit"
                :loading="isSubmitting"
                test-id="org-service-form-submit-btn"
              >
                {{ t('provider.serviceForm.submit') }}
              </BaseButton>
            </div>
          </form>
        </template>

        <template #tab-quantity>
          <form
            class="flex flex-col gap-4"
            novalidate
            data-test-id="org-service-form-quantity"
            @submit.prevent="handleSubmit"
          >
            <BaseInput
              v-model="name"
              :label="t('provider.serviceForm.name')"
              :placeholder="t('provider.serviceForm.namePh')"
              required
              test-id="org-service-form-name-input-qty"
            />
            <BaseTextarea
              v-model="description"
              :label="t('provider.serviceForm.description')"
              :placeholder="t('provider.serviceForm.descriptionPh')"
              test-id="org-service-form-description-input-qty"
            />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <BaseSelect
                v-model="categoryId"
                :options="categoryOptions"
                :label="t('provider.serviceForm.category')"
                :placeholder="t('provider.serviceForm.category')"
                test-id="org-service-form-category-select-qty"
              />
              <BaseInput
                v-model="price"
                type="number"
                :label="t('provider.serviceForm.price')"
                test-id="org-service-form-price-input-qty"
              />
              <BaseInput
                v-model="quantity"
                type="number"
                :label="t('provider.serviceForm.quantity')"
                test-id="org-service-form-quantity-input"
              />
            </div>
            <BaseFileUploader
              v-model="photos"
              :label="t('provider.serviceForm.photo')"
              :hint="t('provider.serviceForm.photoHint')"
              accept="image/*"
              :max-size-mb="5"
            />
            <div class="flex items-center justify-end gap-2">
              <BaseButton
                variant="ghost"
                test-id="org-service-form-cancel-btn-qty"
                @click="onCancel"
              >
                {{ t('provider.serviceForm.cancel') }}
              </BaseButton>
              <BaseButton
                variant="primary"
                type="submit"
                :loading="isSubmitting"
                test-id="org-service-form-submit-btn-qty"
              >
                {{ t('provider.serviceForm.submit') }}
              </BaseButton>
            </div>
          </form>
        </template>
      </BaseTabs>
    </BaseCard>
  </section>
</template>
