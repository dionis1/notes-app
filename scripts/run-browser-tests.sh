#!/bin/sh
set -eu
cleanup() {
 containers=$(docker ps -aq --filter label=notes.browser-test=true)
 if [ -n "$containers" ]; then docker rm -f $containers >/dev/null 2>&1 || true; fi
}
cleanup
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
npx playwright test "$@"
