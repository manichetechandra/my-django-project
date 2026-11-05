#!/usr/bin/env python3
"""
إعادة ترقيم فصول الكتاب للإصدار الثالث (مرة واحدة):
  - أسماء ملفات الفصول (git mv)
  - خطوات المراحل في stages/*.src (@@file و @@block STEP و until= و file=PATH@STEP)
  - خطوات @stage في الفصول
  - الإشارات في النص: «الفصل 25»، «الفصول 31–33»، «الفصلين 6 و11»، «الفصول 36، 37»...

  python3 tools/renumber_v3.py            عرض ما سيتغير
  python3 tools/renumber_v3.py --apply    التطبيق
"""
import os
import re
import subprocess
import sys

BOOK = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
APPLY = '--apply' in sys.argv

# الفصل القديم => الجديد (الجديدة: 18 i18n، 25 richtext، 38 admin-login، 40 translator، 41 editor، 43–47 المسابقات)
MAP = {i: i for i in range(0, 18)}
MAP.update({18: 19, 19: 20, 20: 21, 21: 22, 22: 23, 23: 24, 24: 26, 25: 27, 26: 28, 27: 29, 28: 30, 29: 31,
            30: 32, 31: 33, 32: 34, 33: 35, 34: 36, 35: 37, 36: 39, 37: 42, 38: 48})


def step(s: str) -> str:
    """'36.1' => '39.1'"""
    if s in ('-', '*', ''):
        return s
    m = re.fullmatch(r'(\d+)(\.\d+)?', s)
    if not m:
        return s
    return str(MAP[int(m.group(1))]) + (m.group(2) or '')


changes = []

# ---------------------------------------------------------------- المراحل
for name in sorted(os.listdir(os.path.join(BOOK, 'stages'))):
    p = os.path.join(BOOK, 'stages', name)
    src = open(p, encoding='utf-8').read()
    out = []
    for line in src.split('\n'):
        if line.startswith('@@file '):
            parts = line.split()
            parts[2] = step(parts[2])
            line = ' '.join(parts)
        elif line.startswith('@@block '):
            parts = line.split()
            parts[2] = step(parts[2])
            for i in range(3, len(parts)):
                if parts[i].startswith('until='):
                    parts[i] = 'until=' + step(parts[i][6:])
                elif parts[i].startswith('file='):
                    f, s = parts[i][5:].rsplit('@', 1)
                    parts[i] = 'file=' + f + '@' + step(s)
            line = ' '.join(parts)
        out.append(line)
    new = '\n'.join(out)
    if new != src:
        changes.append(f'stages/{name}')
        if APPLY:
            open(p, 'w', encoding='utf-8').write(new)

# ---------------------------------------------------------------- الإشارات في النص
NUM = r'\d+'
# كلمة «الفصل/الفصول/الفصلين/فصل» ثم قائمة أرقام: 25 | 31–33 | 6 و11 | 36، 37 | 16 و17 و33
REF = re.compile(r'(الفصلين|الفصول|الفصل|فصول|فصل)(\s+)(\d+(?:\s*(?:[–-]|،|و|,)\s*\d+)*)')


def renum_list(s: str) -> str:
    return re.sub(NUM, lambda m: str(MAP.get(int(m.group(0)), int(m.group(0)))), s)


def fix_text(text: str) -> str:
    def rep(m):
        word = m.group(1)
        return word + m.group(2) + renum_list(m.group(3))
    return REF.sub(rep, text)


def fix_stage_directive(line: str) -> str:
    # @stage NAME BLOCK STEP [extra]  |  @stage NAME * STEP [FILE]
    parts = line.split(' ')
    if len(parts) >= 4:
        parts[3] = step(parts[3])
    return ' '.join(parts)


chapters_dir = os.path.join(BOOK, 'chapters')
renames = []
for name in sorted(os.listdir(chapters_dir)):
    p = os.path.join(chapters_dir, name)
    src = open(p, encoding='utf-8').read()
    lines = []
    for line in src.split('\n'):
        if line.startswith('@stage '):
            line = fix_stage_directive(line)
        elif not line.startswith(('@file ', '@moved ', '@shot ')):
            line = fix_text(line)
        elif line.startswith('@moved ') and '|' in line:
            head, note = line.split('|', 1)
            line = head + '|' + fix_text(note)
        elif line.startswith('@shot ') and '|' in line:
            head, cap = line.split('|', 1)
            line = head + '|' + fix_text(cap)
        lines.append(line)
    new = '\n'.join(lines)
    if new != src:
        changes.append(f'chapters/{name}')
        if APPLY:
            open(p, 'w', encoding='utf-8').write(new)
    m = re.match(r'^(\d+)-(.+)$', name)
    if m and MAP[int(m.group(1))] != int(m.group(1)):
        renames.append((name, f'{MAP[int(m.group(1))]:02d}-{m.group(2)}'))

# الأسماء من الأكبر إلى الأصغر حتى لا يصطدم اسم جديد باسم قديم لم يُنقل بعد
for old, new in sorted(renames, key=lambda x: -int(x[0].split('-')[0])):
    changes.append(f'mv {old} -> {new}')
    if APPLY:
        subprocess.run(['git', 'mv', os.path.join('chapters', old), os.path.join('chapters', new)], cwd=BOOK, check=True)

print('\n'.join(changes))
print(f'{len(changes)} تغيير' + ('' if APPLY else ' (عرض فقط؛ --apply للتطبيق)'))
