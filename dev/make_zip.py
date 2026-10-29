#!/usr/bin/env python3
"""الملف المضغوط الجاهز للتحميل من مجلد php/ (ملفات Git فقط، دون الجلسات والسجلات والوثائق الخاصة)

    python3 dev/make_zip.py [php] [dou-setif-php-v2.zip] [dou-setif]

داخل الملف مجلد واحد (dou-setif/) يُنسخ كما هو إلى htdocs أو public_html.
"""
import os
import subprocess
import sys
import time
import zipfile

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
src = sys.argv[1] if len(sys.argv) > 1 else 'php'
out = sys.argv[2] if len(sys.argv) > 2 else 'dou-setif-php-v2.zip'
prefix = sys.argv[3] if len(sys.argv) > 3 else 'dou-setif'
os.chdir(root)
files = subprocess.run(['git', 'ls-files', '-co', '--exclude-standard', '--', src], check=True,
                       capture_output=True, text=True).stdout.split('\n')
files = sorted(f for f in files if f and os.path.isfile(f))
stamp = time.localtime()[:6]
dirs = set()
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    def add_dir(d):
        if d and d not in dirs:
            add_dir(os.path.dirname(d))
            dirs.add(d)
            info = zipfile.ZipInfo(d + '/', stamp)
            info.external_attr = (0o40755 << 16) | 0x10
            z.writestr(info, b'')
    for f in files:
        rel = os.path.relpath(f, src)
        name = prefix + '/' + rel.replace(os.sep, '/')
        add_dir(os.path.dirname(name))
        info = zipfile.ZipInfo(name, stamp)
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        with open(f, 'rb') as fh:
            z.writestr(info, fh.read())
print(f'{out}: {len(files)} ملفاً، {os.path.getsize(out) / 1e6:.2f} MB')
