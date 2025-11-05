#!/usr/bin/env bash
set -euo pipefail

# Simple MySQL backup script based on DATABASE_URL
# Usage: ./tools/backup_db.sh [output_dir]

cd "$(dirname "$0")/.." || exit 1

OUTDIR=${1:-"./var/backups"}
mkdir -p "$OUTDIR"

URL=${DATABASE_URL:-$(grep -E '^\s*DATABASE_URL=' .env .env.local 2>/dev/null | tail -n1 | sed -E 's/.*DATABASE_URL="?([^"\r\n]+).*/\1/')}

if [[ -z "$URL" ]]; then
  echo "DATABASE_URL introuvable. Exportez-la ou définissez-la dans .env.local" >&2
  exit 1
fi

# Parse mysql://user:pass@host:port/db
proto=$(echo "$URL" | sed -E 's#^(.*)://.*#\1#')
if [[ "$proto" != "mysql" ]]; then
  echo "Seul MySQL est géré par ce script." >&2
  exit 1
fi

creds=$(echo "$URL" | sed -E 's#^[^:]+://([^@]+)@.*#\1#')
user=$(echo "$creds" | cut -d: -f1)
pass=$(echo "$creds" | cut -d: -f2-)
host=$(echo "$URL" | sed -E 's#^[^@]+@([^:/]+).*#\1#')
port=$(echo "$URL" | sed -En 's#^[^@]+@[^:]+:([0-9]+).*#\1#p')
db=$(echo "$URL" | sed -E 's#.*/([^\?]+).*#\1#')

DATE=$(date +%F_%H-%M-%S)
FILE="$OUTDIR/db-$DATE.sql.gz"

echo "Sauvegarde de $db vers $FILE"
MYSQL_PWD="$pass" mysqldump -u"$user" -h"$host" ${port:+-P"$port"} --single-transaction --routines --triggers "$db" \
  | gzip -9 > "$FILE"

echo "OK"

