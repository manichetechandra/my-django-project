// نسخة TinyMCE الخاصة بالمستخدم في متصفح حقيقي: نضع إصدارات حقيقية (من npm) في أماكن مختلفة من الموقع التجريبي
// ونتحقق أن نموذج الإدارة يستعملها هي (الإصدار المحمَّل، مصدر الملفات، الواجهة العربية، الكتابة تصل للحقل،
// سطر «محرر النصوص: ...» أسفل النموذج)، ثم نحذفها ونعيد الموقع كما كان.
//   node dev/tinymce_versions.mjs [--base=http://127.0.0.1:8002] [--site=/tmp/site2] [--cache=/tmp/tmce/user] [--admin=miloudsellam:AdminDemo2026]
//   موقع في مجلد فرعي كما في XAMPP (http://localhost/dou-setif/) مع نسخة بجانبه في htdocs/tinymce:
//   node dev/tinymce_versions.mjs --base=http://127.0.0.1:8105/dou-setif --site=/tmp/htdocs3/dou-setif --sibling=1
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const require = createRequire(path.join(ROOT, 'book', 'tools', 'shots.mjs'));
const puppeteer = require('puppeteer-core');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const BASE = (args.base || 'http://127.0.0.1:8002').replace(/\/$/, '');
const SITE = args.site || '/tmp/site2';
const CACHE = args.cache || '/tmp/tmce/user';
const [ADMIN, PASS] = (args.admin || 'miloudsellam:AdminDemo2026').split(':');
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
const ORIGIN = new URL(BASE).origin;
const BASE_PATH = new URL(BASE).pathname.replace(/\/$/, ''); // '' أو '/dou-setif'
const BUNDLED = path.join(SITE, 'assets', 'vendor', 'tinymce');
const AWAY = SITE.replace(/\/$/, '') + '.vendor-away'; // خارج مجلد الموقع حتى لا يُعدّ نسخة أخرى

/** حزمة tinymce@ver من npm (مجلد package/ فيه tinymce.min.js) */
function tinymce(ver) {
  const dir = path.join(CACHE, 'v' + ver);
  if (!fs.existsSync(path.join(dir, 'package', 'tinymce.min.js'))) {
    fs.mkdirSync(dir, { recursive: true });
    execFileSync('bash', ['-c', `curl -sfL https://registry.npmjs.org/tinymce/-/tinymce-${ver}.tgz | tar xz -C '${dir}'`]);
  }
  return path.join(dir, 'package');
}

// الحالات: [وصف، الإصدار (null = نسخة المشروع)، مكان النسخة داخل الموقع، المسار المتوقع في سطر «محرر النصوص»]
const CASES = [
  ['نسخة المشروع وحدها', null, null, 'assets/vendor/tinymce/tinymce.min.js'],
  ['7.9.3 — مجلد التحميل الرسمي في مجلد الموقع', '7.9.3', 'tinymce/js/tinymce', 'tinymce/js/tinymce/tinymce.min.js'],
  ['6.8.6 — منسوخ كما هو داخل assets/vendor/tinymce', '6.8.6', 'assets/vendor/tinymce/tinymce_6.8.6/tinymce/js/tinymce', 'assets/vendor/tinymce/tinymce_6.8.6/tinymce/js/tinymce/tinymce.min.js'],
  ['5.10.9 — Composer: vendor/tinymce/tinymce', '5.10.9', 'vendor/tinymce/tinymce', 'vendor/tinymce/tinymce/tinymce.min.js'],
  ['7.9.3 — اسم مجلد فيه مسافة', '7.9.3', 'tinymce 7/js/tinymce', 'tinymce 7/js/tinymce/tinymce.min.js'],
  ['4.9.11 — assets/js/tinymce', '4.9.11', 'assets/js/tinymce', 'assets/js/tinymce/tinymce.min.js'],
];
if (args.sibling) {
  CASES.push(['7.9.3 — htdocs/tinymce بجانب مجلد الموقع', '7.9.3', '../tinymce/js/tinymce', '../tinymce/js/tinymce/tinymce.min.js']);
}

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
let requests = [];
let errors = [];
page.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(ORIGIN + '/') || /^(data|blob|about):/.test(u)) { requests.push(u); return req.continue(); }
  if (/tinymce|tiny\.cloud/.test(u)) errors.push('محرر من خارج الموقع: ' + u);
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
page.on('response', (r) => { if (r.url().startsWith(ORIGIN + '/') && r.status() >= 400) errors.push(`HTTP ${r.status()} ${r.url()}`); });
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push('console: ' + m.text()); });
let failures = 0;
const check = (cond, label, detail = '') => { console.log((cond ? '  ✓ ' : '  ✗ ') + label + (!cond && detail ? `\n      ${detail}` : '')); if (!cond) failures++; return cond; };
const go = (url) => page.goto(BASE + url, { waitUntil: 'networkidle0' });

// دخول المدير
await go('/admin/login/');
await page.type('input[name=username]', ADMIN);
await page.type('input[name=password]', PASS);
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form [type=submit]')]);
check(!page.url().includes('/login'), 'دخول المدير');

const created = [];
const bundledAway = AWAY;
try {
  for (const [label, ver, place, shown] of CASES) {
    console.log(`— ${label}`);
    let dest = null;
    if (ver) {
      dest = path.join(SITE, place);
      // نحذف بعد الحالة أول مجلد أنشأناه نحن فقط (لا assets/vendor/tinymce نفسه)
      const segs = place.split('/');
      let first = SITE;
      for (const seg of segs) { first = path.resolve(first, seg); if (!fs.existsSync(first)) break; }
      if (fs.existsSync(first)) throw new Error('المجلد موجود مسبقاً: ' + first);
      created.push(first);
      fs.cpSync(tinymce(ver), dest, { recursive: true });
    }
    requests = [];
    errors = [];
    await go('/admin/faqs/add/');
    const n = await page.$$eval('textarea[data-richtext]', (a) => a.length);
    let ready = false;
    try {
      await page.waitForFunction((k) => document.querySelectorAll('textarea[data-richtext]').length === k
        && [...document.querySelectorAll('textarea[data-richtext]')].every((t) => t.richEditor), { timeout: 20000 }, n);
      ready = true;
    } catch { /* يُسجَّل أدناه */ }
    const info = await page.evaluate(() => ({
      version: window.tinymce ? `${tinymce.majorVersion}.${tinymce.minorVersion}` : null,
      editors: window.tinymce ? (tinymce.editors?.length ?? tinymce.get().length) : 0,
      line: document.querySelector('[data-editor-source]')?.textContent.replace(/\s+/g, ' ').trim() || '',
      bold: document.querySelector('.tox-tbtn[aria-label], .mce-btn[aria-label]') ? [...document.querySelectorAll('.tox-tbtn[aria-label], .mce-btn[aria-label]')].map((b) => b.getAttribute('aria-label')).slice(0, 12).join('|') : '',
      // الترجمة نفسها (TinyMCE 4 يضع في aria-label النص الإنجليزي ويترجم التلميح الظاهر فقط)
      tr: window.tinymce && tinymce.util && tinymce.util.I18n ? tinymce.util.I18n.translate('Bold') + '|' + tinymce.util.I18n.translate('Insert/edit link') : '',
    }));
    const want = ver ? ver.split('.').slice(0, 2).join('.') : '8.10';
    check(ready && n === 3, `${n} محررات جاهزة (عربي + FR + EN)`, JSON.stringify(info));
    check(info.version !== null && info.version.startsWith(want), `الإصدار المحمَّل في الصفحة ${info.version} = ${want}`);
    check(info.line.includes(`TinyMCE ${info.version}`) && info.line.includes(shown)
      && info.line.includes(ver ? 'نسختك' : 'النسخة المرفقة بالمشروع'), `سطر «محرر النصوص»: ${info.line}`);
    const core = requests.filter((u) => /\/tinymce(\.min)?\.js(\?|$)/.test(decodeURIComponent(u)));
    const dirUrl = path.posix.normalize(BASE_PATH + '/' + path.posix.dirname(shown)); // ../tinymce ← /tinymce من جذر الخادم
    const fromShown = (u) => decodeURIComponent(new URL(u).pathname).startsWith(dirUrl + '/');
    check(core.length === 1 && fromShown(core[0]), `tinymce.min.js من ${path.dirname(shown)}/ وحده`, core.join(' , '));
    const parts = requests.filter((u) => /\/(plugins|themes|models|icons|skins)\//.test(u));
    check(parts.length > 3 && parts.every(fromShown), `الإضافات والواجهة (${parts.length} ملفاً) من المجلد نفسه`, parts.filter((u) => !fromShown(u)).join(' , '));
    const lang = requests.filter((u) => /\/langs\/ar[^/]*\.js/.test(u));
    check(lang.length === 1, `ملف العربية: ${lang.map((u) => new URL(u).pathname).join(' ')}`);
    check(/[\u0600-\u06FF]/.test(info.tr) && !/Bold|link/.test(info.tr), `ترجمة واجهة المحرر بالعربية (${info.tr})`, info.tr);
    if (!want.startsWith('4.')) check(/غامق|عريض/.test(info.bold), 'أزرار المحرر بالعربية', info.bold);
    // الكتابة في المحرر العربي تصل إلى textarea (زر الترجمة وحفظ النموذج يقرآنه)
    if (ready) {
      const typed = await page.evaluate(async () => {
        const ta = document.querySelector('textarea[data-richtext="ar"]');
        const ed = ta.richEditor.editor;
        ed.focus();
        ed.selection.select(ed.getBody(), true);
        ed.selection.collapse(false);
        return true;
      });
      await page.keyboard.type('نص من نسخة المستخدم');
      await new Promise((r) => setTimeout(r, 300));
      const val = await page.$eval('textarea[data-richtext="ar"]', (t) => t.value);
      check(typed && val.includes('<p>') && val.includes('نص من نسخة المستخدم'), 'الكتابة تصل إلى الحقل بصيغة HTML', val.slice(0, 120));
    }
    check(errors.length === 0, 'لا أخطاء في الصفحة ولا ملفات مفقودة', errors.slice(0, 5).join(' | '));
    if (dest) {
      for (const d of created.splice(0)) fs.rmSync(d, { recursive: true, force: true });
    }
    check(fs.existsSync(path.join(BUNDLED, 'tinymce.min.js')) && fs.existsSync(path.join(BUNDLED, 'langs', 'ar.js')), 'نسخة المشروع باقية كما هي بعد الحالة');
  }

  // لا TinyMCE إطلاقاً: رسالة واضحة، والحقول تبقى خانات نص عادية
  console.log('— لا TinyMCE إطلاقاً');
  fs.renameSync(BUNDLED, bundledAway);
  errors = [];
  await go('/admin/faqs/add/');
  const none = await page.evaluate(() => ({
    warning: [...document.querySelectorAll('.alert-warning')].map((a) => a.textContent.replace(/\s+/g, ' ')).join(' '),
    plain: [...document.querySelectorAll('textarea[data-richtext]')].every((t) => t.offsetParent !== null && !t.richEditor),
    line: !!document.querySelector('[data-editor-source]'),
  }));
  check(none.warning.includes('غير موجود') && none.warning.includes('tinymce/') && none.warning.includes('config.php'), 'رسالة «TinyMCE غير موجود» مع أماكن وضعه');
  check(none.plain && !none.line, 'الحقول خانات نص عادية، ولا سطر «محرر النصوص»');
} finally {
  for (const d of created) fs.rmSync(d, { recursive: true, force: true });
  if (fs.existsSync(bundledAway)) fs.renameSync(bundledAway, BUNDLED);
  await browser.close();
}
console.log(failures ? `✗ ${failures} فحص فشل` : '✓ كل الفحوص ناجحة');
process.exit(failures ? 1 : 0);
