#!/usr/bin/env bash
set -euo pipefail

DB_NAME="sewaaja"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-/Applications/XAMPP/xamppfiles/bin/mysqldump}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

mkdir -p database
if [ -n "$DB_PASS" ]; then
  "$MYSQLDUMP_BIN" -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --no-data --databases "$DB_NAME" --add-drop-database --triggers --default-character-set=utf8mb4 > database/schema.sql
else
  "$MYSQLDUMP_BIN" -h "$DB_HOST" -u "$DB_USER" --no-data --databases "$DB_NAME" --add-drop-database --triggers --default-character-set=utf8mb4 > database/schema.sql
fi

git add database/schema.sql scripts/export-schema-and-push.sh SETUP_LOCAL.md
if git diff --cached --quiet; then
  echo "No schema changes to commit."
else
  git commit -m "Update database schema"
  git push
fi
