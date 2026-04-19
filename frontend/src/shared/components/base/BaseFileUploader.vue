<script setup lang="ts">
/**
 * Drag-and-drop загрузчик с превью списка файлов.
 * При превышении maxSizeMb эмитит `error` и не добавляет файл.
 */
import { computed, ref } from 'vue'
import { Upload, X } from 'lucide-vue-next'

interface Props {
  modelValue?: File[]
  multiple?: boolean
  accept?: string
  maxSizeMb?: number
  disabled?: boolean
  label?: string
  hint?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  multiple: false,
  disabled: false,
  label: 'Перетащите файлы сюда или нажмите',
})

const emit = defineEmits<{
  'update:modelValue': [files: File[]]
  error: [message: string]
}>()

const inputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref<boolean>(false)

const openPicker = () => {
  if (props.disabled) return
  inputRef.value?.click()
}

const formatSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const validate = (file: File): boolean => {
  if (props.maxSizeMb !== undefined) {
    const limit = props.maxSizeMb * 1024 * 1024
    if (file.size > limit) {
      emit(
        'error',
        `Файл "${file.name}" превышает максимальный размер ${props.maxSizeMb} MB`,
      )
      return false
    }
  }
  return true
}

const addFiles = (incoming: FileList | File[]) => {
  const next: File[] = props.multiple ? [...props.modelValue] : []
  const arr = Array.from(incoming)
  for (const f of arr) {
    if (!validate(f)) continue
    next.push(f)
    if (!props.multiple) break
  }
  emit('update:modelValue', next)
}

const onInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) addFiles(target.files)
  target.value = ''
}

const onDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
  if (props.disabled) return
  const files = event.dataTransfer?.files
  if (files) addFiles(files)
}

const onDragOver = (event: DragEvent) => {
  event.preventDefault()
  if (props.disabled) return
  isDragging.value = true
}

const onDragLeave = () => {
  isDragging.value = false
}

const removeFile = (index: number) => {
  const next = [...props.modelValue]
  next.splice(index, 1)
  emit('update:modelValue', next)
}

const zoneClass = computed<string>(() => {
  const base =
    'w-full flex flex-col items-center justify-center gap-2 p-6 rounded-md border-2 border-dashed transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent'
  if (props.disabled) return `${base} bg-surface-muted border-border opacity-60 cursor-not-allowed`
  if (isDragging.value) return `${base} bg-accent/5 border-accent`
  return `${base} bg-surface border-border hover:bg-surface-muted cursor-pointer`
})
</script>

<template>
  <div class="w-full" data-test-id="base-file-uploader">
    <button
      type="button"
      :class="zoneClass"
      :disabled="disabled"
      :aria-label="label"
      data-test-id="base-file-uploader-zone"
      @click="openPicker"
      @drop="onDrop"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
    >
      <Upload class="w-6 h-6 text-text-subtle" aria-hidden="true" />
      <span class="text-sm font-medium text-text">{{ label }}</span>
      <span v-if="hint" class="text-xs text-text-subtle">{{ hint }}</span>
    </button>
    <input
      ref="inputRef"
      type="file"
      class="hidden"
      :multiple="multiple"
      :accept="accept"
      :disabled="disabled"
      data-test-id="base-file-uploader-input"
      @change="onInputChange"
    />
    <ul
      v-if="modelValue.length > 0"
      class="mt-3 flex flex-col gap-1"
      data-test-id="base-file-uploader-list"
    >
      <li
        v-for="(file, index) in modelValue"
        :key="`${file.name}-${index}`"
        class="flex items-center justify-between gap-2 px-3 py-2 bg-surface-muted rounded-sm text-sm"
      >
        <div class="flex-1 min-w-0">
          <div class="text-text truncate" data-test-id="base-file-uploader-item-name">
            {{ file.name }}
          </div>
          <div class="text-xs text-text-subtle">{{ formatSize(file.size) }}</div>
        </div>
        <button
          type="button"
          class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-sm text-text-subtle hover:bg-surface hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
          aria-label="Удалить файл"
          :data-test-id="`base-file-uploader-remove-${index}`"
          @click="removeFile(index)"
        >
          <X class="w-3.5 h-3.5" aria-hidden="true" />
        </button>
      </li>
    </ul>
  </div>
</template>
