import process from 'node:process'
import { defineConfig, devices } from '@playwright/test'

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './e2e',
  /* Maximum time one test can run for. */
  timeout: 30 * 1000,
  expect: {
    /**
     * Maximum time expect() should wait for the condition to be met.
     * For example in `await expect(locator).toHaveText();`
     */
    timeout: 5000,
  },
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /**
   * Retry на CI: 1 достаточно для устранения случайных сетевых дёрганий под
   * нагрузкой. 2 — overkill, worst-case gives 3x-time при flaky.
   */
  retries: process.env.CI ? 1 : 0,
  /**
   * 2 workers на CI — sweet spot для Vite preview (single-threaded, начинает
   * захлёбываться при 4+ одновременных клиентах). Тесты изолированы:
   * test.beforeEach создаёт новый browser context, HAR-моки per-test.
   */
  workers: process.env.CI ? 2 : undefined,
  /**
   * line reporter даёт быстрый progress feedback в stdout GH Actions,
   * html сохраняется для upload-artifact при failure (open: 'never' чтобы
   * не пытался запустить браузер на раннере).
   */
  reporter: process.env.CI
    ? [['line'], ['html', { open: 'never' }]]
    : 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Maximum time each action such as `click()` can take. Defaults to 0 (no limit). */
    actionTimeout: 0,
    /**
     * Base URL.
     *
     * Local: nginx (`make up`) на :8080 — обслуживает фронт + проксирует /api → php.
     * CI: preview build на :4173 (потребует proxy в будущем, пока CI не цель).
     */
    baseURL: process.env.CI ? 'http://localhost:4173' : 'http://localhost:8080',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',

    /* Only on CI systems run the tests headless */
    headless: !!process.env.CI,

    /**
     * Проект использует kebab-case `data-test-id` (см. .claude/rules/testing.md
     * и vue.md). Дефолтное значение Playwright — `data-testid`.
     */
    testIdAttribute: 'data-test-id',
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
      },
    },
    {
      name: 'firefox',
      use: {
        ...devices['Desktop Firefox'],
      },
    },
    {
      name: 'webkit',
      use: {
        ...devices['Desktop Safari'],
      },
    },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: {
    //     ...devices['Pixel 5'],
    //   },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: {
    //     ...devices['iPhone 12'],
    //   },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: {
    //     channel: 'msedge',
    //   },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: {
    //     channel: 'chrome',
    //   },
    // },
  ],

  /* Folder for test artifacts such as screenshots, videos, traces, etc. */
  // outputDir: 'test-results/',

  /**
   * Локально стек поднимается через `make up` (nginx на :8080). Playwright не
   * запускает webServer самостоятельно. На CI используется preview build.
   */
  webServer: process.env.CI
    ? {
        command: 'npm run preview',
        port: 4173,
        reuseExistingServer: false,
      }
    : undefined,
})
