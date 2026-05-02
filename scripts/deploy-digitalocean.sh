#!/usr/bin/env bash
#
# Wrapper: uploads (optional) tarball + streams scripts/deploy-remote-body.sh over SSH.
#
# Required env:
#   DEPLOY_SSH_TARGET   SSH user@host
#   DEPLOY_APP_ROOT     Absolute path ON THE SERVER (e.g. /var/www/codeigniter-tutorial)
#
# Optional env:
#   DEPLOY_SSH_CONFIG=/abs/path/to/ssh_config   # ssh -F / scp -F (local rehearsals)
#   DEPLOY_KEEP_RELEASES=5
#   DEPLOY_APACHE=false
#   DEPLOY_APACHE_CMD='sudo systemctl reload apache2'
#   DEPLOY_SMOKE_URL=http://127.0.0.1/hello       # curls after symlink; rollback on failure
#   DEPLOY_PKG_TOP_DIR=codeigniter-tutorial       # tarball top folder name
#   REMOTE_TARBALL_DELETE_AFTER_DEPLOY=0          # retain downloaded/tmp tarballs
#   DEPLOY_STRICT_HOST_KEY_CHECKING=accept-new
#
# One-time bootstrap on the Droplet filesystem:
#   ./scripts/deploy-digitalocean.sh init-shared   # prepares shared writable layout
# Copy production `.env` to `${DEPLOY_APP_ROOT}/shared/.env` manually.
#
# Deploy (downloads tarball on server, or SCP from your laptop):
#   ./scripts/deploy-digitalocean.sh deploy 'https://github.com/OWNER/REPO/releases/download/v1.0.0/codeigniter-tutorial-v1.0.0.tar.gz'
#   ./scripts/deploy-digitalocean.sh deploy ./path/to/codeigniter-tutorial-v1.0.0.tar.gz [--label prod]
#
# Roll back `current` symlink to the deployment immediately older than today's target:
#   ./scripts/deploy-digitalocean.sh rollback
#
# Inspect what is deployed:
#   ./scripts/deploy-digitalocean.sh releases

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-.}")/.." && pwd)"
REMOTE_BODY="${ROOT_DIR}/scripts/deploy-remote-body.sh"

usage() { sed -n '2,40p' "$0"; }

_require_target() {
  if [[ -z "${DEPLOY_SSH_TARGET:-}" ]]; then
    printf '%s\n' "DEPLOY_SSH_TARGET (user@host) is required." >&2
    usage >&2
    exit 1
  fi
}

_require_app_root() {
  if [[ -z "${DEPLOY_APP_ROOT:-}" ]]; then
    printf '%s\n' "DEPLOY_APP_ROOT must be an absolute directory path on the server." >&2
    usage >&2
    exit 1
  fi
}

_invoke_remote_env() {
  local -a ssh_cfg=()
  [[ -n "${DEPLOY_SSH_CONFIG:-}" ]] && ssh_cfg=( -F "${DEPLOY_SSH_CONFIG}" )
  # Pipe exports + remote script so values with spaces are not broken by ssh's remote-shell parsing.
  {
    printf 'export DEPLOY_CLI_COMMAND=%q\n' "${DEPLOY_CLI_COMMAND}"
    printf 'export DEPLOY_APP_ROOT=%q\n' "${DEPLOY_APP_ROOT}"
    printf 'export DEPLOY_KEEP_RELEASES=%q\n' "${DEPLOY_KEEP_RELEASES:-5}"
    printf 'export DEPLOY_APACHE_CMD=%q\n' "${DEPLOY_APACHE_CMD:-}"
    printf 'export DEPLOY_APACHE=%q\n' "${DEPLOY_APACHE:-}"
    printf 'export DEPLOY_SMOKE_URL=%q\n' "${DEPLOY_SMOKE_URL:-}"
    printf 'export DEPLOY_PKG_TOP_DIR=%q\n' "${DEPLOY_PKG_TOP_DIR:-codeigniter-tutorial}"
    printf 'export REMOTE_TARBALL_DOWNLOAD_URL=%q\n' "${REMOTE_TARBALL_DOWNLOAD_URL:-}"
    printf 'export REMOTE_TARBALL_LOCAL_PATH=%q\n' "${REMOTE_TARBALL_LOCAL_PATH:-}"
    printf 'export REMOTE_DEPLOY_LABEL=%q\n' "${REMOTE_DEPLOY_LABEL:-}"
    printf 'export REMOTE_TARBALL_DELETE_AFTER_DEPLOY=%q\n' "${REMOTE_TARBALL_DELETE_AFTER_DEPLOY:-1}"
    cat "${REMOTE_BODY}"
  } | ssh "${ssh_cfg[@]}" -o "StrictHostKeyChecking=${DEPLOY_STRICT_HOST_KEY_CHECKING:-accept-new}" \
      "${DEPLOY_SSH_TARGET}" bash -s --
}

CMD="${1:-}"
shift || true

case "${CMD}" in
  init-shared | releases | rollback)
    _require_target
    _require_app_root
    export DEPLOY_CLI_COMMAND="${CMD}"
    export REMOTE_TARBALL_DOWNLOAD_URL=""
    export REMOTE_TARBALL_LOCAL_PATH=""
    export REMOTE_DEPLOY_LABEL=""
    _invoke_remote_env
    ;;
  deploy)
    _require_target
    _require_app_root

    SOURCE="${1:-}"
    LABEL=""
    shift || true

    while (($#)); do
      case "$1" in
        --label)
          LABEL="${2:-}"
          shift 2 || exit 1
          ;;
        *)
          printf '%s\n' "unknown option: $1" >&2
          usage >&2
          exit 1
          ;;
      esac
    done

    [[ -n "${SOURCE}" ]] || {
      printf '%s\n' "Provide tarball URL or local tarball path." >&2
      usage >&2
      exit 1
    }

    export REMOTE_TARBALL_DOWNLOAD_URL=""
    export REMOTE_TARBALL_LOCAL_PATH=""
    if [[ "${SOURCE}" =~ ^https?:// ]]; then
      export REMOTE_TARBALL_DOWNLOAD_URL="${SOURCE}"
    elif [[ -f "${SOURCE}" ]]; then
      TMP="/tmp/ci-release-upload-$$-${RANDOM}.tar.gz"
      printf '%s\n' "Uploading ${SOURCE} -> ${DEPLOY_SSH_TARGET}:${TMP}" >&2
      scp_cfg=()
      [[ -n "${DEPLOY_SSH_CONFIG:-}" ]] && scp_cfg=( -F "${DEPLOY_SSH_CONFIG}" )
      scp "${scp_cfg[@]}" -q "${SOURCE}" "${DEPLOY_SSH_TARGET}:${TMP}"
      export REMOTE_TARBALL_LOCAL_PATH="${TMP}"
    else
      printf '%s\n' "deploy SOURCE must be an http(s) URL or existing local tarball path." >&2
      exit 1
    fi

    export DEPLOY_CLI_COMMAND=deploy
    export REMOTE_DEPLOY_LABEL="${LABEL}"
    _invoke_remote_env
    ;;
  -h | --help | '')
    usage
    ;;
  *)
    printf '%s\n' "unknown command: ${CMD}" >&2
    usage >&2
    exit 1
    ;;
esac
