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


def track(code, secret, client=None, headers=None):
    cl = client or Client()
    t, _ = cl.token('/competitions/track/')
    return cl.text('POST', '/competitions/track/', data={'csrfmiddlewaretoken': t, 'tracking_code': code, 'secret': secret}, headers=headers)


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
               '/admin/recruitment/export/?mode=full', '/admin/recruitment/audit/', f'/admin/recruitment/purge/{CID}/']
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
s, h, _b = adm.text('POST', f'/admin/recruitment/applications/{APP_ID}/', data={'csrfmiddlewaretoken': t, 'status': 'incomplete',
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
print('12) حذف معطيات المترشحين بعد انتهاء المسابقة (المدير العام)')
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
x(f"UPDATE users SET can_recruit = {int(old_perm)} WHERE username = 'hicham41'")
clear_ratelimit()
print(f'\n{"✓ كل اختبارات المسابقات نجحت" if failures == 0 else f"✗ {failures} اختبار فشل"}')
sys.exit(1 if failures else 0)
