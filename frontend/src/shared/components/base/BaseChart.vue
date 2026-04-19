<script setup lang="ts">
/**
 * Wireframe-график (SVG) для заглушки под будущий chart.js/d3.
 * Отрисовывает оси, сетку и упрощённое представление данных: bars / line / area.
 */
import { computed } from 'vue'

type ChartType = 'bar' | 'line' | 'area'

interface DataPoint {
  x: string
  y: number
}

interface Props {
  type: ChartType
  data: DataPoint[]
  label?: string
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  height: 200,
})

const PADDING = { top: 16, right: 12, bottom: 24, left: 32 }
const VIEW_WIDTH = 320

const innerWidth = computed<number>(() => VIEW_WIDTH - PADDING.left - PADDING.right)
const innerHeight = computed<number>(() => props.height - PADDING.top - PADDING.bottom)

const maxY = computed<number>(() => {
  if (props.data.length === 0) return 1
  const m = Math.max(...props.data.map((d) => d.y))
  return m > 0 ? m : 1
})

const yFor = (val: number): number => {
  return PADDING.top + innerHeight.value - (val / maxY.value) * innerHeight.value
}

const xFor = (i: number): number => {
  if (props.data.length <= 1) return PADDING.left + innerWidth.value / 2
  return PADDING.left + (i / (props.data.length - 1)) * innerWidth.value
}

const gridLines = computed<number[]>(() => {
  const count = 4
  const step = innerHeight.value / count
  const arr: number[] = []
  for (let i = 0; i <= count; i += 1) {
    arr.push(PADDING.top + i * step)
  }
  return arr
})

const barWidth = computed<number>(() => {
  if (props.data.length === 0) return 0
  return (innerWidth.value / props.data.length) * 0.6
})

const barX = (i: number): number => {
  const slot = innerWidth.value / props.data.length
  return PADDING.left + i * slot + slot / 2 - barWidth.value / 2
}

const linePath = computed<string>(() => {
  if (props.data.length === 0) return ''
  return props.data
    .map((d, i) => `${i === 0 ? 'M' : 'L'}${xFor(i).toFixed(2)},${yFor(d.y).toFixed(2)}`)
    .join(' ')
})

const areaPath = computed<string>(() => {
  if (props.data.length === 0) return ''
  const line = linePath.value
  const last = props.data[props.data.length - 1]
  if (!last) return ''
  const baseRight = `L${xFor(props.data.length - 1).toFixed(2)},${(PADDING.top + innerHeight.value).toFixed(2)}`
  const baseLeft = `L${xFor(0).toFixed(2)},${(PADDING.top + innerHeight.value).toFixed(2)} Z`
  return `${line} ${baseRight} ${baseLeft}`
})
</script>

<template>
  <figure
    class="w-full bg-surface border border-border rounded-md p-3"
    data-test-id="base-chart"
  >
    <figcaption
      v-if="label"
      class="text-xs font-medium text-text-subtle mb-2"
    >
      {{ label }}
    </figcaption>
    <svg
      :viewBox="`0 0 ${VIEW_WIDTH} ${height}`"
      :height="height"
      width="100%"
      role="img"
      :aria-label="label || 'Chart'"
      preserveAspectRatio="none"
      :data-test-id="`base-chart-svg-${type}`"
    >
      <g stroke="var(--color-border)" stroke-width="1">
        <line
          v-for="(y, i) in gridLines"
          :key="`grid-${i}`"
          :x1="PADDING.left"
          :x2="VIEW_WIDTH - PADDING.right"
          :y1="y"
          :y2="y"
          stroke-dasharray="2 3"
          opacity="0.5"
        />
      </g>
      <line
        :x1="PADDING.left"
        :x2="PADDING.left"
        :y1="PADDING.top"
        :y2="PADDING.top + innerHeight"
        stroke="var(--color-border)"
      />
      <line
        :x1="PADDING.left"
        :x2="VIEW_WIDTH - PADDING.right"
        :y1="PADDING.top + innerHeight"
        :y2="PADDING.top + innerHeight"
        stroke="var(--color-border)"
      />
      <template v-if="type === 'bar'">
        <rect
          v-for="(d, i) in data"
          :key="`bar-${i}`"
          :x="barX(i)"
          :y="yFor(d.y)"
          :width="barWidth"
          :height="PADDING.top + innerHeight - yFor(d.y)"
          fill="var(--color-accent)"
          opacity="0.8"
          rx="2"
          data-test-id="base-chart-bar"
        />
      </template>
      <template v-if="type === 'area'">
        <path :d="areaPath" fill="var(--color-accent)" opacity="0.2" />
      </template>
      <template v-if="type === 'line' || type === 'area'">
        <path
          :d="linePath"
          fill="none"
          stroke="var(--color-accent)"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          data-test-id="base-chart-line"
        />
        <circle
          v-for="(d, i) in data"
          :key="`pt-${i}`"
          :cx="xFor(i)"
          :cy="yFor(d.y)"
          r="2.5"
          fill="var(--color-surface)"
          stroke="var(--color-accent)"
          stroke-width="1.5"
          data-test-id="base-chart-point"
        />
      </template>
      <g font-size="9" fill="var(--color-text-subtle)" text-anchor="middle">
        <text
          v-for="(d, i) in data"
          :key="`lx-${i}`"
          :x="xFor(i)"
          :y="height - 6"
        >
          {{ d.x }}
        </text>
      </g>
    </svg>
  </figure>
</template>
