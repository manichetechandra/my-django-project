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
- [x] 05–20
- [ ] 21–26، A، B
