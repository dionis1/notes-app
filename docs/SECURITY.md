# Framework version and remaining risks

Laravel is intentionally pinned to **10.49.1**. It has not been upgraded. Laravel 10 security support ended on 4 February 2025. Compatible dependencies were refreshed, but the current Composer audit reports four entries in Laravel itself:

| Advisory ID | Finding |
| --- | --- |
| PKSA-d5tc-s1qs-h781 | XSS in debug page information |
| PKSA-m5cs-t1y6-qpcs | Temporary signed URL path confusion |
| PKSA-3r5d-mb8f-1qw9 | Email validation CRLF injection |
| PKSA-mdq4-51ck-6kdq | Email validation CRLF injection (overlapping entry) |

Full upstream advisory links and affected versions are in [composer-audit.json](composer-audit.json). Run `composer audit` for the raw findings; it is expected to fail while those advisories remain. The application explicitly rejects control characters and whitespace inside email addresses, but this is not a framework patch or a claim that all affected framework behavior has been fixed.

Keep this app local while the unsupported framework constraint remains. Local password recovery links are written to Docker logs rather than sent by an email provider. Do not share those logs or use real sensitive information in screenshots.

The Laravel implementation includes authentication, scoped ownership, policies, CSRF middleware, escaped views, locked IDs/versions, session regeneration, authentication throttling, and atomic write preconditions. Tests exercise those application boundaries; they do not remove framework-level risks.
