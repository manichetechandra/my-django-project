// محرر النصوص المنسّقة في لوحة الإدارة (TinyMCE) في متصفح حقيقي:
//   خبر جديد ← كتابة وتنسيق (عنوان، غامق، تسطير، قائمة، محاذاة، رابط) بلوحة المفاتيح وشريط الأدوات ونافذة الرابط
//   ← «ترجمة تلقائية» تملأ محرري FR و EN ← حفظ ← الصفحة العامة بالعربية والفرنسية ← حذف الخبر (البيانات التجريبية كما هي)
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
  if (u.startsWith(BASE) || /^(data|blob|about):/.test(u)) return req.continue();
  if (/tinymce|tiny\.cloud/.test(u)) external.push(u); // المحرر يجب أن يُحمَّل من الموقع نفسه، لا من CDN
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
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function shot(name) {
  await page.addStyleTag({ content: '*{animation:none!important;transition:none!important;caret-color:transparent!important}' });
  await page.screenshot({ path: path.join(OUT, name + '.png') });
}
// محرر TinyMCE بحسب textarea (id): محتواه، وزر في شريط أدواته، والكتابة داخله
const content = (id) => page.evaluate((i) => tinymce.get(i).getContent(), id);
const tool = (id, name) => page.click(`#${id} ~ .tox-tinymce button[data-mce-name="${name}"]`);
async function focusEnd(id) {
  await page.evaluate((i) => { const ed = tinymce.get(i); ed.focus(); ed.selection.select(ed.getBody(), true); ed.selection.collapse(false); }, id);
}
// رابط https://www.mesrs.dz ونصه؛ وإن كان فيه target="_blank" فمعه rel="noopener noreferrer"
function linkOk(html, text) {
  const m = html.match(/<a ([^>]*href="https:\/\/www\.mesrs\.dz"[^>]*)>([^<]*)<\/a>/);
  return !!m && (!/target=/.test(m[1]) || /rel="noopener noreferrer"/.test(m[1])) && (!text || m[2] === text);
}

// 1) الدخول ونموذج خبر جديد
await go('/admin/login/');
await page.type('input[name=username]', ADMIN);
await page.type('input[name=password]', PASS);
await submit('form [type=submit]');
await go('/admin/news/add/');
await page.waitForFunction(() => window.tinymce && tinymce.get().length === 3 && tinymce.get().every((e) => e.initialized), { timeout: 20000 });
const info = await page.evaluate(() => ({
  version: tinymce.majorVersion + '.' + tinymce.minorVersion,
  ids: tinymce.get().map((e) => e.id),
  dirs: tinymce.get().map((e) => e.getBody().getAttribute('dir')),
  ui: document.querySelector('.tox-tinymce').getAttribute('dir'),
  bold: (document.querySelector('.tox-tinymce button[data-mce-name="bold"]') || {}).ariaLabel,
  hidden: [...document.querySelectorAll('textarea.admin-richtext')].every((t) => getComputedStyle(t).display === 'none'),
  disabled: tinymce.get().some((e) => e.mode && e.mode.isReadOnly && e.mode.isReadOnly()),
}));
check(info.ids.join() === 'f_content,f_content_fr,f_content_en', `3 محررات TinyMCE ${info.version} (المحتوى + FR + EN): ${info.ids.join()}`);
check(info.dirs.join() === 'rtl,ltr,ltr', 'المحتوى العربي من اليمين لليسار، و FR و EN من اليسار لليمين: ' + info.dirs.join());
check(info.ui === 'rtl' && info.bold === 'غامق', `واجهة المحرر بالعربية (langs/ar.js): زر «${info.bold}»`);
check(info.hidden, 'حقول textarea الأصلية مخفية خلف المحرر');
check(!info.disabled, 'المحرر مفعّل (license_key: gpl)');

// 2) الكتابة والتنسيق كما يفعل الموظف
const title = 'خبر تجريبي للمحرر ' + Date.now().toString().slice(-6);
await page.type('#f_title', title);
await focusEnd('f_content');
await page.keyboard.type('صرف المنحة');
// «فقرة» ← «عنوان 1»
await tool('f_content', 'blocks');
await page.waitForSelector('.tox-selected-menu .tox-collection__item', { timeout: 5000 });
await page.evaluate(() => [...document.querySelectorAll('.tox-selected-menu .tox-collection__item')].find((i) => i.textContent.trim() === 'عنوان 1').click());
await sleep(200);
await focusEnd('f_content');
await page.keyboard.press('Enter');
await page.keyboard.type('يعلن مدير الخدمات الجامعية عن ');
await page.keyboard.down('Control'); await page.keyboard.press('KeyB'); await page.keyboard.up('Control');
await page.keyboard.type('صرف المنحة');
await page.keyboard.down('Control'); await page.keyboard.press('KeyB'); await page.keyboard.up('Control');
await page.keyboard.type(' للطلبة، ');
await page.keyboard.down('Control'); await page.keyboard.press('KeyU'); await page.keyboard.up('Control');
await page.keyboard.type('ابتداءً من الأحد');
await page.keyboard.down('Control'); await page.keyboard.press('KeyU'); await page.keyboard.up('Control');
await page.keyboard.type('.');
await tool('f_content', 'aligncenter');
await page.keyboard.press('Enter');
await tool('f_content', 'aligncenter'); // الفقرة الجديدة ترث المحاذاة: نلغيها
await tool('f_content', 'bullist');
await page.keyboard.type('الإقامة دباغين');
await page.keyboard.press('Enter');
await page.keyboard.type('الإقامة الباز');
await page.keyboard.press('Enter');
await page.keyboard.press('Enter'); // سطر فارغ في القائمة = الخروج منها
await page.keyboard.type('التفاصيل في موقع الوزارة');
// تحديد «موقع الوزارة» ثم Ctrl+K: نافذة الرابط
await page.evaluate((text) => {
  const ed = tinymce.get('f_content');
  const p = ed.getBody().lastElementChild;
  const node = [...p.childNodes].reverse().find((n) => n.nodeType === 3 && n.data.includes(text));
  const r = ed.dom.createRng();
  const start = node.data.lastIndexOf(text);
  r.setStart(node, start);
  r.setEnd(node, start + text.length);
  ed.selection.setRng(r);
}, 'موقع الوزارة');
await page.keyboard.down('Control'); await page.keyboard.press('KeyK'); await page.keyboard.up('Control');
await page.waitForSelector('.tox-dialog input.tox-textfield', { timeout: 5000 });
await page.type('.tox-dialog input.tox-textfield', 'www.mesrs.dz');
await page.click('.tox-dialog__footer .tox-button:not(.tox-button--secondary)');
await page.waitForFunction(() => !document.querySelector('.tox-dialog'), { timeout: 5000 });
await sleep(300);
let ar = await content('f_content');
check(/<h2>صرف المنحة<\/h2>/.test(ar), 'عنوان h2 من قائمة «فقرة»: ' + ar.slice(0, 40));
check(/<strong>صرف المنحة<\/strong>/.test(ar), 'غامق (Ctrl+B) ← <strong>');
check(/<u>ابتداءً من الأحد<\/u>/.test(ar), 'تسطير (Ctrl+U) ← <u> (لا span style)');
check(/<p style="text-align: center;">يعلن/.test(ar), 'محاذاة للمنتصف ← style="text-align: center;" (كل الأدوات: المحاذاة الافتراضية في TinyMCE)');
check(/<ul>\s*<li>الإقامة دباغين<\/li>\s*<li>الإقامة الباز<\/li>\s*<\/ul>/.test(ar), 'قائمة نقطية: ' + ((ar.match(/<ul>[\s\S]*?<\/ul>/) || [''])[0]).replace(/\s+/g, ' '));
check(linkOk(ar, 'موقع الوزارة'), 'رابط من نافذة الرابط (www. ← https://): ' + ((ar.match(/<a [^>]+>[^<]*<\/a>/) || [''])[0]));
check(!/data-mce|<span/.test(ar) && (ar.match(/style="/g) || []).length === 1, 'لا data-mce ولا span في ناتج المحرر، و style المحاذاة وحدها');
check(await page.$eval('textarea[name=content]', (t) => t.value.replace(/\s+/g, ' ')) === ar.replace(/\s+/g, ' '), 'textarea الأصلي محدَّث مع المحرر');

// 3) الترجمة التلقائية تملأ محرري FR و EN مع بقاء التنسيق
// نمرّر إلى الزر أولاً (بعد الكتابة في المحرر يكون شريط أدواته اللاصق فوق أعلى الصفحة)
await page.$eval('[data-auto-translate]', (b) => b.scrollIntoView({ block: 'center' }));
await page.click('[data-auto-translate]');
await page.waitForFunction(() => /✓|✗/.test(document.querySelector('[data-tr-status]').textContent), { timeout: 20000 }).catch(async () => {
  console.log('  ! الحالة: ' + await page.$eval('[data-tr-status]', (e) => e.innerHTML) + ' | أخطاء: ' + errors.join(' | '));
});
check((await page.$eval('[data-tr-status]', (e) => e.textContent)).includes('✓'), 'زر «ترجمة تلقائية»: ' + await page.$eval('[data-tr-status]', (e) => e.textContent));
const fr = await content('f_content_fr');
const en = await content('f_content_en');
check(/<h2>Versement de la bourse<\/h2>/.test(fr) && /<ul>/.test(fr) && /<strong>/.test(fr) && /<u>/.test(fr) && /style="text-align: center;"/.test(fr) && linkOk(fr),
  'محرر FR: نفس الهيكل والتنسيق مترجماً: ' + fr.replace(/\s+/g, ' ').slice(0, 110));
check(/<h2>Scholarship payment<\/h2>/.test(en) && /<ul>/.test(en) && linkOk(en), 'محرر EN: ' + en.replace(/\s+/g, ' ').slice(0, 70));
check(await page.$eval('textarea[name=content_fr]', (t) => t.richEditor.element.classList.contains('admin-tr-new')), 'محرر الترجمة الجديدة ملوّن (admin-tr-new)');
check(await page.$eval('#f_title_fr', (i) => i.value) !== '', 'وعنوان الخبر بالفرنسية أيضاً');
await focusEnd('f_content');
await page.keyboard.type(' .');
await sleep(300);
check(await page.$eval('textarea[name=content_fr]', (t) => t.richEditor.element.classList.contains('admin-tr-stale')), 'تعديل العربي بعد الترجمة يلوّن محرر FR (admin-tr-stale)');
await page.evaluate(() => document.querySelector('label[for=f_content]').scrollIntoView());
await page.evaluate(() => window.scrollBy(0, -70));
await shot('admin-news-tinymce');

// 4) الحفظ
await submit('button[name=_continue]');
const editUrl = page.url();
check(/\/admin\/news\/\d+\/$/.test(editUrl), 'حُفظ الخبر ← صفحة تعديله: ' + editUrl.replace(BASE, ''));
await page.waitForFunction(() => window.tinymce && tinymce.get('f_content') && tinymce.get('f_content').initialized, { timeout: 20000 });
ar = await content('f_content');
check(/<h2>صرف المنحة<\/h2>/.test(ar) && /<ul>/.test(ar) && /<u>/.test(ar), 'المحتوى المحفوظ يعود إلى المحرر كما هو');
const slug = await page.$eval('#f_slug', (i) => i.value);
await go('/admin/news/');
const cell = await page.evaluate((t) => { const row = [...document.querySelectorAll('tr')].find((r) => r.textContent.includes(t)); return row ? row.textContent : ''; }, title);
check(cell !== '' && !/<p|<h2|&lt;/.test(cell), 'قائمة الأخبار في لوحة الإدارة: نص بلا وسوم');

// 5) الصفحة العامة بالعربية والفرنسية
for (const [lang, prefix, heading] of [['ar', '', 'صرف المنحة'], ['fr', '/fr', 'Versement de la bourse']]) {
  await go(`${prefix}/news/${encodeURIComponent(slug)}/`);
  const html = await page.$eval('.rich-text', (e) => e.innerHTML).catch(() => '');
  check(html.includes(`<h2>${heading}</h2>`) && /<strong>/.test(html) && /<u>/.test(html) && /<ul>/.test(html), `الصفحة العامة (${lang}): عنوان وغامق وتسطير وقائمة`);
  check(linkOk(html), `الصفحة العامة (${lang}): الرابط`);
  check(!/data-|<span/.test(html) && (html.match(/style="/g) || []).length === 1, `الصفحة العامة (${lang}): لا خصائص زائدة`);
  const align = await page.$eval('.rich-text p[style*="text-align: center"]', (p) => getComputedStyle(p).textAlign).catch(() => '');
  check(align === 'center', `المحاذاة للمنتصف تظهر في الصفحة (${lang}): ${align}`);
  check(await page.$eval('.rich-text ul', (u) => getComputedStyle(u).listStyleType).catch(() => '') === 'disc', `القائمة بنقاط (${lang})`);
  await page.evaluate(() => document.querySelector('.rich-text').scrollIntoView({ block: 'center' }));
  await shot('public-news-' + lang);
}

// 6) حذف الخبر التجريبي
await go(editUrl.replace(/\/$/, '/delete/'));
await submit('form button[type=submit]');
const gone = await page.goto(`${BASE}/news/${encodeURIComponent(slug)}/`, { waitUntil: 'networkidle0' });
check(gone.status() === 404, 'حُذف الخبر التجريبي (404)');

check(external.length === 0, 'المحرر يُحمَّل من الموقع نفسه (لا CDN): ' + (external[0] || ''));
check(errors.length === 0, 'لا أخطاء JavaScript' + (errors.length ? ': ' + errors.slice(0, 3).join(' | ') : ''));
await browser.close();
console.log(failures ? `✗ ${failures} فحص فاشل` : '✓ محرر TinyMCE يعمل');
process.exit(failures ? 1 : 0);
