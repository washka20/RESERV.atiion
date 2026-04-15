<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
const serviceId = route.query.serviceId as string | undefined
</script>

<template>
  <div
    class="mx-auto max-w-2xl px-4 py-12 text-center sm:px-6 lg:px-8"
    data-test-id="booking-stub-page"
  >
    <h1 class="text-2xl font-bold text-gray-900">Бронирование</h1>
    <p class="mt-2 text-sm text-gray-500">
      Модуль бронирования будет реализован в Plan 7.
    </p>
    <p v-if="serviceId" class="mt-4 text-sm text-gray-700">
      Услуга: <code class="rounded bg-gray-100 px-1.5 py-0.5">{{ serviceId }}</code>
    </p>
  </div>
</template>
