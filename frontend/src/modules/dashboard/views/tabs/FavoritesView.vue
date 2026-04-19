<script setup lang="ts">
/**
 * FavoritesView — вкладка dashboard "Избранное".
 *
 * Backend /favorites появится позже — пока empty state + CTA в каталог.
 */
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { Heart } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'

const { t } = useI18n()
const router = useRouter()

function goToCatalog(): void {
  void router.push({ name: 'catalog' })
}
</script>

<template>
  <section data-test-id="dashboard-tab-favorites">
    <BaseCard padding="lg">
      <header class="mb-4">
        <h2 class="text-lg font-semibold text-text">
          {{ t('profile.favoritesTitle') }}
        </h2>
      </header>

      <div data-test-id="favorites-empty">
        <BaseEmptyState
          :title="t('profile.favoritesEmptyTitle')"
          :description="t('profile.favoritesEmptyDesc')"
        >
          <template #icon>
            <Heart class="h-8 w-8" aria-hidden="true" />
          </template>
          <template #action>
            <BaseButton
              variant="primary"
              test-id="favorites-browse-btn"
              @click="goToCatalog"
            >
              {{ t('profile.favoritesCta') }}
            </BaseButton>
          </template>
        </BaseEmptyState>
      </div>
    </BaseCard>
  </section>
</template>
