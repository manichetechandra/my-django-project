#!/usr/bin/env bash
# الموقع التجريبي للنسخة 2.0: كود php/ + بيانات الكتاب التجريبية + ترجمتها (dev/demo-i18n.sql)
#   bash dev/demo-site.sh [/tmp/site2]        (قاعدة dou_setif2، والخادم يعمل على المجلد نفسه)
set -euo pipefail
cd "$(dirname "$0")/.."
SITE=${1:-/tmp/site2}
export DB_NAME=${DB_NAME:-dou_setif2}
( cd book && SRC_DIR="$(cd ../php && pwd)" bash tools/demo-site.sh "$SITE" )
${PHP_BIN:-/tmp/tools/bin/php} -r '
$m = new mysqli("127.0.0.1", "root", "", getenv("DB_NAME"), 3306);
$m->set_charset("utf8mb4");
$n = 0; $miss = [];
foreach (preg_split("/;\n/", file_get_contents($argv[1])) as $sql) {
    $sql = trim($sql);
    if (!str_starts_with($sql, "UPDATE")) { if ($sql !== "" && !str_starts_with($sql, "--")) $m->query($sql); continue; }
    $m->query($sql);
    if ($m->affected_rows < 1) $miss[] = substr($sql, 0, 120);
    $n++;
}
echo "ترجمة المحتوى التجريبي: $n سجلاً" . ($miss ? "، لم تُطبَّق على:\n  " . implode("\n  ", $miss) : "") . "\n";
' dev/demo-i18n.sql
