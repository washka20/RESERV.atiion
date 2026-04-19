<script setup lang="ts">
/**
 * OrgInboxView — входящие бронирования organization.
 *
 * Filter chips переключают фильтр статуса. Для каждой pending-брони —
 * карточка с action-ми Подтвердить / Отклонить.
 *
 * Backend endpoint пока stub → в любой ветке ошибки показываем
 * BaseEmptyState + toast с пояснением.
 */
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Inbox as InboxIcon } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseChip from '@/shared/components/base/BaseChip.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseDialog from '@/shared/components/base/BaseDialog.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import * as orgBookingsApi from '@/api/org-bookings.api'
import type { Booking, BookingStatus } from '@/types/booking.types'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

type FilterValue = 'all' | BookingStatus

const filter = ref<FilterValue>('pending')
const bookings = ref<Booking[]>([])
const isLoading = ref<boolean>(false)
const declineDialogOpen = ref<boolean>(false)
const pendingDecline = ref<string | null>(null)

const chips = computed(() => [
  { id: 'all', label: t('provider.inbox.filterAll') },
  { id: 'pending', label: t('provider.inbox.filterPending') },
  { id: 'confirmed', label: t('provider.inbox.filterConfirmed') },
  { id: 'cancelled', label: t('provider.inbox.filterCancelled') },
])

async function loadInbox(): Promise<void> {
  isLoading.value = true
  try {
    const params =
      filter.value === 'all' ? {} : { status: filter.value as BookingStatus }
    const envelope = await orgBookingsApi.listInbox(orgSlug.value, params)
    bookings.value = envelope.success && envelope.data ? envelope.data : []
  } catch {
    bookings.value = []
  } finally {
    isLoading.value = false
  }
}

function formatPrice(b: Booking): string {
  return `${new Intl.NumberFormat('ru-RU').format(b.totalPriceAmount / 100)} ${b.totalPriceCurrency}`
}

function formatSchedule(b: Booking): string {
  if (b.startAt && b.endAt) {
    const start = new Date(b.startAt)
    const end = new Date(b.endAt)
    const fmtDate = new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'long',
    })
    const fmtTime = new Intl.DateTimeFormat('ru-RU', {
      hour: '2-digit',
      minute: '2-digit',
    })
    return `${fmtDate.format(start)} · ${fmtTime.format(start)}—${fmtTime.format(end)}`
  }
  if (b.checkIn && b.checkOut) return `${b.checkIn} — ${b.checkOut}`
  return ''
}

async function onConfirm(bookingId: string): Promise<void> {
  try {
    await orgBookingsApi.confirm(bookingId)
    toast.success(t('provider.inbox.successConfirm'))
    await loadInbox()
  } catch {
    toast.error(t('provider.inbox.stubError'))
  }
}

function askDecline(bookingId: string): void {
  pendingDecline.value = bookingId
  declineDialogOpen.value = true
}

async function onDeclineConfirm(): Promise<void> {
  if (!pendingDecline.value) return
  try {
    await orgBookingsApi.decline(pendingDecline.value)
    toast.success(t('provider.inbox.successDecline'))
    await loadInbox()
  } catch {
    toast.error(t('provider.inbox.stubError'))
  } finally {
    declineDialogOpen.value = false
    pendingDecline.value = null
  }
}

watch(filter, () => {
  void loadInbox()
})

onMounted(() => {
  void loadInbox()
})
</script>

<template>
  <section data-test-id="org-inbox-view" class="flex flex-col gap-4">
    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-inbox-title"
    >
      {{ t('provider.inbox.title') }}
    </h1>

    <div class="flex flex-wrap items-center gap-2" data-test-id="org-inbox-filters">
      <BaseChip
        v-for="chip in chips"
        :key="chip.id"
        clickable
        :selected="filter === chip.id"
        :data-test-id="`org-inbox-filter-chip-${chip.id}`"
        @click="filter = chip.id as FilterValue"
      >
        {{ chip.label }}
      </BaseChip>
    </div>

    <div v-if="bookings.length === 0" data-test-id="org-inbox-empty">
      <BaseEmptyState
        :title="t('provider.inbox.emptyTitle')"
        :description="t('provider.inbox.emptyDesc')"
      >
        <template #icon>
          <InboxIcon class="h-10 w-10" aria-hidden="true" />
        </template>
      </BaseEmptyState>
    </div>

    <ul v-else class="flex flex-col gap-3" data-test-id="org-inbox-list">
      <li
        v-for="booking in bookings"
        :key="booking.id"
        :data-test-id="`org-inbox-item-${booking.id}`"
      >
        <BaseCard padding="md">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-semibold text-text">
                Booking {{ booking.id.slice(0, 8) }}
              </div>
              <div class="text-xs text-text-subtle mt-1">
                {{ formatSchedule(booking) }}
              </div>
              <div class="text-xs text-text-subtle mt-1">
                {{ formatPrice(booking) }}
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <BaseButton
                variant="primary"
                size="sm"
                :test-id="`org-inbox-confirm-btn-${booking.id}`"
                @click="onConfirm(booking.id)"
              >
                {{ t('provider.inbox.confirm') }}
              </BaseButton>
              <BaseButton
                variant="danger"
                size="sm"
                :test-id="`org-inbox-decline-btn-${booking.id}`"
                @click="askDecline(booking.id)"
              >
                {{ t('provider.inbox.decline') }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>
      </li>
    </ul>

    <BaseDialog
      v-model="declineDialogOpen"
      :title="t('provider.inbox.declineConfirmTitle')"
      :message="t('provider.inbox.declineConfirmMessage')"
      variant="danger"
      :confirm-label="t('provider.inbox.decline')"
      @confirm="onDeclineConfirm"
    />
  </section>
</template>
