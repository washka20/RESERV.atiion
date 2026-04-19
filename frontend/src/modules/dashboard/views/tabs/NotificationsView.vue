<script setup lang="ts">
/**
 * NotificationsView — вкладка dashboard с уведомлениями.
 *
 * Backend /notifications появится позже, поэтому сейчас:
 *  - пустой список (BaseEmptyState);
 *  - секция "настройки каналов" с BaseToggle — сохранение stub.
 */
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bell } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseToggle from '@/shared/components/base/BaseToggle.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const { toast } = useToast()

const emailEnabled = ref<boolean>(true)
const phoneEnabled = ref<boolean>(false)

function handleSaveChannels(): void {
  toast.error(t('profile.channelsSaveStub'))
}
</script>

<template>
  <section
    class="flex flex-col gap-6"
    data-test-id="dashboard-tab-notifications"
  >
    <BaseCard padding="lg">
      <header class="mb-4">
        <h2 class="text-lg font-semibold text-text">
          {{ t('profile.notificationsTitle') }}
        </h2>
      </header>

      <div data-test-id="notifications-empty">
        <BaseEmptyState
          :title="t('profile.notificationsEmptyTitle')"
          :description="t('profile.notificationsEmptyDesc')"
        >
          <template #icon>
            <Bell class="h-8 w-8" aria-hidden="true" />
          </template>
        </BaseEmptyState>
      </div>
    </BaseCard>

    <BaseCard padding="lg">
      <header class="mb-4">
        <h2 class="text-lg font-semibold text-text">
          {{ t('profile.notificationsChannelsTitle') }}
        </h2>
      </header>

      <div class="flex flex-col gap-4">
        <BaseToggle
          v-model="emailEnabled"
          :label="t('profile.channelEmail')"
          id="notifications-email-toggle"
        />
        <BaseToggle
          v-model="phoneEnabled"
          :label="t('profile.channelPhone')"
          id="notifications-phone-toggle"
        />

        <div class="flex items-center justify-end">
          <BaseButton
            variant="primary"
            size="sm"
            test-id="notifications-save-channels-btn"
            @click="handleSaveChannels"
          >
            {{ t('profile.save') }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>
  </section>
</template>
