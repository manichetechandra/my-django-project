// =====================================================================
//  لقطات الشاشة للكتاب: تفتح صفحات الموقع في Chromium وتحفظها في images/
//  المتطلبات: خادم الموقع يعمل (php -S 0.0.0.0:8000 index.php داخل php/)
//             وقاعدة بيانات فيها بيانات tools/shots-seed.php
//  الاستعمال: node tools/shots.mjs [--base=http://127.0.0.1:8000] [--only=24-list,24-detail]
//             node tools/shots.mjs --set=stages [--only=04-navbar]   لقطات الملفات المؤقتة (stages/*.src):
//             كل لقطة تُبنى في .build/stage-site ويُشغَّل لها خادم PHP مؤقت على المنفذ 8100
//             (قاعدة البيانات dou_stage للمراحل التي تستعمل PDO — أنشئها بـ tools/stage-db.php)
//  مكتبات CDN (Bootstrap, jQuery, Font Awesome, Chart.js) تُقدَّم من حزم npm محلية،
//  لأن بيئة البناء قد لا تصل إلى CDN. والباركود يُرسم محلياً بمكتبة bwip-js.
// =====================================================================
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { spawn } from 'node:child_process';
import net from 'node:net';
import puppeteer from 'puppeteer-core';
import { writeSnapshot } from './stages.mjs';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const SET = args.set || 'site';
const BASE = SET === 'stages' ? 'http://127.0.0.1:8100' : (args.base || 'http://127.0.0.1:8000').replace(/\/$/, '');
const ONLY = args.only ? new Set(args.only.split(',')) : null;
const CDN = path.join(BOOK, '.build', 'cdn');
const IMAGES = path.join(BOOK, 'images');

function pkg(spec) {
  const at = spec.lastIndexOf('@');
  const name = spec.slice(0, at);
  const ver = spec.slice(at + 1);
  const dir = path.join(CDN, spec);
  if (!fs.existsSync(path.join(dir, 'package.json'))) {
    fs.mkdirSync(dir, { recursive: true });
    const url = `https://registry.npmjs.org/${name}/-/${name.split('/').pop()}-${ver}.tgz`;
    execFileSync('bash', ['-c', `curl -sfL "${url}" | tar xz -C "${dir}" --strip-components=1`]);
  }
  return dir;
}

const MIME = { '.css': 'text/css', '.js': 'application/javascript', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf', '.eot': 'application/vnd.ms-fontobject', '.svg': 'image/svg+xml' };
function cdnFile(url) {
  const u = url.split('?')[0].split('#')[0];
  let m;
  if ((m = u.match(/^https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@3\.4\.1\/(.+)$/))) return path.join(pkg('bootstrap@3.4.1'), m[1]);
  if (/^https:\/\/code\.jquery\.com\/jquery-3\.6\.0(\.min)?\.js$/.test(u)) return path.join(pkg('jquery@3.6.0'), 'dist/jquery.min.js');
  if ((m = u.match(/^https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome\/4\.7\.0\/(.+)$/))) return path.join(pkg('font-awesome@4.7.0'), m[1]);
  if (/^https:\/\/cdn\.jsdelivr\.net\/npm\/chart\.js@4\.4\.0\/dist\/chart\.umd(\.min)?\.js$/.test(u)) return path.join(pkg('chart.js@4.4.0'), 'dist/chart.umd.js');
  return null;
}

let bwip = null;
function barcode(url) {
  if (!bwip) bwip = createRequire(import.meta.url)(path.join(pkg('bwip-js@4.11.4'), 'dist/bwip-js-node.js'));
  const p = new URL(url).searchParams;
  return bwip.toBuffer({
    bcid: p.get('bcid') || 'code128', text: p.get('text') || '0',
    scale: Number(p.get('scale') || 2), height: Number(p.get('height') || 10),
    includetext: p.get('includetext') !== 'false', textxalign: 'center',
  });
}

// ------------------------------------------------------------------ اللقطات
const W = Number(args.width || 1920);
const STUDENT = ['1234566', 'Talib2026x'];
const STAFF = ['miloudsellam', 'AdminDemo2026'];
// ------------------------------------------------------------------ لقطات المراحل (الملفات المؤقتة)
const stageShots = [
  { name: '02-first', stage: ['home', 2.5], url: '/', w: 1100, h: 330 },
  { name: '03-bootstrap', stage: ['home', 3], url: '/', w: 1200, h: 330 },
  { name: '04-navbar', stage: ['home', 4], url: '/', h: 120 },
  {
    name: '04-dropdown', stage: ['home', 4], url: '/', h: 300,
    act: async (page) => { await page.click('#myNavbar .dropdown-toggle'); },
  },
  {
    name: '04-mobile', stage: ['home', 4], url: '/', w: 420, h: 640,
    act: async (page) => { await page.click('.navbar-toggle'); await new Promise((r) => setTimeout(r, 600)); },
  },
  { name: '05-slider', stage: ['home', 5], url: '/', h: 600 },
  { name: '06-news', stage: ['home', 6], url: '/', h: 640, scrollTo: '#list-news', scrollPad: 112 },
  { name: '07-activities', stage: ['home', 7], url: '/', h: 720, scrollTo: 'h3:has(.fa-calendar-check-o)', scrollPad: 100 },
  { name: '07-stats', stage: ['home', 7], url: '/', h: 560, scrollTo: '.statistics-section', scrollPad: 60, wait: 2600 },
  { name: '08-departments', stage: ['home', 8], url: '/', h: 760, scrollTo: '.outer', scrollPad: 100 },
  { name: '08-cites', stage: ['home', 8], url: '/', h: 470, scrollTo: '#cite', scrollPad: 95 },
  { name: '09-footer', stage: ['home', 9], url: '/', h: 420, scrollTo: 'footer', scrollPad: 60 },
  { name: '09-full', stage: ['home', 9], url: '/', w: 1920, full: true, scaleTo: 0.3, wait: 2600 },
  // صفحات بمحتوى ثابت فوق الموقع التجريبي (/tmp/site): overlay = [stage, step]
  { name: '26-director-static', overlay: ['director', 26.1], url: '/director-word/', h: 760 },
  { name: '27-newslist-static', overlay: ['newslist', 27.1], url: '/news/', h: 900 },
  { name: '29-contact-static', overlay: ['contact', 29.1], url: '/contact/', h: 1000, scrollTo: 'form', scrollPad: 200 },
  // 15-db يحتاج قاعدة dou_stage بالخبر الإضافي: bash tools/stage-db.sh 15.5
  { name: '15-db', stage: ['home', 15], url: '/', h: 600, scrollTo: '#list-news', scrollPad: 112, files: ['config.php'] },
];

const shots = SET === 'stages' ? stageShots : [
  { name: '24-404', url: '/no-such-page/', h: 620 },
  { name: '25-home', url: '/', h: 900 },
  { name: '26-director', url: '/director-word/', h: 760 },
  { name: '26-organization', url: '/organization/', h: 900, scrollTo: 'img[src*=chart]', scrollPad: 160 },
  { name: '27-news-list', url: '/news/', h: 900 },
  { name: '27-news-detail', url: '/news/maktaba-jadida/', h: 1100 },
  { name: '27-activities', url: '/activities/', h: 900 },
  { name: '28-services', url: '/services/', h: 900 },
  {
    name: '29-contact', url: '/contact/', h: 1060, scrollTo: '.breadcrumb', scrollPad: 100,
    act: async (page) => {
      await page.type('input[name=full_name]', 'أح');
      await page.type('input[name=email]', 'ahmed@example.com');
      await page.type('input[name=phone]', '0555');
      await page.select('select[name=subject]', await page.$eval('select[name=subject] option:nth-child(2)', (o) => o.value));
      await page.type('textarea[name=message]', 'أريد الاستفسار عن موعد صرف المنحة الجامعية.');
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form:has(textarea[name=message]) [type=submit]')]);
    },
  },
  { name: '30-downloads', url: '/downloads/', h: 900 },
  { name: '30-search', url: '/search/?q=' + encodeURIComponent('المنحة الجامعية'), h: 900 },
  { name: '31-login', url: '/students/login/', h: 860 },
  { name: '22-dashboard', url: '/students/dashboard/', login: STUDENT, h: 1000 },
  { name: '22-create', url: '/students/applications/create/1/', login: STUDENT, h: 1100 },
  { name: '23-card', url: '/students/student-card/', login: STUDENT, w: 900, h: 470, scale: 2 },
  {
    name: '24-dashboard', url: '/students/admin/dashboard/', login: STAFF, untilFooter: true, wait: 1800,
    // صورتان من الصفحة الطويلة: الجزء العلوي (حتى جدول آخر الطلبات) والرسوم البيانية
    regions: [['24-dashboard', null, '.recent-table'], ['24-charts', 'h2:has(.fa-bar-chart)', 'bottom:#daysChart']],
  },
  {
    name: '24-list', url: '/students/admin/applications/', login: STAFF, h: 1000, scrollTo: 'ol.breadcrumb',
    act: async (page) => {
      const boxes = await page.$$('.app-checkbox');
      for (const i of [1, 2]) await boxes[i].click();
    },
  },
  { name: '24-detail', url: '/students/admin/applications/{id:202600009}/', login: STAFF, h: 1250 },
  { name: '25-index', url: '/admin/', login: STAFF, h: 900 },
  { name: '25-list', url: '/admin/news/', login: STAFF, h: 760 },
  { name: '25-edit', url: '/admin/sliders/1/', login: STAFF, h: 940 },
  { name: '25-app-edit', url: '/admin/student_applications/{id:202600007}/', login: STAFF, h: 1180 },
  { name: '25-applications', url: '/admin/student_applications/', login: STAFF, h: 820 },
];

async function login(page, [user, pass]) {
  await page.goto(BASE + '/students/login/', { waitUntil: 'networkidle0' });
  await page.type('input[name=username]', user);
  await page.type('input[name=password]', pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form:has(input[name=password]) [type=submit]')]);
}

async function resolveUrl(url) {
  // {id:202600009} ← رقم الطلب في قاعدة البيانات، من صفحة القائمة نفسها لا من SQL
  return url.replace(/\{id:(\d+)\}/, (_, num) => idByNumber[num] ?? '0');
}
const idByNumber = {};

// خطوط الموقع ('Segoe UI', Tahoma...) غير موجودة على Linux: نستبدلها بـ Noto Sans و Noto Sans Arabic
// القريبين منها في الشكل والعرض، حتى تشبه اللقطات ما يراه زائر على Windows
// (مجلد خطوط خاص باللقطات بلا Cairo و Noto Kufi، لأن Chromium يختارهما للعربية إن وُجدا)
const fontsConf = path.join(BOOK, '.build', 'shots-fonts.conf');
const fontsDir = path.join(BOOK, '.build', 'shots-fonts');
fs.mkdirSync(fontsDir, { recursive: true });
const nsa = pkg('@expo-google-fonts/noto-sans-arabic@0.4.3');
for (const [dir, files] of [
  [path.join(BT, 'fonts'), ['NotoSans_400Regular.ttf', 'NotoSans_700Bold.ttf', 'NotoSans_400Regular_Italic.ttf', 'NotoEmoji_400Regular.ttf', 'NotoSansSymbols2_400Regular.ttf', 'JetBrainsMono_400Regular.ttf']],
  [nsa, ['400Regular/NotoSansArabic_400Regular.ttf', '500Medium/NotoSansArabic_500Medium.ttf', '600SemiBold/NotoSansArabic_600SemiBold.ttf', '700Bold/NotoSansArabic_700Bold.ttf']],
]) {
  for (const f of files) fs.copyFileSync(path.join(dir, f), path.join(fontsDir, path.basename(f)));
}
const prefer = '<prefer><family>Noto Sans</family><family>Noto Sans Arabic</family></prefer>';
fs.writeFileSync(fontsConf, `<?xml version="1.0"?>
<!DOCTYPE fontconfig SYSTEM "fonts.dtd">
<fontconfig>
  <dir>${fontsDir}</dir>
  <cachedir>${path.join(BT, 'fonts-cache-shots')}</cachedir>
${['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'Arial', 'Helvetica', 'Helvetica Neue', 'sans-serif', 'serif']
  .map((f) => `  <alias binding="same"><family>${f}</family>${prefer}</alias>`).join('\n')}
  <alias><family>monospace</family><prefer><family>JetBrains Mono</family><family>Noto Sans Arabic</family></prefer></alias>
</fontconfig>
`);
const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--font-render-hinting=none', '--lang=ar'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: fontsConf },
});
fs.mkdirSync(IMAGES, { recursive: true });

async function newPage(ctx, h = 800, w = W, scale = 1) {
  const page = await ctx.newPage();
  await page.setViewport({ width: w, height: h, deviceScaleFactor: scale });
  await page.setRequestInterception(true);
  page.on('request', (req) => {
    const u = req.url();
    if (u.startsWith(BASE) || u.startsWith('data:') || u.startsWith('blob:')) return req.continue();
    try {
      const f = cdnFile(u);
      if (f && fs.existsSync(f)) {
        return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
      }
      if (u.startsWith('https://bwipjs-api.metafloor.com/')) return req.respond({ status: 200, contentType: 'image/png', body: barcode(u) });
    } catch (e) {
      console.warn('تعذر تقديم', u, e.message);
    }
    return req.respond({ status: 404, contentType: 'text/plain', body: '' });
  });
  page.on('pageerror', (e) => console.warn(`  خطأ JavaScript: ${e.message}`));
  page.on('dialog', (d) => { console.warn(`  نافذة: ${d.message()}`); d.dismiss(); });
  return page;
}

// ------------------------------------------------------------------ خادم مؤقت للمراحل
const PHP = process.env.PHP_BIN || '/tmp/tools/bin/php';
const SRC = path.resolve(BOOK, '..', 'php');
let server = null;
let serverKey = '';
const portOpen = (port) => new Promise((res) => {
  const sock = net.connect(port, '127.0.0.1', () => { sock.destroy(); res(true); });
  sock.on('error', () => res(false));
});
async function stopServer() {
  if (!server) return;
  server.kill('SIGTERM');
  for (let i = 0; i < 50 && (await portOpen(8100)); i++) await new Promise((r) => setTimeout(r, 100));
  server = null;
}
async function prepareStage(s) {
  const key = `${s.stage.join('@')}|${(s.files || []).join(',')}`;
  if (key === serverKey && server) return;
  await stopServer();
  const dir = path.join(BOOK, '.build', 'stage-site');
  fs.rmSync(dir, { recursive: true, force: true });
  fs.mkdirSync(dir, { recursive: true });
  writeSnapshot(s.stage[0], s.stage[1], dir);
  fs.cpSync(path.join(SRC, 'assets'), path.join(dir, 'assets'), { recursive: true });
  fs.cpSync(path.join(BOOK, 'demo', 'uploads'), path.join(dir, 'uploads'), { recursive: true });
  for (const f of s.files || []) fs.cpSync(path.join(SRC, f), path.join(dir, f), { recursive: true });
  server = spawn(PHP, ['-S', '127.0.0.1:8100', '-t', dir], { env: { ...process.env, DB_NAME: 'dou_stage' }, stdio: ['ignore', 'ignore', 'pipe'] });
  server.stderr.on('data', (d) => { const t = String(d); if (/error|warning|fatal/i.test(t) && !/Accepted|Closing|\[200\]|\[304\]/.test(t)) process.stderr.write('  php: ' + t); });
  for (let i = 0; i < 50 && !(await portOpen(8100)); i++) await new Promise((r) => setTimeout(r, 100));
  serverKey = key;
}

// صفحة من الموقع التجريبي الكامل (/tmp/site من tools/demo-site.sh) مع ملفات مؤقتة فوقه (مثل قالب بمحتوى ثابت)
const DEMO_SITE = process.env.DEMO_SITE || '/tmp/site';
async function prepareOverlay(s) {
  const key = `overlay|${s.overlay.join('@')}`;
  if (key === serverKey && server) return;
  await stopServer();
  const dir = path.join(BOOK, '.build', 'overlay-site');
  fs.rmSync(dir, { recursive: true, force: true });
  fs.cpSync(DEMO_SITE, dir, { recursive: true });
  writeSnapshot(s.overlay[0], s.overlay[1], dir);
  server = spawn(PHP, ['-S', '127.0.0.1:8100', '-t', dir, path.join(dir, 'index.php')], { env: { ...process.env, DB_NAME: 'dou_setif' }, stdio: ['ignore', 'ignore', 'pipe'] });
  server.stderr.on('data', (d) => { const t = String(d); if (/error|warning|fatal/i.test(t) && !/Accepted|Closing|\[200\]|\[30\d\]/.test(t)) process.stderr.write('  php: ' + t); });
  for (let i = 0; i < 50 && !(await portOpen(8100)); i++) await new Promise((r) => setTimeout(r, 100));
  serverKey = key;
}

let ctx = null;
let session = '';
for (const s of shots) {
  if (ONLY && !ONLY.has(s.name)) continue;
  if (s.stage) await prepareStage(s);
  if (s.overlay) await prepareOverlay(s);
  const want = s.login ? s.login[0] : 'guest';
  if (!ctx || want !== session) {
    if (ctx) await ctx.close();
    ctx = await browser.createBrowserContext();
    session = want;
    if (s.login) {
      const p = await newPage(ctx);
      await login(p, s.login);
      if (s.login === STAFF && !Object.keys(idByNumber).length) {
        await p.goto(BASE + '/students/admin/applications/', { waitUntil: 'networkidle0' });
        Object.assign(idByNumber, await p.$$eval('tr.app-row', (rows) => Object.fromEntries(rows.map((r) => [r.querySelector('strong').textContent.trim(), r.dataset.id]))));
      }
      await p.close();
    }
  }
  const page = await newPage(ctx, s.full ? 800 : s.h || 800, s.w || W, s.scale || 1);
  await page.goto(BASE + (await resolveUrl(s.url)), { waitUntil: 'networkidle0' });
  if (s.act) await s.act(page);
  // إخفاء رسائل التنبيه العائمة (إلا في صفحة اتصل بنا) وشريط إشعار الموقع إن غطّى المحتوى
  await page.addStyleTag({ content: `${s.name === '29-contact' ? '' : '.flash-messages{display:none!important}'} *{transition:none!important;animation:none!important}` });
  await page.evaluate(() => document.fonts.ready);
  if (s.scrollTo) {
    await page.evaluate((sel, pad) => {
      const el = document.querySelector(sel);
      if (el) window.scrollTo(0, Math.max(0, el.getBoundingClientRect().top + window.scrollY - pad));
    }, s.scrollTo, s.scrollPad ?? 115);
  }
  await new Promise((r) => setTimeout(r, s.wait || 400));
  const file = path.join(IMAGES, `${s.name}.png`);
  if (s.untilFooter) {
    // الصفحة كاملة حتى بداية التذييل
    const bottom = await page.$eval('footer', (el) => Math.ceil(el.getBoundingClientRect().top + window.scrollY));
    await page.setViewport({ width: W, height: bottom, deviceScaleFactor: 1 });
    await new Promise((r) => setTimeout(r, s.wait || 400));
    await page.evaluate(() => window.scrollTo(0, 0));
    const full = s.regions ? path.join(BOOK, '.build', `${s.name}-full.png`) : file;
    await page.screenshot({ path: full });
    if (s.regions) {
      // «bottom:محدد» = أسفل العنصر الأب (البطاقة) بدل أعلى العنصر
      const top = (sel) => sel.startsWith('bottom:')
        ? page.$eval(sel.slice(7), (el) => Math.round(el.parentElement.getBoundingClientRect().bottom + window.scrollY) + 50)
        : page.$eval(sel, (el) => Math.round(el.getBoundingClientRect().top + window.scrollY));
      for (const [name, from, to] of s.regions) {
        const y0 = from ? (await top(from)) - 25 : 0;
        const y1 = (await top(to)) - 25;
        const out = path.join(IMAGES, `${name}.png`);
        execFileSync('python3', ['-c', 'import sys; from PIL import Image; im = Image.open(sys.argv[1]); im.crop((0, int(sys.argv[3]), im.width, int(sys.argv[4]))).save(sys.argv[2], optimize=True)', full, out + '.tmp.png', String(y0), String(y1)], { env: { ...process.env, PYTHONPATH: path.join(BT, 'pylib') } });
        fs.renameSync(out + '.tmp.png', out);
        console.log(`  ← ${name} (${y0}–${y1})`);
      }
    }
  } else {
    await page.screenshot({ path: file, fullPage: !!s.full });
    if (s.scaleTo) {
      execFileSync('python3', ['-c', 'import sys; from PIL import Image; im = Image.open(sys.argv[1]); f = float(sys.argv[2]); im.convert("RGB").resize((int(im.width * f), int(im.height * f)), Image.LANCZOS).save(sys.argv[1], optimize=True)', file, String(s.scaleTo)], { env: { ...process.env, PYTHONPATH: path.join(BT, 'pylib') } });
    }
  }
  console.log(`✓ ${s.name}  (${(fs.statSync(file).size / 1024).toFixed(0)} KB)`);
  await page.close();
}
await stopServer();
await browser.close();
