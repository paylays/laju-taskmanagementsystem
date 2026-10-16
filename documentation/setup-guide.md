# Setup guide

Local development on one machine.

## Requirements

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.2+ | Extensions: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `openssl` |
| Composer | 2.x | |
| MySQL | 8.0 | MariaDB 10.6+ untested |
| Node.js | 20+ | Tested on 24 |

On Windows, [Laragon](https://laragon.org) provides PHP, MySQL, and Composer. This project was built on it.

## 1. Databases

```sql
CREATE DATABASE lajur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;       -- app
CREATE DATABASE lajur_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;  -- backend tests
```

The E2E suite creates `lajur_e2e` itself.

## 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php -r "echo 'JWT_SECRET='.bin2hex(random_bytes(32)).PHP_EOL;"   # paste the line into .env
php artisan migrate --seed       # or: mysql -u root lajur < database/dump.sql
```

Change `DB_USERNAME` and `DB_PASSWORD` in `.env` if MySQL is not `root` without a password.

Run in two terminals:

```bash
php artisan serve        # API on http://127.0.0.1:8000
php artisan queue:work   # emails, virus scan, thumbnails, bulk updates, exports
```

Assignment emails are written to `storage/logs/laravel.log`.

## 3. Frontend

```bash
cd frontend
npm install
npm run dev              # http://127.0.0.1:5173
```

Vite proxies `/api` to port 8000. For another address, set `LAJUR_API_URL` before `npm run dev`.

## 4. Sign in

Open http://127.0.0.1:5173 as `admin@lajur.test` / `password`. Members: `rina`, `bagus`, `dewi`, `fajar`, `sari` (`@lajur.test`, same password). Use a second browser profile to see live updates.

To see a blocked upload, upload a text file containing `LAJUR-SIMULATED-VIRUS`.

## Tests

```bash
cd backend  && php artisan test            # 61 tests, database lajur_test
cd frontend && npm test                    # 40 tests
cd frontend && npx playwright install chromium && npm run test:e2e   # 6 tests; install once
```

E2E starts its own API (port 8010, database `lajur_e2e`, synchronous queue, files in `backend/storage/app/e2e`) and Vite (port 5180), so dev data is untouched. Creating `lajur_e2e` uses `root` without a password unless `DB_USERNAME` and `DB_PASSWORD` are set in your shell.

## Configuration (`backend/.env`)

| Variable | Default | Meaning |
|---|---|---|
| `CACHE_STORE` | `database` | API response cache. `redis` also works (Laragon ships `redis-server` in `C:\laragon\bin\redis`). |
| `REDIS_CLIENT` | `predis` | Pure-PHP client; no PHP extension needed |
| `JWT_SECRET` | `APP_KEY` | Token signing key |
| `JWT_TTL` | `120` | Token lifetime, minutes |
| `SSE_HOLD_SECONDS` | `0` | Stream connection length. Keep 0 with `php artisan serve`. |
| `SSE_RETRY_MS` | `2000` | Browser reconnect delay |
| `ATTACHMENT_DISK` | `local` | Disk for uploads and exports |
| `ATTACHMENT_SCAN_DELAY_MS` | `1500` | Simulated scan delay, so the pending state is visible |
| `ATTACHMENT_SCAN_TEST_MARKER` | `LAJUR-SIMULATED-VIRUS` | Text treated as a virus |
| `LOCAL_DISK_ROOT` | `storage/app/private` | Root of the `local` disk |

## Troubleshooting

| Symptom | Fix |
|---|---|
| "Cannot reach the server" | Start `php artisan serve`, or check `LAJUR_API_URL`. |
| Files stay "scanning for viruses", or the export waits in the queue | Start `php artisan queue:work`. |
| "Live updates paused, reconnecting" | The API is down or restarting; the board reconnects by itself. |
| An EICAR file never uploads | Windows Defender removed it. Use the marker text. |
| Old tasks show for up to a minute after re-seeding with Redis | Run `php artisan cache:clear`. |
| `could not find driver` in tests | `.env` or `phpunit.xml` still points at SQLite; set `mysql`. |
