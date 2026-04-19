<script setup lang="ts">
/**
 * VerifyPhoneView — OTP верификация телефона.
 *
 * Backend `/auth/verify-phone` пока не реализован (v1.1) — при 404 показываем stub.
 *
 * UI: 6 маленьких input-ов с auto-focus next (OTP pattern).
 * Resend button с countdown 60с (через window.setInterval).
 */
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Phone, Rocket } from 'lucide-vue-next'
import { apiClient } from '@/api/client'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const router = useRouter()
const { toast } = useToast()

const digits = ref<string[]>(['', '', '', '', '', ''])
const inputs = ref<(HTMLInputElement | null)[]>([null, null, null, null, null, null])
const isSubmitting = ref<boolean>(false)
const isStub = ref<boolean>(false)
const error = ref<string>('')

const resendSeconds = ref<number>(60)
let resendTimer: number | null = null

const code = computed<string>(() => digits.value.join(''))
const isCodeComplete = computed<boolean>(() => code.value.length === 6 && /^\d{6}$/.test(code.value))

function startResendCountdown(): void {
  resendSeconds.value = 60
  if (resendTimer !== null) window.clearInterval(resendTimer)
  resendTimer = window.setInterval(() => {
    if (resendSeconds.value <= 0) {
      if (resendTimer !== null) window.clearInterval(resendTimer)
      resendTimer = null
      return
    }
    resendSeconds.value -= 1
  }, 1000)
}

startResendCountdown()

onBeforeUnmount(() => {
  if (resendTimer !== null) window.clearInterval(resendTimer)
})

function handleDigitInput(index: number, event: Event): void {
  const target = event.target as HTMLInputElement
  const raw = target.value.replace(/\D/g, '').slice(-1)
  digits.value[index] = raw
  target.value = raw
  if (raw && index < 5) {
    void nextTick(() => inputs.value[index + 1]?.focus())
  }
  error.value = ''
}

function handleKeydown(index: number, event: KeyboardEvent): void {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
    void nextTick(() => inputs.value[index - 1]?.focus())
  }
}

function handlePaste(event: ClipboardEvent): void {
  const pasted = event.clipboardData?.getData('text').replace(/\D/g, '').slice(0, 6) ?? ''
  if (!pasted) return
  event.preventDefault()
  for (let i = 0; i < 6; i += 1) {
    digits.value[i] = pasted[i] ?? ''
  }
  const lastIdx = Math.min(pasted.length - 1, 5)
  void nextTick(() => inputs.value[lastIdx]?.focus())
}

function extractStatus(err: unknown): number | null {
  if (err && typeof err === 'object' && 'response' in err) {
    const res = (err as { response?: { status?: number } }).response
    return res?.status ?? null
  }
  return null
}

async function handleSubmit(): Promise<void> {
  if (!isCodeComplete.value || isSubmitting.value) return
  isSubmitting.value = true
  error.value = ''
  try {
    await apiClient.post('/auth/verify-phone', { code: code.value })
    toast.success(t('auth.verify.emailSuccess'))
    await router.push({ name: 'catalog' })
  } catch (err) {
    const code404 = extractStatus(err)
    if (code404 === 404) {
      isStub.value = true
    } else {
      error.value = t('auth.verify.phoneError')
    }
  } finally {
    isSubmitting.value = false
  }
}

async function handleResend(): Promise<void> {
  if (resendSeconds.value > 0) return
  try {
    await apiClient.post('/auth/verify-phone/resend')
    toast.success(t('auth.verify.phoneResend'))
    startResendCountdown()
  } catch (err) {
    const code404 = extractStatus(err)
    if (code404 === 404) {
      isStub.value = true
    } else {
      toast.error(t('auth.verify.phoneError'))
    }
  }
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
        data-test-id="auth-verify-phone-stub"
      >
        <template #icon>
          <Rocket class="h-10 w-10" aria-hidden="true" />
        </template>
        <template #action>
          <BaseButton
            variant="primary"
            size="md"
            test-id="auth-verify-phone-home-btn"
            @click="goHome"
          >
            {{ t('auth.verify.stubCta') }}
          </BaseButton>
        </template>
      </BaseEmptyState>

      <template v-else>
        <header class="mb-6 text-center">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-surface-muted text-text-subtle">
            <Phone class="h-6 w-6" aria-hidden="true" />
          </div>
          <h1
            class="text-xl font-bold tracking-tight text-text"
            data-test-id="auth-verify-phone-title"
          >
            {{ t('auth.verify.phoneTitle') }}
          </h1>
          <p class="mt-1 text-sm text-text-subtle">{{ t('auth.verify.phoneSubtitle') }}</p>
        </header>

        <form
          class="flex flex-col gap-4"
          novalidate
          data-test-id="auth-verify-phone-form"
          @submit.prevent="handleSubmit"
        >
          <div
            class="flex justify-center gap-2"
            data-test-id="auth-verify-phone-otp-row"
            @paste="handlePaste"
          >
            <input
              v-for="(d, i) in digits"
              :key="i"
              :ref="(el) => { inputs[i] = el as HTMLInputElement | null }"
              :value="d"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="1"
              :data-test-id="`auth-verify-phone-otp-input-${i}`"
              class="h-12 w-10 rounded-md border border-border bg-surface text-center text-lg font-semibold text-text transition-colors focus:border-accent focus:outline-none"
              :class="error ? 'border-danger' : ''"
              @input="handleDigitInput(i, $event)"
              @keydown="handleKeydown(i, $event)"
            >
          </div>

          <p
            v-if="error"
            class="text-center text-sm text-danger"
            data-test-id="auth-verify-phone-error"
          >
            {{ error }}
          </p>

          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            full-width
            :loading="isSubmitting"
            :disabled="!isCodeComplete"
            test-id="auth-verify-phone-submit-btn"
          >
            {{ t('auth.verify.phoneSubmit') }}
          </BaseButton>

          <BaseButton
            variant="ghost"
            full-width
            :disabled="resendSeconds > 0"
            test-id="auth-verify-phone-resend-btn"
            @click="handleResend"
          >
            <template v-if="resendSeconds > 0">
              {{ t('auth.verify.phoneResendWait', { seconds: resendSeconds }) }}
            </template>
            <template v-else>
              {{ t('auth.verify.phoneResend') }}
            </template>
          </BaseButton>
        </form>
      </template>
    </BaseCard>
  </section>
</template>
