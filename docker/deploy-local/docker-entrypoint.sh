#!/usr/bin/env bash
set -euo pipefail

APP_ROOT=/srv/codeigniter-tutorial
BOOT="${APP_ROOT}/_bootstrap"

mkdir -p "${APP_ROOT}/shared"

mkdir -p "${BOOT}/public"
if [[ ! -f "${BOOT}/public/index.php" ]]; then
  printf '%s\n' '<?php echo "Local deploy sandbox (stub): run init-shared then deploy.\n";' \
    >"${BOOT}/public/index.php"
fi

# Symlink so the first deploy rollback can restore a non-circular previous target (not a real current/ dir).
ln -sfn "${BOOT}" "${APP_ROOT}/current"

chown -R deploy:deploy "${APP_ROOT}" 2>/dev/null || true

mkdir -p /var/run/sshd
if [[ -f /tmp/deploy.pub ]]; then
  cp /tmp/deploy.pub /home/deploy/.ssh/authorized_keys
  chown deploy:deploy /home/deploy/.ssh/authorized_keys
  chmod 600 /home/deploy/.ssh/authorized_keys
fi

if grep -q '^#*PasswordAuthentication' /etc/ssh/sshd_config; then
  sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
else
  printf '%s\n' 'PasswordAuthentication no' >>/etc/ssh/sshd_config
fi
if grep -q '^#*PermitRootLogin' /etc/ssh/sshd_config; then
  sed -i 's/^#*PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
else
  printf '%s\n' 'PermitRootLogin no' >>/etc/ssh/sshd_config
fi

/usr/sbin/sshd

exec "$@"
