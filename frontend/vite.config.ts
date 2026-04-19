import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // Vue DevTools floating panel — включается только при VITE_VUE_DEVTOOLS=1.
    // По-умолчанию отключён: overlay в середине низа перекрывал mobile BottomNav.
    ...(process.env.VITE_VUE_DEVTOOLS === '1' ? [vueDevTools()] : []),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
})
