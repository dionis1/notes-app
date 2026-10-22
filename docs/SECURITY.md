# Application security notes

- Notes and snapshots are scoped to their authenticated owner. IDs alone never grant access. Unowned legacy notes stay hidden.
- Policies run inside create/update/delete/restore actions as well as owner-scoped HTTP lookups. Shared validation protects direct action calls.
- Updates, restores, and deletes require an expected version. Snapshot creation is transactional; persistence failures roll back note changes.
- React renders content as text, including historical snapshots. It does not render stored HTML.
- Sessions use HTTP-only cookies and Sanctum's stateful middleware. Passwords are hashed; email normalization, credential bounds, throttling, session regeneration/logout, and reset-token expiry/reuse checks are tested.
- Draft recovery uses an account-keyed memory map in the current tab, not localStorage. Explicit sign-out clears it. Closing/reloading loses that memory. Shared computers still require ordinary care with open sessions and visible screens.
- The optional public demo credentials are local-only; seeding refuses production and never resets an existing account.
- The local SQLite database and snapshots are not end-to-end encrypted. Revision retention lasts until permanent deletion. Application access control does not protect against someone who can read the database files.
- Password-reset links are secrets written to the local log. Keep the log and Docker data volume private.

Run `composer audit --locked` and `npm audit` against the committed lockfiles. Automated regression and axe checks support review; this repository is not an independent security audit. Docker binds to localhost and is intended for local development.
