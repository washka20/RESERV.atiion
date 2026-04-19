<script setup lang="ts">
/**
 * OrgPayoutsView — настройки выплат + транзакции + 30-day chart.
 *
 * Два блока:
 *   1. Форма настроек выплат (BaseCard): bank_name, account_number, holder, BIC,
 *      schedule, minimum_payout — submit через payoutsStore.updateSettings().
 *   2. Chart + transactions table (BaseDataTable) с gross/fee/net formatting.
 *
 * Backend endpoints реально работают (Plan 8): GET /payout-settings, GET /payouts.
 * При ошибке 404/5xx fallback на пустые значения + toast.
 */
import { computed, onMounted, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseChart from '@/shared/components/base/BaseChart.vue'
import BaseDataTable from '@/shared/components/base/BaseDataTable.vue'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import { usePayoutsStore } from '@/stores/payouts.store'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const payoutsStore = usePayoutsStore()
const authStore = useAuthStore()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const canManage = computed<boolean>(() =>
  authStore.canAccessOrg(orgSlug.value, 'payouts.manage'),
)

/**
 * Локальный reactive state формы — хранится отдельно от store, чтобы
 * не мутировать источник данных до submit.
 */
const form = reactive({
  bankName: '',
  accountNumber: '',
  accountHolder: '',
  bic: '',
  schedule: 'monthly' as 'weekly' | 'biweekly' | 'monthly' | 'on_request',
  minimumPayout: 0,
})

const scheduleOptions = computed(() => [
  { value: 'weekly', label: t('provider.payouts.scheduleWeekly') },
  { value: 'biweekly', label: t('provider.payouts.scheduleBiweekly') },
  { value: 'monthly', label: t('provider.payouts.scheduleMonthly') },
  { value: 'on_request', label: t('provider.payouts.scheduleOnRequest') },
])

const scheduleModel = computed({
  get: () => form.schedule,
  set: (v: string | number) => {
    form.schedule = String(v) as typeof form.schedule
  },
})

const columns = computed(() => [
  { key: 'id', label: 'ID' },
  { key: 'gross', label: t('provider.payouts.colGross'), align: 'right' as const },
  { key: 'fee', label: t('provider.payouts.colFee'), align: 'right' as const },
  { key: 'net', label: t('provider.payouts.colNet'), align: 'right' as const },
  { key: 'status', label: t('provider.payouts.colStatus') },
  { key: 'createdAt', label: t('provider.payouts.colDate') },
])

function formatMoney(amount: number, currency = 'RUB'): string {
  return `${new Intl.NumberFormat('ru-RU').format(amount / 100)} ${currency}`
}

function statusLabel(status: 'pending' | 'completed' | 'failed'): string {
  const map = {
    pending: t('provider.payouts.statusPending'),
    completed: t('provider.payouts.statusCompleted'),
    failed: t('provider.payouts.statusFailed'),
  } as const
  return map[status]
}

const rows = computed(() =>
  payoutsStore.transactions.map((tx) => {
    const fee = Math.round(tx.amount * 0.1)
    const net = tx.amount - fee
    return {
      id: tx.id.slice(0, 8),
      gross: formatMoney(tx.amount, tx.currency),
      fee: `-${formatMoney(fee, tx.currency)}`,
      net: formatMoney(net, tx.currency),
      status: statusLabel(tx.status),
      createdAt: new Date(tx.createdAt).toLocaleDateString('ru-RU'),
    }
  }),
)

/**
 * Mock chart-данные — 30 точек. Реальные данные появятся в Plan 15 при
 * агрегации транзакций bc-wise.
 */
const chartData = computed(() =>
  Array.from({ length: 30 }, (_, i) => ({
    x: `${i + 1}`,
    y: Math.round(80000 + Math.random() * 120000) / 100,
  })),
)

async function handleSubmit(): Promise<void> {
  try {
    await payoutsStore.updateSettings(orgSlug.value, {
      bankAccount: form.accountNumber || null,
      iban: form.bic || null,
      accountHolder: form.accountHolder || null,
      autoPayoutEnabled: form.schedule !== 'on_request',
    })
    toast.success(t('provider.payouts.saveSuccess'))
  } catch {
    toast.error(payoutsStore.error ?? t('provider.payouts.saveError'))
  }
}

watch(
  () => payoutsStore.settings,
  (settings) => {
    if (!settings) return
    form.accountNumber = settings.bankAccount ?? ''
    form.bic = settings.iban ?? ''
    form.accountHolder = settings.accountHolder ?? ''
    form.schedule = settings.autoPayoutEnabled ? 'monthly' : 'on_request'
  },
  { immediate: true },
)

onMounted(async () => {
  await Promise.allSettled([
    payoutsStore.loadSettings(orgSlug.value),
    payoutsStore.loadTransactions(orgSlug.value),
  ])
})
</script>

<template>
  <section data-test-id="org-payouts-view" class="flex flex-col gap-6">
    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-payouts-title"
    >
      {{ t('provider.payouts.title') }}
    </h1>

    <BaseCard padding="lg">
      <h2
        class="text-lg font-semibold text-text mb-4"
        data-test-id="org-payouts-settings-title"
      >
        {{ t('provider.payouts.settingsTitle') }}
      </h2>
      <form
        class="grid grid-cols-1 gap-4 md:grid-cols-2"
        novalidate
        data-test-id="org-payouts-settings-form"
        @submit.prevent="handleSubmit"
      >
        <BaseInput
          v-model="form.bankName"
          :label="t('provider.payouts.bankName')"
          :disabled="!canManage"
          test-id="org-payouts-bank-name-input"
        />
        <BaseInput
          v-model="form.accountNumber"
          :label="t('provider.payouts.accountNumber')"
          :disabled="!canManage"
          test-id="org-payouts-account-number-input"
        />
        <BaseInput
          v-model="form.accountHolder"
          :label="t('provider.payouts.accountHolder')"
          :disabled="!canManage"
          test-id="org-payouts-account-holder-input"
        />
        <BaseInput
          v-model="form.bic"
          :label="t('provider.payouts.bic')"
          :disabled="!canManage"
          test-id="org-payouts-bic-input"
        />
        <BaseSelect
          v-model="scheduleModel"
          :options="scheduleOptions"
          :label="t('provider.payouts.schedule')"
          :disabled="!canManage"
          test-id="org-payouts-schedule-select"
        />
        <BaseInput
          v-model="form.minimumPayout"
          type="number"
          :label="t('provider.payouts.minPayout')"
          :disabled="!canManage"
          test-id="org-payouts-min-payout-input"
        />
        <div class="md:col-span-2 flex items-center justify-end">
          <BaseButton
            variant="primary"
            type="submit"
            :disabled="!canManage"
            :loading="payoutsStore.isLoading"
            test-id="org-payouts-save-btn"
          >
            {{ t('provider.payouts.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <BaseChart
      type="bar"
      :data="chartData"
      :label="t('provider.payouts.chartTitle')"
      :height="220"
    />

    <BaseCard padding="md">
      <h2
        class="text-lg font-semibold text-text mb-3 flex items-center gap-2"
        data-test-id="org-payouts-transactions-title"
      >
        {{ t('provider.payouts.transactionsTitle') }}
        <BaseBadge variant="neutral">{{ rows.length }}</BaseBadge>
      </h2>
      <BaseDataTable
        :columns="columns"
        :rows="rows"
        :empty-message="t('provider.payouts.transactionsEmpty')"
        data-test-id="org-payouts-transactions-table"
      />
    </BaseCard>
  </section>
</template>
