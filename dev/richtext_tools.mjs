// «كل الأدوات» في محرر TinyMCE بمتصفح حقيقي: ما تراه في المحرر هو ما يُحفظ ويظهر في الموقع.
//   خبر جديد ← لون نص وخلفية، خط وحجم، تباعد أسطر، محاذاة، إزاحة، اتجاه، منخفض/مرتفع، خط فاصل، جدول، صورة مرفوعة
//   (رفع حقيقي إلى /admin/editor/upload/) مع تعليق، فيديو YouTube من نافذة «الوسائط»، أكورديون، مثال برمجي، مرساة، رموز
//   ← حفظ ← مقارنة المحفوظ بما في المحرر ← الصفحة العامة (الألوان والأحجام محسوبة فعلاً، الصورة تُحمَّل، الفيديو بـ sandbox)
//   ← حذف الخبر والصورة (البيانات التجريبية كما هي)
//   node dev/richtext_tools.mjs [--base=http://127.0.0.1:8002] [--site=/tmp/site2] [--out=/tmp/shots/tools] [--admin=miloudsellam:AdminDemo2026]
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
const SITE = args.site || '/tmp/site2';
const OUT = args.out || '/tmp/shots/tools';
const [ADMIN, PASS] = (args.admin || 'miloudsellam:AdminDemo2026').split(':');
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
const BP = new URL(BASE).pathname.replace(/\/$/, ''); // '' أو '/dou-setif' (موقع في مجلد فرعي كما في XAMPP)
const BPRE = BP.replace(/[.*+?^${}()|[\]\\\/]/g, '\\$&');
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
const errors = [];
const embeds = [];
page.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(BASE) || /^(data|blob|about):/.test(u)) return req.continue();
  if (/youtube|ytimg|google/.test(u)) embeds.push(u); // لا إنترنت في الاختبار: الفيديو نفسه لا يُحمَّل
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push('console: ' + m.text()); });
page.on('dialog', (d) => d.accept());
let failures = 0;
const check = (cond, label, detail = '') => { console.log((cond ? '  ✓ ' : '  ✗ ') + label + (!cond && detail ? `\n      ${detail}` : '')); if (!cond) failures++; return cond; };
const go = (url) => page.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'networkidle0' });
const submit = (selector) => Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click(selector)]);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const ed = (fn, ...a) => page.evaluate(fn, ...a);

// 1) الدخول ونموذج خبر جديد
await go('/admin/login/');
await page.type('input[name=username]', ADMIN);
await page.type('input[name=password]', PASS);
await submit('form [type=submit]');
await go('/admin/news/add/');
await page.waitForFunction(() => window.tinymce && tinymce.get().length === 3 && tinymce.get().every((e) => e.initialized), { timeout: 30000 });
const title = 'خبر كل الأدوات ' + Date.now().toString().slice(-6);
await page.type('#f_title', title);

// 2) كل الأدوات عبر أوامر المحرر نفسها (ما تفعله الأزرار والقوائم)
const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAAEklEQVR42mP4z8DAwMDAxMDAAAAWBAH/cyb1NQAAAABJRU5ErkJggg==';
await ed((png) => {
  const e = tinymce.get('f_content');
  e.focus();
  e.setContent('<p>نص ملوّن وبخط آخر ومظلَّل.</p><p>فقرة في الوسط بتباعد أسطر.</p><p>فقرة مزاحة.</p>'
    + '<p>Texte en français</p><p>H2O و x2</p><p>قبل الجدول</p>');
  // تحديد نص كما يفعل الموظف بالفأرة
  const pick = (text, from = 0, len = null) => {
    const w = e.getDoc().createTreeWalker(e.getBody(), NodeFilter.SHOW_TEXT);
    let n;
    while ((n = w.nextNode())) {
      const i = n.data.indexOf(text);
      if (i >= 0) {
        const r = e.dom.createRng();
        r.setStart(n, i + from);
        r.setEnd(n, i + from + (len === null ? text.length : len));
        e.selection.setRng(r);
        return n;
      }
    }
    throw new Error('النص غير موجود: ' + text);
  };
  pick('ملوّن'); e.execCommand('ForeColor', false, '#e03e2d');
  pick('بخط آخر'); e.execCommand('FontName', false, 'traditional arabic,serif'); e.execCommand('FontSize', false, '18pt');
  pick('مظلَّل'); e.execCommand('HiliteColor', false, '#fbeeb8');
  pick('فقرة في الوسط'); e.execCommand('JustifyCenter'); e.execCommand('LineHeight', false, '1.5');
  pick('فقرة مزاحة'); e.execCommand('Indent');
  pick('Texte en'); e.execCommand('mceDirectionLTR');
  pick('H2O', 1, 1); e.execCommand('Subscript');
  pick('x2', 1, 1); e.execCommand('Superscript');
  // بعد «قبل الجدول»: خط فاصل وجدول 2×2
  pick('قبل الجدول'); e.selection.collapse(false);
  e.execCommand('InsertHorizontalRule');
  e.execCommand('mceInsertTable', false, { rows: 2, columns: 2 });
  const cells = e.getBody().querySelectorAll('table td');
  cells[0].textContent = 'خلية أ'; cells[1].textContent = 'خلية ب';
  // في الآخر: صورة (data: ← تُرفع)، مثال برمجي، مرساة، رموز، أكورديون
  e.selection.select(e.getBody(), true); e.selection.collapse(false);
  const atEnd = () => { e.selection.select(e.getBody(), true); e.selection.collapse(false); };
  e.insertContent(`<p>صورة: <img src="data:image/png;base64,${png}" alt="مربع" width="40" height="40"></p>`);
  atEnd(); e.insertContent('<pre class="language-php"><code>&lt;?php echo "مرحبا"; ?&gt;</code></pre>');
  // المثال البرمجي يبقى محدَّداً بعد إدراجه (كتلة غير قابلة للتعديل): فقرة جديدة بعده، كما يضغط الموظف Enter
  e.dom.add(e.getBody(), 'p', null, 'بعد المثال');
  atEnd(); e.insertContent('<p><a id="part-2"></a>مرساة و<a href="#part-2">رابط إليها</a> 😀 ©</p>');
  atEnd(); e.execCommand('InsertAccordion');
}, PNG);
await sleep(300);
// رفع الصورة الحقيقي: TinyMCE يرسلها إلى /admin/editor/upload/ (images_upload_handler) ويضع رابطها مكانها
const uploaded = await ed(async () => {
  const e = tinymce.get('f_content');
  const res = await e.uploadImages();
  return { res: res.map((x) => ({ status: x.status, url: x.uploadUri || (x.element && x.element.getAttribute('src')) })), html: e.getContent() };
});
const imgSrc = (uploaded.html.match(/<img src="([^"]+)"/) || [])[1] || '';
check(new RegExp('^' + BPRE + '/uploads/editor/\\d{4}/\\d{2}/[\\w-]+_[0-9a-f]{8}\\.png$').test(imgSrc), 'رفع الصورة من المحرر ← uploads/editor/...: ' + imgSrc, JSON.stringify(uploaded.res));
// تعليق تحت الصورة (image_caption): نافذة «صورة» ← «إظهار التسمية التوضيحية»
await ed(() => { const e = tinymce.get('f_content'); e.selection.select(e.getBody().querySelector('img')); e.execCommand('mceImage'); });
await page.waitForSelector('.tox-dialog', { timeout: 5000 });
const captionBox = await page.evaluateHandle(() => [...document.querySelectorAll('.tox-dialog .tox-checkbox')].find((c) => /الشرح|تسمية|caption/i.test(c.textContent)));
const hasCaption = !!(await captionBox.evaluate((c) => !!c).catch(() => false));
check(hasCaption, 'نافذة «صورة»: خيار «إظهار الشرح» (تعليق تحت الصورة)');
if (hasCaption) await captionBox.asElement().click();
await page.click('.tox-dialog__footer .tox-button:not(.tox-button--secondary)');
await page.waitForFunction(() => !document.querySelector('.tox-dialog'), { timeout: 5000 });
await ed(() => { const c = tinymce.get('f_content').getBody().querySelector('figcaption'); if (c) c.textContent = 'تعليق الصورة'; });
// فيديو: نافذة «الوسائط» ← رابط YouTube عادي ← iframe
await ed(() => {
  const e = tinymce.get('f_content');
  const p = e.dom.add(e.getBody(), 'p', null, 'الفيديو: '); // فقرة بعد الأكورديون
  e.selection.select(p, true); e.selection.collapse(false);
  e.execCommand('mceMedia');
});
await page.waitForSelector('.tox-dialog input', { timeout: 5000 });
await page.type('.tox-dialog input', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
await sleep(300);
await page.click('.tox-dialog__footer .tox-button:not(.tox-button--secondary)');
await page.waitForFunction(() => !document.querySelector('.tox-dialog'), { timeout: 5000 });
await sleep(400);
const E = await ed(() => { const e = tinymce.get('f_content'); e.save(); return e.getContent(); });
const want = [
  ['لون النص', /<span style="color: (#e03e2d|rgb\(224, 62, 45\));">ملوّن<\/span>/],
  ['الخط والحجم', /font-family: 'traditional arabic', serif;/, /font-size: 18pt;/],
  ['لون الخلفية', /background-color: (#fbeeb8|rgb\(251, 238, 184\));/],
  ['محاذاة وتباعد أسطر', /<p style="text-align: center; line-height: 1\.5;">|<p style="line-height: 1\.5; text-align: center;">/],
  ['إزاحة', /<p style="padding-right: 40px;">فقرة مزاحة/],
  ['اتجاه من اليسار', /<p dir="ltr"[^>]*>Texte en français/],
  ['منخفض ومرتفع', /<sub>2<\/sub>/, /<sup>2<\/sup>/],
  ['خط فاصل وجدول', /<hr>/, /<table[^>]*style="[^"]*border-collapse: collapse[^"]*"[^>]*>[\s\S]*خلية أ/],
  ['صورة مرفوعة بتعليق', new RegExp('<figure class="image"><img src="' + BPRE + '/uploads/editor/[^"]+" alt="مربع" width="40" height="40">\\s*<figcaption>تعليق الصورة</figcaption>\\s*</figure>')],
  ['فيديو YouTube', /<iframe src="https:\/\/www\.youtube\.com\/embed\/dQw4w9WgXcQ"[^>]*><\/iframe>/],
  ['أكورديون', /<details [^>]*class="mce-accordion"[^>]*>\s*<summary[^>]*>/],
  ['مثال برمجي', /<pre class="language-php"><code>&lt;\?php echo "مرحبا"; \?&gt;<\/code><\/pre>/],
  ['مرساة ورمز', /<a id="part-2"><\/a>مرساة و<a href="#part-2">رابط إليها<\/a> 😀 ©/],
];
for (const [label, ...res] of want) {
  check(res.every((re) => re.test(E)), `المحرر: ${label}`, E.replace(/\s+/g, ' ').slice(0, 1500));
}

// 3) الحفظ: المحفوظ = ما في المحرر (عدا خصائص يضيفها الخادم: loading و sandbox و referrerpolicy و rel)
await page.$eval('button[name=_continue]', (b) => b.scrollIntoView({ block: 'center' }));
await submit('button[name=_continue]');
const editUrl = page.url();
check(/\/admin\/news\/\d+\/$/.test(editUrl), 'حُفظ الخبر ← صفحة تعديله: ' + editUrl.replace(BASE, ''));
const slug = await page.$eval('#f_slug', (i) => i.value);
await go(`/news/${encodeURIComponent(slug)}/`);
const S = await page.$eval('.rich-text', (e) => e.innerHTML);
const norm = await ed((a, b) => {
  const clean = (html) => {
    const d = document.createElement('div');
    d.innerHTML = html;
    d.querySelectorAll('[loading],[sandbox],[referrerpolicy],[rel]').forEach((n) => ['loading', 'sandbox', 'referrerpolicy', 'rel'].forEach((x) => n.removeAttribute(x)));
    return d.innerHTML.replace(/\s+/g, ' ').replace(/> </g, '><').trim();
  };
  return { e: clean(a), s: clean(b) };
}, E, S);
let diff = '';
if (norm.e !== norm.s) {
  let i = 0;
  while (i < norm.e.length && norm.e[i] === norm.s[i]) i++;
  diff = `المحرر: …${norm.e.slice(Math.max(0, i - 80), i + 120)}\n      الموقع: …${norm.s.slice(Math.max(0, i - 80), i + 120)}`;
}
check(norm.e === norm.s, 'ما في المحرر هو ما حُفظ ويظهر في الموقع (لا شيء ضاع في التنظيف)', diff);
check(/<iframe [^>]*sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox"/.test(S)
  && /referrerpolicy="strict-origin-when-cross-origin"/.test(S), 'الفيديو في الموقع داخل sandbox');

// 4) الصفحة العامة: التنسيق محسوب فعلاً في المتصفح
const look = await ed(() => {
  const q = (s) => document.querySelector('.rich-text ' + s);
  const cs = (s, p) => { const n = q(s); return n ? getComputedStyle(n)[p] : ''; };
  const img = q('img[src*="/uploads/editor/"]');
  return {
    color: cs('span[style*="color: "]:not([style*="background"])', 'color'),
    size: cs('span[style*="font-size"]', 'fontSize'),
    bg: cs('span[style*="background-color"]', 'backgroundColor'),
    align: cs('p[style*="text-align: center"]', 'textAlign'),
    indent: cs('p[style*="padding-right"]', 'paddingRight'),
    dir: cs('p[dir="ltr"]', 'direction'),
    img: img ? { w: img.naturalWidth, ok: img.complete && img.naturalWidth > 0, max: getComputedStyle(img).maxWidth } : null,
    caption: (q('figure.image figcaption') || {}).textContent,
    details: !!q('details summary'),
    table: q('table') ? getComputedStyle(q('td')).borderTopStyle : '',
    overflow: document.querySelector('.rich-text').scrollWidth <= document.querySelector('.rich-text').clientWidth + 1,
  };
});
check(look.color === 'rgb(224, 62, 45)' && look.size === '24px' && look.bg === 'rgb(251, 238, 184)', `الألوان والحجم في الصفحة: ${look.color} / ${look.size} / ${look.bg}`);
check(look.align === 'center' && look.indent === '40px' && look.dir === 'ltr', `المحاذاة والإزاحة والاتجاه: ${look.align} / ${look.indent} / ${look.dir}`);
check(look.img && look.img.ok && look.img.w === 4, 'الصورة المرفوعة تُحمَّل في الصفحة', JSON.stringify(look.img));
check(look.caption === 'تعليق الصورة' && look.details && look.table === 'solid', 'التعليق والأكورديون والجدول يظهرون');
check(look.overflow, 'لا شيء يتجاوز عرض النص');
await page.evaluate(() => document.querySelector('.rich-text').scrollIntoView({ block: 'start' }));
await page.evaluate(() => window.scrollBy(0, -90));
await page.screenshot({ path: path.join('/', 'tmp', 'shots', 'tools', 'public-tools.png') }).catch(() => {});

// 5) حذف الخبر والصورة المرفوعة
await go(editUrl.replace(/\/$/, '/delete/'));
await submit('form button[type=submit]');
const gone = await page.goto(`${BASE}/news/${encodeURIComponent(slug)}/`, { waitUntil: 'networkidle0' });
check(gone.status() === 404, 'حُذف الخبر التجريبي (404)');
if (imgSrc) {
  const file = path.join(SITE, decodeURIComponent(imgSrc.replace(/^.*?\/uploads\//, 'uploads/')));
  if (fs.existsSync(file)) fs.unlinkSync(file);
}
check(errors.length === 0, 'لا أخطاء JavaScript' + (errors.length ? ': ' + errors.slice(0, 3).join(' | ') : ''));
await browser.close();
console.log(failures ? `✗ ${failures} فحص فاشل` : '✓ كل أدوات المحرر تعمل، وما تراه هو ما يُحفظ');
process.exit(failures ? 1 : 0);
