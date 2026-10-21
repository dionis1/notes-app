# Security notes — Laravel 13 / React SPA

## Framework and dependencies

The application now targets **PHP 8.4 + Laravel 13** and uses stable locked dependencies. The older Laravel 10 findings in the former `composer-audit.json` and historical documentation described the **previous implementation**, not the upgraded framework. Do not use them as a current vulnerability report. Run `composer audit` and `npm audit` against the **current** lockfiles before deployment. This repository is not security-audited.

## Data access

- Every notes query is scoped to the authenticated owner's user ID. Another user's record returns 404 rather than revealing its presence.
- Notes from the legacy schema without an owner remain private and cannot be silently claimed at registration.
- Policies protect updates and deletes. SQL includes an expected integer version; stale modifications cannot overwrite newer data.
- API responses use `NoteResource` to avoid accidentally disclosing database columns.

## Authentication

- Laravel's server-side session guard and Sanctum's stateful API middleware handle authentication. Axios sends CSRF tokens; session cookies are HTTP-only.
- Authentication endpoints are throttled and sessions are regenerated after login or registration, invalidated on logout.
- Passwords are hashed; registration enforces a minimum length and a byte cap. Password-reset flows use Laravel tokens.
- Password reset mail uses the log driver by default. Logs may contain secret reset links—do not share them.

## Operating precautions

Docker Compose binds the app to localhost and keeps the database and encryption key in a named volume. Never expose `APP_DEBUG=true` on a public server. Configure HTTPS, cookie domains, reliable mail, backups, log retention and security monitoring before a real deployment.

The automated feature tests include authentication, unauthorized data access, validation and concurrent update protection. Playwright also checks separate-account data isolation and major accessibility issues, but testing does not replace a security review.
