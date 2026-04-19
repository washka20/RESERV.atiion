<script setup lang="ts">
/**
 * Data display: StatCard, DataTable, Calendar, Chart, Timeline.
 */
import { ref } from 'vue'
import { Users, Calendar as CalendarIcon, CreditCard } from 'lucide-vue-next'
import BaseStatCard from '@/shared/components/base/BaseStatCard.vue'
import BaseDataTable from '@/shared/components/base/BaseDataTable.vue'
import BaseCalendar from '@/shared/components/base/BaseCalendar.vue'
import BaseChart from '@/shared/components/base/BaseChart.vue'
import BaseTimeline from '@/shared/components/base/BaseTimeline.vue'

type SortDir = 'asc' | 'desc'

interface SalonRow extends Record<string, unknown> {
  id: number
  name: string
  city: string
  revenue: number
  rating: number
}

const rows: SalonRow[] = [
  { id: 1, name: 'Салон Саввина', city: 'Москва', revenue: 1200000, rating: 4.8 },
  { id: 2, name: 'Loft 23', city: 'СПб', revenue: 850000, rating: 4.6 },
  { id: 3, name: 'Beauty Club', city: 'Казань', revenue: 620000, rating: 4.5 },
  { id: 4, name: 'Yoga Space', city: 'Москва', revenue: 410000, rating: 4.9 },
  { id: 5, name: 'Sauna Relax', city: 'Сочи', revenue: 980000, rating: 4.3 },
]

const columns = [
  { key: 'name', label: 'Название', sortable: true },
  { key: 'city', label: 'Город', sortable: true },
  {
    key: 'revenue',
    label: 'Выручка',
    align: 'right' as const,
    sortable: true,
    render: (r: Record<string, unknown>) =>
      `${((r.revenue as number) / 1000).toLocaleString('ru-RU')} тыс ₽`,
  },
  { key: 'rating', label: 'Рейтинг', align: 'right' as const, sortable: true },
]

const sortKey = ref<string>('revenue')
const sortDir = ref<SortDir>('desc')

const sortedRows = ref<SalonRow[]>([...rows])
const applySort = (key: string, dir: SortDir) => {
  sortKey.value = key
  sortDir.value = dir
  sortedRows.value = [...rows].sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (typeof va === 'number' && typeof vb === 'number') {
      return dir === 'asc' ? va - vb : vb - va
    }
    return dir === 'asc'
      ? String(va).localeCompare(String(vb))
      : String(vb).localeCompare(String(va))
  })
}
applySort(sortKey.value, sortDir.value)

const onSort = (key: string, dir: SortDir) => {
  applySort(key, dir)
}

const selectedDate = ref<Date | null>(new Date())

const chartData = [
  { x: 'Пн', y: 12 },
  { x: 'Вт', y: 18 },
  { x: 'Ср', y: 14 },
  { x: 'Чт', y: 22 },
  { x: 'Пт', y: 28 },
  { x: 'Сб', y: 35 },
  { x: 'Вс', y: 30 },
]

const timelineEvents = [
  { id: 1, date: '12 апр', title: 'Бронирование создано', description: 'Клиент — Анна С.', variant: 'neutral' as const },
  { id: 2, date: '12 апр', title: 'Оплата прошла', description: '3 500 ₽ · Yandex Pay', variant: 'success' as const },
  { id: 3, date: '13 апр', title: 'Напоминание отправлено', variant: 'warning' as const },
  { id: 4, date: '14 апр', title: 'Визит завершён', variant: 'success' as const },
]
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Data display</h2>
      <p class="text-sm text-text-subtle mt-1">
        StatCard · DataTable · Calendar · Chart · Timeline.
      </p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <BaseStatCard label="Брони за неделю" :value="142" delta="+12%" trend="up" hint="vs прошлой неделе">
        <template #icon>
          <CalendarIcon class="w-4 h-4" aria-hidden="true" />
        </template>
      </BaseStatCard>
      <BaseStatCard label="Выручка" value="1.2 млн ₽" delta="-4%" trend="down" hint="vs прошлой неделе">
        <template #icon>
          <CreditCard class="w-4 h-4" aria-hidden="true" />
        </template>
      </BaseStatCard>
      <BaseStatCard label="Новые клиенты" :value="38" delta="0%" trend="flat">
        <template #icon>
          <Users class="w-4 h-4" aria-hidden="true" />
        </template>
      </BaseStatCard>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">DataTable</span>
      <BaseDataTable
        :columns="columns"
        :rows="sortedRows"
        :sort-key="sortKey"
        :sort-dir="sortDir"
        @sort="onSort"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
        <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Calendar</span>
        <BaseCalendar v-model="selectedDate" />
      </div>
      <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Chart · bar</span>
          <BaseChart type="bar" :data="chartData" label="Брони по дням" :height="180" />
        </div>
        <div class="flex flex-col gap-2">
          <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Chart · line</span>
          <BaseChart type="line" :data="chartData" label="Динамика" :height="180" />
        </div>
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Timeline</span>
      <BaseTimeline :events="timelineEvents" />
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseStatCard label="Брони" :value="142" trend="up" delta="+12%" /&gt;
&lt;BaseDataTable :columns="columns" :rows="rows" @sort="onSort" /&gt;
&lt;BaseCalendar v-model="date" /&gt;
&lt;BaseChart type="bar" :data="data" /&gt;
&lt;BaseTimeline :events="events" /&gt;</code></pre>
  </div>
</template>
