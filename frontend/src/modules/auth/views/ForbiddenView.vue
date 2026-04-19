<script setup lang="ts">
/**
 * Placeholder view для /forbidden — показывается если user не имеет
 * org-permission для запрошенного route.
 */
</script>

<template>
  <main class="forbidden-view">
    <h1 data-test-id="auth-forbidden-title">Доступ запрещён</h1>
    <p>У вас недостаточно прав для просмотра этой страницы.</p>
  </main>
</template>
