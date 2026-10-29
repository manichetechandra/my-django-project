// لقطات كاملة لكل صفحات الموقع (للتطوير: مقارنة قبل/بعد، ومراجعة اللغات)
//   node dev/pages.mjs --base=http://127.0.0.1:8002 --out=/tmp/shots/ar [--lang=fr] [--only=home,news] [--width=1366]
// يتطلب أدوات الكتاب: cd book && bash tools/setup.sh (Chromium في /tmp/booktools وحزم node_modules)
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const BOOK = path.join(ROOT, 'book');
const require = createRequire(path.join(BOOK, 'tools', 'shots.mjs'));
const puppeteer = require('puppeteer-core');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const BASE = (args.base || 'http://127.0.0.1:8002').replace(/\/$/, '');
const OUT = args.out || '/tmp/shots/ar';
const LANG = args.lang && args.lang !== 'ar' ? '/' + args.lang : '';
const ONLY = args.only ? new Set(args.only.split(',')) : null;
const W = Number(args.width || 1366);
const MAXH = Number(args.maxh || 7000);
const CDN = path.join(BOOK, '.build', 'cdn');

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

// نفس خطوط لقطات الكتاب: Noto Sans و Noto Sans Arabic بدل Segoe UI و Tahoma
const fontsDir = '/tmp/devshots-fonts';
const fontsConf = '/tmp/devshots-fonts.conf';
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
  <cachedir>/tmp/devshots-fonts-cache</cachedir>
${['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'Arial', 'Helvetica', 'Helvetica Neue', 'sans-serif', 'serif']
  .map((f) => `  <alias binding="same"><family>${f}</family>${prefer}</alias>`).join('\n')}
  <alias><family>monospace</family><prefer><family>JetBrains Mono</family><family>Noto Sans Arabic</family></prefer></alias>
</fontconfig>
`);

const STUDENT = ['1234566', 'Talib2026x'];
const STAFF = ['miloudsellam', 'AdminDemo2026'];

// [الاسم, المسار, الدخول] — {first:LIST:SELECTOR} = أول رابط في صفحة القائمة
const PAGES = [
  ['home', '/'],
  ['director', '/director-word/'],
  ['organization', '/organization/'],
  ['history', '/history/'],
  ['news', '/news/'],
  ['news-detail', '{first:/news/:a[href*="/news/"]:not([href$="/news/"])}'],
  ['activities', '/activities/'],
  ['activity-detail', '{first:/activities/:a[href*="/activities/"]:not([href$="/activities/"])}'],
  ['departements', '/departements/'],
  ['departement-detail', '{first:/departements/:a[href*="/departements/"]:not([href$="/departements/"])}'],
  ['cities', '/cities/'],
  ['cite-detail', '{first:/cities/:a[href*="/cities/"]:not([href$="/cities/"])}'],
  ['services', '/services/'],
  ['service-type', '/services/type/housing/'],
  ['service-detail', '{first:/services/:a[href*="/services/"]:not([href*="/type/"]):not([href$="/services/"])}'],
  ['contact', '/contact/'],
  ['downloads', '/downloads/'],
  ['downloads-category', '{first:/downloads/:a[href*="/downloads/category/"]}'],
  ['faq', '/faq/'],
  ['faq-detail', '/faq/3/'],
  ['search', '/search/?q=%D8%A7%D9%84%D8%A5%D9%82%D8%A7%D9%85%D8%A9'],
  ['search-empty', '/search/'],
  ['register', '/students/register/'],
  ['login', '/students/login/'],
  ['404', '/no-such-page/'],
  ['st-dashboard', '/students/dashboard/', STUDENT],
  ['st-create', '/students/applications/create/', STUDENT],
  ['st-create-type', '/students/applications/create/1/', STUDENT],
  ['st-applications', '/students/applications/', STUDENT],
  ['st-app-detail', '{first:/students/applications/:a[href*="/students/applications/"][href$="/"]:not([href*="create"]):not([href*="print"]):not([href$="/applications/"])}', STUDENT],
  ['st-app-print', '{first:/students/applications/:a[href*="/print/"]:not([href*="print-all"])}', STUDENT],
  ['st-print-all', '/students/applications/print-all/', STUDENT],
  ['st-notifications', '/students/notifications/', STUDENT],
  ['st-profile', '/students/profile/', STUDENT],
  ['st-profile-edit', '/students/profile/edit/', STUDENT],
  ['st-password', '/students/profile/change-password/', STUDENT],
  ['st-card', '/students/student-card/', STUDENT],
  ['staff-dashboard', '/students/admin/dashboard/', STAFF],
  ['staff-applications', '/students/admin/applications/', STAFF],
  ['staff-app-detail', '{first:/students/admin/applications/:a[href*="/students/admin/applications/"][href$="/"]:not([href*="export"]):not([href$="/applications/"])}', STAFF],
];

const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--font-render-hinting=none', '--lang=ar'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: fontsConf },
});
fs.mkdirSync(OUT, { recursive: true });

const FREEZE = `*, *::before, *::after { animation: none !important; transition: none !important; caret-color: transparent !important; }`;

async function newPage(ctx) {
  const page = await ctx.newPage();
  await page.setViewport({ width: W, height: 900, deviceScaleFactor: 1 });
  await page.setRequestInterception(true);
  page.on('request', (req) => {
    const u = req.url();
    if (u.startsWith(BASE) || u.startsWith('data:') || u.startsWith('blob:')) return req.continue();
    const f = cdnFile(u);
    if (f && fs.existsSync(f)) {
      return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
    }
    return req.respond({ status: 404, contentType: 'text/plain', body: '' });
  });
  page.on('pageerror', (e) => console.warn(`  خطأ JavaScript: ${e.message}`));
  page.on('dialog', (d) => d.dismiss());
  return page;
}

async function login(page, [user, pass]) {
  await page.goto(BASE + LANG + '/students/login/', { waitUntil: 'networkidle0' });
  await page.type('input[name=username]', user);
  await page.type('input[name=password]', pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form:has(input[name=password]) [type=submit]')]);
}

async function resolve(page, spec) {
  const m = spec.match(/^\{first:([^:]+):(.+)\}$/);
  if (!m) return LANG + spec;
  await page.goto(BASE + LANG + m[1], { waitUntil: 'networkidle0' });
  const href = await page.evaluate((sel) => { const a = document.querySelector(sel); return a ? a.getAttribute('href') : null; }, m[2]);
  if (!href) return null;
  return href.replace(/^https?:\/\/[^/]+/, '');
}

const contexts = new Map();
async function contextFor(cred) {
  const key = cred ? cred[0] : 'anon';
  if (!contexts.has(key)) {
    const ctx = await browser.createBrowserContext();
    if (cred) { const p = await newPage(ctx); await login(p, cred); await p.close(); }
    contexts.set(key, ctx);
  }
  return contexts.get(key);
}

let ok = 0;
for (const [name, spec, cred] of PAGES) {
  if (ONLY && !ONLY.has(name)) continue;
  const ctx = await contextFor(cred);
  const page = await newPage(ctx);
  try {
    const url = await resolve(page, spec);
    if (!url) { console.warn(`✗ ${name}: لم أجد الرابط`); continue; }
    const resp = await page.goto(BASE + url, { waitUntil: 'networkidle0' });
    await page.addStyleTag({ content: FREEZE });
    await page.evaluate(() => {
      if (window.jQuery && jQuery.fn.carousel) jQuery('.carousel').carousel('pause');
      document.querySelectorAll('.flash-messages').forEach((e) => e.remove());
    });
    await new Promise((r) => setTimeout(r, Number(args.wait || 2500)));
    const h = Math.min(MAXH, await page.evaluate(() => document.documentElement.scrollHeight));
    await page.setViewport({ width: W, height: h, deviceScaleFactor: 1 });
    await new Promise((r) => setTimeout(r, 200));
    // النافذة صارت بطول الصفحة: نلتقط النافذة نفسها (clip يزيح الصفحات العربية أفقياً)
    await page.screenshot({ path: path.join(OUT, name + '.png'), captureBeyondViewport: false });
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    console.log(`✓ ${name} ${resp.status()} ${url} ${h}px${overflow ? ' ⚠ تمرير أفقي' : ''}`);
    ok++;
  } catch (e) {
    console.warn(`✗ ${name}: ${e.message}`);
  } finally {
    await page.close();
  }
}
await browser.close();
console.log(`${ok} لقطة في ${OUT}`);
