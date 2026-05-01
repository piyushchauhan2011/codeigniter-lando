#!/usr/bin/env bash
# Build markdown for the sticky PR comment (coverage + PhpMetrics).
# Expects: build/coverage-text.txt, build/phpmetrics-summary.json
# Env (optional): GITHUB_SHA, GITHUB_SERVER_URL, GITHUB_REPOSITORY, GITHUB_RUN_ID

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COVERAGE_TXT="${ROOT}/build/coverage-text.txt"
METRICS_JSON="${ROOT}/build/phpmetrics-summary.json"
OUT="${1:-${ROOT}/build/pr-comment.md}"

if [[ ! -f "$COVERAGE_TXT" ]]; then
	echo "Missing ${COVERAGE_TXT}" >&2
	exit 1
fi
if [[ ! -f "$METRICS_JSON" ]]; then
	echo "Missing ${METRICS_JSON}" >&2
	exit 2
fi

SHA_SHORT="${GITHUB_SHA:-local}"
RUN_LINK=""
if [[ -n "${GITHUB_SERVER_URL:-}" && -n "${GITHUB_REPOSITORY:-}" && -n "${GITHUB_RUN_ID:-}" ]]; then
	RUN_LINK="${GITHUB_SERVER_URL}/${GITHUB_REPOSITORY}/actions/runs/${GITHUB_RUN_ID}"
fi

{
	echo "## PHP test coverage & metrics"
	echo ""
	echo "_Updated automatically for commit \`${SHA_SHORT:0:7}\`._"
	if [[ -n "$RUN_LINK" ]]; then
		echo "_[View workflow run](${RUN_LINK})_."
	fi
	echo ""
	echo "### PHPUnit coverage (lines, \`app/\` filter)"
	echo ""
	echo '```'
	cat "$COVERAGE_TXT"
	echo '```'
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
} >"$OUT"

echo "Wrote ${OUT}"
