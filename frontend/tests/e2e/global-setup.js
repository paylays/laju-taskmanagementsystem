import { execFileSync } from 'node:child_process'
import fs from 'node:fs'
import { backendDir, e2eEnv } from './env.js'

// Fresh database and empty file storage before every E2E run.
export default function globalSetup() {
  const env = { ...process.env, ...e2eEnv }
  const run = (args) => execFileSync('php', args, { cwd: backendDir, env, stdio: 'inherit' })

  run(['-r', `
    $pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306'),
      getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS \`' . getenv('DB_DATABASE') . '\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  `])
  run(['artisan', 'migrate:fresh', '--seed', '--force'])

  fs.rmSync(e2eEnv.LOCAL_DISK_ROOT, { recursive: true, force: true })
}
