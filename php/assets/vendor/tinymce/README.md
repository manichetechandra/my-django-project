# TinyMCE — محرر النصوص المنسّقة في لوحة الإدارة

المشروع يحتوي نسخة جاهزة من **TinyMCE 8.10.1** (من حزمة npm الرسمية [`tinymce`](https://www.npmjs.com/package/tinymce)،
الملفات المصغّرة اللازمة فقط، بلا تعديل) مع ملف العربية `langs/ar.js` (حزمة اللغات الرسمية عبر [`tinymce-i18n`](https://www.npmjs.com/package/tinymce-i18n)).
لا يحتاج إنترنت ولا CDN.

## استعمال نسختك من TinyMCE بدل هذه

**لا تحذف شيئاً ولا تستبدل شيئاً:** ضع مجلد TinyMCE الخاص بك **كما هو** في أحد هذه الأماكن، والموقع يستعمله تلقائياً
بدل هذه النسخة (نسختك لها الأولوية دائماً):

| المكان (في XAMPP: `C:\xampp\htdocs\dou-setif\...`) | مثال |
|---|---|
| **في مجلد الموقع مباشرة (الأسهل)** | `dou-setif/tinymce/js/tinymce/tinymce.min.js` (مجلد التحميل الرسمي كما فككته) |
| داخل هذا المجلد | `dou-setif/assets/vendor/tinymce/tinymce_7.9.3/tinymce/js/tinymce/tinymce.min.js` |
| `assets/` أو `assets/js/` أو `js/` أو `lib/` أو `libs/` | `dou-setif/assets/js/tinymce/tinymce.min.js` |
| Composer أو npm | `dou-setif/vendor/tinymce/tinymce/` أو `dou-setif/node_modules/tinymce/` |
| بجانب مجلد الموقع | `C:\xampp\htdocs\tinymce\js\tinymce\tinymce.min.js` |
| أي مكان آخر داخل مجلد الموقع | اكتب مساره في `config.php`: `'editor' => ['tinymce' => 'editors/tinymce'],` |
| مكان ملفات هذه النسخة | استبدل محتوى هذا المجلد بمحتوى المجلد الذي فيه `tinymce.min.js` |

- يكفي أن **يبدأ اسم المجلد بـ `tinymce`** (مثل `tinymce` أو `tinymce_7.9.3` أو `TinyMCE`)، وأن يكون `tinymce.min.js`
  على عمق 4 مجلدات على الأكثر داخله. في ملف التحميل الرسمي من tiny.cloud هو `tinymce/js/tinymce/tinymce.min.js`.
- **كيف تتأكد؟** أسفل كل نموذج فيه محرر في لوحة الإدارة (مثلاً «إضافة خبر») سطر مثل:
  «محرر النصوص: TinyMCE 7.9.3 — نسختك: `tinymce/js/tinymce/tinymce.min.js`».
  إن كتب «النسخة المرفقة بالمشروع» فالموقع لم يجد نسختك: تأكد أن الملف `tinymce.min.js` موجود فعلاً في أحد الأماكن أعلاه.
  بعد تغيير النسخة حدّث الصفحة بـ **Ctrl+F5**.
- تعمل الإصدارات **4 و 5 و 6 و 7 و 8**. الإضافات المستعملة (موجودة في كل نسخة): `lists` `link` `autolink` `table` `code`
  (و `hr` في الإصدارين 4 و 5).
- **ملف العربية:** إن كان في نسختك `langs/ar.js` (أو `ar_SA.js` أو `ar-SA.js`) يُستعمل، وإلا يستعير الموقع ملف العربية
  من هذا المجلد فتبقى الواجهة بالعربية (يظهر ذلك في السطر نفسه).
- نسخة مطابقة لهذه النسخة (نسخة احتياطية `tinymce.bak` مثلاً) لا تتقدّم أبداً على نسختك.
- لا يُبحث في `uploads/` ولا `storage/` ولا `app/` ولا `database/`.
- إن لم يجد الموقع أي TinyMCE تظهر رسالة في نموذج لوحة الإدارة، وتبقى الحقول خانات نص (HTML) تعمل.

## الترخيص

TinyMCE 7 و 8: **GPL-2.0-or-later** (انظر `license.md`) — مجاني لمشروع مفتوح المصدر كهذا، لذلك يعمل بالمفتاح
`license_key: 'gpl'` (في `assets/js/admin-richtext.js`؛ بدونه يعطّل TinyMCE 8 المحرر). لمشروع مغلق المصدر يلزم ترخيص تجاري
من Tiny: https://www.tiny.cloud/pricing/ — الإصداران 6 (MIT) و 5 (LGPL) لا يحتاجان المفتاح.

| الملف | SHA-256 |
|---|---|
| `tinymce.min.js` (8.10.1) | `a76eae0c9747774f3a026488b18a0540bba25dfaafa8f614540050fa0aa50320` |
| `langs/ar.js` | `2050c4e6be6ff055bea8bb32ebd30bcea73f21b205622c9ebe23ace4f7199435` |

الأمان لا يعتمد على المحرر: كل HTML يُنظَّف في الخادم بقائمة سماح صارمة (`app/core/RichText.php`) عند الحفظ وعند العرض.
