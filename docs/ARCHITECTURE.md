# Architecture decisions

## API and ownership

Laravel serves the React shell and `/api/v1` on the same origin. Axios uses session cookies and CSRF headers. React owns editable state; the server independently authorizes data access. Reads start from the current user's `notes()` relationship, returning 404 for another owner's IDs. `NotePolicy` protects domain actions too, including direct calls without a controller. Legacy unowned notes stay inaccessible.

Form Requests validate transport input. `NoteRules` shares content constraints with actions, so invoking an action directly does not bypass validation. `CredentialRequest` normalizes string email input without coercing malformed arrays; `CredentialRules` preserves email/control-character and password byte limits. API Resources select response fields. The former broad `NoteService` is replaced by focused create/update/delete/restore actions.

## Transactions and concurrency

Notes begin at version 1. An edit checks the expected version, preserves a baseline snapshot for an older owned note if necessary, conditionally updates the owner/version-matched row, and records the resulting snapshot in one transaction. Snapshot persistence failure rolls back the note write. Tests inject those failures to verify atomicity.

Restore verifies the source revision belongs to the same authorized note, then uses the same conditional update path. It creates a new version with a `restored_from_version` marker; historical snapshots are not overwritten. Stale edits, restores, or deletes return 409. Permanent deletion is version checked and cascades to its revision records. This history does not offer recovery after permanent deletion.

The snapshot table's unique `(note_id, version)` index enforces one snapshot per version and supports paginated history queries. Existing notes are not bulk-copied by the migration: their state is captured on their first edit. Earlier pre-migration versions cannot be reconstructed.

## React state boundaries

List-loading errors and editor mutation errors are separate; a successful background search cannot dismiss a conflict. Search requests use AbortController and stale-result guards. List pages clamp to the last valid page after deletion. Writes disable editor controls and use an in-memory mutex to avoid overlapping operations.

Unsaved changes require explicit confirmation before switching/discarding notes or signing out. Conflict controls let the writer load the latest saved version or keep the draft as a new note. History restoration explicitly confirms replacement, including unsaved editor content. The history dialog traps focus, closes on Escape, and restores focus to its opener.

The auth provider holds a memory-only draft map keyed by user ID. A 401 redirects to sign-in while keeping the current account's draft in that tab. The same user can recover it after signing in; another account uses a different map entry. Explicit sign-out clears the map. No draft data is written to browser storage, and reload/close loses the in-memory draft after the browser's unsaved-change warning.

## Queries and local runtime

Notes paginate with `created_at DESC, id DESC`, using owner/importance indexes. History pages contain ten snapshots. Substring search uses parameterized LIKE queries; owner indexes constrain access, but a normal B-tree does not accelerate a leading-wildcard content search. Lazy loading is prohibited in development/tests to catch accidental relationship queries.

Docker Compose runs one PHP app container. Node builds/tests the frontend during image creation. SQLite and the generated local key persist in a named volume; migrations are additive. Demo data is explicit, local-only, and transactional. Existing demo accounts are skipped rather than reset.

PHP tests force isolated in-memory settings, including when launched inside Compose. Browser checks use a separate disposable container/database on port 8010. README screenshots use synthetic records owned by the demo writer. No CI/deployment tooling is part of this setup.
