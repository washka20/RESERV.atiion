import { describe, it, expect, beforeEach } from 'vitest'
import { nextTick } from 'vue'
import { useTheme } from '../useTheme'

describe('useTheme', () => {
    beforeEach(() => {
        localStorage.clear()
        document.documentElement.classList.remove('dark')
    })

    it('setTheme dark → html has dark class', async () => {
        const { setTheme, isDark } = useTheme()
        setTheme('dark')
        await nextTick()
        expect(isDark.value).toBe(true)
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })

    it('setTheme light → dark class removed', async () => {
        const { setTheme, isDark } = useTheme()
        setTheme('dark')
        await nextTick()
        setTheme('light')
        await nextTick()
        expect(isDark.value).toBe(false)
        expect(document.documentElement.classList.contains('dark')).toBe(false)
    })

    it('toggle flips theme', async () => {
        const { setTheme, toggle, theme } = useTheme()
        setTheme('light')
        await nextTick()
        toggle()
        expect(theme.value).toBe('dark')
    })
})
