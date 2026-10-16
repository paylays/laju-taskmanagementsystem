# Requirements checklist

Each item in `Candidate-Instructions.md` with its status. Agreed scope: Parts 1, 2 and 4, then Part 3.3.

**Done** = complete. **Partial** = what is missing is named. **Not built** = out of scope.

## Part 1: Backend

| Requirement | Status | Where / notes |
|---|---|---|
| **1.1** Tables `users`, `tasks`, `task_attachments`, `task_comments` with the listed columns | Done | `backend/database/migrations` |
| SQL schema with proper indexes | Done | `backend/database/schema.sql`, explained in `database-schema.md` |
| Seeder: 5+ users, 15+ tasks, 10+ comments | Done | 6 users, 18 tasks, 14 comments |
| **1.2** `POST /auth/login` (JWT), `POST /auth/logout`, `GET /auth/me` | Done | `AuthController`, `JwtService`; logout revokes the token |
| `GET/POST/PUT/DELETE /tasks` with pagination, filters, sorting | Done | `TaskController`, `Task::scopeFilter` |
| `POST /tasks/{id}/attachments`, `GET /attachments/{id}/download`, `DELETE /attachments/{id}` | Done | `AttachmentController` |
| **1.3** Secure upload, validation, type and size limits | Done | type read from file bytes; 10 / 20 / 50 MB per category |
| Images, documents, videos | Done | |
| Image thumbnails | Done | 320px progressive JPEG |
| Secure storage | Done | private disk, random names, authenticated download |
| Advanced: virus scan simulation | Done | `VirusScanner` |
| Advanced: chunked upload over 50 MB, file versioning | Not built | |
| **1.4** Jobs: assignment email, bulk status, file processing | Done | `backend/app/Jobs` |
| Data export CSV / PDF | Partial | CSV only |

## Part 2: Frontend (Vue 3)

| Requirement | Status | Where / notes |
|---|---|---|
| Login and logout | Done | `LoginView.vue`, header Sign out |
| Task dashboard with CRUD | Done | `BoardView.vue`, `TaskPanel.vue` |
| Real-time updates | Done | SSE, `useLiveEvents.js` |
| Drag-and-drop upload | Done | `UploadZone.vue` |
| Responsive | Done | lanes become tabs below 900px; checked at 390, 768, 1440px |
| Advanced: filters and search, real-time comments, upload progress, toasts | Done | `FilterBar`, `CommentThread`, `AttachmentSection`, `ToastStack` |

## Part 3: Bonus

| Item | Status | Notes |
|---|---|---|
| 3.1 Video upload | Partial | upload and download only; no streaming, player, video thumbnails, or quality levels |
| 3.2 Live task status, real-time comments | Done | via SSE |
| 3.2 WebSockets, presence, typing indicators | Not built | SSE chosen instead (see `architecture.md`) |
| 3.3 Caching (Redis/Memcached) | Done | `ApiCache`; database store by default, Redis tested |
| 3.3 Query optimization | Done | new lane index (102.7 ms to 1.5 ms on 50,000 rows), eager loading, N+1 guard |
| 3.3 API response caching | Done | cached task and user lists, ETag / 304 |
| 3.3 Image optimization and lazy loading | Done | small progressive thumbnails, loaded when visible |
| 3.3 Code splitting and lazy loading | Done | route chunks, task panel on demand, Latin-only font |

## Part 4: Documentation and testing

| Requirement | Status | Where |
|---|---|---|
| README with setup | Done | `README.md`, `backend/README.md`, `frontend/README.md` |
| API docs | Done | `api-docs/openapi.yaml` (OpenAPI 3.1, passes Redocly lint) |
| Database schema docs | Done | `database-schema.md` |
| Architecture decisions | Done | `architecture.md` |
| Deployment guide | Not built | the app is not deployed; production notes (php-fpm, SSE hold time, Redis) are in `architecture.md` |
| Backend unit, API, database, upload tests | Done | 61 PHPUnit tests in `backend/tests` |
| Frontend component and API integration tests | Done | 40 Vitest tests in `frontend/tests/unit` |
| E2E tests for critical flows | Done | 6 Playwright tests in `frontend/tests/e2e` |

## Submission

| Item | Status | Notes |
|---|---|---|
| Source code | Done | Laravel uses `backend/app` instead of `backend/src` |
| SQL dump with sample data | Done | `backend/database/dump.sql` |
| Documentation | Done | |
| Demo recording, hosted version (optional) | Not built | left to the candidate |

All five minimum requirements (authentication, task CRUD, file upload, frontend, documentation) are met.
