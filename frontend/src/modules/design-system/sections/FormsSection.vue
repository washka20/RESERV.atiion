<script setup lang="ts">
/**
 * Forms: Stepper (navigable) + FileUploader.
 */
import { ref } from 'vue'
import BaseStepper from '@/shared/components/base/BaseStepper.vue'
import BaseFileUploader from '@/shared/components/base/BaseFileUploader.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useToast } from '@/shared/composables/useToast'

const steps = [
  { id: 'service', label: 'Услуга', description: 'Выбор типа и мастера' },
  { id: 'slot', label: 'Слот', description: 'Дата и время' },
  { id: 'contact', label: 'Контакты', description: 'Имя и телефон' },
  { id: 'confirm', label: 'Готово', description: 'Подтверждение' },
]

const currentStep = ref<string | number>('slot')
const files = ref<File[]>([])
const { toast } = useToast()

const next = () => {
  const idx = steps.findIndex((s) => s.id === currentStep.value)
  if (idx < steps.length - 1) {
    const nextStep = steps[idx + 1]
    if (nextStep) currentStep.value = nextStep.id
  }
}

const prev = () => {
  const idx = steps.findIndex((s) => s.id === currentStep.value)
  if (idx > 0) {
    const prevStep = steps[idx - 1]
    if (prevStep) currentStep.value = prevStep.id
  }
}

const onFileError = (message: string) => {
  toast.error(message)
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Forms</h2>
      <p class="text-sm text-text-subtle mt-1">Stepper + FileUploader с drag-n-drop.</p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-6">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">Stepper (navigable)</span>
      <BaseStepper v-model="currentStep" :steps="steps" navigable />
      <div class="flex gap-2">
        <BaseButton variant="secondary" @click="prev">Назад</BaseButton>
        <BaseButton @click="next">Далее</BaseButton>
      </div>
    </div>

    <div class="bg-surface border border-border rounded-md p-6 flex flex-col gap-4">
      <span class="font-mono text-xs text-text-subtle uppercase tracking-wide">FileUploader</span>
      <BaseFileUploader
        v-model="files"
        multiple
        accept="image/*"
        :max-size-mb="5"
        label="Перетащите фото или выберите"
        hint="PNG / JPG, до 5 MB"
        @error="onFileError"
      />
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseStepper v-model="step" :steps="steps" navigable /&gt;
&lt;BaseFileUploader v-model="files" multiple :max-size-mb="5" @error="..." /&gt;</code></pre>
  </div>
</template>
