#!/usr/bin/env python3
"""
ترتيب ملفَّي اللغة php/app/lang/fr.php و en.php حسب فصول الكتاب.

كل نص يوضع في قسم الفصل الذي يظهر فيه **أول مرة** في كود الكتاب (من خريطة التغطية التي يكتبها
build.mjs في .build/line-chapters.json)، فيعرض كل فصل قسمه بالتوجيه «@lang fr» و«@lang en»
والقارئ يبني الملفين قسماً بعد قسم. النصوص نفسها وترجماتها لا تتغير، الترتيب فقط.

  node tools/build.mjs --coverage          (خريطة التغطية أولاً)
  python3 tools/lang_regroup.py            تقرير: عدد النصوص في كل فصل، بلا كتابة
  python3 tools/lang_regroup.py --apply    يكتب fr.php و en.php و tools/lang-sections.json

قواعد التجاوز (OVERRIDES) للفصول التي لم تُكتب بعد أو لأسطر ستنتقل إلى فصل آخر.
"""
import json
import os
import re
import subprocess
import sys

BOOK = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ROOT = os.path.dirname(BOOK)
PHP = os.environ.get('PHP', '/tmp/tools/bin/php')
LANG_DIR = os.path.join(ROOT, 'php', 'app', 'lang')
BIG = 10 ** 6

# (الملف، من، إلى، الفصل) — تُقدَّم على خريطة التغطية
OVERRIDES = [
    ('app/helpers.php', 583, 632, 31),                 # محاولات الدخول ورسالة «محاولات كثيرة» مع RateLimit
    ('app/views/base/footer.php', 1, BIG, 26),         # التذييل النهائي مع القالب العام
    ('app/views/base/navbar.php', 79, 84, 45),         # رابط المسابقات في القائمة
    ('app/views/homepage/index.php', 5, 5, 45),        # competitions.css
    ('app/views/homepage/index.php', 315, 337, 45),    # صندوق «مسابقات توظيف مفتوحة»
    ('app/core/PdfGuard.php', 1, BIG, 46),
    ('app/models/recruitment.php', 1, BIG, 44),
    ('app/controllers/competitions.php', 1, 97, 45),     # الإعلانات: القائمة والتفاصيل والملفات المنشورة
    ('app/controllers/competitions.php', 98, 475, 46),   # الترشح الإلكتروني والوصل
    ('app/controllers/competitions.php', 476, BIG, 47),  # فضاء المترشح: المتابعة والاستكمال والطباعة
] + [('app/views/competitions/%s.php' % v, 1, BIG, 45) for v in ('list', 'detail')] \
  + [('app/views/competitions/%s.php' % v, 1, BIG, 46) for v in ('apply', 'receipt')] \
  + [('app/views/competitions/%s.php' % v, 1, BIG, 47) for v in ('track', 'print_convocation', 'print_result', '_print_open', '_print_close')]

# عنوان قسم كل فصل في ملف اللغة (موضوعه، بلا رقم الفصل: البرنامج مستقل عن الكتاب)
TITLES = {
    18: 'رسائل عامة: الدوال المساعدة ونقطة الدخول',
    19: 'قاعدة البيانات', 20: 'الموجّه', 21: 'القوالب وصفحات الأخطاء', 22: 'النماذج (Model)',
    23: 'نماذج الجداول: الحالات والتسميات', 24: 'المستخدم الحالي', 25: 'النصوص المنسّقة',
    26: 'القالب العام: القائمة والتذييل والإشعارات', 27: 'الصفحة الرئيسية',
    28: 'صفحات المديرية', 29: 'الأخبار والنشاطات', 30: 'الأقسام والإقامات والخدمات',
    31: 'النماذج واتصل بنا', 32: 'التحميلات والأسئلة الشائعة والبحث', 33: 'حسابات الطلبة: التسجيل والدخول',
    34: 'لوحة الطالب والطلبات', 35: 'الإشعارات والملف الشخصي والبطاقة الرقمية', 36: 'لوحة الموظفين (فضاء المشرف)',
    44: 'مسابقات التوظيف: الحالات والتسميات', 45: 'مسابقات التوظيف: الإعلانات',
    46: 'مسابقات التوظيف: الترشح الإلكتروني والوصل', 47: 'مسابقات التوظيف: فضاء المترشح',
}


def chapter_no(name):
    m = re.match(r'(\d+)-', name)
    return int(m.group(1)) if m else 99


def load_coverage():
    path = os.path.join(BOOK, '.build', 'line-chapters.json')
    if not os.path.exists(path):
        sys.exit('شغّل أولاً: node tools/build.mjs --coverage')
    return json.load(open(path, encoding='utf-8'))


def where():
    out = subprocess.run([PHP, '-d', 'extension=tokenizer', os.path.join(ROOT, 'dev', 'i18n_keys.php'), '--where'],
                         capture_output=True, text=True, check=True).stdout
    return json.loads(out)


def php_keys(file):
    code = 'echo json_encode(array_keys(require $argv[1]), JSON_UNESCAPED_UNICODE);'
    return json.loads(subprocess.run([PHP, '-r', code, file], capture_output=True, text=True, check=True).stdout)


def main():
    apply = '--apply' in sys.argv
    cov = load_coverage()
    locs = where()

    # أسماء الأشهر والأيام ووحدات الوقت في قسم «التواريخ» من helpers.php مصفوفات تُترجم هناك مباشرة،
    # لا مفاتيح __() (نفس قاعدة dev/i18n_keys.php)، فلا نعدّها ظهوراً للنص
    hl = open(os.path.join(ROOT, 'php', 'app', 'helpers.php'), encoding='utf-8').read().split('\n')
    dates_line = next(i + 1 for i, l in enumerate(hl) if '// ====' in l and 'التواريخ' in l)

    def loc_chapter(f, line):
        if f == 'app/helpers.php' and line >= dates_line:
            return None
        for of, a, b, c in OVERRIDES:
            if of == f and a <= line <= b:
                return c
        best = None
        for s, e, chap, _kind in cov.get(f, []):
            if s <= line <= e:
                n = chapter_no(chap)
                best = n if best is None else min(best, n)
        return best

    files = {}
    for code in ('fr', 'en'):
        path = os.path.join(LANG_DIR, code + '.php')
        lines = open(path, encoding='utf-8').read().split('\n')
        if lines and lines[-1] == '':
            lines.pop()
        start = lines.index('return [') + 1
        end = len(lines) - 1  # ];
        assert lines[end] == '];', lines[end]
        entries = [l for l in lines[start:end] if l.strip() and not l.strip().startswith('//')]
        keys = php_keys(path)
        assert len(keys) == len(entries), (code, len(keys), len(entries))
        files[code] = {'path': path, 'header': lines[:start], 'entries': list(zip(keys, entries))}
    assert [k for k, _ in files['fr']['entries']] == [k for k, _ in files['en']['entries']], 'fr/en: ترتيب مختلف'

    groups, unknown = {}, []
    for i, (key, _line) in enumerate(files['fr']['entries']):
        chs = [loc_chapter(*(w.rsplit(':', 1)[0], int(w.rsplit(':', 1)[1]))) for w in locs.get(key, [])]
        chs = [c for c in chs if c is not None]
        if not chs:
            unknown.append((key, locs.get(key)))
            continue
        groups.setdefault(max(18, min(chs)), []).append(i)
    if unknown:
        for k, w in unknown:
            print('  ؟ بلا فصل:', k[:60], w)
        sys.exit('نصوص بلا فصل: أضف قاعدة في OVERRIDES')

    for ch in sorted(groups):
        print('الفصل %2d  %3d نصاً  %s' % (ch, len(groups[ch]), TITLES.get(ch, '؟؟ بلا عنوان')))
    missing_titles = [ch for ch in groups if ch not in TITLES]
    if missing_titles:
        sys.exit('أضف عناوين الفصول: %s' % missing_titles)
    if not apply:
        return

    sections = {}
    for code, data in files.items():
        out = list(data['header'])
        for ch in sorted(groups):
            a = len(out) + 1
            out.append('    // ' + '=' * 56 + ' ' + TITLES[ch])
            out.extend(data['entries'][i][1] for i in groups[ch])
            out.append('')
            sections.setdefault(str(ch), {})[code] = [a, len(out)]
        out.append('];')
        open(data['path'], 'w', encoding='utf-8').write('\n'.join(out) + '\n')
        total = len(out)
        # الفصل الأول يكتب رأس الملف وسطر الإغلاق أيضاً
        first = str(min(groups))
        s, e = sections[first][code]
        sections[first][code] = [[1, e], [total, total]]
        for ch in sections:
            if ch != first:
                sections[ch][code] = [sections[ch][code]]
    # المدى بصيغة @file: "1-20,997-997"
    spec = {ch: {code: ','.join('%d-%d' % (a, b) for a, b in parts) for code, parts in v.items()} for ch, v in sections.items()}
    json.dump(spec, open(os.path.join(BOOK, 'tools', 'lang-sections.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print('كُتب fr.php و en.php و tools/lang-sections.json')


if __name__ == '__main__':
    main()
