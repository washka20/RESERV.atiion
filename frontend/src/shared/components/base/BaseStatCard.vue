<script setup lang="ts">
/**
 * Статистическая карточка: label сверху, value в центре, delta + trend справа,
 * hint мелким текстом снизу. Trend определяет цвет delta.
 */
import { computed } from 'vue'
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'

type Trend = 'up' | 'down' | 'flat'

interface Props {
  label: string
  value: string | number
  delta?: string
  trend?: Trend
  hint?: string
}

const props = withDefaults(defineProps<Props>(), {
  trend: 'flat',
})

const trendColorClass = computed<string>(() => {
  const map: Record<Trend, string> = {
    up: 'text-success',
    down: 'text-danger',
    flat: 'text-text-subtle',
  }
  return map[props.trend]
})

const trendIcon = computed(() => {
  const map: Record<Trend, typeof TrendingUp> = {
    up: TrendingUp,
    down: TrendingDown,
    flat: Minus,
  }
  return map[props.trend]
})
</script>

<template>
  <div
    class="bg-surface rounded-md border border-border p-4 shadow-sm flex flex-col gap-2"
    data-test-id="base-stat-card"
  >
    <div class="flex items-start justify-between gap-2">
      <span
        class="text-sm font-medium text-text-subtle"
        data-test-id="base-stat-card-label"
      >
        {{ label }}
      </span>
      <span v-if="$slots.icon" class="shrink-0 text-text-subtle">
        <slot name="icon" />
      </span>
    </div>
    <div class="flex items-end justify-between gap-3">
      <span
        class="text-2xl font-semibold text-text leading-none"
        data-test-id="base-stat-card-value"
      >
        {{ value }}
      </span>
      <span
        v-if="delta"
        class="inline-flex items-center gap-1 text-sm font-medium"
        :class="trendColorClass"
        data-test-id="base-stat-card-delta"
      >
        <component :is="trendIcon" class="w-4 h-4" aria-hidden="true" />
        {{ delta }}
      </span>
    </div>
    <span
      v-if="hint"
      class="text-xs text-text-subtle"
      data-test-id="base-stat-card-hint"
    >
      {{ hint }}
    </span>
  </div>
</template>
