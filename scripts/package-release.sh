#!/usr/bin/env bash
set -euo pipefail

TAG="${1:-dev}"
APP_NAME="codeigniter-tutorial"
ARCHIVE_NAME="${APP_NAME}-${TAG}.tar.gz"
DIST_DIR="dist"
STAGE_DIR="${DIST_DIR}/stage"

rm -rf "${STAGE_DIR}"
mkdir -p "${STAGE_DIR}" "${DIST_DIR}"

rsync -a \
  --exclude ".git" \
  --exclude ".github" \
  --exclude ".vscode" \
  --exclude ".idea" \
  --exclude "node_modules" \
  --exclude "tests" \
  --exclude "writable/cache/*" \
  --exclude "writable/session/*" \
  --exclude "writable/debugbar/*" \
  --exclude "writable/logs/*" \
  --exclude "writable/uploads/*" \
  --exclude "dist" \
  ./ "${STAGE_DIR}/${APP_NAME}/"

tar -czf "${DIST_DIR}/${ARCHIVE_NAME}" -C "${STAGE_DIR}" "${APP_NAME}"
rm -rf "${STAGE_DIR}"

echo "Created ${DIST_DIR}/${ARCHIVE_NAME}"
