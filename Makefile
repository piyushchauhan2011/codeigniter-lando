# Local deploy rehearsal against docker/deploy-local (see README "Local Docker rehearsal").
.PHONY: deploy-local-up deploy-local-down deploy-local-wait-ssh deploy-local-init deploy-local-chown-writable \
	deploy-local-fetch-artifact deploy-local-deploy-url deploy-local-deploy-file deploy-local-demo \
	deploy-local-package-from-workspace deploy-local-demo-built \
	deploy-local-build-release-ci deploy-local-demo-ci deploy-local-migrate

DOCKER_COMPOSE_LOCAL := docker compose -f docker/deploy-local/docker-compose.yml

# Override to test another tag, e.g. make deploy-local-demo DEPLOY_LOCAL_RELEASE_URL=... DEPLOY_LOCAL_ARTIFACT=dist/releases/codeigniter-tutorial-v0.0.2.tar.gz
DEPLOY_LOCAL_RELEASE_URL ?= https://github.com/piyushchauhan2011/codeigniter-lando/releases/download/v0.0.1/codeigniter-tutorial-v0.0.1.tar.gz
DEPLOY_LOCAL_ARTIFACT ?= dist/releases/codeigniter-tutorial-v0.0.1.tar.gz
# Tag for workspace-built tarball (fast path: pnpm only; see deploy-local-demo-built)
DEPLOY_LOCAL_PACKAGE_TAG ?= workspace-local
# Tag / filename suffix for CI-parity tarball (see scripts/build-release-artifact-local.sh + deploy-local-demo-ci)
RELEASE_ARTIFACT_TAG ?= local-release

deploy-local-up:
	./scripts/deploy-local-up.sh

deploy-local-down:
	./scripts/deploy-local-down.sh

deploy-local-wait-ssh:
	@echo "Waiting for deploy-target SSH…"
	@for i in $$(seq 1 45); do \
		ssh -F "$(CURDIR)/docker/deploy-local/ssh_config" -o BatchMode=yes -o ConnectTimeout=3 \
			codeigniter-local-deploy true 2>/dev/null && exit 0; \
		sleep 1; \
	done; \
	echo "SSH on port 2222 did not become ready." >&2; exit 1

deploy-local-init:
	DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy \
	DEPLOY_SSH_CONFIG=$(CURDIR)/docker/deploy-local/ssh_config \
	DEPLOY_APP_ROOT=/srv/codeigniter-tutorial \
	DEPLOY_APACHE_CMD='sudo apachectl graceful' \
	DEPLOY_SMOKE_URL=http://127.0.0.1/hello \
	./scripts/deploy-digitalocean.sh init-shared

deploy-local-chown-writable:
	$(DOCKER_COMPOSE_LOCAL) exec -u root deploy-target \
	  chown -R www-data:www-data /srv/codeigniter-tutorial/shared/writable

deploy-local-fetch-artifact:
	mkdir -p dist/releases
	curl -fL "$(DEPLOY_LOCAL_RELEASE_URL)" -o "$(DEPLOY_LOCAL_ARTIFACT)"

deploy-local-deploy-url:
	DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy \
	DEPLOY_SSH_CONFIG=$(CURDIR)/docker/deploy-local/ssh_config \
	DEPLOY_APP_ROOT=/srv/codeigniter-tutorial \
	DEPLOY_APACHE_CMD='sudo apachectl graceful' \
	DEPLOY_SMOKE_URL=http://127.0.0.1/hello \
	./scripts/deploy-digitalocean.sh deploy "$(DEPLOY_LOCAL_RELEASE_URL)"

deploy-local-deploy-file:
	DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy \
	DEPLOY_SSH_CONFIG=$(CURDIR)/docker/deploy-local/ssh_config \
	DEPLOY_APP_ROOT=/srv/codeigniter-tutorial \
	DEPLOY_APACHE_CMD='sudo apachectl graceful' \
	DEPLOY_SMOKE_URL=http://127.0.0.1/hello \
	./scripts/deploy-digitalocean.sh deploy "$(CURDIR)/$(DEPLOY_LOCAL_ARTIFACT)"

deploy-local-migrate:
	@echo "spark migrate --all (uses hostname mysql inside compose network)…"
	$(DOCKER_COMPOSE_LOCAL) exec deploy-target bash -lc 'cd "$$(readlink -f /srv/codeigniter-tutorial/current)" && php spark migrate --all'

# Bring stack up, bootstrap shared dirs, download GitHub Release tarball locally, SCP deploy into container.
deploy-local-demo: deploy-local-up deploy-local-wait-ssh deploy-local-init deploy-local-chown-writable deploy-local-fetch-artifact deploy-local-deploy-file
	$(MAKE) deploy-local-migrate

# Same as deploy-local-demo but builds Vite assets only (frozen lockfile), packs — skips composer reinstall (faster).
deploy-local-package-from-workspace:
	pnpm install --frozen-lockfile
	pnpm build
	./scripts/package-release.sh "$(DEPLOY_LOCAL_PACKAGE_TAG)"

deploy-local-demo-built: deploy-local-up deploy-local-wait-ssh deploy-local-init deploy-local-chown-writable deploy-local-package-from-workspace
	DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy \
	DEPLOY_SSH_CONFIG=$(CURDIR)/docker/deploy-local/ssh_config \
	DEPLOY_APP_ROOT=/srv/codeigniter-tutorial \
	DEPLOY_APACHE_CMD='sudo apachectl graceful' \
	DEPLOY_SMOKE_URL=http://127.0.0.1/hello \
	./scripts/deploy-digitalocean.sh deploy "$(CURDIR)/dist/codeigniter-tutorial-$(DEPLOY_LOCAL_PACKAGE_TAG).tar.gz"
	$(MAKE) deploy-local-migrate

# Build tarball like .github/workflows/release.yml (composer --no-dev, pnpm install/build, package-release.sh).
deploy-local-build-release-ci:
	./scripts/build-release-artifact-local.sh "$(RELEASE_ARTIFACT_TAG)"

# Full stack + CI-parity tarball + deploy into docker/deploy-local.
deploy-local-demo-ci: deploy-local-up deploy-local-wait-ssh deploy-local-init deploy-local-chown-writable deploy-local-build-release-ci
	DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy \
	DEPLOY_SSH_CONFIG=$(CURDIR)/docker/deploy-local/ssh_config \
	DEPLOY_APP_ROOT=/srv/codeigniter-tutorial \
	DEPLOY_APACHE_CMD='sudo apachectl graceful' \
	DEPLOY_SMOKE_URL=http://127.0.0.1/hello \
	./scripts/deploy-digitalocean.sh deploy "$(CURDIR)/dist/codeigniter-tutorial-$(RELEASE_ARTIFACT_TAG).tar.gz"
	$(MAKE) deploy-local-migrate
