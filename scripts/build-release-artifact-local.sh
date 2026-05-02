#!/usr/bin/env bash
#
# Build dist/codeigniter-tutorial-<tag>.tar.gz using the same steps as .github/workflows/release.yml
# (composer prod deps → pnpm install → pnpm build → verify assets → scripts/package-release.sh).
#
# Usage:
#   ./scripts/build-release-artifact-local.sh              # tag defaults to local-release
#   ./scripts/build-release-artifact-local.sh v0.0.2-test
#

set -euo pipefail

TAG="${1:-local-release}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-.}")/.." && pwd)"
cd "${ROOT_DIR}"

printf '%s\n' "→ composer install --no-dev (matches release.yml)" >&2
composer install --no-dev --no-interaction --prefer-dist --no-progress

printf '%s\n' "→ pnpm install --no-frozen-lockfile" >&2
pnpm install --no-frozen-lockfile

printf '%s\n' "→ pnpm build" >&2
pnpm build

printf '%s\n' "→ verify frontend outputs" >&2
test -f public/assets/dist/css/tutorialStyle.css
test -f public/assets/dist/js/tutorial.js

printf '%s\n' "→ ./scripts/package-release.sh ${TAG}" >&2
./scripts/package-release.sh "${TAG}"

printf '%s\n' "Done: ${ROOT_DIR}/dist/codeigniter-tutorial-${TAG}.tar.gz" >&2
printf '%s\n' "Reminder: production Composer deps only — run composer install (no --no-dev) to restore dev tools locally." >&2
