# شجرة ملفات المشروع

::: intro
هذا الملحق خريطة المشروع النهائي كاملاً: كل ملف، والفصول التي كتبت فيها أسطره النهائية. استعمله لتتأكد أنك لم تنسَ ملفاً، أو لتعرف أين تبحث حين تريد تعديل شيء. وفي آخره قائمة الملفات المؤقتة التي أنشأناها في الطريق ثم حذفناها، فلا يبقى منها شيء في مجلدك.
:::

## الشجرة الكاملة

```plaintext dou-setif/
dou-setif/
├── app/
│   ├── controllers/
│   │   ├── activities.php                  ← الفصل 29
│   │   ├── admin.php                       ← الفصول 39، 42
│   │   ├── cities.php                      ← الفصل 30
│   │   ├── contact.php                     ← الفصل 31
│   │   ├── departements.php                ← الفصل 30
│   │   ├── downloads.php                   ← الفصل 32
│   │   ├── faq.php                         ← الفصل 32
│   │   ├── homepage.php                    ← الفصول 27، 28
│   │   ├── news.php                        ← الفصل 29
│   │   ├── search.php                      ← الفصل 32
│   │   ├── services.php                    ← الفصل 30
│   │   ├── site_stats.php                  ← الفصل 24
│   │   ├── staff.php                       ← الفصل 36
│   │   └── students.php                    ← الفصول 33–35
│   ├── core/
│   │   ├── Auth.php                        ← الفصل 24
│   │   ├── DB.php                          ← الفصل 19
│   │   ├── Form.php                        ← الفصل 31
│   │   ├── Model.php                       ← الفصل 22
│   │   ├── Page.php                        ← الفصل 22
│   │   ├── Router.php                      ← الفصل 20
│   │   ├── Upload.php                      ← الفصل 32
│   │   ├── View.php                        ← الفصل 21
│   │   └── XlsxWriter.php                  ← الفصل 36
│   ├── models/
│   │   ├── content.php                     ← الفصل 23
│   │   ├── homepage.php                    ← الفصل 23
│   │   └── students.php                    ← الفصل 33
│   ├── views/
│   │   ├── activities/
│   │   │   ├── activity_detail.php         ← الفصل 29
│   │   │   └── activity_list.php           ← الفصل 29
│   │   ├── admin/
│   │   │   ├── delete.php                  ← الفصل 39
│   │   │   ├── form.php                    ← الفصل 42
│   │   │   ├── index.php                   ← الفصل 42
│   │   │   ├── layout.php                  ← الفصل 39
│   │   │   ├── list.php                    ← الفصل 42
│   │   │   └── password.php                ← الفصل 39
│   │   ├── base/
│   │   │   ├── base.php                    ← الفصول 3–5، 26
│   │   │   ├── footer.php                  ← الفصول 9، 12
│   │   │   ├── navbar.php                  ← الفصول 4، 26، 35
│   │   │   └── notifications_js.php        ← الفصل 35
│   │   ├── cities/
│   │   │   ├── cite_detail.php             ← الفصل 30
│   │   │   └── cite_list.php               ← الفصل 30
│   │   ├── contact/
│   │   │   └── contact.php                 ← الفصل 31
│   │   ├── departements/
│   │   │   ├── departement_detail.php      ← الفصل 30
│   │   │   └── departement_list.php        ← الفصل 30
│   │   ├── downloads/
│   │   │   ├── _file_card.php              ← الفصل 32
│   │   │   ├── _styles.php                 ← الفصل 32
│   │   │   ├── download_by_category.php    ← الفصل 32
│   │   │   └── download_center.php         ← الفصل 32
│   │   ├── errors/
│   │   │   ├── 403.php                     ← الفصل 21
│   │   │   ├── 403_csrf.php                ← الفصل 21
│   │   │   ├── 404.php                     ← الفصل 21
│   │   │   ├── 405.php                     ← الفصل 21
│   │   │   ├── 500.php                     ← الفصل 21
│   │   │   └── _error.php                  ← الفصل 21
│   │   ├── faq/
│   │   │   ├── faq_detail.php              ← الفصل 32
│   │   │   └── faq_list.php                ← الفصل 32
│   │   ├── homepage/
│   │   │   ├── director_word.php           ← الفصل 28
│   │   │   ├── directorate_history.php     ← الفصل 28
│   │   │   ├── index.php                   ← الفصول 5–8، 27
│   │   │   └── organization_structure.php  ← الفصل 28
│   │   ├── news/
│   │   │   ├── news_detail.php             ← الفصل 29
│   │   │   └── news_list.php               ← الفصل 29
│   │   ├── notifications/
│   │   │   └── notification_bar.php        ← الفصل 26
│   │   ├── search/
│   │   │   └── search_results.php          ← الفصل 32
│   │   ├── services/
│   │   │   ├── service_by_type.php         ← الفصل 30
│   │   │   ├── service_card.php            ← الفصل 30
│   │   │   ├── service_detail.php          ← الفصل 30
│   │   │   └── service_list.php            ← الفصل 30
│   │   └── students/
│   │       ├── admin/
│   │       │   ├── application_detail.php  ← الفصل 36
│   │       │   ├── applications_list.php   ← الفصل 36
│   │       │   └── dashboard.php           ← الفصل 36
│   │       ├── profile/
│   │       │   ├── change_password.php     ← الفصل 35
│   │       │   ├── edit.php                ← الفصل 35
│   │       │   ├── student_card.php        ← الفصل 35
│   │       │   └── view.php                ← الفصل 35
│   │       ├── application_detail.php      ← الفصل 34
│   │       ├── application_print.php       ← الفصل 34
│   │       ├── create_application.php      ← الفصل 34
│   │       ├── dashboard.php               ← الفصل 34
│   │       ├── login.php                   ← الفصل 33
│   │       ├── my_applications.php         ← الفصل 34
│   │       ├── notifications_list.php      ← الفصل 35
│   │       ├── print_all.php               ← الفصل 34
│   │       └── register.php                ← الفصل 33
│   ├── .htaccess                           ← الفصل 16
│   ├── admin_config.php                    ← الفصل 42
│   ├── bootstrap.php                       ← الفصل 16
│   ├── forms.php                           ← الفصول 31، 33–35
│   ├── helpers.php                         ← الفصول 16، 17
│   └── routes.php                          ← الفصل 20
├── assets/
│   ├── css/
│   │   └── admin.css                       ← الفصل 37
│   ├── images/
│   │   ├── default-news.svg                ← الفصل 29
│   │   ├── default-slide.svg               ← الفصل 27
│   │   ├── logo.svg                        ← الفصل 2
│   │   └── placeholder.svg                 ← الفصل 26
│   └── js/
│       └── admin.js                        ← الفصل 37
├── database/
│   ├── tools/
│   │   ├── create_admin.php                ← الفصل 33
│   │   └── django_json_to_sql.php          ← الملحق ب
│   ├── .htaccess                           ← الفصل 16
│   ├── data.sql                            ← بيانات الموقع الأصلي (الملحق ب)
│   └── schema.sql                          ← الفصول 14، 24–31، 33
├── storage/
│   ├── logs/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── private/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── sessions/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   └── .htaccess                           ← الفصل 16
├── uploads/
│   ├── departements/
│   │   ├── bourse.jpg                      ← صورة من الموقع الأصلي
│   │   └── drh.jpg                         ← صورة من الموقع الأصلي
│   ├── director/
│   │   └── drh.jpg                         ← صورة من الموقع الأصلي
│   ├── news/
│   │   └── main/
│   │       └── Lighthouse.jpg              ← صورة من الموقع الأصلي
│   └── .htaccess                           ← الفصل 16
├── .gitignore                              ← الفصل 16
├── .htaccess                               ← الفصل 16
├── config.php                              ← الفصل 15
├── index.php                               ← الفصل 16
└── README.md                               ← دليل التثبيت المختصر
```

كيف تقرأ أرقام الفصول؟

- **فصل واحد**: كتبت الملف كاملاً في ذلك الفصل.
- **عدة فصول**: كبر الملف على مراحل. `students.php` مثلاً بدأ بالتسجيل والدخول في الفصل 33، ثم أضفنا إليه الطلبات في الفصل 34 والإشعارات والملف الشخصي في الفصل 35. و`schema.sql` يكبر جدولاً بعد جدول، كلما احتاجت صفحة جديدة إلى بيانات.
- **الفصول 3–9 في ملفات القالب**: أسطر التنسيق والأقسام في `base.php` و`navbar.php` و`footer.php` و`homepage/index.php` كتبتها أول مرة في الصفحة الثابتة `index.html`، ثم نقلتها كما هي إلى ملفاتها النهائية في الفصول 12 و26 و27 (صناديق «منقول»).

## ما لا تكتبه بيدك

بعض الملفات في النسخة المرجعية `dou-setif-php.zip` ليست كوداً يُكتب:

| الملف | ما هو؟ |
|---|---|
| `database/data.sql` | بيانات الموقع الأصلي (أخبار، إقامات، حسابات...). لا تحتاجها، فقاعدتك ملأتها بنفسك. من يريد محتوى الموقع الأصلي يستوردها كما في الملحق ب |
| `uploads/.../*.jpg` | أربع صور من الموقع الأصلي. أما مجلد `uploads/` عندك ففيه صورك أنت: التي وضعتها في الفصول 5–8، والتي رفعتها من لوحة الإدارة |
| `storage/*/.gitkeep` | ملفات فارغة تُبقي المجلدات الفارغة في Git (الفصل 16)، فتوجد المجلدات عند تنزيل المشروع. المجلدات نفسها أنشأتها في الفصل 16 |
| `README.md` | دليل التثبيت المختصر |
| `database/tools/django_json_to_sql.php` | أداة نقل البيانات من Django، يشرحها الملحق ب |

## الملفات المؤقتة

أنشأنا في الطريق ملفات للتجربة أو للمقارنة، ثم حذفناها. تأكد أن لا شيء منها بقي في مجلدك:

| الملف | أنشأناه في | مصيره |
|---|---|---|
| `test.txt` | الفصل 1 | حذفناه بعد التأكد أن Apache يقدّم ملفات المجلد |
| `index.html` | الفصول 2–9 | صار `index.php` في الفصل 10 |
| `info.php` | الفصل 10 | حذفناه بعد قراءة إعدادات PHP، لأنه يكشف معلومات عن الخادم |
| قاعدة `test_db` | الفصل 13 | حذفناها بالأمر `DROP DATABASE` في آخر الفصل نفسه |
| `database/sample_data.sql` | الفصل 14 | حذفناه في الفصل 27، وبقيت بياناته في القاعدة |
| `index_old.php` | الفصل 16 (هو `index.php` القديم) | حذفناه في الفصل 27، بعد أن انتقل كل محتواه إلى القوالب |
| `hello.php` | الفصل 17 (وكبر حتى الفصل 24) | حذفناه في الفصل 27 |
| `hello_view.php` و`app/views/test/` | الفصل 21 | حذفناهما في آخر الفصل نفسه |
| `admin-demo/` | الفصل 37 | حذفناه في الفصل 42 |
| `app/views/admin/news_list.php` و`news_form.php` | الفصل 39 | حلّ محلهما `list.php` و`form.php` في الفصل 42 |

## الملفات حسب الفصول

الفصول 0 و1 و10 و11 و13 ليست في الجدول: فيها تجهيز البيئة وأساسيات PHP وMySQL، وما كتبته فيها تجارب أو أسطر تغيّرت لاحقاً.

| الفصل | ما كتبته من ملفات المشروع النهائي |
|---|---|
| 2 | `assets/images/logo.svg` |
| 3–9 | الصفحة الثابتة `index.html`: تنسيقها (3–5) صار في `base.php`، وشريط التنقل (4) في `navbar.php`، والأقسام (5–8) في `homepage/index.php`، والتذييل (9) في `footer.php` |
| 12 | `app/views/base/navbar.php` و`app/views/base/footer.php`: نقلنا إليهما الشريط والتذييل، وصار التذييل نهائياً |
| 14 | بداية `database/schema.sql`: جداول الصفحة الرئيسية |
| 15 | `config.php` |
| 16 | `index.php` الجديد (نقطة الدخول) و`.htaccess` و`.gitignore` و`app/bootstrap.php` وبداية `app/helpers.php`، وملفات `.htaccess` في `app` و`database` و`storage` و`uploads` |
| 17 | بقية `app/helpers.php` |
| 18 | `app/core/DB.php` |
| 19 | `app/core/Router.php` و`app/routes.php` |
| 20 | `app/core/View.php` وقوالب الأخطاء في `app/views/errors/` |
| 21 | `app/core/Model.php` و`app/core/Page.php` |
| 22 | النماذج `app/models/homepage.php` و`app/models/content.php` |
| 23 | `app/core/Auth.php` و`app/controllers/site_stats.php`، وجدولا الزيارات والإحصائيات |
| 24 | القالب الأساسي النهائي `base.php` و`navbar.php`، وشريط الإشعارات `notification_bar.php` وجدوله، و`placeholder.svg` |
| 25 | `app/controllers/homepage.php` (بدايته) و`homepage/index.php` النهائي و`default-slide.svg`، وجداول كلمة المدير والخدمات والتحميلات والأسئلة الشائعة |
| 26 | بقية `homepage.php` وصفحات المديرية الثلاث |
| 27 | الأخبار والنشاطات: متحكمان وأربعة قوالب، و`default-news.svg`، وجداول معارض الصور |
| 28 | المصالح والإقامات والخدمات: ثلاثة متحكمات وثمانية قوالب |
| 29 | `app/core/Form.php` وبداية `app/forms.php` وصفحة «اتصل بنا» |
| 30 | `app/core/Upload.php`، والتحميلات والأسئلة الشائعة والبحث: ثلاثة متحكمات وسبعة قوالب |
| 31 | `app/models/students.php` وجداول الطلاب، والتسجيل والدخول: بداية `app/controllers/students.php` و`login.php` و`register.php`، و`create_admin.php` |
| 32 | لوحة الطالب والطلبات والطباعة: ستة قوالب |
| 33 | الإشعارات والملف الشخصي والبطاقة: خمسة قوالب و`notifications_js.php`، وتنسيق قائمة الإشعارات في `navbar.php` |
| 34 | `app/controllers/staff.php` و`app/core/XlsxWriter.php` وقوالب `app/views/students/admin/` |
| 35 | `assets/css/admin.css` و`assets/js/admin.js` |
| 36 | قوالب اللوحة `layout.php` و`delete.php` و`password.php`، والحارس والدخول والخروج وكلمة المرور في `app/controllers/admin.php` |
| 37 | `app/admin_config.php` وبقية `admin.php`، والقوالب `index.php` و`list.php` و`form.php` في `app/views/admin/` |
| الملحق ب | `database/tools/django_json_to_sql.php` |

## بالأرقام

| الجزء | عدد الملفات | عدد الأسطر |
|---|---|---|
| النواة `app/core` | 9 | 1570 |
| النماذج `app/models` | 3 | 728 |
| المتحكمات `app/controllers` | 14 | 2289 |
| القوالب `app/views` | 57 | 12769 |
| ملفات `app` الأخرى (الدوال المساعدة، الروابط، النماذج، إعدادات الإدارة...) | 6 | 1547 |
| `assets` (CSS و JavaScript والصور) | 6 | 184 |
| `database` (بدون `data.sql`) | 4 | 736 |
| الجذر وملفات `.htaccess` في `storage` و `uploads` | 6 | 175 |
| **المجموع** | **105** | **19998** |

قرابة عشرين ألف سطر، كتبتها كلها بيدك سطراً سطراً، وأنت بدأت من مجلد فارغ.
