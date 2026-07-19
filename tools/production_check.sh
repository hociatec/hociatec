#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"

failures=0
warnings=0

section() {
    printf '\n== %s ==\n' "$1"
}

ok() {
    printf '[OK] %s\n' "$1"
}

warn() {
    warnings=$((warnings + 1))
    printf '[WARN] %s\n' "$1"
}

fail() {
    failures=$((failures + 1))
    printf '[FAIL] %s\n' "$1"
}

run() {
    local label="$1"
    shift

    if "$@"; then
        ok "$label"
    else
        fail "$label"
    fi
}

require_file() {
    local path="$1"
    local label="$2"

    if [[ -s "$path" ]]; then
        ok "$label"
    else
        fail "$label"
    fi
}

require_env_value() {
    local name="$1"
    local required="${2:-1}"
    local value="${!name:-}"

    if [[ -n "$value" && "$value" != "change-me" && "$value" != *"_xxx"* ]]; then
        ok "$name is configured"
    elif [[ "$required" == "1" ]]; then
        fail "$name is missing or placeholder"
    else
        warn "$name is not configured"
    fi
}

require_env_equals() {
    local name="$1"
    local expected="$2"
    local value="${!name:-}"

    if [[ "$value" == "$expected" ]]; then
        ok "$name=$expected"
    else
        fail "$name must be $expected"
    fi
}

require_env_false() {
    local name="$1"
    local value="${!name:-}"

    if [[ "$value" == "0" || "$value" == "false" ]]; then
        ok "$name is disabled"
    else
        fail "$name must be 0 or false"
    fi
}

forbid_path() {
    local path="$1"
    local label="$2"

    if [[ -e "$path" ]]; then
        fail "$label"
    else
        ok "$label"
    fi
}

section "Environment"
cd "$BACKEND_DIR"
eval "$(
    php -r '
        require __DIR__."/vendor/autoload.php";

        $dotenv = new Symfony\Component\Dotenv\Dotenv();
        $files = [".env", ".env.local", ".env.prod", ".env.prod.local"];

        foreach ($files as $file) {
            if (is_file($file)) {
                $dotenv->overload($file);
            }
        }

        foreach (array_keys($_SERVER + $_ENV) as $name) {
            if (preg_match("/^(APP_|DATABASE_URL$|JWT_|MAILER_|CONTACT_|CORS_|TRUSTED_|STRIPE_|MESSENGER_)/", $name)) {
                $value = $_SERVER[$name] ?? $_ENV[$name] ?? "";
                echo "export ".$name."=".escapeshellarg((string) $value).";\n";
            }
        }
    '
)"

require_env_equals APP_ENV prod
require_env_false APP_DEBUG
require_env_value APP_SECRET
require_env_value DATABASE_URL
require_env_value JWT_PASSPHRASE
require_env_value MAILER_DSN
require_env_value MAILER_FROM
require_env_value CONTACT_RECIPIENT
require_env_value APP_FRONTEND_URL
require_env_value CORS_ALLOW_ORIGIN
require_env_value TRUSTED_HOSTS
require_env_value TRUSTED_PROXIES
require_env_value STRIPE_SECRET_KEY
require_env_value STRIPE_WEBHOOK_SECRET
require_env_value STRIPE_REFUND_WEBHOOK_SECRET
require_env_value MESSENGER_TRANSPORT_DSN
require_env_value MESSENGER_FAILURE_TRANSPORT_DSN

require_file "$BACKEND_DIR/config/jwt/private.pem" "JWT private key exists"
require_file "$BACKEND_DIR/config/jwt/public.pem" "JWT public key exists"

section "Backend"
run "Production env cache is generated" composer dump-env prod
run "Backend quality checks pass" composer run quality
run "Doctrine mapping is valid" env APP_ENV=prod php bin/console doctrine:schema:validate --skip-sync --no-interaction
run "Doctrine migrations are current" env APP_ENV=prod php bin/console doctrine:migrations:up-to-date --no-interaction
run "Messenger transports exist" env APP_ENV=prod php bin/console messenger:setup-transports --no-interaction
run "Messenger failed commands are available" env APP_ENV=prod php bin/console messenger:failed:show --help

section "Frontend"
cd "$FRONTEND_DIR"
forbid_path "$FRONTEND_DIR/dist-stale-20260714" "No stale frontend dist directory is present"
run "Frontend quality checks pass" npm run quality
run "Frontend production build succeeds" npm run build

section "Result"
if (( failures > 0 )); then
    printf '%d failure(s), %d warning(s)\n' "$failures" "$warnings"
    exit 1
fi

printf 'Production check passed with %d warning(s)\n' "$warnings"
