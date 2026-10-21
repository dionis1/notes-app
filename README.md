# Notes — Laravel 13 API + React SPA

A private, responsive notes application built with **Laravel 13**, **PHP 8.4**, **React 19**, **Tailwind CSS 4** and **Vite 6**. The browser talks to a versioned Laravel JSON API through secure, same-origin session cookies.

## Features

- Register, sign in, sign out, and request or complete a password reset.
- Create, edit, delete, and mark notes as important.
- Search titles and content, filter important notes, and browse ten notes per page.
- All notes are private: users cannot view, change, or delete another user's notes.
- Optimistic concurrency protection prevents stale edits and deletes; HTTP 409 conflicts leave the editor's draft intact.
- Responsive React interface with Tailwind CSS 4, accessible form labels, focus styles, delete confirmation and user feedback.
- Laravel Form Requests, API Resources, a NoteService and a NotePolicy.
- PHPUnit feature tests, Laravel Pint, React production build, and Chromium browser tests with axe accessibility checks.

This is a learning/portfolio application, not an audited production service. Notes do not sync between browser tabs in real time; conflicts are detected when saving.

## Start on Ubuntu with Docker

You need only Docker Engine and Docker Compose. **No local PHP, Composer or Node installation is necessary.**

```bash
git clone https://github.com/dionis1/notes-app.git
cd notes-app
docker compose up --build -d
docker compose ps
```

Open **http://localhost:8000** and create an account. The first build takes several minutes. The image builds React assets with Node 22 and runs Laravel with PHP 8.4.

```bash
docker compose logs --tail=50 app
docker compose exec app php artisan test
docker compose down
```

A named Docker volume retains your notes database and local application key. **Do not use `docker compose down -v` if you want to keep your notes**; `-v` deletes the volume.

To use a different port: `APP_PORT=8001 docker compose up --build -d`. The application binds to localhost by default.

## Start without Docker

Requirements: **PHP 8.4**, Composer 2, Node 22 with npm, and PHP PDO SQLite, mbstring, DOM/XML.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
mkdir -p storage/app
touch storage/app/notes.sqlite
php artisan migrate
npm run build
php artisan serve
```

Open http://localhost:8000. For React/Tailwind development, run `npm run dev` in another terminal. The frontend and API should share the same origin for Sanctum's cookie-based authentication.

If you already have notes in `storage/app/notes.sqlite`, **back it up first**. The migration preserves records; do not run `migrate:fresh` on valuable data. Notes predating ownership are left unassigned and cannot be viewed by a new account automatically.

## API

All note endpoints are under `/api/v1` and require `auth:sanctum`. The React client obtains `/sanctum/csrf-cookie` before authentication requests, and Axios sends the CSRF token with state-changing requests.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/v1/me` | Safe current-user summary |
| GET | `/api/v1/notes` | Paginated notes; `q`, `important`, `page`, `per_page` |
| POST | `/api/v1/notes` | Create a note |
| GET | `/api/v1/notes/{id}` | Read an owned note |
| PATCH/PUT | `/api/v1/notes/{id}` | Update using the required `version` |
| DELETE | `/api/v1/notes/{id}` | Delete using the required `version` |

Credentials and recovery: `POST /register`, `POST /login`, `POST /logout`, `POST /forgot-password`, `POST /reset-password`.

API errors: **401** for unauthenticated access, **404** for foreign/missing notes, **422** for invalid input and **409** for concurrent edits/deletes. The server returns explicit API Resources rather than entire Eloquent records. Filtering and pagination execute in the database.

## Tests and quality checks

```bash
php artisan test
vendor/bin/pint --test
composer validate --no-check-publish
npm ci
npm run build
npx playwright install --with-deps chromium
npm run test:browser
```

GitHub Actions runs Laravel/Pint, the React production build, and Playwright with Chromium and axe-core. PHP tests use in-memory SQLite. The browser test starts a separate development server on port 8010, resetting **only** `/tmp/notes-browser.sqlite`; never point it at a real notes database.

For dependency reviews, run `composer audit` and `npm audit`. A passing automated test suite is not a security audit.

## Code organization

- `app/Http/Controllers/Api/V1/NoteController.php`: versioned HTTP endpoints and owner-scoped reads.
- `app/Http/Requests/NoteRequest.php`: validated note payloads.
- `app/Http/Resources/NoteResource.php`: controlled JSON responses.
- `app/Services/NoteService.php`: scoped operations and version-aware writes.
- `app/Policies/NotePolicy.php`: ownership authorization.
- `app/Http/Controllers/AuthController.php`: sessions and password recovery.
- `resources/js/`: React SPA, Axios client, authentication and notes pages.
- `resources/css/app.css`: Tailwind 4 entry point.
- `tests/Feature` and `tests/Browser`: server and real-browser regression tests.

See [architecture decisions](docs/ARCHITECTURE.md) and [security considerations](docs/SECURITY.md). Previous Livewire screenshots and implementation history remain available through Git history.

## Limitations

No multi-user note sharing, rich-text editor, file attachments, background synchronization or real-time collaboration. Local password-reset emails are logged; no external mail provider is configured. Production deployment, backups and a security review require additional work.

## License

[MIT](LICENSE).
