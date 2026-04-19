<script setup lang="ts">
/**
 * RegisterCustomerView — форма регистрации customer-а.
 *
 * Отправляет `authStore.register({email, password, first_name, last_name, middle_name?})`.
 * При успехе backend возвращает user + tokens — store уже их применяет.
 * После — редирект на /catalog.
 *
 * Валидация клиентская минимальная (формат email, длина пароля, совпадение подтверждения,
 * agreeTerms) — финальная валидация на backend через FormRequest.
 */
import { computed, nextTick, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, Eye, EyeOff, Mail } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
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
const isSubmitting = ref<boolean>(false)
const submitted = ref<boolean>(false)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const emailError = computed<string>(() => {
  if (!submitted.value) return ''
  if (!email.value.trim()) return t('common.error')
  if (!EMAIL_RE.test(email.value.trim())) return t('common.error')
  return ''
})

const passwordError = computed<string>(() => {
  if (!submitted.value) return ''
  if (password.value.length < 8) return t('auth.register.passwordTooShort')
  return ''
})

const passwordConfirmError = computed<string>(() => {
  if (!submitted.value && !passwordConfirm.value) return ''
  if (passwordConfirm.value && passwordConfirm.value !== password.value) {
    return t('auth.register.passwordMismatch')
  }
  return ''
})

const firstNameError = computed<string>(() =>
  submitted.value && !firstName.value.trim() ? t('common.error') : '',
)
const lastNameError = computed<string>(() =>
  submitted.value && !lastName.value.trim() ? t('common.error') : '',
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
    await router.push({ name: 'catalog' })
  } catch {
    const msg = authStore.error ?? t('auth.register.errorGeneric')
    toast.error(msg)
    await nextTick()
    const el = document.querySelector<HTMLInputElement>(
      '[data-test-id="auth-register-customer-email-input"]',
    )
    el?.focus()
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="flex min-h-[calc(100vh-theme(spacing.16))] items-center justify-center px-4 py-10">
    <BaseCard class="w-full max-w-md" padding="lg" elevation="md">
      <RouterLink
        to="/register"
        class="mb-4 inline-flex items-center gap-1 text-xs text-text-subtle hover:text-text"
        data-test-id="auth-register-customer-back-link"
      >
        <ChevronLeft class="h-3.5 w-3.5" aria-hidden="true" />
        {{ t('auth.register.backToRole') }}
      </RouterLink>

      <header class="mb-6">
        <h1
          class="text-2xl font-bold tracking-tight text-text"
          data-test-id="auth-register-customer-title"
        >
          {{ t('auth.register.customerFormTitle') }}
        </h1>
        <p class="mt-1 text-sm text-text-subtle">
          {{ t('auth.register.customerFormSubtitle') }}
        </p>
      </header>

      <form
        class="flex flex-col gap-4"
        novalidate
        data-test-id="auth-register-customer-form"
        @submit.prevent="handleSubmit"
      >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseInput
            v-model="firstName"
            :label="t('auth.register.firstName')"
            :placeholder="t('auth.register.firstNamePh')"
            :error="firstNameError"
            autocomplete="given-name"
            required
            test-id="auth-register-customer-first-name-input"
          />
          <BaseInput
            v-model="lastName"
            :label="t('auth.register.lastName')"
            :placeholder="t('auth.register.lastNamePh')"
            :error="lastNameError"
            autocomplete="family-name"
            required
            test-id="auth-register-customer-last-name-input"
          />
        </div>

        <BaseInput
          v-model="middleName"
          :label="t('auth.register.middleName')"
          :placeholder="t('auth.register.middleNamePh')"
          autocomplete="additional-name"
          test-id="auth-register-customer-middle-name-input"
        />

        <BaseInput
          v-model="email"
          type="email"
          :label="t('auth.register.email')"
          :placeholder="t('auth.register.emailPh')"
          :error="emailError"
          autocomplete="email"
          required
          test-id="auth-register-customer-email-input"
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
          test-id="auth-register-customer-password-input"
        >
          <template #suffix>
            <button
              type="button"
              class="inline-flex h-7 w-7 items-center justify-center rounded-sm text-text-subtle hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
              :aria-label="showPassword ? t('auth.login.passwordHide') : t('auth.login.passwordShow')"
              data-test-id="auth-register-customer-password-toggle"
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
          test-id="auth-register-customer-password-confirm-input"
        />

        <div class="flex flex-col gap-1">
          <BaseCheckbox
            v-model="agreeTerms"
            :label="t('auth.register.agreeTerms')"
            data-test-id="auth-register-customer-agree-terms-checkbox"
          />
          <p
            v-if="agreeTermsError"
            class="text-sm text-danger"
            data-test-id="auth-register-customer-agree-terms-error"
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
          test-id="auth-register-customer-submit-btn"
        >
          {{ t('auth.register.submitCustomer') }}
        </BaseButton>
      </form>

      <p class="mt-6 text-center text-sm text-text-subtle">
        {{ t('auth.register.alreadyHaveAccount') }}
        <RouterLink
          to="/login"
          class="ml-1 font-semibold text-text hover:text-accent"
          data-test-id="auth-register-customer-login-link"
        >
          {{ t('auth.register.signIn') }}
        </RouterLink>
      </p>
    </BaseCard>
  </section>
</template>
