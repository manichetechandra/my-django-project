#!/usr/bin/env node
// =====================================================================
//  المراحل: ملفات مؤقتة تتطوّر عبر فصول الكتاب (الصفحة الثابتة، ثم PHP، ثم قاعدة البيانات...)
//
//  الملف الرئيسي stages/<name>.src : أسطر عادية + كتل
//    @@file index.html 2                  اسم الملف الرئيسي ابتداءً من الخطوة 2 (يمكن تغييره لاحقاً)
//    @@block NAME STEP [from=PATH:a-b] [replaces=A,B] [until=STEP] [file=PATH@STEP] [show=a-b]
//      ...أسطر...                         (تُترك فارغة مع from=: النص يؤخذ من ملف المشروع النهائي php/PATH)
//    @@end
//  الخطوة STEP رقم الفصل (يمكن أن يكون عشرياً مثل 27.1). لقطة الخطوة k = الأسطر العادية
//  + الكتل التي STEP<=k ولم تُستبدل (replaces) ولم تنتهِ (until) — كل كتلة في ملفها (file=PATH@STEP ينقلها).
//
//  سطر الأوامر:  node tools/stages.mjs <name> <step> <outdir>   يكتب ملفات اللقطة
// =====================================================================
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SRC = path.resolve(BOOK, '..', 'php');
const cache = new Map();

function finalLines(file) {
  const abs = path.join(SRC, file);
  if (!fs.existsSync(abs)) throw new Error(`stages: ملف المشروع غير موجود ${file}`);
  return fs.readFileSync(abs, 'utf8').replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n');
}

export function loadStage(name) {
  if (cache.has(name)) return cache.get(name);
  const file = path.join(BOOK, 'stages', `${name}.src`);
  if (!fs.existsSync(file)) throw new Error(`stages: لا يوجد ${file}`);
  const raw = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n');
  const items = []; // {type:'line', text} | {type:'block', ...}
  const files = []; // [{step, path}]
  const blocks = new Map();
  let cur = null;
  raw.forEach((line, i) => {
    const ln = i + 1;
    if (line.startsWith('@@file ')) {
      const [, p, step] = line.split(/\s+/);
      files.push({ step: parseFloat(step), path: p });
      return;
    }
    if (line.startsWith('@@block ')) {
      if (cur) throw new Error(`${name}.src:${ln}: كتلة داخل كتلة`);
      const [, bname, step, ...attrs] = line.trim().split(/\s+/);
      const a = Object.fromEntries(attrs.map((x) => { const k = x.indexOf('='); return [x.slice(0, k), x.slice(k + 1)]; }));
      cur = { type: 'block', name: bname, step: parseFloat(step), lines: [], src: ln, ...a };
      if (!(cur.step >= 0)) throw new Error(`${name}.src:${ln}: خطوة غير صالحة`);
      if (blocks.has(bname)) throw new Error(`${name}.src:${ln}: اسم مكرر ${bname}`);
      if (a.from) {
        const m = a.from.match(/^(.+):(\d+)-(\d+)$/);
        if (!m) throw new Error(`${name}.src:${ln}: from غير صالح`);
        cur.fromFile = m[1]; cur.fromStart = +m[2]; cur.fromEnd = +m[3];
        const all = finalLines(m[1]);
        if (cur.fromEnd > all.length) throw new Error(`${name}.src:${ln}: ${m[1]} فيه ${all.length} سطراً فقط`);
        cur.lines = all.slice(cur.fromStart - 1, cur.fromEnd);
      }
      if (a.file) { const [p, s] = a.file.split('@'); cur.moveTo = p; cur.moveStep = parseFloat(s); }
      cur.replaces = a.replaces ? a.replaces.split(',') : [];
      cur.until = a.until ? parseFloat(a.until) : Infinity;
      blocks.set(bname, cur);
      items.push(cur);
      return;
    }
    if (line === '@@end') {
      if (!cur) throw new Error(`${name}.src:${ln}: @@end بلا كتلة`);
      if (cur.from && cur.fromLinesAdded) throw new Error(`${name}.src:${cur.src}: كتلة from يجب أن تكون فارغة`);
      cur = null;
      return;
    }
    if (cur) {
      if (cur.from) throw new Error(`${name}.src:${ln}: كتلة from=${cur.from} يجب أن تكون فارغة`);
      cur.lines.push(line);
    } else items.push({ type: 'line', text: line });
  });
  if (cur) throw new Error(`${name}.src: كتلة ${cur.name} بلا @@end`);
  files.sort((a, b) => a.step - b.step);
  const first = files.length ? files[0].step : 0;
  const st = { name, items, blocks, files, first };
  cache.set(name, st);
  return st;
}

function mainFile(st, k) {
  let p = null;
  for (const f of st.files) if (f.step <= k) p = f.path;
  return p;
}

function visible(st, b, k) {
  if (b.step > k || k >= b.until) return false;
  for (const o of st.blocks.values()) {
    if (o !== b && o.replaces.includes(b.name) && o.step <= k && k < o.until) return false;
  }
  return true;
}

function fileOf(st, item, k) {
  if (item.type === 'block' && item.moveTo && k >= item.moveStep) return item.moveTo;
  return mainFile(st, k);
}

/** ملفات اللقطة عند الخطوة k: Map(path -> {lines:[], ranges: Map(blockName -> [start,end])}) */
export function snapshot(name, k) {
  const st = loadStage(name);
  const out = new Map();
  const get = (p) => { if (!out.has(p)) out.set(p, { lines: [], ranges: new Map() }); return out.get(p); };
  for (const it of st.items) {
    const p = fileOf(st, it, k);
    if (!p) continue;
    if (it.type === 'line') {
      if (k < st.first) continue;
      get(p).lines.push(it.text);
    } else if (visible(st, it, k)) {
      const f = get(p);
      const s = f.lines.length + 1;
      f.lines.push(...it.lines);
      f.ranges.set(it.name, [s, f.lines.length]);
    }
  }
  return out;
}

/** كتلة للعرض في الكتاب: الأسطر وأرقامها في لقطة خطوتها (أو خطوة محددة) واسم الملف */
export function stageBlock(name, blockName, step) {
  const st = loadStage(name);
  const b = st.blocks.get(blockName);
  if (!b) throw new Error(`@stage: لا توجد كتلة ${blockName} في ${name}.src`);
  const k = step != null && step !== '' ? parseFloat(step) : b.step;
  const snap = snapshot(name, k);
  for (const [p, f] of snap) {
    if (f.ranges.has(blockName)) {
      const [s, e] = f.ranges.get(blockName);
      let lines = f.lines.slice(s - 1, e);
      let start = s;
      if (b.show) { // عرض جزء فقط من الكتلة: show=a-b (أرقام نسبية داخل الكتلة)
        const [x, y] = b.show.split('-').map(Number);
        lines = lines.slice(x - 1, y);
        start = s + x - 1;
      }
      return { file: p, start, end: start + lines.length - 1, total: f.lines.length, lines, block: b, step: k };
    }
  }
  throw new Error(`@stage: الكتلة ${blockName} غير ظاهرة عند الخطوة ${k}`);
}

export function writeSnapshot(name, k, outdir) {
  const snap = snapshot(name, k);
  const written = [];
  for (const [p, f] of snap) {
    const abs = path.join(outdir, p);
    fs.mkdirSync(path.dirname(abs), { recursive: true });
    fs.writeFileSync(abs, f.lines.join('\n') + '\n');
    written.push(p);
  }
  return written;
}

if (process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])) {
  const [name, step, outdir] = process.argv.slice(2);
  if (!name || step == null || !outdir) {
    console.error('الاستعمال: node tools/stages.mjs <name> <step> <outdir>');
    process.exit(1);
  }
  for (const p of writeSnapshot(name, parseFloat(step), outdir)) console.log(path.join(outdir, p));
}
