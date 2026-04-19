<script setup lang="ts">
/**
 * OrgCalendarView — календарь бронирований organization.
 *
 * MVP: только представление "Месяц" через BaseCalendar. При выборе дня —
 * BaseEmptyState (бронирования пока не доступны через API).
 * Вкладки "Неделя" / "День" задизейблены до Plan 15.
 */
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseCalendar from '@/shared/components/base/BaseCalendar.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseTabs from '@/shared/components/base/BaseTabs.vue'

const { t } = useI18n()
const route = useRoute()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const activeTab = ref<'month' | 'week' | 'day'>('month')
const selectedDate = ref<Date>(new Date())

const tabs = computed(() => [
  { id: 'month', label: t('provider.calendar.tabMonth') },
  { id: 'week', label: t('provider.calendar.tabWeek'), disabled: true },
  { id: 'day', label: t('provider.calendar.tabDay'), disabled: true },
])

const selectedLabel = computed<string>(() => {
  const fmt = new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
  return fmt.format(selectedDate.value)
})
</script>

<template>
  <section
    data-test-id="org-calendar-view"
    class="flex flex-col gap-4"
    :data-org-slug="orgSlug"
  >
    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-calendar-title"
    >
      {{ t('provider.calendar.title') }}
    </h1>

    <BaseTabs
      v-model="activeTab"
      :tabs="tabs"
      data-test-id="org-calendar-tabs"
    >
      <template #tab-month>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-[auto_1fr]">
          <BaseCalendar
            v-model="selectedDate"
            data-test-id="org-calendar-month"
          />
          <BaseCard padding="md" class="min-h-[240px]">
            <h2
              class="text-base font-semibold text-text mb-2"
              data-test-id="org-calendar-day-label"
            >
              {{ selectedLabel }}
            </h2>
            <BaseEmptyState
              :title="t('provider.calendar.emptyTitle')"
              :description="t('provider.calendar.emptyDesc')"
              data-test-id="org-calendar-day-empty"
            />
          </BaseCard>
        </div>
      </template>
      <template #tab-week>
        <BaseEmptyState title="Coming soon" />
      </template>
      <template #tab-day>
        <BaseEmptyState title="Coming soon" />
      </template>
    </BaseTabs>
  </section>
</template>
