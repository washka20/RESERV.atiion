<script setup lang="ts">
/**
 * Вертикальный timeline: слева дата + цветной dot, справа title + description.
 * Между dots — линия. Variant управляет цветом dot (accent по умолчанию).
 */
type Variant = 'neutral' | 'success' | 'warning' | 'danger'

interface TimelineEvent {
  id: string | number
  date: string
  title: string
  description?: string
  variant?: Variant
}

interface Props {
  events: TimelineEvent[]
}

defineProps<Props>()

const dotClass = (variant?: Variant): string => {
  const map: Record<Variant, string> = {
    neutral: 'bg-accent border-accent',
    success: 'bg-success border-success',
    warning: 'bg-warning border-warning',
    danger: 'bg-danger border-danger',
  }
  return map[variant ?? 'neutral']
}
</script>

<template>
  <ol class="relative flex flex-col" data-test-id="base-timeline">
    <li
      v-for="(event, index) in events"
      :key="event.id"
      class="relative flex gap-3 pb-4 last:pb-0"
      :data-test-id="`base-timeline-item-${event.id}`"
    >
      <div class="relative flex flex-col items-center shrink-0 w-20">
        <div
          class="text-xs font-medium text-text-subtle mb-1 text-right w-full"
          data-test-id="base-timeline-date"
        >
          {{ event.date }}
        </div>
      </div>
      <div class="relative flex flex-col items-center shrink-0">
        <span
          class="relative z-10 block w-3 h-3 rounded-full border-2"
          :class="dotClass(event.variant)"
          :data-test-id="`base-timeline-dot-${event.variant ?? 'neutral'}`"
          aria-hidden="true"
        />
        <span
          v-if="index < events.length - 1"
          class="absolute top-3 left-1/2 -translate-x-1/2 w-px h-[calc(100%+0rem)] bg-border"
          aria-hidden="true"
        />
      </div>
      <div class="flex-1 min-w-0 -mt-0.5">
        <div
          class="text-sm font-medium text-text"
          data-test-id="base-timeline-title"
        >
          {{ event.title }}
        </div>
        <div
          v-if="event.description"
          class="text-xs text-text-subtle mt-0.5"
          data-test-id="base-timeline-description"
        >
          {{ event.description }}
        </div>
      </div>
    </li>
  </ol>
</template>
