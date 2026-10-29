# خطة الكتاب — الإصدار الثاني (إعادة كتابة كاملة)

> **تجميد الكتاب (النسخة 1.0):** أدوات الكتاب (build, checkrefs, stages, shots, demo-site) تقرأ الكود من
> `dou-setif-php.zip` (يُفك في `.build/project-v1` عبر `tools/project.mjs`)، لا من `php/`.
> مجلد `php/` صار النسخة 2.0 متعددة اللغات (عربي/فرنسي/إنجليزي) ولا يشرحها الكتاب.
> لا تعدّل `dou-setif-php.zip` إلا لإصلاح خطأ يُعكس في الكتاب أيضاً.


## طلب المستخدم (حرفياً تقريباً)
«اعادة صياغة الكتاب: القارئ لو اتبعه سيبرمج هذا المشروع. مجلد المشروع فارغ، ثم ننشئ أول مجلد وأول ملف وهكذا.
مثال: صفحة index فيها navbar و slider و آخر الأخبار وعرض الأقسام وعرض الإقامات وفوتر… البرمجة تبدأ من navbar،
تكون HTML ثم ندخل عليها PHP وقواعد البيانات حتى نفهم ما الذي نضيفه: من static إلى dynamic. نفس الشيء للوحة تحكم المدير.»
قرارات المستخدم (ask_user): **الكود النهائي مطابق حرفياً لمجلد php/ الحالي** (لا نغيّر المشروع)، و**الكتاب كاملاً مرة واحدة** (لا تسليم جزئي).
المتطلبات السابقة ما زالت سارية: عربي، مفصّل جداً، لمبتدئ تماماً، كل ملفات المشروع مشروحة (تغطية 105/105).

## المسار التعليمي
1. الجزء 1 (01–09): صفحة رئيسية **ثابتة** HTML/CSS في `index.html` قطعة قطعة بنفس ترتيب الصفحة:
   الرأس+Bootstrap → navbar → slider → شريط الأخبار وآخر الأخبار → النشاطات والإحصائيات → مواقع مهمة/الأقسام/الإقامات → footer.
   التنسيقات (CSS) والسكربتات تُكتب هنا **بنصها النهائي** (مأخوذة من الملفات النهائية بـ from=) ثم «تُنقل» لاحقاً.
2. الجزء 2 (10–12): PHP: إعادة التسمية إلى index.php، echo/متغيرات، مصفوفات+حلقات بدل التكرار، include (navbar.php/footer.php في مساراتها النهائية).
3. الجزء 3 (13–15): MySQL: phpMyAdmin، SQL، جداول الصفحة الرئيسية (قطع schema.sql النهائية)، بيانات تجريبية، config.php النهائي + PDO مباشرة → الصفحة ديناميكية.
4. الجزء 4 (16–25): تنظيم المشروع حتى البنية النهائية (نقطة دخول، helpers، DB، Router، View، Model، النماذج، Auth، القالب، الصفحة الرئيسية النهائية).
   القديم `index.php` يُعاد تسميته `index_old.php` ليبقى يعمل حتى الفصل 25 ثم يُحذف.
5. الجزء 5 (26–30): باقي صفحات الموقع؛ لكل صفحة: (أ) الرابط + دالة مؤقتة + القالب بمحتوى **ثابت** (لقطة) ← (ب) المتحكم النهائي + المحتوى الديناميكي النهائي (لقطة).
6. الجزء 6 (31–33) فضاء الطالب، الجزء 7 (34) الموظفون: نفس النمط ثابت ← ديناميكي.
7. الجزء 8 (35–37) لوحة المدير: (35) صفحات ثابتة admin-demo/*.html بـ admin.css/admin.js النهائيين ← (36) CRUD للأخبار مكتوب يدوياً (مؤقت)
   ← (37) التعميم: admin_config.php + admin.php + القوالب النهائية، وحذف المؤقت.
8. 38 الأمان والنشر، الملحق أ (الشجرة)، الملحق ب (استيراد Django).

## الفصول (chapters/) — نص الإصدار الأول (chapters-v1/) حُذف بعد اكتمال الثاني، وهو في تاريخ Git (e07fc08)
| ملف | المحتوى | ملفات نهائية تُعرض | مصدر النص القديم |
|---|---|---|---|
| 00-intro | ما سنبنيه، المنهج ثابت→ديناميكي، أنواع كتل الكود | — | v1/00 |
| 01-setup | XAMPP، VS Code، المتصفح، **المجلد الفارغ dou-setif** | — | v1/01 |
| 02-first-page | index.html: الهيكل، الوسوم، الشعار assets/images/logo.svg | logo.svg | v1/02 |
| 03-base-style | CSS، روابط Bootstrap/FA، تنسيقات base العامة، jQuery/Bootstrap JS | base.php 22-218 (from) | v1/02,14 |
| 04-navbar | شريط التنقل ثابت + تنسيقه | navbar.php 226-575 (from) | v1/14 |
| 05-slider | carousel + `<style>` الصفحة + سكربت الصفحة | index.php 5-12,216,763-792 (from) | v1/15 |
| 06-news | شريط الأخبار + آخر الأخبار | index.php 13-65 (from) | v1/15 |
| 07-activities-stats | النشاطات + الإحصائيات (style+script) | index.php 66-135,512-575 (from) | v1/15 |
| 08-departments-cities | مواقع مهمة + الأقسام + الإقامات | index.php 136-215 (from) | v1/15 |
| 09-footer | التذييل، الصفحة الثابتة كاملة | footer.php 1-53,55 (from) | v1/14 |
| 10-php-first | index.php، echo، متغيرات، date | — | v1/03 |
| 11-php-arrays | مصفوفات/حلقات/شروط/دوال، e() مؤقتة | — | v1/03 |
| 12-php-include | navbar.php وfooter.php بالـ include | footer.php 54 | v1/03 |
| 13-mysql-basics | قاعدة البيانات، phpMyAdmin، SQL | — | v1/04 |
| 14-homepage-tables | schema.sql: الرأس + sliders/statistics/news/activities/cites/departements + بيانات تجريبية | schema 1-43,63-88,126-150,174-199,212-232,245-266 | v1/06 |
| 15-pdo | config.php + PDO + الصفحة ديناميكية | config.php | v1/05,07 |
| 16-structure | لماذا ننظم، الهيكل، .htaccess، index.php، bootstrap، helpers 1-36، ملفات فارغة | (كما v1/05) | v1/05 |
| 17-helpers | helpers 37-$ | helpers.php | v1/08 |
| 18-db-class | DB.php | DB.php | v1/07 |
| 19-router | Router.php + routes.php 1-14 (الباقي مع كل صفحة) | Router, routes | v1/09 |
| 20-views | View.php + errors/* | | v1/10 |
| 21-model | Model.php + Page.php | | v1/11 |
| 22-models | models/homepage.php + content.php كاملاً | | v1/12 |
| 23-auth-stats | Auth.php + site_stats.php + جداول site_visits/site_statistics | | v1/13 |
| 24-layout | base.php، navbar.php النهائي، footer، notification_bar + جدول notifications | | v1/14 |
| 25-homepage-final | controllers/homepage.php 1-21 + القالب النهائي + جداول director_words/services/downloads/faq اللازمة للعدادات؛ حذف index_old.php | | v1/15 |
| 26-directorate | كلمة المدير/الهيكل/التاريخ | | v1/16 |
| 27-news-activities | | | v1/17 |
| 28-departements-cites-services | | | v1/18 |
| 29-forms-contact | Form.php، forms.php (جزء)، contact | | v1/19 |
| 30-downloads-faq-search | Upload.php، downloads، faq، search | | v1/20 |
| 31-accounts | users + student_profiles، students.php (جزء)، login/register، create_admin | | v1/21 |
| 32-applications | | | v1/22 |
| 33-notifications-profile | الإشعارات، الملف الشخصي، البطاقة، notifications_js | | v1/23 |
| 34-staff | staff.php، XlsxWriter، students/admin/* | | v1/24 |
| 35-admin-static | admin-demo/*.html ثابتة + admin.css + admin.js | admin.css, admin.js | جديد |
| 36-admin-news | CRUD الأخبار يدوياً (مؤقت: stages/adminnews.src) + layout/delete/password النهائية | admin.php 35-48، 92-134 (from) | جديد |
| 37-admin-generic | admin_config + admin.php + views/admin/* | | v1/25 |
| 38-security-deploy | | | v1/26 |
| A-files / B-django-import | | | v1/A, v1/B |

## نظام المراحل (stages/) — كود مؤقت مُختبَر
- `stages/<name>.src`: ملف رئيسي يتطوّر عبر الفصول. كتل:
  `@@block NAME STEP [file=PATH] [from=FINALPATH:a-b] [replaces=OTHER]` … `@@end`
  STEP رقم الفصل (عشري مسموح 27.1). اللقطة عند الخطوة k = الأسطر الثابتة + الكتل STEP≤k غير المستبدلة.
  `@@file PATH STEP` يحدد اسم الملف الناتج ابتداءً من خطوة (index.html ← index.php). كتلة file= تُكتب في ملف آخر.
- في الفصول: `@stage <name> NAME [STEP]` يعرض الكتلة بأرقام أسطرها في لقطة تلك الخطوة، بعنوان اسم الملف وشارة «مؤقت».
  كتل from= تسجّل تغطية الملف النهائي؛ لاحقاً `@moved FINALPATH a-b | نص` يعرض صندوقاً مختصراً «منقول» (يتحقق أن الأسطر سبق عرضها).
- `node tools/stages.mjs <name> <step> <outdir>` يكتب اللقطة لاختبارها ولقطات الشاشة.

## البيئة (تُمسح /tmp عند كل إعادة تشغيل)
- الأسرع: `bash tools/setup.sh` ثم `bash tools/devenv.sh` (PHP 8.0 + MySQL 5.7) ثم `bash tools/devenv.sh mysqld` (عملية دائمة) ثم `bash tools/demo-site.sh` وخادم `cd /tmp/site && /tmp/tools/bin/php -S 0.0.0.0:8000 index.php`.
- `bash tools/setup.sh` (Node + Chromium + pypdf + خطوط).
- PHP 8.0: `mkdir -p /tmp/tools/php80 && cd /tmp/tools/php80 && curl -sL https://registry.npmjs.org/@libphp/amazon-linux-2-v80/-/amazon-linux-2-v80-0.0.21.tgz | tar xz`
  ثم `P=/tmp/tools/php80/package/native; LD_LIBRARY_PATH=$P/lib $P/php/php -n -d extension_dir=$P/php/modules -d extension=mbstring -d extension=pdo -d extension=pdo_mysql -d extension=mysqli -d extension=fileinfo -d extension=zip ...`
- MySQL 5.7: الوصفة في قسم «لقطات الشاشة» أدناه.
- بعد إعادة التشغيل: `git fetch origin <branch> && git reset FETCH_HEAD` ثم `git checkout -- book/dou-setif-book.pdf` (الملفات الثنائية غير المحفوظة تتلف).

## الحالة
- [x] أدوات: stages.mjs + @stage/@moved/insert/replace في build.mjs + checkrefs.mjs (فحص أرقام الأسطر) + `--coverage` (تقرير التغطية بلا PDF)
- [x] الفصول 00–38 والملحقان أ و ب مكتوبة ومختبرة (المراحل جُرّبت بخادم PHP + MySQL في صندوق العمل)
- [x] تغطية ملفات المشروع 105/105 (البناء يكتب .build/coverage.txt و .build/files-chapters.json الذي وُلّدت منه شجرة الملحق أ)
- [x] لقطات الشاشة v2 (book/images، 49 صورة) من الموقع التجريبي: `bash tools/demo-site.sh` ثم خادم PHP على /tmp/site
- [x] حذف chapters-v1 وصور v1 غير المستعملة
- [x] بناء كامل + ZIP مطابق لـ php/ + تسليم (رسالة عربية فيها قائمة إصلاحات الكود)

## إصلاحات في php/ أثناء الإصدار الثاني (الكتاب والـ ZIP محدّثان)
- assets/css/admin.css الأسطر 79-82: مربعات الاختيار في RTL (bootstrap-rtl لا يُحمَّل، الفصل 3).
- app/views/search/search_results.php السطر 24: `color: #2c3e50` لحقل البحث الكبير (كان يرث الأبيض من الترويسة فلا يُرى النص).
- app/views/base/base.php الأسطر 220-227: إزاحة الأعمدة `col-md-offset-1/2/3` من اليمين (≥992px)، فتتوسط بطاقات صفحات الطالب والموظفين (التسجيل، تقديم طلب، تفاصيل الطلب، تعديل الملف، كلمة المرور، مراجعة الطلب). الكتاب: الفصل 24 (+9 أسطر في base.php بعد 219)، والفصلان 25 (229 و284) و31 و34.
- tools/shots.mjs: الباركود في لقطة البطاقة (toBuffer تُرجع Promise)، و`css` لكل لقطة (33-card: ارتفاع سطر Arial).

## ملاحظات مهمة
- ملفات الكود النهائية في php/ لا تُغيَّر (قرار المستخدم) إلا لإصلاح خطأ حقيقي، مع تحديث الكتاب والـ ZIP معه. كل شيء آخر مؤقت ومذكور.
- ملف صنف واحد (Auth.php, Model.php...) يجب أن يُعرض كاملاً في فصل واحد (الملف غير المكتمل خطأ صياغة يكسر الموقع).
- routes.php عُرض كاملاً في 19 (navbar يحتاج كل أسماء المسارات).
- اختبار خادم: لا تستعمل `pkill -f "127.0.0.1:PORT"` (يقتل أمر bash نفسه)؛ استعمل `& echo $! > pidfile` ثم `kill $(cat pidfile)`.

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
