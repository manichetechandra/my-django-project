#!/usr/bin/env python3
"""اختبارات الأمان للنسخة 2.0 (كل ثغرة أُصلحت لها اختبار يتأكد أنها لا تعود).

  python3 dev/security_check.py [http://127.0.0.1:8002] [/tmp/site2]

يحتاج الموقع التجريبي (bash dev/demo-site.sh) — يعدّل بعض السجلات ثم يعيدها كما كانت،
ويمسح عدادات محاولات الدخول (storage/ratelimit) قبل وبعد الاختبارات.
"""
import glob
import http.client
import os
import re
import subprocess
import sys
import time
import urllib.parse

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8002'
SITE = sys.argv[2] if len(sys.argv) > 2 else '/tmp/site2'
PHP = os.environ.get('PHP_BIN', '/tmp/tools/bin/php')
DB_NAME = os.environ.get('DB_NAME', 'dou_setif2')
U = urllib.parse.urlparse(BASE)
ADMIN = ('miloudsellam', 'AdminDemo2026')
STAFF = ('hicham41', 'StaffDemo2026')
STUDENT = ('1234566', 'Talib2026x')

failures = 0


def check(cond, label):
    global failures
    print(('  ✓ ' if cond else '  ✗ ') + label)
    if not cond:
        failures += 1


class Client:
    def __init__(self):
        self.cookies = {}

    def req(self, method, path, data=None, headers=None):
        c = http.client.HTTPConnection(U.hostname, U.port or 80, timeout=60)
        h = {'Host': U.netloc}
        if self.cookies:
            h['Cookie'] = '; '.join(f'{k}={v}' for k, v in self.cookies.items())
        body = None
        if data is not None:
            body = urllib.parse.urlencode(data)
            h['Content-Type'] = 'application/x-www-form-urlencoded'
        h.update(headers or {})
        c.request(method, path, body, h)
        r = c.getresponse()
        text = r.read().decode('utf-8', 'replace')
        hdrs = {}
        for k, v in r.getheaders():
            hdrs.setdefault(k.lower(), []).append(v)
            if k.lower() == 'set-cookie':
                name, _, rest = v.partition('=')
                self.cookies[name] = rest.split(';')[0]
        return r.status, {k: ', '.join(v) for k, v in hdrs.items()}, text

    def token(self, path):
        _s, _h, b = self.req('GET', path)
        m = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', b)
        return m.group(1) if m else ''

    def login(self, path, user, password, headers=None):
        t = self.token(path)
        return self.req('POST', path, {'csrfmiddlewaretoken': t, 'username': user, 'password': password}, headers)


def php(code):
    out = subprocess.run([PHP, '-r', 'require "app/bootstrap.php"; ' + code], cwd=SITE, capture_output=True, text=True,
                         env={**os.environ, 'DB_NAME': DB_NAME})
    if out.returncode != 0:
        print(out.stderr[-500:])
    return out.stdout.strip()


def sql(query, params='[]'):
    return php(f'echo json_encode(DB::query({query!r}, {params})->rowCount());')


def clear_ratelimit():
    for f in glob.glob(os.path.join(SITE, 'storage', 'ratelimit', '*.json')):
        os.remove(f)


def location(h):
    return h.get('location', '')


clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('1) واجهة دخول خاصة بالإدارة، منفصلة عن فضاء الطالب')
for who in (ADMIN, STAFF):
    c = Client()
    s, h, b = c.login('/students/login/', *who)
    check(s == 200 and 'اسم المستخدم أو كلمة المرور غير صحيحة' in b, f'فضاء الطالب يرفض حساب الموظف {who[0]} برسالة عامة')
    s, h, _ = c.req('GET', '/admin/')
    check(s == 302 and '/admin/login/' in location(h), f'  ولا جلسة له بعدها ({s})')
c = Client()
s, h, b = c.login('/admin/login/', *STUDENT)
check(s == 200 and 'لحساب موظف' in b, 'واجهة الإدارة ترفض حساب الطالب')
c = Client()
s, h, _ = c.login('/admin/login/', *ADMIN)
check(s == 302 and location(h).endswith('/admin/'), 'واجهة الإدارة تقبل المدير ← /admin/')
s, _h, b = c.req('GET', '/admin/')
check(s == 200, '  لوحة الإدارة تعمل بعد الدخول')
c = Client()
s, h, _ = c.login('/students/login/', *STUDENT)
check(s == 302 and '/students/dashboard/' in location(h), 'فضاء الطالب يقبل الطالب')
s, h, _ = Client().req('GET', '/students/admin/dashboard/')
check(s == 302 and '/admin/login/?next=' in location(h), 'لوحة متابعة الطلبات (بلا دخول) ← /admin/login/')
s, h, _ = Client().req('GET', '/admin/news/')
check(s == 302 and location(h).startswith('/admin/login/?next=%2Fadmin%2Fnews%2F'), '/admin/news/ (بلا دخول) ← /admin/login/?next=...')
s, h, b = Client().req('GET', '/admin/login/')
check('noindex' in b and 'autocomplete="current-password"' in b and 'remember_me' not in b, 'صفحة دخول الإدارة: noindex وبلا «تذكرني»')

# ----------------------------------------------------------------------------------------------
print('2) إعادة التوجيه لمواقع خارجية (Open Redirect)')
# (بلا نقطة في المسار: خادم php -S يغيّر SCRIPT_NAME للمسارات التي تشبه ملفاً مثل evil.com)
for path in ('/ar//evil/', '/ar/%5Cevil/', '/ar/%09/evil/', '/ar/%2F%2Fevil/', '/ar/%0d%0aSet-Cookie:x=1/'):
    s, h, _ = Client().req('GET', path)
    loc = location(h)
    check(s == 301 and not re.match(r'^(/|\\)[/\\\t]', loc) and not loc.startswith('http'), f'{path} ← {loc!r}')
for nxt in ('//evil.example/', '/\\evil.example/', '/\t/evil.example/', 'https://evil.example/', '/%09/evil.example'):
    c = Client()
    t = c.token('/students/login/')
    s, h, _ = c.req('POST', '/students/login/?next=' + urllib.parse.quote(nxt, safe=''),
                    {'csrfmiddlewaretoken': t, 'username': STUDENT[0], 'password': STUDENT[1]})
    loc = location(h)
    # مقبول: لوحة الطالب، أو مسار داخلي بـ "/" واحدة بلا أحرف تحكم (مثل "/%09/evil" وهو مسار في نفس الموقع)
    check(s == 302 and re.match(r'^/[^/\\]', loc) is not None and not re.search(r'[\x00-\x20]', loc), f'دخول الطالب next={nxt!r} ← {loc}')
c = Client()
s, h, _ = c.login('/admin/login/?next=' + urllib.parse.quote('//evil.example/'), *ADMIN)
check(location(h) == '/admin/', f'دخول الإدارة next=//evil.example/ ← {location(h)}')
c = Client()
s, h, _ = c.login('/admin/login/?next=' + urllib.parse.quote('/students/admin/applications/?status=pending'), *STAFF)
check(location(h) == '/students/admin/applications/?status=pending', 'دخول الإدارة يرجع لصفحة الموظفين المطلوبة')
s, h, _ = Client().req('GET', '/news')
check(s == 301 and location(h) == '/news/', 'APPEND_SLASH: /news ← /news/')

# ----------------------------------------------------------------------------------------------
print('3) حد محاولات الدخول الفاشلة (تخمين كلمات المرور)')
clear_ratelimit()
c = Client()
for i in range(5):
    # تغيير X-Forwarded-For لا يتجاوز الحد (لا خوادم وسيطة موثوقة)
    c.login('/admin/login/', STAFF[0], 'wrong-password-%d' % i, {'X-Forwarded-For': '10.9.8.%d' % i})
s, h, b = c.login('/admin/login/', *STAFF)
check(s == 429 and 'retry-after' in h and 'محاولات دخول فاشلة كثيرة' in b, f'بعد 5 محاولات خاطئة: حتى كلمة المرور الصحيحة تُرفض ({s})')
s, h, b = Client().login('/students/login/', STAFF[0], STAFF[1])
check(s == 429, f'  ونفس الحد في فضاء الطالب (عداد مشترك) ({s})')
s, h, _ = Client().login('/admin/login/', *ADMIN)
check(s == 302, '  حساب آخر من نفس العنوان ما زال يدخل')
clear_ratelimit()
s, h, _ = Client().login('/admin/login/', *STAFF)
check(s == 302, '  بعد انتهاء المدة (مسح العداد) يدخل الموظف')

# ----------------------------------------------------------------------------------------------
print('4) XSS عبر الروابط (javascript:) وقوالب JavaScript')
check(php("echo json_encode([is_allowed_link('javascript:alert(1)'), is_allowed_link(' JaVa\\tScript:alert(1)'), is_allowed_link('data:text/html,x'), is_allowed_link('//evil.example'), is_allowed_link('/news/'), is_allowed_link('https://www.mesrs.dz/'), is_allowed_link('#top')]);")
      == '[false,false,false,false,true,true,true]', 'حقل «الرابط» في لوحة الإدارة يقبل /... و https:// فقط')
check(php("echo json_encode([link_url('javascript:alert(1)'), link_url(\"java\\nscript:alert(1)\"), link_url('/news/'), safe_url('javascript://x%0Aalert(1)'), safe_url('https://a.dz/')]);")
      == '["#","#","\\/news\\/","#","https:\\/\\/a.dz\\/"]', 'link_url و safe_url تُبطل javascript: في البيانات القديمة')
old_link = php("echo DB::value('SELECT link FROM sliders WHERE id = (SELECT MIN(id) FROM sliders)');")
old_site = php("echo DB::value('SELECT website FROM departements WHERE id = (SELECT MIN(id) FROM departements)');")
old_docs = php("echo json_encode(DB::value('SELECT required_documents FROM application_types WHERE id = (SELECT MIN(id) FROM application_types)'));")
slug = php("echo DB::value('SELECT slug FROM departements WHERE id = (SELECT MIN(id) FROM departements)');")
php("DB::query(\"UPDATE sliders SET link = 'javascript:alert(1)', is_active = 1 WHERE id = (SELECT * FROM (SELECT MIN(id) FROM sliders) t)\");"
    "DB::query(\"UPDATE departements SET website = 'javascript://comment%0Aalert(1)' WHERE id = (SELECT * FROM (SELECT MIN(id) FROM departements) t)\");"
    "DB::query('UPDATE application_types SET required_documents = ? WHERE id = (SELECT * FROM (SELECT MIN(id) FROM application_types) t)', ['`' . chr(36) . '{alert(1)}`' . chr(10) . 'وثيقة']);")
_s, _h, b = Client().req('GET', '/')
check('javascript:' not in ''.join(re.findall(r'href="([^"]*)"', b)).lower(), 'الصفحة الرئيسية: لا رابط javascript: (سلايدر)')
_s, _h, b = Client().req('GET', '/departements/' + urllib.parse.quote(slug) + '/')
check('href="javascript' not in b.lower(), 'صفحة القسم: الموقع الإلكتروني javascript:// لا يُعرض كرابط')
c = Client()
c.login('/students/login/', *STUDENT)
_s, _h, b = c.req('GET', '/students/applications/create/')
check('documents: `' not in b and re.search(r'documents: "[^"]*\$\{alert\(1\)\}', b) is not None, 'طلب جديد: الوثائق المطلوبة نص JSON وليست قالب `...${}`')
php("DB::update('sliders', ['link' => " + (repr(old_link) if old_link else 'null') + "], 'id = (SELECT * FROM (SELECT MIN(id) FROM sliders) t)');"
    "DB::update('departements', ['website' => " + (repr(old_site) if old_site else 'null') + "], 'id = (SELECT * FROM (SELECT MIN(id) FROM departements) t)');"
    "DB::update('application_types', ['required_documents' => json_decode(" + repr(old_docs) + ")], 'id = (SELECT * FROM (SELECT MIN(id) FROM application_types) t)');")

# ----------------------------------------------------------------------------------------------
print('5) تسجيل الخروج عبر رابط من موقع آخر (CSRF)')
c = Client()
c.login('/students/login/', *STUDENT)
s, h, _ = c.req('GET', '/students/logout/')
s2, _h, _ = c.req('GET', '/students/dashboard/')
check(s == 302 and s2 == 200, 'GET /students/logout/ بلا رمز لا يسجّل الخروج')
_s, _h, b = c.req('GET', '/students/dashboard/')
m = re.search(r'href="([^"]*/students/logout/\?t=[0-9a-f]{32})"', b)
check(m is not None, 'رابط الخروج في القائمة يحمل رمزاً')
if m:
    c.req('GET', m.group(1))
    s, h, _ = c.req('GET', '/students/dashboard/')
    check(s == 302 and '/students/login/' in location(h), '  ومعه يسجّل الخروج فعلاً')
c = Client()
c.login('/admin/login/', *ADMIN)
s, h, _ = c.req('GET', '/admin/logout/')
s2, _h, _ = c.req('GET', '/admin/')
check(s2 == 200, 'GET /admin/logout/ بلا رمز لا يسجّل الخروج')

# ----------------------------------------------------------------------------------------------
print('6) ترويسات الأمان ووضع التطوير')
s, h, b = Client().req('GET', '/')
check(h.get('x-content-type-options') == 'nosniff', 'X-Content-Type-Options: nosniff')
check('strict-origin-when-cross-origin' in h.get('referrer-policy', ''), 'Referrer-Policy')
check("base-uri 'self'" in h.get('content-security-policy', '') and "object-src 'none'" in h.get('content-security-policy', ''), 'Content-Security-Policy (base-uri, object-src)')
check('x-powered-by' not in h, 'لا X-Powered-By (إصدار PHP مخفي)')
check('httponly' in h.get('set-cookie', '').lower() and 'samesite=lax' in h.get('set-cookie', '').lower(), 'كوكي الجلسة HttpOnly و SameSite')
check(php("$_SERVER = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'dou-setif.dz', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6']; echo json_encode(is_local_request());") == 'false',
      'وضع التطوير لا يُفعَّل خلف خادم وسيط (REMOTE_ADDR=127.0.0.1 مع اسم نطاق حقيقي)')
check(php("$_SERVER = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'localhost']; echo json_encode(is_local_request());") == 'true', '  ويبقى مفعّلاً على http://localhost')
check(php("$_SERVER = ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6']; echo client_ip();") == '1.2.3.4', 'client_ip يتجاهل X-Forwarded-For من مصدر غير موثوق')
check(php("$GLOBALS['__config']['app']['trusted_proxies'] = ['10.0.0.0/8']; $_SERVER = ['REMOTE_ADDR' => '10.1.2.3', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 10.0.0.9']; echo client_ip();") == '6.6.6.6', '  ويقبله من خادم وسيط موثوق (CIDR)')

# ----------------------------------------------------------------------------------------------
print('7) نموذج «اتصل بنا»: الرسائل الآلية والإغراق')
clear_ratelimit()
before = php("echo ContactMessage::count();")
c = Client()
t = c.token('/contact/')
form = {'csrfmiddlewaretoken': t, 'full_name': 'Bot', 'email': 'bot@example.com', 'phone': '0555000000', 'subject': 'general', 'message': 'spam spam spam spam'}
c.req('POST', '/contact/', {**form, 'contact_website': 'http://spam.example'})
check(php("echo ContactMessage::count();") == before, 'الحقل الخفي (honeypot) مملوء ← لا تُحفظ الرسالة')
codes = []
for i in range(6):
    t = c.token('/contact/')
    s, _h, _b = c.req('POST', '/contact/', {**form, 'csrfmiddlewaretoken': t, 'full_name': 'Test %d' % i})
    codes.append(s)
check(codes[:5] == [302] * 5 and codes[5] == 429, f'5 رسائل ثم 429 ({codes})')
php("DB::query(\"DELETE FROM contact_messages WHERE email = 'bot@example.com'\");")
clear_ratelimit()

# ----------------------------------------------------------------------------------------------
print('8) أخرى')
t0 = time.time()
s, _h, _b = Client().req('GET', '/search/?q=' + urllib.parse.quote(' '.join('كلمة%d' % i for i in range(1500))))
check(s == 200 and time.time() - t0 < 1.0, f'بحث بـ 1500 كلمة: محدود (100 حرف، 8 كلمات) ← {time.time() - t0:.2f}s')
check(php("echo json_encode([csv_safe('=HYPERLINK(\"http://x\")'), csv_safe('+1'), csv_safe('@SUM(A1)'), csv_safe('-2'), csv_safe('أحمد'), csv_safe(5)]);")
      == '["\'=HYPERLINK(\\"http:\\/\\/x\\")","\'+1","\'@SUM(A1)","\'-2","\\u0623\\u062d\\u0645\\u062f",5]', 'تصدير CSV: الخلايا التي تبدأ بـ = + - @ تُعرض كنص')
c = Client()
c.login('/students/login/', *STUDENT)
_s, _h, b = c.req('GET', '/students/student-card/')
check('bwipjs' not in b and 'data:image/svg+xml;base64,' in b, 'البطاقة الرقمية: الباركود يُولَّد محلياً (لا يُرسل رقم التسجيل لموقع خارجي)')
_s, _h, b = Client().req('GET', '/')
cdn = re.findall(r'<(?:link|script)[^>]+(?:href|src)="https://(?:cdn\.jsdelivr\.net|code\.jquery\.com|cdnjs\.cloudflare\.com)[^>]*>', b)
check(cdn and all('integrity="sha' in tag and 'crossorigin="anonymous"' in tag for tag in cdn), f'ملفات CDN بتوقيع SRI ({len(cdn)} ملفات)')
check('bootstrap-rtl@3.4.0' not in b, 'لا رابط لحزمة bootstrap-rtl@3.4.0 غير الموجودة')
check(php("echo json_encode(Auth::verifyPassword('x', '!unusable:set-a-new-password-with-database/tools/set_password.php'));") == 'false',
      'كلمات المرور في data.sql غير قابلة للاستعمال')
data_sql = open(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'php', 'database', 'data.sql'), encoding='utf-8').read()
check('pbkdf2_sha256$' not in data_sql, 'data.sql بلا تشفير كلمات مرور Django')

# ----------------------------------------------------------------------------------------------
print('9) «نسيت كلمة مرور المدير؟» من المتصفح (/admin/recover/)')


def recover_page(c):
    _s, _h, b = c.req('GET', '/admin/recover/')
    m = re.search(r'id="recover-file">(recover-[0-9a-f]{12}\.txt)<', b)
    t = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', b)
    return (m.group(1) if m else ''), (t.group(1) if t else ''), b


STORAGE = os.path.join(SITE, 'storage')
c = Client()
fname, t, b = recover_page(c)
check(fname != '', f'الصفحة تعطي اسم ملف عشوائياً ({fname})')
s, h, b = c.req('POST', '/admin/recover/', {'csrfmiddlewaretoken': t, 'username': ADMIN[0], 'password1': 'Hacked#2026x', 'password2': 'Hacked#2026x'})
check('لم يُعثر' in b and 'name="password1"' not in b, 'بدون الملف: لا نموذج ولا تغيير لكلمة المرور')
other = Client()
f2, _t2, _b2 = recover_page(other)
open(os.path.join(STORAGE, f2), 'w').close()
s, h, b = c.req('POST', '/admin/recover/', {'csrfmiddlewaretoken': t})
check('لم يُعثر' in b, 'ملف أنشأه زائر آخر لرمزه لا يصلح لهذه الجلسة')
os.remove(os.path.join(STORAGE, f2))
open(os.path.join(STORAGE, fname + '.txt'), 'w').close()  # Windows يضيف .txt مرة ثانية إن كانت الامتدادات مخفية
s, h, _b = c.req('POST', '/admin/recover/', {'csrfmiddlewaretoken': t})
_s, _h, b = c.req('GET', '/admin/recover/')
check(s == 302 and 'name="password1"' in b and f'value="{ADMIN[0]}"' in b, 'مع الملف (حتى باسم .txt.txt): نموذج كلمة المرور واسم المدير الحالي')
t = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', b).group(1)
s, h, b = c.req('POST', '/admin/recover/', {'csrfmiddlewaretoken': t, 'username': ADMIN[0], 'password1': '12345678', 'password2': '12345678'})
check(s == 200 and 'أرقام' in b, 'كلمة مرور ضعيفة تُرفض')
s, h, b = c.req('POST', '/admin/recover/', {'csrfmiddlewaretoken': t, 'username': ADMIN[0], 'password1': 'Recovered#2026', 'password2': 'Recovered#2026'})
check(s == 302 and location(h) == '/admin/login/' and not glob.glob(os.path.join(STORAGE, 'recover-*')), 'الحفظ ← صفحة الدخول، والملف حُذف')
s, h, _b = Client().login('/admin/login/', ADMIN[0], 'Recovered#2026')
check(s == 302, 'الدخول بكلمة المرور الجديدة')
s, h, _b = Client().login('/admin/login/', *ADMIN)
check(s == 200, 'كلمة المرور القديمة لم تعد تعمل')
php("DB::update('users', ['password' => Auth::hashPassword(" + repr(ADMIN[1]) + ")], 'username = :u', ['u' => " + repr(ADMIN[0]) + "]);")
s, h, _b = Client().login('/admin/login/', *ADMIN)
check(s == 302, '  (إرجاع كلمة مرور المدير التجريبية)')
c = Client()
c.login('/admin/login/', *STAFF)
s, h, _b = c.req('GET', '/admin/recover/')
check(s == 302 and location(h) == '/admin/password/', 'المتصل فعلاً يُحوَّل إلى «تغيير كلمة المرور» العادي')

clear_ratelimit()
print('\n' + ('✓ كل اختبارات الأمان نجحت' if not failures else f'✗ {failures} اختبار فشل'))
sys.exit(1 if failures else 0)
