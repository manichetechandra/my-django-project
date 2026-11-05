#!/usr/bin/env python3
"""
الكتاب الإصدار الثالث: نقل إشارات الفصول من كود النسخة 1.0 (dou-setif-php.zip) إلى كود النسخة 2 (php/).

  python3 tools/remap_v2.py            تقرير فقط (.build/remap-report.txt) بلا تعديل
  python3 tools/remap_v2.py --apply    تعديل الفصول والمراحل: @file و @moved و from= وأرقام الأسطر في النص

لكل ملف: مطابقة أسطر 1.0 ← 2 (difflib). المدى a-b يصير a'-b' (يشمل الأسطر الجديدة داخله)، وحالته:
  same     المحتوى نفسه (إزاحة فقط: لا كتابة)
  changed  تغيّر المحتوى (يُراجع النص) — مع الأسطر المختلفة
ثم أسطر النسخة 2 التي لا تغطيها أي إشارة (كود جديد يحتاج نصاً أو فصلاً جديداً).
"""
import difflib
import json
import os
import re
import subprocess
import sys
import zipfile

BOOK = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
REPO = os.path.dirname(BOOK)
APPLY = '--apply' in sys.argv
EXCLUDE = [r'^database/data\.sql$', r'^README\.md$', r'^SECURITY\.md$', r'\.gitkeep$', r'\.(jpe?g|png|gif|webp|ico|svg)$',
           r'^assets/vendor/', r'^assets/js/tinymce-lang/', r'^uploads/', r'^storage/', r'(^|/)\.gitignore$']

# ------------------------------------------------------------------ النسختان
# --from-rev=REV : المصدر php/ في مراجعة git (بعد تعديل كود البرنامج نفسه، مثل تنظيف أسطر) بدل dou-setif-php.zip
FROM_REV = next((a.split('=', 1)[1] for a in sys.argv if a.startswith('--from-rev=')), None)
V1 = {}
if FROM_REV:
    for n in subprocess.run(['git', 'ls-tree', '-r', '--name-only', FROM_REV, 'php/'], cwd=REPO, capture_output=True, text=True).stdout.split():
        try:
            V1[n[len('php/'):]] = subprocess.run(['git', 'show', f'{FROM_REV}:{n}'], cwd=REPO, capture_output=True, check=True).stdout.decode('utf-8').replace('\r\n', '\n')
        except UnicodeDecodeError:
            pass
else:
    z = zipfile.ZipFile(os.path.join(REPO, 'dou-setif-php.zip'))
    for n in z.namelist():
        if not n.endswith('/') and n.startswith('dou-setif/'):
            V1[n[len('dou-setif/'):]] = z.read(n).decode('utf-8', 'replace').replace('\r\n', '\n')
FILES2 = subprocess.run(['git', 'ls-files'], cwd=os.path.join(REPO, 'php'), capture_output=True, text=True).stdout.split()
V2 = {}
for f in FILES2:
    try:
        V2[f] = open(os.path.join(REPO, 'php', f), encoding='utf-8').read().replace('\r\n', '\n')
    except UnicodeDecodeError:
        pass


def lines(text):
    t = text[:-1] if text.endswith('\n') else text
    return t.split('\n') if t else []


MAPS = {}


def fmap(f):
    """(أسطر 1.0، أسطر 2، opcodes) لملف"""
    if f not in MAPS:
        a, b = lines(V1.get(f, '')), lines(V2.get(f, ''))
        MAPS[f] = (a, b, difflib.SequenceMatcher(None, a, b, autojunk=False).get_opcodes())
    return MAPS[f]


def map_line(f, n, edge):
    """سطر 1.0 (يبدأ من 1) ← سطر 2. edge='start' أو 'end' (لأطراف المدى داخل كتلة متغيرة) أو 'point'"""
    a, b, ops = fmap(f)
    i = n - 1
    for tag, i1, i2, j1, j2 in ops:
        if i1 <= i < i2 or (tag == 'insert' and False):
            if tag == 'equal':
                return j1 + (i - i1) + 1
            if tag == 'replace':
                if edge == 'start':
                    return j1 + 1
                if edge == 'end':
                    return j2
                return min(j1 + (i - i1), j2 - 1) + 1
            if tag == 'delete':
                return j1 + (1 if edge == 'start' else 0) or 1
    return len(b) if edge != 'start' else 1


def map_range(f, s, e):
    a, b, _ = fmap(f)
    s2, e2 = map_line(f, s, 'start'), map_line(f, e, 'end')
    if e2 < s2:
        e2 = s2
    same = a[s - 1:e] == b[s2 - 1:e2]
    return s2, e2, same


def diff_lines(f, s, e, s2, e2, limit=6):
    a, b, _ = fmap(f)
    out = []
    for line in difflib.unified_diff(a[s - 1:e], b[s2 - 1:e2], lineterm='', n=0):
        if line.startswith(('+++', '---', '@@')):
            continue
        out.append(line[:150])
        if len(out) >= limit:
            out.append('   …')
            break
    return out


# ------------------------------------------------------------------ الفصول والمراحل
report = []
covered = {}  # file -> set(lines v2)
stats = {'same': 0, 'changed': 0, 'new-file': 0, 'whole': 0}


def cover(f, s, e):
    covered.setdefault(f, set()).update(range(s, e + 1))


RANGE = re.compile(r'^(\d+)-(\d+|\$)$')
PROSE = re.compile(r'(السطرين|السطران|الأسطر|السطر|سطر|سطرا|أسطر)(\s*\*?\*?\s*)(\d+)(\s*[–\-]\s*(\d+))?')


def remap_prose(line, f):
    if f is None or f not in V1 or f not in V2:
        return line

    def sub(m):
        n1 = int(m.group(3))
        if n1 < 1 or n1 > len(lines(V1[f])):
            return m.group(0)
        if m.group(5):
            n2 = int(m.group(5))
            if n2 < n1 or n2 > len(lines(V1[f])):
                return m.group(0)
            s2, e2, _ = map_range(f, n1, n2)
            dash = m.group(4).replace(m.group(5), '')
            return f'{m.group(1)}{m.group(2)}{s2}{dash}{e2}'
        return f'{m.group(1)}{m.group(2)}{map_line(f, n1, "point")}'

    return PROSE.sub(sub, line)


chapters = sorted(x for x in os.listdir(os.path.join(BOOK, 'chapters')) if x.endswith('.md'))
for ch in chapters:
    path = os.path.join(BOOK, 'chapters', ch)
    src = open(path, encoding='utf-8').read().split('\n')
    out = []
    ctx = None  # ملف آخر صندوق @file (لأرقام الأسطر في النص)
    fence = False
    for ln, line in enumerate(src, 1):
        if line.startswith('```'):
            fence = not fence
            out.append(line)
            continue
        if fence:
            out.append(line)
            continue
        m = re.match(r'^@file\s+(\S+)(?:\s+(\S+))?(.*)$', line)
        if m:
            f, rng, rest = m.group(1), m.group(2), m.group(3) or ''
            # مدى متعدد «a-b,c-d»: كل جزء يُنقل وحده
            if rng and ',' in rng and all(RANGE.match(p) for p in rng.split(',')) and f in V1 and f in V2:
                ctx = f
                parts = []
                for p in rng.split(','):
                    r = RANGE.match(p)
                    s, e = int(r.group(1)), (len(lines(V1[f])) if r.group(2) == '$' else int(r.group(2)))
                    s2, e2, same = map_range(f, s, e)
                    if r.group(2) == '$':
                        e2 = len(lines(V2[f]))
                    cover(f, s2, e2)
                    stats['same' if same else 'changed'] += 1
                    if not same:
                        report.append(f'{ch}:{ln} ~ {f} {p} → {s2}-{e2}')
                        report.extend('      ' + d for d in diff_lines(f, s, e, s2, e2))
                    parts.append(f'{s2}-{"$" if r.group(2) == "$" else e2}')
                out.append(f'@file {f} {",".join(parts)}{rest}')
                continue
            if rng and not RANGE.match(rng) and rng != '-':
                rest, rng = ' ' + rng + rest, None
            ctx = f
            if f not in V2:
                report.append(f'{ch}:{ln} ✗ الملف غير موجود في النسخة 2: {f}')
                out.append(line)
                continue
            n2 = len(lines(V2[f]))
            if f not in V1:
                stats['new-file'] += 1
                report.append(f'{ch}:{ln} ? ملف ليس في 1.0: {f}')
                out.append(line)
                continue
            if not rng or rng == '-':
                stats['whole'] += 1
                same = V1[f] == V2[f]
                cover(f, 1, n2)
                if not same:
                    stats['changed'] += 1
                    report.append(f'{ch}:{ln} ~ الملف كاملاً تغيّر: {f} ({len(lines(V1[f]))}→{n2})')
                out.append(line)
                continue
            r = RANGE.match(rng)
            s = int(r.group(1))
            e = len(lines(V1[f])) if r.group(2) == '$' else int(r.group(2))
            s2, e2, same = map_range(f, s, e)
            if r.group(2) == '$':
                e2 = n2
                same = lines(V1[f])[s - 1:] == lines(V2[f])[s2 - 1:]
            cover(f, s2, e2)
            new_rng = f'{s2}-{"$" if r.group(2) == "$" else e2}'
            stats['same' if same else 'changed'] += 1
            if not same:
                report.append(f'{ch}:{ln} ~ {f} {rng} → {new_rng}')
                report.extend('      ' + d for d in diff_lines(f, s, e, s2, e2))
            out.append(f'@file {f} {new_rng}{rest}')
            continue
        m = re.match(r'^@moved\s+(\S+)\s+(\d+)-(\d+|\$)(.*)$', line)
        if m:
            f = m.group(1)
            if f in V1 and f in V2:
                s = int(m.group(2))
                e = len(lines(V1[f])) if m.group(3) == '$' else int(m.group(3))
                s2, e2, same = map_range(f, s, e)
                if m.group(3) == '$':
                    e2 = len(lines(V2[f]))
                stats['same' if same else 'changed'] += 1
                if not same:
                    report.append(f'{ch}:{ln} ~ @moved {f} {m.group(2)}-{m.group(3)} → {s2}-{e2}')
                out.append(f'@moved {f} {s2}-{"$" if m.group(3) == "$" else e2}{m.group(4)}')
                ctx = f
                continue
        if line.startswith('@stage'):
            ctx = None
            out.append(line)
            continue
        out.append(remap_prose(line, ctx))
    if APPLY:
        open(path, 'w', encoding='utf-8').write('\n'.join(out))

for st in sorted(x for x in os.listdir(os.path.join(BOOK, 'stages')) if x.endswith('.src')):
    path = os.path.join(BOOK, 'stages', st)
    text = open(path, encoding='utf-8').read()

    def sub(m):
        f, s, e = m.group(1), int(m.group(2)), int(m.group(3))
        if f not in V1 or f not in V2:
            report.append(f'stages/{st}: ✗ from={f}:{s}-{e} (الملف غير موجود)')
            return m.group(0)
        s2, e2, same = map_range(f, s, e)
        cover(f, s2, e2)
        stats['same' if same else 'changed'] += 1
        if not same:
            report.append(f'stages/{st} ~ from={f}:{s}-{e} → {s2}-{e2}')
            report.extend('      ' + d for d in diff_lines(f, s, e, s2, e2, 4))
        return f'from={f}:{s2}-{e2}'

    new = re.sub(r'from=([\w./-]+):(\d+)-(\d+)', sub, text)
    if APPLY and new != text:
        open(path, 'w', encoding='utf-8').write(new)

# ------------------------------------------------------------------ ما لا تغطيه أي إشارة
uncovered = []
total_new = 0
for f in sorted(V2):
    if any(re.search(p, f) for p in EXCLUDE):
        continue
    n = len(lines(V2[f]))
    seen = covered.get(f, set())
    miss = [i for i in range(1, n + 1) if i not in seen]
    if not miss:
        continue
    total_new += len(miss)
    regions = []
    start = prev = miss[0]
    for i in miss[1:] + [None]:
        if i is not None and i == prev + 1:
            prev = i
            continue
        regions.append((start, prev))
        if i is not None:
            start = prev = i
    b = lines(V2[f])
    head = f'{f}: {len(miss)}/{n} سطر غير مغطى' + (' (ملف جديد)' if f not in V1 else '')
    uncovered.append(head)
    for s, e in regions[:12]:
        uncovered.append(f'      {s}-{e}: {b[s - 1].strip()[:110]}')
    if len(regions) > 12:
        uncovered.append(f'      … و{len(regions) - 12} مقطعاً آخر')

os.makedirs(os.path.join(BOOK, '.build'), exist_ok=True)
summary = (f"الإشارات: {stats['same']} مطابقة (إزاحة فقط)، {stats['changed']} تغيّر محتواها، {stats['whole']} ملف كامل، "
           f"{stats['new-file']} ملف ليس في 1.0 | أسطر النسخة 2 غير المغطاة: {total_new}")
with open(os.path.join(BOOK, '.build', 'remap-report.txt'), 'w', encoding='utf-8') as fh:
    fh.write(summary + '\n\n== إشارات تغيّر محتواها (تحتاج مراجعة النص)\n' + '\n'.join(report)
             + '\n\n== أسطر غير مغطاة في النسخة 2 (نص جديد أو فصل جديد)\n' + '\n'.join(uncovered) + '\n')
print(summary)
print('التقرير: .build/remap-report.txt' + (' — طُبّقت التعديلات' if APPLY else ' (بلا تعديل؛ --apply للتطبيق)'))
