#!/usr/bin/env python3
"""أدوات PDF لبناء الكتاب (تحتاج pypdf و pypdfium2):

  outline <file.pdf>                          -> JSON: عدد الصفحات + عناوين الفهرس الجانبي ورقم صفحة كل عنوان
  stamp <body.pdf> <overlay.pdf> <out.pdf>    -> يطبع الترويسة وأرقام الصفحات (overlay) فوق صفحات الكتاب
  png <file.pdf> <outdir> <pages> [scale]     -> يحوّل صفحات إلى صور PNG للمراجعة، مثال: 1,2,10-12
"""
import json
import os
import sys


def outline(path):
    from pypdf import PdfReader
    r = PdfReader(path)
    out = []

    def walk(items, level):
        for it in items:
            if isinstance(it, list):
                walk(it, level + 1)
            else:
                out.append({'level': level, 'title': it.title, 'page': r.get_destination_page_number(it) + 1})

    walk(r.outline, 1)
    print(json.dumps({'pages': len(r.pages), 'outline': out}, ensure_ascii=False))


def stamp(body, overlay, out, meta_json=None):
    from pypdf import PdfReader, PdfWriter
    w = PdfWriter(clone_from=body)
    ov = PdfReader(overlay)
    if len(ov.pages) != len(w.pages):
        raise SystemExit(f'overlay has {len(ov.pages)} pages, body has {len(w.pages)}')
    for page, top in zip(w.pages, ov.pages):
        page.merge_page(top)
        page.compress_content_streams()  # merge_page يكتب المحتوى غير مضغوط
    meta = {}
    if meta_json:
        meta = json.load(open(meta_json, encoding='utf-8')) if os.path.isfile(meta_json) else json.loads(meta_json)
    bookmarks = meta.pop('_outline', None)
    if meta:
        w.add_metadata({'/' + k: v for k, v in meta.items()})
    if bookmarks:
        # إعادة بناء الفهرس الجانبي بعناوين نظيفة بدل عناوين Chromium الملتصقة
        if '/Outlines' in w._root_object:
            del w._root_object['/Outlines']
        parents = {}
        for b in bookmarks:
            lvl = int(b['level'])
            parent = None
            for up in range(lvl - 1, 0, -1):
                if up in parents:
                    parent = parents[up]
                    break
            parents[lvl] = w.add_outline_item(b['title'], int(b['page']) - 1, parent=parent)
            for k in [k for k in parents if k > lvl]:
                del parents[k]
    w.page_mode = '/UseOutlines'
    w.compress_identical_objects(remove_duplicates=True, remove_unreferenced=True)
    with open(out, 'wb') as f:
        w.write(f)
    print(f'{out}: {len(w.pages)} pages, {os.path.getsize(out) / 1e6:.2f} MB')


def png(path, outdir, pages, scale='1.4'):
    import pypdfium2 as pdfium
    doc = pdfium.PdfDocument(path)
    os.makedirs(outdir, exist_ok=True)
    wanted = []
    for part in pages.split(','):
        if '-' in part:
            a, b = part.split('-')
            wanted += range(int(a), int(b) + 1)
        elif part:
            wanted.append(int(part))
    for n in wanted:
        if 1 <= n <= len(doc):
            img = doc[n - 1].render(scale=float(scale)).to_pil()
            target = os.path.join(outdir, f'page-{n:03d}.png')
            img.save(target)
            print(target)


if __name__ == '__main__':
    cmd, *rest = sys.argv[1:]
    {'outline': outline, 'stamp': stamp, 'png': png}[cmd](*rest)
