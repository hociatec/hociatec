#!/usr/bin/env bash

set -euo pipefail

SOURCE_PATH="${1:-}"
EXPECTED_VERSION="${2:-}"
EXPECTED_BUILD="${3:-}"

if [ -z "${SOURCE_PATH}" ] || [ -z "${EXPECTED_VERSION}" ] || [ -z "${EXPECTED_BUILD}" ]; then
  echo "Usage: $0 <source-json> <expected-version> <expected-build>" >&2
  exit 1
fi

if [ ! -f "${SOURCE_PATH}" ]; then
  echo "Fichier source AltStore introuvable: ${SOURCE_PATH}" >&2
  exit 1
fi

python3 - "${SOURCE_PATH}" "${EXPECTED_VERSION}" "${EXPECTED_BUILD}" <<'PY'
import json
import sys

source_path, expected_version, expected_build = sys.argv[1:]

with open(source_path, "r", encoding="utf-8") as fh:
    data = json.load(fh)

apps = data.get("apps") or []
if not apps:
    raise SystemExit("Le fichier source AltStore ne contient aucune application.")

versions = apps[0].get("versions") or []
if not versions:
    raise SystemExit("Le fichier source AltStore ne contient aucune version.")

app_download_url = apps[0].get("downloadURL", "")
if not app_download_url.endswith(".ipa"):
    raise SystemExit("Le downloadURL AltStore au niveau de l'application ne pointe pas vers un .ipa.")

entry = versions[0]

if entry.get("version") != expected_version:
    raise SystemExit(
        f"Version AltStore invalide: {entry.get('version')} != {expected_version}"
    )

if str(entry.get("buildVersion")) != expected_build:
    raise SystemExit(
        f"Build AltStore invalide: {entry.get('buildVersion')} != {expected_build}"
    )

download_url = entry.get("downloadURL", "")
if not download_url.endswith(".ipa"):
    raise SystemExit("Le downloadURL AltStore ne pointe pas vers un .ipa.")

if not data.get("name"):
    raise SystemExit("Le nom de la source AltStore est vide.")
PY

echo "Source AltStore valide: ${SOURCE_PATH}"
