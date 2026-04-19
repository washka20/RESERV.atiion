import { ref, watchEffect, computed } from 'vue'

const STORAGE_KEY = 'reserv:theme'
type Theme = 'light' | 'dark' | 'auto'

const stored = (typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null) as Theme | null
const theme = ref<Theme>(stored ?? 'auto')

const mql = typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia('(prefers-color-scheme: dark)')
    : null

const prefersDark = ref(mql?.matches ?? false)

mql?.addEventListener?.('change', (e) => {
    prefersDark.value = e.matches
})

const isDark = computed(() => (theme.value === 'auto' ? prefersDark.value : theme.value === 'dark'))

watchEffect(() => {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', isDark.value)
    }
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, theme.value)
    }
})

/**
 * Composable для управления темой. theme принимает light/dark/auto
 * (auto = prefers-color-scheme). isDark — computed с учётом системной темы.
 */
export function useTheme() {
    return {
        theme,
        isDark,
        setTheme(t: Theme) { theme.value = t },
        toggle() { theme.value = isDark.value ? 'light' : 'dark' },
    }
}
