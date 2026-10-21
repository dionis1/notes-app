#!/bin/sh
set -eu
# A dedicated, disposable database; never use the development notes database.
rm -f /tmp/notes-browser.sqlite
touch /tmp/notes-browser.sqlite
php artisan migrate --force --no-interaction
exec php artisan serve --host=127.0.0.1 --port=8010
