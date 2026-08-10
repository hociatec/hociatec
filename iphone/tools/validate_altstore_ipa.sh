#!/usr/bin/env bash

set -euo pipefail

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 path/to/app.ipa" >&2
  exit 1
fi

IPA_PATH="$1"

if [ ! -f "${IPA_PATH}" ]; then
  echo "IPA introuvable: ${IPA_PATH}" >&2
  exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
  echo "La commande unzip est requise." >&2
  exit 1
fi

ZIP_LISTING="$(zipinfo -1 "${IPA_PATH}")"

require_entry() {
  local pattern="$1"
  local message="$2"

  if ! printf '%s\n' "${ZIP_LISTING}" | grep -Eq "${pattern}"; then
    echo "${message}" >&2
    exit 1
  fi
}

reject_entry() {
  local pattern="$1"
  local message="$2"

  if printf '%s\n' "${ZIP_LISTING}" | grep -Eq "${pattern}"; then
    echo "${message}" >&2
    exit 1
  fi
}

require_entry '^Payload/$' "IPA invalide: dossier Payload absent."
require_entry '^Payload/[^/]+\.app/$' "IPA invalide: bundle .app absent dans Payload."
require_entry '^Payload/[^/]+\.app/Info\.plist$' "IPA invalide: Info.plist absent du bundle principal."
require_entry '^Payload/[^/]+\.app/Frameworks/App\.framework/App$' "IPA invalide: App.framework manquant."
require_entry '^Payload/[^/]+\.app/Frameworks/Flutter\.framework/Flutter$' "IPA invalide: Flutter.framework manquant."

reject_entry '^Payload/[^/]+\.app/_CodeSignature/' "IPA AltStore invalide: le bundle principal contient encore _CodeSignature."
reject_entry '^Payload/[^/]+\.app/embedded\.mobileprovision$' "IPA AltStore invalide: embedded.mobileprovision ne doit pas etre present."
reject_entry '^Payload/[^/]+\.app/SC_Info/' "IPA AltStore invalide: SC_Info ne doit pas etre present."

APP_INFO="$(unzip -p "${IPA_PATH}" Payload/*.app/Info.plist | strings)"

if ! printf '%s\n' "${APP_INFO}" | grep -Fq 'CFBundleIdentifier'; then
  echo "IPA invalide: CFBundleIdentifier absent du Info.plist principal." >&2
  exit 1
fi

if ! printf '%s\n' "${APP_INFO}" | grep -Fq 'CFBundleShortVersionString'; then
  echo "IPA invalide: CFBundleShortVersionString absent du Info.plist principal." >&2
  exit 1
fi

if ! printf '%s\n' "${APP_INFO}" | grep -Fq 'CFBundleVersion'; then
  echo "IPA invalide: CFBundleVersion absent du Info.plist principal." >&2
  exit 1
fi

echo "IPA AltStore valide: ${IPA_PATH}"
