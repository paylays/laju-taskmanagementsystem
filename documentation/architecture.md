# Architecture decisions

Laravel 12 JSON API plus a Vue 3 single-page app. The owner asked for the lightest stack that is easy to run, so each choice below keeps the number of moving parts low.

```
 BROWSER                         SERVER
+------------+   REST + JWT     +-------------+  queries, jobs  +---------+
| Vue 3 SPA  | ---------------> | Laravel API | --------------> |  MySQL  |
|            | <--------------- |             |                 +---------+
+------------+  SSE /api/stream +-------------+                      ^
                                       |                             | jobs table
                                       | files                +-------------+
                                       v                      | queue:work  | --> Mailer
                                +--------------+    files     +-------------+
                                | Private disk | <-----------------+
                                +--------------+
```

## Backend

| Decision | Reason |
|---|---|
| Laravel, not vanilla PHP | Routing, validation, policies, queues, mail, and testing are built in. |
| JWT with `firebase/php-jwt` and a small guard (`Auth::viaRequest`) | Under 100 lines with no hidden behaviour, so no `jwt-auth` package. |
| Policies for authorization | One place for the rules; the SPA only mirrors them to hide controls that would fail. |
| Database queue | No Redis or broker to install. |
| SSE over a `task_events` table | Real-time without a WebSocket server or Redis pub/sub. |

### Authentication

- `JwtService` issues HS256 tokens with `sub`, `jti`, `role`, `iat`, `exp`, `iss`.
- Logout stores the `jti` in `revoked_tokens`; each request checks it by primary key.
- Tokens last `JWT_TTL` minutes (120). There is no refresh token: a 401 sends the user back to sign in.
- `JWT_SECRET` falls back to `APP_KEY` so a fresh clone works. Set a separate secret in production.

### Authorization

Every signed-in user can read every task (small-team board). Admins, the creator, and the assignee may edit a task. Only admins and the creator may delete it.

### File uploads

- The MIME type is read from the file bytes (`finfo`). A renamed `.exe` is rejected.
- Size limits per category (image 10 MB, document 20 MB, video 50 MB) live in `config/attachments.php`.
- Files go to the private disk as `attachments/{task_id}/{uuid}.{ext}`, with the extension taken from the detected type. The original name is used only in `Content-Disposition`.
- Public disk serving is off. Downloads go through an authenticated endpoint that sends `nosniff`, and are refused while the scan is `pending` (409) or the file is `infected` (410).

### Background jobs

| Job | Trigger | What it does |
|---|---|---|
| `SendTaskAssignedNotification` | Task created with, or changed to, an assignee other than the actor | Sends the email. Skips if the task was deleted or reassigned meanwhile. |
| `BulkUpdateTaskStatus` | `POST /tasks/bulk-status` | Saves tasks one by one so each emits a live event. Ids are filtered by policy before dispatch. |
| `ProcessAttachment` | Every upload | Virus scan, then a 320px thumbnail for images. Infected files are deleted; the row stays so the uploader sees why. |
| `ExportTasks` | `POST /exports` | Writes the filtered tasks to CSV using `lazy()`. Cells starting with `= + - @` are escaped against formula injection. |

The virus scanner is simulated. It flags the EICAR signature and a configurable marker (`LAJUR-SIMULATED-VIRUS`), because Windows Defender quarantines EICAR files before they can be uploaded. Callers only use `isInfected()`, so a ClamAV client can replace it.

### Real-time

Observers write each change to `task_events`. `GET /api/stream` sends the rows after the client's `Last-Event-ID`.

- **Why SSE:** data only flows server to browser (writes use REST), it runs over plain HTTP, and the browser reconnects and resumes on its own.
- **Why a table:** no extra service, and the row id makes resuming after a dropped connection free.
- **Connection length:** `php artisan serve` handles one request at a time, so `SSE_HOLD_SECONDS=0` (default) answers and closes; the browser reconnects after 2 s. Behind php-fpm, set about 25 s for sub-second updates and size the pool for one worker per open board.
- **Token:** `EventSource` cannot send headers, so only the stream route accepts `?token=`. Tokens in URLs can reach access logs; short-lived stream tickets would fix that.

## Frontend

- **No state library.** Two screens and a side panel; a reactive `auth` object and component state are enough. Axios is used for upload progress events.
- **Board loading.** Each lane is its own paginated request. Live events trigger one batched refetch (400 ms), so a bulk update of 20 tasks causes one refresh. A per-lane request counter drops late responses, so fast filter changes never show stale results.
- **Open task panel.** Events are applied in place: comments append, scan results update, and edits refresh the form. If the user has unsaved changes, the panel shows a notice instead of overwriting them.
- **Design.** `DESIGN.md` holds the owner's direction and the reason for each visual decision. Colour pairs were checked against WCAG AA.

## Performance (brief, Part 3.3)

| Area | What was done | Effect |
|---|---|---|
| Caching | `ApiCache` caches `GET /tasks` (60 s) and `GET /users` (1 h). Store is `database` by default, `redis` in production (`predis`, no PHP extension). Tests pass on both. | Identical lane requests after a live event hit the cache, not MySQL. |
| Invalidation | Each scope keeps a version number; observers bump it on task, comment, attachment, or user changes. Works without cache tags. | Old entries are never read and expire on their TTL. |
| HTTP caching | `ConditionalJsonResponse` adds `ETag` and `Cache-Control: private, no-cache` to JSON GETs. | Unchanged lane: 4.3 kB becomes a 304 with 0 bytes. |
| Database | Index `tasks (status, created_at)`; eager loading everywhere; `preventLazyLoading()` outside production; export uses `lazy(500)`. | Lane query on 50,000 rows: 102.7 ms to 1.5 ms. No N+1 queries in the test suite. |
| Images | 320px progressive JPEG thumbnails, fetched only when they scroll into view (IntersectionObserver, since they need the bearer token). | The panel never downloads a full-size image for a preview. |
| Bundle | Route chunks, the task panel loaded on first open, Latin-only font subset. | See below. |

The task list cache is shared between users because everyone sees the same tasks for the same filters. If per-user visibility is added, the user id must become part of the key.

| Bundle | Before | After |
|---|---|---|
| JS for the sign-in page | 174.9 kB (64.5 kB gzip) | 150.0 kB (57.3 kB gzip) |
| Board code | in the first bundle | 13.3 kB, after sign-in |
| Task panel | in the first bundle | 15.7 kB, on first open |
| Build output | 26 files, 516 kB | 13 files, 286 kB |

## Testing

| Layer | Tool | Location |
|---|---|---|
| Backend unit | PHPUnit | `backend/tests/Unit` |
| Backend API and integration | PHPUnit on MySQL `lajur_test` | `backend/tests/Feature` |
| Frontend unit, component, API client | Vitest, Vue Test Utils, axios-mock-adapter | `frontend/tests/unit` |
| End to end | Playwright on database `lajur_e2e` | `frontend/tests/e2e` |

Backend tests use MySQL because Laragon's PHP has no `pdo_sqlite` and priority sorting relies on MySQL's `FIELD()`.

## Known limits

- No refresh tokens.
- One organisation; every user sees every task.
- No chunked uploads, file versions, or PDF export.
- JWT in the SSE URL (see Real-time).
