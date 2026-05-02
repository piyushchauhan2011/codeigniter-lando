#!/usr/bin/env bash
#
# Starts docker/deploy-local: SSH + Apache/PHP mimic for deploy-digitalocean.sh rehearsal.
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-.}")/.." && pwd)"
DL="${ROOT_DIR}/docker/deploy-local"

mkdir -p "${DL}/.ssh"
if [[ ! -f "${DL}/.ssh/deploy" ]]; then
  ssh-keygen -t ed25519 -f "${DL}/.ssh/deploy" -N '' -C 'codeigniter-deploy-local'
fi
chmod 600 "${DL}/.ssh/deploy" 2>/dev/null || true
chmod 644 "${DL}/.ssh/deploy.pub"

if [[ ! -f "${DL}/shared.env" ]]; then
  cp "${DL}/shared.env.example" "${DL}/shared.env"
  printf '%s\n' "Created ${DL}/shared.env from example — edit app.baseURL / encryption.key / DB if needed." >&2
fi

SSH_CONF="${DL}/ssh_config"
cat >"${SSH_CONF}" <<EOF
Host codeigniter-local-deploy
  HostName 127.0.0.1
  Port 2222
  User deploy
  IdentityFile ${DL}/.ssh/deploy
  StrictHostKeyChecking accept-new
EOF
chmod 600 "${SSH_CONF}"

docker compose -f "${DL}/docker-compose.yml" build
docker compose -f "${DL}/docker-compose.yml" up -d

printf '\n%s\n' "Stack is up."
printf '%s\n' "  Site (host):     http://127.0.0.1:9080/"
printf '%s\n' "  SSH (host):      ssh -F ${SSH_CONF} codeigniter-local-deploy"
printf '%s\n' ""
printf '%s\n' "Exports for scripts/deploy-digitalocean.sh:"
printf '%s\n' "  export DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy"
printf '%s\n' "  export DEPLOY_SSH_CONFIG=${SSH_CONF}"
printf '%s\n' "  export DEPLOY_APP_ROOT=/srv/codeigniter-tutorial"
printf '%s\n' "  export DEPLOY_APACHE_CMD='sudo apachectl graceful'"
printf '%s\n' "  export DEPLOY_SMOKE_URL=http://127.0.0.1/hello"
printf '%s\n' ""
printf '%s\n' "MySQL runs with this stack (PHP uses hostname mysql). Host port for mysql CLI/tools: 127.0.0.1:3307"
printf '%s\n' "After deploy, migrations: make deploy-local-migrate   (or: pnpm deploy:local:migrate)"
printf '%s\n' ""
printf '%s\n' "Typical rehearsal:"
printf '%s\n' "  ./scripts/package-release.sh dev-local"
printf '%s\n' "  ./scripts/deploy-digitalocean.sh init-shared"
printf '%s\n' "  docker compose -f ${DL}/docker-compose.yml exec -u root deploy-target \\"
printf '%s\n' "    chown -R www-data:www-data /srv/codeigniter-tutorial/shared/writable"
printf '%s\n' "  ./scripts/deploy-digitalocean.sh deploy ./dist/codeigniter-tutorial-dev-local.tar.gz --label local"
printf '%s\n' ""
