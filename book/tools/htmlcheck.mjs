#!/usr/bin/env node
// =====================================================================
//  فحص بنية الكتاب بعد البناء (.build/final.html) — أخطاء لا يراها فاحص الإحالات:
//   1. عدد البنود <li> والجداول <table> وصفوفها <tr> يساوي عددها في مصادر الفصول
//      (بند مرقّم يلي فقرة دون سطر فارغ يصير تكملة لها، فينقص العدد).
//   2. لا يبقى في النص المطبوع رمز Markdown حرفي: ** أو ` أو ::: أو ](...) أو # أو @file...
//   3. لا كود داخل النص يخرج عن سطره (الكود لا ينكسر: white-space: nowrap)، ولا عنصر أعرض من مكانه.
//   4. لا سهم «←» ولا علامة «» بين كلمتين لاتينيتين: تأخذ اتجاه اللاتينية فتنقلب (الحل: <bdi> أو كلمة عربية بينهما).
//  الاستعمال: node tools/build.mjs ثم node tools/htmlcheck.mjs  (يخرج بالرمز 1 إن وجد مشكلة)
// =====================================================================
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import puppeteer from 'puppeteer-core';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const HTML = process.env.HTMLCHECK_FILE || path.join(BOOK, '.build', 'final.html');
if (!fs.existsSync(HTML)) { console.error('ابنِ الكتاب أولاً: node tools/build.mjs'); process.exit(2); }

// ---------------------------------------------------------------- 1. المصادر
let srcLi = 0, srcTables = 0, srcRows = 0;
for (const f of fs.readdirSync(path.join(BOOK, 'chapters')).filter((x) => x.endsWith('.md')).sort()) {
  let fence = false;
  for (const l of fs.readFileSync(path.join(BOOK, 'chapters', f), 'utf8').split('\n')) {
    if (l.trimStart().startsWith('```')) { fence = !fence; continue; }
    if (fence) continue;
    if (/^\s*(\d+[.)]|[-*+]) /.test(l)) srcLi++;
    if (/^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(l)) srcTables++;
    if (l.trimStart().startsWith('|')) srcRows++;
  }
}

// ---------------------------------------------------------------- 2 و3. في المتصفح
const browser = await puppeteer.launch({
  executablePath: path.join(BT, 'rt', 'chromium'),
  headless: 'shell',
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--font-render-hinting=none', '--allow-file-access-from-files'],
  env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: path.join(BT, 'fonts.conf') },
  protocolTimeout: 900000,
});
let r;
try {
  const page = await browser.newPage();
  await page.emulateMediaType('print');
  await page.setViewport({ width: 650, height: 1000 }); // عرض A4 ناقص الهامشين (172 مم)
  await page.goto(pathToFileURL(HTML).href, { waitUntil: 'load', timeout: 0 });
  await page.evaluate(() => document.fonts.ready);
  r = await page.evaluate(() => {
    const heads = [...document.querySelectorAll('h1,h2,h3')];
    const where = (el) => { let h = ''; for (const x of heads) { if (x.compareDocumentPosition(el) & Node.DOCUMENT_POSITION_FOLLOWING) h = x.textContent.trim(); else break; } return h.slice(0, 60); };
    const skip = (n) => n.parentElement && n.parentElement.closest('.code, pre, code, script, style, svg, .toc, .moved-edges');
    // النص المطبوع خارج الكود
    const literal = [];
    const pats = [[/\*\*/, '**'], [/`/, '`'], [/:::/, ':::'], [/\]\(/, ']('], [/^\s*#{1,6} /m, '#'], [/(^|\n)\s*@(file|stage|lang|shot|moved)\b/, '@'], [/\uFFFD/, 'U+FFFD']];
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (skip(n)) continue;
      for (const [re, name] of pats) if (re.test(n.data)) literal.push({ name, text: n.data.trim().slice(0, 100), at: where(n.parentElement) });
    }
    // الكود داخل النص لا ينكسر: هل يخرج عن مكانه؟
    const overflow = [];
    const isBlock = (el) => { const d = getComputedStyle(el).display; return !d.startsWith('inline') && d !== 'contents'; };
    for (const c of document.querySelectorAll('code')) {
      if (c.closest('.code, pre, .moved-edges, .toc')) continue;
      let b = c.parentElement;
      while (b && !isBlock(b)) b = b.parentElement;
      if (!b) continue;
      const cs = getComputedStyle(b), br = b.getBoundingClientRect();
      const left = br.left + parseFloat(cs.paddingLeft) + parseFloat(cs.borderLeftWidth);
      const right = br.right - parseFloat(cs.paddingRight) - parseFloat(cs.borderRightWidth);
      if ([...c.getClientRects()].some((x) => x.left < left - 1.5 || x.right > right + 1.5)) overflow.push({ text: c.textContent.slice(0, 100), at: where(c) });
    }
    for (const el of document.querySelectorAll('p, li, td, th, h1, h2, h3, h4, figcaption, .box, .code-head, blockquote')) {
      if (el.closest('.toc')) continue;
      if (el.scrollWidth > el.clientWidth + 1 && !el.querySelector('p, li, .box')) overflow.push({ text: el.textContent.trim().slice(0, 100), at: where(el) });
    }
    // اتجاه النص: سهم أو علامة تنصيص «» بين كلمتين لاتينيتين (بلا حرف عربي بينهما) تأخذ اتجاه اللاتينية،
    // فينقلب السهم وتتبعثر العلامات. الكود و<bdi> معزولان: يُحسبان علامة محايدة.
    const bidi = [];
    const ISO = (el) => ['CODE', 'KBD', 'BDI'].includes(el.tagName) || el.classList.contains('ltr') || el.getAttribute('dir') === 'ltr';
    const BLOCK = (el) => !getComputedStyle(el).display.startsWith('inline');
    const own = (el) => {
      let t = '';
      for (const n of el.childNodes) {
        if (n.nodeType === 3) t += n.data;
        else if (n.nodeType === 1) t += ISO(n) ? '\uFFFC' : (BLOCK(n) ? '\u2029' : own(n));
      }
      return t;
    };
    const isL = (c) => /[A-Za-z\u00C0-\u00FF]/.test(c);
    const isR = (c) => /[\u0621-\u064A\u066E-\u06D3]/.test(c);
    const side = (t, i, step) => { for (let j = i + step; j >= 0 && j < t.length; j += step) { if (t[j] === '\u2029') return 'E'; if (isL(t[j])) return 'L'; if (isR(t[j])) return 'R'; } return 'E'; };
    for (const el of document.querySelectorAll('p, li, td, th, h1, h2, h3, h4, figcaption, dt, dd')) {
      if (el.closest('.code, pre, .toc')) continue;
      const t = own(el);
      for (let i = 0; i < t.length; i++) {
        if ('←→«»'.includes(t[i]) && side(t, i, -1) === 'L' && side(t, i, 1) === 'L') { bidi.push({ text: t.slice(Math.max(0, i - 40), i + 40).replace(/\uFFFC/g, '▢'), at: where(el) }); break; }
      }
    }
    return {
      bidi,
      li: document.querySelectorAll('li').length - document.querySelectorAll('.toc li').length,
      tables: document.querySelectorAll('table').length,
      rows: document.querySelectorAll('tr').length,
      literal, overflow,
    };
  });
} finally {
  await browser.close();
}

let bad = 0;
const cmp = (name, a, b) => { const ok = a === b; if (!ok) bad++; console.log(`${ok ? '✓' : '✗'} ${name}: المصادر ${a}، الكتاب ${b}`); };
cmp('البنود', srcLi, r.li);
cmp('الجداول', srcTables, r.tables);
cmp('صفوف الجداول', srcRows - srcTables, r.rows);
console.log(`${r.literal.length ? '✗' : '✓'} رموز Markdown حرفية في النص: ${r.literal.length}`);
for (const x of r.literal) console.log(`   ${x.name} | ${x.at} | ${x.text}`);
console.log(`${r.overflow.length ? '✗' : '✓'} عناصر تخرج عن مكانها: ${r.overflow.length}`);
for (const x of r.overflow) console.log(`   ${x.at} | ${x.text}`);
console.log(`${r.bidi.length ? '✗' : '✓'} سهم أو «» بين كلمتين لاتينيتين (ينقلب): ${r.bidi.length}`);
for (const x of r.bidi) console.log(`   ${x.at} | ${x.text}`);
bad += r.literal.length + r.overflow.length + r.bidi.length;
process.exit(bad ? 1 : 0);
