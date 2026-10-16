import path from 'node:path'
import { fileURLToPath } from 'node:url'

export const API_PORT = 8010
export const WEB_PORT = 5180

const backendDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../backend')

// Real environment variables win over backend/.env, so these isolate the E2E run.
export const e2eEnv = {
  APP_URL: `http://127.0.0.1:${API_PORT}`,
  DB_DATABASE: process.env.E2E_DB_DATABASE ?? 'lajur_e2e',
  QUEUE_CONNECTION: 'sync',
  MAIL_MAILER: 'array',
  ATTACHMENT_SCAN_DELAY_MS: '0',
  SSE_HOLD_SECONDS: '0',
  SSE_RETRY_MS: '1000',
  LOCAL_DISK_ROOT: path.join(backendDir, 'storage', 'app', 'e2e'),
}

export { backendDir }
