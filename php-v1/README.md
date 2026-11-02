# مديرية الخدمات الجامعية سطيف — نسخة PHP + MySQL

هذه نسخة كاملة من الموقع بعد تحويله من **Django + PostgreSQL** إلى **PHP + MySQL**.
الموقع مكتوب بـ PHP خالص بدون أي إطار عمل أو Composer، لذلك يعمل مباشرة على XAMPP وعلى أي استضافة مشتركة (cPanel).

نفس الروابط، ونفس التصميم، ونفس البيانات، ونفس كلمات المرور.

---

## المتطلبات

| المكوّن | الإصدار الأدنى | ملاحظة |
|---|---|---|
| PHP | 8.0 أو أحدث (تمت التجربة على 8.0 و 8.5) | الامتدادات: `pdo_mysql`، `mbstring`، `fileinfo`، `zip` (لتصدير Excel). كلها مفعّلة في XAMPP |
| MySQL / MariaDB | MySQL 5.7+ أو MariaDB 10.2+ | الترميز `utf8mb4` |
| خادم الويب | Apache مع `mod_rewrite` | موجود في XAMPP وأغلب الاستضافات. يوجد بديل إن لم يتوفر (انظر أسفله) |

---

## التثبيت على XAMPP (Windows)

1. انسخ مجلد `php` إلى `C:\xampp\htdocs\` وغيّر اسمه مثلاً إلى `dou-setif`.
2. شغّل **Apache** و **MySQL** من لوحة تحكم XAMPP.
3. افتح phpMyAdmin على `http://localhost/phpmyadmin`:
   - اضغط **New (جديد)** وأنشئ قاعدة بيانات باسم `dou_setif` بترميز `utf8mb4_unicode_ci`.
   - اختر القاعدة ثم **Import (استيراد)**: استورد الملف `database/schema.sql` أولاً، ثم `database/data.sql`.
4. افتح الملف `config.php` وتأكد من بيانات الاتصال (الإعداد الافتراضي لـ XAMPP: المستخدم `root` بدون كلمة مرور).
5. افتح الموقع على: `http://localhost/dou-setif/`
6. لوحة الإدارة على: `http://localhost/dou-setif/admin/`

> الموقع يكتشف المجلد الذي وُضع فيه تلقائياً، فلا داعي لتعديل أي رابط.

### الحسابات

كل الحسابات القديمة نُقلت **بنفس كلمات المرور** التي كانت في Django (يتعرف الموقع على تشفير Django ويحوّله تلقائياً إلى تشفير PHP الحديث عند أول دخول):

| المستخدم | الصلاحية |
|---|---|
| `miloudsellam` | مدير عام (كل الصلاحيات) |
| `hicham41` | موظف (متابعة طلبات الطلاب + لوحة الإدارة بدون إدارة المستخدمين) |
| `1234566` و `20232023` | طلاب |

نسيت كلمة المرور أو تريد مديراً جديداً؟ (بديل `python manage.py createsuperuser`):

```bat
cd C:\xampp\htdocs\dou-setif
C:\xampp\php\php.exe database\tools\create_admin.php admin "كلمة-مرور-قوية#2025"
```

---

## النشر على استضافة (cPanel)

1. ارفع **محتوى** مجلد `php` إلى `public_html` (أو إلى مجلد فرعي داخله).
2. من **MySQL Databases** في cPanel: أنشئ قاعدة بيانات ومستخدماً واربطهما بكل الصلاحيات.
3. من phpMyAdmin استورد `database/schema.sql` ثم `database/data.sql`.
4. عدّل `config.php`:
   - بيانات قاعدة البيانات (الاسم، المستخدم، كلمة المرور).
   - `'debug' => false` (مهم جداً حتى لا تظهر تفاصيل الأخطاء للزوار).
5. تأكد أن المجلدين `uploads/` و `storage/` قابلان للكتابة (الصلاحية 755 أو 775).

يمكن أيضاً وضع الإعدادات في متغيرات البيئة بدل تعديل الملف:
`DB_HOST`، `DB_PORT`، `DB_NAME`، `DB_USER`، `DB_PASSWORD`، `APP_DEBUG`، `BASE_URL`، `PRETTY_URLS`.

### إذا لم يكن `mod_rewrite` متاحاً
ضع `'pretty_urls' => false` في `config.php` فتصبح الروابط بالشكل `/index.php/news/` وتعمل بدون `.htaccess`.

### خادم Nginx
```nginx
root /var/www/dou-setif;
index index.php;
location ~ ^/(app|database|storage)/ { deny all; }
location /uploads/ { location ~ \.php$ { deny all; } }
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
```

### تجربة سريعة بدون XAMPP
```bash
cd php
php -S localhost:8000 index.php
```

---

## الصور والملفات المرفوعة

الصور التي كانت على Django/Cloudinary **غير موجودة في المستودع** (ما عدا 3 صور نُقلت إلى `uploads/`).
انسخ ملفاتك القديمة إلى المجلد `uploads/` **بنفس المسارات** المحفوظة في قاعدة البيانات، مثلاً:

```
uploads/slider/9.jpg
uploads/news/main/10.jpg
uploads/cites/main/lamp.jpg
uploads/students/photos/hamdane.jpg
```

إلى أن تفعل ذلك، تظهر صورة بديلة رمادية مكان كل صورة مفقودة بدل صورة مكسورة.

وثائق طلبات الطلاب أصبحت **خاصة** وتُحفظ في `storage/private/` (لا يمكن فتحها من المتصفح مباشرة)، ولا يحمّلها إلا الطالب صاحب الطلب أو الموظفون.
الوثائق القديمة (إن وُجدت) ضعها في `storage/private/students/documents/...` بنفس مسارها القديم.

---

## نقل بيانات أحدث من موقع Django المنشور

إذا كانت قاعدة بيانات الموقع المنشور (Render) تحتوي بيانات أحدث من `data.sql`:

```bash
# 1) في مشروع Django القديم
python manage.py dumpdata --natural-foreign --natural-primary --exclude contenttypes --exclude auth.permission --exclude admin.logentry --exclude sessions --indent 1 -o data.json

# 2) في هذا المشروع
php database/tools/django_json_to_sql.php data.json > database/data.sql
```

ثم أعد استيراد `schema.sql` و `data.sql`. الأداة تحوّل التواريخ من UTC إلى توقيت الجزائر تلقائياً.

---

## بنية المشروع

```
php/
├── index.php              ← نقطة الدخول الوحيدة (كل الطلبات تمر من هنا)
├── config.php             ← إعدادات قاعدة البيانات والموقع
├── .htaccess              ← الروابط النظيفة + حماية المجلدات الداخلية
├── app/
│   ├── routes.php         ← جدول الروابط (بديل urls.py)
│   ├── controllers/       ← منطق الصفحات (بديل views.py) — ملف لكل تطبيق Django
│   ├── models/            ← الجداول والعلاقات (بديل models.py)
│   ├── views/             ← القوالب (بديل templates/) بنفس الأسماء
│   ├── forms.php          ← النماذج والتحقق (بديل forms.py)
│   ├── admin_config.php   ← إعدادات لوحة الإدارة (بديل admin.py)
│   ├── helpers.php        ← دوال مساعدة (فلاتر القوالب، الروابط، الأمان...)
│   └── core/              ← النواة: قاعدة البيانات، الموجّه، القوالب، المصادقة، رفع الملفات، Excel
├── assets/                ← CSS / JS / صور الموقع (بديل static/)
├── uploads/               ← الصور والملفات المرفوعة (بديل media/)
├── storage/               ← ملفات خاصة: وثائق الطلبات، الجلسات، سجل الأخطاء
└── database/
    ├── schema.sql         ← بنية الجداول
    ├── data.sql           ← البيانات المنقولة من Django
    └── tools/             ← أداة نقل البيانات + إنشاء مدير
```

### المقابل بين Django و PHP

| في Django | في هذه النسخة |
|---|---|
| `{% url 'news:news_detail' news.slug %}` | `<?= e(url('news:news_detail', $news['slug'])) ?>` |
| `{{ news.title }}` | `<?= e($news['title']) ?>` |
| `{{ app.get_status_display }}` | `<?= e($app->get_status_display()) ?>` |
| `{% extends %}` / `{% block %}` | `extend('base/base')` / `block('content')` ... `endblock()` |
| `News.objects.filter(status='published')` | `News::where("status = 'published'")` |
| `get_object_or_404(...)` | `Model::findOrFail($id)` |
| `@login_required` | `require_login()` |
| `messages.success(request, '...')` | `flash('success', '...')` |
| Django admin | `/admin/` (لوحة مكتوبة خصيصاً، تُضبط من `app/admin_config.php`) |

---

## الوظائف

- **الموقع العام**: الرئيسية (سلايدر، شريط الأخبار، النشاطات، الإحصائيات)، كلمة المدير، الهيكل التنظيمي، تاريخ المديرية، الأخبار، النشاطات، المصالح، الإقامات، الخدمات، مركز التحميلات، الأسئلة الشائعة، البحث الشامل مع معالجة "ال" التعريف، اتصل بنا، شريط الإشعارات، إحصائيات الزوار.
- **فضاء الطالب**: التسجيل، الدخول (مع "تذكرني")، تقديم الطلبات مع وثائق، متابعة الطلبات وإلغاؤها، الإشعارات الفورية (تحديث تلقائي + صوت + إشعارات سطح المكتب)، الملف الشخصي والصورة، تغيير كلمة المرور، البطاقة الرقمية، الطباعة.
- **لوحة الموظفين** (`/students/admin/dashboard/`): إحصائيات ورسوم بيانية، قائمة الطلبات مع البحث والفلترة، إجراءات جماعية، قبول/رفض مع ملاحظات وإشعار الطالب، تصدير Excel.
- **لوحة الإدارة** (`/admin/`): إدارة كل الجداول (إضافة، تعديل، حذف، بحث، فلاتر، إجراءات جماعية، تعديل مباشر من القائمة، معرض الصور، الوثائق المرفقة، المستخدمون والصلاحيات).

## ما الذي أُصلح أو حُسّن أثناء التحويل

- **الإشعارات المكررة**: كان الطالب يتلقى إشعارين عند كل تغيير لحالة طلبه (من `signals.py` ومن `views.py` معاً). الآن يصله إشعار واحد.
- **الإشعارات الفورية**: كان في `navbar.html` كود CSS داخل وسم `<script>` فيعطّل السكربت كله، وكان سكربت `base.html` يبحث عن عناصر غير موجودة. الآن عداد الإشعارات والقائمة يتحدثان فعلاً.
- **رقم الطلب**: كان يُحسب بعدّ الطلبات، فقد يتكرر الرقم بعد حذف طلب. الآن يُحسب من آخر رقم مع إعادة المحاولة عند التزامن.
- **الوثائق المرفقة**: كانت متاحة لأي شخص يعرف الرابط وبدون فحص للنوع أو الحجم. الآن هي خاصة، ويُفحص نوعها الحقيقي وحجمها.
- **إحصائيات الزوار**: كان يُحسب كل طلب كزيارة، بما فيها فحص الإشعارات كل 10 ثوانٍ. الآن لا تُحسب طلبات AJAX، ومنطق "الزائر الجديد" صحيح (لم يزر الموقع خلال 24 ساعة).
- **مركز التحميلات**: كانت قوالبه مفقودة فتظهر صفحة خطأ. الآن أُنشئت.
- **قالب تفاصيل السؤال**: كان اسمه `faq_detail.htm` بدل `.html` فتظهر صفحة خطأ. أصلحت الصفحة.
- **رسائل الخطأ**: كانت تظهر بدون لون لأن Bootstrap لا يعرف `alert-error`. الآن تظهر بالأحمر.
- **أخطاء HTML** في شريط التنقل: محرف `<` زائد، ووسم `<style>` مكرر.
- **الأمان**: حماية CSRF لكل النماذج، استعلامات محضّرة ضد حقن SQL، تهريب HTML ضد XSS، منع إعادة التوجيه لمواقع خارجية بعد الدخول، منع تنفيذ السكربتات في مجلد الرفع، وتجديد معرّف الجلسة عند الدخول.
