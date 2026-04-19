<script setup lang="ts">
/**
 * ResetPasswordView — форма установки нового пароля по token-ссылке.
 *
 * Backend `/auth/reset-password/{token}` пока не реализован (v1.1):
 *  - 404 → stub empty state;
 *  - 200 → success screen + CTA "Войти".
 */
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Check, Eye, EyeOff, Rocket } from 'lucide-vue-next'
import { apiClient } from '@/api/client'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const password = ref<string>('')
const passwordConfirm = ref<string>('')
const showPassword = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)
const isStub = ref<boolean>(false)
const isSuccess = ref<boolean>(false)

const token = computed<string>(() => {
  const raw = route.params.token
  return typeof raw === 'string' ? raw : ''
})

const mismatch = computed<boolean>(
  () => passwordConfirm.value.length > 0 && password.value !== passwordConfirm.value,
)
const tooShort = computed<boolean>(() => password.value.length > 0 && password.value.length < 8)
const isValid = computed<boolean>(
  () => password.value.length >= 8 && password.value === passwordConfirm.value,
)

function extractStatus(err: unknown): number | null {
  if (err && typeof err === 'object' && 'response' in err) {
    const res = (err as { response?: { status?: number } }).response
    return res?.status ?? null
  }
  return null
}

async function handleSubmit(): Promise<void> {
  if (!isValid.value || isSubmitting.value) return
  isSubmitting.value = true
  try {
    await apiClient.post(`/auth/reset-password/${encodeURIComponent(token.value)}`, {
      password: password.value,
    })
    isSuccess.value = true
  } catch (err) {
    const code = extractStatus(err)
    if (code === 404) isStub.value = true
  } finally {
    isSubmitting.value = false
  }
}

async function goLogin(): Promise<void> {
  await router.push({ name: 'login' })
}

async function goHome(): Promise<void> {
  await router.push({ name: 'catalog' })
}
</script>

<template>
  <section class="flex min-h-[calc(100vh-theme(spacing.16))] items-center justify-center px-4 py-10">
    <BaseCard class="w-full max-w-md" padding="lg" elevation="md">
      <BaseEmptyState
        v-if="isStub"
        :title="t('auth.verify.stubTitle')"
        :description="t('auth.verify.stubDesc')"
        data-test-id="auth-reset-password-stub"
      >
        <template #icon>
          <Rocket class="h-10 w-10" aria-hidden="true" />
        </template>
        <template #action>
          <BaseButton
            variant="primary"
            size="md"
            test-id="auth-reset-password-home-btn"
            @click="goHome"
          >
            {{ t('auth.verify.stubCta') }}
          </BaseButton>
        </template>
      </BaseEmptyState>

      <div
        v-else-if="isSuccess"
        class="flex flex-col gap-4 py-4 text-center"
        data-test-id="auth-reset-password-success"
      >
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success/10 text-success">
          <Check class="h-6 w-6" aria-hidden="true" />
        </div>
        <h1 class="text-xl font-bold text-text">{{ t('auth.reset.successTitle') }}</h1>
        <p class="text-sm text-text-subtle">{{ t('auth.reset.successDesc') }}</p>
        <BaseButton
          variant="primary"
          size="lg"
          full-width
          test-id="auth-reset-password-login-btn"
          @click="goLogin"
        >
          {{ t('auth.reset.goToLogin') }}
        </BaseButton>
      </div>

      <template v-else>
        <header class="mb-6">
          <h1
            class="text-2xl font-bold tracking-tight text-text"
            data-test-id="auth-reset-password-title"
          >
            {{ t('auth.reset.title') }}
          </h1>
          <p class="mt-1 text-sm text-text-subtle">{{ t('auth.reset.subtitle') }}</p>
        </header>

        <form
          class="flex flex-col gap-4"
          novalidate
          data-test-id="auth-reset-password-form"
          @submit.prevent="handleSubmit"
        >
          <BaseInput
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            :label="t('auth.reset.password')"
            :error="tooShort ? t('auth.register.passwordTooShort') : ''"
            autocomplete="new-password"
            required
            test-id="auth-reset-password-input"
          >
            <template #suffix>
              <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-sm text-text-subtle hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                :aria-label="showPassword ? t('auth.login.passwordHide') : t('auth.login.passwordShow')"
                data-test-id="auth-reset-password-toggle"
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
            :label="t('auth.reset.passwordConfirm')"
            :error="mismatch ? t('auth.register.passwordMismatch') : ''"
            autocomplete="new-password"
            required
            test-id="auth-reset-password-confirm-input"
          />

          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            full-width
            :loading="isSubmitting"
            :disabled="!isValid"
            test-id="auth-reset-password-submit-btn"
          >
            {{ t('auth.reset.submit') }}
          </BaseButton>
        </form>
      </template>
    </BaseCard>
  </section>
</template>
