<script setup lang="ts">
import BasePagination from '@/shared/components/base/BasePagination.vue'
import type { PaginationMeta } from '@/types/catalog.types'

const props = defineProps<{ meta: PaginationMeta }>()
const emit = defineEmits<{ change: [page: number] }>()

function onUpdate(page: number): void {
  emit('change', page)
}
</script>

<template>
  <nav
    class="flex items-center justify-between gap-4 border-t border-border pt-4"
    aria-label="Pagination"
    data-test-id="catalog-pagination"
  >
    <span class="text-sm text-text-subtle" data-test-id="catalog-pagination-info">
      Стр. {{ props.meta.page }} из {{ props.meta.lastPage }}
    </span>
    <BasePagination
      :current-page="props.meta.page"
      :total-pages="props.meta.lastPage"
      @update:current-page="onUpdate"
    />
  </nav>
</template>
