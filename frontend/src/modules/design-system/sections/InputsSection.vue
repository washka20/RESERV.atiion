<script setup lang="ts">
/**
 * Inputs: Input / Textarea / Select / Checkbox / Radio / Toggle.
 */
import { ref } from 'vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseTextarea from '@/shared/components/base/BaseTextarea.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseCheckbox from '@/shared/components/base/BaseCheckbox.vue'
import BaseRadio from '@/shared/components/base/BaseRadio.vue'
import BaseToggle from '@/shared/components/base/BaseToggle.vue'

const email = ref('user@example.com')
const bad = ref('abc')
const note = ref('')
const city = ref<string | number>('')
const consent = ref(true)
const plan = ref<string | number | null>('pro')
const notifications = ref(true)

const cityOptions = [
  { value: 'msk', label: 'Москва' },
  { value: 'spb', label: 'Санкт-Петербург' },
  { value: 'kzn', label: 'Казань' },
]
</script>

<template>
  <div class="flex flex-col gap-6">
    <header>
      <h2 class="text-2xl font-semibold text-text">Form controls</h2>
      <p class="text-sm text-text-subtle mt-1">
        Поля с label / helper / error. Все контролы поддерживают v-model.
      </p>
    </header>

    <div class="bg-surface border border-border rounded-md p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <BaseInput
        v-model="email"
        label="Email"
        type="email"
        placeholder="you@example.com"
        helper="Мы не передаём адрес третьим сторонам"
      />

      <BaseInput
        v-model="bad"
        label="Пароль"
        type="password"
        error="Минимум 8 символов"
      />

      <BaseTextarea
        v-model="note"
        label="Комментарий"
        placeholder="Пожелания к администратору…"
        helper="До 500 символов"
        :rows="3"
      />

      <BaseSelect
        v-model="city"
        label="Город"
        :options="cityOptions"
        placeholder="— выберите —"
      />

      <div class="flex flex-col gap-3">
        <span class="text-sm font-medium text-text">Согласия</span>
        <BaseCheckbox v-model="consent" label="Согласен на рассылку" />
        <BaseCheckbox :model-value="false" disabled label="Disabled" />
      </div>

      <div class="flex flex-col gap-3">
        <span class="text-sm font-medium text-text">Тариф</span>
        <BaseRadio v-model="plan" name="plan" value="free" label="Free" />
        <BaseRadio v-model="plan" name="plan" value="pro" label="Pro" />
        <BaseRadio v-model="plan" name="plan" value="biz" label="Business" />
      </div>

      <div class="flex flex-col gap-3">
        <span class="text-sm font-medium text-text">Переключатели</span>
        <BaseToggle v-model="notifications" label="Push-уведомления" />
        <BaseToggle :model-value="false" disabled label="Beta features (disabled)" />
      </div>
    </div>

    <pre class="font-mono text-xs bg-surface-muted p-3 rounded-md overflow-x-auto"><code>&lt;BaseInput v-model="email" label="Email" helper="..." /&gt;
&lt;BaseSelect v-model="city" :options="cityOptions" /&gt;
&lt;BaseToggle v-model="enabled" label="On" /&gt;</code></pre>
  </div>
</template>
