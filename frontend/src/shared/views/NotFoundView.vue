<script setup lang="ts">
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
</script>

<template>
  <section
    class="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center px-4 py-12"
    data-test-id="not-found-view"
  >
    <BaseEmptyState
      title="Страница не найдена"
      description="Возможно, вы перешли по устаревшей ссылке или опечатались в адресе."
    >
      <template #action>
        <BaseButton variant="primary" @click="$router.push({ name: 'catalog' })">
          В каталог
        </BaseButton>
      </template>
    </BaseEmptyState>
  </section>
</template>
