<script setup lang="ts">
/**
 * Feedback: Avatar / EmptyState / Skeleton + Toast triggers.
 */
import { Inbox } from 'lucide-vue-next'
import BaseAvatar from '@/shared/components/base/BaseAvatar.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseSkeleton from '@/shared/components/base/BaseSkeleton.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useToast } from '@/shared/composables/useToast'

const { toast } = useToast()
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Feedback</h2>
      <p class="text-sm text-text-subtle mt-1">
        Avatar / EmptyState / Skeleton / Toast — пользовательская обратная связь.
      </p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Avatars</span>
      <div class="flex flex-wrap items-end gap-4">
        <BaseAvatar alt="Анна Савина" size="sm" />
        <BaseAvatar alt="Игорь Петров" size="md" />
        <BaseAvatar alt="Мария Ключникова" size="lg" />
        <BaseAvatar alt="Admin User" size="xl" shape="square" />
        <BaseAvatar alt="Broken" src="https://example.invalid/none.jpg" size="md" />
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Toast triggers</span>
      <div class="flex flex-wrap gap-3">
        <BaseButton @click="toast.success('Бронь подтверждена')">Success</BaseButton>
        <BaseButton variant="danger" @click="toast.error('Слот уже забронирован')">Error</BaseButton>
        <BaseButton variant="secondary" @click="toast.info('Новая версия доступна')">Info</BaseButton>
        <BaseButton variant="ghost" @click="toast.warning('Срок почти истёк')">Warning</BaseButton>
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Skeleton</span>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseSkeleton variant="text" :lines="4" />
        <BaseSkeleton variant="card" />
        <div class="flex items-center gap-3">
          <BaseSkeleton variant="circle" />
          <BaseSkeleton variant="text" :lines="2" />
        </div>
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6">
      <BaseEmptyState
        title="Пока нет бронирований"
        description="Как только клиент забронирует слот — появится здесь. Создай первую услугу, чтобы начать."
      >
        <template #icon>
          <Inbox class="w-12 h-12" aria-hidden="true" />
        </template>
        <template #action>
          <BaseButton>Создать услугу</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>const { toast } = useToast()
toast.success('Готово!')

&lt;BaseAvatar alt="John Doe" size="lg" /&gt;
&lt;BaseEmptyState title="Пусто" /&gt;</code></pre>
  </div>
</template>
