<script setup lang="ts">
/**
 * PersonalProfileView — личные данные пользователя в табе "Профиль" dashboard.
 *
 * Read-only форма + submit stub: PUT /auth/me пока не реализован.
 * Кнопка "Сохранить" эмитит toast "Ещё не реализовано".
 *
 * Блок "Сменить пароль" тоже stub — backend endpoint /auth/change-password
 * появится позже. Секция отображается для UX-демонстрации future flow.
 */
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseAvatar from '@/shared/components/base/BaseAvatar.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const authStore = useAuthStore()
const { toast } = useToast()

const firstName = ref<string>('')
const lastName = ref<string>('')
const middleName = ref<string>('')

const passwordCurrent = ref<string>('')
const passwordNew = ref<string>('')
const passwordConfirm = ref<string>('')

const isSaving = ref<boolean>(false)
const isChangingPassword = ref<boolean>(false)

const userEmail = computed<string>(() => authStore.user?.email ?? '')
const fullName = computed<string>(() => {
  const parts = [authStore.user?.firstName, authStore.user?.lastName].filter(Boolean)
  return parts.join(' ') || userEmail.value
})

watch(
  () => authStore.user,
  (u) => {
    if (!u) return
    firstName.value = u.firstName ?? ''
    lastName.value = u.lastName ?? ''
    middleName.value = u.middleName ?? ''
  },
  { immediate: true },
)

async function handleSaveProfile(): Promise<void> {
  if (isSaving.value) return
  isSaving.value = true
  try {
    // stub: PUT /auth/me появится позже
    toast.error(t('profile.saveStub'))
  } finally {
    isSaving.value = false
  }
}

async function handleChangePassword(): Promise<void> {
  if (isChangingPassword.value) return
  isChangingPassword.value = true
  try {
    // stub: POST /auth/change-password появится позже
    toast.error(t('profile.passwordStub'))
  } finally {
    isChangingPassword.value = false
  }
}
</script>

<template>
  <section
    class="flex flex-col gap-6"
    data-test-id="dashboard-tab-profile"
  >
    <BaseCard padding="lg">
      <header class="mb-4 flex items-center gap-4">
        <BaseAvatar :alt="fullName" size="lg" />
        <div class="flex-1">
          <h2 class="text-lg font-semibold text-text">
            {{ t('profile.personalTitle') }}
          </h2>
          <p class="mt-1 text-sm text-text-subtle">
            {{ t('profile.personalSubtitle') }}
          </p>
        </div>
        <BaseButton
          variant="ghost"
          size="sm"
          disabled
          test-id="profile-change-photo-btn"
        >
          {{ t('profile.changePhoto') }}
        </BaseButton>
      </header>

      <form
        class="flex flex-col gap-4"
        novalidate
        data-test-id="profile-personal-form"
        @submit.prevent="handleSaveProfile"
      >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput
            v-model="firstName"
            :label="t('profile.firstName')"
            autocomplete="given-name"
            test-id="profile-first-name-input"
          />
          <BaseInput
            v-model="lastName"
            :label="t('profile.lastName')"
            autocomplete="family-name"
            test-id="profile-last-name-input"
          />
        </div>
        <BaseInput
          v-model="middleName"
          :label="t('profile.middleName')"
          autocomplete="additional-name"
          test-id="profile-middle-name-input"
        />
        <BaseInput
          :model-value="userEmail"
          :label="t('profile.email')"
          type="email"
          readonly
          test-id="profile-email-input"
        />

        <div class="flex items-center justify-end">
          <BaseButton
            type="submit"
            variant="primary"
            :loading="isSaving"
            test-id="profile-save-btn"
          >
            {{ t('profile.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <BaseCard padding="lg">
      <header class="mb-4">
        <h2 class="text-lg font-semibold text-text">
          {{ t('profile.passwordTitle') }}
        </h2>
      </header>

      <form
        class="flex flex-col gap-4"
        novalidate
        data-test-id="profile-password-form"
        @submit.prevent="handleChangePassword"
      >
        <BaseInput
          v-model="passwordCurrent"
          type="password"
          :label="t('profile.passwordCurrent')"
          autocomplete="current-password"
          test-id="profile-password-current-input"
        />
        <BaseInput
          v-model="passwordNew"
          type="password"
          :label="t('profile.passwordNew')"
          autocomplete="new-password"
          test-id="profile-password-new-input"
        />
        <BaseInput
          v-model="passwordConfirm"
          type="password"
          :label="t('profile.passwordConfirm')"
          autocomplete="new-password"
          test-id="profile-password-confirm-input"
        />

        <div class="flex items-center justify-end">
          <BaseButton
            type="submit"
            variant="primary"
            :loading="isChangingPassword"
            test-id="profile-password-save-btn"
          >
            {{ t('profile.passwordSave') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </section>
</template>
