# شجرة ملفات المشروع

::: intro
هذا الملحق خريطة المشروع النهائي كاملاً، أي النسخة 2.0 من البرنامج: كل ملف، والفصول التي كتبت فيها أسطره النهائية. استعمله لتتأكد أنك لم تنسَ ملفاً، أو لتعرف أين تبحث حين تريد تعديل شيء. وفي آخره الملفات التي ينشئها الموقع وحده وهو يعمل، والملفات المؤقتة التي أنشأناها في الطريق ثم حذفناها، فلا يبقى منها شيء في مجلدك.
:::

## الشجرة الكاملة

```plaintext dou-setif/
dou-setif/
├── app/
│   ├── controllers/
│   │   ├── activities.php                  ← الفصل 29
│   │   ├── admin.php                       ← الفصول 36، 38، 40–42، 48
│   │   ├── cities.php                      ← الفصل 30
│   │   ├── competitions.php                ← الفصول 45–47
│   │   ├── contact.php                     ← الفصل 31
│   │   ├── departements.php                ← الفصل 30
│   │   ├── downloads.php                   ← الفصل 32
│   │   ├── faq.php                         ← الفصل 32
│   │   ├── homepage.php                    ← الفصول 27، 28، 45
│   │   ├── news.php                        ← الفصل 29
│   │   ├── recruitment_admin.php           ← الفصل 48
│   │   ├── search.php                      ← الفصل 32
│   │   ├── services.php                    ← الفصل 30
│   │   ├── site_stats.php                  ← الفصل 24
│   │   ├── staff.php                       ← الفصل 36
│   │   └── students.php                    ← الفصول 33–35
│   ├── core/
│   │   ├── Auth.php                        ← الفصل 24
│   │   ├── Barcode.php                     ← الفصل 34
│   │   ├── Crypto.php                      ← الفصل 43
│   │   ├── DB.php                          ← الفصل 19
│   │   ├── Form.php                        ← الفصل 31
│   │   ├── Model.php                       ← الفصل 22
│   │   ├── Page.php                        ← الفصل 22
│   │   ├── PdfGuard.php                    ← الفصل 46
│   │   ├── RateLimit.php                   ← الفصل 31
│   │   ├── RichText.php                    ← الفصل 25
│   │   ├── Router.php                      ← الفصل 20
│   │   ├── Translator.php                  ← الفصل 40
│   │   ├── Upload.php                      ← الفصل 32
│   │   ├── View.php                        ← الفصل 21
│   │   └── XlsxWriter.php                  ← الفصل 36
│   ├── lang/
│   │   ├── en.php                          ← الفصول 18، 21، 23، 26–36، 44–47
│   │   └── fr.php                          ← الفصول 18، 21، 23، 26–36، 44–47
│   ├── models/
│   │   ├── content.php                     ← الفصل 23
│   │   ├── homepage.php                    ← الفصل 23
│   │   ├── recruitment.php                 ← الفصل 44
│   │   └── students.php                    ← الفصل 33
│   ├── views/
│   │   ├── activities/
│   │   │   ├── activity_detail.php         ← الفصل 29
│   │   │   └── activity_list.php           ← الفصل 29
│   │   ├── admin/
│   │   │   ├── recruitment/
│   │   │   │   ├── application.php         ← الفصل 48
│   │   │   │   ├── applications.php        ← الفصل 48
│   │   │   │   ├── audit.php               ← الفصل 48
│   │   │   │   ├── purge.php               ← الفصل 48
│   │   │   │   └── results.php             ← الفصل 48
│   │   │   ├── delete.php                  ← الفصل 39
│   │   │   ├── form.php                    ← الفصل 42
│   │   │   ├── index.php                   ← الفصل 42
│   │   │   ├── layout.php                  ← الفصل 38
│   │   │   ├── list.php                    ← الفصل 42
│   │   │   ├── login.php                   ← الفصل 36
│   │   │   ├── password.php                ← الفصل 38
│   │   │   ├── recover.php                 ← الفصل 38
│   │   │   └── translate_all.php           ← الفصل 40
│   │   ├── base/
│   │   │   ├── base.php                    ← الفصول 3–5، 26
│   │   │   ├── footer.php                  ← الفصول 9، 26
│   │   │   ├── navbar.php                  ← الفصول 4، 26، 35، 45
│   │   │   └── notifications_js.php        ← الفصل 35
│   │   ├── cities/
│   │   │   ├── cite_detail.php             ← الفصل 30
│   │   │   └── cite_list.php               ← الفصل 30
│   │   ├── competitions/
│   │   │   ├── _print_close.php            ← الفصل 47
│   │   │   ├── _print_open.php             ← الفصل 47
│   │   │   ├── apply.php                   ← الفصل 46
│   │   │   ├── detail.php                  ← الفصل 45
│   │   │   ├── list.php                    ← الفصل 45
│   │   │   ├── print_convocation.php       ← الفصل 47
│   │   │   ├── print_result.php            ← الفصل 47
│   │   │   ├── receipt.php                 ← الفصل 46
│   │   │   └── track.php                   ← الفصل 47
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
│   │   │   ├── index.php                   ← الفصول 5–8، 27، 45
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
│   ├── admin_config.php                    ← الفصول 42، 44
│   ├── bootstrap.php                       ← الفصل 16
│   ├── forms.php                           ← الفصول 31، 33–35
│   ├── helpers.php                         ← الفصول 16، 17، 25، 31
│   ├── i18n.php                            ← الفصل 18
│   └── routes.php                          ← الفصل 20
├── assets/
│   ├── css/
│   │   ├── admin.css                       ← الفصول 36–38، 40–42، 48
│   │   ├── competitions-print.css          ← الفصل 47
│   │   ├── competitions.css                ← الفصول 45–47
│   │   └── rich-text.css                   ← الفصل 25
│   ├── images/
│   │   ├── default-news.svg                ← الفصل 29
│   │   ├── default-slide.svg               ← الفصل 27
│   │   ├── logo.svg                        ← الفصل 2
│   │   └── placeholder.svg                 ← الفصل 26
│   ├── js/
│   │   ├── tinymce-lang/                   ← العربية لكل إصدار من TinyMCE، تنسخه (الفصل 41)
│   │   ├── admin-richtext.js               ← الفصل 41
│   │   ├── admin.js                        ← الفصول 37، 40
│   │   └── competitions.js                 ← الفصل 46
│   └── vendor/
│       └── tinymce-8.10.1/                 ← محرر TinyMCE كما نُشر، تنسخه (الفصل 41)
├── database/
│   ├── migrations/
│   │   ├── 2026_10_i18n.sql                ← الملحق ج
│   │   ├── 2026_11_recruitment.sql         ← الملحق ج
│   │   └── 2026_11_recruitment_2_convocations.sql ← الملحق ج
│   ├── tools/
│   │   ├── create_admin.php                ← الفصل 33
│   │   ├── django_json_to_sql.php          ← الملحق ب
│   │   ├── set_password.php                ← الفصل 38
│   │   └── translate_content.php           ← الفصل 40
│   ├── .htaccess                           ← الفصل 16
│   ├── data.sql                            ← بيانات الموقع الأصلي (الملحق ب)
│   └── schema.sql                          ← الفصول 14، 24، 26–31، 33، 43
├── storage/
│   ├── logs/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── private/
│   │   └── .gitkeep                        ← يُبقي المجلد الفارغ
│   ├── ratelimit/
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
├── config.php                              ← الفصول 15، 17، 18، 40، 41، 43
├── index.php                               ← الفصل 16
├── README.md                               ← دليل التثبيت والاستعمال المختصر
└── SECURITY.md                             ← ملخص الحماية وقائمة النشر
```

### كيف تقرأ أرقام الفصول؟

- **فصل واحد**: كتبت الملف كاملاً في ذلك الفصل.
- **عدة فصول**: كبر الملف على مراحل. `students.php` مثلاً بدأ بالتسجيل والدخول في الفصل 33، ثم أضفنا إليه الطلبات في الفصل 34 والإشعارات والملف الشخصي في الفصل 35. و`schema.sql` يكبر جدولاً بعد جدول، كلما احتاجت صفحة جديدة إلى بيانات، حتى جداول المسابقات في الفصل 43. و`admin.php` بدأ بواجهة دخول المدير في الفصل 36، وكبر مع كل قسم من أقسام اللوحة.
- **ملفا الترجمة** `fr.php` و`en.php`: كل فصل فيه نصوص جديدة يراها الزائر يضيف إليهما ترجماتها، لذلك تتكرر فيهما أرقام الفصول.
- **الفصول 3–9 في ملفات القالب**: أسطر التنسيق والأقسام في `base.php` و`navbar.php` و`footer.php` و`homepage/index.php` كتبتها أول مرة في الصفحة الثابتة `index.html`، ثم نقلتها كما هي إلى ملفاتها النهائية في الفصول 12 و26 و27 (صناديق «منقول»).

## ما لا تكتبه بيدك

بعض الملفات في النسخة المرجعية `dou-setif-php-v2.zip` ليست كوداً يُكتب:

| الملف | ما هو؟ |
|---|---|
| `assets/vendor/tinymce-8.10.1/` | محرر النصوص TinyMCE كما نشرته شركة Tiny، بلا تعديل. تنسخه ولا تكتبه (الفصل 41) |
| `assets/js/tinymce-lang/` | ملفات الواجهة العربية لكل إصدار من TinyMCE، من 4 إلى 8. تنسخها أيضاً (الفصل 41) |
| `database/data.sql` | بيانات الموقع الأصلي (أخبار، إقامات، حسابات...). لا تحتاجها، فقاعدتك ملأتها بنفسك. من يريد محتوى الموقع الأصلي يستوردها كما في الملحق ب |
| `uploads/.../*.jpg` | أربع صور من الموقع الأصلي. أما مجلد `uploads/` عندك ففيه صورك أنت: التي وضعتها في الفصول 5–8، والتي رفعتها من لوحة الإدارة |
| `storage/*/.gitkeep` | ملفات فارغة تُبقي المجلدات الفارغة في Git (الفصل 16)، فتوجد المجلدات عند تنزيل المشروع. أنشأت `logs` و`private` و`sessions` في الفصل 16، وينشئ الموقع `ratelimit` وحده أول مرة (الفصل 31) |
| `README.md` | دليل التثبيت والاستعمال المختصر: التثبيت، والحسابات، واللغات ومفتاح Gemini، والمسابقات، والمحرر، والترقية |
| `SECURITY.md` | ملخص الحماية، وقائمة ما تفعله قبل النشر وبعده، وهو ما شرحه الفصل 49 بالتفصيل |
| `database/tools/django_json_to_sql.php` | أداة نقل البيانات من Django، يشرحها الملحق ب |
| `database/migrations/` | ملفات ترقية موقع يعمل بالنسخة 1.0، يشرحها الملحق ج |

## ما ينشئه الموقع وهو يعمل

حين تستعمل الموقع تظهر في مجلدك ملفات ومجلدات ليست في الشجرة. لا تحذفها، فهي بيانات موقعك:

| المكان | ما فيه |
|---|---|
| `uploads/` | الصور والملفات التي ترفعها من لوحة الإدارة، في مجلد لكل قسم، ومنها `uploads/editor/` لصور المحرر (الفصل 41) |
| `storage/private/documents/` | وثائق الطلبة المرفوعة مع طلباتهم (الفصل 34) |
| `storage/private/recruitment.key` | مفتاح تشفير ملفات المترشحين، يُنشأ مع أول استعمال (الفصل 43). **إن ضاع لا يُقرأ أي ملف مشفّر**: خذ منه نسخة كما في الفصل 49 |
| `storage/private/recruitment/` | ملفات المترشحين المشفّرة، في مجلد لكل مسابقة (الفصل 46) |
| `storage/logs/` | سجل الأخطاء `php-errors.log` وسجل الأمان `security.log` (الفصلان 16 و17) |
| `storage/sessions/` و`storage/ratelimit/` | ملفات الجلسات وعدّادات المحاولات. حذفها يُخرج المستخدمين من حساباتهم ويصفّر العدّادات، لا أكثر |

## الملفات المؤقتة

أنشأنا في الطريق ملفات للتجربة أو للمقارنة، ثم حذفناها. تأكد أن لا شيء منها بقي في مجلدك، وخصوصاً قبل النشر (الفصل 49):

| الملف | أنشأناه في | مصيره |
|---|---|---|
| `test.txt` | الفصل 1 | حذفناه بعد التأكد أن Apache يقدّم ملفات المجلد |
| `index.html` | الفصول 2–9 | صار `index.php` في الفصل 10 |
| `info.php` و`test.php` | الفصل 10 | حذفناهما: الأول يكشف معلومات عن الخادم، والثاني للأمثلة فقط |
| قاعدة `test_db` | الفصل 13 | حذفناها بالأمر `DROP DATABASE` في آخر الفصل نفسه |
| `database/sample_data.sql` | الفصل 14 | حذفناه في الفصل 27، وبقيت بياناته في القاعدة |
| `index_old.php` | الفصل 16 (هو `index.php` القديم) | حذفناه في الفصل 27، بعد أن انتقل كل محتواه إلى القوالب |
| `hello.php` | الفصل 17 (وكبر حتى الفصل 25) | حذفناه في الفصل 27 |
| `hello_view.php` و`app/views/test/` | الفصل 21 | حذفناهما في آخر الفصل نفسه |
| `hello_model.php` | الفصل 22 | حذفناه في آخر الفصل نفسه |
| `admin-demo/` | الفصل 37 | حذفناه في الفصل 42 |
| `news_list.php` و`news_form.php` و`_news_tr.php` في `app/views/admin/` | الفصلان 39 و40 | حلّ محلها `list.php` و`form.php` في الفصل 42 |
| `tr_test.php` | الفصل 40 | حذفناه في الفصل نفسه |
| `crypto_test.php`، و`storage/private/test.enc` من تمارينه | الفصل 43 | حذفنا الأول في الفصل نفسه، واحذف الثاني إن صنعته. أما ملف المفتاح بجانبه فيبقى |
| `codes_test.php` | تمارين الفصل 44 | احذفه بعد التمرين |
| `pdf_test.php` و`test-ok.pdf` و`test-js.pdf` | الفصل 46 | حذفناها في الفصل نفسه |

## الملفات حسب الفصول

الفصول 0 و1 و10 و11 و13 ليست في الجدول: فيها تجهيز البيئة وأساسيات PHP وMySQL، وما كتبته فيها تجارب أو أسطر تغيّرت لاحقاً. ولا الفصل 49: فيه مراجعة الحماية والنشر، بلا ملف جديد.

| الفصل | ما كتبته من ملفات المشروع النهائي |
|---|---|
| 2 | `assets/images/logo.svg` |
| 3–9 | الصفحة الثابتة `index.html`: تنسيقها (3–5) صار في `base.php`، وشريط التنقل (4) في `navbar.php`، والأقسام (5–8) في `homepage/index.php`، والتذييل (9) في `footer.php` |
| 12 | `app/views/base/navbar.php` و`app/views/base/footer.php`: نقلنا إليهما الشريط والتذييل |
| 14 | بداية `database/schema.sql`: جدول المستخدمين وجداول الصفحة الرئيسية |
| 15 | بداية `config.php`: قاعدة البيانات وإعدادات الموقع |
| 16 | `index.php` الجديد (نقطة الدخول) و`.htaccess` و`.gitignore` و`app/bootstrap.php` وبداية `app/helpers.php`، وملفات `.htaccess` في `app` و`database` و`storage` و`uploads` |
| 17 | بقية `app/helpers.php` الأساسية، وقسم الأمان في `config.php` |
| 18 | `app/i18n.php`، وبداية ملفَّي الترجمة `app/lang/fr.php` و`app/lang/en.php`، وقسم اللغات في `config.php` |
| 19 | `app/core/DB.php` |
| 20 | `app/core/Router.php` و`app/routes.php` |
| 21 | `app/core/View.php` وقوالب الأخطاء في `app/views/errors/` |
| 22 | `app/core/Model.php` و`app/core/Page.php` |
| 23 | النماذج `app/models/homepage.php` و`app/models/content.php` |
| 24 | `app/core/Auth.php` و`app/controllers/site_stats.php`، وجدولا الزيارات والإحصائيات |
| 25 | منظّف النصوص المنسّقة `app/core/RichText.php` وتنسيقها `assets/css/rich-text.css`، ودالة عرضها في `helpers.php` |
| 26 | القالب الأساسي النهائي `base.php` و`navbar.php` و`footer.php`، وشريط الإشعارات `notification_bar.php` وجدوله، و`placeholder.svg` |
| 27 | `app/controllers/homepage.php` (بدايته) و`homepage/index.php` النهائي و`default-slide.svg`، وجداول كلمة المدير والخدمات والتحميلات والأسئلة الشائعة |
| 28 | بقية `homepage.php` وصفحات المديرية الثلاث، وجدولاها |
| 29 | الأخبار والنشاطات: متحكمان وأربعة قوالب، و`default-news.svg`، وجداول معارض الصور وشريط آخر الأخبار |
| 30 | المصالح والإقامات والخدمات: ثلاثة متحكمات وثمانية قوالب، وجدول صور الإقامات |
| 31 | `app/core/Form.php` و`app/core/RateLimit.php` وبداية `app/forms.php`، وصفحة «اتصل بنا» وجدولها، ودوال حد محاولات الدخول في `helpers.php` |
| 32 | `app/core/Upload.php`، والتحميلات والأسئلة الشائعة والبحث: ثلاثة متحكمات وسبعة قوالب |
| 33 | `app/models/students.php` وجداول الطلاب، والتسجيل والدخول: بداية `app/controllers/students.php` و`login.php` و`register.php`، و`create_admin.php` |
| 34 | لوحة الطالب والطلبات والطباعة: ستة قوالب، و`app/core/Barcode.php` |
| 35 | الإشعارات والملف الشخصي والبطاقة: خمسة قوالب و`notifications_js.php`، وقائمة الإشعارات في `navbar.php` |
| 36 | فضاء المشرف: `staff.php` و`XlsxWriter.php` وقوالب `app/views/students/admin/`، وواجهة دخول المدير: بداية `admin.php` و`app/views/admin/login.php` وتنسيقها في `admin.css` |
| 37 | تصميم اللوحة: أغلب `assets/css/admin.css`، وبداية `assets/js/admin.js` |
| 38 | حارس اللوحة في `admin.php`، والقوالب `layout.php` و`password.php` و`recover.php`، و`set_password.php` |
| 39 | `app/views/admin/delete.php`، وإدارة الأخبار بقوالب مؤقتة |
| 40 | `app/core/Translator.php` و`translate_all.php` و`translate_content.php`، وقسم الترجمة في `config.php` |
| 41 | `assets/js/admin-richtext.js` وقسم المحرر في `config.php`، ونسخة TinyMCE وملفات العربية |
| 42 | `app/admin_config.php` وبقية `admin.php`، والقوالب `index.php` و`list.php` و`form.php` في `app/views/admin/` |
| 43 | `app/core/Crypto.php`، وجداول المسابقات الثمانية، وقسم المسابقات في `config.php` |
| 44 | `app/models/recruitment.php`، وأقسام المسابقات في `admin_config.php` |
| 45 | `app/controllers/competitions.php` (بدايته) وقالبا القائمة والصفحة و`competitions.css`، ورابط القائمة العلوية وصندوق الرئيسية |
| 46 | الترشح الإلكتروني: `app/core/PdfGuard.php` و`apply.php` و`receipt.php` و`competitions.js` |
| 47 | فضاء المترشح: `track.php` وقوالب الطباعة الأربعة و`competitions-print.css` |
| 48 | `app/controllers/recruitment_admin.php` وقوالب `app/views/admin/recruitment/` الخمسة |
| الملحق&nbsp;ب | `database/tools/django_json_to_sql.php` |
| الملحق&nbsp;ج | ملفات الترقية الثلاثة في `database/migrations/` |

## بالأرقام

| الجزء | عدد الملفات | عدد الأسطر |
|---|---|---|
| النواة `app/core` | 15 | 3433 |
| النماذج `app/models` | 4 | 1257 |
| المتحكمات `app/controllers` | 16 | 4592 |
| القوالب `app/views` | 74 | 14740 |
| ملفا الترجمة `app/lang` | 2 | 1982 |
| ملفات `app` الأخرى (الدوال المساعدة، اللغات، الروابط، النماذج، إعدادات الإدارة...) | 7 | 2174 |
| `assets` (CSS وJavaScript والصور، بدون TinyMCE) | 11 | 1043 |
| `database` (بدون `data.sql`) | 9 | 1541 |
| الجذر وملفات `.htaccess` في `storage` و`uploads` | 6 | 280 |
| **المجموع** | **144** | **31042** |

قرابة واحد وثلاثين ألف سطر، كتبتها كلها بيدك سطراً سطراً، وأنت بدأت من مجلد فارغ.
