#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 || -z "${1:-}" ]]; then
  echo "Usage: tools/apply_mailer_dsn.sh 'brevo+api://CLE_API_ACTIVE@default' [test-recipient]" >&2
  exit 2
fi

MAILER_DSN_VALUE=$1
TEST_RECIPIENT=${2:-contact@hociatec.fr}
PROJECT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
ENV_FILE="$PROJECT_DIR/.env.prod.local"

case "$MAILER_DSN_VALUE" in
  null://*|*votre-cle*|*change-me*|*CLE_API_ACTIVE*)
    echo "MAILER_DSN invalide: la valeur ressemble a un placeholder." >&2
    exit 2
    ;;
esac

cd "$PROJECT_DIR"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "$ENV_FILE introuvable." >&2
  exit 2
fi

MAILER_DSN_VALUE="$MAILER_DSN_VALUE" ENV_FILE="$ENV_FILE" php <<'PHP'
<?php
$file = getenv('ENV_FILE');
$dsn = getenv('MAILER_DSN_VALUE');
$contents = file_get_contents($file);
if (false === $contents) {
    fwrite(STDERR, "Impossible de lire $file.\n");
    exit(2);
}
$line = 'MAILER_DSN="'.str_replace(['\\', '"'], ['\\\\', '\\"'], $dsn).'"';
if (preg_match('/^MAILER_DSN=.*$/m', $contents)) {
    $contents = preg_replace('/^MAILER_DSN=.*$/m', $line, $contents, 1);
} else {
    $contents .= (str_ends_with($contents, "\n") ? '' : "\n").$line."\n";
}
file_put_contents($file, $contents);
PHP

composer dump-env prod
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
APP_ENV=prod APP_DEBUG=0 php bin/console mailer:test "$TEST_RECIPIENT" --from=contact@hociatec.fr --subject='Test mailer Hociatec' --body='Test transport mail Hociatec'

if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files hociatec-messenger.service >/dev/null 2>&1; then
  sudo systemctl restart hociatec-messenger.service
fi

echo "Mailer configure et teste avec succes."
