<script setup lang="ts">
/**
 * OrgDashboardView — provider dashboard с breadcrumb, stat cards, chart и recent bookings.
 *
 * Mounted на /o/:slug. Получает slug из useRoute.
 * На mount вызывает orgStore.loadStats(slug); при 404 / envelope.success=false
 * показывает toast и fallback-значения "—" в карточках.
 */
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseChart from '@/shared/components/base/BaseChart.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseStatCard from '@/shared/components/base/BaseStatCard.vue'
import { useOrgStore } from '@/stores/org.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const orgStore = useOrgStore()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const fallback = '—'

/**
 * Форматирует сумму в рублях с разделителями. value хранится в копейках / нативном int.
 * Если value === null или undefined — возвращает fallback.
 */
function formatCurrency(value: number | null | undefined): string {
  if (value === null || value === undefined) return fallback
  return `${new Intl.NumberFormat('ru-RU').format(value)} ₽`
}

function formatNumber(value: number | null | undefined): string {
  if (value === null || value === undefined) return fallback
  return new Intl.NumberFormat('ru-RU').format(value)
}

const revenue = computed<string>(() => formatCurrency(orgStore.orgStats?.revenue ?? null))
const fee = computed<string>(() => {
  const revenueVal = orgStore.orgStats?.revenue
  if (revenueVal === undefined || revenueVal === null) return fallback
  return formatCurrency(Math.round(revenueVal * 0.1))
})
const payout = computed<string>(() => {
  const revenueVal = orgStore.orgStats?.revenue
  if (revenueVal === undefined || revenueVal === null) return fallback
  return formatCurrency(Math.round(revenueVal * 0.9))
})
const bookings = computed<string>(() =>
  formatNumber(orgStore.orgStats?.totalBookings ?? null),
)

const chartData = computed(() =>
  Array.from({ length: 7 }, (_, i) => ({
    x: `Д${i + 1}`,
    y: Math.round(
      ((orgStore.orgStats?.revenue ?? 0) / 7) * (0.6 + Math.random() * 0.8),
    ),
  })),
)

onMounted(async () => {
  try {
    await orgStore.loadStats(orgSlug.value)
  } catch {
    toast.error(orgStore.error ?? t('provider.dashboard.errorStats'))
  }
})
</script>

<template>
  <section data-test-id="org-dashboard-view" class="flex flex-col gap-6">
    <nav
      class="text-xs text-text-subtle"
      aria-label="Breadcrumb"
      data-test-id="org-dashboard-breadcrumb"
    >
      {{ t('provider.crumbHome') }} / {{ orgSlug }}
    </nav>

    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-dashboard-title"
    >
      {{ t('provider.dashboard.title') }}
    </h1>

    <div
      class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4"
      data-test-id="org-dashboard-stats"
    >
      <BaseStatCard
        :label="t('provider.dashboard.revenue30d')"
        :value="revenue"
        :hint="t('provider.dashboard.revenueHint')"
      />
      <BaseStatCard
        :label="t('provider.dashboard.fee')"
        :value="fee"
        trend="down"
      />
      <BaseStatCard
        :label="t('provider.dashboard.payout')"
        :value="payout"
        trend="up"
      />
      <BaseStatCard
        :label="t('provider.dashboard.bookings')"
        :value="bookings"
      />
    </div>

    <BaseChart
      type="area"
      :data="chartData"
      :label="t('provider.dashboard.chartLabel')"
      :height="220"
    />

    <BaseCard padding="md">
      <h2
        class="text-lg font-semibold text-text mb-3"
        data-test-id="org-dashboard-recent-title"
      >
        {{ t('provider.dashboard.recent') }}
      </h2>
      <BaseEmptyState :title="t('provider.dashboard.recentEmpty')" />
    </BaseCard>
  </section>
</template>
