#!/usr/bin/env bash
# On PR CI: checkout the PR base commit, run PHPUnit coverage for app/, then restore PR head.
# Expects: git repo, vendor/ at head, pcov, BASE_SHA and HEAD_SHA env vars.

set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

BASE_SHA="${BASE_SHA:?BASE_SHA missing}"
HEAD_SHA="${HEAD_SHA:?HEAD_SHA missing}"

git fetch --no-tags origin "${BASE_SHA}"

git checkout -f "${BASE_SHA}"
composer install --no-interaction --prefer-dist --no-progress

mkdir -p build
set +e
vendor/bin/phpunit \
	--coverage-filter app \
	--coverage-text=build/base-coverage-text.txt \
	--only-summary-for-coverage-text \
	>/dev/null 2>&1
set -e

if [[ ! -s build/base-coverage-text.txt ]]; then
	{
		echo "Code Coverage Report Summary:"
		echo "  Classes:  0.00% (0/0)"
		echo "  Methods:  0.00% (0/0)"
		echo "  Lines:    0.00% (0/0)"
	} >build/base-coverage-text.txt
fi

git checkout -f "${HEAD_SHA}"
composer install --no-interaction --prefer-dist --no-progress

exit 0
