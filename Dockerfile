# ---------- Stage 1: Build assets ----------
FROM node:20-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json* yarn.lock* pnpm-lock.yaml* ./
RUN if [ -f package-lock.json ]; then \
      npm ci --no-audit --no-fund ; \
    else \
      npm install --no-audit --no-fund ; \
    fi

COPY resources ./resources
COPY public ./public
COPY vite.config.* ./
# COPY postcss.config.* tailwind.config.* ./
RUN npm run build


# ---------- Stage 2: PHP + Nginx (Production) ----------
FROM webdevops/php-nginx:8.3 AS app
ENV TZ=Asia/Jakarta \
    WEB_DOCUMENT_ROOT=/app/public

WORKDIR /app

# 1) Siapkan layer vendor (tanpa scripts karena belum ada artisan)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

# 2) Copy seluruh source proyek (vendor tetap dari layer sebelumnya)
COPY . /app

# 3) Copy hasil build Vite
COPY --from=assets /app/public/build /app/public/build

# 4) Buat semua folder runtime & set permission
RUN set -eux; \
    mkdir -p \
      /app/bootstrap/cache \
      /app/storage/app/public \
      /app/storage/framework/cache/data \
      /app/storage/framework/sessions \
      /app/storage/framework/views; \
    chown -R application:application /app; \
    chmod -R ug+rwX /app/storage /app/bootstrap/cache

# 5) Pakai user non-root
USER application

# 6) Jalankan scripts composer yang tertunda + optimize autoload
RUN composer dump-autoload -o && php artisan package:discover --ansi || true

# 7) Buat storage symlink (idempotent)
RUN php artisan storage:link || true

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -fsS http://127.0.0.1/health || exit 1
