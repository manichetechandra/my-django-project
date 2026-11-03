// مسار الترشح كاملاً في متصفح حقيقي (Chromium): ملء الاستمارة + رفع وثائق PDF + الوصل + متابعة الطلب
//   node dev/recruitment_flow.mjs --base=http://127.0.0.1:8002 --out=/tmp/shots/flow [--lang=fr]
// يحتاج أدوات الكتاب (cd book && bash tools/setup.sh) وملفات PDF التجريبية: python3 dev/pdf_samples.py /tmp/pdfs
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
const OUT = args.out || '/tmp/shots/flow';
const LANG = args.lang && args.lang !== 'ar' ? '/' + args.lang : '';
const PDFS = args.pdfs || '/tmp/pdfs';
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
fs.mkdirSync(OUT, { recursive: true });
if (!fs.existsSync(path.join(PDFS, 'ok_simple.pdf'))) execFileSync('python3', [path.join(ROOT, 'dev', 'pdf_samples.py'), PDFS]);

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
await page.setViewport({ width: 1280, height: 900 });
await page.setRequestInterception(true);
page.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(BASE) || u.startsWith('data:')) return req.continue();
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));
page.on('dialog', (d) => d.dismiss());
let failures = 0;
const check = (cond, label) => { console.log((cond ? '  ✓ ' : '  ✗ ') + label); if (!cond) failures++; };
async function shot(name) {
  await page.addStyleTag({ content: '*{animation:none!important;transition:none!important} .flash-messages{display:none!important}' });
  const h = await page.evaluate(() => document.documentElement.scrollHeight);
  await page.setViewport({ width: 1280, height: Math.min(h, 6000) });
  await page.screenshot({ path: path.join(OUT, name + '.png') });
  await page.setViewport({ width: 1280, height: 900 });
}

// 1) من قائمة المسابقات إلى الاستمارة
await page.goto(`${BASE}${LANG}/competitions/`, { waitUntil: 'networkidle0' });
const applyHref = await page.$eval('a[href*="/apply/"]', (a) => a.getAttribute('href'));
await page.goto(BASE + applyHref, { waitUntil: 'networkidle0' });
check(await page.$('#cmp-apply') !== null, 'الاستمارة ظاهرة: ' + applyHref);

// 2) فحص الملفات في المتصفح قبل الرفع
const firstFile = await page.$('input.cmp-file');
await firstFile.uploadFile(path.join(PDFS, 'bad_png_renamed.pdf'));
await firstFile.uploadFile(path.join(PDFS, 'ok_simple.pdf'));
const stateOk = await page.$eval('input.cmp-file', (i) => i.parentNode.querySelector('.cmp-file-state').textContent);
check(stateOk.includes('ok_simple.pdf'), 'اختيار ملف PDF يعرض اسمه وحجمه: ' + stateOk.trim());

// 3) ملء الاستمارة
const nin = '1' + String(Date.now()).slice(-12).padStart(12, '0') + '00042';
await page.select('#id_post_id', await page.$eval('#id_post_id option:nth-child(2)', (o) => o.value));
for (const [id, value] of [['last_name', 'بوعلام'], ['first_name', 'خديجة'], ['last_name_latin', 'Boualem'], ['first_name_latin', 'khadidja'],
  ['birth_place', 'سطيف'], ['nin', nin], ['phone', '0661002233'], ['email', 'khadidja@example.dz'], ['address', 'حي المعبودة، عمارة 4'],
  ['wilaya', 'سطيف'], ['diploma', 'ليسانس في الحقوق'], ['specialty', 'قانون عام'], ['graduation_year', '2020']]) {
  await page.type('#id_' + id, value);
}
await page.select('#id_gender', 'F');
await page.select('#id_national_service', 'na');
await page.$eval('#id_birth_date', (el) => { el.value = '1998-05-20'; });
const fileInputs = await page.$$('input.cmp-file');
for (const [k, input] of fileInputs.entries()) await input.uploadFile(path.join(PDFS, k === 1 ? 'ok_scan.pdf' : 'ok_simple.pdf'));
await page.click('#id_declaration');
await page.click('#id_consent');
check(await page.$eval('#website_url', (i) => i.value === ''), 'حقل الفخ يبقى فارغاً في المتصفح العادي');
await shot('1-apply-filled');
await new Promise((r) => setTimeout(r, 3200)); // رمز الوقت: 3 ثوانٍ على الأقل منذ فتح الاستمارة

// 4) الإرسال ← الوصل
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#cmp-apply button[type=submit]')]);
check(page.url().includes('/competitions/receipt/'), 'بعد الإرسال: صفحة الوصل ' + page.url().replace(BASE, ''));
const tracking = await page.$eval('#cmp-tracking', (e) => e.textContent.trim()).catch(() => '');
const secret = await page.$eval('#cmp-secret', (e) => e.textContent.trim()).catch(() => '');
check(/^C\d{2}-[2-9A-HJKMNP-Z]{8}$/.test(tracking) && /^[2-9A-HJKMNP-Z]{5}-[2-9A-HJKMNP-Z]{5}$/.test(secret), `رقم المتابعة ${tracking} والرمز السري ${secret}`);
check(await page.$('img.cmp-barcode') !== null, 'باركود رقم المتابعة في الوصل');
await shot('2-receipt');

// 5) متابعة الطلب
await page.goto(`${BASE}${LANG}/competitions/track/`, { waitUntil: 'networkidle0' });
await page.type('#tracking_code', tracking.toLowerCase());
await page.type('#secret', secret);
await shot('3-track-form');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('.cmp-track-form button[type=submit]')]);
const status = await page.$eval('.cmp-status h3', (e) => e.textContent.trim()).catch(() => '');
check(status !== '', 'صفحة المتابعة تعرض الحالة: ' + status);
await shot('4-track-result');
check(errors.length === 0, 'بلا أخطاء JavaScript' + (errors.length ? ': ' + errors.join(' | ') : ''));
await browser.close();
fs.writeFileSync(path.join(OUT, 'codes.json'), JSON.stringify({ tracking, secret, nin }));
console.log(failures ? `✗ ${failures} فشل` : `✓ مسار الترشح يعمل في المتصفح (${OUT})`);
process.exit(failures ? 1 : 0);
