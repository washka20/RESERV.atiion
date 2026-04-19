<script setup lang="ts">
/**
 * Confirm / alert диалог поверх BaseModal.
 * variant='danger' делает CTA danger-style.
 */
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

type Variant = 'neutral' | 'danger'

interface Props {
  modelValue: boolean
  title: string
  message?: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: Variant
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  confirmLabel: 'Подтвердить',
  cancelLabel: 'Отмена',
  variant: 'neutral',
  loading: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  confirm: []
  cancel: []
}>()

const close = () => emit('update:modelValue', false)
const onConfirm = () => emit('confirm')
const onCancel = () => {
  emit('cancel')
  close()
}
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    size="sm"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <p v-if="message" class="text-base text-text" data-test-id="base-dialog-message">
      {{ message }}
    </p>
    <slot />
    <template #footer>
      <BaseButton
        variant="secondary"
        data-test-id="base-dialog-cancel"
        @click="onCancel"
      >
        {{ cancelLabel }}
      </BaseButton>
      <BaseButton
        :variant="variant === 'danger' ? 'danger' : 'primary'"
        :loading="loading"
        data-test-id="base-dialog-confirm"
        @click="onConfirm"
      >
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
