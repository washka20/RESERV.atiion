<script setup lang="ts">
import type { Booking } from '@/types/booking.types'
import BookingCard from './BookingCard.vue'

defineProps<{ bookings: Booking[] }>()
defineEmits<{ cancel: [id: string] }>()
</script>

<template>
  <div class="space-y-3" data-test-id="dashboard-bookings-list">
    <BookingCard
      v-for="b in bookings"
      :key="b.id"
      :booking="b"
      @cancel="$emit('cancel', $event)"
    />
  </div>
</template>
