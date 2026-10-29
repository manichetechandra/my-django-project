#!/usr/bin/env bash
# فحص لوحة الإدارة: لكل قسم في admin_config صفحة القائمة + الإضافة + تعديل أول سجل ترجع 200 دون أخطاء PHP
#   bash dev/admin_smoke.sh [http://127.0.0.1:8002] [/tmp/site2]
B=${1:-http://127.0.0.1:8002}
SITE=${2:-/tmp/site2}
PHP=${PHP_BIN:-/tmp/tools/bin/php}
JAR=$(mktemp)
tok=$(curl -s -c "$JAR" -b "$JAR" "$B/students/login/" | grep -o 'name="csrfmiddlewaretoken" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
curl -s -c "$JAR" -b "$JAR" -o /dev/null -X POST "$B/students/login/" \
  --data-urlencode "csrfmiddlewaretoken=$tok" --data-urlencode "username=${ADMIN_USER:-miloudsellam}" --data-urlencode "password=${ADMIN_PASS:-AdminDemo2026}"
# المسارات: من admin_config نفسه (القائمة، الإضافة إن كانت مسموحة، أول سجل)
URLS=$(cd "$SITE" && DB_NAME=${DB_NAME:-dou_setif2} $PHP -r '
require "app/bootstrap.php";
foreach (admin_resources() as $key => $r) {
    echo "/admin/$key/\n";
    if ($r["can_add"] ?? true) echo "/admin/$key/add/\n";
    $id = DB::value("SELECT id FROM `" . $r["model"]::table() . "` ORDER BY id LIMIT 1");
    if ($id) echo "/admin/$key/$id/\n";
}
echo "/admin/\n/admin/translate-all/\n/admin/password/\n";')
fail=0; n=0
for u in $URLS; do
  code=$(curl -s -b "$JAR" -o /tmp/admin_smoke.html -w "%{http_code}" "$B$u")
  n=$((n+1))
  if [ "$code" != 200 ] || grep -q "Fatal error\|Warning:\|Notice:\|Deprecated:\|Uncaught" /tmp/admin_smoke.html; then
    echo "✗ $code $u"; grep -o "Fatal error[^<]*\|Warning:[^<]*\|Uncaught[^<]*" /tmp/admin_smoke.html | head -2; fail=1
  fi
done
rm -f "$JAR"
[ $fail = 0 ] && echo "✓ لوحة الإدارة: $n صفحة تعمل"
exit $fail
