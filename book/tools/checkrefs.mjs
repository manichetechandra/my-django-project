#!/usr/bin/env node
// =====================================================================
//  فحص إشارات أرقام الأسطر في الفصول: «**السطر 41**: ... `code`»
//  لكل إشارة نتحقق أن مقاطع الكود المذكورة بين علامتي ` موجودة فعلاً في تلك الأسطر
//  من آخر صندوق كود قبلها (@file أو @stage). ونقترح الإزاحة إن وُجدت في أسطر مجاورة.
//  الاستعمال: node tools/checkrefs.mjs [16 17 ...]   (بدون معاملات: كل الفصول)
// =====================================================================
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { stageBlock, stageFile } from './stages.mjs';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SRC = path.resolve(BOOK, '..', 'php');
const only = process.argv.slice(2);
const files = fs.readdirSync(path.join(BOOK, 'chapters')).filter((f) => f.endsWith('.md')).sort()
  .filter((f) => !only.length || only.some((p) => f.startsWith(p)));

const finalCache = new Map();
const finalLines = (f) => {
  if (!finalCache.has(f)) finalCache.set(f, fs.readFileSync(path.join(SRC, f), 'utf8').replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n'));
  return finalCache.get(f);
};
const norm = (s) => s.replace(/\s+/g, '').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
const AR = /[\u0600-\u06FF]/;

let problems = 0;
for (const file of files) {
  const text = fs.readFileSync(path.join(BOOK, 'chapters', file), 'utf8').split('\n');
  let ctx = null;
  let inFence = false;
  text.forEach((line, i) => {
    if (line.startsWith('```')) { inFence = !inFence; return; }
    if (inFence) return;
    let m;
    if ((m = line.match(/^@file\s+(\S+)(?:\s+(\S+))?/))) {
      const all = finalLines(m[1]);
      let s = 1, e = all.length;
      if (m[2] && m[2] !== '-' && /^\d/.test(m[2])) { const r = m[2].match(/^(\d+)-(\d+|\$)$/); s = +r[1]; e = r[2] === '$' ? all.length : +r[2]; }
      ctx = { label: `${m[1]} ${s}-${e}`, lines: all, s, e };
      return;
    }
    if ((m = line.match(/^@stage\s+(\S+)\s+(\S+)(?:\s+(\S+))?(?:\s+(\S+))?/))) {
      try {
        const b = m[2] === '*' ? stageFile(m[1], m[3], m[4]) : stageBlock(m[1], m[2], m[3] === '-' ? '' : m[3]);
        const full = stageFile(m[1], b.step, b.file);
        ctx = { label: `stage ${m[1]} ${m[2]} (${b.file} ${b.start}-${b.end})`, lines: full.lines, s: b.start, e: b.end };
      } catch (err) { console.log(`${file}:${i + 1}: ${err.message}`); problems++; }
      return;
    }
    if (line.startsWith('@')) return;
    if (!ctx) return;
    // كل إشارة رقمية مع المقاطع التي تليها حتى الإشارة التالية
    const re = /\*\*(?:السطر|الأسطر|السطران)\s+(\d+)(?:\s*[–-]\s*(\d+))?\*\*/g;
    const refs = [...line.matchAll(re)];
    refs.forEach((r, k) => {
      const a = +r[1], b = r[2] ? +r[2] : a;
      const seg = line.slice(r.index + r[0].length, k + 1 < refs.length ? refs[k + 1].index : line.length);
      const snippets = [...seg.matchAll(/`([^`]+)`/g)].map((x) => x[1]).filter((x) => x.length >= 3 && !AR.test(x) && !/^\d+$/.test(x));
      if (!snippets.length) return;
      const range = (x, y) => norm(ctx.lines.slice(Math.max(0, x - 1), Math.min(ctx.lines.length, y)).join('\n'));
      if (a < 1 || b > ctx.lines.length) {
        console.log(`${file}:${i + 1}: «${r[0]}» خارج الملف (${ctx.label})`); problems++; return;
      }
      const inRange = range(a, b);
      const found = snippets.filter((sn) => inRange.includes(norm(sn)));
      if (found.length) return;
      // ابحث عن إزاحة قريبة
      let hint = '';
      for (const d of [1, -1, 2, -2, 3, -3]) {
        const t = range(a + d, b + d);
        if (snippets.some((sn) => t.includes(norm(sn)))) { hint = ` ← ربما ${a + d}${b !== a ? '–' + (b + d) : ''}`; break; }
      }
      // لا ننبه إن لم يوجد المقطع أصلاً في الكتلة (غالباً اسم عام أو مثال)
      const inBlock = range(ctx.s, ctx.e);
      if (!hint && !snippets.some((sn) => inBlock.includes(norm(sn)))) return;
      console.log(`${file}:${i + 1}: «${r[0]}» لا يحتوي ${snippets.map((x) => '`' + x + '`').join(' ')} (${ctx.label})${hint}`);
      problems++;
    });
  });
}
console.log(problems ? `\n${problems} إشارة تحتاج مراجعة` : 'كل الإشارات سليمة');
