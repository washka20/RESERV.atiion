#!/bin/sh
set -eu

MC_ALIAS=local
BUCKET_NAME="${MINIO_BUCKET:-reservatiion}"
ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"
ROOT_USER="${MINIO_ROOT_USER:-minioadmin}"
ROOT_PASSWORD="${MINIO_ROOT_PASSWORD:-minioadmin}"

echo "[minio-init] waiting for MinIO at ${ENDPOINT}..."
until mc alias set "${MC_ALIAS}" "${ENDPOINT}" "${ROOT_USER}" "${ROOT_PASSWORD}" >/dev/null 2>&1; do
  sleep 1
done

echo "[minio-init] creating bucket '${BUCKET_NAME}' if absent..."
mc mb --ignore-existing "${MC_ALIAS}/${BUCKET_NAME}"

echo "[minio-init] setting anonymous download policy for bucket..."
mc anonymous set download "${MC_ALIAS}/${BUCKET_NAME}" || true

echo "[minio-init] done."
