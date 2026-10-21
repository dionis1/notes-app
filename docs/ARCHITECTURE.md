# Architecture — Laravel 13 API and React SPA

## Boundaries

The server is **Laravel 13** with a versioned JSON API and session-based authentication. **React 19** renders the interface, React Router manages client-side navigation, Axios handles requests, and **Tailwind CSS 4** styles the pages.

The React editor owns form state. All reads and mutations happen through `/api/v1/notes` rather than embedded Eloquent data or Livewire hydration. Laravel validates requests and returns `NoteResource` fields only.

## Ownership and legacy data

Queries always start from the authenticated user's `notes()` relation. `NotePolicy` authorizes writes and foreign records return 404. The ownership migration deliberately leaves legacy records with `user_id = null` hidden; **never auto-assign these records to the first person who registers**. Back up the database and verify ownership before performing any manual data migration.

## Concurrency

Notes have monotonically increasing integer `version` values, initially 1. Updates and deletes use one conditional SQL operation that includes the expected version and current user. A stale request fails without mutating the record. The JSON controller translates version conflicts to HTTP **409**, and React retains the editor's draft. A deleted note cannot accidentally be re-created as a side effect of editing.

## API shape and paging

`NoteRequest` validates bounded note payloads; `NoteResource` exposes only public note fields. The list endpoint supports `q` (title/content search), `important`, and bounded `per_page` (1–50, default 10). Database ordering uses `created_at DESC, id DESC` for deterministic pagination. Indexes support owner and importance filtering.

## Sessions and CSRF

Authentication uses Laravel's `web` session guard. Sanctum's stateful API middleware accepts same-origin session cookies. React first gets `/sanctum/csrf-cookie` and Axios sends CSRF headers with mutations. Password reset links are Laravel-generated; account access is not based on localStorage bearer tokens.

## Local architecture

Docker Compose builds Vite assets in a Node 22 stage and serves Laravel using PHP 8.4 and SQLite. A named volume preserves the local database and encryption key. CI runs in-memory PHP tests and a separate disposable SQLite file for browser tests. This project intentionally avoids deployment/cluster infrastructure.
