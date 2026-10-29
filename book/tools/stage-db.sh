#!/usr/bin/env bash
# قاعدة dou_stage كما تكون عند القارئ في خطوة معينة: schema.sql (stages/db.src) + البيانات التجريبية (stages/sample.src)
set -euo pipefail
cd "$(dirname "$0")/.."
STEP=${1:?الخطوة}
T=$(mktemp -d)
node tools/stages.mjs db "$STEP" "$T" >/dev/null
node tools/stages.mjs sample "$STEP" "$T" >/dev/null 2>&1 || true
FILES=("$T/database/schema.sql")
[ -f "$T/database/sample_data.sql" ] && FILES+=("$T/database/sample_data.sql")
${PHP_BIN:-/tmp/tools/bin/php} tools/stage-db.php dou_stage "${FILES[@]}"
rm -rf "$T"
