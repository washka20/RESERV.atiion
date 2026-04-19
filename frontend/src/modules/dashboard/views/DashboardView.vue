<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseTabs from '@/shared/components/base/BaseTabs.vue'
import { useBookingStore } from '@/stores/booking.store'
import BookingFilters from '../components/BookingFilters.vue'
import BookingsList from '../components/BookingsList.vue'

type TabId = 'bookings' | 'profile' | 'notifications' | 'favorites'

const booking = useBookingStore()
const route = useRoute()
const router = useRouter()

const TABS: { id: TabId; label: string }[] = [
  { id: 'bookings', label: 'Мои брони' },
  { id: 'profile', label: 'Профиль' },
  { id: 'notifications', label: 'Уведомления' },
  { id: 'favorites', label: 'Избранное' },
]

/** Активная вкладка — синхронизирована с `?tab=` в URL. */
function readTab(): TabId {
  const raw = (route.query.tab as string | undefined) ?? 'bookings'
  return TABS.some((t) => t.id === raw) ? (raw as TabId) : 'bookings'
}

const activeTab = ref<TabId>(readTab())

watch(
  () => route.query.tab,
  () => {
    const next = readTab()
    if (next !== activeTab.value) activeTab.value = next
  },
)

watch(activeTab, (next) => {
  if (route.query.tab === next) return
  void router.replace({ query: { ...route.query, tab: next } })
})

/** Значение 'all' — сбрасывает фильтр (не передаётся в API). */
const filterStatus = ref<string>('all')

async function load(): Promise<void> {
  const params = filterStatus.value === 'all' ? {} : { status: filterStatus.value }
  try {
    await booking.fetchUserBookings(params)
  } catch {
    /* ошибка уже в booking.error */
  }
}

onMounted(load)

watch(filterStatus, load)

async function handleCancel(id: string): Promise<void> {
  try {
    await booking.cancelBooking(id)
  } catch {
    /* ошибка уже в booking.error */
  }
}

const hasBookings = computed(() => booking.userBookings.length > 0)
</script>

<template>
  <div
    class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8"
    data-test-id="dashboard-page"
  >
    <header class="mb-6">
      <h1 class="text-2xl font-bold text-text">Кабинет</h1>
      <p class="mt-1 text-sm text-text-subtle">Управление бронированиями и профилем</p>
    </header>

    <BaseTabs v-model="activeTab" :tabs="TABS">
      <template #tab-bookings>
        <section class="space-y-4" :data-test-id="`dashboard-tab-bookings`">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-text">Мои бронирования</h2>
            <BookingFilters v-model="filterStatus" />
          </div>

          <div
            v-if="booking.error"
            class="rounded-md border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
            data-test-id="dashboard-error"
          >
            {{ booking.error }}
          </div>

          <div
            v-if="booking.isLoading"
            class="rounded-md border border-border bg-surface-muted p-8 text-center text-sm text-text-subtle"
            data-test-id="dashboard-loading"
          >
            Загрузка…
          </div>

          <div v-else-if="!hasBookings" data-test-id="dashboard-empty">
            <BaseEmptyState
              title="У вас пока нет бронирований"
              description="Перейдите в каталог, чтобы выбрать услугу"
            />
          </div>

          <BookingsList
            v-else
            :bookings="booking.userBookings"
            @cancel="handleCancel"
          />
        </section>
      </template>

      <template #tab-profile>
        <section :data-test-id="`dashboard-tab-profile`">
          <BaseEmptyState
            title="Профиль"
            description="Управление профилем будет доступно в Plan 14"
          />
        </section>
      </template>

      <template #tab-notifications>
        <section :data-test-id="`dashboard-tab-notifications`">
          <BaseEmptyState
            title="Уведомления"
            description="Уведомления появятся в Plan 14"
          />
        </section>
      </template>

      <template #tab-favorites>
        <section :data-test-id="`dashboard-tab-favorites`">
          <BaseEmptyState
            title="Избранное"
            description="Сохранённые услуги появятся в Plan 14"
          />
        </section>
      </template>
    </BaseTabs>
  </div>
</template>
