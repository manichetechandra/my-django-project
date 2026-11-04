# CKEditor 5 — نسخة محلية لمحرر النصوص المنسّقة في لوحة الإدارة

- الإصدار: **49.0.1** من حزمة npm الرسمية [`ckeditor5`](https://www.npmjs.com/package/ckeditor5) (الملفات كما هي بلا أي تعديل).
- الترخيص: **GPL-2.0-or-later** (انظر `LICENSE.md` و `COPYING.GPL`) — مجاني للمشاريع مفتوحة المصدر،
  لذلك يعمل المحرر بالمفتاح `licenseKey: 'GPL'` (في `assets/js/admin-richtext.js`). لاستعماله في مشروع مغلق المصدر
  يلزم ترخيص تجاري من CKSource: https://ckeditor.com/legal/ckeditor-licensing-options
- لماذا نسخة محلية وليس CDN؟ لوحة الإدارة أهم جزء في الموقع: لا نحمّل فيها أي سكربت من خادم خارجي،
  والمحرر يعمل حتى بلا إنترنت (XAMPP في شبكة داخلية). والمفتاح `GPL` لا يعمل مع CDN الخاص بـ CKEditor أصلاً.

| الملف | من الحزمة | SHA-256 |
|---|---|---|
| `ckeditor5.umd.js` | `dist/browser/ckeditor5.umd.js` | `d00faf38df0532180459affb6a4b150e822a96294806456357e1d344b787fa87` |
| `ckeditor5.css` | `dist/browser/ckeditor5.css` | `55566635e83314234e4ba106eb5f24157a1b06cfe7b3e51b0d653f804fcf3aa0` |
| `translations/ar.umd.js` | `dist/translations/ar.umd.js` | `5982d18d02b7d9d44a6f3891aa218b2e7fd8485495f1f60ce600124892f3bb33` |

## التحديث إلى إصدار أحدث

```bash
npm pack ckeditor5@الإصدار && tar xzf ckeditor5-*.tgz
cp package/dist/browser/ckeditor5.umd.js package/dist/browser/ckeditor5.css  php/assets/vendor/ckeditor5/
cp package/dist/translations/ar.umd.js php/assets/vendor/ckeditor5/translations/
```

ثم جرّب نموذج «خبر» في لوحة الإدارة. المحرر لا يعتمد عليه الأمان: كل HTML يُنظَّف في الخادم
بقائمة سماح صارمة (`app/core/RichText.php`) عند الحفظ وعند العرض.
