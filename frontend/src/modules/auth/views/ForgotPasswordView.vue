<script setup lang="ts">
/**
 * ForgotPasswordView — форма запроса ссылки сброса пароля.
 *
 * Backend `/auth/forgot-password` пока не реализован (v1.1):
 *  - 404 → показываем stub empty state;
 *  - успех/любой другой код → generic success message (не раскрываем,
 *    зарегистрирован email или нет, чтобы предотвратить user enumeration).
 */
import { computed, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, Mail, Rocket } from 'lucide-vue-next'
import { apiClient } from '@/api/client'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'

const { t } = useI18n()
const router = useRouter()

const email = ref<string>('')
const isSubmitting = ref<boolean>(false)
const isStub = ref<boolean>(false)
const isSent = ref<boolean>(false)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const isValid = computed<boolean>(() => EMAIL_RE.test(email.value.trim()))

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
    await apiClient.post('/auth/forgot-password', { email: email.value.trim() })
    isSent.value = true
  } catch (err) {
    const code = extractStatus(err)
    if (code === 404) {
      isStub.value = true
    } else {
      isSent.value = true
    }
  } finally {
    isSubmitting.value = false
  }
}

function reset(): void {
  isSent.value = false
  email.value = ''
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
        data-test-id="auth-forgot-password-stub"
      >
        <template #icon>
          <Rocket class="h-10 w-10" aria-hidden="true" />
        </template>
        <template #action>
          <BaseButton
            variant="primary"
            size="md"
            test-id="auth-forgot-password-home-btn"
            @click="goHome"
          >
            {{ t('auth.verify.stubCta') }}
          </BaseButton>
        </template>
      </BaseEmptyState>

      <div
        v-else-if="isSent"
        class="flex flex-col gap-4 py-4 text-center"
        data-test-id="auth-forgot-password-success"
      >
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success/10 text-success">
          <Mail class="h-6 w-6" aria-hidden="true" />
        </div>
        <h1 class="text-xl font-bold text-text">{{ t('auth.forgot.title') }}</h1>
        <p class="text-sm text-text-subtle">{{ t('auth.forgot.successMessage') }}</p>
        <BaseButton
          variant="ghost"
          full-width
          test-id="auth-forgot-password-reset-btn"
          @click="reset"
        >
          {{ t('auth.forgot.anotherEmail') }}
        </BaseButton>
      </div>

      <template v-else>
        <RouterLink
          to="/login"
          class="mb-4 inline-flex items-center gap-1 text-xs text-text-subtle hover:text-text"
          data-test-id="auth-forgot-password-back-link"
        >
          <ChevronLeft class="h-3.5 w-3.5" aria-hidden="true" />
          {{ t('auth.forgot.backToLogin') }}
        </RouterLink>
        <header class="mb-6">
          <h1
            class="text-2xl font-bold tracking-tight text-text"
            data-test-id="auth-forgot-password-title"
          >
            {{ t('auth.forgot.title') }}
          </h1>
          <p class="mt-1 text-sm text-text-subtle">{{ t('auth.forgot.subtitle') }}</p>
        </header>

        <form
          class="flex flex-col gap-4"
          novalidate
          data-test-id="auth-forgot-password-form"
          @submit.prevent="handleSubmit"
        >
          <BaseInput
            v-model="email"
            type="email"
            :label="t('auth.forgot.emailLabel')"
            placeholder="you@example.com"
            autocomplete="email"
            required
            test-id="auth-forgot-password-email-input"
          >
            <template #prefix>
              <Mail class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseInput>
          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            full-width
            :loading="isSubmitting"
            :disabled="!isValid"
            test-id="auth-forgot-password-submit-btn"
          >
            {{ t('auth.forgot.submit') }}
          </BaseButton>
        </form>
      </template>
    </BaseCard>
  </section>
</template>
