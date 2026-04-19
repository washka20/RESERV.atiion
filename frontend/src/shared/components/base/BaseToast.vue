<script setup lang="ts">
/**
 * Контейнер toast-ов. Teleport в body, fixed bottom-right, auto-dismiss через таймер в useToast.
 * Подключать в App.vue один раз: <BaseToast />.
 */
import { computed } from 'vue'
import { CheckCircle2, AlertTriangle, Info, XCircle, X } from 'lucide-vue-next'
import { useToastItems, remove, type ToastItem, type ToastVariant } from '../../composables/useToast'

const items = useToastItems()

const variantIcon = (variant: ToastVariant) => {
  const map = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
    warning: AlertTriangle,
  }
  return map[variant]
}

const variantClass = (variant: ToastVariant): string => {
  const map: Record<ToastVariant, string> = {
    success: 'bg-success/10 border-success text-text',
    error: 'bg-danger/10 border-danger text-text',
    info: 'bg-accent/10 border-accent text-text',
    warning: 'bg-warning/10 border-warning text-text',
  }
  return map[variant]
}

const iconColorClass = (variant: ToastVariant): string => {
  const map: Record<ToastVariant, string> = {
    success: 'text-success',
    error: 'text-danger',
    info: 'text-accent',
    warning: 'text-warning',
  }
  return map[variant]
}

const visibleItems = computed<ToastItem[]>(() => [...items])
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
      role="region"
      aria-label="Notifications"
      data-test-id="base-toast-region"
    >
      <TransitionGroup name="toast">
        <div
          v-for="item in visibleItems"
          :key="item.id"
          role="status"
          aria-live="polite"
          class="pointer-events-auto min-w-[240px] max-w-[360px] shadow-md rounded-md border px-3 py-2 flex items-start gap-2 bg-surface"
          :class="variantClass(item.variant)"
          :data-test-id="`base-toast-${item.variant}`"
        >
          <component
            :is="variantIcon(item.variant)"
            class="w-4 h-4 mt-0.5 shrink-0"
            :class="iconColorClass(item.variant)"
            aria-hidden="true"
          />
          <div
            class="flex-1 text-sm text-text"
            data-test-id="base-toast-message"
          >
            {{ item.message }}
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-sm text-text-subtle hover:bg-surface-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            aria-label="Закрыть уведомление"
            :data-test-id="`base-toast-close-${item.id}`"
            @click="remove(item.id)"
          >
            <X class="w-3 h-3" aria-hidden="true" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition:
    transform 0.2s ease,
    opacity 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}
</style>
