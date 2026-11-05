# الإصدار الثالث للكتاب — من مجلد فارغ حتى البرنامج 2 (قيد العمل)

> **قرار المستخدم (ask_user، 2026-11-05):** «كتاب واحد جديد كامل» — من مجلد فارغ حتى البرنامج 2 بكل شيء
> (تعدد اللغات والترجمة الآلية، إصلاحات العرض والأمان، المسابقات وفضاء المترشح، شريط التنقل، محرر TinyMCE بكل أدواته).
> الكود النهائي **مطابق حرفياً لـ `php/`** (= `dou-setif-php-v2.zip`)، والكتاب يُسلَّم **كاملاً مرة واحدة**؛ إلى ذلك الحين يبقى
> `dou-setif-book.pdf` (الإصدار الثاني، النسخة 1.0) كما هو. المتطلبات نفسها: عربي، مفصّل جداً، لمبتدئ، ثابت ← ديناميكي، تغطية كاملة.

## الأدوات
- `tools/project.mjs`: المصدر الافتراضي صار `php/` (النسخة 2). `BOOK_V1=1` يعيد الكتاب السابق (dou-setif-php.zip).
- `tools/remap_v2.py`: نقل إشارات الفصول من أسطر 1.0 إلى أسطر 2 (difflib): `@file` و `@moved` و `from=` وأرقام الأسطر في النص.
  طُبّق مرة واحدة (2026-11-05): 81 إشارة مطابقة (إزاحة فقط)، 322 تغيّر محتواها، 39 ملف كامل. التقرير `.build/remap-report.txt`
  (لا تشغّله مرة أخرى بعد التطبيق: الفصول صارت بأسطر النسخة 2).
- التغطية تستثني الآن أيضاً: `SECURITY.md`، و `assets/vendor/` (TinyMCE يحمّله القارئ)، و `assets/js/tinymce-lang/`.
- بعد النقل: `node tools/build.mjs --coverage` يعمل بلا أخطاء — **99/144 ملف مكتمل**؛ `node tools/checkrefs.mjs` ← 135 إشارة نصية للمراجعة.

## ترتيب الفصول النهائي (أُعيد الترقيم بـ tools/renumber_v3.py — طُبّق مرة واحدة)
القديم ← الجديد: 0–17 كما هي؛ 18←19 … 23←24؛ 24←26؛ 25←27 … 34←36؛ 35←37؛ 36←39؛ 37←42؛ 38←48.
الترتيب يتبع **اعتماد الكود**: لا يستدعي فصلٌ دالة أو صنفاً يُكتب بعده (الحالة الوسيطة تعمل دائماً).

| # | الفصل | ملاحظات |
|---|---|---|
| 01–15 | الصفحة الثابتة، PHP، MySQL | CSS بخصائص منطقية (inline-start) من البداية؛ النصوص التي صارت t() تبقى مؤقتة في الصفحة الثابتة وتُستبدل في 26–27؛ أعمدة _fr و _en تُذكر عند إنشاء الجداول |
| 16 | الهيكل | ملفات فارغة جديدة: i18n.php، RateLimit، Translator، RichText، Barcode، Crypto، PdfGuard، recruitment.php، competitions.php، recruitment_admin.php |
| 17 | الدوال المساعدة | + الأمان (safe_url، redirect_path، safe_next، الترويسات، security_log، client_ip...)، can_recruit، login_throttle (RateLimit في 31) |
| **18** | **تعدد اللغات** (جديد) | app/i18n.php، ملفات lang (الرأس والسطر الأخير)، /fr/ و /en/ |
| 19–24 | DB، الموجّه، القوالب، Model (+translatable، tr)، النماذج، Auth | |
| **25** | **النص المنسّق الآمن** (جديد) | RichText.php، rich-text.css (rich_text() في القوالب من 27) |
| 26–27 | القالب الرئيسي، الرئيسية النهائية | مبدّل اللغة، hreflang، الإبراز النشط في الشريط، t() في النصوص المنقولة (استبدال) |
| 28–32 | الصفحات | 31: + RateLimit (حماية «اتصل بنا») |
| 33–35 | فضاء الطالب | |
| 36 | الموظفون | |
| 37 | الإدارة: التصميم الثابت | |
| **38** | **حماية اللوحة وقالبها وكلمة المرور** (جديد) | الحارس admin_guard و admin_render، قائمة ورئيسية مؤقتتان، layout.php، admin_password + password.php، الاستعادة admin_recover + recover.php + admin.css 122-129، set_password.php (الدخول/الخروج انتقلا إلى 36) |
| 39 | إدارة الأخبار يدوياً (مؤقت) | |
| **40** | **الترجمة الآلية** (جديد) | Translator.php، translate_content.php، صفحة «ترجمة المحتوى» (admin_translate_all + translate_all.php) |
| **41** | **محرر النصوص TinyMCE** (جديد) | admin_tinymce/locate، admin_editor_upload، admin-richtext.js — يُجرَّب على نموذج الأخبار المؤقت |
| 42 | المحرك العام للوحة | + حقول FR/EN وزر الترجمة والترجمة عند الحفظ، والمحرر في form.php؛ **بدون** أقسام المسابقات (admin_config 477–566، admin_menu 74–85) |
| **43–48** | **المسابقات** (جديد، 6 فصول) | 43 التصميم والمخاطر والجداول (schema 16-23، 605-795) و config 105-121 و Crypto (+ crypto_test.php مؤقت)؛ 44 models/recruitment.php و lang 44 و admin_config 477-566 (القارئ يضيف مسابقة من اللوحة: ثمرة المحرك العام)؛ 45 الإعلانات: competitions.php 1-97، list/detail، competitions.css (1-88، 155-161، 207-226)، navbar 79-84، الرئيسية (index.php 5، 315-336، homepage.php 19)؛ 46 الترشح: PdfGuard، competitions.php 98-474، apply/receipt، competitions.js، css 89-132؛ 47 فضاء المترشح: 476-706، track و _print_* و print_*، competitions-print.css، css 133-154 و 162-206 (يُختبر بـ UPDATE في phpMyAdmin)؛ 48 لوحة الإدارة: recruitment_admin.php، views/admin/recruitment، admin.css 138-177، admin.php 74-84 و 103-111 |
| 49 | الأمان والنشر | + الترويسات، السجلات، مفتاح التشفير ونسخه، HTTPS، استبدال `/dou-setif/` في ملف SQL (صور المحرر) |
| أ، ب، **ج** | الشجرة، استيراد Django، **ترقية موقع قائم من 1.0** (migrations) | |

ملفات اللغة: كل فصل يبني واجهة ينتهي بـ «ترجمة نصوص هذا الفصل» (قطعة fr.php وقطعة en.php). يُعاد ترتيب أقسام
ملفَّي اللغة في php/ حسب الفصل الذي يظهر فيه كل نص أول مرة (أداة آلية: موضع النص في الكود ← الفصل من خريطة التغطية)،
ثم يُعاد بناء dou-setif-php-v2.zip واختبار i18n_keys (الترتيب وحده يتغيّر، لا المفاتيح).

## قرارات أثناء الكتابة (الإصدار الثالث)
- **دخول الموظفين في الفصل 36** (لا 38): require_staff ترسل إلى admin:login، والموظف لا يدخل من فضاء الطالب،
  فلا تُختبر لوحة الموظفين بدونه. الفصل 36 يبدأ بـ admin_login و admin_logout (admin.php 130-187)
  و views/admin/login.php وقسم تصميم الدخول من admin.css. الفصل 38 يبقى للحماية (admin_guard)
  وتغيير كلمة المرور والاستعادة (recover) والأدوات. جدول الروابط كاملاً في الفصل 20 (admin:index معرّف مبكراً).
- **helpers.php**: الفصل 17 يعرض كل شيء ما عدا: محاولات الدخول 583-632 ← الفصل 31 مع RateLimit (too_many_attempts_message يستعملها نموذج الاتصال؛ login_throttle_* تُستعمل في 33 و36)،
  و rich_text/plain_text 820-834 ← الفصل 25 (تحتاج RichText؛ الفصل 17 يعرض 788-819,835-893). time_left و can_recruit بقيتا في 17 مع إشارة.
- **config.php**: 43-46 (trusted_proxies) و 55-72 (security) ← الفصل 17.
- **schema.sql السطر 64 (can_recruit) في الفصل 14** مع جدول users (لا 43): admin_config قسم المستخدمين (الفصل 42) يحفظ العمود.
- **ملفات اللغة**: أقسامها مرتبة حسب الفصول (tools/lang_regroup.py)، وكل فصل يعرض قسمه بالتوجيه `@lang fr` و `@lang en`
  (المدى من tools/lang-sections.json). المسابقات (navbar 79-84، homepage index.php 5 و 315-...، PdfGuard، recruitment.php،
  competitions.php و views/competitions) ← 44/45/46 بقواعد تجاوز في الأداة حتى تُكتب تلك الفصول.
- **اختبار hello.php في 17 بلا تواريخ** (format_date/timesince تحتاج lang()) ← تُجرَّب في 18.
- **بعد الفصل 36 (للفصول القادمة):** الفصل 37 يعرض admin.css 1-82 بالعلامة `insert` (وإلا «تحذير ترتيب» لأن 102-121 سبقت)،
  و reader-sim 37 = admin.css 1-82 + 102-121. الفصول 38 و42 لا تعيد عرض admin.php السطر 1 ولا 126-187 (التغطية تعدّها مكررة).
  **الفصل 38 يجب أن يوفّر admin_index (ولو مؤقتة)**: رابط «إعدادات النظام» في لوحة المشرف والدخول بلا next يقودان إلى /admin/
  (صفحة خطأ 500 حتى ذلك الحين، مذكورة في تجربة الفصل 36). ورابط «نسيت كلمة مرور المدير؟» كذلك حتى admin_recover في 38.
- build.mjs: سطر الفجوة يقول «نضيفها في فصل لاحق»؛ إن كانت الأسطر كُتبت في فصل **سابق** (مثل admin.css 84-190
  في 36 ثم الباقي في 37) يجب أن يقول «كتبناها في الفصل N» ← يُعدَّل build.mjs عند الحاجة.
- **نصوص التجارب التي تخلط وسوم HTML بالكلام** تُكتب بالفرنسية (الفصل 25): build.mjs يعزل العربي داخل سلاسل الكود
  باتجاه rtl، فينقلب ترتيب الوسوم الواقعة وسط كلام عربي. السلاسل العربية الخالصة أو المحاطة بوسم من طرفيها لا بأس بها.
- عنوان كتلة الكود «مثال» لم يعد يُكرَّر بجانب الشارة «مثال» (build.mjs).
- البناء الجزئي السريع للمراجعة: `node tools/build.mjs --only=25` (6 ث، dou-setif-book.partial.pdf مُتجاهَل في git).
- **تعديلات على php/ أثناء الكتابة (تُذكر في الرد النهائي، وتتطلب إعادة بناء dou-setif-php-v2.zip):**
  base.php، تعليق navbar، ترتيب config، docblock plural_unit، حذف tr_field من i18n.php، ترتيب ملفات اللغة، رابط 500.php؛
  وفي الفصل 32: search.php بلا قسم sliders (كان يُعدّ في المجموع ولا يُعرض)، روابط التصفية في search_results.php
  بـ rawurlencode (بحث «A&B» كان ينقطع)، والبحث داخل صفحتي التحميلات والأسئلة يشمل ترجمة لغة الصفحة (title_fr...).
  وفي الفصل 33: تسمية البريد في register.php بلا الصنف required (البريد اختياري)، وتنظيف docblock الدالة notify() في students.php.
  وفي الفصل 34: لوحة الطالب dashboard.php (بطاقة «بطاقتي الرقمية»: مسافة مفقودة قبل class ولون التدرج #f39c ← #f39c12؛
  بطاقتا «معلوماتي الشخصية» و«الإشعارات» كانتا href="#" فصارتا url('students:profile_view') و url('students:notifications_list'))،
  وملاحظات الإدارة في application_detail.php داخل div بدل p (linebreaks تُخرج فقرات p ولا تتداخل الفقرات). Barcode.php صار في الفصل 34
  (صفحات الطباعة تحتاجه) بدل 44، وتعليق جدول BARS فيه صار بلا «1 = …» (كان ينقلب في اتجاه العرض). أداة tools/reader-sim.sh تبني مشروع القارئ حتى فصل معيّن للتجربة.
  build.mjs: كتلة الكود تُلوَّن في سياق الملف كاملاً ثم تُقتطع أسطرها (القطعة التي تبدأ داخل <style> أو <script> كانت بلا تلوين،
  فلا تُعزل تعليقاتها العربية: «← قللنا من 60px» كانت تظهر مقلوبة).
  وفي الفصل 35: navbar.php فقد ~190 سطراً من تنسيق ميت عام (.notifications-header و.notification-item {display:flex}... كانت
  داخل body فتغلب تنسيق الصفحات: رأس صفحة الإشعارات صار داكناً، وإشعارات لوحة الطالب صارت أفقية)، وصارت قائمة الجرس تنفتح نحو داخل
  الصفحة (كانت تخرج من الشاشة في العربية)، وعدّاد رأسها يطفو إلى جهة النهاية (pull-left كان يضعه في البداية بالفرنسية) ← أسطر ch04–ch12
  التي تشير إلى navbar.php أُزيحت. notifications_list.php: أزرار التصفية flex بزوايا منطقية (ترتيبها كان معكوساً في العربية)،
  وأزرار الإجراءات تلتف على الشاشات الصغيرة، وشارة «جديد» لا تغطي زر الحذف، وfa-check-double (غير موجود في FA 4.7) ← fa-check.
  الملف الشخصي: تكرار تعليق في view.php، الحد الأقصى للصورة في edit.php من config (كان «2 ميغابايت» ثابتاً والحد 5)، وfetch بـ json_js،
  وhelp_text داخل e() في change_password.php. البطاقة: سنة الصلاحية تتبع السنة الجامعية (تنتهي 30/09، والتسجيل يبدأ في سبتمبر)،
  وحذف qr_data غير المستعمل، وأزرار الطباعة تحت البطاقة بدل جانبها (flex-direction: column)، وارتفاع سطر ثابت 1.15 حتى لا تُقص
  الصفوف الأخيرة والباركود حين لا يتوفر خط Arial، ومسافة بين التسمية والقيمة، وprint-color-adjust: exact (تُطبع بألوانها).
  وأيضاً: روابط إجراءات الإشعارات في JavaScript من url() (كانت بلا بادئة اللغة فتصل رسائل الخادم بالعربية في الصفحة الفرنسية)،
  و.empty-state > i (كان سهم زر «العودة» يكبر إلى 80px)، وتنظيف أسطر قائمة الطالب في navbar.php (مسافة ناقصة قبل target
  وتعليق «← جديد!» وإزاحات)، واستعلام مكرر عن .notif-empty في renderList بـ notifications_js.php.
  وفي الفصل 36 (لوحة الموظفين): staff.php — docblock الملف صار جملة عربية بلا أقواس، وdocblock يتيم أُصلح، وcsv_safe نُقلت من أول
  الملف إلى ما قبل التصدير مباشرة (الأسطر 246-256؛ تستعملها recruitment_admin.php أيضاً)، واسم المراجِع/المصدِّر في Excel بـ display_name().
  dashboard.php: minmax(170px) بدل 220 (البطاقات الخمس في صف واحد؛ كانت 4+1)، والتحية بـ display_name()، و`<br><br>` مرتب،
  و`const RTL = json_js(is_rtl())` مع `rtl: RTL` في الرسوم الأربعة (كان rtl: true ثابتاً: مفتاح الألوان معكوس بالفرنسية)،
  وتعليقا JavaScript بلا كود PHP، وتعليق tension بلا «0 = …» (كان ينقلب). applications_list.php: تعليقات «جديد/السابق/الحل» القديمة،
  و.empty-state > i، وإزاحة كتلة h3، وتعليقات بلا استدعاءات PHP. application_detail.php: تعليقات (المتحكم، بلا action، linebreaks())،
  والمراجِع بـ display_name()، وdir="auto" على تفاصيل الطلب وملاحظات الإدارة (نص الطالب العربي في الصفحة الفرنسية)،
  وزر إغلاق النافذة المنبثقة `.modal-header .close { float: left; float: inline-end; }` (كان ملاصقاً للعنوان في العربية).
  الكتاب: الفصل 36 يبدأ بدخول الموظفين (admin.php 1 + 126-187، views/admin/login.php، admin.css 102-121 بالعلامة new)،
  ثم staff_dashboard مؤقتة فيها require_staff (لتجربة الدخول بـ ?next=). shots.mjs: دخول الموظفين من /admin/login/،
  ولقطة جديدة 34-admin-login، ومتغير DEMO_DB لخادم overlay.
  وفي الفصل 38: admin_recover — في دالة clean السطر 278 صار `$f->cleaned['username'] ?? ''` (اسم مرفوض في الخطوة 2
  كان يعطي خطأ 500: add_error تحذف cleaned['username'] ثم تقرأها password_errors). أُضيف فحص رجوع في dev/security_check.py.
- **الفصل 38 (مكتوب):** admin.php يُعرض من stages/adminnews.src **بأرقام ملف القارئ** بعد كل إضافة (لا أرقام الملف النهائي)،
  مع ملاحظة في الفصل: open(1، لا يُعرض؛ عرضه الفصل 36)، header 2-6، guard 7-27 (الحارس + admin_render)، menu-temp 28-41
  (admin_menu فارغة + admin_index مؤقتة)، auth 42-103 (الدخول/الخروج من 36، لا يُعرض)، password 104-131، recover 132-244
  (يُعرض على 4 قطع بالمدى النسبي 1-33/34-46/47-86/87-113)، و index-view (app/views/admin/index.php مؤقت). الفصل 39 يُلحق
  الأخبار بعد recover بالترقيم نفسه؛ والفصل 42 يعرض الملف النهائي بأرقامه (لا يعيد 1-6 و41-61 و126-328).
  build.mjs: عرض جزء من كتلة منسوخة (`@stage name block step a-b`) يُحسب في التغطية لأسطر ذلك الجزء فقط (كان يُحسب المدى كله
  في كل مرة)، وعناوين الصناديق (::: why ...) تمر بـ isoHtml مثل العناوين (كان «require_staff()» يظهر «()require_staff»).
  reader-sim 38-41: admin.php والقوالب المؤقتة من `node tools/stages.mjs adminnews N.99`، وviews/admin = login, layout,
  password, recover + المؤقتة. اللقطات: 38-index-temp و38-password (overlay adminnews 38، بعرض 1100، كلمة المرور بأخطائها)،
  و38-recover (الموقع الكامل). اختبار المحاكاة /tmp/t38.py (الحارس، 403، كلمة المرور والجلسات، الاستعادة بكل حالاتها، set_password).
- **الفصل 39 (مكتوب):** menu-news (39.1) يستبدل menu-temp (admin.php 28-46)، ثم الأخبار المؤقتة بعد recover: news-list 250-268،
  news-form 269-364 (أُضيف: طول العنوان ≤ 300، وشكل الرابط `^[\p{L}\p{N}_-]{1,300}$` مع strtolower كما في المحرك العام)،
  news-delete 365-383، والقالبان المؤقتان news_list.php و news_form.php، و delete.php النهائي (@file). المحتوى يُحفظ كما كُتب
  (نص عادي؛ rich_text تنظّف عند العرض) — **الفصل 41 يجب أن**: يجعل محتوى نموذج الأخبار المؤقت RichText::normalize() عند الحفظ،
  و textarea المحتوى data-richtext مع RichText::forEditor()، كما وعد الفصل 39. لقطات 39-news-list/form/errors (بعرض 1280)
  بدل 36-news-* (حُذفت). reader-sim 39-41 يُبقي delete.php. اختبار /tmp/t39.py (القائمة، الأخطاء، الصورة، التعديل، الحذف، 403).
  الفصل 37: الأسطر 50-51 من admin.css (المعاينة) صارت «للفصل 39».
- **الفصل 40 (مكتوب، العنوان «الترجمة الآلية إلى الفرنسية والإنجليزية»):** config.php 79-97 (insert)، Translator.php كاملاً بترتيبه
  (1-50، 51-86، 87-194 الجماعية، 195-269 Gemini، 270-349 MyMemory، 350-410 test+http)، ملف مؤقت tr_test.php، translate_content.php،
  ثم في admin.php (مرحلة adminnews): resources-temp 40.2 (admin_resources() مؤقتة: قسم news بحقوله المترجمة فقط، content من النوع
  textarea — **الفصل 41 يغيّره إلى richtext** مع المحرر)، translation 40.2 (النهائي 1037-1117 = أسطر المرحلة 418-498)، translate-ajax 40.2
  (1162-1184 = 499-521)، translate-all 40.3 (1201-1231 = 522-552)، news-form-tr (يستبدل news-form: جمع الترجمات null للفارغ + سطر
  admin_auto_translate)، القوالب: news-form-view-tr (شريط الترجمة + include لكل حقل)، القالب الجزئي المؤقت _news_tr.php،
  news-list-view-tr (الشارات)، index-view-tr (زر «ترجمة المحتوى» للمدير العام)، ثم admin.css 84-100 و admin.js 83-150 (insert)
  و translate_all.php النهائي. **الفصل 42 يجب أن**: يستبدل resources-temp بـ admin_resources النهائية (8-15) ويحذف news_list.php
  و news_form.php و _news_tr.php، ويعرض admin_auto_translate_inline (1118-1160)؛ و admin_editor_upload (1185-1200) في الفصل 41.
  اللقطات 40-news-translate / 40-news-badges / 40-translate-all: خادم dev/mock_translate.py مع MOCK_WORDS من
  book/tools/mock-words.php (ترجمات الموقع التجريبي الحقيقية)، ومتغيرات TRANSLATE_PROVIDER=gemini GEMINI_API_KEY=dev-key
  GEMINI_ENDPOINT=http://127.0.0.1:8999/v1beta MYMEMORY_ENDPOINT=http://127.0.0.1:8999/get لأمر shots، وخبر مؤقت بلا ترجمة
  (slug = book-untranslated) يُضاف إلى dou_setif2 قبل اللقطتين الأخيرتين ويُحذف بعدهما. reader-sim يُبقي translate_all.php من 40.
  اختبار /tmp/t40.py (يحتاج الخادم الوهمي وخادم المحاكاة بمتغيرات الترجمة). build.mjs لم يتغير.
- **الفصل 41 (مكتوب، العنوان «محرر النصوص المنسّقة في لوحة الإدارة»):** نسخ assets/vendor/tinymce-8.10.1 و assets/js/tinymce-lang
  من النسخة المرجعية (خارج التغطية في build.mjs)، config.php 98-104 (insert)، ثم مرحلة adminnews بثلاث خطوات حتى تتطابق أرقام
  الأسطر في نهاية الفصل: **41.1** resources-rt (يستبدل resources-temp: content نوعه richtext و rows 14) و news-form-rt (يستبدل
  news-form-tr: content بـ RichText::normalize، والترجمات المنسّقة normalize — مثل جمع النموذج النهائي 803-805)؛ **41.2**
  editor-upload (النهائي 1185-1200 = أسطر المرحلة 525-540، بعد translate-ajax) و tinymce (1372-1515 = 572-715، آخر admin.php
  المؤقت)؛ **41.3** news-form-view-rt (تنبيه «غير موجود» من form.php 20-24، textarea المحتوى admin-richtext + forEditor، سطر
  «محرر النصوص» من form.php 81-86، كتلة extra_js من form.php 184-191) و news-tr-partial-rt (فرع richtext = form.php 67).
  ثم admin-richtext.js كاملاً على 6 قطع، و admin.css 178-190 (insert، آخر الملف عند القارئ). reader-sim يحذف قبل 41:
  assets/vendor و tinymce-lang و admin-richtext.js. لقطات 41-editor و 41-editor-image و 41-news-public مقصوصة (clip جديد في
  shots.mjs) وتحتاج الخبر المؤقت 90: `DB_NAME=dou_setif2 php tools/shots-rich.php add` ثم `del`. book.css: أول 3 أسطر من صندوق
  الكود مع رأسه وآخر 3 معاً (لا سطر يتيم). اختبارات: /tmp/t41.py (الخادم: المحرر في النموذج، التنظيف، الترجمة المنسّقة، المحرر
  الفارغ = مطلوب، forEditor، الرفع وأخطاؤه و 403)، /tmp/ch41/richtext_flow41.mjs (متصفح حقيقي على المحاكاة: 36 فحصاً)،
  /tmp/ch41/ui41.mjs (نافذة كود المصدر تحذف onclick، لسان «تحميل» يعود إلى «عام» بالرابط، فيديو YouTube)، و dev/tinymce_check.php.
  **الفصل 48 يجب أن**: ينبّه إلى أن صور المحرر تُحفظ بروابط فيها مجلد الموقع (`/dou-setif/uploads/editor/...`، وروابط داخلية قد
  تبدأ بـ `http://localhost/dou-setif/`): قبل استيراد ملف SQL في الاستضافة (الموقع في جذر النطاق) استبدال `/dou-setif/` بـ `/`
  في الملف المصدَّر (بحث واستبدال في محرر نصوص)، كما وعد الفصل 41. **الملحق أ يجب أن**: يضيف المجلدين tinymce-8.10.1 و
  tinymce-lang إلى «ما لا تكتبه بيدك». **المقدمة والملحقان أ و ب**: اسم النسخة المرجعية dou-setif-php-v2.zip لا dou-setif-php.zip.
- **المسابقات 43–48 (قرار 2026-11-05):** ستة فصول بدل خمسة؛ الأمان والنشر صار 49 (`49-security-deploy.md`) وكل الإشارات إلى
  «الفصل 47/48» في 14، 15، 17، 20، 27، 33، 36، 37، 41، 42، ب نُقلت. `tools/lang_regroup.py`: OVERRIDES جديدة (competitions.php 1-97 ← 45،
  98-475 ← 46، 476+ ← 47؛ list/detail ← 45، apply/receipt ← 46، track/_print_*/print_* ← 47؛ PdfGuard ← 46) وعنوان قسم جديد 47؛
  طُبّق (`--apply`): fr.php و en.php صارا 991 سطراً (الأقسام 18–44 لم تتغيّر حرفاً؛ 45 = 775-826، 46 = 827-909، 47 = 910-990)،
  و i18n_keys: 946 نصاً بلا نقص. reader-sim: admin42 حتى 47 بخطوة N (الكتلة config-recruit 44 في stages/admin42.src)،
  competitions.php بأجزاء، القوالب و competitions.css بالأقسام، competitions.js (46)، competitions-print.css (47)، recruitment_admin (48).
- **الفصل 43 (مكتوب):** خريطة الفصول الستة، رحلة المترشح، جدول المخاطر (← الفصل)، أين تُحفظ كل معلومة، الجداول الثمانية
  (5 صناديق insert)، config 105-121 insert، أفكار التشفير الست، Crypto في 5 صناديق، crypto_test.php بدالة `show()` (عناوين عربية
  بلا أرقام في السلاسل: الأرقام بجانب العربية داخل سلسلة تنقلب في صندوق الكود)، المفتاح الضائع، المفتاح غير الصالح. اختبار:
  /tmp/q43/crypto_test.php و file_test.php على المحاكاة 43 بسطر الأوامر (النتائج في الفصل من التشغيل الفعلي)، و 403 لملف المفتاح.
  **إصلاح في php/ (يدخل الـ ZIP عند إعادة بنائه):** assets/css/competitions.css السطر 167 — قاعدة `.cmp-card` لبطاقات فضاء المترشح
  (أُضيفت في f5ce1a5) كانت تلغي بطاقة قائمة المسابقات (الشريط الملوّن 5px من جهة البداية صار 1px)، وتترك بطاقات صفحة المتابعة
  بحشوة 18px/20px فلا يمتد رأسها إلى الحواف. صارت `.cmp-result, .cmp-convocation, .cmp-complete { padding: 0; border: 1px solid #dfe5ea; ... }`
  (تحقق بلقطتين: القائمة borderRightWidth = 5px، ورؤوس بطاقات المتابعة ممتدة).
- **الفصل 44 (مكتوب):** models/recruitment.php في 10 صناديق، lang 44، admin_config 477-504/505-522/523-566 (insert)، تجربة
  إضافة مسابقة (الرتب، الوثائق، الموعد، ملف)، المراحل بتغيير التواريخ، الصلاحية بموظف الفصل 42. لقطات 44-list (clip
  #admin-list-form، عرض 1600) و 44-posts و 44-exams (clip للجداول المرتبطة، المسابقة 1) من overlay admin42 خطوة 44.
  اختبار /tmp/q44/t44.py على المحاكاة 44: 27 فحصاً (الإضافة بجداولها، slug، تنظيف RichText، reference_number،
  conditions_list، المسودة لا تقبل الترشح، الإجراء الجماعي، before_delete للمسابقة 1، الصلاحية 404/200، الحذف CASCADE).
  **إصلاح في php/:** admin_config.php السطر 517 — اسم الحقل `online_apply` كان «قبول الترشح الإلكتروني (استمارة + رفع الوثائق PDF)»
  فصار عنوان عمود وفلتر طويلاً يضغط عمود العنوان ويُخفي عمودَي الطلبات والنتائج عند عرض 1280–1366؛ صار
  `$bool('الترشح الإلكتروني') + ['help' => 'استمارة على الموقع ورفع الوثائق PDF. ألغِ التحديد إن كان الترشح بإيداع الملف الورقي وحده']`،
  و README.md السطر 213 «الترشح الإلكتروني».
- **الفصل 42 (أُعيدت كتابته على النسخة 2):** admin_config.php كاملاً إلا 477-566 (صندوق بمدى متعدد 459-476,567-613 فيه شريط
  «نضيفها في فصل لاحق»)، و admin.php: 7-39 replace (مكان resources-rt)، 62-73,85-102,112-124 replace (مكان menu-news: بلا قائمة
  المسابقات 74-84 ولا تنبيه الترقية 103-111 — الفصل 47)، ثم 329-1036 (مكان دوال الأخبار المؤقتة)، و 1118-1161 بعد الترجمة،
  و 1232-1371 بعد translate_all، و 1516-1638 في آخر الملف؛ بلا @moved (الأجزاء المكتوبة في 36-41 تُذكر نصاً). القوالب: index.php
  (`- replace`)، list.php، form.php (4 قطع)، admin.css 132-136 insert، وحذف news_list.php و news_form.php و _news_tr.php و admin-demo/.
  **stages/admin42.src** يبني admin.php و admin_config.php بلا المسابقات: reader-sim 42-46 يستعمله (ويحذف views/admin/recruitment)،
  ولقطات 42-index/list/edit/app-edit/applications (overlay admin42، عرض 1280) بدل 37-*.png (حُذفت). admin_smoke على المحاكاة 42:
  76 صفحة تعمل. **التغطية الكاملة `--coverage` تعمل الآن بلا حيلة نقل الفصل 42**: 107/144 (الباقي: المسابقات 43-47 والملحق ج،
  ومكرر base.php 16-22/418-424 و footer.php 55-56 — يُراجع).
  **الفصل 47 يجب أن**: يدرج admin.php 74-84 و 103-111، و admin_config.php 477-566، وقوالب views/admin/recruitment، و admin.css 138-176.

## قائمة العمل
- [x] الأدوات على النسخة 2 + نقل الإشارات (remap_v2.py)
- [ ] 135 إشارة نصية (checkrefs) — الأرقام في النص
- [ ] 322 صندوق كود تغيّر محتواه: تحديث الشرح (t()، lang_url، rich_text، القائمة، العرض...) فصلاً فصلاً
- [ ] بقايا في ملفات قائمة (admin.css 108، helpers 31، navbar 26، students 17، form.php 13 سطراً...) ← نص أو فصل جديد
- [ ] الفصول الجديدة (40 ملفاً جديداً، 9 056 سطراً) ثم إعادة الترقيم
- [ ] المراحل (stages/*.src) تعمل في كل خطوة بالنسخة 2 (stage-db.sh وخادم PHP)
- [ ] لقطات الشاشة بالنسخة 2 (المبدّل، الإصلاحات، المسابقات، المحرر)
- [ ] الملحق أ من التغطية، بناء كامل، تسليم

---

# خطة الكتاب — الإصدار الثاني (إعادة كتابة كاملة) — مكتمل، سُلِّم 2026-10-29

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
