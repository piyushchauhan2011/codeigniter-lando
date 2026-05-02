#!/usr/bin/env bash
# Tear down docker/deploy-local stack (does not delete docker/deploy-local/.ssh or shared.env).
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-.}")/.." && pwd)"
exec docker compose -f "${ROOT_DIR}/docker/deploy-local/docker-compose.yml" down "$@"
