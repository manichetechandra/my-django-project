#!/usr/bin/env python3
"""شجرة ملفات المشروع للملحق أ، مع الفصول التي تكتب كل ملف.

    node tools/build.mjs --coverage      # أولاً: يكتب .build/files-chapters.json لكل الفصول
    python3 tools/mktree.py > /tmp/tree.txt

ثم انسخ الناتج إلى كتلة الشجرة في chapters/A-files.md. أي ملف يظهر أمامه «???» لم يكتبه أي فصل:
إما أن البناء كان جزئياً (--only يكتب الملف لفصله وحده)، وإما أن الملف جديد في php/ ويحتاج فصلاً أو ملاحظة في NOTES.
"""
import json, os, re
BOOK=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ROOT=os.path.join(os.path.dirname(BOOK),'php')
d=json.load(open(os.path.join(BOOK,'.build','files-chapters.json'),encoding='utf-8'))
AR={'B':'ب','C':'ج','A':'أ'}
def chnum(c):
    m=re.match(r'^(\d+)',c)
    return int(m.group(1)) if m else c[0]
def label(chs):
    nums=[chnum(c) for c in chs]
    ints=sorted(n for n in nums if isinstance(n,int))
    lets=[n for n in nums if not isinstance(n,int)]
    parts=[]
    i=0
    while i<len(ints):
        j=i
        while j+1<len(ints) and ints[j+1]==ints[j]+1: j+=1
        if j-i>=2: parts.append(f"{ints[i]}–{ints[j]}")
        elif j==i+1: parts += [str(ints[i]), str(ints[j])]
        else: parts.append(str(ints[i]))
        i=j+1
    out=[]
    if parts:
        out.append(('الفصل ' if len(parts)==1 and '–' not in parts[0] else 'الفصول ')+'، '.join(parts))
    for l in lets: out.append('الملحق '+AR[l])
    return '، '.join(out)
NOTES={
 'README.md':'دليل التثبيت والاستعمال المختصر',
 'SECURITY.md':'ملخص الحماية وقائمة النشر',
 'database/data.sql':'بيانات الموقع الأصلي (الملحق ب)',
}
COLLAPSE={
 'assets/vendor/tinymce-8.10.1':'محرر TinyMCE كما نُشر، تنسخه (الفصل 41)',
 'assets/js/tinymce-lang':'العربية لكل إصدار من TinyMCE، تنسخه (الفصل 41)',
}
def note(rel):
    if rel in d and d[rel]['chapters']: return label(d[rel]['chapters'])
    if rel in NOTES: return NOTES[rel]
    if rel.endswith('.gitkeep'): return 'يُبقي المجلد الفارغ'
    if re.search(r'\.jpe?g$',rel): return 'صورة من الموقع الأصلي'
    return '???'
def key(name, isdir):
    return (0 if isdir else 1, name.lower().lstrip('.') if False else name.lower())
lines=['dou-setif/']
COL=44
def pad(t):
    return t.ljust(COL) if len(t)<COL else t+' '
def walk(rel, prefix):
    full=os.path.join(ROOT,rel) if rel else ROOT
    entries=os.listdir(full)
    dirs=sorted([e for e in entries if os.path.isdir(os.path.join(full,e))], key=str.lower)
    files=sorted([e for e in entries if not os.path.isdir(os.path.join(full,e))], key=lambda s:(not s.startswith('.'), s.lower()))
    items=[(e,True) for e in dirs]+[(e,False) for e in files]
    for i,(e,isdir) in enumerate(items):
        last=i==len(items)-1
        r=(rel+'/'+e) if rel else e
        conn='└── ' if last else '├── '
        name=e+('/' if isdir else '')
        text=prefix+conn+name
        if isdir and r in COLLAPSE:
            lines.append(pad(text)+'← '+COLLAPSE[r]); continue
        if isdir:
            lines.append(text)
            walk(r, prefix+('    ' if last else '│   '))
        else:
            lines.append(pad(text)+'← '+note(r))
walk('', '')
print('\n'.join(lines))
