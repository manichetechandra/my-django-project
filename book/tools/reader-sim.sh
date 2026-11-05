#!/usr/bin/env bash
# محاكاة مشروع القارئ في نهاية فصل معيّن، لتجربة كود الكتاب في صندوق العمل (ليست للقارئ).
#   bash tools/reader-sim.sh 34 [/tmp/ch24sim]
# يبني المجلد من php/ (النسخة النهائية) مع حذف ما لم يُكتب بعد حتى ذلك الفصل:
#   ملفات core الفارغة، المتحكمات والقوالب اللاحقة، أجزاء students.php و forms.php وملفَي اللغة،
#   وقطع المسابقات في الرئيسية وشريط التنقل (الفصل 45).
# ثم الخادم (يحاكي .htaccess تحت /dou-setif):
#   DB_NAME=dou_setif2 /tmp/tools/bin/php -S 0.0.0.0:8077 -t /tmp/simroot /tmp/simroot/router.php
set -euo pipefail
N=${1:?رقم الفصل}
SIM=${2:-/tmp/ch24sim}
BOOK=$(cd "$(dirname "$0")/.." && pwd)
P=$(cd "$BOOK/../php" && pwd)

mkdir -p "$SIM"
# نفرّغ المجلد دون حذفه، حتى يبقى خادم php -S الذي يعمل عليه صالحاً
find "$SIM" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
cp -a "$P/." "$SIM/"
cd "$SIM"
find storage -mindepth 2 -type f ! -name .gitkeep -delete
[ "$N" -ge 41 ] || rm -rf assets/vendor

empty() { for f in "$@"; do printf '<?php\n' > "$f"; done; }
cut_lines() { # cut_lines FILE "sed-delete-expr"
  sed -i "$2" "$1"
}
keep_lines() { # keep_lines FILE A-B
  local a=${2%-*} b=${2#*-}
  sed -n "${a},${b}p" "$P/$1" > "$1"
}

# --- core: الأصناف التي لم تُكتب بعد تبقى ملفات فارغة (أنشأها الفصل 16)
[ "$N" -ge 34 ] || empty app/core/Barcode.php
[ "$N" -ge 36 ] || empty app/core/XlsxWriter.php
[ "$N" -ge 40 ] || empty app/core/Translator.php
[ "$N" -ge 43 ] || empty app/core/Crypto.php
[ "$N" -ge 44 ] || { empty app/core/PdfGuard.php app/models/recruitment.php; }
[ "$N" -ge 42 ] || empty app/admin_config.php

# --- المتحكمات
[ "$N" -ge 36 ] || rm -f app/controllers/admin.php app/controllers/staff.php assets/css/admin.css
[ "$N" -ge 45 ] || rm -f app/controllers/competitions.php
[ "$N" -ge 47 ] || rm -f app/controllers/recruitment_admin.php
if   [ "$N" -lt 33 ]; then rm -f app/controllers/students.php
elif [ "$N" -lt 34 ]; then keep_lines app/controllers/students.php 1-156
elif [ "$N" -lt 35 ]; then keep_lines app/controllers/students.php 1-394
fi
if   [ "$N" -lt 33 ]; then keep_lines app/forms.php 1-57
elif [ "$N" -lt 34 ]; then keep_lines app/forms.php 1-115
elif [ "$N" -lt 35 ]; then keep_lines app/forms.php 1-128
fi

# --- لوحة الإدارة: الفصل 36 يكتب الدخول والخروج فقط (الأسطر 126-187)، وقسم صفحة الدخول من admin.css
if [ "$N" -ge 36 ] && [ "$N" -lt 38 ]; then
  { sed -n '1p' "$P/app/controllers/admin.php"; sed -n '126,187p' "$P/app/controllers/admin.php"; } > app/controllers/admin.php
  find app/views/admin -mindepth 1 -maxdepth 1 ! -name login.php -exec rm -rf {} +
  [ "$N" -ge 37 ] || keep_lines assets/css/admin.css 102-121
fi

# --- القوالب
[ "$N" -ge 36 ] || rm -rf app/views/admin app/views/students/admin
[ "$N" -ge 45 ] || rm -rf app/views/competitions
[ "$N" -ge 35 ] || { rm -rf app/views/students/profile app/views/students/notifications_list.php; : > app/views/base/notifications_js.php; }
[ "$N" -ge 34 ] || rm -f app/views/students/{dashboard,create_application,my_applications,application_detail,application_print,print_all}.php
[ "$N" -ge 33 ] || rm -rf app/views/students

# --- المسابقات في الرئيسية وشريط التنقل (الفصل 45)
if [ "$N" -lt 45 ]; then
  sed -i '79,84d' app/views/base/navbar.php
  sed -i '315,337d;5d' app/views/homepage/index.php
  sed -i '19d' app/controllers/homepage.php
fi

# --- ملفا اللغة: من أولهما حتى آخر قسم كُتب، ثم سطر الإغلاق
TOTAL=$(wc -l < "$P/app/lang/fr.php")
END=$(python3 -c '
import json, sys
d = json.load(open(sys.argv[1])); n = int(sys.argv[2]); total = int(sys.argv[3])
keys = [int(k) for k in d if int(k) <= n]
parts = [tuple(map(int, p.split("-"))) for p in d[str(max(keys))]["fr"].split(",")] if keys else [(0, 0)]
print(max(b for a, b in parts if a < total))  # الجزء الذي يبدأ عند سطر الإغلاق ليس محتوى
' "$BOOK/tools/lang-sections.json" "$N" "$TOTAL")
for l in fr en; do
  total=$(wc -l < "$P/app/lang/$l.php")
  { sed -n "1,${END}p" "$P/app/lang/$l.php"; sed -n "${total}p" "$P/app/lang/$l.php"; } > app/lang/$l.php
done

# --- خادم المحاكاة: /tmp/simroot/dou-setif ← المجلد، وموجّه يحاكي .htaccess
mkdir -p /tmp/simroot
ln -sfn "$SIM" /tmp/simroot/dou-setif
cat > /tmp/simroot/router.php <<'EOF'
<?php
// يحاكي .htaccess الموقع لخادم php -S: الموقع في المجلد الفرعي /dou-setif
$prefix = '/dou-setif';
$uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (strpos($uri, $prefix . '/') !== 0 && $uri !== $prefix) {
    http_response_code(404);
    echo 'Not Found';
    return true;
}
$rel = substr($uri, strlen($prefix));
$root = __DIR__ . $prefix;
$deny = preg_match('#^/(app|database|storage)(/|$)#', $rel) || preg_match('#(^|/)\.(?!well-known)#', $rel)
    || preg_match('/^(config\.php|config\.local\.php|.*\.sql|.*\.md|.*\.log|.*\.bak|.*\.swp|.*\.key|.*\.enc|composer\.(json|lock))$/', basename($rel));
if ($deny) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
if ($rel !== '' && $rel !== '/' && is_file($root . $rel) && substr($rel, -4) !== '.php') {
    return false; // ملف ثابت (assets, uploads)
}
$_SERVER['SCRIPT_NAME'] = $prefix . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['PHP_SELF'] = $prefix . '/index.php';
chdir($root);
require $root . '/index.php';
return true;
EOF
echo "المحاكاة جاهزة: $SIM (حتى الفصل $N، ملفا اللغة حتى السطر $END)"
