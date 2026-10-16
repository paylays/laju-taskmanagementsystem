import { defineConfig } from '@playwright/test'
import { e2eEnv, API_PORT, WEB_PORT } from './tests/e2e/env.js'

// E2E runs against its own database (lajur_e2e) and API port, so your dev data is never touched.
export default defineConfig({
  testDir: './tests/e2e',
  globalSetup: './tests/e2e/global-setup.js',
  workers: 1,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  reporter: [['list']],
  use: {
    baseURL: `http://127.0.0.1:${WEB_PORT}`,
    viewport: { width: 1280, height: 800 },
    trace: 'retain-on-failure',
  },
  webServer: [
    {
      // php -S directly (not `artisan serve`) so the env overrides below reach Laravel.
      // Laravel's router script treats the working directory as the public folder.
      command: `php -S 127.0.0.1:${API_PORT} ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
      cwd: '../backend/public',
      url: `http://127.0.0.1:${API_PORT}/up`,
      env: e2eEnv,
      stderr: 'ignore', // php -S logs every connection to stderr
      reuseExistingServer: false,
      timeout: 60_000,
    },
    {
      command: `node node_modules/vite/bin/vite.js --port ${WEB_PORT} --strictPort --host 127.0.0.1`,
      url: `http://127.0.0.1:${WEB_PORT}`,
      env: { LAJUR_API_URL: `http://127.0.0.1:${API_PORT}` },
      reuseExistingServer: false,
      timeout: 60_000,
    },
  ],
})
