#!/usr/bin/env bash
# نسخ كود php/ إلى الموقع التجريبي (دون لمس uploads/ و storage/ وقاعدة البيانات)
#   bash dev/sync.sh [/tmp/site2]
set -euo pipefail
cd "$(dirname "$0")/.."
SITE=${1:-/tmp/site2}
( cd php && git ls-files -co --exclude-standard | grep -v '^uploads/\|^storage/' ) | while read -r f; do
  mkdir -p "$SITE/$(dirname "$f")"
  cp "php/$f" "$SITE/$f"
done
echo "sync: php/ → $SITE"
