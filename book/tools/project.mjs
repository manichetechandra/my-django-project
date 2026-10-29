// مصدر كود الكتاب: النسخة 1.0 المجمّدة في dou-setif-php.zip
// (مجلد php/ في المستودع صار النسخة 2.0 متعددة اللغات، والكتاب يشرح النسخة 1.0 كما هي)
//
//   import { SRC, projectFiles } from './project.mjs';
//   node tools/project.mjs          ← يطبع مسار المجلد (للسكربتات: SRC=$(node tools/project.mjs))
//
// يُفك الملف المضغوط مرة واحدة في .build/project-v1، ويُعاد فكه إن تغيّر.
// للتجربة على مجلد آخر: BOOK_SRC=/path/to/project node tools/build.mjs
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const BOOK = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const ZIP = path.resolve(BOOK, '..', 'dou-setif-php.zip');
const OUT = path.join(BOOK, '.build', 'project-v1');
const ROOT = 'dou-setif/';

function extract() {
  const hash = crypto.createHash('sha1').update(fs.readFileSync(ZIP)).digest('hex');
  const stamp = path.join(OUT, '.zip-sha1');
  if (fs.existsSync(stamp) && fs.readFileSync(stamp, 'utf8') === hash) return;
  fs.rmSync(OUT, { recursive: true, force: true });
  fs.mkdirSync(OUT, { recursive: true });
  execFileSync('python3', ['-c', 'import sys, zipfile; zipfile.ZipFile(sys.argv[1]).extractall(sys.argv[2])', ZIP, OUT]);
  fs.writeFileSync(stamp, hash);
}

export const SRC = process.env.BOOK_SRC ? path.resolve(process.env.BOOK_SRC) : (extract(), path.join(OUT, ROOT));

/** ملفات المشروع (مثل git ls-files): أسماء الملفات داخل الملف المضغوط */
export function projectFiles() {
  if (process.env.BOOK_SRC) {
    return execFileSync('git', ['ls-files'], { cwd: SRC, encoding: 'utf8' }).trim().split('\n');
  }
  const out = execFileSync('python3', ['-c',
    'import sys, zipfile; print("\\n".join(n for n in zipfile.ZipFile(sys.argv[1]).namelist() if not n.endswith("/")))', ZIP],
  { encoding: 'utf8' });
  return out.trim().split('\n').filter((n) => n.startsWith(ROOT)).map((n) => n.slice(ROOT.length)).sort();
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  console.log(SRC.replace(/\/$/, ''));
}
