<script setup lang="ts">
/**
 * OrgOnboardingWizardView — 4-step wizard запуска организации.
 *
 * Route: `/provider/onboarding` (обычно через redirect из RegisterProviderView
 * с query `?org_name=...&org_type=...&city=...`).
 *
 * Шаги:
 *  1. Business profile — name / type / city / description / logo (stub).
 *  2. Первая услуга — упрощённая форма TIME_SLOT|QUANTITY.
 *  3. Доступность — placeholder (MVP заглушка).
 *  4. Публикация — review summary + "Опубликовать".
 *
 * Прогресс сохраняется в `sessionStorage` (ключ `reserv.onboarding.draft`),
 * чтобы refresh не терял введённые данные. На последнем шаге POST /organizations —
 * если backend отвечает 404/5xx, toast ошибки и остаёмся на шаге 4.
 *
 * BaseStepper навигация disabled (navigable=false) — нельзя прыгать вперёд,
 * только последовательно.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseStepper from '@/shared/components/base/BaseStepper.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseTextarea from '@/shared/components/base/BaseTextarea.vue'
import BaseFileUploader from '@/shared/components/base/BaseFileUploader.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseTabs from '@/shared/components/base/BaseTabs.vue'
import { useToast } from '@/shared/composables/useToast'
import { apiClient } from '@/api/client'
import type { Envelope } from '@/types/auth.types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { toast } = useToast()

const DRAFT_KEY = 'reserv.onboarding.draft'

type WizardStepId = 'profile' | 'service' | 'availability' | 'publish'

interface WizardDraft {
  orgName: string
  orgType: string
  city: string
  description: string
  orgPhone: string
  orgEmail: string
  serviceType: 'time_slot' | 'quantity'
  serviceName: string
  servicePrice: number | string
  serviceDuration: number | string
  serviceQuantity: number | string
  currentStep: WizardStepId
}

const STEP_ORDER: WizardStepId[] = ['profile', 'service', 'availability', 'publish']

const currentStep = ref<WizardStepId>('profile')

const orgName = ref<string>('')
const orgType = ref<string>('salon')
const city = ref<string>('')
const description = ref<string>('')
const orgPhone = ref<string>('')
const orgEmail = ref<string>('')
const logo = ref<File[]>([])

const serviceType = ref<'time_slot' | 'quantity'>('time_slot')
const serviceName = ref<string>('')
const servicePrice = ref<number | string>('')
const serviceDuration = ref<number | string>(60)
const serviceQuantity = ref<number | string>(1)

const isSubmitting = ref<boolean>(false)
const submitted = ref<boolean>(false)
const draftRestored = ref<boolean>(false)

const orgTypeOptions = computed<{ value: string; label: string }[]>(() => [
  { value: 'salon', label: t('auth.register.orgTypeSalon') },
  { value: 'rental', label: t('auth.register.orgTypeRental') },
  { value: 'consult', label: t('auth.register.orgTypeConsult') },
  { value: 'other', label: t('auth.register.orgTypeOther') },
])

const steps = computed(() => [
  { id: 'profile', label: t('onboarding.stepProfile') },
  { id: 'service', label: t('onboarding.stepService') },
  { id: 'availability', label: t('onboarding.stepAvailability') },
  { id: 'publish', label: t('onboarding.stepPublish') },
])

const serviceTypeTabs = computed(() => [
  { id: 'time_slot', label: t('onboarding.typeTimeSlot') },
  { id: 'quantity', label: t('onboarding.typeQuantity') },
])

const currentIndex = computed<number>(() => STEP_ORDER.indexOf(currentStep.value))

const canGoBack = computed<boolean>(() => currentIndex.value > 0)
const canGoNext = computed<boolean>(() => currentIndex.value < STEP_ORDER.length - 1)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const profileValid = computed<boolean>(() =>
  orgName.value.trim().length > 0 &&
  orgType.value.trim().length > 0 &&
  city.value.trim().length > 0 &&
  orgPhone.value.trim().length >= 7 &&
  EMAIL_RE.test(orgEmail.value.trim()),
)

const serviceValid = computed<boolean>(() =>
  serviceName.value.trim().length > 0 &&
  Number(servicePrice.value) > 0,
)

/**
 * Хранилище черновика — sessionStorage, чтобы после закрытия вкладки
 * wizard начинался с нуля, но refresh не сбрасывал прогресс.
 */
function saveDraft(): void {
  try {
    const draft: WizardDraft = {
      orgName: orgName.value,
      orgType: orgType.value,
      city: city.value,
      description: description.value,
      orgPhone: orgPhone.value,
      orgEmail: orgEmail.value,
      serviceType: serviceType.value,
      serviceName: serviceName.value,
      servicePrice: servicePrice.value,
      serviceDuration: serviceDuration.value,
      serviceQuantity: serviceQuantity.value,
      currentStep: currentStep.value,
    }
    sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
  } catch {
    /* sessionStorage недоступен (private mode) — игнорируем */
  }
}

function loadDraft(): WizardDraft | null {
  try {
    const raw = sessionStorage.getItem(DRAFT_KEY)
    if (!raw) return null
    return JSON.parse(raw) as WizardDraft
  } catch {
    return null
  }
}

function clearDraft(): void {
  try {
    sessionStorage.removeItem(DRAFT_KEY)
  } catch {
    /* noop */
  }
}

function prefillFromQuery(): void {
  const q = route.query
  if (typeof q.org_name === 'string' && q.org_name.trim() && !orgName.value) {
    orgName.value = q.org_name.trim()
  }
  if (typeof q.org_type === 'string' && q.org_type.trim()) {
    orgType.value = q.org_type.trim()
  }
  if (typeof q.city === 'string' && q.city.trim() && !city.value) {
    city.value = q.city.trim()
  }
}

function goBack(): void {
  if (!canGoBack.value) return
  const prev = STEP_ORDER[currentIndex.value - 1]
  if (prev) currentStep.value = prev
}

function goNext(): void {
  if (!canGoNext.value) return
  submitted.value = true
  if (currentStep.value === 'profile' && !profileValid.value) return
  if (currentStep.value === 'service' && !serviceValid.value) return
  submitted.value = false
  const next = STEP_ORDER[currentIndex.value + 1]
  if (next) currentStep.value = next
}

/**
 * Публикация — POST /organizations с правильным shape payload'а.
 *
 * Backend (CreateOrganizationRequest) ожидает:
 * - name, description: локализованные объекты {ru, en?}
 * - type, city, phone, email: required strings
 * При ошибке остаёмся на шаге publish с toast. При success redirect на org dashboard.
 *
 * TODO: после success создавать первую услугу через POST /organizations/{slug}/services
 * (backend endpoint ещё не реализован).
 */
async function handlePublish(): Promise<void> {
  if (isSubmitting.value) return
  isSubmitting.value = true
  try {
    const trimmedDescription = description.value.trim()
    const payload = {
      name: { ru: orgName.value.trim() },
      type: orgType.value,
      city: city.value.trim(),
      description: trimmedDescription ? { ru: trimmedDescription } : undefined,
      phone: orgPhone.value.trim(),
      email: orgEmail.value.trim(),
    }
    const resp = await apiClient.post<Envelope<{ slug: string }>>(
      '/organizations',
      payload,
    )
    const slug = resp.data?.data?.slug
    if (!resp.data?.success || !slug) {
      throw new Error(resp.data?.error?.message ?? t('onboarding.errorPublish'))
    }
    clearDraft()
    toast.success(t('onboarding.successTitle'))
    await router.push(`/o/${encodeURIComponent(slug)}`)
  } catch (err) {
    const message = err instanceof Error ? err.message : t('onboarding.errorPublish')
    toast.error(message)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  const draft = loadDraft()
  if (draft) {
    orgName.value = draft.orgName
    orgType.value = draft.orgType
    city.value = draft.city
    description.value = draft.description
    orgPhone.value = draft.orgPhone ?? ''
    orgEmail.value = draft.orgEmail ?? ''
    serviceType.value = draft.serviceType
    serviceName.value = draft.serviceName
    servicePrice.value = draft.servicePrice
    serviceDuration.value = draft.serviceDuration
    serviceQuantity.value = draft.serviceQuantity
    currentStep.value = draft.currentStep
    draftRestored.value = true
  }
  prefillFromQuery()
})

watch(
  [
    orgName,
    orgType,
    city,
    description,
    orgPhone,
    orgEmail,
    serviceType,
    serviceName,
    servicePrice,
    serviceDuration,
    serviceQuantity,
    currentStep,
  ],
  saveDraft,
  { deep: false },
)

onBeforeUnmount(() => {
  saveDraft()
})

const priceDisplay = computed<string>(() => {
  const p = Number(servicePrice.value)
  return Number.isFinite(p) && p > 0 ? `${p} ₽` : '—'
})

const typeLabel = computed<string>(() => {
  const map: Record<string, string> = {
    salon: t('auth.register.orgTypeSalon'),
    rental: t('auth.register.orgTypeRental'),
    consult: t('auth.register.orgTypeConsult'),
    other: t('auth.register.orgTypeOther'),
  }
  return map[orgType.value] ?? orgType.value
})
</script>

<template>
  <section
    class="mx-auto max-w-3xl px-4 py-8 sm:px-6"
    data-test-id="onboarding-wizard-view"
  >
    <header class="mb-6 text-center">
      <h1
        class="text-2xl font-bold tracking-tight text-text"
        data-test-id="onboarding-title"
      >
        {{ t('onboarding.title') }}
      </h1>
      <p class="mt-1 text-sm text-text-subtle">
        {{ t('onboarding.subtitle') }}
      </p>
    </header>

    <div
      v-if="draftRestored"
      class="mb-4 rounded-md border border-border bg-surface-muted px-3 py-2 text-xs text-text-subtle"
      data-test-id="onboarding-draft-banner"
    >
      {{ t('onboarding.savedBanner') }}
    </div>

    <div class="mb-6">
      <BaseStepper
        v-model="currentStep"
        :steps="steps"
        :navigable="false"
      />
    </div>

    <BaseCard padding="lg">
      <section
        v-if="currentStep === 'profile'"
        class="flex flex-col gap-4"
        data-test-id="onboarding-step-profile"
      >
        <header>
          <h2 class="text-lg font-semibold text-text">
            {{ t('onboarding.profileSection') }}
          </h2>
          <p class="mt-1 text-sm text-text-subtle">
            {{ t('onboarding.profileHint') }}
          </p>
        </header>

        <BaseInput
          v-model="orgName"
          :label="t('onboarding.orgName')"
          :placeholder="t('onboarding.orgNamePh')"
          :error="submitted && !orgName.trim() ? t('common.error') : ''"
          required
          test-id="onboarding-org-name-input"
        />

        <BaseSelect
          v-model="orgType"
          :options="orgTypeOptions"
          :label="t('onboarding.orgType')"
          required
          test-id="onboarding-org-type-select"
        />

        <BaseInput
          v-model="city"
          :label="t('onboarding.city')"
          :placeholder="t('onboarding.cityPh')"
          :error="submitted && !city.trim() ? t('common.error') : ''"
          required
          test-id="onboarding-city-input"
        />

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput
            v-model="orgPhone"
            :label="t('onboarding.orgPhone')"
            :placeholder="t('onboarding.orgPhonePh')"
            :error="submitted && orgPhone.trim().length < 7 ? t('common.error') : ''"
            required
            type="tel"
            autocomplete="tel"
            test-id="onboarding-phone-input"
          />
          <BaseInput
            v-model="orgEmail"
            :label="t('onboarding.orgEmail')"
            :placeholder="t('onboarding.orgEmailPh')"
            :error="submitted && !EMAIL_RE.test(orgEmail.trim()) ? t('common.error') : ''"
            required
            type="email"
            autocomplete="email"
            test-id="onboarding-email-input"
          />
        </div>

        <BaseTextarea
          v-model="description"
          :label="t('onboarding.description')"
          :placeholder="t('onboarding.descriptionPh')"
          test-id="onboarding-description-input"
        />

        <BaseFileUploader
          v-model="logo"
          :label="t('onboarding.logo')"
          :hint="t('onboarding.logoHint')"
          accept="image/*"
          :max-size-mb="5"
        />
      </section>

      <section
        v-else-if="currentStep === 'service'"
        class="flex flex-col gap-4"
        data-test-id="onboarding-step-service"
      >
        <header>
          <h2 class="text-lg font-semibold text-text">
            {{ t('onboarding.serviceSection') }}
          </h2>
          <p class="mt-1 text-sm text-text-subtle">
            {{ t('onboarding.serviceHint') }}
          </p>
        </header>

        <BaseTabs
          v-model="serviceType"
          :tabs="serviceTypeTabs"
          data-test-id="onboarding-service-type-tabs"
        >
          <template #tab-time_slot>
            <div class="flex flex-col gap-4">
              <BaseInput
                v-model="serviceName"
                :label="t('onboarding.serviceName')"
                :placeholder="t('onboarding.serviceNamePh')"
                :error="submitted && !serviceName.trim() ? t('common.error') : ''"
                required
                test-id="onboarding-service-name-input"
              />
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                  v-model="servicePrice"
                  type="number"
                  :label="t('onboarding.servicePrice')"
                  :error="submitted && Number(servicePrice) <= 0 ? t('common.error') : ''"
                  required
                  test-id="onboarding-service-price-input"
                />
                <BaseInput
                  v-model="serviceDuration"
                  type="number"
                  :label="t('onboarding.serviceDuration')"
                  test-id="onboarding-service-duration-input"
                />
              </div>
            </div>
          </template>

          <template #tab-quantity>
            <div class="flex flex-col gap-4">
              <BaseInput
                v-model="serviceName"
                :label="t('onboarding.serviceName')"
                :placeholder="t('onboarding.serviceNamePh')"
                :error="submitted && !serviceName.trim() ? t('common.error') : ''"
                required
                test-id="onboarding-service-name-input-qty"
              />
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                  v-model="servicePrice"
                  type="number"
                  :label="t('onboarding.servicePrice')"
                  :error="submitted && Number(servicePrice) <= 0 ? t('common.error') : ''"
                  required
                  test-id="onboarding-service-price-input-qty"
                />
                <BaseInput
                  v-model="serviceQuantity"
                  type="number"
                  :label="t('onboarding.serviceQuantity')"
                  test-id="onboarding-service-quantity-input"
                />
              </div>
            </div>
          </template>
        </BaseTabs>
      </section>

      <section
        v-else-if="currentStep === 'availability'"
        class="flex flex-col gap-4"
        data-test-id="onboarding-step-availability"
      >
        <header>
          <h2 class="text-lg font-semibold text-text">
            {{ t('onboarding.availabilitySection') }}
          </h2>
        </header>
        <div
          class="rounded-md border border-border bg-surface-muted p-4 text-sm text-text-subtle"
          data-test-id="onboarding-availability-placeholder"
        >
          {{ t('onboarding.availabilityStub') }}
        </div>
      </section>

      <section
        v-else-if="currentStep === 'publish'"
        class="flex flex-col gap-4"
        data-test-id="onboarding-step-publish"
      >
        <header>
          <h2 class="text-lg font-semibold text-text">
            {{ t('onboarding.publishSection') }}
          </h2>
          <p class="mt-1 text-sm text-text-subtle">
            {{ t('onboarding.publishHint') }}
          </p>
        </header>

        <dl
          class="grid grid-cols-1 gap-3 rounded-md border border-border bg-surface p-4 text-sm md:grid-cols-2"
          data-test-id="onboarding-review-summary"
        >
          <div>
            <dt class="text-xs uppercase tracking-wider text-text-subtle">
              {{ t('onboarding.reviewOrg') }}
            </dt>
            <dd class="text-text font-medium">{{ orgName || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wider text-text-subtle">
              {{ t('onboarding.reviewType') }}
            </dt>
            <dd class="text-text">{{ typeLabel }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wider text-text-subtle">
              {{ t('onboarding.reviewCity') }}
            </dt>
            <dd class="text-text">{{ city || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wider text-text-subtle">
              {{ t('onboarding.reviewService') }}
            </dt>
            <dd class="text-text">{{ serviceName || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wider text-text-subtle">
              {{ t('onboarding.reviewPrice') }}
            </dt>
            <dd class="text-text">{{ priceDisplay }}</dd>
          </div>
        </dl>
      </section>

      <footer class="mt-6 flex items-center justify-between gap-2">
        <BaseButton
          variant="ghost"
          :disabled="!canGoBack || isSubmitting"
          test-id="onboarding-back-btn"
          @click="goBack"
        >
          {{ t('onboarding.back') }}
        </BaseButton>

        <BaseButton
          v-if="currentStep !== 'publish'"
          variant="primary"
          :disabled="isSubmitting"
          test-id="onboarding-next-btn"
          @click="goNext"
        >
          {{ t('onboarding.next') }}
        </BaseButton>

        <BaseButton
          v-else
          variant="primary"
          :loading="isSubmitting"
          test-id="onboarding-publish-btn"
          @click="handlePublish"
        >
          {{ t('onboarding.publishCta') }}
        </BaseButton>
      </footer>
    </BaseCard>
  </section>
</template>
