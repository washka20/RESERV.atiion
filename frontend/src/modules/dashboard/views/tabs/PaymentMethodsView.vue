<script setup lang="ts">
/**
 * PaymentMethodsView — вкладка dashboard "Способы оплаты".
 *
 * Stub до интеграции платежного провайдера (Plan 12+). Добавление карты
 * disabled — кнопка только для демонстрации future UX.
 */
import { useI18n } from 'vue-i18n'
import { CreditCard } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'

const { t } = useI18n()
</script>

<template>
  <section data-test-id="dashboard-tab-payment-methods">
    <BaseCard padding="lg">
      <header class="mb-4">
        <h2 class="text-lg font-semibold text-text">
          {{ t('profile.paymentTitle') }}
        </h2>
      </header>

      <div data-test-id="payment-methods-empty">
        <BaseEmptyState
          :title="t('profile.paymentEmptyTitle')"
          :description="t('profile.paymentEmptyDesc')"
        >
          <template #icon>
            <CreditCard class="h-8 w-8" aria-hidden="true" />
          </template>
          <template #action>
            <BaseButton
              variant="primary"
              disabled
              test-id="payment-methods-add-btn"
            >
              {{ t('profile.paymentAddCta') }}
            </BaseButton>
          </template>
        </BaseEmptyState>
      </div>
    </BaseCard>
  </section>
</template>
