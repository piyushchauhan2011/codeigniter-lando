#!/usr/bin/env bash
# Build markdown for the sticky PR comment (coverage + PhpMetrics + optional base diff).
#
# Inputs:
#   $1 = output path (default: build/pr-comment.md)
#
# Expected files:
#   COVERAGE_TXT (default: build/coverage-text.txt) — PR head summary from PHPUnit --coverage-text
#   build/phpmetrics-summary.json
#
# Optional PHPUnit split (unit vs feature):
#   PHPUNIT_JUNIT (default: build/phpunit-junit.xml) — from composer test:coverage --log-junit
#
# Optional for +/- lines vs PR base:
#   BASE_COVERAGE_TXT (default: build/base-coverage-text.txt if readable)
#   BASE_SHA (optional, short label in table)

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COVERAGE_TXT="${COVERAGE_TXT:-${ROOT}/build/coverage-text.txt}"
METRICS_JSON="${ROOT}/build/phpmetrics-summary.json"
OUT="${1:-${ROOT}/build/pr-comment.md}"

COVERAGE_PR_LABEL="${COVERAGE_PR_LABEL:-This PR}"
COVERAGE_BASE_LABEL="${COVERAGE_BASE_LABEL:-PR base}"

extract_line_metric() {
	local file="$1"
	local line pct covered total
	if [[ ! -f "$file" ]]; then
		echo "0 0 0"
		return
	fi
	line=$(grep 'Lines:' "$file" | head -1 | tr -d '\r' || true)
	if [[ -z "$line" ]]; then
		echo "0 0 0"
		return
	fi
	pct=$(echo "$line" | sed -n 's/.*Lines:[[:space:]]*\([0-9.]*\)%.*/\1/p')
	covered=$(echo "$line" | sed -n 's/.*(\([0-9]*\)\/.*/\1/p')
	total=$(echo "$line" | sed -n 's/.*\/\([0-9]*\)).*/\1/p')
	[[ -z "$pct" ]] && pct="0"
	[[ -z "$covered" ]] && covered="0"
	[[ -z "$total" ]] && total="0"
	echo "$pct $covered $total"
}

if [[ ! -f "$COVERAGE_TXT" ]]; then
	echo "Missing ${COVERAGE_TXT}" >&2
	exit 1
fi
if [[ ! -f "$METRICS_JSON" ]]; then
	echo "Missing ${METRICS_JSON}" >&2
	exit 2
fi

BASE_FILE="${BASE_COVERAGE_TXT:-${ROOT}/build/base-coverage-text.txt}"
if [[ ! -r "$BASE_FILE" ]]; then
	BASE_FILE=""
fi

read -r PR_PCT PR_COV PR_TOT <<<"$(extract_line_metric "$COVERAGE_TXT")"
if [[ -n "$BASE_FILE" ]]; then
	read -r BASE_PCT BASE_COV BASE_TOT <<<"$(extract_line_metric "$BASE_FILE")"
else
	BASE_PCT=""; BASE_COV=""; BASE_TOT=""
fi

SHA_SHORT="${GITHUB_SHA:-local}"
RUN_LINK=""
if [[ -n "${GITHUB_SERVER_URL:-}" && -n "${GITHUB_REPOSITORY:-}" && -n "${GITHUB_RUN_ID:-}" ]]; then
	RUN_LINK="${GITHUB_SERVER_URL}/${GITHUB_REPOSITORY}/actions/runs/${GITHUB_RUN_ID}"
fi

PHPUNIT_JUNIT="${PHPUNIT_JUNIT:-${ROOT}/build/phpunit-junit.xml}"
PHPUNIT_UNIT_TESTS=0
PHPUNIT_UNIT_ASSERTIONS=0
PHPUNIT_FEATURE_TESTS=0
PHPUNIT_FEATURE_ASSERTIONS=0
if [[ -f "$PHPUNIT_JUNIT" ]]; then
	eval "$(php "${ROOT}/scripts/ci-phpunit-junit-split.php" "$PHPUNIT_JUNIT")"
fi
PHPUNIT_SPLIT_TOTAL=$((PHPUNIT_UNIT_TESTS + PHPUNIT_FEATURE_TESTS))

SIGN_MARK_LINE_DELTA() {
	local d=$((PR_COV - BASE_COV))
	if ((d > 0)); then echo "+${d}"; elif ((d < 0)); then echo "${d}"; else echo "0"; fi
}

SIGN_MARK_TOTAL_DELTA() {
	local d=$((PR_TOT - BASE_TOT))
	if ((d > 0)); then echo "+${d}"; elif ((d < 0)); then echo "${d}"; else echo "0"; fi
}

PP_DELTA_STR() {
	if [[ -z "$BASE_FILE" ]]; then
		echo "—"
		return
	fi
	awk -v h="$PR_PCT" -v b="$BASE_PCT" 'BEGIN {
    d = h - b
    if (d > 0) printf "+%.2f pp", d
    else if (d < 0) printf "%.2f pp", d
    else printf "±0 pp"
  }'
}

{
	echo "## PHP test coverage & metrics"
	echo ""
	echo "_Updated automatically for commit \`${SHA_SHORT:0:7}\`._"
	if [[ -n "$RUN_LINK" ]]; then
		echo "_[View workflow run](${RUN_LINK})_."
	fi
	echo ""

	if [[ -n "$BASE_FILE" && -n "${BASE_SHA:-}" ]]; then
		echo "### Coverage vs PR target (\`${BASE_SHA:0:7}\`)"
	else
		echo "### Coverage vs PR target"
	fi
	echo ""
	echo "| | Covered lines | Executable lines | Line % |"
	echo "| :--- | ---: | ---: | ---: |"
	if [[ -n "$BASE_FILE" ]]; then
		echo "| **${COVERAGE_PR_LABEL}** | ${PR_COV} | ${PR_TOT} | ${PR_PCT}% |"
		echo "| **${COVERAGE_BASE_LABEL}** | ${BASE_COV} | ${BASE_TOT} | ${BASE_PCT}% |"
		echo "| **Δ (this PR)** | $(SIGN_MARK_LINE_DELTA) | $(SIGN_MARK_TOTAL_DELTA) | $(PP_DELTA_STR) |"
	else
		echo "| **${COVERAGE_PR_LABEL}** | ${PR_COV} | ${PR_TOT} | ${PR_PCT}% |"
	fi
	echo ""
	if [[ -f "$PHPUNIT_JUNIT" && "${PHPUNIT_SPLIT_TOTAL:-0}" -gt 0 ]]; then
		echo "### PHPUnit — unit vs feature"
		echo ""
		echo "| Layer | Tests | Assertions | Role |"
		echo "| :--- | ---: | ---: | :--- |"
		echo "| **Unit** (\`tests/unit/\`) | ${PHPUNIT_UNIT_TESTS} | ${PHPUNIT_UNIT_ASSERTIONS} | Fast, isolated checks (no full app/HTTP boot in this repo’s suite). |"
		echo "| **Feature / integration** (\`tests/feature/\`) | ${PHPUNIT_FEATURE_TESTS} | ${PHPUNIT_FEATURE_ASSERTIONS} | Full stack: CodeIgniter bootstrap, HTTP via \`FeatureTestTrait\`, database + migrations where tests use \`DatabaseTestTrait\`. |"
		echo ""
	fi
	echo "<details>"
	echo "<summary>PHPUnit text summary (PR head, <code>app/</code> filter)</summary>"
	echo ""
	echo '```'
	cat "$COVERAGE_TXT"
	echo '```'
	echo ""
	echo "</details>"
	echo ""

	echo "### PhpMetrics summary (\`app/\`, \`scripts/\`)"
	echo ""

	if command -v jq >/dev/null 2>&1; then
		jq -r '
      def row(k; v): "| **" + k + "** | " + (v | tostring) + " |";
      "| Metric | Value |\n| --- | --- |",
      row("Classes"; .OOP.classes),
      row("Methods"; .OOP.methods),
      row("Logical LOC"; .LOC.logicalLinesOfCode),
      row("Avg cyclomatic complexity / class"; .Complexity.avgCyclomaticComplexityByClass),
      row("Violations (critical / error / warning)"; "\(.Violations.critical) / \(.Violations.error) / \(.Violations.warning)")
    ' "$METRICS_JSON"
	else
		echo '(jq not available; raw JSON below)'
		echo ""
		echo '```json'
		cat "$METRICS_JSON"
		echo '```'
	fi

	echo ""
	echo "### CI checks — run locally"
	echo ""
	if [[ -n "${GITHUB_REPOSITORY:-}" ]]; then
		echo "Commands mirror **[.github/workflows/ci.yml](https://github.com/${GITHUB_REPOSITORY}/blob/main/.github/workflows/ci.yml)** (PHP 8.2 + Node 22 / pnpm)."
	else
		echo "Commands mirror **.github/workflows/ci.yml** (PHP 8.2 + Node 22 / pnpm)."
	fi
	echo ""
	echo "| Job | What | Command |"
	echo "| --- | --- | --- |"
	echo "| PHP | Syntax | \`composer ci:php:lint\` |"
	echo "| PHP ✓ | Static analysis | \`composer phpstan:check\` |"
	echo "| PHP ✓ | Tests | \`composer test\` |"
	echo "| Frontend | TypeScript | \`pnpm typecheck\` |"
	echo "| Frontend ✓ | Format | \`pnpm format:check\` (fix: \`pnpm format\`) |"
	echo "| Frontend ✓ | JS/TS lint | \`pnpm lint\` |"
	echo "| Frontend ✓ | SCSS lint | \`pnpm lint:css\` (fix: \`pnpm lint:css:fix\`) |"
	echo "| Frontend ✓ | All linters | \`pnpm lint:all\` |"
	echo "| Frontend ✓ | Vitest | \`pnpm test\` |"
	echo "| Frontend ✓ | Production build | \`pnpm build\` |"
	echo ""
	echo "**Reading CI results:** open the PR’s *Checks* tab → job → **Summary** for pass/fail wrap-up and troubleshooting tables."
	echo ""
} >"$OUT"

echo "Wrote ${OUT}"
