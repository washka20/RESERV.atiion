<script setup lang="ts">
/**
 * VerifyEmailView — landing для ссылки подтверждения email.
 *
 * Endpoint `POST /auth/verify-email/{token}` на backend ещё не реализован (v1.1).
 * Поэтому при 404 показываем BaseEmptyState с CTA "На главную" вместо спиннера —
 * пользователю понятно что фича ещё не запущена.
 *
 * На success (когда endpoint появится) — toast и redirect на /catalog.
 */
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Mail, Rocket } from 'lucide-vue-next'
import { apiClient } from '@/api/client'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseSpinner from '@/shared/components/base/BaseSpinner.vue'
import { useToast } from '@/shared/composables/useToast'

type Status = 'loading' | 'success' | 'stub' | 'error'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { toast } = useToast()

const status = ref<Status>('loading')

function extractStatus(err: unknown): number | null {
  if (err && typeof err === 'object' && 'response' in err) {
    const res = (err as { response?: { status?: number } }).response
    return res?.status ?? null
  }
  return null
}

async function goHome(): Promise<void> {
  await router.push({ name: 'catalog' })
}

onMounted(async () => {
  const token = route.params.token
  if (typeof token !== 'string' || token.length === 0) {
    status.value = 'stub'
    return
  }
  try {
    await apiClient.post(`/auth/verify-email/${encodeURIComponent(token)}`)
    status.value = 'success'
    toast.success(t('auth.verify.emailSuccess'))
    setTimeout(() => {
      void router.push({ name: 'catalog' })
    }, 1000)
  } catch (err) {
    const code = extractStatus(err)
    status.value = code === 404 ? 'stub' : 'error'
  }
})
</script>

<template>
  <section class="flex min-h-[calc(100vh-theme(spacing.16))] items-center justify-center px-4 py-10">
    <BaseCard class="w-full max-w-md" padding="lg" elevation="md">
      <div
        v-if="status === 'loading'"
        class="flex flex-col items-center gap-3 py-8 text-center"
        data-test-id="auth-verify-email-loading"
      >
        <BaseSpinner size="md" />
        <p class="text-sm text-text-subtle">{{ t('auth.verify.emailLoading') }}</p>
      </div>

      <div
        v-else-if="status === 'success'"
        class="flex flex-col items-center gap-3 py-8 text-center"
        data-test-id="auth-verify-email-success"
      >
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success/10 text-success">
          <Mail class="h-6 w-6" aria-hidden="true" />
        </div>
        <h1 class="text-xl font-bold text-text">
          {{ t('auth.verify.emailSuccess') }}
        </h1>
      </div>

      <BaseEmptyState
        v-else
        :title="t('auth.verify.stubTitle')"
        :description="t('auth.verify.stubDesc')"
        data-test-id="auth-verify-email-stub"
      >
        <template #icon>
          <Rocket class="h-10 w-10" aria-hidden="true" />
        </template>
        <template #action>
          <BaseButton
            variant="primary"
            size="md"
            test-id="auth-verify-email-home-btn"
            @click="goHome"
          >
            {{ t('auth.verify.stubCta') }}
          </BaseButton>
        </template>
      </BaseEmptyState>
    </BaseCard>
  </section>
</template>
