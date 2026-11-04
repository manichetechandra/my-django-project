# TinyMCE — محرر النصوص المنسّقة في لوحة الإدارة

المشروع يحتوي نسخة جاهزة من **TinyMCE 8.10.1** (من حزمة npm الرسمية [`tinymce`](https://www.npmjs.com/package/tinymce)،
الملفات المصغّرة اللازمة فقط، بلا تعديل) مع ملف العربية `langs/ar.js` (حزمة اللغات الرسمية عبر [`tinymce-i18n`](https://www.npmjs.com/package/tinymce-i18n)).
لا يحتاج إنترنت ولا CDN.

## استعمال نسختك من TinyMCE بدل هذه

انسخ **محتوى المجلد الذي فيه `tinymce.min.js`** إلى هنا (استبدل الموجود)، حتى يصير عندك:

```
dou-setif/assets/vendor/tinymce/tinymce.min.js
dou-setif/assets/vendor/tinymce/plugins/...
dou-setif/assets/vendor/tinymce/skins/...
dou-setif/assets/vendor/tinymce/themes/...
dou-setif/assets/vendor/tinymce/icons/...
dou-setif/assets/vendor/tinymce/models/...      (TinyMCE 6 وما بعده)
dou-setif/assets/vendor/tinymce/langs/ar.js     (ملف العربية: إن لم يكن عندك تبقى الواجهة بالإنجليزية)
```

- في ملف التحميل الرسمي من tiny.cloud هذا المجلد هو `tinymce/js/tinymce/`. ولو نسخت المجلد كما هو
  (`assets/vendor/tinymce/js/tinymce/tinymce.min.js`) يجده الموقع أيضاً.
- تعمل الإصدارات **5 و 6 و 7 و 8**. الإضافات المستعملة (موجودة في كل نسخة): `lists` `link` `autolink` `table` `code` (و `hr` في الإصدار 5).
- ملف العربية يُكتشف تلقائياً إن كان اسمه `langs/ar.js` أو `langs/ar_SA.js` أو `langs/ar-SA.js`.
- إن لم يجد الموقع TinyMCE تظهر رسالة في نموذج لوحة الإدارة، وتبقى الحقول خانات نص (HTML) تعمل.

## الترخيص

TinyMCE 7 و 8: **GPL-2.0-or-later** (انظر `license.md`) — مجاني لمشروع مفتوح المصدر كهذا، لذلك يعمل بالمفتاح
`license_key: 'gpl'` (في `assets/js/admin-richtext.js`؛ بدونه يعطّل TinyMCE 8 المحرر). لمشروع مغلق المصدر يلزم ترخيص تجاري
من Tiny: https://www.tiny.cloud/pricing/ — الإصداران 6 (MIT) و 5 (LGPL) لا يحتاجان المفتاح.

| الملف | SHA-256 |
|---|---|
| `tinymce.min.js` (8.10.1) | `a76eae0c9747774f3a026488b18a0540bba25dfaafa8f614540050fa0aa50320` |
| `langs/ar.js` | `2050c4e6be6ff055bea8bb32ebd30bcea73f21b205622c9ebe23ace4f7199435` |

الأمان لا يعتمد على المحرر: كل HTML يُنظَّف في الخادم بقائمة سماح صارمة (`app/core/RichText.php`) عند الحفظ وعند العرض.
