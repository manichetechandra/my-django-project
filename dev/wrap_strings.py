#!/usr/bin/env python3
"""لف النصوص العربية الثابتة في القوالب بدالة الترجمة t().

  python3 dev/wrap_strings.py php/app/views/news/*.php            ← عرض ما سيتغير
  python3 dev/wrap_strings.py --write php/app/views/news/*.php    ← تطبيق

  نص بين الوسوم:        <h2>جميع الأخبار</h2>      → <h2><?= t('جميع الأخبار') ?></h2>
  الخصائص:             placeholder="ابحث..."        → placeholder="<?= t('ابحث...') ?>"
  نصوص JavaScript:      alert('تم الحفظ')            → alert(<?= js_t('تم الحفظ') ?>)

ما لا يمكن لفه آلياً (نص عربي داخل كود PHP، أو نص ملاصق لمتغير) يُطبع للمراجعة اليدوية.
"""
import html
import re
import sys

AR = re.compile(r'[\u0600-\u06FF]')
TOKEN = re.compile(r'(<\?(?:php|=)?.*?(?:\?>|$)|<!--.*?-->|<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<[a-zA-Z/!][^<>]*(?:<\?.*?\?>[^<>]*)*>)', re.S)
ATTRS = ('placeholder', 'title', 'alt', 'value', 'aria-label', 'content', 'data-original-title', 'data-confirm', 'label')
report = []


def php_str(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


def wrap_text(text, ctx, prev, nxt):
    if not AR.search(text):
        return text
    m = re.match(r'^(\s*)(.*?)(\s*)$', text, re.S)
    lead, core, trail = m.groups()
    # نص ملاصق لوسم PHP يطبع متغيراً: جملة مقسومة تحتاج مراجعة
    if (prev.startswith('<?=') and not lead) or (nxt.startswith('<?=') and not trail):
        report.append(f'{ctx}: نص ملاصق لمتغير: {core[:70]!r}')
    core_one = html.unescape(re.sub(r'\s+', ' ', core))
    return f"{lead}<?= t({php_str(core_one)}) ?>{trail}"


def wrap_tag(tag, ctx):
    if not AR.search(tag) or tag.startswith('<!'):
        return tag

    def attr(m):
        name, q, val = m.group(1), m.group(2), m.group(3)
        if name.lower() not in ATTRS or not AR.search(val):
            return m.group(0)
        if '<?' in val:
            report.append(f'{ctx}: خاصية فيها PHP: {name}={val[:70]!r}')
            return m.group(0)
        if name.lower() == 'value' and not re.search(r'type=["\']?(submit|button|reset)', tag):
            report.append(f'{ctx}: value عربية (تحقق): {val[:60]!r}')
            return m.group(0)
        return f'{name}={q}<?= t({php_str(html.unescape(val))}) ?>{q}'

    out = re.sub(r'([a-zA-Z-]+)=(["\'])((?:<\?.*?\?>|(?!\2).)*)\2', attr, tag, flags=re.S)
    if AR.search(re.sub(r'<\?.*?\?>', '', out)) and out == tag:
        report.append(f'{ctx}: وسم فيه عربي لم يُلف: {tag[:90]!r}')
    return out


def wrap_script(block, ctx):
    head_end = block.index('>') + 1
    head, body, tail = block[:head_end], block[head_end:-9], block[-9:]

    def lit(m):
        q, s = m.group(1), m.group(2)
        if not AR.search(s):
            return m.group(0)
        if '<?' in s or '${' in s or '\\' in s:
            report.append(f'{ctx}: نص JS يحتاج مراجعة: {s[:70]!r}')
            return m.group(0)
        return f'<?= js_t({php_str(s)}) ?>'

    # نتجنب وسوم PHP الموجودة أصلاً داخل السكربت
    parts = re.split(r'(<\?.*?\?>)', body, flags=re.S)
    for i in range(0, len(parts), 2):
        parts[i] = re.sub(r'(["\'])((?:\\.|(?!\1)[^\n])*)\1', lit, parts[i])
        for t in re.findall(r'`[^`]*`', parts[i]):
            if AR.search(t):
                report.append(f'{ctx}: قالب JS بعلامة ` فيه عربي: {t[:70]!r}')
    for i in range(1, len(parts), 2):
        if AR.search(parts[i]):
            report.append(f'{ctx}: كود PHP داخل سكربت فيه عربي: {parts[i][:70]!r}')
    return head + ''.join(parts) + tail


def convert(text, name):
    tokens = TOKEN.split(text)
    out = []
    for i, tok in enumerate(tokens):
        if not tok:
            out.append(tok)
            continue
        line = text.count('\n', 0, sum(len(t) for t in tokens[:i])) + 1
        ctx = f'{name}:{line}'
        if tok.startswith('<?'):
            if AR.search(re.sub(r'(//|#)[^\n]*|/\*.*?\*/', '', tok, flags=re.S)) and not re.match(r'<\?(php)?\s*(block|extend|endblock|include_view)\b', tok):
                report.append(f'{ctx}: نص عربي داخل PHP: {tok[:90]!r}')
            out.append(tok)
        elif tok.startswith('<!--'):
            out.append(tok)
        elif tok.startswith('<script'):
            out.append(wrap_script(tok, ctx))
        elif tok.startswith('<style'):
            if re.search(r'content\s*:\s*["\'][^"\']*[\u0600-\u06FF]', tok):
                report.append(f'{ctx}: CSS content بالعربية')
            out.append(tok)
        elif tok.startswith('<'):
            out.append(wrap_tag(tok, ctx))
        else:
            prev = next((t for t in reversed(tokens[:i]) if t), '')
            nxt = next((t for t in tokens[i + 1:] if t), '')
            out.append(wrap_text(tok, ctx, prev, nxt))
    return ''.join(out)


def main():
    write = '--write' in sys.argv
    files = [a for a in sys.argv[1:] if not a.startswith('--')]
    total = 0
    for f in files:
        src = open(f, encoding='utf-8').read()
        dst = convert(src, f)
        n = dst.count('<?= t(') - src.count('<?= t(') + dst.count('js_t(') - src.count('js_t(')
        if dst != src:
            total += n
            print(f'{f}: {n} نص')
            if write:
                open(f, 'w', encoding='utf-8').write(dst)
    print(f'المجموع: {total} نص')
    if report:
        print(f'\nللمراجعة اليدوية ({len(report)}):')
        for r in report:
            print('  ' + r)


if __name__ == '__main__':
    main()
