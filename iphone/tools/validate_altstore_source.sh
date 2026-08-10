#!/usr/bin/env bash

set -euo pipefail

if [ "$#" -ne 3 ]; then
  echo "Usage: $0 path/to/source.json expected_version expected_build" >&2
  exit 1
fi

SOURCE_PATH="$1"
EXPECTED_VERSION="$2"
EXPECTED_BUILD="$3"

if [ ! -f "${SOURCE_PATH}" ]; then
  echo "Source AltStore introuvable: ${SOURCE_PATH}" >&2
  exit 1
fi

python3 - "$SOURCE_PATH" "$EXPECTED_VERSION" "$EXPECTED_BUILD" <<'PY'
import json
import sys

source_path, expected_version, expected_build = sys.argv[1:4]

with open(source_path, "r", encoding="utf-8") as handle:
    payload = json.load(handle)

apps = payload.get("apps")
if not isinstance(apps, list) or not apps:
    raise SystemExit("Source AltStore invalide: apps absent ou vide.")

app = apps[0]
versions = app.get("versions")
if not isinstance(versions, list) or not versions:
    raise SystemExit("Source AltStore invalide: versions absent ou vide.")

version_entry = versions[0]

bundle_identifier = app.get("bundleIdentifier")
version = version_entry.get("version")
build = str(version_entry.get("buildVersion"))
download_url = version_entry.get("downloadURL")
size = version_entry.get("size")

if bundle_identifier != "fr.hociatec.hociatecMobile":
    raise SystemExit("Source AltStore invalide: bundleIdentifier inattendu.")

if version != expected_version:
    raise SystemExit(
        f"Source AltStore invalide: version attendue {expected_version}, obtenue {version}."
    )

if build != expected_build:
    raise SystemExit(
        f"Source AltStore invalide: build attendu {expected_build}, obtenu {build}."
    )

if not isinstance(download_url, str) or not download_url.endswith(".ipa"):
    raise SystemExit("Source AltStore invalide: downloadURL manquant ou invalide.")

if not isinstance(size, int) or size <= 0:
    raise SystemExit("Source AltStore invalide: size manquant ou invalide.")

print(
    f"Source AltStore valide: version {version} ({build}), bundle {bundle_identifier}."
)
PY
