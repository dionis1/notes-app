# Notes API contract

## Session authentication

The supported client is the same-origin React SPA. GET `/sanctum/csrf-cookie`, then POST `/login` with `email` and `password`. Axios sends cookies and the XSRF header. Laravel 13 uses origin-aware request-forgery checks with token validation as a fallback; untrusted cross-site writes without a valid token are rejected.

POST `/register` accepts `name`, `email`, `password`, and `password_confirmation`. POST `/logout` invalidates the session. POST `/forgot-password` accepts `email` and gives a generic response. POST `/reset-password` accepts `email`, `token`, `password`, and `password_confirmation`. Passwords require 12 characters and a maximum of 72 bytes; malformed email/password/token input returns validation errors. Reset tokens expire after 60 minutes and cannot be replayed.

Auth endpoints are throttled to five requests per minute. Business endpoints require Sanctum authentication and allow 120 requests per minute per user. There is no bearer-token issuance UI.

## Endpoints

All paths below start with `/api/v1`.

| Method | Path | Behavior |
| --- | --- | --- |
| GET | `/me` | Current session user |
| GET | `/notes` | Owner-scoped paginated notes |
| POST | `/notes` | Create a note and version 1 snapshot |
| GET | `/notes/{id}` | Read an owned note |
| PATCH, PUT | `/notes/{id}` | Update using current version |
| DELETE | `/notes/{id}` | Permanently delete using current version |
| GET | `/notes/{id}/revisions` | Paginated private snapshots |
| POST | `/notes/{id}/revisions/{revision}/restore` | Restore an earlier snapshot as a new version |

List filters: `q` searches title/content (maximum 100 characters), `important=1|0` filters importance, `per_page=1..50` defaults to 10, and `page` must be positive. An omitted importance filter includes both kinds. Revision pages contain ten records, newest version first. Single resources use `{ "data": {...} }`; lists include `data`, `links`, and `meta`.

Create example:

```json
{ "title": "An idea", "content": "A thought to revisit.", "is_important": true }
```

Title is required and at most 255 characters; content is required and at most 10,000 characters. `is_important` is optional and boolean. Creates reject a supplied version. Ownership fields are never accepted from the client.

The returned version starts at 1. Send it on every subsequent write:

```json
{ "content": "An improved thought.", "version": 1 }
```

Successful updates increment the version. DELETE and restore require `{ "version": 2 }` using the current value. Restore returns the updated note; the revision list records `restored_from_version`. A revision from a different note is rejected even if both notes have the same owner. A deleted note cannot be recreated by retrying an update.

## Responses and errors

Creates return 201, reads/updates/restores 200, and deletes 204. Unauthenticated requests return 401, inaccessible/missing records 404, stale versions 409, forgery failures 419, validation failures 422, and rate-limit failures 429.

Validation responses use `{ "message": "...", "errors": { "field": ["..."] } }`. Other errors expose a message. The client keeps conflict drafts and offers explicit recovery; it does not silently retry writes or overwrite newer data.
