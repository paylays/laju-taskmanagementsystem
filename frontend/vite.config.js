import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// The dev server proxies /api to Laravel, so the browser sees one origin and no CORS setup is needed.
const apiTarget = process.env.LAJUR_API_URL ?? 'http://127.0.0.1:8000'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: Number(process.env.LAJUR_WEB_PORT ?? 5173),
    proxy: {
      '/api': { target: apiTarget, changeOrigin: true },
    },
  },
  test: {
    environment: 'jsdom',
    include: ['tests/unit/**/*.spec.js'],
  },
})
