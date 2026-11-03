#!/usr/bin/env python3
"""ملفات PDF تجريبية لاختبار فحص وثائق المترشحين (app/core/PdfGuard.php).

  python3 dev/pdf_samples.py /tmp/pdfs      ← يكتب الملفات في المجلد

ملفات سليمة يجب قبولها (ok_*) وملفات خبيثة أو تالفة يجب رفضها (bad_*).
"""
import os
import random
import sys
import zlib


def build(objects, trailer_extra=b'', header=b'%PDF-1.7\n%\xe2\xe3\xcf\xd3\n'):
    """objects: قائمة نصوص الكائنات (بدون «N 0 obj») — الكائن 1 هو Catalog"""
    out = bytearray(header)
    offsets = []
    for i, body in enumerate(objects, start=1):
        offsets.append(len(out))
        out += b'%d 0 obj\n' % i + body + b'\nendobj\n'
    xref = len(out)
    out += b'xref\n0 %d\n0000000000 65535 f \n' % (len(objects) + 1)
    for off in offsets:
        out += b'%010d 00000 n \n' % off
    out += b'trailer\n<< /Size %d /Root 1 0 R ' % (len(objects) + 1) + trailer_extra + b'>>\nstartxref\n%d\n%%%%EOF\n' % xref
    return bytes(out)


def stream(dict_body, data):
    return b'<< ' + dict_body + b' /Length %d >>\nstream\n' % len(data) + data + b'\nendstream'


def page_objects(content=b'BT /F1 24 Tf 72 720 Td (Diplome - test) Tj ET', extra_catalog=b''):
    return [
        b'<< /Type /Catalog /Pages 2 0 R ' + extra_catalog + b'>>',
        b'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        stream(b'', content),
        b'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ]


def samples():
    rnd = random.Random(2026)
    s = {}
    s['ok_simple.pdf'] = build(page_objects())
    # صورة ممسوحة: بيانات ثنائية عشوائية مضغوطة (2 ميغابايت تقريباً) — يجب ألا تُرفض بالخطأ
    noise = bytes(rnd.getrandbits(8) for _ in range(1_600_000))
    objs = page_objects(content=b'q 595 0 0 842 0 0 cm /Im1 Do Q')
    objs[2] = b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /XObject << /Im1 6 0 R >> >> >>'
    objs.append(stream(b'/Type /XObject /Subtype /Image /Width 800 /Height 666 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode',
                       zlib.compress(noise, 1)))
    s['ok_scan.pdf'] = build(objs)
    # مجرى كائنات مضغوط سليم (مثل ملفات Word الحديثة)
    inner = b'<< /Title (Releve de notes) >>'
    objs = page_objects()
    objs.append(stream(b'/Type /ObjStm /N 1 /First 4 /Filter /FlateDecode', zlib.compress(b'7 0 ' + inner)))
    s['ok_objstm.pdf'] = build(objs)
    # رابط إنترنت عادي (/URI مسموح)
    objs = page_objects()
    objs[2] = objs[2][:-2] + b' /Annots [6 0 R] >>'
    objs.append(b'<< /Type /Annot /Subtype /Link /Rect [0 0 100 20] /A << /S /URI /URI (https://www.mesrs.dz/) >> >>')
    s['ok_link.pdf'] = build(objs)

    # ---------------------------------------------------------------- يجب رفضها
    s['bad_js_openaction.pdf'] = build(page_objects(extra_catalog=b'/OpenAction 6 0 R ') + [b'<< /S /JavaScript /JS (app.alert\\(1\\)) >>'])
    s['bad_js_hex.pdf'] = build(page_objects(extra_catalog=b'/OpenAction 6 0 R ') + [b'<< /S /Java#53cript /J#53 (app.alert\\(1\\)) >>'])
    objs = page_objects(extra_catalog=b'/OpenAction 7 0 R ')
    objs.append(stream(b'/Type /ObjStm /N 1 /First 4 /Filter /FlateDecode', zlib.compress(b'7 0 << /S /JavaScript /JS (app.alert\\(1\\)) >>')))
    s['bad_js_objstm.pdf'] = build(objs)
    s['bad_launch.pdf'] = build(page_objects(extra_catalog=b'/OpenAction << /S /Launch /F (cmd.exe) >> '))
    s['bad_gotor_unc.pdf'] = build(page_objects(extra_catalog=b'/OpenAction << /S /GoToR /F (\\\\\\\\10.0.0.1\\\\share\\\\x.pdf) /D [0 /Fit] >> '))
    s['bad_embedded.pdf'] = build(page_objects(extra_catalog=b'/Names << /EmbeddedFiles << /Names [(x.exe) 6 0 R] >> >> ') + [b'<< /Type /Filespec /F (x.exe) >>'])
    s['bad_aa.pdf'] = build(page_objects(extra_catalog=b'/AA << /WC << /S /URI /URI (https://evil.example/) >> >> '))
    s['bad_xfa.pdf'] = build(page_objects(extra_catalog=b'/AcroForm << /XFA 6 0 R >> ') + [stream(b'', b'<xdp:xdp/>')])
    s['bad_encrypted.pdf'] = build(page_objects() + [b'<< /Filter /Standard /V 2 /R 3 /O <00> /U <00> /P -4 >>'], trailer_extra=b'/Encrypt 6 0 R ')
    # كائن JavaScript مخبّأ داخل «بيانات مجرى» مزيفة يقرؤه القارئ من جدول xref
    hidden = b'<< /Length 200 >>\nstream\n' + b'x' * 10 + b'\n7 0 obj\n<< /S /Java#53cript /J#53 (app.alert\\(1\\)) >>\nendobj\n' + b'y' * 10 + b'\nendstream'
    s['bad_hidden_in_stream.pdf'] = build(page_objects(extra_catalog=b'/OpenAction 7 0 R ') + [hidden])
    # مجرى كائنات بترميز غير مألوف (لتجاوز الفحص)
    objs = page_objects(extra_catalog=b'/OpenAction 7 0 R ')
    objs.append(stream(b'/Type /ObjStm /N 1 /First 4 /Filter /ASCIIHexDecode', b'<< /S /JavaScript >>'.hex().encode() + b'>'))
    s['bad_objstm_hex.pdf'] = build(objs)
    # قنبلة ضغط داخل مجرى كائنات (100 ميغابايت بعد فك الضغط)
    objs = page_objects()
    objs.append(stream(b'/Type /ObjStm /N 1 /First 4 /Filter /FlateDecode', zlib.compress(b' ' * (100 * 1024 * 1024), 9)))
    s['bad_zipbomb.pdf'] = build(objs)
    s['bad_html.pdf'] = b'<html><body><script>alert(document.cookie)</script></body></html>\n%PDF-1.4\n%%EOF\n'
    s['bad_php_polyglot.pdf'] = b'<?php system($_GET["c"]); ?>\n' + build(page_objects())
    s['bad_truncated.pdf'] = build(page_objects())[:-60]
    s['bad_png_renamed.pdf'] = b'\x89PNG\r\n\x1a\n' + bytes(rnd.getrandbits(8) for _ in range(2000))
    s['bad_empty.pdf'] = b''
    return s


if __name__ == '__main__':
    out = sys.argv[1] if len(sys.argv) > 1 else '/tmp/pdfs'
    os.makedirs(out, exist_ok=True)
    for name, data in samples().items():
        with open(os.path.join(out, name), 'wb') as f:
            f.write(data)
    print(f'{len(samples())} ملفاً في {out}')
