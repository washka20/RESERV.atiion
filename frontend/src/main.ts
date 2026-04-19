import './shared/styles/base.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { i18n } from './shared/i18n'
import { useAuthStore } from './stores/auth.store'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)

/**
 * Гидратация auth-состояния до mount: читает токены из localStorage и
 * подгружает /auth/me + /me/memberships. Ошибки/невалидные токены —
 * молча чистятся; router guards отработают редирект на /login где нужно.
 */
useAuthStore()
  .hydrate()
  .catch(() => {
    // hydrate сам ресетит state при ошибках; глушим unhandled rejection
  })
  .finally(() => {
    app.mount('#app')
  })
