// محرر النصوص المنسّقة في لوحة الإدارة (CKEditor 5) في متصفح حقيقي:
//   خبر جديد ← كتابة وتنسيق (عنوان، غامق، قائمة، رابط) بلوحة المفاتيح وشريط الأدوات ← «ترجمة تلقائية» تملأ محرري FR و EN
//   ← حفظ ← الصفحة العامة بالعربية والفرنسية تعرض التنسيق ← حذف الخبر (تبقى البيانات التجريبية كما هي)
//   node dev/richtext_flow.mjs [--base=http://127.0.0.1:8002] [--out=/tmp/shots/rich] [--admin=miloudsellam:AdminDemo2026]
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const require = createRequire(path.join(ROOT, 'book', 'tools', 'shots.mjs'));
const puppeteer = require('puppeteer-core');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const BASE = (args.base || 'http://127.0.0.1:8002').replace(/\/$/, '');
const OUT = args.out || '/tmp/shots/rich';
const [ADMIN, PASS] = (args.admin || 'miloudsellam:AdminDemo2026').split(':');
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
fs.mkdirSync(OUT, { recursive: true });

function cdnFile(url) {
  const u = url.split('?')[0];
  let m;
  if ((m = u.match(/^https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@3\.4\.1\/(.+)$/))) return path.join(CDN, 'bootstrap@3.4.1', m[1]);
  if (/^https:\/\/code\.jquery\.com\/jquery-3\.6\.0(\.min)?\.js$/.test(u)) return path.join(CDN, 'jquery@3.6.0', 'dist/jquery.min.js');
  if ((m = u.match(/^https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome\/4\.7\.0\/(.+)$/))) return path.join(CDN, 'font-awesome@4.7.0', m[1]);
  return null;
}
const MIME = { '.css': 'text/css', '.js': 'application/javascript', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf' };

const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--lang=ar'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: fs.existsSync('/tmp/devshots-fonts.conf') ? '/tmp/devshots-fonts.conf' : `${BT}/fonts.conf` },
});
const page = await browser.newPage();
await page.setViewport({ width: 1366, height: 900 });
await page.setRequestInterception(true);
const external = [];
page.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(BASE) || u.startsWith('data:') || u.startsWith('blob:')) return req.continue();
  if (u.includes('ckeditor')) external.push(u); // المحرر يجب أن يُحمَّل من الموقع نفسه، لا من CDN
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push('console: ' + m.text()); });
page.on('dialog', (d) => d.accept());
let failures = 0;
const check = (cond, label) => { console.log((cond ? '  ✓ ' : '  ✗ ') + label); if (!cond) failures++; return cond; };
const go = (url) => page.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'networkidle0' });
const submit = (selector) => Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click(selector)]);
async function shot(name, full = true) {
  await page.addStyleTag({ content: '*{animation:none!important;transition:none!important;caret-color:transparent!important}' });
  if (full) {
    const h = await page.evaluate(() => document.documentElement.scrollHeight);
    await page.setViewport({ width: 1366, height: Math.min(h, 5000) });
  }
  await page.screenshot({ path: path.join(OUT, name + '.png') });
  await page.setViewport({ width: 1366, height: 900 });
}
// رابط https://www.mesrs.dz بخصائص target="_blank" و rel="noopener noreferrer" (بأي ترتيب) ونصه
function linkOk(html, text) {
  const m = html.match(/<a ([^>]*href="https:\/\/www\.mesrs\.dz"[^>]*)>([^<]*)<\/a>/);
  return !!m && /target="_blank"/.test(m[1]) && /rel="noopener noreferrer"/.test(m[1]) && (!text || m[2] === text);
}
// بيانات محرر بحسب اسم الحقل (textarea name)
const editorData = (name) => page.evaluate((n) => {
  const ta = document.querySelector(`textarea[name="${n}"]`);
  return ta && ta.ckeditorInstance ? ta.ckeditorInstance.getData() : null;
}, name);

// 1) الدخول ونموذج خبر جديد
await go('/admin/login/');
await page.type('input[name=username]', ADMIN);
await page.type('input[name=password]', PASS);
await submit('form [type=submit]');
await go('/admin/news/add/');
await page.waitForSelector('.ck-editor__editable', { timeout: 15000 });
const editors = await page.$$eval('.ck-editor__editable', (els) => els.map((e) => ({ lang: e.getAttribute('lang'), dir: e.getAttribute('dir') })));
check(editors.length === 3, `3 محررات في نموذج الخبر (المحتوى + FR + EN): ${editors.length}`);
check(editors[0] && editors[0].lang === 'ar' && editors[0].dir === 'rtl', 'محرر المحتوى العربي من اليمين لليسار: ' + JSON.stringify(editors[0]));
check(editors.slice(1).every((e) => e.dir === 'ltr') && editors.map((e) => e.lang).join() === 'ar,fr,en', 'محررا FR و EN من اليسار لليمين: ' + JSON.stringify(editors.slice(1)));
const ui = await page.$eval('.ck-editor', (el) => ({ dir: el.getAttribute('dir'), heading: (el.querySelector('.ck-heading-dropdown .ck-button__label') || {}).textContent }));
check(ui.dir === 'rtl' && /فقرة/.test(ui.heading || ''), 'واجهة المحرر بالعربية: ' + JSON.stringify(ui));
check(await page.$$eval('textarea.admin-richtext', (t) => t.every((x) => getComputedStyle(x).display === 'none')), 'حقول textarea الأصلية مخفية خلف المحرر');
const toolbar = await page.$$eval('.ck-editor:first-of-type .ck-toolbar__items > .ck-button, .ck-editor:first-of-type .ck-toolbar__items > .ck-dropdown', (b) => b.length);
check(toolbar >= 14, `أزرار شريط الأدوات: ${toolbar}`);

// 2) الكتابة والتنسيق كما يفعل الموظف
const title = 'خبر تجريبي للمحرر ' + Date.now().toString().slice(-6);
await page.type('#f_title', title);
await page.click('.ck-editor__editable[lang=ar]');
await page.keyboard.type('صرف المنحة');
// العنوان: قائمة «فقرة» ← «عنوان 1»
await page.click('.ck-editor:first-of-type .ck-heading-dropdown > button');
await page.click('.ck-editor:first-of-type .ck-heading-dropdown .ck-list__item:nth-child(2) button');
await page.keyboard.press('End');
await page.keyboard.press('Enter');
await page.keyboard.type('يعلن مدير الخدمات الجامعية عن ');
await page.keyboard.down('Control'); await page.keyboard.press('KeyB'); await page.keyboard.up('Control');
await page.keyboard.type('صرف المنحة');
await page.keyboard.down('Control'); await page.keyboard.press('KeyB'); await page.keyboard.up('Control');
await page.keyboard.type(' للطلبة.');
await page.keyboard.press('Enter');
await page.keyboard.type('* '); // Autoformat: «* » = قائمة نقطية
await page.keyboard.type('الإقامة دباغين');
await page.keyboard.press('Enter');
await page.keyboard.type('الإقامة الباز');
await page.keyboard.press('Enter');
await page.keyboard.press('Enter'); // سطر فارغ في القائمة = الخروج منها
await page.keyboard.type('التفاصيل في ');
// رابط: Ctrl+K ثم العنوان
await page.keyboard.type('موقع الوزارة');
// تحديد «موقع الوزارة» (عبر واجهة CKEditor البرمجية: تحديد النص العربي بالأسهم غير ثابت التوقيت) ثم Ctrl+K كما يفعل الموظف
await new Promise((r) => setTimeout(r, 300));
await page.evaluate((text) => {
  const ed = document.querySelector('textarea[name=content]').ckeditorInstance;
  ed.model.change((writer) => {
    const root = ed.model.document.getRoot();
    const para = root.getChild(root.childCount - 1);
    const full = [...para.getChildren()].map((n) => n.data || '').join('');
    const start = full.lastIndexOf(text);
    writer.setSelection(writer.createRange(writer.createPositionAt(para, start), writer.createPositionAt(para, start + text.length)));
  });
  ed.editing.view.focus();
}, 'موقع الوزارة');
await page.keyboard.down('Control'); await page.keyboard.press('KeyK'); await page.keyboard.up('Control');
await page.waitForSelector('.ck-link-form input, .ck-balloon-panel input', { timeout: 5000 });
await page.keyboard.type('www.mesrs.dz');
await page.keyboard.press('Enter');
await new Promise((r) => setTimeout(r, 400));
let ar = await editorData('content');
check(/<h2>صرف المنحة<\/h2>/.test(ar), 'عنوان h2: ' + ar.slice(0, 60));
check(/<strong>صرف المنحة<\/strong>/.test(ar), 'نص غامق (Ctrl+B)');
check(/<ul><li[^>]*>الإقامة دباغين<\/li><li[^>]*>الإقامة الباز<\/li><\/ul>/.test(ar), 'قائمة نقطية («* »): ' + (ar.match(/<ul>.*?<\/ul>/) || [ar])[0]);
check(linkOk(ar, 'موقع الوزارة'), 'رابط خارجي https:// مع target و rel: ' + (ar.match(/<a [^>]+>.*?<\/a>/) || [ar.slice(-160)])[0]);
check(await page.$eval('textarea[name=content]', (t) => t.value) === ar, 'textarea الأصلي محدَّث مع المحرر');

// 3) الترجمة التلقائية تملأ محرري FR و EN مع بقاء التنسيق
await page.click('[data-auto-translate]');
await page.waitForFunction(() => /✓|✗/.test(document.querySelector('[data-tr-status]').textContent), { timeout: 20000 });
const status = await page.$eval('[data-tr-status]', (e) => e.textContent);
check(status.includes('✓'), 'زر «ترجمة تلقائية»: ' + status);
const fr = await editorData('content_fr');
const en = await editorData('content_en');
check(/<h2>Versement de la bourse<\/h2>/.test(fr) && /<ul><li/.test(fr) && /<strong>/.test(fr) && linkOk(fr), 'محرر FR: نفس الهيكل مترجماً: ' + fr);
check(/<h2>Scholarship payment<\/h2>/.test(en) && /<ul><li/.test(en) && linkOk(en), 'محرر EN: ' + en.slice(0, 70));
check(await page.$eval('textarea[name=content_fr]', (t) => t.ckeditorInstance.ui.element.classList.contains('admin-tr-new')), 'محرر الترجمة الجديدة ملوّن (admin-tr-new)');
check(await page.$eval('#f_title_fr', (i) => i.value) !== '', 'وعنوان الخبر بالفرنسية أيضاً');
// تعديل النص العربي بعد الترجمة ← تنبيه «تغيّر النص العربي» على محرري الترجمة
await page.click('.ck-editor__editable[lang=ar]');
await page.keyboard.press('End');
await page.keyboard.type(' .');
await new Promise((r) => setTimeout(r, 300));
check(await page.$eval('textarea[name=content_fr]', (t) => t.ckeditorInstance.ui.element.classList.contains('admin-tr-stale')), 'تعديل العربي بعد الترجمة يلوّن محرر FR (admin-tr-stale)');
await page.evaluate(() => window.scrollTo(0, 0));
await shot('admin-news-editor', false);
await page.evaluate(() => document.querySelector('.admin-translations').scrollIntoView());
await shot('admin-news-editor-translations', false);

// 4) الحفظ
await submit('button[name=_continue]');
const editUrl = page.url();
check(/\/admin\/news\/\d+\/$/.test(editUrl), 'حُفظ الخبر ← صفحة تعديله: ' + editUrl.replace(BASE, ''));
await page.waitForSelector('.ck-editor__editable', { timeout: 15000 });
ar = await editorData('content');
check(/<h2>صرف المنحة<\/h2>/.test(ar) && /<ul>/.test(ar), 'المحتوى المحفوظ يعود إلى المحرر كما هو');
const slug = await page.$eval('#f_slug', (i) => i.value);
await go('/admin/news/');
const cell = await page.evaluate((t) => { const row = [...document.querySelectorAll('tr')].find((r) => r.textContent.includes(t)); return row ? row.textContent : ''; }, title);
check(cell !== '' && !/<p>|<h2>|&lt;/.test(cell), 'قائمة الأخبار في لوحة الإدارة: نص بلا وسوم');

// 5) الصفحة العامة بالعربية والفرنسية
for (const [lang, prefix, heading] of [['ar', '', 'صرف المنحة'], ['fr', '/fr', 'Versement de la bourse']]) {
  await go(`${prefix}/news/${encodeURIComponent(slug)}/`);
  const html = await page.$eval('.rich-text', (e) => e.innerHTML).catch(() => '');
  check(html.includes(`<h2>${heading}</h2>`) && /<strong>/.test(html) && /<ul><li>/.test(html), `الصفحة العامة (${lang}): عنوان وغامق وقائمة`);
  check(linkOk(html), `الصفحة العامة (${lang}): الرابط مع target="_blank" و rel="noopener noreferrer"`);
  check(!/data-list-item-id|data-|style=|class="(?!text-)/.test(html), `الصفحة العامة (${lang}): لا خصائص زائدة من المحرر (data-list-item-id ...) — نُظّفت عند الحفظ`);
  const style = await page.$eval('.rich-text ul', (u) => getComputedStyle(u).listStyleType).catch(() => '');
  check(style === 'disc', `القائمة تظهر بنقاط (${lang}): ${style}`);
  await page.evaluate(() => document.querySelector('.rich-text').scrollIntoView({ block: 'center' }));
  await shot('public-news-' + lang, false);
}

// 6) حذف الخبر التجريبي
await go(editUrl.replace(/\/$/, '/delete/'));
await submit('form [type=submit].btn-danger, form button[type=submit]');
const gone = await page.goto(`${BASE}/news/${encodeURIComponent(slug)}/`, { waitUntil: 'networkidle0' });
check(gone.status() === 404, 'حُذف الخبر التجريبي (404)');

check(external.length === 0, 'المحرر يُحمَّل من الموقع نفسه (لا CDN): ' + (external[0] || ''));
check(errors.length === 0, 'لا أخطاء JavaScript' + (errors.length ? ': ' + errors.slice(0, 3).join(' | ') : ''));
await browser.close();
console.log(failures ? `✗ ${failures} فحص فاشل` : '✓ محرر النصوص المنسّقة يعمل');
process.exit(failures ? 1 : 0);
