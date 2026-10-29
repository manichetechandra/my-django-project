# شجرة ملفات المشروع

::: intro
هذا الملحق خريطة المشروع كاملاً: كل ملف، والفصل الذي كتبته فيه. استعمله لتتأكد أنك لم تنسَ ملفاً، أو لتعرف أين تبحث حين تريد تعديل شيء.
:::

## الشجرة الكاملة

```plaintext dou-setif/
dou-setif/
├── app/
│   ├── controllers/
│   │   ├── activities.php                  ← الفصل 17
│   │   ├── admin.php                       ← الفصل 25
│   │   ├── cities.php                      ← الفصل 18
│   │   ├── contact.php                     ← الفصل 19
│   │   ├── departements.php                ← الفصل 18
│   │   ├── downloads.php                   ← الفصل 20
│   │   ├── faq.php                         ← الفصل 20
│   │   ├── homepage.php                    ← الفصول 15، 16
│   │   ├── news.php                        ← الفصل 17
│   │   ├── search.php                      ← الفصل 20
│   │   ├── services.php                    ← الفصل 18
│   │   ├── site_stats.php                  ← الفصل 13
│   │   ├── staff.php                       ← الفصل 24
│   │   └── students.php                    ← الفصول 21، 22، 23
│   ├── core/
│   │   ├── Auth.php                        ← الفصل 13
│   │   ├── DB.php                          ← الفصل 7
│   │   ├── Form.php                        ← الفصل 19
│   │   ├── Model.php                       ← الفصل 11
│   │   ├── Page.php                        ← الفصل 11
│   │   ├── Router.php                      ← الفصل 9
│   │   ├── Upload.php                      ← الفصل 20
│   │   ├── View.php                        ← الفصل 10
│   │   └── XlsxWriter.php                  ← الفصل 24
│   ├── models/
│   │   ├── content.php                     ← الفصل 12
│   │   ├── homepage.php                    ← الفصل 12
│   │   └── students.php                    ← الفصل 12
│   ├── views/
│   │   ├── activities/
│   │   │   ├── activity_detail.php         ← الفصل 17
│   │   │   └── activity_list.php           ← الفصل 17
│   │   ├── admin/
│   │   │   ├── delete.php                  ← الفصل 25
│   │   │   ├── form.php                    ← الفصل 25
│   │   │   ├── index.php                   ← الفصل 25
│   │   │   ├── layout.php                  ← الفصل 25
│   │   │   ├── list.php                    ← الفصل 25
│   │   │   └── password.php                ← الفصل 25
│   │   ├── base/
│   │   │   ├── base.php                    ← الفصل 14
│   │   │   ├── footer.php                  ← الفصل 14
│   │   │   ├── navbar.php                  ← الفصل 14
│   │   │   └── notifications_js.php        ← الفصل 23
│   │   ├── cities/
│   │   │   ├── cite_detail.php             ← الفصل 18
│   │   │   └── cite_list.php               ← الفصل 18
│   │   ├── contact/
│   │   │   └── contact.php                 ← الفصل 19
│   │   ├── departements/
│   │   │   ├── departement_detail.php      ← الفصل 18
│   │   │   └── departement_list.php        ← الفصل 18
│   │   ├── downloads/
│   │   │   ├── _file_card.php              ← الفصل 20
│   │   │   ├── _styles.php                 ← الفصل 20
│   │   │   ├── download_by_category.php    ← الفصل 20
│   │   │   └── download_center.php         ← الفصل 20
│   │   ├── errors/
│   │   │   ├── 403.php                     ← الفصل 10
│   │   │   ├── 403_csrf.php                ← الفصل 10
│   │   │   ├── 404.php                     ← الفصل 10
│   │   │   ├── 405.php                     ← الفصل 10
│   │   │   ├── 500.php                     ← الفصل 10
│   │   │   └── _error.php                  ← الفصل 10
│   │   ├── faq/
│   │   │   ├── faq_detail.php              ← الفصل 20
│   │   │   └── faq_list.php                ← الفصل 20
│   │   ├── homepage/
│   │   │   ├── director_word.php           ← الفصل 16
│   │   │   ├── directorate_history.php     ← الفصل 16
│   │   │   ├── index.php                   ← الفصل 15
│   │   │   └── organization_structure.php  ← الفصل 16
│   │   ├── news/
│   │   │   ├── news_detail.php             ← الفصل 17
│   │   │   └── news_list.php               ← الفصل 17
│   │   ├── notifications/
│   │   │   └── notification_bar.php        ← الفصل 14
│   │   ├── search/
│   │   │   └── search_results.php          ← الفصل 20
│   │   ├── services/
│   │   │   ├── service_by_type.php         ← الفصل 18
│   │   │   ├── service_card.php            ← الفصل 18
│   │   │   ├── service_detail.php          ← الفصل 18
│   │   │   └── service_list.php            ← الفصل 18
│   │   └── students/
│   │       ├── admin/
│   │       │   ├── application_detail.php  ← الفصل 24
│   │       │   ├── applications_list.php   ← الفصل 24
│   │       │   └── dashboard.php           ← الفصل 24
│   │       ├── profile/
│   │       │   ├── change_password.php     ← الفصل 23
│   │       │   ├── edit.php                ← الفصل 23
│   │       │   ├── student_card.php        ← الفصل 23
│   │       │   └── view.php                ← الفصل 23
│   │       ├── application_detail.php      ← الفصل 22
│   │       ├── application_print.php       ← الفصل 22
│   │       ├── create_application.php      ← الفصل 22
│   │       ├── dashboard.php               ← الفصل 22
│   │       ├── login.php                   ← الفصل 21
│   │       ├── my_applications.php         ← الفصل 22
│   │       ├── notifications_list.php      ← الفصل 23
│   │       ├── print_all.php               ← الفصل 22
│   │       └── register.php                ← الفصل 21
│   ├── .htaccess                           ← الفصل 5
│   ├── admin_config.php                    ← الفصل 25
│   ├── bootstrap.php                       ← الفصل 5
│   ├── forms.php                           ← الفصول 19، 21، 22، 23
│   ├── helpers.php                         ← الفصول 5، 8
│   └── routes.php                          ← الفصل 9
├── assets/
│   ├── css/
│   │   └── admin.css                       ← الفصل 25
│   ├── images/
│   │   ├── default-news.svg                ← الفصل 14
│   │   ├── default-slide.svg               ← الفصل 14
│   │   ├── logo.svg                        ← الفصل 14
│   │   └── placeholder.svg                 ← الفصل 14
│   └── js/
│       └── admin.js                        ← الفصل 25
├── database/
│   ├── tools/
│   │   ├── create_admin.php                ← الفصل 21
│   │   └── django_json_to_sql.php          ← الملحق ب
│   ├── .htaccess                           ← الفصل 5
│   ├── data.sql                            ← البيانات الجاهزة (الفصل 6)
│   └── schema.sql                          ← الفصل 6
├── storage/
│   ├── logs/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── private/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── sessions/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   └── .htaccess                           ← الفصل 5
├── uploads/
│   ├── departements/
│   │   ├── bourse.jpg                      ← صورة من الموقع الأصلي
│   │   └── drh.jpg                         ← صورة من الموقع الأصلي
│   ├── director/
│   │   └── drh.jpg                         ← صورة من الموقع الأصلي
│   ├── news/
│   │   └── main/
│   │       └── Lighthouse.jpg              ← صورة من الموقع الأصلي
│   └── .htaccess                           ← الفصل 5
├── .gitignore                              ← الفصل 5
├── .htaccess                               ← الفصل 5
├── config.php                              ← الفصل 5
├── index.php                               ← الفصل 5
└── README.md                               ← دليل التثبيت المختصر
```

## ما لا تكتبه بيدك

بعض الملفات ليست كوداً يُكتب، بل تنسخها من النسخة المرجعية `dou-setif-php.zip`:

| الملف | ما هو؟ |
|---|---|
| `database/data.sql` | بيانات الموقع الأصلي (أخبار، إقامات، حسابات...)، تستوردها في الفصل 6 |
| `uploads/.../*.jpg` | الصور القليلة التي نُقلت من الموقع الأصلي، والباقي ترفعه من لوحة الإدارة |
| `storage/*/.gitkeep` | ملفات فارغة تُبقي المجلدات الفارغة في Git، فتوجد المجلدات عند تنزيل المشروع |
| `README.md` | دليل التثبيت المختصر |
| `database/tools/django_json_to_sql.php` | أداة نقل البيانات من Django، يشرحها الملحق ب |

## الملفات حسب الفصول

| الفصل | الملفات |
|---|---|
| 5 | `index.php` و `config.php` و `.htaccess` و `.gitignore` و `app/bootstrap.php` وبداية `app/helpers.php`، وملفات `.htaccess` في `app` و `database` و `storage` و `uploads` |
| 6 | `database/schema.sql` |
| 7 | `app/core/DB.php` |
| 8 | بقية `app/helpers.php` |
| 9 | `app/core/Router.php` و `app/routes.php` |
| 10 | `app/core/View.php` وقوالب الأخطاء `app/views/errors/` |
| 11 | `app/core/Model.php` و `app/core/Page.php` |
| 12 | النماذج: `app/models/homepage.php` و `content.php` و `students.php` |
| 13 | `app/core/Auth.php` و `app/controllers/site_stats.php` |
| 14 | القالب الأساسي `app/views/base/` (ما عدا `notifications_js.php`)، وشريط الإشعارات، والصور `assets/images/` |
| 15 | `app/controllers/homepage.php` (بدايته) و `app/views/homepage/index.php` |
| 16 | بقية `homepage.php` وصفحات المديرية الثلاث |
| 17 | الأخبار والنشاطات: متحكمان وأربعة قوالب |
| 18 | المصالح والإقامات والخدمات: ثلاثة متحكمات وثمانية قوالب |
| 19 | `app/core/Form.php` وبداية `app/forms.php` و «اتصل بنا» |
| 20 | `app/core/Upload.php` والتحميلات والأسئلة الشائعة والبحث |
| 21 | التسجيل والدخول: بداية `app/controllers/students.php`، و `login.php` و `register.php`، و `create_admin.php` |
| 22 | لوحة الطالب والطلبات والطباعة |
| 23 | الإشعارات والملف الشخصي والبطاقة، و `notifications_js.php` |
| 24 | `app/controllers/staff.php` و `app/core/XlsxWriter.php` وقوالب `app/views/students/admin/` |
| 25 | `app/admin_config.php` و `app/controllers/admin.php` وقوالب `app/views/admin/` و `admin.css` و `admin.js` |

## بالأرقام

| الجزء | عدد الملفات | عدد الأسطر |
|---|---|---|
| النواة `app/core` | 9 | 1570 |
| النماذج `app/models` | 3 | 728 |
| المتحكمات `app/controllers` | 14 | 2289 |
| القوالب `app/views` | 57 | 12759 |
| ملفات `app` الأخرى (الدوال المساعدة، الروابط، النماذج، إعدادات الإدارة...) | 6 | 1547 |
| `assets` (CSS و JavaScript والصور) | 6 | 180 |
| `database` (بدون `data.sql`) | 4 | 736 |
| الجذر وملفات `.htaccess` في `storage` و `uploads` | 6 | 175 |
| **المجموع** | **105** | **19984** |

قرابة عشرين ألف سطر كتبتها سطراً سطراً، وأكثر من ستين بالمئة منها قوالب HTML: الجزء الأكبر من أي موقع هو ما يراه الزائر.

::: tip
لتتأكد أن ملفك مطابق للأصل، قارنه بالملف نفسه في `dou-setif-php.zip`. في VS Code: افتح الملفين، ثم من لوحة الأوامر (Ctrl+Shift+P) اختر **Compare Active File With...**، فتظهر الفروق ملوّنة سطراً بسطر.
:::
