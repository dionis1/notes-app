FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js vitest.config.js ./
COPY resources ./resources
COPY tests/Frontend ./tests/Frontend
RUN npm run test:unit && npm run build

FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libonig-dev libxml2-dev \
    && docker-php-ext-install -j4 pdo_sqlite mbstring dom \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN touch .env \
    && composer dump-autoload --optimize \
    && mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs vendor/pestphp/pest/.temp vendor/pestphp/pest-plugin-mutate/.temp \
    && chown -R www-data:www-data storage bootstrap/cache vendor/pestphp/pest/.temp vendor/pestphp/pest-plugin-mutate/.temp
USER www-data
EXPOSE 8000
ENTRYPOINT ["sh", "docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
