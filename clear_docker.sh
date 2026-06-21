docker compose -f docker-compose.dev.yml exec app php artisan cache:clear  &&
docker compose -f docker-compose.dev.yml exec app php artisan route:clear &&
docker compose -f docker-compose.dev.yml exec app php artisan config:clear &&
docker compose -f docker-compose.dev.yml exec app php artisan view:clear &&
docker compose -f docker-compose.dev.yml exec app php artisan optimize:clear

