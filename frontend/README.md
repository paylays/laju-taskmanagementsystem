# Lajur frontend

Vue 3 single-page app built with Vite. Setup is in [../documentation/setup-guide.md](../documentation/setup-guide.md).

```bash
npm install
npm run dev          # http://127.0.0.1:5173, proxies /api to http://127.0.0.1:8000
npm run build        # production build in dist/
npm test             # Vitest
npm run test:e2e     # Playwright (run `npx playwright install chromium` once)
```

Set `LAJUR_API_URL` to point the dev proxy at an API on another address.

## Where things are

| Path | Contents |
|---|---|
| `src/views/LoginView.vue` | Sign-in page |
| `src/views/BoardView.vue` | The board: filters, three lanes (tabs on narrow screens), bulk actions, export, live updates |
| `src/components/TaskPanel.vue` | Side panel for creating and editing a task, with files and comments |
| `src/components/AttachmentSection.vue`, `UploadZone.vue` | Drag-and-drop uploads with progress, scan status, download, delete |
| `src/components/CommentThread.vue` | Comments; also receives live comments from the board |
| `src/composables/useLiveEvents.js` | EventSource connection to `/api/stream` with reconnect handling |
| `src/composables/useToasts.js`, `components/ToastStack.vue` | Toast messages |
| `src/api/` | Axios instance (token header, 401 handling, error messages) and one function per endpoint |
| `src/stores/auth.js` | Session in `localStorage`, plus the same edit/delete rules the API enforces |
| `src/styles.css` | Design tokens from `../DESIGN.md` |

## Tests

`tests/unit` (Vitest, jsdom): formatting helpers, the auth store, `TaskRow`, `UploadZone` (drag and drop), `ToastStack`, the API client (token, 401, validation and network errors, multipart uploads), `CommentThread` and `LoginView` against a mocked API.

`tests/e2e` (Playwright): sign-in errors; create, upload, comment, move and delete a task; live updates between two users; bulk status change; permissions for a member; phone layout. The run starts its own API on port 8010 with the database `lajur_e2e` and its own Vite on port 5180.
