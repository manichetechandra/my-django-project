#!/usr/bin/env bash
# موقع تجريبي كامل للقطات الشاشة: نسخة من كود الكتاب (النسخة 1.0 من dou-setif-php.zip) في /tmp/site
# + صور demo/uploads + قاعدة dou_setif ببيانات demo-seed.php
#   لموقع من مجلد آخر (مثل النسخة 2.0 في php/):  SRC_DIR=../php bash tools/demo-site.sh /tmp/site2
#   bash tools/demo-site.sh            ثم شغّل الخادم (عملية دائمة):
#   cd /tmp/site && /tmp/tools/bin/php -S 0.0.0.0:8000 index.php
set -euo pipefail
cd "$(dirname "$0")/.."
SITE=${1:-/tmp/site}
# نفرّغ المجلد دون حذفه، حتى يبقى خادم php -S الذي يعمل فيه صالحاً
mkdir -p "$SITE"
find "$SITE" -mindepth 1 -delete
SRC=${SRC_DIR:-$(node tools/project.mjs)}
cp -r "$SRC/." "$SITE/"
rm -rf "$SITE/storage/sessions/"*
mkdir -p "$SITE/storage/sessions" "$SITE/storage/logs" "$SITE/storage/private"
cp -r demo/uploads/. "$SITE/uploads/"
${PHP_BIN:-/tmp/tools/bin/php} tools/demo-seed.php "$SITE"
