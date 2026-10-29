#!/usr/bin/env bash
# فحص سريع: كل الصفحات العامة بكل لغة ترجع 200 (أو 404 للصفحة غير الموجودة) دون أخطاء PHP
#   bash dev/smoke.sh [http://127.0.0.1:8002]
B=${1:-http://127.0.0.1:8002}
PAGES="/ /director-word/ /organization/ /history/ /news/ /news/sarf-al-minha/ /activities/ /activities/dawrat-korat-al-qadam/ /departements/ /departements/maslahat-al-minah/ /cities/ /cities/debaghine/ /services/ /services/type/housing/ /services/naql-jamii/ /contact/ /downloads/ /downloads/category/scholarship_forms/ /faq/ /faq/3/ /search/?q=%D8%A7%D9%84%D8%A5%D9%82%D8%A7%D9%85%D8%A9 /search/?q=bourse /students/register/ /students/login/ /no-such-page/"
fail=0
for L in "" /fr /en; do
  for p in $PAGES; do
    code=$(curl -s -o /tmp/smoke.html -w "%{http_code}" "$B$L$p")
    want=200; [ "$p" = "/no-such-page/" ] && want=404
    if [ "$code" != "$want" ] || grep -q "Fatal error\|Warning:\|Notice:\|Deprecated:\|Uncaught" /tmp/smoke.html; then
      echo "✗ $code $L$p"; grep -o "Fatal error[^<]*\|Warning:[^<]*\|Uncaught[^<]*" /tmp/smoke.html | head -2; fail=1
    fi
  done
done
[ $fail = 0 ] && echo "✓ كل الصفحات تعمل بالعربية والفرنسية والإنجليزية"
