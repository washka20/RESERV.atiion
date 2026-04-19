<script setup lang="ts">
/**
 * Cards + Badges + Chips.
 */
import { ref } from 'vue'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import BaseChip from '@/shared/components/base/BaseChip.vue'

const selectedFilters = ref<string[]>(['spa'])

const toggleFilter = (id: string) => {
  const idx = selectedFilters.value.indexOf(id)
  if (idx === -1) selectedFilters.value.push(id)
  else selectedFilters.value.splice(idx, 1)
}

const tags = ref([
  { id: '1', label: 'Москва' },
  { id: '2', label: 'До 3 000 ₽' },
  { id: '3', label: '4.5+' },
])

const removeTag = (id: string) => {
  tags.value = tags.value.filter((t) => t.id !== id)
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Cards · Badges · Chips</h2>
      <p class="text-sm text-text-subtle mt-1">Контейнеры и inline-статусы.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <BaseCard padding="sm" elevation="none">
        <h3 class="font-semibold text-text">Padding sm · elevation none</h3>
        <p class="text-sm text-text-subtle mt-1">Минимальная карточка.</p>
      </BaseCard>
      <BaseCard padding="md" elevation="sm">
        <h3 class="font-semibold text-text">Padding md · shadow sm</h3>
        <p class="text-sm text-text-subtle mt-1">Дефолт для списков.</p>
      </BaseCard>
      <BaseCard padding="lg" elevation="md">
        <h3 class="font-semibold text-text">Padding lg · shadow md</h3>
        <p class="text-sm text-text-subtle mt-1">Акцентный блок на лендинге.</p>
      </BaseCard>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Badges</span>
      <div class="flex flex-wrap gap-2">
        <BaseBadge variant="neutral">Neutral</BaseBadge>
        <BaseBadge variant="success">Confirmed</BaseBadge>
        <BaseBadge variant="warning">Pending</BaseBadge>
        <BaseBadge variant="danger">Cancelled</BaseBadge>
        <BaseBadge variant="info">New</BaseBadge>
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Chips</span>
      <div class="flex flex-wrap gap-2">
        <BaseChip
          label="Спа"
          clickable
          :selected="selectedFilters.includes('spa')"
          @click="toggleFilter('spa')"
        />
        <BaseChip
          label="Массаж"
          clickable
          :selected="selectedFilters.includes('massage')"
          @click="toggleFilter('massage')"
        />
        <BaseChip
          label="Йога"
          clickable
          :selected="selectedFilters.includes('yoga')"
          @click="toggleFilter('yoga')"
        />
        <BaseChip label="Disabled" clickable disabled />
      </div>

      <div class="flex flex-wrap gap-2">
        <BaseChip
          v-for="tag in tags"
          :key="tag.id"
          :label="tag.label"
          removable
          @remove="removeTag(tag.id)"
        />
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseCard padding="md" elevation="sm"&gt;...&lt;/BaseCard&gt;
&lt;BaseBadge variant="success"&gt;Confirmed&lt;/BaseBadge&gt;
&lt;BaseChip label="Moscow" clickable :selected="true" /&gt;</code></pre>
  </div>
</template>
