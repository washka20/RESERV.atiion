<script setup lang="ts">
/**
 * Модалка с teleport в <body>, backdrop, Escape-close и focus trap.
 * Slots: header, default (body), footer.
 */
import { computed, ref, watch } from 'vue'
import { useFocusTrap } from '../../composables/useFocusTrap'

type Size = 'sm' | 'md' | 'lg'

interface Props {
  modelValue: boolean
  title?: string
  size?: Size
  dismissible?: boolean
  closeLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  dismissible: true,
  closeLabel: 'Закрыть',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  close: []
}>()

const dialogRef = ref<HTMLElement | null>(null)
const isOpen = computed<boolean>(() => props.modelValue)

useFocusTrap(dialogRef, isOpen)

const sizeClass = computed<string>(() => {
  const map: Record<Size, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-2xl',
  }
  return map[props.size]
})

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

const onBackdropClick = () => {
  if (props.dismissible) close()
}

const onKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.dismissible) {
    event.stopPropagation()
    close()
  }
}

watch(isOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50"
        data-test-id="base-modal-backdrop"
        @click.self="onBackdropClick"
        @keydown="onKeyDown"
      >
        <div
          ref="dialogRef"
          role="dialog"
          aria-modal="true"
          :aria-label="title"
          tabindex="-1"
          class="relative w-full rounded-md bg-surface shadow-lg border border-border focus:outline-none"
          :class="sizeClass"
          data-test-id="base-modal"
          @keydown="onKeyDown"
        >
          <header
            v-if="$slots.header || title"
            class="flex items-start justify-between gap-4 px-5 py-4 border-b border-border"
          >
            <div class="flex-1 min-w-0">
              <slot name="header">
                <h2 class="text-lg font-semibold text-text truncate">{{ title }}</h2>
              </slot>
            </div>
            <button
              v-if="dismissible"
              type="button"
              class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-sm text-text-subtle hover:bg-surface-muted"
              :aria-label="closeLabel"
              data-test-id="base-modal-close"
              @click="close"
            >
              <svg
                viewBox="0 0 16 16"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                aria-hidden="true"
              >
                <path d="M4 4 L12 12 M12 4 L4 12" />
              </svg>
            </button>
          </header>
          <div class="px-5 py-4">
            <slot />
          </div>
          <footer
            v-if="$slots.footer"
            class="px-5 py-4 border-t border-border flex items-center justify-end gap-2"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
