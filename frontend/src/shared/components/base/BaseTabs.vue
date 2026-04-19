<script setup lang="ts">
/**
 * ARIA-tabs с клавиатурной навигацией (Arrow Left/Right).
 * Контент каждой табы рендерится через slot `tab-{id}`.
 */
import { computed, nextTick, ref } from 'vue'

interface Tab {
  id: string
  label: string
  disabled?: boolean
}

interface Props {
  tabs: Tab[]
  modelValue: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const tabRefs = ref<Record<string, HTMLButtonElement | null>>({})

const currentIndex = computed<number>(() =>
  props.tabs.findIndex((t) => t.id === props.modelValue),
)

const setTabRef = (id: string) => (el: Element | null) => {
  tabRefs.value[id] = el as HTMLButtonElement | null
}

const activate = (id: string) => {
  const tab = props.tabs.find((t) => t.id === id)
  if (!tab || tab.disabled) return
  emit('update:modelValue', id)
}

const focusTab = async (id: string) => {
  await nextTick()
  tabRefs.value[id]?.focus()
}

const onKeyDown = (event: KeyboardEvent) => {
  const { tabs } = props
  if (!tabs.length) return
  const idx = currentIndex.value
  const step = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0
  if (!step) return
  event.preventDefault()

  let next = idx
  for (let i = 0; i < tabs.length; i += 1) {
    next = (next + step + tabs.length) % tabs.length
    if (!tabs[next].disabled) break
  }
  const nextId = tabs[next].id
  activate(nextId)
  focusTab(nextId)
}
</script>

<template>
  <div data-test-id="base-tabs">
    <div
      role="tablist"
      class="flex gap-1 border-b border-border"
      @keydown="onKeyDown"
    >
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :ref="setTabRef(tab.id)"
        type="button"
        role="tab"
        :id="`tab-${tab.id}`"
        :aria-selected="modelValue === tab.id"
        :aria-controls="`panel-${tab.id}`"
        :tabindex="modelValue === tab.id ? 0 : -1"
        :disabled="tab.disabled"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
        :class="
          modelValue === tab.id
            ? 'border-accent text-accent'
            : 'border-transparent text-text-subtle hover:text-text'
        "
        :data-test-id="`base-tab-${tab.id}`"
        @click="activate(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>
    <div
      v-for="tab in tabs"
      :key="tab.id"
      :id="`panel-${tab.id}`"
      role="tabpanel"
      :aria-labelledby="`tab-${tab.id}`"
      :hidden="modelValue !== tab.id"
      class="pt-4"
    >
      <slot :name="`tab-${tab.id}`" />
    </div>
  </div>
</template>
