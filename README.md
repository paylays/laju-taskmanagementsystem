# Lajur

A task board for small teams: three lanes (To do, In progress, Done), file attachments with a virus scan, comments, and changes that show up for everyone without a reload. Built for the Full-Stack Developer technical assessment, covering Part 1 (PHP backend), Part 2 (frontend) and Part 4 (documentation and tests).

*Lajur* is Indonesian for "lane".

| | |
|---|---|
| Backend | Laravel 12, PHP 8.2, MySQL 8, JWT (`firebase/php-jwt`), database queue |
| Frontend | Vue 3, Vite, Vue Router, Axios |
| Real-time | Server-Sent Events from a `task_events` table |
| Caching | Laravel cache (database by default, Redis in production) plus ETag / 304 |
| Tests | PHPUnit (61), Vitest (37), Playwright (6) |

## What it does

- Sign in with email and password; the API issues a JWT, and logout revokes it.
- Create, edit, and delete tasks with a status, priority, assignee, and due date. Filter by text, assignee and priority; sort five ways; page through each lane.
- Select several tasks and move them to another status in one go (runs as a background job).
- Attach images, documents, and videos by drag and drop or a file picker, with upload progress. Each file is type-checked by its contents, scanned (simulated antivirus), and thumbnailed if it is an image, before it can be downloaded.
- Comment on tasks. Comments, task changes, and attachment scan results reach other signed-in users live.
- Assignees get an email (queued). Export the current view to CSV (queued).
- Works on phones: the lanes turn into tabs below 900px wide.

## Quick start

Needs PHP 8.2+, Composer, MySQL 8, and Node 20+. Full instructions are in [documentation/setup-guide.md](documentation/setup-guide.md).

```bash
mysql -u root -e "CREATE DATABASE lajur; CREATE DATABASE lajur_test;"

cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve                 # terminal 1
php artisan queue:work            # terminal 2

cd ../frontend
npm install
npm run dev                       # terminal 3, open http://127.0.0.1:5173
```

Sign in as `admin@lajur.test` / `password`. Five member accounts are seeded too (`rina`, `bagus`, `dewi`, `fajar`, `sari` @lajur.test, same password).

## Tests

```bash
cd backend  && php artisan test         # API, jobs, uploads, SSE, database
cd frontend && npm test                 # components, store, API client
cd frontend && npm run test:e2e         # browser flows against a separate database
```

## Repository layout

Laravel keeps application code in `backend/app` and settings in `backend/config`, so `app/` plays the role of the brief's `backend/src/`.

```
backend/                 Laravel API
  app/Http/Controllers   one controller per resource, plus the SSE stream
  app/Jobs               the four queued jobs
  app/Services           JWT, uploads, virus scanner, thumbnails
  app/Observers          write the real-time event feed, queue assignment emails
  database/              migrations, seeder, schema.sql, dump.sql
  tests/                 Unit and Feature
frontend/                Vue SPA
  src/views              sign-in page and board
  src/components         task row, panel, uploads, comments, filters, toasts
  tests/unit             Vitest
  tests/e2e              Playwright
documentation/
  requirements-checklist.md  the brief, item by item, with where each one lives
  demo-script.md         steps and subtitles for the demo recording
  api-docs/openapi.yaml  every endpoint, request and response
  architecture.md        why each piece is built the way it is
  database-schema.md     tables, indexes, relationships
  setup-guide.md         local setup, configuration, troubleshootingDESIGN.md                visual direction and the reason behind each UI decision
```

## Scope

Built: everything in Parts 1, 2 and 4 of the brief, plus the "advanced" frontend features (filtering and search, real-time comments, upload progress, toasts), the virus scan simulation, and the Part 3.3 performance work (response caching, ETags, a measured index, progressive thumbnails, code splitting).

Not built: chunked uploads over 50 MB, file versioning, PDF export, and bonus challenges 3.1 (video streaming) and 3.2 (WebSockets, presence, typing indicators). [documentation/requirements-checklist.md](documentation/requirements-checklist.md) goes through the brief line by line.