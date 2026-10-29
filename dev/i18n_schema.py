#!/usr/bin/env python3
"""يضيف أعمدة الترجمة (_fr و _en) إلى schema.sql ويكتب ملف الترقية للقواعد الموجودة.

  python3 dev/i18n_schema.py            ← يعدّل php/database/schema.sql ويكتب php/database/migrations/2026_10_i18n.sql

نفس القائمة موجودة في النماذج (protected static array $translatable) وفي app/admin_config.php.
"""
import os
import re

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'php', 'database')
LANGS = ['fr', 'en']
FIELDS = {
    'sliders': ['title', 'subtitle', 'button_text'],
    'statistics': ['label'],
    'director_words': ['director_name', 'director_title', 'word_title', 'word_content'],
    'organization_structures': ['title', 'description', 'content'],
    'directorate_histories': ['title', 'introduction', 'content'],
    'news': ['title', 'summary', 'content'],
    'news_images': ['caption'],
    'latest_news': ['description'],
    'activities': ['title', 'short_description', 'description', 'location', 'organizer'],
    'activity_images': ['caption'],
    'cites': ['name', 'description', 'address'],
    'cite_images': ['caption'],
    'departements': ['name', 'description', 'head_of_department'],
    'services': ['title', 'short_description', 'detailed_description', 'requirements', 'how_to_apply', 'contact_info'],
    'download_files': ['title', 'description'],
    'faq_categories': ['name'],
    'faqs': ['question', 'answer'],
    'notifications': ['title', 'message', 'link_text'],
    'application_types': ['name', 'description', 'required_documents'],
    'student_notifications': ['title', 'message'],
}
# الفرنسية والإنجليزية أطول من العربية عادة: نوسّع الحقول القصيرة
WIDER = {50: 100, 100: 200, 200: 300, 300: 500, 500: 800}


def col_type(defn, align=False):
    """نوع عمود الترجمة: نفس نوع العمود العربي (أوسع قليلاً للنصوص القصيرة) ويقبل NULL"""
    width = len(re.match(r'\S+\s*', defn).group(0)) if align else 0
    m = re.match(r'VARCHAR\((\d+)\)', defn)
    if m:
        n = int(m.group(1))
        return f'VARCHAR({WIDER.get(n, min(n * 2, 1000))})'.ljust(width - 1) + ' NULL DEFAULT NULL'
    m = re.match(r'(MEDIUMTEXT|LONGTEXT|TEXT)', defn)
    if m:
        return m.group(1).ljust(width - 1) + ' NULL'
    raise ValueError(defn)


schema_path = os.path.join(ROOT, 'schema.sql')
schema = open(schema_path, encoding='utf-8').read()
alters = []
for table, cols in FIELDS.items():
    m = re.search(r'(CREATE TABLE `' + table + r'` \()(.*?)(\n\) ENGINE)', schema, re.S)
    assert m, table
    body = m.group(2)
    adds = []
    for c in cols:
        if re.search(r'\n\s*`' + c + r'_fr` ', body):
            continue  # أضيف سابقاً
        mm = re.search(r'(\n(\s*)`' + c + r'`(\s+)([^\n]*?),?\n)', body)
        assert mm, (table, c)
        indent, defn = mm.group(2), mm.group(4)
        width = len('`' + c + '`') + len(mm.group(3))
        new_lines = ''
        prev = c
        for lg in LANGS:
            name = f'`{c}_{lg}`'
            new_lines += f'{indent}{name.ljust(width - 1)} {col_type(defn, True)},\n'
            adds.append(f'ADD COLUMN `{c}_{lg}` {col_type(defn)} AFTER `{prev}`')
            prev = f'{c}_{lg}'
        line = mm.group(1)
        body = body.replace(line, line + new_lines, 1)
    schema = schema[:m.start(2)] + body + schema[m.end(2):]
    if adds:
        alters.append(f'ALTER TABLE `{table}`\n  ' + ',\n  '.join(adds) + ';')

open(schema_path, 'w', encoding='utf-8').write(schema)
os.makedirs(os.path.join(ROOT, 'migrations'), exist_ok=True)
mig = os.path.join(ROOT, 'migrations', '2026_10_i18n.sql')
if alters:
    open(mig, 'w', encoding='utf-8').write(
        '-- =====================================================================\n'
        '--  ترقية قاعدة بيانات النسخة 1.0 إلى 2.0 (تعدد اللغات)\n'
        '--  يضيف لكل نص يظهر للزوار عمودين: _fr للفرنسية و _en للإنجليزية.\n'
        '--  الأعمدة فارغة (NULL) في البداية: يظهر النص العربي حتى تُضاف الترجمة\n'
        '--  من لوحة الإدارة أو بأداة database/tools/translate_content.php\n'
        '--\n'
        '--  طريقة التطبيق: phpMyAdmin ← قاعدة dou_setif ← Import ← هذا الملف (مرة واحدة فقط)\n'
        '--  (لا تحتاجه إن استوردت schema.sql الجديد: الأعمدة موجودة فيه)\n'
        '-- =====================================================================\n\n'
        'SET NAMES utf8mb4;\n\n' + '\n\n'.join(alters) + '\n')
print(f'{sum(len(c) for c in FIELDS.values())} حقلاً مترجماً في {len(FIELDS)} جدولاً'
      + (f' — كُتب {mig}' if alters else ' — schema.sql محدّث مسبقاً'))
