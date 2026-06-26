#!/bin/sh
set -e

echo "=== [1/5] Pulling latest changes from git ==="
git pull eeja main

echo "=== [2/5] Running local setup script ==="
./setup.sh

echo "=== [3/5] Rebuilding and restarting containers ==="
docker compose up -d --build

echo "=== [4/5] Restarting Laravel queue workers ==="
docker compose exec -T app php artisan queue:restart

echo "=== [5/5] Performing basic health check ==="
docker compose ps
docker compose logs app --tail=20
docker compose logs reverb --tail=20

echo "=== Deployment completed successfully! ==="
