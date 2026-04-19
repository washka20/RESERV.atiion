<script setup lang="ts">
/**
 * OrgSettingsView — настройки organization + danger zone.
 *
 * Секции:
 *   1. Профиль (name, description, type, city, phone, email)
 *   2. Политика отмены (select)
 *   3. Верификация (placeholder — появится в Plan 11 KYC)
 *   4. Danger zone — архивирование organization (stub; endpoint доступен
 *      только platform-admin, не owner)
 *
 * Submit формы и архивирование — stub-ы (backend endpoints для owner
 * пока не реализованы). При любой ошибке → toast.
 */
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ShieldAlert } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseTextarea from '@/shared/components/base/BaseTextarea.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import BaseDialog from '@/shared/components/base/BaseDialog.vue'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const authStore = useAuthStore()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const canArchive = computed<boolean>(() =>
  authStore.canAccessOrg(orgSlug.value, 'organization.archive'),
)

const form = reactive({
  name: '',
  description: '',
  type: 'salon' as 'salon' | 'rental' | 'consult' | 'other',
  city: '',
  phone: '',
  email: '',
  policy: 'moderate' as 'flexible' | 'moderate' | 'strict',
})

const typeOptions = computed(() => [
  { value: 'salon', label: t('provider.settings.typeSalon') },
  { value: 'rental', label: t('provider.settings.typeRental') },
  { value: 'consult', label: t('provider.settings.typeConsult') },
  { value: 'other', label: t('provider.settings.typeOther') },
])

const policyOptions = computed(() => [
  { value: 'flexible', label: t('provider.settings.policyFlexible') },
  { value: 'moderate', label: t('provider.settings.policyModerate') },
  { value: 'strict', label: t('provider.settings.policyStrict') },
])

const typeModel = computed({
  get: () => form.type,
  set: (v: string | number) => {
    form.type = String(v) as typeof form.type
  },
})

const policyModel = computed({
  get: () => form.policy,
  set: (v: string | number) => {
    form.policy = String(v) as typeof form.policy
  },
})

const isSubmitting = ref<boolean>(false)
const archiveDialogOpen = ref<boolean>(false)

function handleSubmit(): void {
  isSubmitting.value = true
  toast.error(t('provider.settings.saveStubError'))
  isSubmitting.value = false
}

function onArchiveConfirm(): void {
  toast.error(t('provider.settings.dangerStubError'))
  archiveDialogOpen.value = false
}
</script>

<template>
  <section
    data-test-id="org-settings-view"
    class="flex flex-col gap-6 max-w-3xl"
  >
    <h1
      class="text-2xl font-bold tracking-tight text-text"
      data-test-id="org-settings-title"
    >
      {{ t('provider.settings.title') }}
    </h1>

    <form
      class="flex flex-col gap-6"
      novalidate
      data-test-id="org-settings-form"
      @submit.prevent="handleSubmit"
    >
      <BaseCard padding="lg">
        <h2
          class="text-lg font-semibold text-text mb-4"
          data-test-id="org-settings-profile-title"
        >
          {{ t('provider.settings.profileTitle') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput
            v-model="form.name"
            :label="t('provider.settings.name')"
            required
            test-id="org-settings-name-input"
          />
          <BaseSelect
            v-model="typeModel"
            :options="typeOptions"
            :label="t('provider.settings.type')"
            test-id="org-settings-type-select"
          />
          <div class="md:col-span-2">
            <BaseTextarea
              v-model="form.description"
              :label="t('provider.settings.description')"
              test-id="org-settings-description-input"
            />
          </div>
          <BaseInput
            v-model="form.city"
            :label="t('provider.settings.city')"
            test-id="org-settings-city-input"
          />
          <BaseInput
            v-model="form.phone"
            type="tel"
            :label="t('provider.settings.phone')"
            test-id="org-settings-phone-input"
          />
          <BaseInput
            v-model="form.email"
            type="email"
            :label="t('provider.settings.email')"
            autocomplete="email"
            test-id="org-settings-email-input"
          />
        </div>
      </BaseCard>

      <BaseCard padding="lg">
        <h2
          class="text-lg font-semibold text-text mb-4"
          data-test-id="org-settings-policy-title"
        >
          {{ t('provider.settings.policyTitle') }}
        </h2>
        <BaseSelect
          v-model="policyModel"
          :options="policyOptions"
          test-id="org-settings-policy-select"
        />
      </BaseCard>

      <BaseCard padding="lg">
        <h2
          class="text-lg font-semibold text-text mb-3 flex items-center gap-2"
          data-test-id="org-settings-verification-title"
        >
          {{ t('provider.settings.verificationTitle') }}
          <BaseBadge variant="warning">
            {{ t('provider.settings.verificationBadge') }}
          </BaseBadge>
        </h2>
        <p class="text-sm text-text-subtle">
          {{ t('provider.settings.verificationHint') }}
        </p>
      </BaseCard>

      <div class="flex items-center justify-end">
        <BaseButton
          variant="primary"
          type="submit"
          :loading="isSubmitting"
          test-id="org-settings-save-btn"
        >
          {{ t('provider.settings.save') }}
        </BaseButton>
      </div>
    </form>

    <BaseCard
      padding="lg"
      class="border-danger/40"
      data-test-id="org-settings-danger-card"
    >
      <h2
        class="text-lg font-semibold text-danger mb-2 flex items-center gap-2"
        data-test-id="org-settings-danger-title"
      >
        <ShieldAlert class="h-5 w-5" aria-hidden="true" />
        {{ t('provider.settings.dangerTitle') }}
      </h2>
      <p class="text-sm text-text-subtle mb-4">
        {{ t('provider.settings.dangerDesc') }}
      </p>
      <BaseButton
        variant="danger"
        :disabled="!canArchive"
        test-id="org-settings-archive-btn"
        @click="archiveDialogOpen = true"
      >
        {{ t('provider.settings.dangerCta') }}
      </BaseButton>
    </BaseCard>

    <BaseDialog
      v-model="archiveDialogOpen"
      :title="t('provider.settings.dangerConfirmTitle')"
      :message="t('provider.settings.dangerConfirmMessage')"
      variant="danger"
      :confirm-label="t('provider.settings.dangerCta')"
      @confirm="onArchiveConfirm"
    />
  </section>
</template>
