#!/usr/bin/env python3
"""النصوص العربية المتبقية في القوالب خارج t() و __() و js_t() والتعليقات.
  python3 dev/ar_left.py php/app/views/**/*.php
"""
import re
import sys

AR = re.compile(r'[\u0600-\u06FF]')
STR = r"'(?:\\.|[^'\\])*'|\"(?:\\.|[^\"\\])*\""
for f in sys.argv[1:]:
    s = open(f, encoding='utf-8').read()
    s = re.sub(r'<!--.*?-->', lambda m: '\n' * m.group(0).count('\n'), s, flags=re.S)
    s = re.sub(r'/\*.*?\*/', lambda m: '\n' * m.group(0).count('\n'), s, flags=re.S)
    s = re.sub(r'(?<![:\'"])//[^\n]*', '', s)
    s = re.sub(r'(?:\b__|\bt|\bjs_t)\(\s*(?:' + STR + ')', '', s)
    for i, line in enumerate(s.split('\n'), 1):
        if AR.search(line):
            print(f'{f}:{i}: {line.strip()[:150]}')
