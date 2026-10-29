#!/usr/bin/env python3
"""يبني php/app/lang/fr.php و php/app/lang/en.php من ترجمات dev/i18n/tr*.py

  python3 dev/i18n/build_lang.py

- keys-v1.tsv  : المفاتيح العربية المرقمة (k001...) التي كُتبت لها الترجمات في tr1..tr4
- tr_extra.py  : ترجمات إضافية مفتاحها النص العربي مباشرة (للنصوص الجديدة)
- يتحقق أن المتغيرات {name} نفسها في العربية والفرنسية والإنجليزية، ويطبع النصوص الناقصة.
"""
import importlib.util
import json
import os
import re
import subprocess
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
PHP = os.environ.get('PHP_BIN', '/tmp/tools/bin/php')


def unescape(s):
    return re.sub(r'\\(.)', lambda m: {'n': '\n', 't': '\t', '\\': '\\'}.get(m.group(1), m.group(0)), s)


def load(name):
    path = os.path.join(HERE, name)
    if not os.path.exists(path):
        return {}
    spec = importlib.util.spec_from_file_location(name[:-3], path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod.T


ids = {}
for line in open(os.path.join(HERE, 'keys-v1.tsv'), encoding='utf-8'):
    kid, _where, text = line.rstrip('\n').split('\t', 2)
    ids[kid] = unescape(text)

by_ar = {}
for n in range(1, 10):
    for kid, pair in load(f'tr{n}.py').items():
        by_ar[ids[kid]] = pair
by_ar.update(load('tr_extra.py'))

out = subprocess.run([PHP, '-d', 'extension=tokenizer', os.path.join(ROOT, 'dev', 'i18n_keys.php'), '--tsv'],
                     capture_output=True, text=True, check=True).stdout
current = []
for line in out.splitlines():
    _kid, where, text = line.split('\t', 2)
    current.append((where.split(':')[0], unescape(text)))

missing, bad = [], []
PH = re.compile(r'\{[a-z_]+\}')
for _f, key in current:
    if key not in by_ar:
        missing.append(key)
        continue
    for lang_text in by_ar[key]:
        if sorted(PH.findall(lang_text)) != sorted(PH.findall(key)):
            bad.append((key, lang_text))


def php_str(s):
    if '\n' in s or '\t' in s:
        return '"' + s.replace('\\', '\\\\').replace('"', '\\"').replace('$', '\\$').replace('\n', '\\n').replace('\t', '\\t') + '"'
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


SECTION = [
    ('app/views/base/', 'القالب العام: القائمة والتذييل والإشعارات'),
    ('app/views/homepage/', 'الصفحة الرئيسية وصفحات المديرية'),
    ('app/views/news/', 'الأخبار'),
    ('app/views/activities/', 'النشاطات'),
    ('app/views/cities/', 'الإقامات الجامعية'),
    ('app/views/departements/', 'الأقسام'),
    ('app/views/services/', 'الخدمات'),
    ('app/views/contact/', 'اتصل بنا'),
    ('app/views/downloads/', 'مركز التحميلات'),
    ('app/views/faq/', 'الأسئلة الشائعة'),
    ('app/views/search/', 'البحث'),
    ('app/views/notifications/', 'شريط الإشعارات'),
    ('app/views/errors/', 'صفحات الأخطاء'),
    ('app/views/students/admin/', 'لوحة المشرف (فضاء الموظفين)'),
    ('app/views/students/', 'فضاء الطالب'),
    ('app/controllers/', 'رسائل المتحكمات (flash وعناوين الصفحات)'),
    ('app/forms.php', 'النماذج: الحقول ورسائل التحقق'),
    ('app/core/', 'التحقق ورفع الملفات'),
    ('app/models/', 'النماذج: الخيارات والإشعارات'),
    ('app/helpers.php', 'رسائل عامة'),
    ('index.php', 'رسائل عامة'),
]


def section(f):
    for prefix, title in SECTION:
        if f.startswith(prefix):
            return title
    return 'أخرى'


def build(code, idx, title, intro):
    groups, seen = {}, set()
    for f, key in current:
        if key in seen or key not in by_ar:
            continue
        seen.add(key)
        groups.setdefault(section(f), []).append(key)
    lines = ['<?php', '/**', f' * {title}', ' * ' + intro,
             ' * النص الذي لا يوجد هنا يظهر بالعربية. المتغيرات بين { } تبقى كما هي.',
             ' * (محتوى قاعدة البيانات — الأخبار والخدمات... — لا يُترجم هنا، بل في حقوله الخاصة من لوحة الإدارة)',
             ' */', 'return [']
    order = [t for _p, t in SECTION]
    for sec in sorted(groups, key=lambda s: order.index(s) if s in order else 99):
        lines.append(f'    // ' + '=' * 56 + f' {sec}')
        for key in groups[sec]:
            lines.append(f'    {php_str(key)} => {php_str(by_ar[key][idx])},')
        lines.append('')
    lines.append('];')
    path = os.path.join(ROOT, 'php', 'app', 'lang', f'{code}.php')
    open(path, 'w', encoding='utf-8').write('\n'.join(lines) + '\n')
    return len(seen)


n = build('fr', 0, 'الترجمة الفرنسية لنصوص الواجهة', "'النص العربي كما هو في القالب' => 'Traduction française'")
build('en', 1, 'الترجمة الإنجليزية لنصوص الواجهة', "'النص العربي كما هو في القالب' => 'English translation'")
print(f'{n} نصاً مترجماً في fr.php و en.php')
if bad:
    print(f'\n⚠ متغيرات غير متطابقة ({len(bad)}):')
    for k, v in bad:
        print(f'   {k!r}\n   → {v!r}')
if missing:
    print(f'\n✗ نصوص بلا ترجمة ({len(missing)}):')
    for k in missing:
        print('   ' + json.dumps(k, ensure_ascii=False))
sys.exit(1 if bad else 0)
