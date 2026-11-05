#!/usr/bin/env python3
"""اختبارات أمان قسم «المسابقات» (الترشح الإلكتروني) في النسخة 2.0.

  python3 dev/recruitment_check.py [http://127.0.0.1:8002] [/tmp/site2]

يحتاج الموقع التجريبي (bash dev/demo-site.sh). ينشئ مسابقة اختبار خاصة به ثم يحذفها مع طلباتها في النهاية،
ويعيد صلاحيات الحسابات كما كانت، ويمسح عدادات المحاولات (storage/ratelimit) قبل وبعد.
"""
import glob
import hashlib
import http.client
import io
import json
import os
import re
import subprocess
import sys
import time
import urllib.parse
import uuid
import zipfile

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from pdf_samples import samples  # noqa: E402

ARGS = [a for a in sys.argv[1:] if not a.startswith('--')]
BASE = ARGS[0] if len(ARGS) > 0 else 'http://127.0.0.1:8002'
SITE = ARGS[1] if len(ARGS) > 1 else '/tmp/site2'
PHP = os.environ.get('PHP_BIN', '/tmp/tools/bin/php')
DB_NAME = os.environ.get('DB_NAME', 'dou_setif2')
U = urllib.parse.urlparse(BASE)
ADMIN = ('miloudsellam', 'AdminDemo2026')
STAFF = ('hicham41', 'StaffDemo2026')
STUDENT = ('1234566', 'Talib2026x')
PDF = samples()
failures = 0


def check(cond, label):
    global failures
    print(('  ✓ ' if cond else '  ✗ ') + label)
    if not cond:
        failures += 1


def php(code):
    out = subprocess.run([PHP, '-r', 'require "app/bootstrap.php"; ' + code], cwd=SITE, capture_output=True, text=True,
                         env={**os.environ, 'DB_NAME': DB_NAME})
    if out.returncode != 0:
        print(out.stderr[-800:])
    return out.stdout.strip()


def q(sql, params=None):
    """SELECT → قائمة صفوف (JSON)"""
    return json.loads(php(f'echo json_encode(DB::all({sql!r}, {json.dumps(params or [])}));') or '[]')


def x(sql, params=None):
    php(f'DB::query({sql!r}, json_decode({json.dumps(json.dumps(params or []))}, true));')


def clear_ratelimit():
    for f in glob.glob(os.path.join(SITE, 'storage', 'ratelimit', '*.json')):
        os.remove(f)


def enc_files():
    return sorted(glob.glob(os.path.join(SITE, 'storage', 'private', 'recruitment', '*', '*.enc')))


class Client:
    def __init__(self):
        self.cookies = {}

    def req(self, method, path, data=None, files=None, headers=None):
        c = http.client.HTTPConnection(U.hostname, U.port or 80, timeout=60)
        h = {'Host': U.netloc}
        if self.cookies:
            h['Cookie'] = '; '.join(f'{k}={v}' for k, v in self.cookies.items())
        body = None
        if files is not None:
            boundary = 'b' + uuid.uuid4().hex
            parts = []
            for k, v in (data or {}).items():
                for item in (v if isinstance(v, list) else [v]):
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{item}\r\n'.encode())
            for k, (fname, content, ctype) in files.items():
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{fname}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + content + b'\r\n')
            body = b''.join(parts) + f'--{boundary}--\r\n'.encode()
            h['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        elif data is not None:
            body = urllib.parse.urlencode(data, doseq=True)
            h['Content-Type'] = 'application/x-www-form-urlencoded'
        h.update(headers or {})
        c.request(method, path, body, h)
        r = c.getresponse()
        raw = r.read()
        hdrs = {}
        for k, v in r.getheaders():
            hdrs.setdefault(k.lower(), []).append(v)
            if k.lower() == 'set-cookie':
                name, _, rest = v.partition('=')
                self.cookies[name] = rest.split(';')[0]
        return r.status, {k: ', '.join(v) for k, v in hdrs.items()}, raw

    def text(self, method, path, **kw):
        s, h, raw = self.req(method, path, **kw)
        return s, h, raw.decode('utf-8', 'replace')

    def token(self, path):
        _s, _h, b = self.text('GET', path)
        m = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', b)
        return (m.group(1) if m else ''), b

    def login(self, user, password, path='/admin/login/'):
        t, _ = self.token(path)
        return self.text('POST', path, data={'csrfmiddlewaretoken': t, 'username': user, 'password': password})


def form_token(client, cid, age=10):
    """رمز وقت فتح الاستمارة كأنها فُتحت قبل $age ثانية (بدل الانتظار 3 ثوانٍ في كل اختبار)"""
    sid = client.cookies.get('dou_session', '')
    return php(f"$t = time() - {age}; echo $t . '.' . substr(Crypto::sign('apply|{cid}|' . $t . '|{sid}'), 0, 32);")


def location(h):
    return h.get('location', '')


# ----------------------------------------------------------------------------------------------
clear_ratelimit()
old_perm = q("SELECT can_recruit FROM users WHERE username = 'hicham41'")[0]['can_recruit']
x("UPDATE users SET can_recruit = 0 WHERE username = 'hicham41'")
setup = php(r"""
$c = Competition::create(['title' => 'مسابقة اختبار الأمان', 'opens_at' => date('Y-m-d', strtotime('-1 day')), 'closes_at' => date('Y-m-d', strtotime('+5 days')),
    'conditions' => 'شرط', 'status' => 'published', 'online_apply' => 1]);
$p1 = CompetitionPost::create(['competition_id' => (int) $c['id'], 'title' => 'رتبة اختبار', 'positions' => 2]);
$r1 = CompetitionRequirement::create(['competition_id' => (int) $c['id'], 'label' => 'وثيقة إلزامية', 'is_required' => 1, 'order' => 1]);
$r2 = CompetitionRequirement::create(['competition_id' => (int) $c['id'], 'label' => 'وثيقة اختيارية', 'is_required' => 0, 'order' => 2]);
$d = Competition::create(['title' => 'مسودة سرية للاختبار', 'opens_at' => date('Y-m-d'), 'closes_at' => date('Y-m-d', strtotime('+5 days')), 'conditions' => 'x', 'status' => 'draft']);
$df = CompetitionFile::create(['competition_id' => (int) $d['id'], 'title' => 'ملف مسودة', 'kind' => 'announcement', 'file' => 'competitions/none.pdf']);
$o = Competition::create(['title' => 'مسابقة أخرى للاختبار', 'opens_at' => date('Y-m-d'), 'closes_at' => date('Y-m-d', strtotime('+5 days')), 'conditions' => 'x', 'status' => 'published']);
$other = CompetitionPost::create(['competition_id' => (int) $o['id'], 'title' => 'رتبة مسابقة أخرى', 'positions' => 1]);
echo json_encode(['cid' => (int) $c['id'], 'slug' => $c['slug'], 'post' => (int) $p1['id'], 'r1' => (int) $r1['id'], 'r2' => (int) $r2['id'],
    'draft' => $d['slug'], 'draft_id' => (int) $d['id'], 'draft_file' => (int) $df['id'], 'other_id' => (int) $o['id'], 'other_post' => (int) $other['id']]);
""")
T = json.loads(setup)
CID, SLUG = T['cid'], T['slug']
APPLY = f'/competitions/{SLUG}/apply/'

VALID = {
    'post_id': str(T['post']), 'last_name': 'بن يحيى', 'first_name': 'مريم', 'last_name_latin': 'Benyahia', 'first_name_latin': 'meriem',
    'gender': 'F', 'birth_date': '1997-04-12', 'birth_place': 'سطيف', 'nin': '109970412000999111', 'phone': '0661987654',
    'email': 'Meriem.B@example.dz', 'address': 'حي 1014 مسكن، سطيف', 'wilaya': 'سطيف', 'national_service': 'na',
    'diploma': 'ليسانس في الحقوق', 'specialty': 'قانون عام', 'graduation_year': '2019', 'experience_years': '2',
    'declaration': '1', 'consent': '1',
}


def submit(data=None, files=None, client=None, ft=True, fname='diplome.pdf', pdf=PDF['ok_simple.pdf'], extra=None, slug=None, cid=None, keep_rl=False):
    if not keep_rl:
        clear_ratelimit()  # عدّاد المحاولات يُختبر وحده في القسم 7
    c = client or Client()
    path = f'/competitions/{slug or SLUG}/apply/'
    t, _ = c.token(path)
    d = dict(VALID if data is None else data)
    d['csrfmiddlewaretoken'] = t
    d['website_url'] = ''
    if ft:
        d['_ft'] = form_token(c, cid or CID)
    d.update(extra or {})
    f = {f'doc_{T["r1"]}': (fname, pdf, 'application/pdf')} if files is None else files
    s, h, b = c.text('POST', path, data=d, files=f)
    return c, s, h, b


def app_count():
    return int(q('SELECT COUNT(*) n FROM competition_applications WHERE competition_id = ?', [CID])[0]['n'])


# ----------------------------------------------------------------------------------------------
print('1) الصفحات العامة: المسودات مخفية')
s, _h, b = Client().text('GET', '/competitions/')
check(s == 200 and 'مسابقة اختبار الأمان' in b and 'مسودة سرية للاختبار' not in b, 'قائمة المسابقات تعرض المنشورة فقط')
s, _h, _b = Client().text('GET', f'/competitions/{T["draft"]}/')
check(s == 404, f'صفحة مسابقة مسودة للزائر ← 404 ({s})')
s, _h, _b = Client().text('GET', f'/competitions/file/{T["draft_file"]}/')
check(s == 404, f'ملف مسابقة مسودة للزائر ← 404 ({s})')
s, _h, b = Client().text('GET', APPLY)
check(s == 200 and 'name="_ft"' in b and 'name="website_url"' in b and 'enctype="multipart/form-data"' in b, 'استمارة الترشح: رمز الوقت وحقل الفخ موجودان')
s, h, _b = Client().text('GET', APPLY)
check('no-store' in h.get('cache-control', '') and 'noindex' in h.get('x-robots-tag', ''), 'الاستمارة: no-store و noindex')
https = php("$GLOBALS['__config']['recruitment']['require_https'] = true; $out = [];"
            "foreach ([['41.1.2.3', 'dou-setif.dz', null], ['41.1.2.3', 'dou-setif.dz', 'on'], ['127.0.0.1', 'localhost', null]] as [$ip, $host, $tls]) {"
            "  $_SERVER['REMOTE_ADDR'] = $ip; $_SERVER['HTTP_HOST'] = $host; unset($_SERVER['HTTPS']); if ($tls) { $_SERVER['HTTPS'] = $tls; }"
            "  $out[] = recruit_https_ok(); } echo json_encode($out);")
check(https == '[false,true,true]', f'الترشح يتطلب HTTPS في الاستضافة (http://… مرفوض، https://… مقبول، localhost للتطوير) {https}')

# ----------------------------------------------------------------------------------------------
print('2) ترشح صحيح: تشفير المعطيات والوثائق')
before = set(enc_files())
optional = PDF['ok_scan.pdf']
c, s, h, b = submit(files={f'doc_{T["r1"]}': ('شهادة الليسانس.pdf', PDF['ok_simple.pdf'], 'application/pdf'),
                           f'doc_{T["r2"]}': ('scan.PDF', optional, 'application/pdf')})
check(s == 302 and location(h).endswith('/competitions/receipt/'), f'الإرسال ← الوصل ({s} {location(h)})')
s, h, b = c.text('GET', '/competitions/receipt/')
codes = re.findall(r'class="cmp-code" id="cmp-(tracking|secret)">([^<]+)<', b)
codes = dict(codes)
TRACK, SECRET = codes.get('tracking', ''), codes.get('secret', '')
check(s == 200 and re.match(r'^C\d{2}-[2-9A-HJKMNP-Z]{8}$', TRACK) and re.match(r'^[2-9A-HJKMNP-Z]{5}-[2-9A-HJKMNP-Z]{5}$', SECRET), f'الوصل يعرض رقم المتابعة {TRACK} والرمز السري')
check('no-store' in h.get('cache-control', '') and h.get('referrer-policy') == 'no-referrer', 'الوصل: no-store و Referrer-Policy: no-referrer')
row = q('SELECT * FROM competition_applications WHERE tracking_code = ?', [TRACK])[0]
raw_row = json.dumps(row, ensure_ascii=False)
check(VALID['nin'] not in raw_row and VALID['phone'] not in raw_row and 'example.dz' not in raw_row.lower() and 'حي 1014' not in raw_row,
      'قاعدة البيانات: لا رقم تعريف ولا هاتف ولا بريد ولا عنوان بنص واضح')
check(row['personal_data'].startswith('v1:') and row['nin_hash'] != hashlib.sha256(VALID['nin'].encode()).hexdigest() and len(row['nin_hash']) == 64,
      'personal_data مشفّرة (v1:…) و nin_hash بصمة HMAC (ليست sha256 بسيطاً يمكن تخمينه)')
check(row['secret_hash'].startswith('$2y$') and SECRET.replace('-', '') not in raw_row, 'الرمز السري محفوظ كبصمة password_hash فقط')
check(row['last_name_latin'] == 'BENYAHIA' and row['first_name_latin'] == 'Meriem', 'توحيد كتابة الاسم اللاتيني (BENYAHIA Meriem)')
new = sorted(set(enc_files()) - before)
check(len(new) == 2 and all(re.match(r'^[a-f0-9]{32}\.enc$', os.path.basename(p)) for p in new), 'وثيقتان بأسماء عشوائية .enc (لا الاسم الأصلي ولا الامتداد)')
check(all(b'%PDF' not in open(p, 'rb').read() and open(p, 'rb').read().startswith(b'DOUENC1') for p in new), 'الوثائق مشفّرة على القرص (لا يوجد %PDF داخلها)')
check(all(oct(os.stat(p).st_mode)[-3:] == '600' for p in new) and oct(os.stat(os.path.join(SITE, 'storage/private/recruitment.key')).st_mode)[-3:] == '600',
      'صلاحيات 600 للوثائق ولملف المفتاح')
check(os.path.exists(os.path.join(SITE, 'storage/private/recruitment/.htaccess')), 'storage/private/recruitment/.htaccess (Require all denied)')
files = q('SELECT original_name, sha256, size FROM competition_application_files WHERE application_id = ? ORDER BY id', [row['id']])
check([f['original_name'] for f in files] == ['شهادة الليسانس.pdf', 'scan.pdf'] and files[0]['sha256'] == hashlib.sha256(PDF['ok_simple.pdf']).hexdigest(),
      'الاسم الأصلي محفوظ للعرض فقط + بصمة sha256 للتحقق من السلامة')
s, _h, b = c.text('GET', '/competitions/receipt/')
check(s == 200 and SECRET in b, 'الوصل يبقى متاحاً في نفس المتصفح (15 دقيقة كحد أقصى)')
s, h, _b = Client().text('GET', '/competitions/receipt/')
check(s == 302, 'الوصل لا يظهر في متصفح آخر (مرتبط بالجلسة)')
t, _ = c.token('/competitions/receipt/')
c.text('POST', '/competitions/receipt/', data={'csrfmiddlewaretoken': t, 'done': '1'})
s, _h, _b = c.text('GET', '/competitions/receipt/')
check(s == 302, '«احتفظت بالرمزين» يمحو الرمز السري من الجلسة')

# ----------------------------------------------------------------------------------------------
print('3) متابعة الطلب: رقم المتابعة وحده لا يكفي')
clear_ratelimit()


def track(code, secret, client=None, headers=None, follow=True):
    """الدخول إلى فضاء المترشح: POST ← 302 (PRG) ← صفحة الطلب (follow) — أو صفحة الخطأ مباشرة"""
    cl = client or Client()
    t, _ = cl.token('/competitions/track/')
    s, h, b = cl.text('POST', '/competitions/track/', data={'csrfmiddlewaretoken': t, 'tracking_code': code, 'secret': secret}, headers=headers)
    if follow and s == 302:
        return cl.text('GET', urllib.parse.urlparse(location(h)).path)
    return s, h, b


s, h, b = track(TRACK, SECRET)
check(s == 200 and 'تم استلام طلبك' in b and 'رتبة اختبار' in b and 'بن يحيى مريم' in b, 'رقم المتابعة + الرمز السري ← حالة الطلب')
check(VALID['nin'] not in b and VALID['phone'] not in b, '  صفحة المتابعة لا تعرض رقم التعريف ولا الهاتف')
s, h, b = track(TRACK.lower().replace('-', ' '), SECRET.lower())
check(s == 200 and 'تم استلام طلبك' in b, 'يقبل الأحرف الصغيرة والمسافات بدل الشرطة')
s1, _h, b1 = track(TRACK, 'AAAAA-AAAAA')
s2, _h, b2 = track('C26-ZZZZZZZZ', SECRET)
check(s1 == 200 and s2 == 200 and 'رقم المتابعة أو الرمز السري غير صحيح' in b1 and 'رقم المتابعة أو الرمز السري غير صحيح' in b2, 'رمز خاطئ أو رقم غير موجود ← نفس الرسالة العامة')
s, _h, _b = Client().text('GET', '/competitions/track/?tracking_code=' + TRACK + '&secret=' + SECRET)
check('تم استلام طلبك' not in _b, 'لا متابعة عبر GET (الرمز لا يظهر في الروابط والسجلات)')
t0 = time.time(); track(TRACK, 'AAAAA-AAAAB'); t1 = time.time(); track('C26-ZZZZZZZY', 'AAAAA-AAAAB'); t2 = time.time()
check(abs((t1 - t0) - (t2 - t1)) < 0.25, f'زمن الرد متقارب لرقم موجود ({t1 - t0:.2f}s) وغير موجود ({t2 - t1:.2f}s): لا يكشف وجود الرقم')
cl = Client()
t, _ = cl.token('/competitions/track/')
s, _h, b = cl.text('POST', '/competitions/track/', data={'csrfmiddlewaretoken': t, 'tracking_code[]': TRACK, 'secret[]': SECRET})
check(s == 200 and 'رقم المتابعة أو الرمز السري غير صحيح' in b, f'حقول بصيغة مصفوفة (tracking_code[]) ← رسالة عادية لا خطأ خادم ({s})')
clear_ratelimit()
for i in range(5):
    track(TRACK, f'WRONG-{i:05d}', headers={'X-Forwarded-For': f'10.0.0.{i}'})
s, h, b = track(TRACK, SECRET)
check(s == 429 and 'retry-after' in h, f'5 محاولات خاطئة لنفس رقم المتابعة ← حظر مؤقت حتى للرمز الصحيح ({s})')
clear_ratelimit()
for i in range(10):
    track(f'C26-ZZZZZZ{chr(65 + i)}{chr(66 + i)}', 'AAAAA-AAAAA', headers={'X-Forwarded-For': f'10.1.0.{i}'})
s, h, b = track(TRACK, SECRET)
check(s == 429, f'10 محاولات خاطئة من نفس العنوان (مع X-Forwarded-For مزيف) ← حظر مؤقت ({s})')
clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('4) لا ترشح مرتين بنفس رقم التعريف')
n = app_count()
_c, s, _h, b = submit()
check(s == 200 and 'يوجد طلب ترشح مسجّل بهذا الرقم' in b and app_count() == n, 'نفس رقم التعريف في نفس المسابقة ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '1099 7041 2000 999 111'})
check(app_count() == n, 'ولو كُتب بمسافات')

# ----------------------------------------------------------------------------------------------
print('5) الوثائق الخبيثة والتالفة تُرفض ولا يُحفظ منها شيء')
before = enc_files()
for name in sorted(k for k in PDF if k.startswith('bad_')):
    _c, s, _h, b = submit(data={**VALID, 'nin': '2' + str(abs(hash(name)) % 10 ** 17).zfill(17)}, pdf=PDF[name])
    check(app_count() == n and 'errorlist' in b, f'{name} ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '209000000000000001'}, fname='shell.php', pdf=PDF['ok_simple.pdf'])
check(app_count() == n and 'يُقبل ملف PDF فقط' in b, 'ملف PDF سليم باسم shell.php ← مرفوض (الامتداد)')
_c, s, _h, b = submit(data={**VALID, 'nin': '209000000000000002'}, fname='x.pdf.php', pdf=PDF['ok_simple.pdf'])
check(app_count() == n, 'x.pdf.php ← مرفوض')
big = PDF['ok_simple.pdf'][:-6] + b' ' * (3 * 1024 * 1024 + 10) + b'%%EOF\n'
_c, s, _h, b = submit(data={**VALID, 'nin': '209000000000000003'}, pdf=big)
check(app_count() == n and 'حجم الملف كبير جداً' in b, 'ملف أكبر من 3 ميغابايت ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '209000000000000004'}, files={})
check(app_count() == n and 'هذا الحقل مطلوب' in b, 'بدون الوثيقة الإلزامية ← مرفوض')
check(enc_files() == before, 'لم يُكتب أي ملف على القرص للطلبات المرفوضة')
log = open(os.path.join(SITE, 'storage/logs/security.log'), encoding='utf-8').read()
check('recruitment_pdf_rejected' in log and 'active:/JavaScript' in log, 'رفض المحتوى النشط مسجّل في security.log')

# ----------------------------------------------------------------------------------------------
print('6) التحقق من المدخلات')
cases = [
    ('nin', '10997041200099911', 'رقم تعريف من 17 رقماً'),
    ('nin', '10997041200099911a', 'رقم تعريف بحرف'),
    ('phone', '0261987654', 'هاتف لا يبدأ بـ 05/06/07'),
    ('last_name', 'Benyahia', 'لقب عربي بحروف لاتينية'),
    ('first_name_latin', 'مريم', 'اسم لاتيني بحروف عربية'),
    ('birth_place', '<script>alert(1)</script>', 'وسوم HTML في مكان الميلاد'),
    ('address', '{{7*7}} ${7*7}', 'أقواس القوالب في العنوان'),
    ('birth_date', time.strftime('%Y-%m-%d'), 'عمر أقل من 18 سنة'),
    ('birth_date', '1997-02-30', 'تاريخ غير موجود'),
    ('graduation_year', '2099', 'سنة تخرج في المستقبل'),
    ('post_id', str(T['other_post']), 'رتبة من مسابقة أخرى'),
    ('gender', 'X', 'جنس غير صالح'),
    ('email', 'not-an-email', 'بريد غير صالح'),
    ('declaration', '', 'بدون التصريح بصحة المعلومات'),
    ('consent', '', 'بدون الموافقة على معالجة المعطيات'),
]
for i, (field, value, label) in enumerate(cases):
    data = {**VALID, 'nin': '3' + str(i).zfill(17), field: value}
    if value == '':
        data.pop(field)
    _c, s, _h, b = submit(data=data)
    check(app_count() == n and 'errorlist' in b, f'{label} ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '300000000000000099', 'gender': 'M', 'national_service': 'na'})
check(app_count() == n and 'الخدمة الوطنية' in b, 'ذكر بلا وضعية تجاه الخدمة الوطنية ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '300000000000000098', 'birth_place': '<b>x</b>'})
check('<b>x</b>' not in b, 'المدخلات المرفوضة لا تُعاد في الصفحة دون تهريب (XSS)')

# ----------------------------------------------------------------------------------------------
print('7) البرامج الآلية: CSRF وحقل الفخ ورمز الوقت')
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000001'}, extra={'website_url': 'http://spam.example'})
check(app_count() == n and 'غير عادية' in b, 'حقل الفخ مملوء ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000007'}, extra={'website_url[]': 'x', '_ft[]': 'x'})
check(s == 200 and app_count() == n, f'حقل الفخ ورمز الوقت بصيغة مصفوفة ← مرفوض بلا خطأ خادم ({s})')
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000008', 'nin[]': 'x', 'post_id[]': '1'})
check(s == 200, f'حقول الاستمارة بصيغة مصفوفة ← لا خطأ خادم ({s})')
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000002'}, ft=False)
check(app_count() == n and 'غير عادية' in b, 'بدون رمز الوقت ← مرفوض')
c = Client()
t, _ = c.token(APPLY)
fresh = php(f"$t = time(); echo $t . '.' . substr(Crypto::sign('apply|{CID}|' . $t . '|{c.cookies.get('dou_session', '')}'), 0, 32);")
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000003'}, client=c, ft=False, extra={'_ft': fresh})
check(app_count() == n and 'غير عادية' in b, 'إرسال خلال أقل من 3 ثوانٍ من فتح الاستمارة ← مرفوض')
_c, s, _h, b = submit(data={**VALID, 'nin': '400000000000000004'}, ft=False, extra={'_ft': str(int(time.time()) - 60) + '.' + '0' * 32})
check(app_count() == n, 'رمز وقت مزوّر (توقيع خاطئ) ← مرفوض')
c = Client()
c.token(APPLY)
s, _h, b = c.text('POST', APPLY, data={**VALID, 'nin': '400000000000000005'}, files={})
check(s == 403 and app_count() == n, f'بدون رمز CSRF ← 403 ({s})')
log = open(os.path.join(SITE, 'storage/logs/security.log'), encoding='utf-8').read()
check('recruitment_bot_suspect' in log, 'محاولات البرامج الآلية مسجّلة في security.log')
clear_ratelimit()
rl = os.path.join(SITE, 'storage', 'ratelimit', hashlib.sha256(b'apply-ip:127.0.0.1').hexdigest() + '.json')
with open(rl, 'w') as f:
    json.dump([int(time.time())] * 30, f)  # 30 محاولة خلال الساعة الأخيرة
_c, s, h, b = submit(data={**VALID, 'nin': '400000000000000006'}, keep_rl=True)
check(s == 429 and 'retry-after' in h and app_count() == n, f'بعد 30 محاولة إرسال في الساعة من نفس العنوان ← 429 ({s})')
clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('8) الأجل يُفرض في الخادم')
for label, sets in [('انتهى الأجل (أمس)', {'closes_at': time.strftime('%Y-%m-%d', time.localtime(time.time() - 86400))}),
                    ('لم يُفتح بعد', {'opens_at': time.strftime('%Y-%m-%d', time.localtime(time.time() + 2 * 86400))}),
                    ('إيداع ورقي فقط', {'online_apply': 0}), ('ملغاة', {'status': 'cancelled'})]:
    c = Client()
    t, b0 = c.token(APPLY)  # الاستمارة فُتحت والمسابقة مفتوحة
    old = q('SELECT opens_at, closes_at, online_apply, status FROM competitions WHERE id = ?', [CID])[0]
    php(f"DB::update('competitions', json_decode({json.dumps(json.dumps(sets))}, true), 'id = :id', ['id' => {CID}]);")
    s, _h, b = c.text('POST', APPLY, data={**VALID, 'nin': '500000000000000001', 'csrfmiddlewaretoken': t, '_ft': form_token(c, CID)},
                      files={f'doc_{T["r1"]}': ('a.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
    php(f"DB::update('competitions', json_decode({json.dumps(json.dumps(old))}, true), 'id = :id', ['id' => {CID}]);")
    check(app_count() == n and 'cmp-apply' not in b and 'alert-warning' in b, f'{label}: الإرسال مرفوض حتى لو كانت الاستمارة مفتوحة قبل ذلك')

# ----------------------------------------------------------------------------------------------
print('9) صلاحيات لوحة الإدارة')
APP_ID = int(row['id'])
FILE_ID = int(q('SELECT id FROM competition_application_files WHERE application_id = ? ORDER BY id LIMIT 1', [APP_ID])[0]['id'])
admin_paths = ['/admin/recruitment/applications/', f'/admin/recruitment/applications/{APP_ID}/', f'/admin/recruitment/files/{FILE_ID}/',
               '/admin/recruitment/export/?mode=full', '/admin/recruitment/audit/', f'/admin/recruitment/purge/{CID}/',
               f'/admin/recruitment/results/?competition={CID}']
for p in admin_paths:
    s, h, _b = Client().text('GET', p)
    check(s == 302 and '/admin/login/' in location(h), f'زائر: {p} ← صفحة دخول الإدارة')
st = Client()
st.login(*STUDENT, path='/students/login/')
s, _h, _b = st.text('GET', admin_paths[2])
check(s in (302, 403) and b'%PDF' not in _b.encode(), f'طالب: وثيقة مترشح ← ممنوع ({s})')
staff = Client()
staff.login(*STAFF)
for p in admin_paths:
    s, _h, b = staff.text('GET', p)
    check(s == 403 and VALID['nin'] not in b, f'موظف بلا صلاحية المسابقات: {p} ← 403')
s, _h, b = staff.text('GET', '/admin/competitions/')
check(s == 404, f'موظف بلا صلاحية: قسم «المسابقات» غير موجود في لوحته ({s})')
s, _h, b = staff.text('GET', '/admin/')
check('طلبات الترشح' not in b and 'admin/competitions/' not in b, '  ولا يظهر في القائمة الجانبية')
x("UPDATE users SET can_recruit = 1 WHERE username = 'hicham41'")
s, _h, b = staff.text('GET', admin_paths[0])
check(s == 200 and TRACK in b, 'بعد منحه «صلاحية مسابقات التوظيف»: يرى الطلبات')
s, _h, b = staff.text('GET', admin_paths[4])
check(s == 403, '  لكن سجل الاطلاع للمدير العام فقط')
s, _h, b = staff.text('GET', admin_paths[5])
check(s == 403, '  وحذف معطيات المترشحين للمدير العام فقط')
x("UPDATE users SET can_recruit = 0 WHERE username = 'hicham41'")
s, _h, b = staff.text('GET', admin_paths[0])
check(s == 403, 'سحب الصلاحية يسري فوراً')

adm = Client()
adm.login(*ADMIN)
s, _h, b = adm.text('GET', f'/admin/recruitment/applications/{APP_ID}/')
check(s == 200 and VALID['nin'] in b and VALID['phone'] in b, 'المدير: صفحة الطلب تعرض المعطيات بعد فك التشفير')
s, h, raw = adm.req('GET', f'/admin/recruitment/files/{FILE_ID}/')
check(s == 200 and raw == PDF['ok_simple.pdf'], 'الوثيقة تُفك تشفيرها مطابقة للأصل حرفاً بحرف')
check(h.get('content-type') == 'application/pdf' and h.get('x-content-type-options') == 'nosniff' and 'no-store' in h.get('cache-control', '')
      and "default-src 'none'" in h.get('content-security-policy', '') and 'inline' in h.get('content-disposition', ''),
      'ترويسات الوثيقة: application/pdf، nosniff، no-store، CSP صارم')
s, h, raw = adm.req('GET', f'/admin/recruitment/files/{FILE_ID}/?dl=1')
check('attachment' in h.get('content-disposition', '') and "filename*=UTF-8''" in h.get('content-disposition', ''), '?dl=1 ← تحميل باسم الملف الأصلي (UTF-8)')
s, h, raw = adm.req('GET', f'/admin/recruitment/export/?competition={CID}')
z = zipfile.ZipFile(io.BytesIO(raw)) if raw[:2] == b'PK' else None
xml = z.read('xl/worksheets/sheet1.xml').decode() if z else ''
check(z is not None and 'BENYAHIA' in xml and VALID['nin'] not in xml and VALID['phone'] not in xml, 'تصدير القائمة المختصرة: بلا رقم التعريف ولا الهاتف')
s, h, raw = adm.req('GET', f'/admin/recruitment/export/?competition={CID}&mode=full')
xml = zipfile.ZipFile(io.BytesIO(raw)).read('xl/worksheets/sheet1.xml').decode() if raw[:2] == b'PK' else ''
check(VALID['nin'] in xml and '<f>' not in xml and 't="inlineStr"' in xml, 'التصدير الكامل: رقم التعريف كنص (لا صيغ Excel قابلة للتنفيذ)')
check('no-store' in h.get('cache-control', '') or 'private' in h.get('cache-control', '') or s == 200, 'ملف التصدير')
audit = q('SELECT action, username, details FROM recruitment_audit WHERE application_id = ? OR (application_id IS NULL AND competition_id = ?) ORDER BY id', [APP_ID, CID])
acts = [a['action'] for a in audit]
check(acts[:1] == ['submit'] and audit[0]['username'] == '' and 'track' in acts, 'سجل الاطلاع: الإرسال والمتابعة باسم «المترشح»')
check(all(a in acts for a in ('view', 'file', 'export')) and any(a['username'] == 'miloudsellam' and a['action'] == 'file' for a in audit), 'سجل الاطلاع: فتح الملف والوثيقة والتصدير باسم الموظف')
check(any(a['action'] == 'export' and 'قائمة كاملة' in a['details'] for a in audit), '  مع نوع التصدير (كاملة/مختصرة) وعدد الطلبات')

# ----------------------------------------------------------------------------------------------
print('10) دراسة الطلب: الحالة ورسالة المترشح')
t, _ = adm.token(f'/admin/recruitment/applications/{APP_ID}/')
s, h, _b = adm.text('POST', f'/admin/recruitment/applications/{APP_ID}/', data={'csrfmiddlewaretoken': t, 'form': 'review', 'status': 'incomplete',
                    'candidate_message': 'ينقص ملفك <script>alert(1)</script> كشف النقاط', 'internal_notes': 'ملاحظة داخلية سرية'})
check(s == 302, 'تغيير الحالة إلى «ملف ناقص» مع رسالة')
s, _h, b = track(TRACK, SECRET)
check('ملفك ناقص' in b and '&lt;script&gt;alert(1)&lt;/script&gt;' in b and '<script>alert(1)' not in b, 'المترشح يرى الحالة والرسالة (مع تهريب HTML)')
check('ملاحظة داخلية سرية' not in b, '  ولا يرى الملاحظات الداخلية')
t, _ = adm.token(f'/admin/recruitment/applications/{APP_ID}/')
adm.text('POST', f'/admin/recruitment/applications/{APP_ID}/', data={'csrfmiddlewaretoken': t, 'status': 'hacked', 'candidate_message': '', 'internal_notes': ''})
check(q('SELECT status FROM competition_applications WHERE id = ?', [APP_ID])[0]['status'] == 'incomplete', 'حالة غير معروفة ← مرفوضة')
t, _ = adm.token('/admin/recruitment/applications/')
adm.text('POST', '/admin/recruitment/applications/', data={'csrfmiddlewaretoken': t, 'new_status': 'accepted', 'ids[]': [str(APP_ID)]})
check(q('SELECT status FROM competition_applications WHERE id = ?', [APP_ID])[0]['status'] == 'accepted', 'إجراء جماعي: قبول الطلبات المحددة')
s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=' + VALID['nin'])
check(TRACK in b, 'البحث برقم التعريف يعمل دون حفظه (مقارنة البصمة)')

# ----------------------------------------------------------------------------------------------
print('11) كشف التلاعب بقاعدة البيانات وبالملفات')
_c, s, h, _b = submit(data={**VALID, 'nin': '109970412000999222', 'last_name': 'عباس', 'first_name': 'ليلى'})
other = q("SELECT id, personal_data FROM competition_applications WHERE competition_id = ? AND id <> ? ORDER BY id DESC LIMIT 1", [CID, APP_ID])[0]
mine = q('SELECT personal_data FROM competition_applications WHERE id = ?', [APP_ID])[0]['personal_data']
x('UPDATE competition_applications SET personal_data = ? WHERE id = ?', [other['personal_data'], APP_ID])
s, _h, b = adm.text('GET', f'/admin/recruitment/applications/{APP_ID}/')
check(s == 200 and 'تعذر فك التشفير' in b and '109970412000999222' not in b, 'نسخ بيانات مشفّرة من طلب آخر ← يُكشف ولا تظهر (السياق AAD)')
x('UPDATE competition_applications SET personal_data = ? WHERE id = ?', [mine, APP_ID])
path = [p for p in enc_files() if os.path.basename(p) == q('SELECT stored_name FROM competition_application_files WHERE id = ?', [FILE_ID])[0]['stored_name']][0]
orig = open(path, 'rb').read()
with open(path, 'wb') as f:
    f.write(orig[:-1] + bytes([orig[-1] ^ 1]))
s, _h, raw = adm.req('GET', f'/admin/recruitment/files/{FILE_ID}/')
check(s == 404 and b'%PDF' not in raw, 'تغيير بايت واحد في الوثيقة المشفّرة ← تُرفض (GCM)')
with open(path, 'wb') as f:
    f.write(orig)
x('UPDATE competition_application_files SET stored_name = ? WHERE id = ?', ['../../../../config.php', FILE_ID])
s, _h, raw = adm.req('GET', f'/admin/recruitment/files/{FILE_ID}/')
check(s == 404 and b'<?php' not in raw, 'اسم ملف مزوّر في قاعدة البيانات (../../config.php) ← 404')
x('UPDATE competition_application_files SET stored_name = ? WHERE id = ?', [os.path.basename(path), FILE_ID])
s, _h, raw = Client().req('GET', '/storage/private/recruitment.key')
check(s in (403, 404) and len(raw) != 65, f'/storage/private/recruitment.key من المتصفح ← {s}')
rel = os.path.relpath(path, SITE)
s, _h, raw = Client().req('GET', '/' + rel)
check(s in (403, 404) and not raw.startswith(b'DOUENC1'), f'/{rel[:40]}... من المتصفح ← {s}')

# ----------------------------------------------------------------------------------------------
def new_app(nin, last, first, last_lat, first_lat):
    """طلب جديد في مسابقة الاختبار ← (رقمه, رقم المتابعة, الرمز السري)"""
    cl, _s, _h, _b = submit(data={**VALID, 'nin': nin, 'last_name': last, 'first_name': first, 'last_name_latin': last_lat, 'first_name_latin': first_lat})
    _s, _h, b = cl.text('GET', '/competitions/receipt/')
    codes = dict(re.findall(r'class="cmp-code" id="cmp-(tracking|secret)">([^<]+)<', b))
    return int(q('SELECT id FROM competition_applications WHERE tracking_code = ?', [codes['tracking']])[0]['id']), codes['tracking'], codes['secret']


def candidate(code, secret):
    cl = Client()
    track(code, secret, client=cl)
    return cl


def complete(cl, files, csrf=True):
    t, _ = cl.token('/competitions/track/')
    return cl.text('POST', '/competitions/track/complete/', data={'csrfmiddlewaretoken': t} if csrf else {}, files=files)


def review(app_id, status, until=None, msg=''):
    t, _ = adm.token(f'/admin/recruitment/applications/{app_id}/')
    d = {'csrfmiddlewaretoken': t, 'form': 'review', 'status': status, 'candidate_message': msg, 'internal_notes': ''}
    if until is not None:
        d['complete_until'] = until
    return adm.text('POST', f'/admin/recruitment/applications/{app_id}/', data=d)


def app_row(app_id):
    return q('SELECT * FROM competition_applications WHERE id = ?', [app_id])[0]


def file_count(app_id):
    return int(q('SELECT COUNT(*) n FROM competition_application_files WHERE application_id = ?', [app_id])[0]['n'])


def session_file(cl):
    return os.path.join(SITE, 'storage', 'sessions', 'sess_' + cl.cookies.get('dou_session', ''))


print('12) فضاء المترشح: الجلسة (تثبيت الجلسة، المدة، الإغلاق، لا أرقام في الروابط)')
clear_ratelimit()
cl = Client()
cl.token('/competitions/track/')
sid_before = cl.cookies.get('dou_session', '')
s, h, _b = track(TRACK, SECRET, client=cl, follow=False)
sid_after = cl.cookies.get('dou_session', '')
check(s == 302 and location(h).endswith('/competitions/track/'), f'الرمزان الصحيحان ← 302 إلى صفحة الطلب (PRG: إعادة التحميل لا تعيد إرسال الرمز) ({s})')
check(sid_before != '' and sid_after != '' and sid_before != sid_after, 'معرّف جلسة جديد بعد الدخول (منع تثبيت الجلسة Session Fixation)')
old_cl = Client()
old_cl.cookies = {'dou_session': sid_before}
s, _h, b = old_cl.text('GET', '/competitions/track/')
check('cmp-track-form' in b and 'cmp-status' not in b, 'المعرّف القديم (قبل الدخول) لا يفتح صفحة الطلب')
s, h, b = cl.text('GET', '/competitions/track/')
check(s == 200 and 'cmp-status' in b and 'no-store' in h.get('cache-control', '') and 'noindex' in h.get('x-robots-tag', '') and h.get('referrer-policy') == 'no-referrer',
      'صفحة الطلب: no-store و noindex و Referrer-Policy: no-referrer')
check(SECRET not in b and SECRET.replace('-', '') not in b, '  والرمز السري لا يظهر فيها')
check(php('echo RECRUIT_GRANT_TTL;') == '1200', 'مدة فضاء المترشح 20 دقيقة (ثابتة لا تتجدد)')
grant = php(f"""$a = CompetitionApplication::findByTracking('{TRACK}'); $id = (int) $a['id']; $o = [];
$_SESSION = ['recruit_track' => ['id' => $id, 'code' => '{TRACK}', 'until' => time() + 60]]; $o[] = recruit_grant() !== null;
$_SESSION = ['recruit_track' => ['id' => $id, 'code' => '{TRACK}', 'until' => time() - 1]]; $o[] = recruit_grant() === null && !isset($_SESSION['recruit_track']);
$_SESSION = ['recruit_track' => ['id' => $id, 'code' => '{TRACK}', 'until' => time() - 300]]; $o[] = recruit_grant(600) !== null;
$_SESSION = ['recruit_track' => ['id' => $id, 'code' => 'C26-ZZZZZZZZ', 'until' => time() + 60]]; $o[] = recruit_grant() === null;
$_SESSION = ['recruit_track' => ['id' => $id + 1, 'code' => '{TRACK}', 'until' => time() + 60]]; $o[] = recruit_grant() === null;
$_SESSION = ['recruit_track' => 'x']; $o[] = recruit_grant() === null;
echo json_encode($o);""")
check(grant == '[true,true,true,true,true,true]', f'الإذن: ينتهي بعد مدته، ومربوط برقم المتابعة (رقم طلب آخر أو رقم متابعة آخر ← مرفوض) {grant}')
sf = session_file(cl)
raw = open(sf).read() if os.path.exists(sf) else ''
with open(sf, 'w') as f:
    f.write(re.sub(r'(s:5:"until";i:)\d+;', lambda m: m.group(1) + str(int(time.time()) - 5) + ';', raw))
s, _h, b = cl.text('GET', '/competitions/track/')
check('until' in raw and 'cmp-track-form' in b and 'cmp-status' not in b, 'بعد انتهاء المدة (في ملف الجلسة نفسه): نموذج الدخول من جديد')
for path in ['/competitions/track/convocation/', '/competitions/track/result/']:
    s, h, _b = Client().text('GET', path)
    check(s == 302 and location(h).endswith('/competitions/track/'), f'{path} بلا جلسة ← نموذج الدخول ({s})')
cl = candidate(TRACK, SECRET)
t, _ = cl.token('/competitions/track/')
cl.text('POST', '/competitions/track/', data={'csrfmiddlewaretoken': t, 'logout': '1'})
s, _h, b = cl.text('GET', '/competitions/track/')
check('cmp-track-form' in b and 'cmp-status' not in b, '«إغلاق صفحة طلبي» ينهي الوصول فوراً')
s, _h, b = Client().text('POST', '/competitions/track/complete/', data={'x': '1'})
check(s == 403, f'رفع وثائق بلا رمز CSRF ← 403 ({s})')
cl = candidate(TRACK, SECRET)
s, _h, b = track(TRACK, 'WRONG-SECRT', client=cl)
s, _h, b = cl.text('GET', '/competitions/track/')
check('cmp-track-form' in b and 'cmp-status' not in b, 'محاولة دخول خاطئة في متصفح مفتوح على طلب ← يُغلق الطلب المفتوح (جهاز مشترك)')
clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('13) إكمال الملف الناقص من طرف المترشح')
B_ID, B_TRACK, B_SECRET = new_app('109950101000555111', 'عباسي', 'نادية', 'Abbassi', 'nadia')
cb = candidate(B_TRACK, B_SECRET)
s, _h, b = cb.text('GET', '/competitions/track/')
check('id="cmp-complete"' not in b, 'طلب «قيد الانتظار»: لا نموذج إكمال')
n0 = file_count(B_ID)
s, h, _b = complete(cb, {f'doc_{T["r2"]}': ('extra.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(s == 302 and file_count(B_ID) == n0, 'رفع وثيقة لطلب غير ناقص (بتجاوز الواجهة) ← مرفوض')
review(B_ID, 'incomplete', msg='ينقص ملفك الوثيقة الاختيارية')
row_b = app_row(B_ID)
default_until = time.strftime('%Y-%m-%d', time.localtime(time.time() + 7 * 86400))
check(row_b['status'] == 'incomplete' and row_b['complete_until'] == default_until, f'«ملف ناقص» بلا تاريخ ← أجل افتراضي بعد 7 أيام ({row_b["complete_until"]})')
s, _h, b = review(B_ID, 'incomplete', until='2001-01-01')
check(app_row(B_ID)['complete_until'] == default_until, 'أجل في الماضي من لوحة الإدارة ← مرفوض')
s, _h, b = cb.text('GET', '/competitions/track/')
check('id="cmp-complete"' in b and f'name="doc_{T["r1"]}"' in b and f'name="doc_{T["r2"]}"' in b and 'ينقص ملفك الوثيقة الاختيارية' in b,
      'المترشح يرى رسالة الإدارة ونموذج رفع الوثائق الناقصة')
clear_ratelimit()
before = set(enc_files())
for name in ['bad_js_openaction.pdf', 'bad_php_polyglot.pdf', 'bad_png_renamed.pdf', 'bad_encrypted.pdf', 'bad_launch.pdf']:
    complete(cb, {f'doc_{T["r2"]}': (name, PDF[name], 'application/pdf')})
check(file_count(B_ID) == n0 and app_row(B_ID)['status'] == 'incomplete' and set(enc_files()) == before, 'وثائق خبيثة أو تالفة ← مرفوضة، لا يُحفظ شيء، والطلب يبقى ناقصاً')
complete(cb, {f'doc_{T["r2"]}': ('shell.php', PDF['ok_simple.pdf'], 'application/pdf')})
check(file_count(B_ID) == n0, 'ملف PDF سليم باسم shell.php ← مرفوض')
complete(cb, {f'doc_{T["other_post"] + 100000}': ('x.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(file_count(B_ID) == n0, 'خانة لوثيقة لا تنتمي لهذه المسابقة ← تُتجاهل')
big = PDF['ok_simple.pdf'][:-6] + b' ' * (3 * 1024 * 1024 + 10) + b'%%EOF\n'
complete(cb, {f'doc_{T["r2"]}': ('big.pdf', big, 'application/pdf')})
check(file_count(B_ID) == n0, 'ملف أكبر من 3 ميغابايت ← مرفوض')
clear_ratelimit()
s, h, _b = complete(cb, {f'doc_{T["r2"]}': ('كشف النقاط.pdf', PDF['ok_scan.pdf'], 'application/pdf')})
row_b = app_row(B_ID)
newf = sorted(set(enc_files()) - before)
added = q('SELECT original_name, added_later, sha256 FROM competition_application_files WHERE application_id = ? ORDER BY id DESC LIMIT 1', [B_ID])[0]
check(s == 302 and file_count(B_ID) == n0 + 1 and row_b['status'] == 'pending' and row_b['completed_at'], 'وثيقة صحيحة ← محفوظة، والطلب يعود «قيد الانتظار» مع وقت الإكمال')
check(len(newf) == 1 and open(newf[0], 'rb').read().startswith(b'DOUENC1') and b'%PDF' not in open(newf[0], 'rb').read() and oct(os.stat(newf[0]).st_mode)[-3:] == '600',
      '  الوثيقة مشفّرة على القرص باسم عشوائي وصلاحيات 600')
check(added['original_name'] == 'كشف النقاط.pdf' and str(added['added_later']) == '1' and added['sha256'] == hashlib.sha256(PDF['ok_scan.pdf']).hexdigest(),
      '  معلَّمة «أُضيفت لاحقاً» مع بصمة sha256')
fid = int(q('SELECT id FROM competition_application_files WHERE application_id = ? ORDER BY id DESC LIMIT 1', [B_ID])[0]['id'])
s, _h, raw = adm.req('GET', f'/admin/recruitment/files/{fid}/')
check(s == 200 and raw == PDF['ok_scan.pdf'], '  الإدارة تفتحها مطابقة للأصل')
s, _h, b = adm.text('GET', f'/admin/recruitment/applications/{B_ID}/')
check('recruit-late-file' in b, '  وتظهر للإدارة بعلامة «إكمال»')
aud = q("SELECT username FROM recruitment_audit WHERE application_id = ? AND action = 'complete'", [B_ID])
check(len(aud) == 1 and aud[0]['username'] == '', 'سجل الاطلاع: «إكمال الملف» باسم المترشح')
s, _h, b = cb.text('GET', '/competitions/track/')
check('id="cmp-complete"' not in b and 'cmp-status is-pending' in b, 'بعد الإكمال: يختفي النموذج')
complete(cb, {f'doc_{T["r2"]}': ('again.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(file_count(B_ID) == n0 + 1, 'ولا يمكن رفع وثيقة أخرى بعده')
review(B_ID, 'incomplete')
x('UPDATE competition_applications SET complete_until = ? WHERE id = ?', [time.strftime('%Y-%m-%d', time.localtime(time.time() - 86400)), B_ID])
s, _h, b = cb.text('GET', '/competitions/track/')
check('id="cmp-complete"' not in b and 'cmp-closed-note' in b, 'بعد انتهاء أجل الإكمال: لا نموذج، ورسالة «انتهى الأجل»')
complete(cb, {f'doc_{T["r2"]}': ('late.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(file_count(B_ID) == n0 + 1, '  والرفع (بتجاوز الواجهة) مرفوض في الخادم')
x('UPDATE competition_applications SET complete_until = ? WHERE id = ?', [default_until, B_ID])
clear_ratelimit()
for i in range(10):
    complete(cb, {f'doc_{T["r2"]}': (f'bad{i}.pdf', PDF['bad_png_renamed.pdf'], 'application/pdf')})
complete(cb, {f'doc_{T["r2"]}': ('ok.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(file_count(B_ID) == n0 + 1 and app_row(B_ID)['status'] == 'incomplete', '10 محاولات رفع في اليوم لكل طلب ← المحاولة 11 مرفوضة حتى بملف صحيح')
clear_ratelimit()
php(f"for ($i = 0; $i < 25; $i++) {{ DB::insert('competition_application_files', ['application_id' => {B_ID}, 'label' => 'x', 'original_name' => 'x.pdf', "
    f"'stored_name' => bin2hex(random_bytes(16)) . '.enc', 'size' => 1, 'sha256' => str_repeat('0', 64)]); }}")
complete(cb, {f'doc_{T["r2"]}': ('ok.pdf', PDF['ok_simple.pdf'], 'application/pdf')})
check(app_row(B_ID)['status'] == 'incomplete', 'سقف لعدد الوثائق في الطلب الواحد (منع ملء القرص)')
x("DELETE FROM competition_application_files WHERE application_id = ? AND label = 'x' AND size = 1", [B_ID])
clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('14) الاستدعاء ورقم التسجيل')
exams = json.loads(php(f"""$e1 = CompetitionExam::create(['competition_id' => {CID}, 'title' => 'المقابلة <img src=x onerror=alert(1)>', 'starts_at' => date('Y-m-d', strtotime('+20 days')) . ' 09:00:00',
    'place' => 'قاعة الاختبار 3', 'instructions' => 'أحضر القلم']);
$e2 = CompetitionExam::create(['competition_id' => {CID}, 'title' => 'الفوج الثاني', 'starts_at' => date('Y-m-d', strtotime('+21 days')) . ' 09:00:00']);
$eo = CompetitionExam::create(['competition_id' => {T['other_id']}, 'title' => 'موعد مسابقة أخرى', 'starts_at' => date('Y-m-d', strtotime('+21 days')) . ' 09:00:00']);
echo json_encode([(int) $e1['id'], (int) $e2['id'], (int) $eo['id']]);"""))
E1, E2, EO = exams
C_ID, C_TRACK, C_SECRET = new_app('109960202000666222', 'أحمدي', 'سليم', 'Ahmedi', 'salim')
x("UPDATE competition_applications SET status = 'accepted' WHERE id = ?", [C_ID])


def bulk(do, ids, exam=None, competition=CID):
    path = '/admin/recruitment/applications/' + (f'?competition={competition}' if competition else '')
    t, _ = adm.token(path)
    d = {'csrfmiddlewaretoken': t, 'do': do, 'ids[]': [str(i) for i in ids]}
    if exam is not None:
        d['exam_id'] = str(exam)
    return adm.text('POST', path, data=d)


bulk('convoke', [APP_ID, C_ID], exam=E1, competition=None)
check(app_row(APP_ID)['exam_id'] is None, 'استدعاء جماعي بلا اختيار المسابقة ← مرفوض')
bulk('convoke', [APP_ID, C_ID], exam=EO)
check(app_row(APP_ID)['exam_id'] is None, 'موعد من مسابقة أخرى ← مرفوض')
bulk('convoke', [APP_ID, C_ID, B_ID], exam=E1)
ra, rc, rb = app_row(APP_ID), app_row(C_ID), app_row(B_ID)
check(int(ra['exam_id'] or 0) == E1 and int(rc['exam_id'] or 0) == E1 and rb['exam_id'] is None, 'استدعاء الملفات المقبولة فقط (الطلب الناقص يُتجاهل)')
check(rc['seat_number'] == '001' and ra['seat_number'] == '002', f'أرقام التسجيل بالتسلسل حسب الترتيب الأبجدي للقب (أحمدي 001، بن يحيى 002) ← {rc["seat_number"]}، {ra["seat_number"]}')
bulk('convoke', [APP_ID], exam=E2)
check(int(app_row(APP_ID)['exam_id']) == E2 and app_row(APP_ID)['seat_number'] == '002', 'نقل مترشح إلى موعد آخر يحتفظ برقم تسجيله')
ca = candidate(TRACK, SECRET)
s, _h, b = ca.text('GET', '/competitions/track/')
check('cmp-convocation' in b and '002' in b and 'الفوج الثاني' in b, 'المترشح يرى استدعاءه ورقم تسجيله')
s, h, b = ca.text('GET', '/competitions/track/convocation/')
check(s == 200 and 'doc-seat' in b and '002' in b and '12/04/1997' in b and 'no-store' in h.get('cache-control', '') and 'noindex' in h.get('x-robots-tag', ''),
      'الاستدعاء المطبوع: رقم التسجيل + تاريخ الميلاد (مفكوك التشفير لصاحبه فقط)، no-store و noindex')
check(VALID['nin'] not in b and VALID['phone'] not in b and 'حي 1014' not in b and 'example.dz' not in b.lower(), '  بلا رقم التعريف ولا الهاتف ولا العنوان ولا البريد')
s, _h, b = ca.text('GET', f'/competitions/track/convocation/?id={C_ID}&tracking_code={C_TRACK}')
check('002' in b and 'بن يحيى مريم' in b and 'أحمدي سليم' not in b, 'إضافة رقم طلب آخر في الرابط ← تُتجاهل (لا IDOR: الجلسة وحدها تحدد الطلب)')
bulk('convoke', [C_ID], exam=E1)
cc = candidate(C_TRACK, C_SECRET)
s, _h, b = cc.text('GET', '/competitions/track/convocation/')
check('&lt;img src=x onerror=alert(1)&gt;' in b and '<img src=x' not in b, 'عنوان موعد فيه HTML ← مُهرَّب في الاستدعاء')
s, _h, b = cc.text('GET', '/competitions/track/')
check('&lt;img src=x onerror=alert(1)&gt;' in b and '<img src=x' not in b, '  وفي صفحة المترشح')
s, _h, b = cb.text('GET', '/competitions/track/convocation/')
check(s == 404, f'مترشح غير مستدعى ← الاستدعاء غير موجود (404) ({s})')
review(C_ID, 'under_review')
check(app_row(C_ID)['exam_id'] is None, 'سحب القبول من مترشح مستدعى يلغي استدعاءه')
s, _h, _b = cc.text('GET', '/competitions/track/convocation/')
check(s == 404, '  ويختفي استدعاؤه')
x("UPDATE competition_applications SET status = 'accepted' WHERE id = ?", [C_ID])
t, _ = adm.token(f'/admin/recruitment/applications/{B_ID}/')
adm.text('POST', f'/admin/recruitment/applications/{B_ID}/', data={'csrfmiddlewaretoken': t, 'form': 'outcome', 'exam_id': str(E1), 'result': '', 'score': '', 'result_rank': '', 'result_note': ''})
check(app_row(B_ID)['exam_id'] is None, 'صفحة الطلب: استدعاء ملف غير مقبول ← مرفوض')
bulk('convoke', [C_ID], exam=E1)
bulk('unconvoke', [C_ID])
check(app_row(C_ID)['exam_id'] is None and app_row(C_ID)['seat_number'] == '001', 'إلغاء الاستدعاء (رقم التسجيل يبقى محجوزاً له)')
acts = [a['action'] for a in q('SELECT action FROM recruitment_audit WHERE application_id = ? ORDER BY id', [C_ID])]
check('convoke' in acts and 'unconvoke' in acts, 'سجل الاطلاع: الاستدعاء وإلغاؤه')

# ----------------------------------------------------------------------------------------------
print('15) النتيجة الفردية والنشر')
RES = f'/admin/recruitment/results/?competition={CID}'


def results_post(action, rows=None):
    t, _ = adm.token(RES)
    d = {'csrfmiddlewaretoken': t, 'action': action}
    for app_id, vals in (rows or {}).items():
        for k, v in vals.items():
            d[f'r[{app_id}][{k}]'] = v
    return adm.text('POST', RES, data=d)


s, _h, b = adm.text('GET', RES)
check(s == 200 and TRACK in b and C_TRACK in b and B_TRACK not in b, 'صفحة النتائج: الملفات المقبولة فقط')
results_post('publish')
check(q('SELECT results_published_at FROM competitions WHERE id = ?', [CID])[0]['results_published_at'] is None, 'نشر نتائج بلا أي نتيجة مسجّلة ← مرفوض')
results_post('save', {APP_ID: {'result': 'passed', 'score': '25', 'rank': '1', 'note': ''}})
check(app_row(APP_ID)['result'] is None, 'علامة أكبر من 20 ← مرفوضة (لا يُحفظ شيء من السطر)')
results_post('save', {APP_ID: {'result': 'hacked', 'score': '12', 'rank': '', 'note': ''}})
check(app_row(APP_ID)['result'] is None, 'نتيجة غير معروفة ← مرفوضة')
results_post('save', {APP_ID: {'result': 'passed', 'score': '12', 'rank': '-3', 'note': ''}})
check(app_row(APP_ID)['result'] is None, 'ترتيب سالب ← مرفوض')
results_post('save', {APP_ID: {'result': 'passed', 'score': '14,5', 'rank': '', 'note': 'احضر يوم <b>الأحد</b>'},
                      C_ID: {'result': 'reserve', 'score': '12.75', 'rank': '', 'note': ''}, B_ID: {'result': 'passed', 'score': '20', 'rank': '', 'note': ''}})
ra, rc, rb = app_row(APP_ID), app_row(C_ID), app_row(B_ID)
check(ra['result'] == 'passed' and ra['score'] == '14.50' and rc['result'] == 'reserve' and rc['score'] == '12.75', 'حفظ النتائج: العلامة بفاصلة أو نقطة (14,5 ← 14.50)')
check(rb['result'] is None, '  ونتيجة لطلب غير مقبول (بتجاوز الواجهة) ← مرفوضة')
results_post('autorank')
check(app_row(APP_ID)['result_rank'] == '1' and app_row(C_ID)['result_rank'] == '2', 'الترتيب التلقائي حسب العلامة (14,50 ← 1، 12,75 ← 2)')
s, _h, b = ca.text('GET', '/competitions/track/')
check('cmp-result' not in b, 'قبل النشر: المترشح لا يرى نتيجته')
s, _h, _b = ca.text('GET', '/competitions/track/result/')
check(s == 404, f'  وإشعار النتيجة غير موجود (404) ({s})')
results_post('publish')
check(q('SELECT results_published_at FROM competitions WHERE id = ?', [CID])[0]['results_published_at'] is not None, 'نشر النتائج')
s, _h, b = ca.text('GET', '/competitions/track/')
check('cmp-result is-passed' in b and '14,50' in b and 'احضر يوم &lt;b&gt;الأحد&lt;/b&gt;' in b, 'بعد النشر: المترشح يرى نتيجته وعلامته والملاحظة (مع تهريب HTML)')
check('12,75' not in b and 'أحمدي' not in b, '  ولا يرى نتائج غيره')
s, h, b = ca.text('GET', '/competitions/track/result/')
check(s == 200 and 'doc-result-value' in b and '14,50' in b and 'no-store' in h.get('cache-control', ''), 'إشعار النتيجة للطباعة (no-store)')
s, _h, b = cc.text('GET', '/competitions/track/')
check('cmp-result is-reserve' in b and '12,75' in b, 'مترشح آخر يرى نتيجته هو (القائمة الاحتياطية)')
results_post('unpublish')
s, _h, _b = ca.text('GET', '/competitions/track/result/')
check(s == 404, 'إلغاء النشر ← تختفي النتائج فوراً')
staff2 = Client()
staff2.login(*STAFF)
s, _h, _b = staff2.text('POST', RES, data={'csrfmiddlewaretoken': staff2.token('/admin/')[0], 'action': 'publish'})
check(s == 403 and q('SELECT results_published_at FROM competitions WHERE id = ?', [CID])[0]['results_published_at'] is None, f'موظف بلا صلاحية المسابقات: نشر النتائج ← 403 ({s})')
acts = [a['action'] for a in q('SELECT action FROM recruitment_audit WHERE competition_id = ? AND username = ?', [CID, 'miloudsellam'])]
check(all(a in acts for a in ('result', 'publish', 'unpublish')), 'سجل الاطلاع: تسجيل النتائج ونشرها وإلغاء النشر باسم الموظف')
s, h, raw = adm.req('GET', f'/admin/recruitment/export/?competition={CID}&status=accepted')
xml = zipfile.ZipFile(io.BytesIO(raw)).read('xl/worksheets/sheet1.xml').decode() if raw[:2] == b'PK' else ''
check('رقم التسجيل' in xml and 'النتيجة' in xml and '14,50' in xml and 'في القائمة الاحتياطية' in xml, 'التصدير: أعمدة الاستدعاء ورقم التسجيل والنتيجة والعلامة والترتيب')

# ----------------------------------------------------------------------------------------------
print('16) حذف معطيات المترشحين بعد انتهاء المسابقة (المدير العام)')
t, _ = adm.token(f'/admin/recruitment/purge/{CID}/')
adm.text('POST', f'/admin/recruitment/purge/{CID}/', data={'csrfmiddlewaretoken': t, 'confirm': 'حذف'})
check(app_count() > 0, 'قبل انتهاء الأجل: الحذف مرفوض')
s, _h, b = adm.text('POST', '/admin/competitions/', data={'csrfmiddlewaretoken': t, 'action': 'delete', 'ids[]': [str(CID)]})
check(int(q('SELECT COUNT(*) n FROM competitions WHERE id = ?', [CID])[0]['n']) == 1, 'حذف مسابقة لها طلبات من لوحة الإدارة ← مرفوض')
php(f"DB::update('competitions', ['closes_at' => date('Y-m-d', strtotime('-1 day'))], 'id = :id', ['id' => {CID}]);")
t, _ = adm.token(f'/admin/recruitment/purge/{CID}/')
adm.text('POST', f'/admin/recruitment/purge/{CID}/', data={'csrfmiddlewaretoken': t, 'confirm': 'نعم'})
check(app_count() > 0, 'بدون كتابة كلمة «حذف» ← مرفوض')
mine_files = [p for p in enc_files() if f'/recruitment/{CID}/' in p]
adm.text('POST', f'/admin/recruitment/purge/{CID}/', data={'csrfmiddlewaretoken': t, 'confirm': 'حذف'})
check(app_count() == 0 and not any(os.path.exists(p) for p in mine_files) and not os.path.isdir(os.path.join(SITE, f'storage/private/recruitment/{CID}')),
      'بعد الأجل: حُذفت الطلبات ووثائقها المشفّرة من القرص')
check(int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE action = 'purge' AND competition_id = ?", [CID])[0]['n']) == 1, 'عملية الحذف مسجّلة في سجل الاطلاع')

# ----------------------------------------------------------------------------------------------
php(f"DB::delete('competitions', 'id IN (' . DB::in([{CID}, {T['draft_id']}, {T['other_id']}]) . ')', [{CID}, {T['draft_id']}, {T['other_id']}]);")
# سطور سجل الاطلاع للمسابقات المؤقتة (السجل بلا مفاتيح أجنبية: لا يُحذف مع المسابقة) — حتى لا تظهر في لقطات الفصل 48
x(f"DELETE FROM recruitment_audit WHERE competition_id IN ({CID}, {T['draft_id']}, {T['other_id']})")
x(f"UPDATE users SET can_recruit = {int(old_perm)} WHERE username = 'hicham41'")
clear_ratelimit()
print(f'\n{"✓ كل اختبارات المسابقات نجحت" if failures == 0 else f"✗ {failures} اختبار فشل"}')
sys.exit(1 if failures else 0)
