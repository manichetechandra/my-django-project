#!/usr/bin/env python3
"""تحويل CSS المكتوب للعربية (right/left) إلى خصائص منطقية تعمل في الاتجاهين.

  python3 dev/logical_css.py php/app/views/**/*.php           ← عرض ما سيتغير فقط
  python3 dev/logical_css.py --write php/app/views/**/*.php   ← تطبيق التغييرات

في صفحة عربية (dir=rtl) تعطي الخصائص المنطقية نفس النتيجة تماماً:
  margin-right  → margin-inline-start      (البداية = اليمين في العربية، واليسار في الفرنسية)
  right: 10px   → inset-inline-start: 10px
  text-align: right → text-align: start
  float: right  → float: right; float: inline-start   (المتصفحات القديمة تأخذ الأولى)
ويُطبع ما يحتاج مراجعة يدوية (translateX، box-shadow، left: 50%...).
"""
import difflib
import re
import sys

RENAME = {
    'margin-right': 'margin-inline-start', 'margin-left': 'margin-inline-end',
    'padding-right': 'padding-inline-start', 'padding-left': 'padding-inline-end',
    'border-right': 'border-inline-start', 'border-left': 'border-inline-end',
    'border-right-width': 'border-inline-start-width', 'border-left-width': 'border-inline-end-width',
    'border-right-color': 'border-inline-start-color', 'border-left-color': 'border-inline-end-color',
    'border-right-style': 'border-inline-start-style', 'border-left-style': 'border-inline-end-style',
    'border-top-right-radius': 'border-start-start-radius', 'border-top-left-radius': 'border-start-end-radius',
    'border-bottom-right-radius': 'border-end-start-radius', 'border-bottom-left-radius': 'border-end-end-radius',
    'right': 'inset-inline-start', 'left': 'inset-inline-end',
}
FRAME = re.compile(r'^\s*(from|to|[\d.]+%)(\s*,\s*(from|to|[\d.]+%))*\s*$')
PHP = re.compile(r'<\?.*?\?>', re.S)

manual = []


def split_top(text, sep):
    """تقسيم مع احترام الأقواس وعلامات التنصيص ووسوم PHP"""
    out, depth, buf, i, q = [], 0, '', 0, None
    while i < len(text):
        if text.startswith('<?', i):
            j = text.find('?>', i)
            j = len(text) if j < 0 else j + 2
            buf += text[i:j]
            i = j
            continue
        c = text[i]
        if q:
            if c == q:
                q = None
        elif c in '"\'':
            q = c
        elif c == '(':
            depth += 1
        elif c == ')':
            depth -= 1
        elif depth == 0 and (c == sep if sep != ' ' else c.isspace()):
            out.append(buf)
            buf = ''
            i += 1
            continue
        buf += c
        i += 1
    out.append(buf)
    return out


def values(v):
    return [x for x in split_top(v.strip(), ' ') if x]


def convert_block(body, where):
    """body = محتوى { ... } أو قيمة style="..." """
    parts = split_top(body, ';')
    # عنصر مفروض عليه اتجاه آخر (direction: ltr) نتركه كما هو
    has_direction = any(re.match(r'\s*direction\s*:(?!\s*rtl)', p) for p in parts)
    centered = any(re.match(r'\s*left\s*:\s*50%', p) for p in parts) or 'translateX(-50%)' in body
    out = []
    for p in parts:
        m = re.match(r'^((?:\s|/\*.*?\*/)*)([-a-zA-Z]+)(\s*:\s*)(.*?)(\s*)$', p, re.S)
        if not m:
            out.append(p)
            continue
        lead, prop, colon, val, trail = m.groups()
        low = prop.lower()
        imp = ''
        mv = re.match(r'^(.*?)(\s*!important)$', val, re.S)
        if mv:
            val, imp = mv.groups()
        new = None
        if low == 'direction' and val.strip().lower() == 'rtl':
            # الاتجاه يأتي من <html dir="..."> — نحذف التصريح (ونُبقي أي تعليق قبله)
            out.append(lead.rstrip() if '/*' in lead else None)
            continue
        elif low in ('float', 'clear') and val.strip().lower() in ('right', 'left'):
            logical = 'inline-start' if val.strip().lower() == 'right' else 'inline-end'
            new = f'{lead}{prop}{colon}{val}{imp}; {prop}{colon}{logical}{imp}{trail}'
        elif low == 'text-align' and val.strip().lower() in ('right', 'left'):
            if has_direction:
                manual.append(f'{where}: text-align مع direction (تُرك): {p.strip()}')
            else:
                new = f'{lead}{prop}{colon}{"start" if val.strip().lower() == "right" else "end"}{imp}{trail}'
        elif low in ('left', 'right') and centered:
            manual.append(f'{where}: توسيط (تُرك): {p.strip()}')
        elif low in RENAME:
            new = f'{lead}{RENAME[low]}{colon}{val}{imp}{trail}'
        elif low in ('margin', 'padding', 'border-width', 'border-color', 'border-style', 'inset'):
            vs = values(val)
            if len(vs) == 4 and vs[1] != vs[3]:
                if low in ('margin', 'padding', 'inset'):
                    b, i_ = (f'{low}-block', f'{low}-inline') if low != 'inset' else ('inset-block', 'inset-inline')
                else:
                    kind = low.split('-')[1]
                    b, i_ = f'border-block-{kind}', f'border-inline-{kind}'
                new = f'{lead}{b}{colon}{vs[0]} {vs[2]}{imp}; {i_}{colon}{vs[1]} {vs[3]}{imp}{trail}'
        elif low == 'border-radius' and '/' not in val:
            vs = values(val)
            if len(vs) == 2:
                tl, tr, br, bl = vs[0], vs[1], vs[0], vs[1]
            elif len(vs) == 3:
                tl, tr, br, bl = vs[0], vs[1], vs[2], vs[1]
            elif len(vs) == 4:
                tl, tr, br, bl = vs
            else:
                tl = tr = br = bl = None
            if tl is not None and (tl != tr or bl != br):
                new = (f'{lead}border-start-start-radius{colon}{tr}{imp}; border-start-end-radius{colon}{tl}{imp}; '
                       f'border-end-start-radius{colon}{br}{imp}; border-end-end-radius{colon}{bl}{imp}{trail}')
        elif low in ('transform',) and re.search(r'translateX\(\s*-?[1-9]', val) and 'translateX(-50%)' not in val:
            manual.append(f'{where}: إزاحة أفقية: {p.strip()}')
        elif low in ('box-shadow', 'text-shadow') and re.match(r'\s*-?[1-9]', val):
            manual.append(f'{where}: ظل أفقي: {p.strip()}')
        elif low in ('background-position', 'transform-origin') and re.search(r'\b(left|right)\b', val):
            manual.append(f'{where}: موضع أفقي: {p.strip()}')
        out.append(p if new is None else new)
    return ';'.join(p for p in out if p is not None)


def convert_css(css, where):
    def rule(m):
        sel, body = m.group(1), m.group(2)
        if FRAME.match(sel.split('}')[-1]):
            return m.group(0)  # إطارات @keyframes
        return sel + '{' + convert_block(body, f'{where} {sel.strip()[:40]!r}') + '}'
    return re.sub(r'([^{}]*)\{([^{}]*)\}', rule, css)


def convert_file(text, name):
    text = re.sub(r'(<style[^>]*>)(.*?)(</style>)', lambda m: m.group(1) + convert_css(m.group(2), name) + m.group(3), text, flags=re.S)
    text = re.sub(r'(\sstyle=")((?:<\?.*?\?>|[^"])*)(")', lambda m: m.group(1) + convert_block(m.group(2), f'{name} style=""') + m.group(3), text)
    text = re.sub(r"(\sstyle=')((?:<\?.*?\?>|[^'])*)(')", lambda m: m.group(1) + convert_block(m.group(2), f"{name} style=''") + m.group(3), text)
    return text


def main():
    write = '--write' in sys.argv
    files = [a for a in sys.argv[1:] if not a.startswith('--')]
    changed = 0
    for f in files:
        src = open(f, encoding='utf-8').read()
        dst = convert_file(src, f)
        if dst != src:
            changed += 1
            n = sum(1 for l in difflib.unified_diff(src.splitlines(), dst.splitlines(), lineterm='', n=0) if l.startswith('-') and not l.startswith('---'))
            print(f'{f}: {n} سطر')
            if write:
                open(f, 'w', encoding='utf-8').write(dst)
    print(f'{changed} ملف {"عُدِّل" if write else "سيتغير"}')
    if manual:
        print('\nللمراجعة اليدوية:')
        for m in manual:
            print('  ' + m)


if __name__ == '__main__':
    main()
