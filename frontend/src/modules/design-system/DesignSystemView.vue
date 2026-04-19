<script setup lang="ts">
/**
 * DesignSystemView — живой каталог компонентов (storybook-lite).
 *
 * Layout: sticky anchor-nav слева, секции-демо справа. Theme toggle в шапке.
 * Каждая секция рендерит live-пример + mini code snippet.
 */
import { computed } from 'vue'
import { Sun, Moon, MonitorSmartphone } from 'lucide-vue-next'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useTheme } from '@/shared/composables/useTheme'

import ColorsSection from './sections/ColorsSection.vue'
import TypographySection from './sections/TypographySection.vue'
import SpacingSection from './sections/SpacingSection.vue'
import ButtonsSection from './sections/ButtonsSection.vue'
import InputsSection from './sections/InputsSection.vue'
import CardsSection from './sections/CardsSection.vue'
import TabsPaginationSection from './sections/TabsPaginationSection.vue'
import ModalsSection from './sections/ModalsSection.vue'
import FeedbackSection from './sections/FeedbackSection.vue'
import DataDisplaySection from './sections/DataDisplaySection.vue'
import FormsSection from './sections/FormsSection.vue'
import NavigationSection from './sections/NavigationSection.vue'

interface NavEntry {
  id: string
  label: string
}

const nav: NavEntry[] = [
  { id: 'colors', label: 'Цвета' },
  { id: 'typography', label: 'Типографика' },
  { id: 'spacing', label: 'Spacing' },
  { id: 'buttons', label: 'Buttons' },
  { id: 'inputs', label: 'Inputs' },
  { id: 'cards', label: 'Cards · Badges · Chips' },
  { id: 'tabs-pagination', label: 'Tabs · Pagination' },
  { id: 'modals', label: 'Modals · Dialog' },
  { id: 'feedback', label: 'Feedback · Toast' },
  { id: 'data-display', label: 'Data display' },
  { id: 'forms', label: 'Forms' },
  { id: 'navigation', label: 'Navigation' },
]

const { theme, isDark, setTheme } = useTheme()

const themeLabel = computed<string>(() => {
  if (theme.value === 'auto') return `Auto (${isDark.value ? 'dark' : 'light'})`
  return theme.value === 'dark' ? 'Dark' : 'Light'
})
</script>

<template>
  <div class="min-h-screen bg-bg text-text" data-test-id="design-system-view">
    <header
      class="sticky top-0 z-20 bg-surface/95 backdrop-blur border-b border-border"
    >
      <div class="max-w-[1200px] mx-auto px-4 md:px-6 py-4 flex items-center justify-between gap-4">
        <div class="flex flex-col">
          <span class="text-xs font-mono text-text-subtle uppercase tracking-wide">
            RESERV.atiion
          </span>
          <h1 class="text-xl font-semibold text-text leading-tight">Design System</h1>
        </div>
        <div class="flex items-center gap-2" data-test-id="design-system-theme-toggle">
          <span class="hidden sm:inline text-xs font-mono text-text-subtle mr-2">
            {{ themeLabel }}
          </span>
          <BaseButton
            variant="ghost"
            size="sm"
            :aria-pressed="theme === 'light'"
            @click="setTheme('light')"
          >
            <template #icon-left>
              <Sun class="w-4 h-4" aria-hidden="true" />
            </template>
            Light
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            :aria-pressed="theme === 'dark'"
            @click="setTheme('dark')"
          >
            <template #icon-left>
              <Moon class="w-4 h-4" aria-hidden="true" />
            </template>
            Dark
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            :aria-pressed="theme === 'auto'"
            @click="setTheme('auto')"
          >
            <template #icon-left>
              <MonitorSmartphone class="w-4 h-4" aria-hidden="true" />
            </template>
            Auto
          </BaseButton>
        </div>
      </div>
    </header>

    <div class="max-w-[1200px] mx-auto px-4 md:px-6 py-8 flex gap-8">
      <aside
        class="hidden lg:block w-56 shrink-0 sticky top-24 self-start"
        aria-label="Design system sections"
      >
        <nav data-test-id="design-system-nav">
          <ul class="flex flex-col gap-1">
            <li v-for="entry in nav" :key="entry.id">
              <a
                :href="`#${entry.id}`"
                class="block px-3 py-1.5 rounded-sm text-sm text-text-subtle hover:bg-surface-muted hover:text-text transition-colors"
                :data-test-id="`design-system-nav-${entry.id}`"
              >
                {{ entry.label }}
              </a>
            </li>
          </ul>
        </nav>
      </aside>

      <main class="flex-1 min-w-0 flex flex-col gap-16">
        <section :id="'colors'" class="scroll-mt-24">
          <ColorsSection />
        </section>
        <section :id="'typography'" class="scroll-mt-24">
          <TypographySection />
        </section>
        <section :id="'spacing'" class="scroll-mt-24">
          <SpacingSection />
        </section>
        <section :id="'buttons'" class="scroll-mt-24">
          <ButtonsSection />
        </section>
        <section :id="'inputs'" class="scroll-mt-24">
          <InputsSection />
        </section>
        <section :id="'cards'" class="scroll-mt-24">
          <CardsSection />
        </section>
        <section :id="'tabs-pagination'" class="scroll-mt-24">
          <TabsPaginationSection />
        </section>
        <section :id="'modals'" class="scroll-mt-24">
          <ModalsSection />
        </section>
        <section :id="'feedback'" class="scroll-mt-24">
          <FeedbackSection />
        </section>
        <section :id="'data-display'" class="scroll-mt-24">
          <DataDisplaySection />
        </section>
        <section :id="'forms'" class="scroll-mt-24">
          <FormsSection />
        </section>
        <section :id="'navigation'" class="scroll-mt-24">
          <NavigationSection />
        </section>
      </main>
    </div>
  </div>
</template>
