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
| 05-structure.md | هيكل المشروع ونقطة الدخول | .htaccess, config.php, index.php, app/bootstrap.php, */.htaccess, .gitignore |
| 06-database.md | قاعدة البيانات | database/schema.sql |
| 07-db-class.md | DB.php | app/core/DB.php |
| 08-helpers.md | الدوال المساعدة | app/helpers.php |
| 09-router.md | الموجّه | app/core/Router.php, app/routes.php 1-14 |
| 10-views.md | القوالب | app/core/View.php, app/views/errors/* |
| 11-models.md | النماذج | app/core/Model.php, app/core/Page.php, app/models/homepage.php, app/models/content.php |
| 12-layout.md | القالب الرئيسي | base.php, navbar.php, footer.php, notification_bar.php, assets/images/*.svg |
| 13-home.md | الصفحة الرئيسية | controllers/homepage.php, controllers/site_stats.php, views/homepage/index.php |
| 14-directorate.md | صفحات المديرية | director_word, organization_structure, directorate_history |
| 15-news-activities.md | الأخبار والنشاطات | news.php, activities.php + views, routes 16-22 |
| 16-dep-cities-services.md | المصالح والإقامات والخدمات | departements, cities, services + views, routes 24-35 |
| 17-forms-contact.md | النماذج واتصل بنا | core/Form.php, forms.php (جزء), contact.php + view, routes 37-38 |
| 18-downloads-faq-search.md | التحميلات والأسئلة والبحث | downloads, faq, search + views, routes 40-50 |
| 19-auth.md | الحسابات وتسجيل الدخول | core/Auth.php, models/students.php (User), students.php (تسجيل/دخول), login/register |
| 20-applications.md | طلبات الطالب | core/Upload.php, StudentProfile..., create/my/detail/print |
| 21-student-extra.md | الإشعارات والملف الشخصي والبطاقة | notifications, profile, card, notifications_js |
| 22-staff.md | لوحة الموظفين | staff.php, XlsxWriter.php, students/admin/* |
| 23-admin.md | لوحة الإدارة | admin.php, admin_config.php, views/admin/*, admin.css, admin.js, create_admin.php |
| 24-security-deploy.md | الأمان والنشر | — |
| A-files.md | ملحق: شجرة الملفات | — |
| B-django-import.md | ملحق: نقل البيانات من Django | database/tools/django_json_to_sql.php |

## الحالة
- [x] أدوات البناء (setup.sh, build.mjs, book.css, pdftool.py)
- [ ] الفصول (تُحدَّث القائمة أعلاه عند الإنجاز)
