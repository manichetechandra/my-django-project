// اختبار الترجمة في لوحة الإدارة من المتصفح: زر «ترجمة تلقائية»، الترجمة عند الحفظ، تعليقات الصور،
// صفحة «ترجمة المحتوى»، ثم ظهور الترجمة في /fr/ و /en/.
//   python3 dev/mock_translate.py &        (خادم Gemini/MyMemory وهمي)
//   الموقع مشغّل بـ: TRANSLATE_PROVIDER=gemini GEMINI_API_KEY=dev-key GEMINI_ENDPOINT=http://127.0.0.1:8999/v1beta MYMEMORY_ENDPOINT=http://127.0.0.1:8999/get
//   node dev/admin_i18n.mjs --base=http://127.0.0.1:8002 --out=/tmp/shots/admin-i18n
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
const OUT = args.out || '/tmp/shots/admin-i18n';
const SITE = args.site || '/tmp/site2';
const CDN = path.join(ROOT, 'book', '.build', 'cdn');
fs.mkdirSync(OUT, { recursive: true });

const sql = (q) => execFileSync('/tmp/tools/bin/php', [path.join(ROOT, 'dev', 'sql.php'), q], { env: { ...process.env, DB_NAME: process.env.DB_NAME || 'dou_setif2' } }).toString().trim();
let failures = 0;
function check(cond, msg) {
  console.log(`${cond ? '✓' : '✗'} ${msg}`);
  if (!cond) failures++;
}

// مكتبات CDN من نسخ محلية (لا إنترنت في بيئة التطوير) — نفس ما يفعله dev/pages.mjs
function cdnFile(u) {
  let m;
  u = u.split('?')[0];
  if ((m = u.match(/^https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@3\.4\.1\/(.+)$/))) return path.join(CDN, 'bootstrap@3.4.1', m[1]);
  if (/^https:\/\/code\.jquery\.com\/jquery-3\.6\.0(\.min)?\.js$/.test(u)) return path.join(CDN, 'jquery@3.6.0', 'dist/jquery.min.js');
  if ((m = u.match(/^https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome\/4\.7\.0\/(.+)$/))) return path.join(CDN, 'font-awesome@4.7.0', m[1]);
  if (/^https:\/\/cdn\.jsdelivr\.net\/npm\/chart\.js@4\.4\.0\/dist\/chart\.umd(\.min)?\.js$/.test(u)) return path.join(CDN, 'chart.js@4.4.0', 'dist/chart.umd.js');
  return null;
}
const MIME = { '.css': 'text/css', '.js': 'application/javascript', '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf' };

const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--lang=ar'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, ...(fs.existsSync('/tmp/devshots-fonts.conf') ? { FONTCONFIG_FILE: '/tmp/devshots-fonts.conf' } : {}) },
});
const page = await browser.newPage();
await page.setViewport({ width: 1366, height: 900 });
await page.setRequestInterception(true);
page.on('request', (req) => {
  const u = req.url();
  if (u.startsWith(BASE) || u.startsWith('data:')) return req.continue();
  const f = cdnFile(u);
  if (f && fs.existsSync(f)) return req.respond({ status: 200, contentType: MIME[path.extname(f)] || 'application/octet-stream', headers: { 'Access-Control-Allow-Origin': '*' }, body: fs.readFileSync(f) });
  return req.respond({ status: 404, body: '' });
});
page.on('pageerror', (e) => { console.log('✗ خطأ JavaScript: ' + e.message); failures++; });
let confirmAnswer = true;
page.on('dialog', (d) => (d.type() === 'confirm' ? (confirmAnswer ? d.accept() : d.dismiss()) : d.dismiss()));
const shot = async (name) => {
  const h = await page.evaluate(() => document.documentElement.scrollHeight);
  await page.setViewport({ width: 1366, height: Math.min(h, 4000) });
  await page.screenshot({ path: path.join(OUT, name + '.png'), captureBeyondViewport: false });
  await page.setViewport({ width: 1366, height: 900 });
};
const go = (p) => page.goto(BASE + p, { waitUntil: 'networkidle0' });
const val = (sel) => page.$eval(sel, (e) => e.value);
const flash = () => page.$$eval('.alert', (els) => els.map((e) => e.textContent.replace(/\s+/g, ' ').trim()).join(' || '));

// ---------------------------------------------------------------- الدخول
await go('/students/login/');
await page.type('input[name=username]', 'miloudsellam');
await page.type('input[name=password]', 'AdminDemo2026');
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form:has(input[name=password]) [type=submit]')]);

// ---------------------------------------------------------------- 1) زر «ترجمة تلقائية» في إضافة خبر
await go('/admin/news/add/');
check(await page.$('[data-auto-translate]') !== null, 'زر «ترجمة تلقائية» موجود في إضافة خبر');
check((await page.$$('[data-tr-of]')).length === 6, 'ستة حقول ترجمة (العنوان والملخص والمحتوى × FR/EN)');
await page.click('[data-auto-translate]');
check((await page.$eval('[data-tr-status]', (e) => e.textContent)).includes('اكتب النص العربي'), 'بلا نص عربي: رسالة «اكتب النص العربي أولاً»');
await page.type('#f_title', 'صرف المنحة للطلبة');
await page.type('#f_summary', 'خبر تجريبي من سطيف');
await page.type('#f_content', 'خبر تجريبي\nالإقامة الجامعية في سطيف');
await page.click('[data-auto-translate]');
await page.waitForFunction(() => /✓|✗/.test(document.querySelector('[data-tr-status]').textContent), { timeout: 15000 });
const status1 = await page.$eval('[data-tr-status]', (e) => e.textContent);
check(status1.includes('✓') && status1.includes('gemini'), 'الزر ترجم عبر gemini: ' + status1.trim());
check(await val('[name=title_fr]') === 'Versement de la bourse للطلبة', 'title_fr = ' + await val('[name=title_fr]'));
check(await val('[name=title_en]') === 'Scholarship payment للطلبة', 'title_en = ' + await val('[name=title_en]'));
check((await val('[name=content_en]')).includes('\n'), 'أسطر المحتوى محفوظة في الترجمة');
await shot('1-add-translated');

// تعديل الترجمة يدوياً ثم الضغط مرة أخرى مع «إلغاء» = لا نستبدل الموجود
await page.$eval('[name=title_fr]', (e) => { e.value = 'Titre corrigé à la main'; });
await page.$eval('[name=summary_en]', (e) => { e.value = ''; });
confirmAnswer = false;
await page.$eval('[data-tr-status]', (e) => { e.textContent = ''; });
await page.click('[data-auto-translate]');
await page.waitForFunction(() => /✓|✗/.test(document.querySelector('[data-tr-status]').textContent), { timeout: 15000 });
check(await val('[name=title_fr]') === 'Titre corrigé à la main', '«إلغاء» في التأكيد يحافظ على الترجمة اليدوية');
check(await val('[name=summary_en]') === 'news test من Sétif', '… ويملأ الحقل الفارغ فقط');
confirmAnswer = true;

// ---------------------------------------------------------------- 2) الحفظ: ترجمة الحقول الفارغة تلقائياً
await page.$eval('[name=content_fr]', (e) => { e.value = ''; });
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[name=_continue]')]);
const f2 = await flash();
check(f2.includes('الفارغة (1)'), 'الحفظ ترجم الحقل الفارغ الوحيد: ' + f2);
const row = sql("SELECT id, slug, title_fr, title_en, content_fr FROM news WHERE title = 'صرف المنحة للطلبة' ORDER BY id DESC LIMIT 1");
console.log('  ' + row);
const [newsId, slug] = row.split(' | ');
check(row.includes('Titre corrigé à la main') && row.includes('actualité de test'), 'قاعدة البيانات: الترجمة اليدوية + المترجمة عند الحفظ');

// ---------------------------------------------------------------- 3) صفحة التعديل: القيم معبأة + تنبيه تغيّر النص العربي
check(await val('[name=title_fr]') === 'Titre corrigé à la main', 'صفحة التعديل تعرض الترجمة المحفوظة');
await page.type('#f_title', ' الجديدة');
check(await page.$eval('[name=title_fr]', (e) => e.classList.contains('admin-tr-stale')), 'تعديل العنوان العربي يلوّن حقول ترجمته (admin-tr-stale)');
await shot('2-edit-stale');

// ---------------------------------------------------------------- 4) الموقع بالفرنسية والإنجليزية
await go('/fr/news/' + slug + '/');
check((await page.$eval('h1', (e) => e.textContent)).includes('Titre corrigé à la main'), '/fr/news/' + slug + '/ يعرض العنوان الفرنسي');
await go('/en/news/' + slug + '/');
check((await page.$eval('h1', (e) => e.textContent)).includes('Scholarship payment'), '/en/news/' + slug + '/ يعرض العنوان الإنجليزي');

// ---------------------------------------------------------------- 5) تعليقات الصور المرفقة
const img = sql(`SELECT news_id, id FROM news_images ORDER BY id LIMIT 1`);
if (img) {
  const [nid, iid] = img.split(' | ');
  sql(`UPDATE news_images SET caption = 'صورة من الإقامة', caption_fr = NULL, caption_en = NULL WHERE id = ${iid}`);
  await go(`/admin/news/${nid}/`);
  check(await page.$(`[name="inline[0][${iid}][caption_fr]"]`) !== null, 'حقول FR/EN لتعليق الصورة المرفقة');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[name=_continue]')]);
  const cap = sql(`SELECT caption_fr, caption_en FROM news_images WHERE id = ${iid}`);
  check(cap === 'صورة من la résidence | صورة من the residence', 'تعليق الصورة تُرجم عند الحفظ: ' + cap);
} else {
  console.log('- لا صور مرفقة في الأخبار: تخطيت اختبار التعليقات');
}

// ---------------------------------------------------------------- 6) صفحة «ترجمة المحتوى» (المحتوى القديم)
sql("UPDATE faqs SET question_fr = NULL, answer_en = NULL WHERE id IN (1, 2)");
sql("UPDATE services SET title_en = '' WHERE id = 1");
await go('/admin/');
check((await page.content()).includes('ترجمة المحتوى تلقائياً'), 'لوحة الإدارة تعرض رابط ترجمة المحتوى الناقص');
await go('/admin/translate-all/');
await shot('3-translate-all-before');
const before = await page.$eval('[data-remaining]', (e) => e.textContent);
check(Number(before) >= 3, 'سجلات تنقصها ترجمة: ' + before);
await page.click('#translate-all-form [type=submit]');
await page.waitForFunction(() => /✓ انتهت|✗/.test(document.querySelector('[data-status]').textContent), { timeout: 60000 });
const st = await page.$eval('[data-status]', (e) => e.textContent);
check(st.includes('✓ انتهت'), 'الترجمة على دفعات: ' + st.trim());
await shot('4-translate-all-done');
check(sql("SELECT COUNT(*) FROM faqs WHERE question_fr IS NULL OR answer_en IS NULL") === '0', 'الأسئلة الشائعة صارت مترجمة');
check(sql("SELECT title_en FROM services WHERE id = 1") !== '', 'الخدمة صارت مترجمة: ' + sql("SELECT title_en FROM services WHERE id = 1"));
check(sql("SELECT question_fr FROM faqs WHERE id = 1").length > 0, 'faq#1 FR: ' + sql("SELECT question_fr FROM faqs WHERE id = 1"));
await go('/admin/translate-all/');
check((await page.content()).includes('كل المحتوى مترجم'), 'بعد الانتهاء: «كل المحتوى مترجم»');

// ---------------------------------------------------------------- 7) المشرف (غير المدير العام) لا يدخل صفحة الترجمة الجماعية
const ctx = await browser.createBrowserContext();
const p2 = await ctx.newPage();
await p2.setRequestInterception(true);
p2.on('request', (req) => (req.url().startsWith(BASE) ? req.continue() : req.respond({ status: 404, body: '' })));
await p2.goto(BASE + '/students/login/', { waitUntil: 'networkidle0' });
await p2.type('input[name=username]', 'hicham41');
await p2.type('input[name=password]', 'StaffDemo2026');
await Promise.all([p2.waitForNavigation({ waitUntil: 'networkidle0' }), p2.click('form:has(input[name=password]) [type=submit]')]);
const r403 = await p2.goto(BASE + '/admin/translate-all/', { waitUntil: 'networkidle0' });
check(r403.status() === 403, 'المشرف العادي: /admin/translate-all/ ← ' + r403.status());

await browser.close();
console.log(failures ? `✗ ${failures} فشل` : '✓ كل الاختبارات نجحت');
process.exit(failures ? 1 : 0);
