# Lajur backend

Laravel 12 JSON API. Setup is in [../documentation/setup-guide.md](../documentation/setup-guide.md); endpoints are in [../documentation/api-docs/openapi.yaml](../documentation/api-docs/openapi.yaml).

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work
php artisan test
```

## Where things are

| Path | Contents |
|---|---|
| `routes/api.php` | All routes. Everything except login sits behind `auth:api`. |
| `app/Providers/AppServiceProvider.php` | The `jwt` guard, service bindings, model observers |
| `app/Services/JwtService.php` | Issue, verify, and revoke tokens |
| `app/Services/AttachmentService.php` | Upload validation (content-sniffed type, per-type size) and storage |
| `app/Services/VirusScanner.php` | Simulated scanner (EICAR + demo marker) |
| `app/Services/ThumbnailGenerator.php` | GD thumbnails (progressive JPEG) |
| `app/Services/ApiCache.php` | Versioned response cache for `GET /tasks` and `GET /users` |
| `app/Http/Middleware/ConditionalJsonResponse.php` | ETag and 304 for JSON GET responses |
| `app/Jobs/` | `SendTaskAssignedNotification`, `BulkUpdateTaskStatus`, `ProcessAttachment`, `ExportTasks` |
| `app/Observers/` | Record `task_events` for the SSE feed; queue assignment emails |
| `app/Http/Controllers/StreamController.php` | `GET /api/stream` (Server-Sent Events) |
| `app/Policies/` | Who may change or delete tasks and attachments |
| `config/attachments.php`, `config/jwt.php`, `config/realtime.php` | App settings, all overridable from `.env` |
| `database/schema.sql` | Schema without data, generated from the migrations |
| `database/dump.sql` | Schema plus seed data |

## Tests

`php artisan test` runs 61 tests against the MySQL database `lajur_test` (set in `phpunit.xml`). The queue is synchronous in tests, so jobs run inline, and the simulated scan delay is 0.

| File | Covers |
|---|---|
| `tests/Unit/JwtServiceTest.php` | Valid, expired, forged, revoked, and malformed tokens |
| `tests/Unit/VirusScannerTest.php` | Signatures, including one split across read chunks |
| `tests/Unit/TaskPolicyTest.php` | Who can update and delete |
| `tests/Feature/AuthTest.php` | Login, me, logout revocation |
| `tests/Feature/TaskApiTest.php` | CRUD, pagination, every filter, sort by priority weight, permissions, bulk update |
| `tests/Feature/AttachmentTest.php` | Upload, content sniffing, size limits, scan, thumbnails, download, delete |
| `tests/Feature/CommentAndStreamTest.php` | Comments and the SSE feed |
| `tests/Feature/JobsTest.php` | Email, bulk update, CSV export (including formula escaping) |
| `tests/Feature/DatabaseTest.php` | Seeder counts, cascades, required indexes |
| `tests/Feature/PerformanceTest.php` | Cached task list (no second query), cache invalidation, ETag / 304, lane index |
