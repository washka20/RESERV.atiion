<script setup lang="ts">
/**
 * Переключатель workspace (личный / организация) с dropdown-меню.
 * click-outside закрывает; при выборе эмитит update:modelValue.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Building2, User, ChevronDown, Check } from 'lucide-vue-next'

type WorkspaceType = 'personal' | 'organization'

interface Workspace {
  id: string
  name: string
  type: WorkspaceType
  avatar?: string
}

interface Props {
  workspaces: Workspace[]
  modelValue: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const isOpen = ref<boolean>(false)
const rootRef = ref<HTMLElement | null>(null)

const active = computed<Workspace | undefined>(() =>
  props.workspaces.find((w) => w.id === props.modelValue),
)

const toggle = () => {
  isOpen.value = !isOpen.value
}

const select = (w: Workspace) => {
  emit('update:modelValue', w.id)
  isOpen.value = false
}

const onOutside = (event: MouseEvent) => {
  if (!isOpen.value) return
  const target = event.target as Node
  if (rootRef.value && !rootRef.value.contains(target)) {
    isOpen.value = false
  }
}

const onKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onOutside, true)
  document.addEventListener('keydown', onKeyDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onOutside, true)
  document.removeEventListener('keydown', onKeyDown)
})

const iconFor = (type: WorkspaceType) =>
  type === 'organization' ? Building2 : User
</script>

<template>
  <div
    ref="rootRef"
    class="relative inline-block"
    data-test-id="base-workspace-switcher"
  >
    <button
      type="button"
      class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-border bg-surface hover:bg-surface-muted text-sm font-medium text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
      :aria-expanded="isOpen"
      aria-haspopup="listbox"
      data-test-id="base-workspace-switcher-trigger"
      @click="toggle"
    >
      <span v-if="active" class="inline-flex items-center gap-2 min-w-0">
        <img
          v-if="active.avatar"
          :src="active.avatar"
          :alt="active.name"
          class="w-5 h-5 rounded-sm object-cover"
        />
        <component
          :is="iconFor(active.type)"
          v-else
          class="w-4 h-4 text-text-subtle"
          aria-hidden="true"
        />
        <span class="truncate max-w-[160px]">{{ active.name }}</span>
      </span>
      <span v-else class="text-text-subtle">Выберите workspace</span>
      <ChevronDown class="w-4 h-4 text-text-subtle" aria-hidden="true" />
    </button>
    <ul
      v-if="isOpen"
      role="listbox"
      class="absolute z-30 mt-1 min-w-[220px] max-h-[280px] overflow-y-auto rounded-md border border-border bg-surface shadow-md py-1"
      data-test-id="base-workspace-switcher-menu"
    >
      <li
        v-for="w in workspaces"
        :key="w.id"
        role="option"
        :aria-selected="w.id === modelValue"
      >
        <button
          type="button"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-text hover:bg-surface-muted focus:outline-none focus-visible:bg-surface-muted"
          :data-test-id="`base-workspace-switcher-option-${w.id}`"
          @click="select(w)"
        >
          <img
            v-if="w.avatar"
            :src="w.avatar"
            :alt="w.name"
            class="w-5 h-5 rounded-sm object-cover shrink-0"
          />
          <component
            :is="iconFor(w.type)"
            v-else
            class="w-4 h-4 text-text-subtle shrink-0"
            aria-hidden="true"
          />
          <span class="flex-1 truncate text-left">{{ w.name }}</span>
          <Check
            v-if="w.id === modelValue"
            class="w-4 h-4 text-accent shrink-0"
            aria-hidden="true"
          />
        </button>
      </li>
    </ul>
  </div>
</template>
