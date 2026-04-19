<script setup lang="ts">
/**
 * Шкала spacing — визуальная демонстрация padding/margin.
 */
interface ScaleEntry {
  token: string
  tailwind: string
  px: number
}

const scale: ScaleEntry[] = [
  { token: 'space-1', tailwind: 'p-1', px: 4 },
  { token: 'space-2', tailwind: 'p-2', px: 8 },
  { token: 'space-3', tailwind: 'p-3', px: 12 },
  { token: 'space-4', tailwind: 'p-4', px: 16 },
  { token: 'space-6', tailwind: 'p-6', px: 24 },
  { token: 'space-8', tailwind: 'p-8', px: 32 },
  { token: 'space-12', tailwind: 'p-12', px: 48 },
]
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Spacing</h2>
      <p class="text-sm text-text-subtle mt-1">Шаг 4px. Используй tailwind утилиты p-1..p-12.</p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-3">
      <div
        v-for="entry in scale"
        :key="entry.token"
        class="flex items-center gap-4"
      >
        <div class="w-32 shrink-0">
          <div class="font-mono text-xs text-text">{{ entry.tailwind }}</div>
          <div class="font-mono text-xs text-text-subtle">{{ entry.px }}px · {{ entry.token }}</div>
        </div>
        <div class="flex-1 bg-accent-soft rounded-sm">
          <div
            class="bg-accent h-4 rounded-sm"
            :style="{ width: `${entry.px * 2}px` }"
          />
        </div>
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;div class="p-4 gap-3"&gt;...&lt;/div&gt;
&lt;div class="px-6 py-3"&gt;...&lt;/div&gt;</code></pre>
  </div>
</template>
