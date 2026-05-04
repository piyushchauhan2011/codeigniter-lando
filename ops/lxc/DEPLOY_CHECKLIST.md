# Deploy checklist (LXC / VPS)

Use before and after each release to practice disciplined rollouts.

## Pre-flight

- [ ] Tagged Git revision matches artifact / `current` checkout.
- [ ] `.env` on shared volume: `CI_ENVIRONMENT=production`, correct `database.*`, `app.baseURL` HTTPS origin.
- [ ] `writable/` owned by php-fpm user; logs directory writable.

## Deploy

- [ ] Enable maintenance mode **only if** your playbook requires it (optional for lab).
- [ ] Extract tarball or sync Git into `releases/<id>/`.
- [ ] `composer install --no-dev --optimize-autoloader` inside release (if not baked into artifact).
- [ ] Symlink `current` → new release.
- [ ] `php spark migrate --all` (expand/contract migrations rehearsed beforehand).
- [ ] `php spark cache:clear` as applicable.
- [ ] Reload php-fpm (`graceful` / `restart`).
- [ ] Restart queue worker systemd unit.

## Smoke

- [ ] `curl -fsS https://your-host/hello`
- [ ] Login + employer dashboard loads.
- [ ] Queue depth drains (`queue` metrics or logs).

## Rollback

- [ ] Point `current` at previous release.
- [ ] Reload php-fpm and restart worker.
- [ ] If schema migrated forward irreversibly, restore DB from backup taken **before** deploy.
