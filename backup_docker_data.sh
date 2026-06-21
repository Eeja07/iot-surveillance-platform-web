#!/bin/bash
set -e

BACKUP_DIR="$(pwd)/docker_backup_$(date +%F_%H-%M-%S)"
mkdir -p "$BACKUP_DIR"

echo "🔴 Stopping containers..."
docker compose down

echo "📦 Backing up MySQL volume..."
docker run --rm \
  -v cctv_miot_db_data:/data \
  -v "$BACKUP_DIR":/backup \
  busybox \
  tar czf /backup/db_data.tar.gz /data

echo "📦 Backing up MinIO volume..."
docker run --rm \
  -v cctv_miot_minio_data:/data \
  -v "$BACKUP_DIR":/backup \
  busybox \
  tar czf /backup/minio_data.tar.gz /data

echo "📦 Backing up EMQX data..."
docker run --rm \
  -v cctv_miot_emqx_data:/data \
  -v "$BACKUP_DIR":/backup \
  busybox \
  tar czf /backup/emqx_data.tar.gz /data

echo "📦 Backing up EMQX logs..."
docker run --rm \
  -v cctv_miot_emqx_log:/data \
  -v "$BACKUP_DIR":/backup \
  busybox \
  tar czf /backup/emqx_log.tar.gz /data

echo "📝 Copying docker-compose file..."
cp docker-compose.dev.yml "$BACKUP_DIR/"

echo "🟢 Backup completed!"
echo "📁 Backup location: $BACKUP_DIR"
