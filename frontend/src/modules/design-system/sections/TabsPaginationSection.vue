<script setup lang="ts">
/**
 * Tabs + Pagination.
 */
import { ref } from 'vue'
import BaseTabs from '@/shared/components/base/BaseTabs.vue'
import BasePagination from '@/shared/components/base/BasePagination.vue'

const activeTab = ref('overview')
const tabs = [
  { id: 'overview', label: 'Обзор' },
  { id: 'schedule', label: 'Расписание' },
  { id: 'reviews', label: 'Отзывы' },
  { id: 'archived', label: 'Архив', disabled: true },
]

const page = ref(5)
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Tabs · Pagination</h2>
      <p class="text-sm text-text-subtle mt-1">
        ARIA-tabs с keyboard-навигацией (Arrow Left/Right) + пагинация с ellipsis.
      </p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <BaseTabs v-model="activeTab" :tabs="tabs">
        <template #tab-overview>
          <p class="text-sm text-text">
            Обзор услуги: базовые параметры, цена, доступные слоты на ближайшие 7 дней.
          </p>
        </template>
        <template #tab-schedule>
          <p class="text-sm text-text">
            Расписание мастера на неделю вперёд. Выбирай слот — и переходи к брони.
          </p>
        </template>
        <template #tab-reviews>
          <p class="text-sm text-text">
            Отзывы клиентов. Сортировка по дате, рейтингу, релевантности.
          </p>
        </template>
      </BaseTabs>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">
        Pagination (text-page={{ page }})
      </span>
      <BasePagination v-model:current-page="page" :total-pages="42" />
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseTabs v-model="activeTab" :tabs="tabs"&gt;
  &lt;template #tab-overview&gt;...&lt;/template&gt;
&lt;/BaseTabs&gt;
&lt;BasePagination v-model:current-page="page" :total-pages="42" /&gt;</code></pre>
  </div>
</template>
