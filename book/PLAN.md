# خطة الكتاب (ملف عمل داخلي)

الكتاب: «بناء موقع مديرية الخدمات الجامعية لولاية سطيف من الصفر» — PDF بالعربية للمبتدئين.
كل الكود يُضمَّن تلقائياً من مجلد `php/` عبر `@file مسار من-إلى` (انظر رأس `tools/build.mjs`).
الهدف: تغطية كل ملفات المشروع (ما عدا data.sql والصور) بحيث لو كتب القارئ القطع بالترتيب يحصل على الملف كاملاً.
تقرير التغطية: `.build/coverage.txt` بعد كل بناء.

## البناء
```
cd book
bash tools/setup.sh          # مرة واحدة لكل بيئة (Chromium + pypdf + الخطوط)
node tools/build.mjs         # بناء كامل → dou-setif-book.pdf
node tools/build.mjs --only=05 --png=1-4   # معاينة فصل
```
ملاحظة الحجم: Chromium لا يُخرج الفهرس الجانبي (ومنه نستخرج أرقام الصفحات) إلا في PDF موسوم (Tagged)،
وشجرة الوسوم وحدها ~185 ألف كائن (~25 MB). لذلك تُطبع مرورات الترقيم موسومة، ثم مرور أخير بلا وسوم
هو الذي يُختم ويُنشر (40 MB → 15 MB). للإبقاء على الوسوم (إمكانية الوصول): `node tools/build.mjs --tagged`.

## الفصول وتوزيع الملفات
| الملف | الفصل | ملفات المشروع |
|---|---|---|
| 00-intro.md | مقدمة | — |
| 01-setup.md | تجهيز بيئة العمل | — |
| 02-web-basics.md | HTML / CSS / Bootstrap / JavaScript | — |
| 03-php-basics.md | أساسيات PHP | — |
| 04-sql-basics.md | أساسيات MySQL | — |
| 05-structure.md | هيكل المشروع ونقطة الدخول | .htaccess, config.php, index.php, app/bootstrap.php, helpers.php 1-36, app/database/storage/uploads .htaccess, .gitignore (+ hello.php مؤقت) |
| 06-database.md | قاعدة البيانات | database/schema.sql (+ استيراد data.sql) |
| 07-db-class.md | DB.php | app/core/DB.php |
| 08-helpers.md | صندوق الأدوات | app/helpers.php 37-$ |
| 09-router.md | الموجّه وخريطة الروابط | app/core/Router.php, app/routes.php (كامل) |
| 10-views.md | القوالب | app/core/View.php, app/views/errors/* |
| 11-model.md | الصنف الأساسي للنماذج | app/core/Model.php, app/core/Page.php |
| 12-models.md | نماذج الموقع | app/models/homepage.php, content.php, students.php |
| 13-auth-stats.md | المستخدم الحالي والزيارات | app/core/Auth.php, app/controllers/site_stats.php |
| 14-layout.md | القالب الرئيسي (أول صفحة تعمل) | base.php, navbar.php, footer.php, notification_bar.php, assets/images/*.svg |
| 15-home.md | الصفحة الرئيسية | controllers/homepage.php, views/homepage/index.php |
| 16-directorate.md | صفحات المديرية | director_word, organization_structure, directorate_history |
| 17-news-activities.md | الأخبار والنشاطات | news.php, activities.php + views |
| 18-dep-cities-services.md | المصالح والإقامات والخدمات | departements, cities, services + views |
| 19-forms-contact.md | النماذج واتصل بنا | core/Form.php, forms.php (جزء), contact |
| 20-downloads-faq-search.md | التحميلات والأسئلة والبحث | core/Upload.php, downloads, faq, search + views |
| 21-accounts.md | حسابات الطلبة | students.php (تسجيل/دخول/خروج), login, register, forms.php (جزء), database/tools/create_admin.php |
| 22-applications.md | لوحة الطالب والطلبات | dashboard, create/my/detail/print (Upload مشروح في 20) |
| 23-student-extra.md | الإشعارات والملف الشخصي والبطاقة | notifications, profile, card, notifications_js |
| 24-staff.md | لوحة الموظفين | staff.php, XlsxWriter.php, students/admin/* |
| 25-admin.md | لوحة الإدارة | admin.php, admin_config.php, views/admin/*, admin.css, admin.js |
| 26-security-deploy.md | الأمان والنشر | — |
| A-files.md | ملحق: شجرة الملفات | — |
| B-django-import.md | ملحق: نقل البيانات من Django | database/tools/django_json_to_sql.php |

## الحالة
- [x] أدوات البناء (setup.sh, build.mjs, book.css, pdftool.py)
- [x] 00–04 الأساسيات
- [x] 05–26، A، B
- [x] لقطات الشاشة 14–25 (images/)
- [x] البناء الكامل (633 صفحة، ~15 MB) + ملف ZIP المحدّث

## لقطات الشاشة (images/)
تحتاج الموقع يعمل مع قاعدة بيانات فيها بيانات تجريبية. في بيئة بلا MySQL مثبت:
```
# 1) MySQL 5.7 من حزمة npm، مع مكتبة libaio وهمية (mysqld يطلبها، ونعطّل AIO)
mkdir -p /tmp/mysql57/lib /tmp/mysql57/data /tmp/mysql57/tmp && cd /tmp/mysql57
curl -sL https://registry.npmjs.org/mysql-server-5.7-lin-x64/-/mysql-server-5.7-lin-x64-1.0.0.tgz | tar xz
printf '#include <errno.h>\nint io_setup(){return -ENOSYS;}int io_destroy(){return -ENOSYS;}int io_submit(){return -ENOSYS;}int io_cancel(){return -ENOSYS;}int io_getevents(){return -ENOSYS;}\n' > lib/aio.c
gcc -shared -fPIC -o lib/libaio.so.1 lib/aio.c -Wl,-soname,libaio.so.1
S=/tmp/mysql57/package/server; OPT="--no-defaults --explicit_defaults_for_timestamp --basedir=$S --datadir=/tmp/mysql57/data/db --lc-messages-dir=$S/share/mysql --innodb-use-native-aio=0 --log_syslog=0"
LD_LIBRARY_PATH=/tmp/mysql57/lib $S/mysqld $OPT --initialize-insecure
LD_LIBRARY_PATH=/tmp/mysql57/lib $S/mysqld $OPT --console --tmpdir=/tmp/mysql57/tmp --socket=/tmp/mysql57/mysql.sock \
  --port=3306 --bind-address=127.0.0.1 --secure-file-priv=/tmp/mysql57/tmp --character-set-server=utf8mb4   # عملية دائمة
# 2) PHP (أي PHP 8 فيه mysqli و pdo_mysql و mbstring و fileinfo و zip)
cd php && php ../book/tools/shots-seed.php      # يعيد إنشاء القاعدة + بيانات تجريبية
php -S 0.0.0.0:8000 index.php                   # عملية دائمة
# 3) اللقطات
cd book && node tools/shots.mjs                 # أو --only=24-list,24-detail  (العرض الافتراضي 1920)
```
الحسابات: المدير miloudsellam / AdminDemo2026، والطالب 1234566 / Talib2026x.
