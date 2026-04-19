<script setup lang="ts">
/**
 * Демонстрация цветовых токенов. Swatch grid с именем и CSS var.
 */
interface Swatch {
  name: string
  className: string
  varName: string
  textOn?: string
}

const swatches: Swatch[] = [
  { name: 'bg', className: 'bg-bg', varName: '--color-bg', textOn: 'text-text' },
  { name: 'surface', className: 'bg-surface', varName: '--color-surface', textOn: 'text-text' },
  { name: 'surface-muted', className: 'bg-surface-muted', varName: '--color-surface-muted', textOn: 'text-text' },
  { name: 'accent', className: 'bg-accent', varName: '--color-accent', textOn: 'text-white' },
  { name: 'accent-soft', className: 'bg-accent-soft', varName: '--color-accent-soft', textOn: 'text-text' },
  { name: 'danger', className: 'bg-danger', varName: '--color-danger', textOn: 'text-white' },
  { name: 'success', className: 'bg-success', varName: '--color-success', textOn: 'text-white' },
  { name: 'warning', className: 'bg-warning', varName: '--color-warning', textOn: 'text-white' },
  { name: 'text', className: 'bg-text', varName: '--color-text', textOn: 'text-white' },
  { name: 'text-subtle', className: 'bg-text-subtle', varName: '--color-text-subtle', textOn: 'text-white' },
  { name: 'border', className: 'bg-border', varName: '--color-border', textOn: 'text-text' },
]
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Цвета</h2>
      <p class="text-sm text-text-subtle mt-1">
        Design tokens через CSS custom properties. Toggle темы — в шапке страницы.
      </p>
    </header>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <div
        v-for="swatch in swatches"
        :key="swatch.name"
        class="rounded-md border border-border overflow-hidden bg-surface"
      >
        <div
          class="h-20 w-full flex items-end justify-start p-3"
          :class="[swatch.className, swatch.textOn ?? 'text-text']"
        >
          <span class="font-mono text-xs">{{ swatch.name }}</span>
        </div>
        <div class="px-3 py-2 border-t border-border">
          <code class="font-mono text-xs text-text-subtle">{{ swatch.varName }}</code>
        </div>
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;div class="bg-accent text-white"&gt;Accent&lt;/div&gt;
&lt;div class="bg-surface border border-border"&gt;Surface&lt;/div&gt;</code></pre>
  </div>
</template>
