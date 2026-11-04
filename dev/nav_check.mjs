// شريط التنقل في كل الصفحات العامة (متصفح حقيقي Chromium):
//   1) رابط «الإقامات الجامعية» يعمل من أي صفحة: ينقل إلى قسم الإقامات في الصفحة الرئيسية (/#cite) بنفس اللغة،
//      وعنوان القسم يظهر تحت الشريط الثابت (sticky) لا خلفه — على الحاسوب وعلى الهاتف (القائمة المطوية)
//      (كان الرابط "#cite" نسبياً: في صفحة المسابقات يصير /competitions/#cite ولا يحدث شيء)
//   2) العنصر المميَّز (active) في الشريط هو قسم الصفحة الحالية فقط (كانت «الرئيسية» مميَّزة في كل الصفحات)
//   3) لا روابط "#..." نسبية في الشريط غير أزرار القوائم المنسدلة
//   node dev/nav_check.mjs [--base=http://127.0.0.1:8002] [--lang=ar|fr|en|all] [--out=/tmp/shots/nav]
//   (موقع داخل مجلد فرعي مثل XAMPP: --base=http://127.0.0.1:8004/dou-setif)
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const require = createRequire(path.join(ROOT, 'book', 'tools', 'shots.mjs'));
const puppeteer = require('puppeteer-core');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const BASE = (args.base || 'http://127.0.0.1:8002').replace(/\/$/, '');
const BASE_PATH = new URL(BASE).pathname.replace(/\/$/, ''); // '' أو '/dou-setif'
const LANGS = !args.lang || args.lang === 'all' ? ['ar', 'fr', 'en'] : args.lang.split(',');
const OUT = args.out || '';
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
if (OUT) fs.mkdirSync(OUT, { recursive: true });

// مكتبات CDN من نسخة محلية (حزم npm في book/.build/cdn، تُنزَّل عند أول حاجة)
function pkg(spec) {
  const at = spec.lastIndexOf('@');
  const name = spec.slice(0, at);
  const dir = path.join(CDN, spec);
  if (!fs.existsSync(path.join(dir, 'package.json'))) {
    fs.mkdirSync(dir, { recursive: true });
    const url = `https://registry.npmjs.org/${name}/-/${name.split('/').pop()}-${spec.slice(at + 1)}.tgz`;
    execFileSync('bash', ['-c', `curl -sfL "${url}" | tar xz -C "${dir}" --strip-components=1`]);
  }
  return dir;
}
function cdnFile(url) {
  const u = url.split('?')[0];
  let m;
  if ((m = u.match(/^https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@3\.4\.1\/(.+)$/))) return path.join(pkg('bootstrap@3.4.1'), m[1]);
  if (/^https:\/\/code\.jquery\.com\/jquery-3\.6\.0(\.min)?\.js$/.test(u)) return path.join(pkg('jquery@3.6.0'), 'dist/jquery.min.js');
  if ((m = u.match(/^https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome\/4\.7\.0\/(.+)$/))) return path.join(pkg('font-awesome@4.7.0'), m[1]);
  if (/^https:\/\/cdn\.jsdelivr\.net\/npm\/chart\.js@4\.4\.0\/dist\/chart\.umd(\.min)?\.js$/.test(u)) return path.join(pkg('chart.js@4.4.0'), 'dist/chart.umd.js');
  return null;
}
const MIME = { '.css': 'text/css', '.js': 'application/javascript', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf' };

// [المسار، أيقونة عنصر الشريط الذي يجب أن يكون مميَّزاً (null = لا شيء)، الدخول]
// {first:القائمة:الرابط} = أول رابط تفاصيل في صفحة القائمة (خارج الشريط)
const STUDENT = ['1234566', 'Talib2026x', '/students/login/'];
const STAFF = ['miloudsellam', 'AdminDemo2026', '/admin/login/'];
const PAGES = [
  ['/', 'fa-home'],
  ['/director-word/', 'fa-building'],
  ['/organization/', 'fa-building'],
  ['/history/', 'fa-building'],
  ['/cities/', 'fa-university'],
  ['{first:/cities/:/cities/}', 'fa-university'],
  ['/news/', 'fa-newspaper-o'],
  ['{first:/news/:/news/}', 'fa-newspaper-o'],
  ['/competitions/', 'fa-trophy'],
  ['/competitions/?tab=closed', 'fa-trophy'],
  ['{first:/competitions/:/competitions/}', 'fa-trophy'],
  ['/competitions/track/', 'fa-trophy'],
  ['/services/', 'fa-cogs'],
  ['/services/type/housing/', 'fa-cogs'],
  ['/downloads/', 'fa-download'],
  ['/faq/', 'fa-question-circle'],
  ['/faq/3/', 'fa-question-circle'],
  ['/contact/', 'fa-envelope'],
  ['/students/login/', 'fa-sign-in'],
  ['/students/register/', 'fa-sign-in'],
  ['/search/?q=%D8%B3%D8%B7%D9%8A%D9%81', null],
  ['/activities/', null],
  ['/departements/', null],
  ['/no-such-page/', null],
  ['/students/dashboard/', 'fa-user-circle', STUDENT],
  ['/students/applications/', 'fa-user-circle', STUDENT],
  ['/news/', 'fa-newspaper-o', STUDENT],
  ['/students/admin/dashboard/', 'fa-shield', STAFF],
  ['/competitions/', 'fa-trophy', STAFF],
];
// الهاتف: القائمة مطوية خلف زر ☰
const MOBILE = ['/competitions/', '/news/', '/'];

const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--lang=ar'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: fs.existsSync('/tmp/devshots-fonts.conf') ? '/tmp/devshots-fonts.conf' : `${BT}/fonts.conf` },
});

async function newPage(ctx, width = 1366, height = 800) {
  const page = await ctx.newPage();
  await page.setViewport({ width, height });
  await page.setRequestInterception(true);
  page.on('request', (req) => {
    const u = req.url();
    if (u.startsWith(BASE.replace(BASE_PATH, '')) || u.startsWith('data:')) return req.continue();
    const f = cdnFile(u);
    if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
    return req.respond({ status: 404, body: '' });
  });
  page.on('pageerror', (e) => { failures++; console.log(`  ✗ خطأ JavaScript في ${page.url()}: ${e.message}`); });
  page.on('dialog', (d) => d.dismiss());
  return page;
}

let failures = 0;
let passed = 0;
const check = (cond, label) => { if (cond) passed++; else { failures++; console.log('  ✗ ' + label); } return cond; };
const go = (page, url) => page.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'networkidle0' });

const contexts = new Map();
async function contextFor(cred) {
  const key = cred ? cred[0] : 'anon';
  if (!contexts.has(key)) {
    const ctx = await browser.createBrowserContext();
    if (cred) {
      const p = await newPage(ctx);
      await go(p, cred[2]);
      await p.type('input[name=username]', cred[0]);
      await p.type('input[name=password]', cred[1]);
      await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click('form:has(input[name=password]) [type=submit]')]);
      await p.close();
    }
    contexts.set(key, ctx);
  }
  return contexts.get(key);
}

async function resolve(page, spec, prefix) {
  const m = spec.match(/^\{first:([^:]+):(.+)\}$/);
  if (!m) return prefix + spec;
  await go(page, prefix + m[1]);
  const href = await page.evaluate((list, part) => {
    const a = [...document.querySelectorAll('a[href]')].find((x) => !x.closest('nav') && x.pathname.includes(part)
      && !x.pathname.endsWith(part) && !/\/(apply|track|type|category|file|receipt)\//.test(x.pathname));
    return a ? a.pathname + a.search : null;
  }, m[1], m[2]);
  return href ? href.slice(BASE_PATH.length) : null;
}

// موضع عنوان قسم الإقامات بالنسبة للشريط الثابت
const citeState = (page) => page.evaluate(() => {
  const nav = document.querySelector('.navbar-fixed-top').getBoundingClientRect();
  const h = document.querySelector('#cite h3');
  const r = h ? h.getBoundingClientRect() : null;
  return {
    path: location.pathname, hash: location.hash, navBottom: Math.round(nav.bottom),
    headTop: r ? Math.round(r.top) : null, headBottom: r ? Math.round(r.bottom) : null, vh: innerHeight,
    menuOpen: !!document.querySelector('#myNavbar.in, #myNavbar.collapsing'),
  };
});

async function clickResidences(page, mobile) {
  const before = await page.evaluate(() => location.pathname + location.search);
  if (mobile) {
    await page.click('.navbar-toggle');
    await page.waitForSelector('#myNavbar.in', { timeout: 3000 });
    await new Promise((r) => setTimeout(r, 400));
  }
  const link = await page.$('#myNavbar a[href$="#cite"]');
  if (!check(link, 'رابط «الإقامات الجامعية» موجود في الشريط')) return null;
  const target = await page.evaluate((a) => a.pathname + a.search, link);
  if (target === before) {
    await link.click(); // نفس الصفحة: انتقال داخلي بلا تحميل
    await new Promise((r) => setTimeout(r, 900));
  } else {
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), link.click()]);
    await new Promise((r) => setTimeout(r, 300));
  }
  return citeState(page);
}

for (const lang of LANGS) {
  const prefix = lang === 'ar' ? '' : '/' + lang;
  const home = BASE_PATH + prefix + '/';
  console.log(`— ${lang.toUpperCase()} (${home})`);
  let n = 0;
  for (const [spec, icon, cred] of PAGES) {
    const ctx = await contextFor(cred);
    const page = await newPage(ctx);
    try {
      const url = await resolve(page, spec, prefix);
      if (!check(url, `${spec}: لم أجد رابط التفاصيل`)) continue;
      await go(page, url);
      const who = cred ? ` [${cred[0]}]` : '';
      const nav = await page.evaluate(() => ({
        active: [...document.querySelectorAll('#myNavbar > ul.navbar-nav > li.active > a > i.fa')]
          .map((i) => [...i.classList].find((c) => c !== 'fa')),
        relative: [...document.querySelectorAll('#myNavbar a[href^="#"]:not([data-toggle])')].map((a) => a.getAttribute('href')),
      }));
      check(JSON.stringify(nav.active) === JSON.stringify(icon ? [icon] : []),
        `${url}${who}: العنصر المميَّز ${JSON.stringify(nav.active)} والمنتظر ${JSON.stringify(icon ? [icon] : [])}`);
      check(nav.relative.length === 0, `${url}${who}: روابط نسبية في الشريط ${JSON.stringify(nav.relative)}`);
      const s = await clickResidences(page, false);
      if (s) {
        check(s.path === home && s.hash === '#cite', `${url}${who}: «الإقامات الجامعية» نقل إلى ${s.path}${s.hash} بدل ${home}#cite`);
        check(s.headTop !== null && s.headTop >= s.navBottom && s.headBottom <= s.vh,
          `${url}${who}: عنوان قسم الإقامات غير ظاهر (أعلاه ${s.headTop}، أسفل الشريط ${s.navBottom})`);
        if (OUT && n < 3) await page.screenshot({ path: path.join(OUT, `${lang}-from-${n}.png`) });
      }
      n++;
    } catch (e) {
      check(false, `${spec}: ${e.message}`);
    } finally {
      await page.close();
    }
  }
  // الهاتف (375×740): القائمة المطوية
  const ctx = await contextFor(null);
  for (const p of MOBILE) {
    const page = await newPage(ctx, 375, 740);
    try {
      await go(page, prefix + p);
      const s = await clickResidences(page, true);
      if (s) {
        check(s.path === home && s.hash === '#cite', `الهاتف ${prefix + p}: نقل إلى ${s.path}${s.hash} بدل ${home}#cite`);
        check(!s.menuOpen, `الهاتف ${prefix + p}: القائمة بقيت مفتوحة فوق القسم`);
        check(s.headTop !== null && s.headTop >= s.navBottom && s.headBottom <= s.vh,
          `الهاتف ${prefix + p}: عنوان قسم الإقامات غير ظاهر (أعلاه ${s.headTop}، أسفل الشريط ${s.navBottom})`);
        if (OUT) await page.screenshot({ path: path.join(OUT, `${lang}-mobile${p.replace(/\W+/g, '-')}.png`) });
      }
    } catch (e) {
      check(false, `الهاتف ${p}: ${e.message}`);
    } finally {
      await page.close();
    }
  }
}
await browser.close();
console.log(failures ? `✗ ${failures} فحص فاشل (${passed} ناجح)` : `✓ شريط التنقل: ${passed} فحصاً ناجحاً`);
process.exit(failures ? 1 : 0);
