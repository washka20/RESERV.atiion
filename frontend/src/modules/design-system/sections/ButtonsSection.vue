<script setup lang="ts">
/**
 * BaseButton — все variants × sizes + loading/disabled.
 */
import { Plus, ArrowRight } from 'lucide-vue-next'
import BaseButton from '@/shared/components/base/BaseButton.vue'
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Buttons</h2>
      <p class="text-sm text-text-subtle mt-1">4 variant × 3 size + loading/disabled + icon slots.</p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-6">
      <div class="flex flex-col gap-2">
        <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Variants</span>
        <div class="flex flex-wrap gap-3">
          <BaseButton variant="primary">Primary</BaseButton>
          <BaseButton variant="secondary">Secondary</BaseButton>
          <BaseButton variant="ghost">Ghost</BaseButton>
          <BaseButton variant="danger">Danger</BaseButton>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Sizes</span>
        <div class="flex flex-wrap items-center gap-3">
          <BaseButton size="sm">Small</BaseButton>
          <BaseButton size="md">Medium</BaseButton>
          <BaseButton size="lg">Large</BaseButton>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">States</span>
        <div class="flex flex-wrap gap-3">
          <BaseButton loading>Loading</BaseButton>
          <BaseButton disabled>Disabled</BaseButton>
          <BaseButton variant="danger" disabled>Danger disabled</BaseButton>
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">With icons</span>
        <div class="flex flex-wrap gap-3">
          <BaseButton>
            <template #icon-left>
              <Plus class="w-4 h-4" aria-hidden="true" />
            </template>
            Добавить
          </BaseButton>
          <BaseButton variant="secondary">
            Далее
            <template #icon-right>
              <ArrowRight class="w-4 h-4" aria-hidden="true" />
            </template>
          </BaseButton>
          <BaseButton full-width variant="primary">Full width</BaseButton>
        </div>
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseButton variant="primary" size="md"&gt;Click&lt;/BaseButton&gt;
&lt;BaseButton loading&gt;Loading&lt;/BaseButton&gt;</code></pre>
  </div>
</template>
