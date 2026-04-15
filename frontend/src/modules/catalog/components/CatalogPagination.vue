<script setup lang="ts">
import { computed } from 'vue'
import type { PaginationMeta } from '@/types/catalog.types'

const props = defineProps<{ meta: PaginationMeta }>()
const emit = defineEmits<{ change: [page: number] }>()

const isFirst = computed(() => props.meta.page <= 1)
const isLast = computed(() => props.meta.page >= props.meta.lastPage)

function goPrev(): void {
  if (!isFirst.value) emit('change', props.meta.page - 1)
}

function goNext(): void {
  if (!isLast.value) emit('change', props.meta.page + 1)
}
</script>

<template>
  <nav
    class="flex items-center justify-between gap-4 border-t border-gray-200 pt-4"
    aria-label="Pagination"
    data-test-id="catalog-pagination"
  >
    <button
      type="button"
      class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="isFirst"
      data-test-id="catalog-pagination-prev"
      @click="goPrev"
    >
      Назад
    </button>

    <span class="text-sm text-gray-600" data-test-id="catalog-pagination-info">
      Стр. {{ meta.page }} из {{ meta.lastPage }}
    </span>

    <button
      type="button"
      class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="isLast"
      data-test-id="catalog-pagination-next"
      @click="goNext"
    >
      Вперёд
    </button>
  </nav>
</template>
