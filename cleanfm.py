#!/usr/bin/env bash
set -euo pipefail

# ==========================
# Configuration
# ==========================
MYSQL_CONTAINER="cctv-db"
MYSQL_DATABASE="Sistem_Camera_MIOT"
MYSQL_USER="root"
MYSQL_PASSWORD="root"

MINIO_ALIAS="local"
MINIO_BUCKET="cctv"
PREFIX="firmware"

TMPDIR=$(mktemp -d)
trap 'rm -rf "$TMPDIR"' EXIT

echo "========================================"
echo " Firmware Orphan Cleanup"
echo "========================================"

echo "[1/6] Exporting firmware paths from database..."

docker exec -i "$MYSQL_CONTAINER" \
    mysql \
    -u"$MYSQL_USER" \
    -p"$MYSQL_PASSWORD" \
    -N \
    "$MYSQL_DATABASE" \
    -e "SELECT path FROM ota_firmwares ORDER BY path;" \
    > "$TMPDIR/keep_bin.txt"

BIN_COUNT=$(wc -l < "$TMPDIR/keep_bin.txt")

echo "Firmware records : $BIN_COUNT"

echo "[2/6] Generating manifest whitelist..."

sed \
's/firmware_/manifest_/;
s/\.bin$/.json/' \
"$TMPDIR/keep_bin.txt" \
> "$TMPDIR/keep_manifest.txt"

cat \
"$TMPDIR/keep_bin.txt" \
"$TMPDIR/keep_manifest.txt" \
| sort \
> "$TMPDIR/keep.txt"

echo "[3/6] Reading MinIO objects..."

mc find "$MINIO_ALIAS/$MINIO_BUCKET/$PREFIX" \
| sed "s#^$MINIO_ALIAS/$MINIO_BUCKET/##" \
| sort \
> "$TMPDIR/all.txt"

TOTAL_OBJECTS=$(wc -l < "$TMPDIR/all.txt")

echo "Objects in MinIO : $TOTAL_OBJECTS"

echo "[4/6] Detecting orphan objects..."

comm -23 \
"$TMPDIR/all.txt" \
"$TMPDIR/keep.txt" \
> "$TMPDIR/orphan.txt"

ORPHANS=$(wc -l < "$TMPDIR/orphan.txt")

echo
echo "========================================"
echo "Summary"
echo "========================================"
echo "Database firmware : $BIN_COUNT"
echo "Expected objects  : $((BIN_COUNT * 2))"
echo "Objects in MinIO  : $TOTAL_OBJECTS"
echo "Orphan objects    : $ORPHANS"
echo

if [[ "$ORPHANS" -eq 0 ]]; then
    echo "No orphan objects found."
    exit 0
fi

echo "Orphan list:"
echo "----------------------------------------"
cat "$TMPDIR/orphan.txt"
echo "----------------------------------------"

echo
read -rp "Delete ALL orphan objects? (yes/no): " ans

if [[ "$ans" != "yes" ]]; then
    echo "Cancelled."
    exit 0
fi

echo
echo "[5/6] Removing orphan objects..."

while IFS= read -r obj; do
    echo "Deleting: $obj"
    mc rm "$MINIO_ALIAS/$MINIO_BUCKET/$obj"
done < "$TMPDIR/orphan.txt"

echo
echo "[6/6] Done."

LEFT=$(mc find "$MINIO_ALIAS/$MINIO_BUCKET/$PREFIX" | wc -l)

echo
echo "Remaining firmware objects: $LEFT"
echo "Cleanup completed successfully."
