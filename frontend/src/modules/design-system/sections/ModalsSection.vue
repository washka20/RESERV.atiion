<script setup lang="ts">
/**
 * Modals + Dialog (confirm).
 */
import { ref } from 'vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseModal from '@/shared/components/base/BaseModal.vue'
import BaseDialog from '@/shared/components/base/BaseDialog.vue'
import { useToast } from '@/shared/composables/useToast'

const modalOpen = ref(false)
const confirmOpen = ref(false)
const dangerOpen = ref(false)

const { toast } = useToast()

const onConfirm = () => {
  confirmOpen.value = false
  toast.success('Подтверждено')
}

const onDelete = () => {
  dangerOpen.value = false
  toast.error('Удалено (demo)')
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Modals · Dialog</h2>
      <p class="text-sm text-text-subtle mt-1">
        Teleport в body, focus-trap, Escape-close, backdrop click.
      </p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-wrap gap-3">
      <BaseButton @click="modalOpen = true">Open modal</BaseButton>
      <BaseButton variant="secondary" @click="confirmOpen = true">Confirm dialog</BaseButton>
      <BaseButton variant="danger" @click="dangerOpen = true">Danger dialog</BaseButton>
    </div>

    <BaseModal v-model="modalOpen" title="Редактирование профиля" size="md">
      <p class="text-sm text-text">
        Это пример модалки. Закрыть можно по Escape, по клику на backdrop, или на крестик в шапке.
      </p>
      <p class="text-sm text-text-subtle mt-3">
        Внутри можно размещать формы, списки, детали бронирования — focus-trap удержит фокус
        в пределах модалки.
      </p>
      <template #footer>
        <BaseButton variant="secondary" @click="modalOpen = false">Отмена</BaseButton>
        <BaseButton @click="modalOpen = false">Сохранить</BaseButton>
      </template>
    </BaseModal>

    <BaseDialog
      v-model="confirmOpen"
      title="Подтвердить действие?"
      message="После подтверждения изменения нельзя будет откатить."
      @confirm="onConfirm"
    />

    <BaseDialog
      v-model="dangerOpen"
      title="Удалить услугу?"
      message="Услуга и все связанные брони будут помечены как удалённые."
      variant="danger"
      confirm-label="Удалить"
      @confirm="onDelete"
    />

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseModal v-model="open" title="..."&gt;
  Контент
  &lt;template #footer&gt;&lt;BaseButton&gt;OK&lt;/BaseButton&gt;&lt;/template&gt;
&lt;/BaseModal&gt;

&lt;BaseDialog v-model="open" title="..." variant="danger" @confirm="..." /&gt;</code></pre>
  </div>
</template>
