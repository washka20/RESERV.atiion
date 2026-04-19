<script setup lang="ts">
/**
 * RegisterRoleView — шаг 1 регистрации: выбор роли (customer / provider).
 *
 * Ведёт на:
 *  - /register/customer (RegisterCustomerView);
 *  - /register/provider (RegisterProviderView).
 *
 * Без state-а: просто navigation cards.
 */
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { User as UserIcon, Building2, ArrowRight, Check } from 'lucide-vue-next'
import BaseCard from '@/shared/components/base/BaseCard.vue'
import BaseButton from '@/shared/components/base/BaseButton.vue'

const { t } = useI18n()
const router = useRouter()

interface RoleBenefit {
  key: string
}

const customerBenefits: RoleBenefit[] = [
  { key: 'Каталог 1000+ услуг' },
  { key: 'Напоминания за 2 часа' },
  { key: 'Гибкая отмена' },
]

const providerBenefits: RoleBenefit[] = [
  { key: 'Услуги в каталоге' },
  { key: 'Календарь и inbox' },
  { key: 'Еженедельные выплаты' },
]

function goCustomer(): void {
  void router.push('/register/customer')
}

function goProvider(): void {
  void router.push('/register/provider')
}
</script>

<template>
  <section class="flex min-h-[calc(100vh-theme(spacing.16))] items-center justify-center px-4 py-10">
    <div class="w-full max-w-3xl">
      <header class="mb-8 text-center">
        <h1
          class="text-3xl font-bold tracking-tight text-text"
          data-test-id="auth-register-role-title"
        >
          {{ t('auth.register.roleTitle') }}
        </h1>
        <p class="mt-2 text-sm text-text-subtle">
          {{ t('auth.register.roleSubtitle') }}
        </p>
      </header>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <BaseCard
          padding="lg"
          elevation="sm"
          class="flex flex-col gap-3 transition-transform hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex h-11 w-11 items-center justify-center rounded-md bg-surface-muted text-text-subtle">
            <UserIcon class="h-5 w-5" aria-hidden="true" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-text">{{ t('auth.register.customerTitle') }}</h2>
            <p class="mt-1 text-sm text-text-subtle">{{ t('auth.register.customerDesc') }}</p>
          </div>
          <ul class="flex flex-col gap-1.5 text-sm text-text-subtle">
            <li
              v-for="(b, i) in customerBenefits"
              :key="`c-${i}`"
              class="flex items-start gap-2"
            >
              <Check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-accent" aria-hidden="true" />
              {{ b.key }}
            </li>
          </ul>
          <BaseButton
            variant="secondary"
            full-width
            class="mt-auto"
            test-id="auth-register-role-customer-btn"
            @click="goCustomer"
          >
            {{ t('auth.register.customerCta') }}
            <template #icon-right>
              <ArrowRight class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseButton>
        </BaseCard>

        <BaseCard
          padding="lg"
          elevation="sm"
          class="flex flex-col gap-3 border-accent bg-accent/5 transition-transform hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex h-11 w-11 items-center justify-center rounded-md bg-accent text-white">
            <Building2 class="h-5 w-5" aria-hidden="true" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-text">{{ t('auth.register.providerTitle') }}</h2>
            <p class="mt-1 text-sm text-text-subtle">{{ t('auth.register.providerDesc') }}</p>
          </div>
          <ul class="flex flex-col gap-1.5 text-sm text-text-subtle">
            <li
              v-for="(b, i) in providerBenefits"
              :key="`p-${i}`"
              class="flex items-start gap-2"
            >
              <Check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-accent" aria-hidden="true" />
              {{ b.key }}
            </li>
          </ul>
          <BaseButton
            variant="primary"
            full-width
            class="mt-auto"
            test-id="auth-register-role-provider-btn"
            @click="goProvider"
          >
            {{ t('auth.register.providerCta') }}
            <template #icon-right>
              <ArrowRight class="h-4 w-4" aria-hidden="true" />
            </template>
          </BaseButton>
        </BaseCard>
      </div>

      <p class="mt-6 text-center text-sm text-text-subtle">
        {{ t('auth.register.alreadyHaveAccount') }}
        <RouterLink
          to="/login"
          class="ml-1 font-semibold text-text hover:text-accent"
          data-test-id="auth-register-role-login-link"
        >
          {{ t('auth.register.signIn') }}
        </RouterLink>
      </p>
    </div>
  </section>
</template>
