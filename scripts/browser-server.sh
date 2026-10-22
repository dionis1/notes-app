#!/bin/sh
set -eu
docker run --rm --stop-timeout=3 --name notes-browser-check --label notes.browser-test=true \
 -p 127.0.0.1:8010:8000 \
 -e APP_ENV=local -e APP_DEBUG=false -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
 -e APP_URL=http://127.0.0.1:8010 -e DB_CONNECTION=sqlite -e DB_DATABASE=/tmp/notes-browser.sqlite \
 -e SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8010,localhost:8010 -e CACHE_STORE=array -e SESSION_DRIVER=file -e MAIL_MAILER=array \
 notes-local:latest &
server_pid=$!
cleanup() {
 docker stop --timeout=3 notes-browser-check >/dev/null 2>&1 || true
 wait "$server_pid" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 0' INT TERM
wait "$server_pid"
