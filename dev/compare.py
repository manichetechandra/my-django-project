#!/usr/bin/env python3
"""مقارنة بكسلية بين مجلدي لقطات (من dev/pages.mjs).

  PYTHONPATH=/tmp/booktools/pylib python3 dev/compare.py /tmp/shots/base /tmp/shots/after [/tmp/shots/diff]

لكل صفحة: نسبة البكسلات المختلفة ومستطيل الاختلاف. تُحفظ صور الاختلاف (باللون الأحمر) في المجلد الثالث.
"""
import os
import sys

from PIL import Image, ImageChops

a_dir, b_dir = sys.argv[1], sys.argv[2]
out = sys.argv[3] if len(sys.argv) > 3 else None
if out:
    os.makedirs(out, exist_ok=True)

names = sorted(set(os.listdir(a_dir)) | set(os.listdir(b_dir)))
same = 0
for n in names:
    if not n.endswith('.png'):
        continue
    pa, pb = os.path.join(a_dir, n), os.path.join(b_dir, n)
    if not (os.path.exists(pa) and os.path.exists(pb)):
        print(f'{n:28} ناقصة في {"الأول" if not os.path.exists(pa) else "الثاني"}')
        continue
    A = Image.open(pa).convert('RGB')
    B = Image.open(pb).convert('RGB')
    note = ''
    if A.size != B.size:
        note = f' (المقاس {A.size[1]}→{B.size[1]})'
        w, h = min(A.width, B.width), min(A.height, B.height)
        A, B = A.crop((0, 0, w, h)), B.crop((0, 0, w, h))
    diff = ImageChops.difference(A, B).convert('L').point(lambda v: 255 if v > 24 else 0)
    box = diff.getbbox()
    if not box and not note:
        same += 1
        continue
    count = diff.histogram()[255] if box else 0
    ratio = count / (A.width * A.height) * 100
    print(f'{n:28} {ratio:7.3f}%  مستطيل {box}{note}')
    if out and box:
        red = Image.new('RGB', A.size, (255, 0, 0))
        vis = Image.composite(red, A.point(lambda v: v // 2 + 128), diff)
        vis.save(os.path.join(out, n))
print(f'{same} صفحة متطابقة تماماً')
