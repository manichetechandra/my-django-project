// مسار الترشح كاملاً في متصفح حقيقي (Chromium): ملء الاستمارة + رفع وثائق PDF + الوصل + متابعة الطلب
// ثم مع الإدارة (متصفح ثانٍ منفصل): ملف ناقص ← المترشح يرفع الوثيقة الناقصة بنفسه ← قبول + استدعاء + نتيجة + نشر
// ← المترشح يرى ويطبع استدعاءه وإشعار نتيجته. (في النهاية يُلغى نشر النتائج لإبقاء المعطيات التجريبية كما هي)
//   node dev/recruitment_flow.mjs --base=http://127.0.0.1:8002 --out=/tmp/shots/flow [--lang=fr] [--admin=miloudsellam:AdminDemo2026]
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
async function shot(name, p = page) {
  await p.addStyleTag({ content: '*{animation:none!important;transition:none!important} .flash-messages{display:none!important}' });
  const h = await p.evaluate(() => document.documentElement.scrollHeight);
  await p.setViewport({ width: 1280, height: Math.min(h, 6000) });
  await p.screenshot({ path: path.join(OUT, name + '.png') });
  await p.setViewport({ width: 1280, height: 900 });
}
const go = (p, url) => p.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'networkidle0' });
const submit = (p, selector) => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click(selector)]);
const text = (p, selector) => p.$eval(selector, (e) => e.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');

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
check(await page.$('.cmp-complete') === null && await page.$('.cmp-convocation') === null, 'طلب جديد: لا استدعاء ولا نموذج إكمال');
check(!page.url().includes('tracking_code') && !(await page.content()).includes(secret), 'الرمز السري لا يظهر في الرابط ولا في الصفحة بعد الدخول');

// 6) الإدارة في متصفح منفصل (جلسة مستقلة): ملف ناقص مع رسالة
const [adminUser, adminPass] = (args.admin || 'miloudsellam:AdminDemo2026').split(':');
const adminCtx = await browser.createBrowserContext();
const admin = await adminCtx.newPage();
await admin.setViewport({ width: 1280, height: 900 });
await admin.setRequestInterception(true);
admin.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(BASE) || u.startsWith('data:')) return req.continue();
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
admin.on('pageerror', (e) => errors.push('admin: ' + e.message));
admin.on('dialog', (d) => d.accept());
await go(admin, '/admin/login/');
await admin.type('#id_username', adminUser);
await admin.type('#id_password', adminPass);
await submit(admin, 'form button[type=submit]');
await go(admin, '/admin/recruitment/applications/?q=' + encodeURIComponent(tracking));
const detailHref = await admin.$eval('a.recruit-code', (a) => a.getAttribute('href')).catch(() => '');
check(detailHref !== '', 'الإدارة تجد الطلب برقم المتابعة');
await go(admin, detailHref);
await admin.select('#r_status', 'incomplete');
check(await admin.$eval('#r_until', (i) => getComputedStyle(i.closest('.form-group')).display !== 'none' && i.value !== ''), 'خانة «آخر أجل لإكمال الملف» تظهر مع «ملف ناقص» بقيمة افتراضية');
await admin.$eval('#r_msg', (t) => { t.value = ''; });
await admin.type('#r_msg', 'ملفك ناقص: كشف النقاط غير واضح. ارفع نسخة واضحة من صفحة متابعة طلبك.');
await submit(admin, '.recruit-decision button[type=submit]');
check((await text(admin, '.recruit-status-big')).includes('ناقص'), 'الحالة الآن «ملف ناقص»');

// 7) المترشح يرفع الوثيقة الناقصة بنفسه
await go(page, `${LANG}/competitions/track/`);
check(await page.$('#cmp-complete') !== null, 'نموذج «إكمال ملفك» ظاهر للمترشح');
check((await text(page, '.cmp-message')).includes('كشف النقاط'), 'رسالة الإدارة ظاهرة');
await shot('5-track-incomplete');
const completeInputs = await page.$$('#cmp-complete input.cmp-file');
await completeInputs[completeInputs.length > 3 ? 3 : 0].uploadFile(path.join(PDFS, 'ok_scan.pdf'));
await submit(page, '#cmp-complete button[type=submit]');
check(await page.$('.alert-success') !== null && await page.$('#cmp-complete') === null, 'بعد الرفع: رسالة نجاح واختفاء النموذج');
check(await page.$('.cmp-status.is-pending') !== null, 'عاد الطلب إلى «قيد الانتظار»');
await shot('6-track-completed');

// 8) الإدارة: الوثيقة الجديدة ظاهرة ← قبول ← استدعاء + نتيجة
await go(admin, detailHref);
check(await admin.$('.recruit-late-file') !== null, 'الوثيقة المضافة ظاهرة للإدارة مع علامة «إكمال»');
await admin.select('#r_status', 'accepted');
await admin.$eval('#r_msg', (t) => { t.value = 'تهانينا، ملفك مقبول. اطبع استدعاءك من هذه الصفحة.'; });
await submit(admin, '.recruit-decision button[type=submit]');
let examValue = await admin.$eval('#r_exam option:nth-child(2)', (o) => o.value).catch(() => '');
if (!examValue) {
  // لا مواعيد في هذه المسابقة: نضيف موعداً من صفحة المسابقة (سطر جديد في «مواعيد الاختبارات» — خانة تاريخ وساعة)
  const editHref = await admin.$eval('.recruit-kv a[href*="/admin/competitions/"]', (a) => a.getAttribute('href'));
  await go(admin, editHref);
  await admin.type('#inline-exams tr.admin-inline-new input[name$="[title]"]', 'الاختبار المهني — الفوج التجريبي');
  await admin.$eval('#inline-exams tr.admin-inline-new input[type="datetime-local"]', (i) => { i.value = '2026-12-15T09:30'; });
  await admin.type('#inline-exams tr.admin-inline-new input[name$="[place]"]', 'مقر المديرية، قاعة الاجتماعات');
  await submit(admin, 'button[name=_continue]');
  const saved = await admin.$$eval('#inline-exams input[type="datetime-local"]', (l) => l.map((i) => i.value));
  check(saved.includes('2026-12-15T09:30'), 'موعد الاختبار حُفظ من صفحة المسابقة (خانة تاريخ وساعة)');
  await go(admin, detailHref);
  examValue = await admin.$eval('#r_exam option:nth-child(2)', (o) => o.value).catch(() => '');
}
check(examValue !== '', 'يوجد موعد اختبار في المسابقة');
await admin.select('#r_exam', examValue);
await admin.select('#r_result', 'passed');
await admin.type('#r_score', '15,5');
await admin.type('#r_rank', '1');
await admin.type('#r_rnote', 'يرجى الحضور إلى مصلحة المستخدمين لاستكمال ملف التوظيف.');
await submit(admin, '.recruit-outcome button[type=submit]');
const seat = await text(admin, '.recruit-outcome .help-block .recruit-code');
check(/^\d{3}$/.test(seat), 'رقم التسجيل مُنح تلقائياً: ' + seat);
await shot('7-admin-application', admin);

// قبل النشر: المترشح يرى الاستدعاء لكن ليس النتيجة
await go(page, `${LANG}/competitions/track/`);
check(await page.$('.cmp-convocation') !== null && await page.$('.cmp-result') === null, 'قبل نشر النتائج: الاستدعاء ظاهر والنتيجة مخفية');
const hidden = await page.goto(`${BASE}${LANG}/competitions/track/result/`, { waitUntil: 'networkidle0' });
check(hidden.status() === 404, 'إشعار النتيجة غير متاح قبل النشر (404)');

// 9) نشر النتائج من صفحة نتائج المسابقة
const competitionId = detailHref && await (async () => { await go(admin, detailHref); return admin.$eval('.breadcrumb a[href*="competition="]', (a) => new URL(a.href).searchParams.get('competition')); })();
await go(admin, '/admin/recruitment/results/?competition=' + competitionId);
await shot('8-admin-results', admin);
const wasPublished = await admin.$('button[value=unpublish]') !== null;
if (!wasPublished) await submit(admin, 'button[value=publish]');
check(await admin.$('.recruit-publish.is-published') !== null, 'النتائج منشورة');

// 10) المترشح: النتيجة + طباعة الاستدعاء وإشعار النتيجة
await go(page, `${LANG}/competitions/track/`);
check(await page.$('.cmp-result.is-passed') !== null, 'بطاقة النتيجة ظاهرة للمترشح');
check((await text(page, '.cmp-figures')).includes('15,50'), 'العلامة 15,50 / 20 ظاهرة');
await shot('9-track-convoked-result');
await go(page, `${LANG}/competitions/track/convocation/`);
check((await text(page, '.doc-seat')) === seat, 'الاستدعاء المطبوع يحمل رقم التسجيل ' + seat);
check((await text(page, '.doc-kv')).includes('1998') || (await text(page, '.doc-kv')).includes('20/05/1998'), 'تاريخ الميلاد (مفكوك التشفير) في استدعاء صاحبه');
await shot('10-print-convocation');
await go(page, `${LANG}/competitions/track/result/`);
check((await text(page, '.doc-result-value')) !== '', 'إشعار النتيجة: ' + await text(page, '.doc-result-value'));
await shot('11-print-result');

// 11) إغلاق صفحة الطلب ثم محاولة فتح الاستدعاء
await go(page, `${LANG}/competitions/track/`);
await submit(page, 'button[name=logout]');
check(await page.$('.cmp-track-form') !== null, 'بعد «إغلاق صفحة طلبي»: نموذج الدخول');
await go(page, `${LANG}/competitions/track/convocation/`);
check(page.url().endsWith('/competitions/track/'), 'بعد الإغلاق: رابط الاستدعاء يعيد إلى نموذج الدخول');

// إعادة المعطيات التجريبية: إلغاء النشر إن لم تكن منشورة قبل الاختبار
if (!wasPublished) {
  await go(admin, '/admin/recruitment/results/?competition=' + competitionId);
  await submit(admin, 'button[value=unpublish]');
}
await adminCtx.close();
check(errors.length === 0, 'بلا أخطاء JavaScript' + (errors.length ? ': ' + errors.join(' | ') : ''));
await browser.close();
fs.writeFileSync(path.join(OUT, 'codes.json'), JSON.stringify({ tracking, secret, nin, seat }));
console.log(failures ? `✗ ${failures} فشل` : `✓ مسار الترشح يعمل في المتصفح (${OUT})`);
process.exit(failures ? 1 : 0);
