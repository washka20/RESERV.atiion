<script setup lang="ts">
/**
 * Шкала типографики: h1/h2/h3/p/small/caption.
 */
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Типографика</h2>
      <p class="text-sm text-text-subtle mt-1">Manrope sans + JetBrains Mono для кода.</p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <div>
        <span class="font-mono text-xs text-text-subtle">h1 · 48px / 700</span>
        <h1 class="text-4xl font-bold text-text leading-tight tracking-tight mt-1">
          Бронируй, что важно
        </h1>
      </div>
      <div>
        <span class="font-mono text-xs text-text-subtle">h2 · 36px / 700</span>
        <h2 class="text-3xl font-bold text-text leading-tight tracking-tight mt-1">
          Готовые решения
        </h2>
      </div>
      <div>
        <span class="font-mono text-xs text-text-subtle">h3 · 22px / 600</span>
        <h3 class="text-xl font-semibold text-text leading-snug mt-1">
          Категории услуг
        </h3>
      </div>
      <div>
        <span class="font-mono text-xs text-text-subtle">p · 15px / 400 / leading 1.55</span>
        <p class="text-base text-text leading-relaxed mt-1 max-w-prose">
          Основной текст интерфейса. Читается комфортно, не утомляет при длинных блоках.
          Используй для описаний, параграфов, каталожных карточек.
        </p>
      </div>
      <div>
        <span class="font-mono text-xs text-text-subtle">small · 13px / 500</span>
        <p class="text-sm font-medium text-text-subtle mt-1">
          Мелкий текст — подписи, метки, вспомогательная информация
        </p>
      </div>
      <div>
        <span class="font-mono text-xs text-text-subtle">caption · 12px / 500 uppercase</span>
        <p class="text-xs font-medium text-text-subtle uppercase tracking-wide mt-1">
          Label · tag · caption
        </p>
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;h1 class="text-4xl font-bold tracking-tight"&gt;Title&lt;/h1&gt;
&lt;p class="text-base text-text leading-relaxed"&gt;Body&lt;/p&gt;</code></pre>
  </div>
</template>
