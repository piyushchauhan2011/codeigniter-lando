#!/usr/bin/env bash
# Server-side deploy / rollback / bookkeeping. Streams over SSH stdin from deploy-digitalocean.sh.

set -euo pipefail

SHARED="${DEPLOY_APP_ROOT:?DEPLOY_APP_ROOT must be an absolute path}/shared"
RELEASES="${DEPLOY_APP_ROOT}/releases"
CURRENT="${DEPLOY_APP_ROOT}/current"

COMMAND="${DEPLOY_CLI_COMMAND:-deploy}"
KEEP="${DEPLOY_KEEP_RELEASES:-5}"
TOP_DIR="${DEPLOY_PKG_TOP_DIR:-codeigniter-tutorial}"

reload_web_server() {
  if [[ "${DEPLOY_APACHE:-}" == "false" ]]; then
    printf '%s\n' "Skipping reload (DEPLOY_APACHE=false)." >&2
    return 0
  fi

  if [[ -n "${DEPLOY_APACHE_CMD:-}" ]]; then
    bash -c "${DEPLOY_APACHE_CMD}"
    return $?
  fi

  if sudo -n systemctl reload apache2 2>/dev/null; then return 0; fi
  if sudo -n systemctl reload httpd 2>/dev/null; then return 0; fi
  if sudo -n /usr/sbin/apache2ctl graceful 2>/dev/null; then return 0; fi
  if sudo -n apachectl graceful 2>/dev/null; then return 0; fi

  printf '%s\n' "Could not reload Apache/httpd (passwordless sudo). Set DEPLOY_APACHE=false to skip." >&2
  return 1
}

ensure_layout() {
  mkdir -p "${SHARED}/writable/logs" "${SHARED}/writable/cache" "${SHARED}/writable/session"
  mkdir -p "${SHARED}/writable/uploads" "${SHARED}/writable/debugbar" "${RELEASES}"
  if [[ ! -f "${SHARED}/writable/index.html" ]]; then
    printf '%s\n' "Directory listing not allowed." >"${SHARED}/writable/index.html"
  fi
  for d in logs cache session uploads debugbar; do
    [[ -f "${SHARED}/writable/${d}/index.html" ]] ||
      printf '%s\n' "Directory listing not allowed." >"${SHARED}/writable/${d}/index.html"
  done

  [[ -f "${SHARED}/writable/.htaccess" ]] || cat <<'HTACCESS_EOF' >"${SHARED}/writable/.htaccess"
<IfModule authz_core_module>
	Require all denied
</IfModule>
<IfModule !authz_core_module>
	Deny from all
</IfModule>
HTACCESS_EOF
}

init_shared_only() {
  ensure_layout
  if [[ ! -f "${SHARED}/.env" ]]; then
    printf '%s\n' "Remember to sync ${SHARED}/.env from your secure template." >&2
  fi
  printf '%s\n' "Prepared ${SHARED}"
}

# Release dirs are named with UTC prefixes so lexical desc == newest deployments first.
_release_dirs_sorted_newest_lex() {
  [[ -d "${RELEASES}" ]] || return 0
  LC_ALL=C find "${RELEASES}" -mindepth 1 -maxdepth 1 \
    \( -type d -o -type l \) ! -name '.*' -printf '%f\n' |
    LC_ALL=C sort -r
}

_release_dirs_sorted_oldest_lex() {
  [[ -d "${RELEASES}" ]] || return 0
  LC_ALL=C find "${RELEASES}" -mindepth 1 -maxdepth 1 \
    \( -type d -o -type l \) ! -name '.*' -printf '%f\n' |
    LC_ALL=C sort
}

_release_count_visible() {
  find "${RELEASES}" -mindepth 1 -maxdepth 1 \
    \( -type d -o -type l \) ! -name '.*' 2>/dev/null | wc -l | tr -d ' '
}

absolute_current_release_dir() {
  if [[ ! -e "${CURRENT}" ]]; then printf ''; return 0; fi
  readlink -f "${CURRENT}"
}

cmd_releases() {
  printf 'CURRENT -> %s\n' "$(absolute_current_release_dir || true)"
  echo "--- releases (newest lexical first) ---"
  mapfile -t lines < <(_release_dirs_sorted_newest_lex)
  for row in "${lines[@]}"; do
    printf '%s\n' "${row}"
  done
}

prune_old_releases() {
  local active=""
  active="$(basename "$(absolute_current_release_dir)" 2>/dev/null)"
  [[ -z "${active}" ]] && active="__none__"

  local count=""
  count="$(_release_count_visible)"
  while (( count > KEEP )); do
    local oldest
    oldest="$( _release_dirs_sorted_oldest_lex | head -n 1)"
    [[ -z "${oldest}" ]] && break

    if [[ "${oldest}" == "${active}" ]]; then
      printf '%s\n' "Cannot prune ${oldest}: it stays active (${KEEP}). Remove stale dirs manually." >&2
      break
    fi

    printf '%s\n' "Removing release ${RELEASES}/${oldest}" >&2
    rm -rf "${RELEASES:?}/${oldest}"

    count="$(_release_count_visible)"
  done
}

smoke_if_configured_or_rollback() {
  local previous_target="$1"
  [[ -n "${DEPLOY_SMOKE_URL:-}" ]] || return 0

  sleep 1

  if curl -sfLo /dev/null --connect-timeout 3 --max-time 15 "${DEPLOY_SMOKE_URL}" 2>/dev/null; then
    return 0
  fi

  printf '%s\n' "Smoke check failed (${DEPLOY_SMOKE_URL}); rewiring CURRENT symlink to previous deployment." >&2
  if [[ -n "${previous_target}" ]] && [[ -d "${previous_target}" ]]; then
    ln -sfn "${previous_target}" "${CURRENT}"
    reload_web_server || true
  fi
  return 1
}

cleanup_tarball_maybe() {
  local path="${1:-}"
  [[ -n "${path}" ]] || return 0
  [[ -f "${path}" ]] || return 0

  local clean="${REMOTE_TARBALL_DELETE_AFTER_DEPLOY:-1}"
  [[ "${clean}" == "1" ]] || [[ "${clean}" == "true" ]] || return 0

  rm -f "${path}"
}

extract_tarball_into_release_dir() {
  local dest_abs="$1"
  local tarball_path="$2"

  local tmp_extract
  tmp_extract="$(mktemp -d "${RELEASES}/.extractXXXXXXXX")"

  trap 'rm -rf "${tmp_extract}"; trap - EXIT' EXIT
  tar -xzf "${tarball_path}" -C "${tmp_extract}"

  [[ -d "${tmp_extract}/${TOP_DIR}" ]] || {
    printf '%s\n' "Tarball missing top-level ${TOP_DIR}/ (see scripts/package-release.sh)." >&2
    rm -rf "${tmp_extract}"
    trap - EXIT
    exit 1
  }

  mkdir -p "${RELEASES}"
  if [[ -e "${dest_abs}" ]]; then
    printf '%s\n' "Destination already exists ${dest_abs}" >&2
    rm -rf "${tmp_extract}"
    trap - EXIT
    exit 1
  fi

  mv "${tmp_extract}/${TOP_DIR}" "${dest_abs}"
  rm -rf "${tmp_extract}"
  trap - EXIT

  rm -rf "${dest_abs}/writable"
  ln -sfn "${SHARED}/writable" "${dest_abs}/writable"
  rm -f "${dest_abs}/.env"
  ln -sfn "${SHARED}/.env" "${dest_abs}/.env"
}

cmd_deploy() {
  ensure_layout

  if [[ ! -f "${SHARED}/.env" ]]; then
    printf '%s\n' "Missing ${SHARED}/.env — copy production credentials there first." >&2
    exit 1
  fi

  local tarball_path=""
  if [[ -n "${REMOTE_TARBALL_LOCAL_PATH:-}" ]]; then
    tarball_path="${REMOTE_TARBALL_LOCAL_PATH}"
    [[ -f "${tarball_path}" ]] || {
      printf '%s\n' "${tarball_path} not found on disk." >&2
      exit 1
    }
  elif [[ -n "${REMOTE_TARBALL_DOWNLOAD_URL:-}" ]]; then
    tarball_path="$(mktemp "${RELEASES}/.downloadXXXXXXXX.tar.gz")"
    printf '%s\n' "Downloading archive…" >&2
    curl -fL "${REMOTE_TARBALL_DOWNLOAD_URL}" -o "${tarball_path}"
  else
    printf '%s\n' "REMOTE tarball env missing." >&2
    exit 1
  fi

  # GNU date %N yields monotonic sub-second ordering — critical for lexical sort == deploy order.
  local sort_key utc_human slug dest
  sort_key="$(date -u +%s%N 2>/dev/null || printf '%s%09d%s' "$(date -u +%s)" "$RANDOM" "$RANDOM")"
  utc_human="$(date -u +'%Y%m%d_%H%M%S')"
  slug="$(basename "${tarball_path}" .tar.gz | tr -cs '[:alnum:]_.-' '_')"
  if [[ -n "${REMOTE_DEPLOY_LABEL:-}" ]]; then
    dest="${RELEASES}/${sort_key}_${utc_human}_${REMOTE_DEPLOY_LABEL}"
  else
    dest="${RELEASES}/${sort_key}_${utc_human}_${slug}"
  fi

  local prev_target
  prev_target="$(absolute_current_release_dir)"

  printf '%s\n' "Deploying archive into ${dest}" >&2
  extract_tarball_into_release_dir "${dest}" "${tarball_path}"

  ln -sfn "${dest}" "${CURRENT}"

  printf '%s\n' "CURRENT -> $(readlink -f "${CURRENT}")" >&2

  if ! reload_web_server; then
    printf '%s\n' "Apache reload failure – reverting symlink." >&2
    if [[ -n "${prev_target}" && -d "${prev_target}" ]]; then
      ln -sfn "${prev_target}" "${CURRENT}"
      reload_web_server || true
    fi
    cleanup_tarball_maybe "${tarball_path}"
    exit 1
  fi

  if ! smoke_if_configured_or_rollback "${prev_target}"; then
    cleanup_tarball_maybe "${tarball_path}"
    printf '%s\n' "Rolling release directory ${dest} is still on disk for inspection." >&2
    exit 1
  fi

  prune_old_releases
  cleanup_tarball_maybe "${tarball_path}"
  printf '%s\n' "--- done ---" >&2
  cmd_releases
}

_cmd_rollback_with_active_base() {
  local active_base="$1"
  local target=""

  mapfile -t names < <(_release_dirs_sorted_newest_lex)
  for i in "${!names[@]}"; do
    if [[ "${names[i]}" == "${active_base}" ]] && [[ $(( i + 1 )) -lt ${#names[@]} ]]; then
      target="${names[$(( i + 1 ))]}"
      break
    fi
  done

  if [[ -z "${target}" ]]; then
    printf '%s\n' "No lexical older deployment after ${active_base}." >&2
    exit 1
  fi

  ln -sfn "${RELEASES}/${target}" "${CURRENT}"

  printf '%s\n' "CURRENT -> $(readlink -f "${CURRENT}")" >&2
  reload_web_server
}

cmd_rollback() {
  ensure_layout

  local active_base active_path
  active_path="$(absolute_current_release_dir)"
  [[ -n "${active_path}" && -d "${active_path}" ]] || {
    printf '%s\n' "CURRENT symlink missing or dangling." >&2
    exit 1
  }
  active_base="$(basename "${active_path}")"

  _cmd_rollback_with_active_base "${active_base}"
}

case "${COMMAND}" in
  init-shared) init_shared_only ;;
  releases)
    ensure_layout
    cmd_releases
    ;;
  rollback) cmd_rollback ;;
  deploy) cmd_deploy ;;
  *)
    printf '%s\n' "Unsupported DEPLOY_CLI_COMMAND (${COMMAND})." >&2
    exit 2
    ;;
esac
