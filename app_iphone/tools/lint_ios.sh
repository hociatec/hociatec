#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APP_DIR="${ROOT_DIR}/app_iphone"
SWIFTLINT_IMAGE="${SWIFTLINT_IMAGE:-ghcr.io/realm/swiftlint:latest}"
SWIFTLINT_CONFIG="${APP_DIR}/.swiftlint.yml"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker est requis pour lancer SwiftLint localement." >&2
  exit 1
fi

if [ ! -f "${SWIFTLINT_CONFIG}" ]; then
  echo "Configuration SwiftLint introuvable: ${SWIFTLINT_CONFIG}" >&2
  exit 1
fi

docker run --rm \
  -v "${ROOT_DIR}:${ROOT_DIR}" \
  -w "${APP_DIR}" \
  "${SWIFTLINT_IMAGE}" \
  swiftlint lint --strict --config .swiftlint.yml
