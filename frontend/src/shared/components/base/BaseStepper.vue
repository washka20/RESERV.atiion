<script setup lang="ts">
/**
 * Горизонтальный stepper: круги с номерами + линии между шагами.
 * Активный шаг крашен в accent, пройденные — с check icon.
 * Если navigable=true — click по шагу эмитит update:modelValue.
 */
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'

interface Step {
  id: string | number
  label: string
  description?: string
}

interface Props {
  steps: Step[]
  modelValue: string | number
  navigable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  navigable: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const activeIndex = computed<number>(() =>
  props.steps.findIndex((s) => s.id === props.modelValue),
)

const onStepClick = (step: Step) => {
  if (!props.navigable) return
  emit('update:modelValue', step.id)
}

const stateFor = (index: number): 'completed' | 'active' | 'upcoming' => {
  if (index < activeIndex.value) return 'completed'
  if (index === activeIndex.value) return 'active'
  return 'upcoming'
}
</script>

<template>
  <ol
    class="flex items-start w-full"
    data-test-id="base-stepper"
    aria-label="Progress"
  >
    <li
      v-for="(step, index) in steps"
      :key="step.id"
      class="flex-1 flex items-start"
      :class="{ 'pointer-events-none': false }"
    >
      <div class="flex flex-col items-center flex-1 min-w-0">
        <div class="flex items-center w-full">
          <div
            v-if="index > 0"
            class="h-0.5 flex-1"
            :class="
              stateFor(index) === 'upcoming' ? 'bg-border' : 'bg-accent'
            "
            aria-hidden="true"
          />
          <button
            type="button"
            class="relative shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            :class="{
              'bg-accent text-white border border-accent':
                stateFor(index) !== 'upcoming',
              'bg-surface text-text-subtle border border-border':
                stateFor(index) === 'upcoming',
              'cursor-pointer': navigable,
              'cursor-default': !navigable,
            }"
            :aria-current="stateFor(index) === 'active' ? 'step' : undefined"
            :disabled="!navigable"
            :data-test-id="`base-stepper-step-${step.id}`"
            @click="onStepClick(step)"
          >
            <Check
              v-if="stateFor(index) === 'completed'"
              class="w-4 h-4"
              aria-hidden="true"
            />
            <span v-else>{{ index + 1 }}</span>
          </button>
          <div
            v-if="index < steps.length - 1"
            class="h-0.5 flex-1"
            :class="
              stateFor(index + 1) === 'upcoming' ? 'bg-border' : 'bg-accent'
            "
            aria-hidden="true"
          />
        </div>
        <div class="mt-2 text-center min-w-0 px-1">
          <div
            class="text-xs font-medium truncate"
            :class="
              stateFor(index) === 'upcoming' ? 'text-text-subtle' : 'text-text'
            "
          >
            {{ step.label }}
          </div>
          <div
            v-if="step.description"
            class="text-[11px] text-text-subtle truncate"
          >
            {{ step.description }}
          </div>
        </div>
      </div>
    </li>
  </ol>
</template>
