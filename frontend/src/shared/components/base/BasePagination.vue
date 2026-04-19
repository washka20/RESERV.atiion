<script setup lang="ts">
/**
 * Пагинация с ellipsis. Пример: `< 1 ... 5 6 7 ... 42 >`.
 */
import { computed } from 'vue'

interface Props {
  currentPage: number
  totalPages: number
  siblingCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  siblingCount: 1,
})

const emit = defineEmits<{
  'update:currentPage': [page: number]
}>()

type Item = number | 'ellipsis-left' | 'ellipsis-right'

const items = computed<Item[]>(() => {
  const { currentPage, totalPages, siblingCount } = props
  if (totalPages <= 1) return [1]

  const result: Item[] = []
  const first = 1
  const last = totalPages

  const leftBound = Math.max(currentPage - siblingCount, first + 1)
  const rightBound = Math.min(currentPage + siblingCount, last - 1)

  result.push(first)

  if (leftBound > first + 1) result.push('ellipsis-left')
  for (let i = leftBound; i <= rightBound; i += 1) {
    if (i > first && i < last) result.push(i)
  }
  if (rightBound < last - 1) result.push('ellipsis-right')

  if (last !== first) result.push(last)
  return result
})

const go = (page: number) => {
  if (page < 1 || page > props.totalPages || page === props.currentPage) return
  emit('update:currentPage', page)
}
</script>

<template>
  <nav
    aria-label="Pagination"
    class="inline-flex items-center gap-1"
    data-test-id="base-pagination"
  >
    <button
      type="button"
      class="h-9 w-9 inline-flex items-center justify-center rounded-sm border border-border text-text disabled:opacity-50 disabled:cursor-not-allowed hover:border-accent"
      :disabled="currentPage <= 1"
      aria-label="Предыдущая страница"
      data-test-id="base-pagination-prev"
      @click="go(currentPage - 1)"
    >
      &lsaquo;
    </button>
    <template v-for="(item, idx) in items" :key="`${item}-${idx}`">
      <span
        v-if="item === 'ellipsis-left' || item === 'ellipsis-right'"
        class="h-9 w-9 inline-flex items-center justify-center text-text-subtle"
        aria-hidden="true"
      >
        …
      </span>
      <button
        v-else
        type="button"
        class="h-9 min-w-9 px-2 inline-flex items-center justify-center rounded-sm border text-sm transition-colors"
        :class="
          item === currentPage
            ? 'border-accent bg-accent text-white'
            : 'border-border text-text hover:border-accent'
        "
        :aria-current="item === currentPage ? 'page' : undefined"
        :data-test-id="`base-pagination-page-${item}`"
        @click="go(item)"
      >
        {{ item }}
      </button>
    </template>
    <button
      type="button"
      class="h-9 w-9 inline-flex items-center justify-center rounded-sm border border-border text-text disabled:opacity-50 disabled:cursor-not-allowed hover:border-accent"
      :disabled="currentPage >= totalPages"
      aria-label="Следующая страница"
      data-test-id="base-pagination-next"
      @click="go(currentPage + 1)"
    >
      &rsaquo;
    </button>
  </nav>
</template>
