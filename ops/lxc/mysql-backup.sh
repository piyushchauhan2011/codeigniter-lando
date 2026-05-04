#!/usr/bin/env bash
set -euo pipefail

# Logical backup of a single MySQL database using mysqldump.
# Usage:
#   MYSQL_HOST=127.0.0.1 MYSQL_USER=u MYSQL_PASSWORD=p MYSQL_DATABASE=db \
#     ./ops/lxc/mysql-backup.sh ./backups/dump.sql.gz
#
# Requires: mysqldump, gzip on PATH.

: "${MYSQL_HOST:?Set MYSQL_HOST}"
: "${MYSQL_USER:?Set MYSQL_USER}"
: "${MYSQL_PASSWORD:?Set MYSQL_PASSWORD}"
: "${MYSQL_DATABASE:?Set MYSQL_DATABASE}"

out="${1:?Usage: $0 /path/to/output.sql.gz}"

mkdir -p "$(dirname "$out")"

MYSQL_PWD="$MYSQL_PASSWORD" mysqldump \
  --host="$MYSQL_HOST" \
  --user="$MYSQL_USER" \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  "$MYSQL_DATABASE" | gzip -c >"$out"

echo "Wrote $out"
