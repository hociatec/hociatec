#!/usr/bin/env bash

set -euo pipefail

IPA_PATH="${1:-}"

if [ -z "${IPA_PATH}" ]; then
  echo "Usage: $0 <path-to-ipa>" >&2
  exit 1
fi

if [ ! -f "${IPA_PATH}" ]; then
  echo "Fichier IPA introuvable: ${IPA_PATH}" >&2
  exit 1
fi

if ! unzip -Z1 "${IPA_PATH}" >/tmp/altstore-ipa-entries.txt; then
  echo "Le fichier n'est pas une archive IPA valide: ${IPA_PATH}" >&2
  exit 1
fi

if ! grep -Eq '^Payload/[^/]+\.app/$' /tmp/altstore-ipa-entries.txt; then
  echo "L'IPA ne contient pas de bundle .app sous Payload/." >&2
  exit 1
fi

if ! grep -Eq '^Payload/[^/]+\.app/Info\.plist$' /tmp/altstore-ipa-entries.txt; then
  echo "L'IPA ne contient pas de Info.plist dans l'app bundle." >&2
  exit 1
fi

if grep -Eq '^Payload/.*/_CodeSignature/' /tmp/altstore-ipa-entries.txt; then
  echo "L'IPA contient encore _CodeSignature, ce qui ne doit pas etre present pour AltStore." >&2
  exit 1
fi

if grep -Eq '^Payload/.*/embedded\.mobileprovision$' /tmp/altstore-ipa-entries.txt; then
  echo "L'IPA contient encore embedded.mobileprovision." >&2
  exit 1
fi

if grep -Eq '^Payload/.*/SC_Info/' /tmp/altstore-ipa-entries.txt; then
  echo "L'IPA contient encore SC_Info." >&2
  exit 1
fi

rm -f /tmp/altstore-ipa-entries.txt
echo "IPA AltStore valide: ${IPA_PATH}"
