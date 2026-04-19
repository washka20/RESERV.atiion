<script setup lang="ts">
/**
 * LoginView — форма логина по email/password.
 *
 * При успехе:
 *  - сохраняет токены и user через authStore.login();
 *  - редирект на `?redirect=` из query или на /catalog.
 *
 * Ошибки login() показываются через BaseToast + focus обратно на email input.
 * Social login (Google/Apple) — placeholders (disabled) до интеграции провайдеров.
 */
import { nextTick, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Eye, EyeOff, Mail } from 'lucide-vue-next'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseCheckbox from '@/shared/components/base/BaseCheckbox.vue'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

const email = ref<string>('')
const password = ref<string>('')
const rememberMe = ref<boolean>(false)
const showPassword = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)
const emailInputRef = ref<InstanceType<typeof BaseInput> | null>(null)

async function handleSubmit(): Promise<void> {
  if (isSubmitting.value) return
  isSubmitting.value = true
  try {
    await authStore.login({ email: email.value, password: password.value })
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null
    if (redirect && redirect.startsWith('/')) {
      await router.push(redirect)
    } else {
      await router.push({ name: 'catalog' })
    }
  } catch {
    const msg = authStore.error ?? t('auth.login.errorGeneric')
    toast.error(msg)
    await nextTick()
    const el = document.querySelector<HTMLInputElement>(
      '[data-test-id="auth-login-email-input"]',
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
      <header class="mb-6 text-center">
        <h1
          class="text-2xl font-bold tracking-tight text-text"
          data-test-id="auth-login-title"
        >
          {{ t('auth.login.title') }}
        </h1>
        <p class="mt-2 text-sm text-text-subtle">
          {{ t('auth.login.subtitle') }}
        </p>
      </header>

      <form
        class="flex flex-col gap-4"
        novalidate
        data-test-id="auth-login-form"
        @submit.prevent="handleSubmit"
      >
        <BaseInput
          ref="emailInputRef"
          v-model="email"
          type="email"
          :label="t('auth.login.emailLabel')"
          :placeholder="t('auth.login.emailPlaceholder')"
          autocomplete="email"
          required
          test-id="auth-login-email-input"
        >
          <template #prefix>
            <Mail class="h-4 w-4" aria-hidden="true" />
          </template>
        </BaseInput>

        <BaseInput
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          :label="t('auth.login.passwordLabel')"
          :placeholder="t('auth.login.passwordPlaceholder')"
          autocomplete="current-password"
          required
          test-id="auth-login-password-input"
        >
          <template #suffix>
            <button
              type="button"
              class="inline-flex h-7 w-7 items-center justify-center rounded-sm text-text-subtle hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
              :aria-label="showPassword ? t('auth.login.passwordHide') : t('auth.login.passwordShow')"
              data-test-id="auth-login-password-toggle"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
              <Eye v-else class="h-4 w-4" aria-hidden="true" />
            </button>
          </template>
        </BaseInput>

        <div class="flex items-center justify-between">
          <BaseCheckbox
            v-model="rememberMe"
            :label="t('auth.login.rememberMe')"
            data-test-id="auth-login-remember-toggle"
          />
          <RouterLink
            to="/forgot-password"
            class="text-sm text-text-subtle hover:text-text"
            data-test-id="auth-login-forgot-link"
          >
            {{ t('auth.login.forgotPassword') }}
          </RouterLink>
        </div>

        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          full-width
          :loading="isSubmitting"
          test-id="auth-login-submit-btn"
        >
          {{ t('auth.login.submit') }}
        </BaseButton>
      </form>

      <div class="my-6 flex items-center gap-3 text-xs text-text-subtle">
        <span class="h-px flex-1 bg-border" />
        <span class="uppercase tracking-wider">{{ t('auth.login.orDivider') }}</span>
        <span class="h-px flex-1 bg-border" />
      </div>

      <div class="flex flex-col gap-2">
        <BaseButton
          variant="secondary"
          full-width
          disabled
          test-id="auth-login-google-btn"
        >
          {{ t('auth.login.withGoogle') }}
          <span class="ml-2 text-xs text-text-subtle">({{ t('auth.login.comingSoon') }})</span>
        </BaseButton>
        <BaseButton
          variant="secondary"
          full-width
          disabled
          test-id="auth-login-apple-btn"
        >
          {{ t('auth.login.withApple') }}
          <span class="ml-2 text-xs text-text-subtle">({{ t('auth.login.comingSoon') }})</span>
        </BaseButton>
      </div>

      <p class="mt-6 text-center text-sm text-text-subtle">
        {{ t('auth.login.noAccount') }}
        <RouterLink
          to="/register"
          class="ml-1 font-semibold text-text hover:text-accent"
          data-test-id="auth-login-register-link"
        >
          {{ t('auth.login.register') }}
        </RouterLink>
      </p>
    </BaseCard>
  </section>
</template>
