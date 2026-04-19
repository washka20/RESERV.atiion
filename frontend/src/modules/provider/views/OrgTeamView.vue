<script setup lang="ts">
/**
 * OrgTeamView — команда organization: список + invite.
 *
 * Backend endpoints пока stub — при 404/network error fallback на
 * текущего user'а как одного-единственного member (из auth.store).
 * Invite вызывает membershipsApi.invite → stub → toast.
 */
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Users, Pencil, Trash2 } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseDataTable from '@/shared/components/base/BaseDataTable.vue'
import BaseDialog from '@/shared/components/base/BaseDialog.vue'
import BaseModal from '@/shared/components/base/BaseModal.vue'
import BaseInput from '@/shared/components/base/BaseInput.vue'
import BaseSelect from '@/shared/components/base/BaseSelect.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseBadge from '@/shared/components/base/BaseBadge.vue'
import * as membershipsApi from '@/api/memberships.api'
import type { OrgMember } from '@/api/memberships.api'
import type { MembershipRole } from '@/types/auth.types'
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

const currentRole = computed<MembershipRole | null>(
  () => authStore.activeMembership(orgSlug.value)?.role ?? null,
)

const isOwner = computed<boolean>(() => currentRole.value === 'owner')

const members = ref<OrgMember[]>([])
const isLoading = ref<boolean>(false)

const inviteModalOpen = ref<boolean>(false)
const removeDialogOpen = ref<boolean>(false)
const removeTarget = ref<OrgMember | null>(null)

const inviteForm = reactive({
  email: '',
  role: 'staff' as MembershipRole,
})

const roleOptions = computed(() => [
  { value: 'admin', label: t('provider.team.roleAdmin') },
  { value: 'staff', label: t('provider.team.roleStaff') },
  { value: 'viewer', label: t('provider.team.roleViewer') },
])

const inviteRoleModel = computed({
  get: () => inviteForm.role,
  set: (v: string | number) => {
    inviteForm.role = String(v) as MembershipRole
  },
})

function currentUserAsMember(): OrgMember | null {
  const user = authStore.user
  if (!user) return null
  const membership = authStore.activeMembership(orgSlug.value)
  if (!membership) return null
  return {
    id: membership.membershipId,
    userId: user.id,
    email: user.email,
    firstName: user.firstName,
    lastName: user.lastName,
    role: membership.role,
    acceptedAt: user.emailVerifiedAt,
    createdAt: user.createdAt,
  }
}

async function loadTeam(): Promise<void> {
  isLoading.value = true
  try {
    const envelope = await membershipsApi.list(orgSlug.value)
    if (envelope.success && envelope.data) {
      members.value = envelope.data
    } else {
      const me = currentUserAsMember()
      members.value = me ? [me] : []
    }
  } catch {
    const me = currentUserAsMember()
    members.value = me ? [me] : []
  } finally {
    isLoading.value = false
  }
}

const columns = computed(() => [
  { key: 'name', label: t('provider.team.colName') },
  { key: 'email', label: t('provider.team.colEmail') },
  { key: 'role', label: t('provider.team.colRole') },
  { key: 'joined', label: t('provider.team.colJoined') },
])

const rows = computed(() =>
  members.value.map((m) => ({
    id: m.id,
    name: [m.firstName, m.lastName].filter(Boolean).join(' ') || m.email,
    email: m.email,
    role: m.role,
    joined: m.acceptedAt
      ? new Date(m.acceptedAt).toLocaleDateString('ru-RU')
      : '—',
  })),
)

function roleLabel(role: MembershipRole): string {
  const map = {
    owner: t('provider.team.roleOwner'),
    admin: t('provider.team.roleAdmin'),
    staff: t('provider.team.roleStaff'),
    viewer: t('provider.team.roleViewer'),
  } as const
  return map[role]
}

async function onInviteSubmit(): Promise<void> {
  try {
    await membershipsApi.invite(orgSlug.value, {
      email: inviteForm.email,
      role: inviteForm.role,
    })
    toast.success(t('provider.team.inviteSuccess'))
    inviteModalOpen.value = false
    inviteForm.email = ''
    await loadTeam()
  } catch {
    toast.error(t('provider.team.inviteStubError'))
  }
}

function askRemove(row: { id: string }): void {
  const m = members.value.find((x) => x.id === row.id) ?? null
  removeTarget.value = m
  removeDialogOpen.value = true
}

function onRemoveConfirm(): void {
  toast.error(t('provider.team.inviteStubError'))
  removeDialogOpen.value = false
  removeTarget.value = null
}

onMounted(() => {
  void loadTeam()
})
</script>

<template>
  <section data-test-id="org-team-view" class="flex flex-col gap-4">
    <header class="flex items-center justify-between gap-3">
      <h1
        class="text-2xl font-bold tracking-tight text-text"
        data-test-id="org-team-title"
      >
        {{ t('provider.team.title') }}
      </h1>
      <BaseButton
        v-if="isOwner"
        variant="primary"
        test-id="org-team-invite-btn"
        @click="inviteModalOpen = true"
      >
        {{ t('provider.team.inviteCta') }}
      </BaseButton>
    </header>

    <BaseEmptyState
      v-if="!isLoading && members.length === 0"
      :title="t('provider.team.emptyTitle')"
      :description="t('provider.team.emptyDesc')"
      data-test-id="org-team-empty"
    >
      <template #icon>
        <Users class="h-10 w-10" aria-hidden="true" />
      </template>
    </BaseEmptyState>

    <BaseCard v-else padding="sm" class="overflow-hidden">
      <BaseDataTable
        :columns="columns"
        :rows="rows"
        :empty-message="t('provider.team.emptyTitle')"
        data-test-id="org-team-table"
      >
        <template #row-actions="{ row }">
          <div v-if="isOwner" class="inline-flex items-center gap-1">
            <BaseBadge variant="info">
              {{ roleLabel(row.role as MembershipRole) }}
            </BaseBadge>
            <button
              type="button"
              class="inline-flex h-8 items-center gap-1 rounded-sm px-2 text-sm text-text-subtle hover:bg-surface-muted hover:text-text"
              :data-test-id="`org-team-edit-btn-${row.id}`"
              @click="toast.info('Изменение ролей — Plan 15')"
            >
              <Pencil class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('provider.team.actionChangeRole') }}
            </button>
            <button
              type="button"
              class="inline-flex h-8 items-center gap-1 rounded-sm px-2 text-sm text-danger hover:bg-danger/10"
              :data-test-id="`org-team-remove-btn-${row.id}`"
              @click="askRemove(row as { id: string })"
            >
              <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('provider.team.actionRemove') }}
            </button>
          </div>
          <BaseBadge v-else variant="info">
            {{ roleLabel(row.role as MembershipRole) }}
          </BaseBadge>
        </template>
      </BaseDataTable>
    </BaseCard>

    <BaseModal
      v-model="inviteModalOpen"
      :title="t('provider.team.inviteModalTitle')"
      size="md"
    >
      <form
        class="flex flex-col gap-4"
        novalidate
        data-test-id="org-team-invite-form"
        @submit.prevent="onInviteSubmit"
      >
        <BaseInput
          v-model="inviteForm.email"
          type="email"
          :label="t('provider.team.inviteEmail')"
          required
          autocomplete="email"
          test-id="org-team-invite-email-input"
        />
        <BaseSelect
          v-model="inviteRoleModel"
          :options="roleOptions"
          :label="t('provider.team.inviteRole')"
          test-id="org-team-invite-role-select"
        />
      </form>
      <template #footer>
        <BaseButton
          variant="secondary"
          test-id="org-team-invite-cancel-btn"
          @click="inviteModalOpen = false"
        >
          {{ t('common.cancel') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          test-id="org-team-invite-submit-btn"
          @click="onInviteSubmit"
        >
          {{ t('provider.team.inviteSubmit') }}
        </BaseButton>
      </template>
    </BaseModal>

    <BaseDialog
      v-model="removeDialogOpen"
      :title="t('provider.team.removeConfirmTitle')"
      :message="t('provider.team.removeConfirmMessage')"
      variant="danger"
      :confirm-label="t('provider.team.actionRemove')"
      @confirm="onRemoveConfirm"
    />
  </section>
</template>
