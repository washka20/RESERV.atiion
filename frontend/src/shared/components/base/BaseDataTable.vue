<script setup lang="ts">
/**
 * Таблица с sortable-заголовками, кастомным render, row-actions slot и empty-state.
 * Sort — неконтролируемый? Нет, контролируемый: принимает sortKey/sortDir, эмитит sort.
 */
import { computed } from 'vue'
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-vue-next'

type Align = 'left' | 'right' | 'center'
type SortDir = 'asc' | 'desc'

interface Column {
  key: string
  label: string
  align?: Align
  sortable?: boolean
  render?: (row: Record<string, unknown>) => string
}

interface Props {
  columns: Column[]
  rows: Record<string, unknown>[]
  sortKey?: string
  sortDir?: SortDir
  emptyMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'Нет данных',
})

const emit = defineEmits<{
  sort: [key: string, dir: SortDir]
  'row-click': [row: Record<string, unknown>]
}>()

const isEmpty = computed<boolean>(() => props.rows.length === 0)

const alignClass = (align?: Align): string => {
  if (align === 'right') return 'text-right'
  if (align === 'center') return 'text-center'
  return 'text-left'
}

const onHeaderClick = (col: Column) => {
  if (!col.sortable) return
  const nextDir: SortDir =
    props.sortKey === col.key && props.sortDir === 'asc' ? 'desc' : 'asc'
  emit('sort', col.key, nextDir)
}

const onRowClick = (row: Record<string, unknown>) => {
  emit('row-click', row)
}

const cellContent = (col: Column, row: Record<string, unknown>): string => {
  if (col.render) return col.render(row)
  const v = row[col.key]
  return v === null || v === undefined ? '' : String(v)
}
</script>

<template>
  <div
    class="w-full overflow-x-auto rounded-md border border-border bg-surface"
    data-test-id="base-data-table"
  >
    <table class="w-full text-sm">
      <thead class="bg-surface-muted">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            scope="col"
            class="px-3 py-2 font-medium text-text-subtle text-xs uppercase tracking-wide border-b border-border"
            :class="alignClass(col.align)"
            :aria-sort="
              col.sortable && sortKey === col.key
                ? sortDir === 'asc'
                  ? 'ascending'
                  : 'descending'
                : undefined
            "
          >
            <button
              v-if="col.sortable"
              type="button"
              class="inline-flex items-center gap-1 hover:text-text focus:outline-none focus-visible:text-text"
              :data-test-id="`base-data-table-sort-${col.key}`"
              @click="onHeaderClick(col)"
            >
              {{ col.label }}
              <ChevronUp
                v-if="sortKey === col.key && sortDir === 'asc'"
                class="w-3 h-3"
                aria-hidden="true"
              />
              <ChevronDown
                v-else-if="sortKey === col.key && sortDir === 'desc'"
                class="w-3 h-3"
                aria-hidden="true"
              />
              <ChevronsUpDown v-else class="w-3 h-3 opacity-50" aria-hidden="true" />
            </button>
            <span v-else>{{ col.label }}</span>
          </th>
          <th
            v-if="$slots['row-actions']"
            scope="col"
            class="px-3 py-2 border-b border-border w-1 text-right"
          >
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isEmpty">
          <td
            :colspan="columns.length + ($slots['row-actions'] ? 1 : 0)"
            class="px-3 py-10 text-center text-text-subtle"
            data-test-id="base-data-table-empty"
          >
            {{ emptyMessage }}
          </td>
        </tr>
        <tr
          v-for="(row, idx) in rows"
          v-else
          :key="idx"
          class="border-t border-border hover:bg-surface-muted/50 cursor-pointer"
          :data-test-id="`base-data-table-row-${idx}`"
          @click="onRowClick(row)"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            class="px-3 py-2 text-text"
            :class="alignClass(col.align)"
          >
            {{ cellContent(col, row) }}
          </td>
          <td
            v-if="$slots['row-actions']"
            class="px-3 py-2 text-right"
            @click.stop
          >
            <slot name="row-actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
