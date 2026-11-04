# ملفات العربية لمحرر TinyMCE (لكل إصدار)

يستعملها `admin-richtext.js` حين لا يكون في نسخة TinyMCE المستعملة ملف العربية (`langs/ar.js`)، حسب إصدارها المحمَّل:

| الملف | لـ TinyMCE | المصدر في حزمة [`tinymce-i18n`](https://www.npmjs.com/package/tinymce-i18n) 26.11.2 |
|---|---|---|
| `ar-4.js` | 4.x | `langs/ar.js` |
| `ar-5.js` | 5.x | `langs5/ar.js` |
| `ar-6.js` | 6.x | `langs6/ar.js` |
| `ar-7.js` | 7.x | `langs7/ar.js` |
| `ar-8.js` | 8.x (وما بعده) | `langs8/ar.js` (هو نفسه `assets/vendor/tinymce-8.10.1/langs/ar.js`) |

حزم اللغات من توزيع TinyMCE الرسمي (https://www.tiny.cloud/get-tiny/language-packages/)، مساهمات المجتمع، وترخيصها ترخيص إصدار
TinyMCE الموافق (LGPL للإصدارين 4 و 5، MIT للإصدار 6، GPL-2.0-or-later للإصدارين 7 و 8). موضوعة خارج `assets/vendor/` حتى تبقى
إن استبدلت مجلد TinyMCE بنسختك.
