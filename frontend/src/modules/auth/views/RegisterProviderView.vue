<script setup lang="ts">
/**
 * RegisterProviderView — форма регистрации провайдера (владельца организации).
 *
 * Backend `/auth/register` не принимает организационных полей, поэтому:
 *  1. Регистрируем user через `authStore.register({user fields})`;
 *  2. Сохраняем org-данные в query и редиректим на
 *     `/provider/onboarding?org_name=...&org_type=...&city=...`;
 *  3. OrgOnboardingWizard (Plan F.1) подхватит query и префиллит шаги.
 */
import { computed, nextTick, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Building2, ChevronLeft, Eye, EyeOff, Mail, MapPin } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseCheckbox from '@/shared/components/base/BaseCheckbox.vue'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

const firstName = ref<string>('')
const lastName = ref<string>('')
const middleName = ref<string>('')
const email = ref<string>('')
const password = ref<string>('')
const passwordConfirm = ref<string>('')
const agreeTerms = ref<boolean>(false)
const showPassword = ref<boolean>(false)

const orgName = ref<string>('')
const orgType = ref<string>('salon')
const city = ref<string>('')

const isSubmitting = ref<boolean>(false)
const submitted = ref<boolean>(false)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const orgTypeOptions = computed<{ value: string; label: string }[]>(() => [
  { value: 'salon', label: t('auth.register.orgTypeSalon') },
  { value: 'rental', label: t('auth.register.orgTypeRental') },
  { value: 'consult', label: t('auth.register.orgTypeConsult') },
  { value: 'other', label: t('auth.register.orgTypeOther') },
])

const emailError = computed<string>(() => {
  if (!submitted.value) return ''
  if (!email.value.trim() || !EMAIL_RE.test(email.value.trim())) return t('common.error')
  return ''
})
const passwordError = computed<string>(() =>
  submitted.value && password.value.length < 8 ? t('auth.register.passwordTooShort') : '',
)
const passwordConfirmError = computed<string>(() =>
  passwordConfirm.value && passwordConfirm.value !== password.value
    ? t('auth.register.passwordMismatch')
    : '',
)
const firstNameError = computed<string>(() =>
  submitted.value && !firstName.value.trim() ? t('common.error') : '',
)
const lastNameError = computed<string>(() =>
  submitted.value && !lastName.value.trim() ? t('common.error') : '',
)
const orgNameError = computed<string>(() =>
  submitted.value && !orgName.value.trim() ? t('common.error') : '',
)
const cityError = computed<string>(() =>
  submitted.value && !city.value.trim() ? t('common.error') : '',
)
const agreeTermsError = computed<string>(() =>
  submitted.value && !agreeTerms.value ? t('auth.register.agreeTermsRequired') : '',
)

const isFormValid = computed<boolean>(
  () =>
    EMAIL_RE.test(email.value.trim()) &&
    password.value.length >= 8 &&
    password.value === passwordConfirm.value &&
    firstName.value.trim().length > 0 &&
    lastName.value.trim().length > 0 &&
    orgName.value.trim().length > 0 &&
    city.value.trim().length > 0 &&
    agreeTerms.value,
)

async function handleSubmit(): Promise<void> {
  submitted.value = true
  if (!isFormValid.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    await authStore.register({
      email: email.value.trim(),
      password: password.value,
      first_name: firstName.value.trim(),
      last_name: lastName.value.trim(),
      middle_name: middleName.value.trim() || null,
    })
    await router.push({
      path: '/provider/onboarding',
      query: {
        org_name: orgName.value.trim(),
        org_type: orgType.value,
        city: city.value.trim(),
      },
    })
  } catch {
    const msg = authStore.error ?? t('auth.register.errorGeneric')
    toast.error(msg)
    await nextTick()
    const el = document.querySelector<HTMLInputElement>(
      '[data-test-id="auth-register-provider-email-input"]',
    )
    el?.focus()
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="flex min-h-[calc(100vh-theme(spacing.16))] items-center justify-center px-4 py-10">
    <BaseCard class="w-full max-w-xl" padding="lg" elevation="md">
      <RouterLink
        to="/register"
        class="mb-4 inline-flex items-center gap-1 text-xs text-text-subtle hover:text-text"
        data-test-id="auth-register-provider-back-link"
      >
        <ChevronLeft class="h-3.5 w-3.5" aria-hidden="true" />
        {{ t('auth.register.backToRole') }}
      </RouterLink>

      <header class="mb-6">
        <h1
          class="text-2xl font-bold tracking-tight text-text"
          data-test-id="auth-register-provider-title"
        >
          {{ t('auth.register.providerFormTitle') }}
        </h1>
        <p class="mt-1 text-sm text-text-subtle">
          {{ t('auth.register.providerFormSubtitle') }}
        </p>
      </header>

      <form
        class="flex flex-col gap-6"
        novalidate
        data-test-id="auth-register-provider-form"
        @submit.prevent="handleSubmit"
      >
        <fieldset class="flex flex-col gap-4 border-0 p-0">
          <legend class="mb-1 text-xs font-semibold uppercase tracking-wider text-text-subtle">
            {{ t('auth.register.ownerSection') }}
          </legend>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <BaseInput
              v-model="firstName"
              :label="t('auth.register.firstName')"
              :placeholder="t('auth.register.firstNamePh')"
              :error="firstNameError"
              autocomplete="given-name"
              required
              test-id="auth-register-provider-first-name-input"
            />
            <BaseInput
              v-model="lastName"
              :label="t('auth.register.lastName')"
              :placeholder="t('auth.register.lastNamePh')"
              :error="lastNameError"
              autocomplete="family-name"
              required
              test-id="auth-register-provider-last-name-input"
            />
          </div>

          <BaseInput
            v-model="middleName"
            :label="t('auth.register.middleName')"
            :placeholder="t('auth.register.middleNamePh')"
            autocomplete="additional-name"
            test-id="auth-register-provider-middle-name-input"
          />

          <BaseInput
            v-model="email"
            type="email"
            :label="t('auth.register.email')"
            :placeholder="t('auth.register.emailPh')"
            :error="emailError"
            autocomplete="email"
            required
            test-id="auth-register-provider-email-input"
          >
            <template #prefix>
              <Mail class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseInput>

          <BaseInput
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            :label="t('auth.register.password')"
            :placeholder="t('auth.register.passwordPh')"
            :error="passwordError"
            autocomplete="new-password"
            required
            test-id="auth-register-provider-password-input"
          >
            <template #suffix>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-sm text-text-subtle hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                :aria-label="showPassword ? t('auth.login.passwordHide') : t('auth.login.passwordShow')"
                data-test-id="auth-register-provider-password-toggle"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
                <Eye v-else class="h-4 w-4" aria-hidden="true" />
              </button>
            </template>
          </BaseInput>

          <BaseInput
            v-model="passwordConfirm"
            :type="showPassword ? 'text' : 'password'"
            :label="t('auth.register.passwordConfirm')"
            :placeholder="t('auth.register.passwordPh')"
            :error="passwordConfirmError"
            autocomplete="new-password"
            required
            test-id="auth-register-provider-password-confirm-input"
          />
        </fieldset>

        <fieldset class="flex flex-col gap-4 border-0 p-0">
          <legend class="mb-1 text-xs font-semibold uppercase tracking-wider text-text-subtle">
            {{ t('auth.register.orgSection') }}
          </legend>

          <BaseInput
            v-model="orgName"
            :label="t('auth.register.orgName')"
            :placeholder="t('auth.register.orgNamePh')"
            :error="orgNameError"
            required
            test-id="auth-register-provider-org-name-input"
          >
            <template #prefix>
              <Building2 class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseInput>

          <BaseSelect
            v-model="orgType"
            :options="orgTypeOptions"
            :label="t('auth.register.orgType')"
            required
            test-id="auth-register-provider-org-type-select"
          />

          <BaseInput
            v-model="city"
            :label="t('auth.register.city')"
            :placeholder="t('auth.register.cityPh')"
            :error="cityError"
            autocomplete="address-level2"
            required
            test-id="auth-register-provider-city-input"
          >
            <template #prefix>
              <MapPin class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseInput>
        </fieldset>

        <div class="flex flex-col gap-1">
          <BaseCheckbox
            v-model="agreeTerms"
            :label="t('auth.register.agreeTerms')"
            data-test-id="auth-register-provider-agree-terms-checkbox"
          />
          <p
            v-if="agreeTermsError"
            class="text-sm text-danger"
            data-test-id="auth-register-provider-agree-terms-error"
          >
            {{ agreeTermsError }}
          </p>
        </div>

        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          full-width
          :loading="isSubmitting"
          test-id="auth-register-provider-submit-btn"
        >
          {{ t('auth.register.submitProvider') }}
        </BaseButton>
      </form>

      <p class="mt-6 text-center text-sm text-text-subtle">
        {{ t('auth.register.alreadyHaveAccount') }}
        <RouterLink
          to="/login"
          class="ml-1 font-semibold text-text hover:text-accent"
          data-test-id="auth-register-provider-login-link"
        >
          {{ t('auth.register.signIn') }}
        </RouterLink>
      </p>
    </BaseCard>
  </section>
</template>
