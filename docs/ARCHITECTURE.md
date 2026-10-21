# Laravel design decisions

## Framework constraint

Laravel remains pinned to 10.49.1. Compatible dependencies were refreshed. Its expired support and remaining framework audit entries are documented in SECURITY.md. No framework upgrade is claimed.

## Private notes

New notes belong to the authenticated user. Every query starts from the user's notes relationship. Policies protect updates and deletes; foreign records return 404. Locked Livewire IDs/versions provide additional protection, while ownership checks remain the actual authorization boundary.

The ownership migration leaves old anonymous records unowned and invisible. A new account never inherits them. Assign an owner only after verifying the records belong to that person.

## Keep the service where it adds behavior

The component manages form state and validation. The service performs version-aware mutations. The original pass-through repository/interface wrappers were removed because they added no distinct storage behavior. Eloquent relationships provide scoped reads without additional abstraction.

## Concurrent edits

Each note has an integer version. Update and delete SQL includes the owner and expected version. A stale write produces a conflict without changing the current record. The component preserves unsaved draft text. Saving a deleted record never falls through to creating a replacement.

## Bounded lists

The component queries 10 notes per page rather than serializing a complete collection into public state. Ordering uses created_at followed by ID so equal timestamps remain deterministic. Indexes support ownership, importance, and ordering. Filters reset the page; stable wire:key values preserve list identity.

## Local development

SQLite and a single Docker service keep the project straightforward to run on a PC. Node compiles local CSS/JS when building the image; PHP serves the app, and a named volume preserves notes and the local key. No other services or delivery infrastructure are included.
