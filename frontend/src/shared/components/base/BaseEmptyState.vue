<script setup lang="ts">
/**
 * Empty state — отображение при пустом списке / отсутствии данных.
 * Slots: icon (опциональная иконка сверху), action (CTA-кнопки снизу).
 */
interface Props {
  title: string
  description?: string
}

defineProps<Props>()
</script>

<template>
  <div
    class="flex flex-col items-center justify-center text-center py-10 px-4"
    data-test-id="base-empty-state"
  >
    <div v-if="$slots.icon" class="mb-4 text-text-subtle">
      <slot name="icon" />
    </div>
    <h3 class="text-lg font-semibold text-text">{{ title }}</h3>
    <p
      v-if="description"
      class="mt-1 text-sm text-text-subtle max-w-sm"
    >
      {{ description }}
    </p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
