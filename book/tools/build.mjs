#!/usr/bin/env node
// =====================================================================
//  بناء الكتاب:  chapters/*.md  →  HTML  →  PDF (Chromium)
//
//  node tools/build.mjs            بناء كامل: فهرس بأرقام الصفحات + ترويسة + ترقيم
//  node tools/build.mjs --fast     معاينة سريعة (مرور واحد، بدون ترقيم)
//  node tools/build.mjs --only=03,04   بناء فصول محددة فقط (للمراجعة)
//  node tools/build.mjs --png=1,5-7    حفظ صفحات كصور في .build/png للمراجعة
//  node tools/build.mjs --coverage     تقرير تغطية ملفات المشروع فقط (.build/coverage.txt)
//
//  تعليمات خاصة داخل ملفات الفصول:
//    @file app/core/DB.php 10-40     تضمين أسطر من ملف في مجلد php/ (يُلوَّن تلقائياً)
//    @file app/core/DB.php 41-$      حتى نهاية الملف        @file config.php   الملف كاملاً
//    @shot images/home.png | تعليق   صورة مع تعليق
//    @pagebreak                      صفحة جديدة
//    ::: tip|warning|note|info|try|why|summary [عنوان] ... :::   صناديق ملوّنة
// =====================================================================
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { execFileSync } from 'node:child_process';
import MarkdownIt from 'markdown-it';
import mdContainer from 'markdown-it-container';
import hljs from 'highlight.js';
import puppeteer from 'puppeteer-core';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SRC = path.resolve(BOOK, '..', 'php');
const BT = process.env.BOOK_TOOLS || '/tmp/booktools';
const OUT = path.join(BOOK, '.build');
const FINAL = path.join(BOOK, process.env.BOOK_OUT || 'dou-setif-book.pdf');
const argv = process.argv.slice(2);
const opt = (name) => (argv.find((a) => a.startsWith(`--${name}=`)) || '').split('=').slice(1).join('=');
const FAST = argv.includes('--fast');
const ONLY = opt('only') ? opt('only').split(',') : null;
const PNG = opt('png');
const PYENV = { ...process.env, PYTHONPATH: path.join(BT, 'pylib') };
const BOOK_TITLE = 'بناء موقع مديرية الخدمات الجامعية من الصفر';

fs.mkdirSync(OUT, { recursive: true });
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// ------------------------------------------------------------------ الفصول
const ORD = ['', 'الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع', 'العاشر',
  'الحادي عشر', 'الثاني عشر', 'الثالث عشر', 'الرابع عشر', 'الخامس عشر', 'السادس عشر', 'السابع عشر', 'الثامن عشر',
  'التاسع عشر', 'العشرون', 'الحادي والعشرون', 'الثاني والعشرون', 'الثالث والعشرون', 'الرابع والعشرون', 'الخامس والعشرون',
  'السادس والعشرون', 'السابع والعشرون'];
const APPX = { A: 'أ', B: 'ب', C: 'ج', D: 'د', E: 'هـ' };

const chapterFiles = fs.readdirSync(path.join(BOOK, 'chapters'))
  .filter((f) => f.endsWith('.md'))
  .sort()
  .filter((f) => !ONLY || ONLY.some((p) => f.startsWith(p)));

function chapterLabel(file) {
  const m = file.match(/^(\d+)/);
  if (m) {
    const n = parseInt(m[1], 10);
    return n === 0 ? { label: 'مقدمة', num: null } : { label: `الفصل ${ORD[n] || n}`, num: n };
  }
  const a = file.match(/^([A-E])/);
  return a ? { label: `الملحق ${APPX[a[1]]}`, num: null } : { label: '', num: null };
}

// ------------------------------------------------------------------ تلوين الكود
const VIEW_RE = /^app\/views\//;
function langFor(file) {
  const ext = path.extname(file).toLowerCase();
  const base = path.basename(file);
  if (base === '.htaccess') return 'apache';
  if (base === '.gitignore') return 'bash';
  if (ext === '.php') return VIEW_RE.test(file) ? 'php-template' : 'php';
  return { '.sql': 'sql', '.css': 'css', '.js': 'javascript', '.svg': 'xml', '.html': 'xml', '.json': 'json', '.md': 'markdown', '.sh': 'bash', '.ini': 'ini' }[ext] || 'plaintext';
}

// تقسيم HTML الملوَّن إلى أسطر مع إغلاق وفتح الوسوم المفتوحة عند كل سطر
function splitLines(html) {
  const lines = [];
  const open = [];
  let cur = '';
  const re = /(<span[^>]*>)|(<\/span>)|(\n)|([^<\n]+)/g;
  let m;
  while ((m = re.exec(html))) {
    if (m[1]) { open.push(m[1]); cur += m[1]; }
    else if (m[2]) { open.pop(); cur += m[2]; }
    else if (m[3]) { cur += '</span>'.repeat(open.length); lines.push(cur); cur = open.join(''); }
    else cur += m[4];
  }
  lines.push(cur);
  return lines;
}

function highlight(code, lang) {
  try {
    if (lang && hljs.getLanguage(lang)) return hljs.highlight(code, { language: lang, ignoreIllegals: true }).value;
  } catch { /* fallthrough */ }
  return esc(code);
}

// النص العربي داخل الكود (تعليقات، نصوص، أسطر عادية) يُعزل باتجاه RTL ليُقرأ بالترتيب الصحيح
const AR = /[\u0600-\u06FF]/;
const Q = "(?:&#x27;|&quot;|'|\"|`)";
const LEAD = new RegExp(`^(\\s*(?:\\/\\/+|#|\\/\\*+|\\*+|&lt;!--|--|${Q})?\\s*)`);
const TRAIL = new RegExp(`(\\s*(?:\\*\\/|--&gt;|${Q})?\\s*)$`);
const TRAIL_NOQ = /(\s*(?:\*\/|--&gt;)?\s*)$/;
const decHtml = (t) => t.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#x27;|&#39;/g, "'").replace(/&amp;/g, '&');
const encHtml = (t) => t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#x27;');
// داخل نص عربي، الكلمة اللاتينية التي تبدأ أو تنتهي برموز محايدة ("/index.php" أو route() أو @name)
// تُعزل باتجاه LTR حتى لا تنتقل رموزها إلى الجهة الخطأ. النص المُدخل HTML مُهرَّب بلا وسوم.
function wrapLatinRuns(text, open = '<span class="ltr">', close = '</span>', onlyIfNeeded = false, html = true) {
  const count = (str, chars) => [...str].filter((c) => chars.includes(c)).length;
  const dec = (t) => (html ? decHtml(t) : t);
  const enc = (t) => (html ? encHtml(t) : t);
  return dec(text).replace(/[^\u0600-\u06FF]+|[\u0600-\u06FF]+/g, (run) => {
    if (/[\u0600-\u06FF]/.test(run)) return enc(run);
    if (!/[A-Za-z0-9]/.test(run) || /^[\s\d.,:\-–]+$/.test(run)) return enc(run);
    // نقشّر من الطرفين: المسافات، وعلامات ترقيم الجملة، والأسهم، والأقواس غير المتوازنة (تبقى خارج العزل)
    const PUNCT = '—–:;,!?←→';
    const unbalancedOpen = (t) => count(t, '([{') > count(t, ')]}');
    const unbalancedClose = (t) => count(t, ')]}') > count(t, '([{');
    const unbalancedQuote = (t, c) => (c === "'" || c === '"') && count(t, c) % 2 === 1;
    let core = run;
    let pre = '';
    let post = '';
    for (;;) {
      const c = core[core.length - 1];
      if (c === ';' && /&#?\w+;$/.test(core)) break; // نص كيان HTML مثل &gt; يبقى كاملاً
      if (c && (/\s/.test(c) || PUNCT.includes(c) || c === '.' || (')]}'.includes(c) && unbalancedClose(core)) || ('([{'.includes(c) && unbalancedOpen(core)) || unbalancedQuote(core, c))) { post = c + post; core = core.slice(0, -1); } else break;
    }
    for (;;) {
      const c = core[0];
      // علامة الترقيم الملاصقة لكلمة لاتينية جزء منها (:question) فلا تُقشَّر
      const punctOk = PUNCT.includes(c) && (core.length === 1 || /[\s—–:;,!?←→]/.test(core[1]));
      if (c && (/\s/.test(c) || punctOk || ('([{'.includes(c) && unbalancedOpen(core)) || (')]}'.includes(c) && unbalancedClose(core)) || unbalancedQuote(core, c))) { pre += c; core = core.slice(1); } else break;
    }
    if (!/[A-Za-z0-9]/.test(core) || /^[\d.,:\-–]+$/.test(core)) return enc(run);
    const o = onlyIfNeeded && /^[A-Za-z0-9]/.test(core) ? '' : open;
    const c = onlyIfNeeded && /[A-Za-z0-9]$/.test(core) ? '' : close;
    return `${enc(pre)}${o}${enc(core)}${c}${enc(post)}`;
  });
}
// نفس المعالجة على نص HTML قد يحتوي وسوماً (العناوين): نعالج النصوص بين الوسوم فقط
const isoHtml = (html) => html.replace(/(^|>)([^<]+)/g, (m, a, t) => a + wrapLatinRuns(t));

function fixBidi(line, plain) {
  if (!AR.test(line)) return line;
  if (plain) {
    const i = line.search(AR);
    return `${line.slice(0, i)}<span class="rtl">${wrapLatinRuns(line.slice(i))}</span>`;
  }
  return line.replace(/(<span class="hljs-(comment|string|quote)">)([^<]*)(<\/span>)/g, (m, open, kind, inner, close) => {
    if (!AR.test(inner)) return m;
    const lead = inner.match(LEAD)[1];
    let rest = inner.slice(lead.length);
    // علامة التنصيص في الآخر لا تُفصل إلا إذا بدأ النص بعلامة تنصيص (نص برمجي لا تعليق)
    const trail = rest.match(new RegExp(Q).test(lead) ? TRAIL : TRAIL_NOQ)[1];
    rest = rest.slice(0, rest.length - trail.length);
    if (kind === 'string') {
      // وسوم HTML في بداية النص أو نهايته تبقى خارج العزل: '<b>المنحة</b>' تُعرض كما في المحرر
      const raw = decHtml(rest);
      let tagLead = raw.match(/^(\s*(?:<[^>]*>\s*)*)/)[1];
      let tagTrail = raw.slice(tagLead.length).match(/((?:\s*<[^>]*>)*\s*)$/)[1];
      let core = raw.slice(tagLead.length, raw.length - tagTrail.length);
      if (!AR.test(core)) return m;
      // قوس في طرف النص شريكه في نص آخر ('... (' و' حرف)</small>') يبقى خارج العزل في مكانه كما في المحرر،
      // ثم الوسوم التي صارت في الطرف بعده
      const cnt = (t, chars) => [...t].filter((ch) => chars.includes(ch)).length;
      const lone = (t, ch) => (')]}'.includes(ch) && cnt(t, ')]}') > cnt(t, '([{')) || ('([{'.includes(ch) && cnt(t, '([{') > cnt(t, ')]}'));
      for (let changed = true; changed && AR.test(core);) {
        changed = false;
        const last = core.slice(-1);
        if (lone(core, last)) { tagTrail = last + tagTrail; core = core.slice(0, -1); changed = true; }
        const first = core[0];
        if (first && lone(core, first)) { tagLead += first; core = core.slice(1); changed = true; }
        const t2 = core.match(/((?:\s*<[^>]*>)+\s*)$/);
        if (t2 && t2[1].length < core.length) { tagTrail = t2[1] + tagTrail; core = core.slice(0, -t2[1].length); changed = true; }
        const l2 = core.match(/^(\s*(?:<[^>]*>\s*)+)/);
        if (l2 && l2[1].length < core.length) { tagLead += l2[1]; core = core.slice(l2[1].length); changed = true; }
        const ws = core.match(/\s+$/);
        if (ws && ws[0].length < core.length) { tagTrail = ws[0] + tagTrail; core = core.slice(0, -ws[0].length); changed = true; }
        const wl = core.match(/^\s+/);
        if (wl && wl[0].length < core.length) { tagLead += wl[0]; core = core.slice(wl[0].length); changed = true; }
      }
      if (process.env.BIDI_DEBUG && (tagLead + core + tagTrail) === raw && (tagLead !== raw.match(/^(\s*(?:<[^>]*>\s*)*)/)[1])) console.error('BIDI lead:', raw);
      if (process.env.BIDI_DEBUG && tagTrail !== raw.slice(raw.match(/^(\s*(?:<[^>]*>\s*)*)/)[1].length).match(/((?:\s*<[^>]*>)*\s*)$/)[1]) console.error('BIDI trail:', raw);
      return `${open}${lead}${encHtml(tagLead)}<span class="rtl">${wrapLatinRuns(encHtml(core))}</span>${encHtml(tagTrail)}${trail}${close}`;
    }
    return `${open}${lead}<span class="rtl">${wrapLatinRuns(rest)}</span>${trail}${close}`;
  });
}

function codeBlock({ code, lang, start = 1, head = '', badge = '', badgeClass = 'ex', range = '', foot = '', kind = '' }) {
  const plain = !lang || lang === 'plaintext' || !hljs.getLanguage(lang);
  const lines = splitLines(highlight(code, lang)).map((l) => fixBidi(l, plain));
  const rows = lines.map((l, i) => `<div class="l"><span class="n">${start + i}</span><span class="c">${l === '' ? ' ' : l}</span></div>`).join('');
  const headHtml = (head || badge || range)
    ? `<div class="code-head">${badge ? `<span class="badge ${badgeClass}">${badge}</span>` : ''}<span class="path">${esc(head)}</span>${range ? `<span class="range">${range}</span>` : ''}</div>`
    : '';
  return `<div class="code ${kind}">${headHtml}<div class="code-body">${rows}</div>${foot ? `<div class="code-foot">${foot}</div>` : ''}</div>`;
}

// ------------------------------------------------------------------ تضمين ملفات المشروع + تتبّع التغطية
const coverage = new Map(); // file -> [[s,e,chapter]]
function includeFile(spec, chapter) {
  const [file, rangeSpec, ...noteParts] = spec.trim().split(/\s+/);
  const abs = path.join(SRC, file);
  if (!fs.existsSync(abs)) throw new Error(`@file: الملف غير موجود: ${file} (${chapter})`);
  const all = fs.readFileSync(abs, 'utf8').replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n');
  let s = 1, e = all.length;
  if (rangeSpec && rangeSpec !== '-') {
    const m = rangeSpec.match(/^(\d+)-(\d+|\$)$/);
    if (!m) throw new Error(`@file: مدى غير صالح "${rangeSpec}" في ${chapter}`);
    s = parseInt(m[1], 10);
    e = m[2] === '$' ? all.length : parseInt(m[2], 10);
  }
  if (s < 1 || e > all.length || s > e) throw new Error(`@file: المدى ${s}-${e} خارج الملف ${file} (${all.length} سطر) في ${chapter}`);
  if (!coverage.has(file)) coverage.set(file, []);
  coverage.get(file).push([s, e, chapter]);
  const whole = s === 1 && e === all.length;
  const badge = s === 1 ? 'ملف جديد' : 'تابع الملف';
  const range = whole ? `الملف كاملاً — ${all.length} سطر` : `الأسطر ${s}–${e} من ${all.length}`;
  const foot = e === all.length && !whole ? 'نهاية الملف' : (whole ? '' : `يتبع الملف من السطر ${e + 1}`);
  return codeBlock({
    code: all.slice(s - 1, e).join('\n'), lang: langFor(file), start: s,
    head: file, badge, badgeClass: s === 1 ? 'new' : 'cont', range, foot,
  });
}

// ------------------------------------------------------------------ Markdown
const md = new MarkdownIt({ html: true, linkify: false, typographer: false });
md.renderer.rules.fence = (tokens, idx) => {
  const t = tokens[idx];
  const [lang = 'plaintext', ...rest] = t.info.trim().split(/\s+/);
  const title = rest.join(' ');
  const terminal = ['cmd', 'shell', 'console', 'terminal'].includes(lang);
  return codeBlock({
    code: t.content.replace(/\n$/, ''),
    lang: terminal ? 'bash' : lang,
    head: title || '',
    badge: terminal ? 'سطر الأوامر' : 'مثال',
    badgeClass: 'ex',
    kind: terminal ? 'terminal' : 'example',
  });
};
md.use(mdContainer, 'intro', { render: (tokens, idx) => (tokens[idx].nesting === 1 ? '<div class="ch-intro">\n' : '</div>\n') });
const BOXES = { tip: 'نصيحة', warning: 'تنبيه', note: 'ملاحظة', info: 'معلومة', try: 'جرّب بنفسك', why: 'لماذا نفعل ذلك؟', summary: 'خلاصة الفصل' };
for (const [name, label] of Object.entries(BOXES)) {
  md.use(mdContainer, name, {
    render(tokens, idx) {
      const t = tokens[idx];
      if (t.nesting === 1) {
        const title = t.info.trim().slice(name.length).trim();
        return `<div class="box box-${name}"><div class="box-title">${md.utils.escapeHtml(title || label)}</div>\n`;
      }
      return '</div>\n';
    },
  });
}

function renderChapter(file) {
  const raw = fs.readFileSync(path.join(BOOK, 'chapters', file), 'utf8').replace(/\r\n/g, '\n');
  const blocks = [];
  let text = raw.replace(/^@file\s+(.+)$/gm, (_, spec) => {
    blocks.push(includeFile(spec, file));
    return `\n<div data-ph="${blocks.length - 1}"></div>\n`;
  });
  text = text.replace(/^@shot\s+(\S+)\s*(?:\|\s*(.*))?$/gm, (_, img, cap) => {
    const abs = path.join(BOOK, img);
    const capHtml = cap ? `<figcaption>${md.utils.escapeHtml(cap)}</figcaption>` : '';
    if (!fs.existsSync(abs)) {
      console.warn(`تحذير: صورة مفقودة ${img} في ${file}`);
      return `\n<figure><div class="shot-missing">${md.utils.escapeHtml(img)}</div>${capHtml}</figure>\n`;
    }
    return `\n<figure><img src="${pathToFileURL(abs).href}">${capHtml}</figure>\n`;
  });
  text = text.replace(/^@pagebreak\s*$/gm, '\n<div class="pb"></div>\n');
  let html = md.render(text);
  // تحذير: علامات Markdown لم تُفسَّر (مثل ** ملاصقة لـ « بعد حرف عربي)
  const prose = html.replace(/<div class="code[\s\S]*?<\/div><\/div>(<div class="code-foot">[\s\S]*?<\/div>)?<\/div>/g, '').replace(/<code>[\s\S]*?<\/code>/g, '');
  for (const m of prose.matchAll(/.{0,30}(\*\*|__|(?<![\w*])\*(?![\s*]))[^<]{0,30}/g)) console.warn(`تحذير Markdown في ${file}: …${m[0].replace(/<[^>]+>/g, '')}…`);
  html = html.replace(/<div data-ph="(\d+)"><\/div>/g, (_, i) => blocks[+i]);
  return html;
}

// ------------------------------------------------------------------ تجميع الكتاب
function fontFaces() {
  const F = (family, file, weight, style = 'normal') =>
    `@font-face{font-family:'${family}';src:url('${pathToFileURL(path.join(BT, 'fonts', file)).href}') format('truetype');font-weight:${weight};font-style:${style};}`;
  return [
    F('Noto Naskh Arabic', 'NotoNaskhArabic_400Regular.ttf', 400), F('Noto Naskh Arabic', 'NotoNaskhArabic_500Medium.ttf', 500),
    F('Noto Naskh Arabic', 'NotoNaskhArabic_600SemiBold.ttf', 600), F('Noto Naskh Arabic', 'NotoNaskhArabic_700Bold.ttf', 700),
    F('Noto Kufi Arabic', 'NotoKufiArabic_400Regular.ttf', 400), F('Noto Kufi Arabic', 'NotoKufiArabic_600SemiBold.ttf', 600),
    F('Noto Kufi Arabic', 'NotoKufiArabic_700Bold.ttf', 700), F('Noto Kufi Arabic', 'NotoKufiArabic_800ExtraBold.ttf', 800),
    F('Noto Sans', 'NotoSans_400Regular.ttf', 400), F('Noto Sans', 'NotoSans_700Bold.ttf', 700), F('Noto Sans', 'NotoSans_400Regular_Italic.ttf', 400, 'italic'),
    F('JetBrains Mono', 'JetBrainsMono_400Regular.ttf', 400), F('JetBrains Mono', 'JetBrainsMono_700Bold.ttf', 700),
    F('JetBrains Mono', 'JetBrainsMono_400Regular_Italic.ttf', 400, 'italic'),
  ].join('\n');
}

const chapters = chapterFiles.map((f) => ({ file: f, ...chapterLabel(f), html: renderChapter(f) }));

// ترقيم العناوين وإضافة معرّفات
const headings = [];
let body = '';
for (const ch of chapters) {
  let h2n = 0;
  let html = ch.html.replace(/<h([1-4])>([\s\S]*?)<\/h\1>/g, (_, lvl, inner) => {
    lvl = +lvl;
    const id = `h${headings.length + 1}`;
    let content = isoHtml(inner);
    if (lvl === 1) {
      content = `<span class="ch-label">${ch.label}</span> <span class="ch-sep">— </span><span class="ch-title">${isoHtml(inner)}</span>`;
    } else if (lvl === 2) {
      h2n += 1;
      if (ch.num) content = `<span class="num">${ch.num}.${h2n}</span> ${isoHtml(inner)}`;
    }
    headings.push({ id, lvl, inner, label: lvl === 1 ? ch.label : '', num: lvl === 2 && ch.num ? `${ch.num}.${h2n}` : '' });
    return `<h${lvl} id="${id}">${content}</h${lvl}>`;
  });
  body += `<section class="chapter" data-file="${ch.file}">${html}</section>\n`;
}

function tocHtml(pages) {
  const rows = headings.filter((h) => h.lvl <= 2).map((h) => {
    const p = pages ? (pages[h.id] ?? '') : '000';
    const text = isoHtml(h.inner.replace(/<[^>]+>/g, ''));
    if (h.lvl === 1) {
      return `<a class="row lvl1" href="#${h.id}"><span class="t">${h.label ? `<span class="lab">${h.label}:</span>` : ''}${text}</span><span class="dots"></span><span class="p">${p}</span></a>`;
    }
    return `<a class="row lvl2" href="#${h.id}"><span class="t">${h.num ? `<span class="num">${h.num}</span>` : ''}${text}</span><span class="dots"></span><span class="p">${p}</span></a>`;
  }).join('\n');
  return `<div class="front"><div class="front-title">المحتويات</div><nav class="toc">${rows}</nav></div>`;
}

function coverHtml() {
  const logo = pathToFileURL(path.join(SRC, 'assets/images/logo.svg')).href;
  return `<div class="cover">
  <div class="band top"></div><div class="circle c1"></div><div class="circle c2"></div>
  <div class="logo"><img src="${logo}"></div>
  <div class="kicker">دليل عملي خطوة بخطوة للمبتدئين</div>
  <div class="title">بناء موقع مديرية الخدمات الجامعية لولاية سطيف من الصفر</div>
  <div class="subtitle">الواجهة العامة، فضاء الطالب، لوحة الموظفين ولوحة الإدارة<br>مع قاعدة البيانات، سطراً بسطر</div>
  <div class="tags"><span>PHP 8</span><span>MySQL</span><span>HTML &amp; CSS</span><span>Bootstrap 3</span><span>JavaScript</span></div>
  <div class="for">لا يحتاج أي خبرة سابقة: <b>كل ما تحتاجه موجود في هذا الكتاب</b></div>
  <div class="edition">الطبعة الأولى — 2026</div>
  <div class="band bottom"></div>
</div>`;
}

function pageHtml(pages) {
  const css = fs.readFileSync(path.join(BOOK, 'tools', 'book.css'), 'utf8');
  const front = fs.existsSync(path.join(BOOK, 'front.md')) && !ONLY
    ? `<div class="front">${md.render(fs.readFileSync(path.join(BOOK, 'front.md'), 'utf8'))}</div>` : '';
  return `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>${BOOK_TITLE}</title>
<style>${fontFaces()}\n${css}</style></head><body>
${ONLY ? '' : coverHtml()}
${front}
${tocHtml(pages)}
${body}
</body></html>`;
}

// ------------------------------------------------------------------ تقرير التغطية
function coverageReport() {
  const EXCLUDE = [/^database\/data\.sql$/, /^README\.md$/, /\.gitkeep$/, /\.jpe?g$/i, /\.png$/i];
  const files = execFileSync('git', ['ls-files'], { cwd: SRC, encoding: 'utf8' }).trim().split('\n')
    .filter((f) => !EXCLUDE.some((re) => re.test(f)));
  const lines = [];
  let full = 0;
  for (const f of files) {
    const src = fs.readFileSync(path.join(SRC, f), 'utf8').replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n');
    const n = src.length;
    const cov = new Array(n + 1).fill(0);
    let prevEnd = 0;
    for (const [s, e, chap] of coverage.get(f) || []) {
      if (s <= prevEnd) console.warn(`تحذير ترتيب: ${f} ${s}-${e} في ${chap} يأتي قبل/داخل قطعة سابقة تنتهي عند ${prevEnd}`);
      prevEnd = Math.max(prevEnd, e);
      for (let i = s; i <= e; i++) cov[i]++;
    }
    const missing = [], dup = [];
    for (let i = 1; i <= n; i++) {
      if (cov[i] === 0 && src[i - 1].trim() !== '') missing.push(i);   // الأسطر الفارغة بين القطع لا تُحسب
      if (cov[i] > 1) dup.push(i);
    }
    const ranges = (arr) => arr.reduce((acc, x) => {
      const last = acc[acc.length - 1];
      if (last && last[1] === x - 1) last[1] = x; else acc.push([x, x]);
      return acc;
    }, []).map(([a, b]) => (a === b ? `${a}` : `${a}-${b}`)).join(',');
    if (!missing.length && !dup.length) { full++; continue; }
    lines.push(`${f} (${n}): ${missing.length ? `ناقص ${ranges(missing)} ` : ''}${dup.length ? `مكرر ${ranges(dup)}` : ''}`);
  }
  const report = `ملفات مكتملة: ${full}/${files.length}\n${lines.join('\n')}\n`;
  fs.writeFileSync(path.join(OUT, 'coverage.txt'), report);
  return { full, total: files.length, report };
}

// ------------------------------------------------------------------ الطباعة
async function launch() {
  return puppeteer.launch({
    executablePath: path.join(BT, 'rt', 'chromium'),
    headless: 'shell',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--no-zygote', '--disable-gpu', '--disable-dev-shm-usage', '--font-render-hinting=none', '--allow-file-access-from-files'],
    env: { ...process.env, HOME: BT, LD_LIBRARY_PATH: `${BT}/rt/al2023/lib:${BT}/rt`, FONTCONFIG_FILE: path.join(BT, 'fonts.conf') },
    protocolTimeout: 900000,
  });
}

async function printPdf(browser, html, file, extra = {}) {
  const htmlFile = file.replace(/\.pdf$/, '.html');
  fs.writeFileSync(htmlFile, html);
  const page = await browser.newPage();
  await page.goto(pathToFileURL(htmlFile).href, { waitUntil: 'load', timeout: 0 });
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: file, preferCSSPageSize: true, printBackground: true, timeout: 0, ...extra });
  await page.close();
}

function pdfOutline(file) {
  return JSON.parse(execFileSync('python3', [path.join(BOOK, 'tools', 'pdftool.py'), 'outline', file], { env: PYENV, encoding: 'utf8', maxBuffer: 1 << 26 }));
}

function mapPages(info) {
  // عناوين الفهرس الجانبي بنفس ترتيب العناوين في الكتاب
  const pages = {};
  const out = info.outline;
  if (out.length !== headings.length) console.warn(`تحذير: عدد عناوين الـ PDF (${out.length}) لا يساوي عدد العناوين (${headings.length})`);
  headings.forEach((h, i) => { if (out[i]) pages[h.id] = out[i].page; });
  return pages;
}

function overlayHtml(total, pages) {
  // ترويسة (عنوان الكتاب + عنوان الفصل) وتذييل (رقم الصفحة) فوق كل صفحة ما عدا الغلاف
  const chapterStarts = headings.filter((h) => h.lvl === 1).map((h) => ({
    page: pages[h.id], title: `${h.label}: ${h.inner.replace(/<[^>]+>/g, '')}`,
  })).filter((c) => c.page).sort((a, b) => a.page - b.page);
  const startSet = new Set(chapterStarts.map((c) => c.page));
  let html = '';
  for (let p = 1; p <= total; p++) {
    let inner = '';
    if (p > 1) {
      const ch = chapterStarts.filter((c) => c.page <= p).pop();
      if (ch && !startSet.has(p)) {
        inner += `<div class="hd"><span class="ct">${esc(ch.title)}</span><span class="bt">${BOOK_TITLE}</span></div>`;
      }
      inner += `<div class="ft"><span>${p}</span></div>`;
    }
    html += `<div class="pg">${inner}</div>`;
  }
  return `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><style>${fontFaces()}
@page { size: A4; margin: 0; }
html, body { margin: 0; background: transparent; }
.pg { width: 210mm; height: 297mm; position: relative; break-after: page; overflow: hidden; }
.hd { position: absolute; top: 11mm; right: 19mm; left: 19mm; display: flex; justify-content: space-between; align-items: baseline;
      border-bottom: .8px solid #c5ced8; padding-bottom: 1.6mm; font-family: 'Noto Kufi Arabic', sans-serif; font-size: 7.6pt; color: #6b7785; }
.hd .ct { color: #2c3e50; font-weight: 600; max-width: 60%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ft { position: absolute; bottom: 9.5mm; left: 0; right: 0; text-align: center; }
.ft span { display: inline-block; min-width: 9mm; padding: .4mm 2.5mm; border-radius: 3mm; background: #2c3e50; color: #fff;
           font-family: 'Noto Sans', sans-serif; font-size: 8pt; font-weight: 700; }
</style></head><body>${html}</body></html>`;
}

// ------------------------------------------------------------------ التشغيل
const t0 = Date.now();
const cov = coverageReport();
console.log(`الفصول: ${chapters.length} | العناوين: ${headings.length} | كتل كود من المشروع: ${[...coverage.values()].reduce((a, b) => a + b.length, 0)}`);
console.log(`تغطية ملفات المشروع: ${cov.full}/${cov.total} ملف مكتمل (التفاصيل في .build/coverage.txt)`);
if (argv.includes('--coverage')) process.exit(0); // تقرير التغطية فقط، بلا PDF

const browser = await launch();
try {
  const pass = path.join(OUT, 'pass.pdf');
  if (FAST) {
    await printPdf(browser, pageHtml(null), pass, { outline: false });
    fs.copyFileSync(pass, FINAL.replace(/\.pdf$/, '.preview.pdf'));
    console.log(`معاينة: ${FINAL.replace(/\.pdf$/, '.preview.pdf')}`);
  } else {
    let pages = null;
    let info = null;
    for (let i = 1; i <= 3; i++) {
      await printPdf(browser, pageHtml(pages), pass, { outline: true, tagged: true });
      info = pdfOutline(pass);
      const next = mapPages(info);
      const stable = pages && headings.every((h) => pages[h.id] === next[h.id]);
      console.log(`المرور ${i}: ${info.pages} صفحة${stable ? ' (أرقام الفهرس ثابتة)' : ''}`);
      if (stable) break;
      pages = next;
    }
    const overlay = path.join(OUT, 'overlay.pdf');
    await printPdf(browser, overlayHtml(info.pages, pages), overlay, { omitBackground: true, printBackground: true });
    // الفهرس الجانبي (Bookmarks) بعناوين نظيفة: «الفصل الأول: ...» و«1.2 ...»
    const plain = (s) => s.replace(/<[^>]+>/g, '').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&').trim();
    const bm = (s) => wrapLatinRuns(plain(s), '\u200E', '\u200E', true, false);
    const bookmarks = headings.filter((h) => h.lvl <= 3 && pages[h.id]).map((h) => ({
      level: h.lvl,
      title: h.lvl === 1 ? (h.label ? `${h.label}: ${bm(h.inner)}` : bm(h.inner)) : (h.num ? `${h.num} ${bm(h.inner)}` : bm(h.inner)),
      page: pages[h.id],
    }));
    const metaFile = path.join(OUT, 'meta.json');
    fs.writeFileSync(metaFile, JSON.stringify({ Title: BOOK_TITLE, Subject: 'برمجة موقع بـ PHP و MySQL من الصفر', Creator: 'book/tools/build.mjs', Keywords: 'PHP, MySQL, Bootstrap, XAMPP', _outline: bookmarks }));
    const target = ONLY ? FINAL.replace(/\.pdf$/, '.partial.pdf') : FINAL;
    console.log(execFileSync('python3', [path.join(BOOK, 'tools', 'pdftool.py'), 'stamp', pass, overlay, target, metaFile], { env: PYENV, encoding: 'utf8' }).trim());
  }
} finally {
  await browser.close();
}
if (PNG) {
  const src = FAST ? FINAL.replace(/\.pdf$/, '.preview.pdf') : (ONLY ? FINAL.replace(/\.pdf$/, '.partial.pdf') : FINAL);
  console.log(execFileSync('python3', [path.join(BOOK, 'tools', 'pdftool.py'), 'png', src, path.join(OUT, 'png'), PNG, '1.3'], { env: PYENV, encoding: 'utf8' }).trim());
}
console.log(`تم في ${((Date.now() - t0) / 1000).toFixed(1)} ث`);
