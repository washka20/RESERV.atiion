<script setup lang="ts">
/**
 * Календарь с выбором одной даты. Month grid 6x7, клавиатурная навигация.
 * Keys: Arrow Left/Right/Up/Down — день; Home/End — первый/последний день месяца;
 * PageUp/PageDown — смена месяца; Enter/Space — select.
 */
import { computed, nextTick, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface Props {
  modelValue?: Date | null
  min?: Date
  max?: Date
  disabledDates?: Date[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: Date]
}>()

const WEEKDAY_LABELS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
const MONTH_LABELS = [
  'Январь',
  'Февраль',
  'Март',
  'Апрель',
  'Май',
  'Июнь',
  'Июль',
  'Август',
  'Сентябрь',
  'Октябрь',
  'Ноябрь',
  'Декабрь',
]

const initial = props.modelValue ?? new Date()
const viewYear = ref<number>(initial.getFullYear())
const viewMonth = ref<number>(initial.getMonth())
const focusedDay = ref<number>(initial.getDate())
const gridRef = ref<HTMLElement | null>(null)

const today = new Date()
const isSameDay = (a: Date, b: Date): boolean =>
  a.getFullYear() === b.getFullYear() &&
  a.getMonth() === b.getMonth() &&
  a.getDate() === b.getDate()

const daysInMonth = (y: number, m: number): number => new Date(y, m + 1, 0).getDate()

/** Возвращает смещение первого дня месяца (0=Пн ... 6=Вс). */
const firstWeekdayOffset = (y: number, m: number): number => {
  const jsDay = new Date(y, m, 1).getDay()
  return (jsDay + 6) % 7
}

interface Cell {
  day: number
  date: Date
  inMonth: boolean
  isToday: boolean
  isSelected: boolean
  isDisabled: boolean
  isWeekend: boolean
}

const cells = computed<Cell[]>(() => {
  const y = viewYear.value
  const m = viewMonth.value
  const offset = firstWeekdayOffset(y, m)
  const total = daysInMonth(y, m)
  const prevTotal = daysInMonth(y, m - 1)
  const list: Cell[] = []

  for (let i = 0; i < 42; i += 1) {
    let day: number
    let inMonth = true
    const year = y
    let month = m
    if (i < offset) {
      day = prevTotal - offset + 1 + i
      month = m - 1
      inMonth = false
    } else if (i >= offset + total) {
      day = i - offset - total + 1
      month = m + 1
      inMonth = false
    } else {
      day = i - offset + 1
    }
    const date = new Date(year, month, day)
    const weekdayIdx = i % 7
    list.push({
      day,
      date,
      inMonth,
      isToday: isSameDay(date, today),
      isSelected: props.modelValue ? isSameDay(date, props.modelValue) : false,
      isDisabled: isDateDisabled(date),
      isWeekend: weekdayIdx === 5 || weekdayIdx === 6,
    })
  }
  return list
})

function isDateDisabled(date: Date): boolean {
  if (props.min && date < stripTime(props.min)) return true
  if (props.max && date > stripTime(props.max)) return true
  if (props.disabledDates) {
    return props.disabledDates.some((d) => isSameDay(d, date))
  }
  return false
}

function stripTime(d: Date): Date {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate())
}

const prevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
  } else {
    viewMonth.value -= 1
  }
}

const nextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
  } else {
    viewMonth.value += 1
  }
}

const selectDate = (date: Date) => {
  if (isDateDisabled(date)) return
  viewYear.value = date.getFullYear()
  viewMonth.value = date.getMonth()
  focusedDay.value = date.getDate()
  emit('update:modelValue', date)
}

const focusCell = async (day: number) => {
  focusedDay.value = day
  await nextTick()
  const btn = gridRef.value?.querySelector<HTMLButtonElement>(
    `[data-test-id="base-calendar-day-${viewYear.value}-${viewMonth.value}-${day}"]`,
  )
  btn?.focus()
}

const moveFocus = (deltaDays: number) => {
  const current = new Date(viewYear.value, viewMonth.value, focusedDay.value)
  current.setDate(current.getDate() + deltaDays)
  viewYear.value = current.getFullYear()
  viewMonth.value = current.getMonth()
  void focusCell(current.getDate())
}

const onGridKey = (event: KeyboardEvent) => {
  const key = event.key
  if (
    key === 'ArrowLeft' ||
    key === 'ArrowRight' ||
    key === 'ArrowUp' ||
    key === 'ArrowDown' ||
    key === 'Home' ||
    key === 'End' ||
    key === 'PageUp' ||
    key === 'PageDown' ||
    key === 'Enter' ||
    key === ' '
  ) {
    event.preventDefault()
  }
  if (key === 'ArrowLeft') moveFocus(-1)
  else if (key === 'ArrowRight') moveFocus(1)
  else if (key === 'ArrowUp') moveFocus(-7)
  else if (key === 'ArrowDown') moveFocus(7)
  else if (key === 'Home') void focusCell(1)
  else if (key === 'End') void focusCell(daysInMonth(viewYear.value, viewMonth.value))
  else if (key === 'PageUp') {
    prevMonth()
    void focusCell(Math.min(focusedDay.value, daysInMonth(viewYear.value, viewMonth.value)))
  } else if (key === 'PageDown') {
    nextMonth()
    void focusCell(Math.min(focusedDay.value, daysInMonth(viewYear.value, viewMonth.value)))
  } else if (key === 'Enter' || key === ' ') {
    selectDate(new Date(viewYear.value, viewMonth.value, focusedDay.value))
  }
}

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      viewYear.value = v.getFullYear()
      viewMonth.value = v.getMonth()
      focusedDay.value = v.getDate()
    }
  },
)

const monthLabel = computed<string>(() => {
  const m = MONTH_LABELS[viewMonth.value] ?? ''
  return `${m} ${viewYear.value}`
})
</script>

<template>
  <div
    class="inline-block bg-surface border border-border rounded-md p-3 shadow-sm"
    data-test-id="base-calendar"
  >
    <div class="flex items-center justify-between mb-2 gap-2">
      <button
        type="button"
        class="inline-flex items-center justify-center w-7 h-7 rounded-sm text-text-subtle hover:bg-surface-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
        aria-label="Предыдущий месяц"
        data-test-id="base-calendar-prev"
        @click="prevMonth"
      >
        <ChevronLeft class="w-4 h-4" aria-hidden="true" />
      </button>
      <div
        class="text-sm font-medium text-text"
        data-test-id="base-calendar-title"
      >
        {{ monthLabel }}
      </div>
      <button
        type="button"
        class="inline-flex items-center justify-center w-7 h-7 rounded-sm text-text-subtle hover:bg-surface-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
        aria-label="Следующий месяц"
        data-test-id="base-calendar-next"
        @click="nextMonth"
      >
        <ChevronRight class="w-4 h-4" aria-hidden="true" />
      </button>
    </div>
    <div
      role="grid"
      ref="gridRef"
      class="grid grid-cols-7 gap-1"
      :aria-label="monthLabel"
      @keydown="onGridKey"
    >
      <div
        v-for="(wd, idx) in WEEKDAY_LABELS"
        :key="wd"
        role="columnheader"
        class="text-[11px] font-medium text-center py-1"
        :class="idx >= 5 ? 'text-text-subtle/70' : 'text-text-subtle'"
      >
        {{ wd }}
      </div>
      <template v-for="cell in cells" :key="`${cell.date.getTime()}`">
        <div role="gridcell" :aria-selected="cell.isSelected">
          <button
            type="button"
            class="w-8 h-8 inline-flex items-center justify-center rounded-sm text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            :class="[
              cell.isSelected
                ? 'bg-accent text-white font-medium'
                : cell.isToday
                  ? 'border border-accent text-accent'
                  : cell.inMonth
                    ? cell.isWeekend
                      ? 'text-text-subtle hover:bg-surface-muted'
                      : 'text-text hover:bg-surface-muted'
                    : 'text-text-subtle/50 hover:bg-surface-muted',
              cell.isDisabled
                ? 'opacity-40 cursor-not-allowed pointer-events-none'
                : '',
            ]"
            :tabindex="
              cell.inMonth && cell.day === focusedDay ? 0 : -1
            "
            :disabled="cell.isDisabled"
            :aria-label="cell.date.toDateString()"
            :data-test-id="`base-calendar-day-${cell.date.getFullYear()}-${cell.date.getMonth()}-${cell.day}`"
            @click="selectDate(cell.date)"
          >
            {{ cell.day }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
