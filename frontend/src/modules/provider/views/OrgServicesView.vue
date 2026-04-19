<script setup lang="ts">
/**
 * OrgServicesView — список услуг organization с action-ми CRUD.
 *
 * Backend endpoint `/organizations/{slug}/services` пока stub —
 * при 404 показывается empty state, при network error — toast.
 * Архивирование также не реализовано → confirm + toast со stub сообщением.
 */
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Package, Pencil, Archive } from 'lucide-vue-next'
import BaseButton from '@/shared/components/base/BaseButton.vue'
import BaseDataTable from '@/shared/components/base/BaseDataTable.vue'
import BaseEmptyState from '@/shared/components/base/BaseEmptyState.vue'
import BaseDialog from '@/shared/components/base/BaseDialog.vue'
import * as servicesApi from '@/api/services.api'
import type { ServiceListItem } from '@/types/catalog.types'
import { useToast } from '@/shared/composables/useToast'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { toast } = useToast()

const orgSlug = computed<string>(() => {
  const slug = route.params.slug
  return typeof slug === 'string' ? slug : ''
})

const services = ref<ServiceListItem[]>([])
const isLoading = ref<boolean>(false)
const archiveDialogOpen = ref<boolean>(false)
const targetService = ref<ServiceListItem | null>(null)

interface TableRow extends Record<string, unknown> {
  id: string
  name: string
  category: string
  type: 'time_slot' | 'quantity'
  price: string
  isActive: boolean
}

const columns = computed(() => [
  { key: 'name', label: t('provider.services.colName') },
  { key: 'category', label: t('provider.services.colCategory') },
  {
    key: 'type',
    label: t('provider.services.colType'),
    render: (row: Record<string, unknown>) =>
      row.type === 'time_slot'
        ? t('provider.services.typeTimeSlot')
        : t('provider.services.typeQuantity'),
  },
  { key: 'price', label: t('provider.services.colPrice'), align: 'right' as const },
  { key: 'status', label: t('provider.services.colStatus') },
])

const rows = computed<TableRow[]>(() =>
  services.value.map((svc) => ({
    id: svc.id,
    name: svc.name,
    category: svc.categoryName,
    type: svc.type,
    price: `${new Intl.NumberFormat('ru-RU').format(svc.priceAmount / 100)} ${svc.priceCurrency}`,
    isActive: svc.isActive,
    status: svc.isActive
      ? t('provider.services.statusActive')
      : t('provider.services.statusArchived'),
  })),
)

async function loadServices(): Promise<void> {
  isLoading.value = true
  try {
    const envelope = await servicesApi.list(orgSlug.value)
    services.value = envelope.success && envelope.data ? envelope.data : []
  } catch {
    services.value = []
  } finally {
    isLoading.value = false
  }
}

function onCreate(): void {
  void router.push(`/o/${orgSlug.value}/services/new`)
}

function onEdit(row: TableRow): void {
  void router.push(`/o/${orgSlug.value}/services/${row.id}/edit`)
}

function askArchive(row: TableRow): void {
  const target = services.value.find((s) => s.id === row.id) ?? null
  targetService.value = target
  archiveDialogOpen.value = true
}

function onArchiveConfirm(): void {
  toast.error(t('provider.services.archiveStubError'))
  archiveDialogOpen.value = false
  targetService.value = null
}

onMounted(() => {
  void loadServices()
})
</script>

<template>
  <section data-test-id="org-services-view" class="flex flex-col gap-4">
    <header class="flex items-center justify-between gap-3">
      <h1
        class="text-2xl font-bold tracking-tight text-text"
        data-test-id="org-services-title"
      >
        {{ t('provider.services.title') }}
      </h1>
      <BaseButton
        variant="primary"
        test-id="org-services-add-btn"
        @click="onCreate"
      >
        {{ t('provider.services.addCta') }}
      </BaseButton>
    </header>

    <BaseEmptyState
      v-if="!isLoading && services.length === 0"
      :title="t('provider.services.emptyTitle')"
      :description="t('provider.services.emptyDesc')"
      data-test-id="org-services-empty"
    >
      <template #icon>
        <Package class="h-10 w-10" aria-hidden="true" />
      </template>
      <template #action>
        <BaseButton
          variant="primary"
          test-id="org-services-empty-cta-btn"
          @click="onCreate"
        >
          {{ t('provider.services.emptyCta') }}
        </BaseButton>
      </template>
    </BaseEmptyState>

    <BaseDataTable
      v-else
      :columns="columns"
      :rows="rows"
      :empty-message="t('provider.services.emptyTitle')"
      data-test-id="org-services-table"
    >
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button
            type="button"
            class="inline-flex h-8 items-center gap-1 rounded-sm px-2 text-sm text-text-subtle hover:bg-surface-muted hover:text-text"
            :data-test-id="`org-services-edit-btn-${row.id}`"
            @click="onEdit(row as TableRow)"
          >
            <Pencil class="h-3.5 w-3.5" aria-hidden="true" />
            {{ t('provider.services.actionEdit') }}
          </button>
          <button
            type="button"
            class="inline-flex h-8 items-center gap-1 rounded-sm px-2 text-sm text-danger hover:bg-danger/10"
            :data-test-id="`org-services-archive-btn-${row.id}`"
            @click="askArchive(row as TableRow)"
          >
            <Archive class="h-3.5 w-3.5" aria-hidden="true" />
            {{ t('provider.services.actionArchive') }}
          </button>
        </div>
      </template>
    </BaseDataTable>

    <BaseDialog
      v-model="archiveDialogOpen"
      :title="t('provider.services.archiveConfirmTitle')"
      :message="t('provider.services.archiveConfirmMessage')"
      variant="danger"
      :confirm-label="t('provider.services.actionArchive')"
      @confirm="onArchiveConfirm"
    />
  </section>
</template>
