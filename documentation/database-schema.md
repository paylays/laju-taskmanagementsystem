# Database schema

MySQL 8, InnoDB, `utf8mb4_unicode_ci`. The migrations in `backend/database/migrations` are the source of truth. Generated from them:

- `backend/database/schema.sql`: tables and indexes.
- `backend/database/dump.sql`: schema plus seed data.

```
                      created_by (1:N)
            +------------------------------------+
            |                                    v
       +---------+  assigned_user_id (0..1:N) +---------+  1:N  +------------------+
       |  users  | -------------------------> |  tasks  | ----> | task_attachments |
       +---------+                            +---------+       +------------------+
         |     |                                   |                  ^
         |     |  user_id (1:N)                    | 1:N              | uploaded_by (0..1:N)
         |     +---------------------------> +---------------+        |
         |                                   | task_comments |        |
         |                                   +---------------+        |
         +------------------------------------------------------------+
         |
         | user_id (1:N)     +---------+
         +-----------------> | exports |
                             +---------+

  Standalone: task_events (SSE feed), revoked_tokens (logged-out JWTs)
```

## Columns

| Table | Columns |
|---|---|
| `users` | `id` PK, `name`, `email` unique, `password` (bcrypt), `role` (admin, member), `created_at`, `updated_at` |
| `tasks` | `id` PK, `title`, `description` (nullable), `status` (todo, in_progress, done), `priority` (low, medium, high), `assigned_user_id` FK (nullable), `created_by` FK, `due_date` (nullable), `created_at`, `updated_at` |
| `task_attachments` | `id` PK, `task_id` FK, `uploaded_by` FK (nullable), `file_name` (original name), `file_path` (random name on disk), `file_size` (bytes), `mime_type` (read from content), `thumbnail_path` (nullable), `scan_status` (pending, clean, infected), `uploaded_at` |
| `task_comments` | `id` PK, `task_id` FK, `user_id` FK, `comment`, `created_at` |
| `task_events` | `id` PK (also the SSE event id), `type`, `payload` (JSON), `created_at` |
| `exports` | `id` PK, `user_id` FK, `status` (pending, processing, done, failed), `filters` (JSON), `file_path` (nullable), `row_count` (nullable), `created_at`, `updated_at` |
| `revoked_tokens` | `jti` PK, `expires_at` |

## Tables

| Table | Purpose | Beyond the brief |
|---|---|---|
| `users` | Accounts and roles | none |
| `tasks` | Tasks | none |
| `task_attachments` | Uploaded files | `uploaded_by` (delete rights), `thumbnail_path`, `scan_status` |
| `task_comments` | Comments | no `updated_at`, since comments are never edited |
| `task_events` | Change feed for `GET /api/stream`; the id is the SSE `Last-Event-ID`. Pruned after 24 h. | supporting |
| `exports` | CSV export requests: status, filters, file path | supporting |
| `revoked_tokens` | `jti` of logged-out JWTs, pruned after expiry | supporting |
| `jobs`, `failed_jobs`, `job_batches` | Laravel database queue | framework |
| `cache`, `cache_locks` | Laravel cache (API responses, login rate limit) | framework |

## Indexes

| Index | Used by |
|---|---|
| `users.email` (unique) | Login; blocks duplicate accounts |
| `users.role` | No current query; kept for role lookups, cheap on a small table |
| `tasks (assigned_user_id, status)` | "Assigned to" filter within a lane; also the foreign key |
| `tasks (status, due_date)` | Lanes sorted by due date |
| `tasks (status, created_at)` | Default lane page (`status = ? ORDER BY created_at DESC LIMIT 25`). On 50,000 tasks with an old backlog: 102.7 ms and 48,025 rows scanned without it, 1.5 ms and 25 rows with it. |
| `tasks.priority` | Priority filter |
| `tasks.created_by` | Foreign key, `created_by` filter |
| `tasks.created_at` | Newest-first sort without a status filter (export) |
| `task_attachments.task_id` | A task's files; foreign key |
| `task_attachments.scan_status` | Files waiting for a scan |
| `task_comments (task_id, created_at)` | A task's thread in order, no filesort |
| `task_events.created_at` | Pruning (stream reads use the primary key) |
| `revoked_tokens.expires_at` | Pruning (lookups use the primary key) |

Search uses `LIKE '%term%'`, which no B-tree index can serve. That is fine at this size; add a FULLTEXT index if tasks grow into tens of thousands.

## Deletes

| Deleted | Effect |
|---|---|
| Task | Comments and attachment rows cascade. The controller removes the files from disk first. |
| User | Their tasks and comments cascade. Tasks assigned to them become unassigned. Their uploads stay, with `uploaded_by = NULL`. |

## Sample data

`php artisan db:seed` creates:

- 6 users: `admin@lajur.test` (admin) and `rina`, `bagus`, `dewi`, `fajar`, `sari` (`@lajur.test`, members). Password for all: `password`.
- 18 tasks across the three statuses, with due dates relative to the seeding day.
- 14 comments on 8 tasks.

Seeding mutes model events, so no emails are queued and the event feed stays empty.
