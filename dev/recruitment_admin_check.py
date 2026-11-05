#!/usr/bin/env python3
"""اختبار صفحات دراسة الملفات في لوحة الإدارة (الفصل 48 من الكتاب) على الموقع التجريبي.

  DB_NAME=dou_setif2 python3 dev/recruitment_admin_check.py [http://127.0.0.1:8002] [/tmp/site2]

يحتاج الموقع التجريبي (bash dev/demo-site.sh): يبحث في طلبات المسابقة التجريبية الأولى (كريم C26-KMZ4N7Q2)، ثم ينشئ
مسابقة مؤقتة بستة طلبات ويمر بالدورة كاملة: القائمة الجانبية، البحث، الملف والوثيقة، القرار، الاستدعاء، النتائج،
التصدير، سجل الاطلاع والصلاحيات، ثم الحذف. ينظف نفسه في النهاية (المسابقة المؤقتة وأسطر السجل الجديدة وصلاحية
hicham41 وعدادات المحاولات). يكمّل dev/recruitment_check.py (اختبارات الأمان).
"""
import datetime, glob, html, http.client, io, json, os, re, subprocess, sys, urllib.parse, uuid, zipfile

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8002'
SITE = sys.argv[2] if len(sys.argv) > 2 else '/tmp/site2'
PHP = '/tmp/tools/bin/php'
DB_NAME = os.environ.get('DB_NAME', 'dou_setif2')
U = urllib.parse.urlparse(BASE)
fails = 0


def check(cond, label):
    global fails
    print(('  ✓ ' if cond else '  ✗ ') + label)
    if not cond:
        fails += 1


def php(code):
    out = subprocess.run([PHP, '-r', 'require "app/bootstrap.php"; ' + code], cwd=SITE, capture_output=True, text=True, env={**os.environ, 'DB_NAME': DB_NAME})
    if out.returncode != 0:
        print(out.stderr[-1500:])
    return out.stdout.strip()


def q(sql, params=None):
    return json.loads(php(f'echo json_encode(DB::all({sql!r}, {json.dumps(params or [])}));') or '[]')


def x(sql, params=None):
    php(f'DB::query({sql!r}, json_decode({json.dumps(json.dumps(params or []))}, true));')


class Client:
    def __init__(self):
        self.cookies = {}

    def req(self, method, path, data=None):
        c = http.client.HTTPConnection(U.hostname, U.port or 80, timeout=60)
        h = {'Host': U.netloc}
        if self.cookies:
            h['Cookie'] = '; '.join(f'{k}={v}' for k, v in self.cookies.items())
        body = None
        if data is not None:
            body = urllib.parse.urlencode(data, doseq=True)
            h['Content-Type'] = 'application/x-www-form-urlencoded'
        c.request(method, path, body, h)
        r = c.getresponse()
        raw = r.read()
        hd = {}
        for k, v in r.getheaders():
            hd.setdefault(k.lower(), []).append(v)
            if k.lower() == 'set-cookie':
                n, _, rest = v.partition('=')
                self.cookies[n] = rest.split(';')[0]
        return r.status, {k: ', '.join(v) for k, v in hd.items()}, raw

    def text(self, method, path, data=None):
        s, h, raw = self.req(method, path, data)
        return s, h, raw.decode('utf-8', 'replace')

    def token(self, path):
        _s, _h, b = self.text('GET', path)
        m = re.search(r'name="csrfmiddlewaretoken" value="([^"]+)"', b)
        return (m.group(1) if m else ''), b

    def login(self, user, pw):
        t, _ = self.token('/admin/login/')
        return self.text('POST', '/admin/login/', {'csrfmiddlewaretoken': t, 'username': user, 'password': pw})

    def post_follow(self, path, data):
        """POST ثم اتباع إعادة التوجيه ← (الصفحة، نصوص التنبيهات)"""
        s, h, b = self.text('POST', path, data)
        if s in (301, 302, 303):
            loc = urllib.parse.urlparse(h.get('location', ''))
            s, h, b = self.text('GET', loc.path + ('?' + loc.query if loc.query else ''))
        alerts = [re.sub(r'<[^>]+>|×', '', html.unescape(a)).strip() for a in re.findall(r'<div class="alert alert-\w+ alert-dismissible" role="alert">(.*?)</div>', b, re.S)]
        return b, alerts


def rows_in_list(b):
    return re.findall(r'class="recruit-code">(C26-[A-Z0-9]{8})</a></td>', b)


def clear_rl():
    for f in glob.glob(os.path.join(SITE, 'storage', 'ratelimit', '*.json')):
        os.remove(f)


mark = int(q('SELECT COALESCE(MAX(id), 0) m FROM recruitment_audit')[0]['m'])
sec_log = os.path.join(SITE, 'storage', 'logs', 'security.log')
sec_size = os.path.getsize(sec_log) if os.path.exists(sec_log) else 0
old_perm = int(q("SELECT can_recruit FROM users WHERE username = 'hicham41'")[0]['can_recruit'])
clear_rl()
T = None
try:
    adm = Client()
    adm.login('miloudsellam', 'AdminDemo2026')

    # ------------------------------------------------------------------ مسابقة مؤقتة بستة طلبات
    T = json.loads(php(r"""
$c = Competition::create(['title' => 'مسابقة اختبار الفصل 48', 'opens_at' => date('Y-m-d', strtotime('-2 days')), 'closes_at' => date('Y-m-d', strtotime('+5 days')),
    'conditions' => 'شرط', 'status' => 'published', 'online_apply' => 1]);
$pa = CompetitionPost::create(['competition_id' => (int) $c['id'], 'title' => 'متصرف إداري', 'positions' => 2, 'order' => 1]);
$pb = CompetitionPost::create(['competition_id' => (int) $c['id'], 'title' => 'ملحق إداري', 'positions' => 3, 'order' => 2]);
$r = CompetitionRequirement::create(['competition_id' => (int) $c['id'], 'label' => 'طلب خطي', 'is_required' => 1, 'order' => 1]);
$ex = CompetitionExam::create(['competition_id' => (int) $c['id'], 'title' => 'المقابلة الشفهية — الفوج الأول', 'starts_at' => date('Y-m-d', strtotime('+30 days')) . ' 08:30:00']);
$c = Competition::find((int) $c['id']);
$posts = $c['posts']; $reqs = $c['requirements'];
$people = [['زروقي', 'أمين', 'ZEROUKI', 'Amine', 0, '109900101000111001'], ['بوعلام', 'سمير', 'BOUALEM', 'Samir', 0, '109900101000111002'],
           ['عمراني', 'ليلى', 'AMRANI', 'Leila', 0, '109900101000111003'], ['دحماني', 'نادية', 'DAHMANI', 'Nadia', 0, '109900101000111004'],
           ['أوراغ', 'كمال', 'OURAGH', 'Kamel', 0, '009900101000111005'], ['شريفي', 'هدى', 'CHERIFI', 'Houda', 1, '109900101000111006']];
$ids = []; $codes = [];
foreach ($people as $k => [$ln, $fn, $lnl, $fnl, $pi, $nin]) {
    $tmp = tempnam(sys_get_temp_dir(), 'p48');
    file_put_contents($tmp, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n" . str_repeat('x', 200) . "\ntrailer << /Root 1 0 R >>\n%%EOF\n");
    $d = ['post_id' => (int) $posts[$pi]['id'], 'last_name' => $ln, 'first_name' => $fn, 'last_name_latin' => $lnl, 'first_name_latin' => $fnl,
          'gender' => 'M', 'birth_date' => '1990-01-01', 'birth_place' => 'سطيف', 'nin' => $nin, 'phone' => '0550000' . (100 + $k), 'email' => '',
          'address' => 'حي الاختبار', 'wilaya' => 'سطيف', 'national_service' => 'na', 'diploma' => 'ليسانس', 'specialty' => 'قانون', 'graduation_year' => 2015, 'experience_years' => 1,
          'doc_' . $reqs[0]['id'] => ['name' => 'demande-' . $k . '.pdf', 'tmp_name' => $tmp, 'size' => filesize($tmp), 'error' => 0]];
    [$app] = recruit_store_application($c, $d, $posts, $reqs);
    @unlink($tmp);
    $ids[] = (int) $app['id']; $codes[] = (string) $app['tracking_code'];
}
echo json_encode(['cid' => (int) $c['id'], 'exam' => (int) $ex['id'], 'ids' => $ids, 'codes' => $codes, 'pa' => (int) $pa['id'], 'pb' => (int) $pb['id']]);
"""))
    CID, EX = T['cid'], T['exam']
    A1, A2, A3, A4, A5, B1 = T['ids']
    LIST = f'/admin/recruitment/applications/?competition={CID}'
    seat = lambda i: q('SELECT seat_number s FROM competition_applications WHERE id = ?', [i])[0]['s']
    exam_of = lambda i: q('SELECT exam_id e FROM competition_applications WHERE id = ?', [i])[0]['e']
    status_of = lambda i: q('SELECT status s FROM competition_applications WHERE id = ?', [i])[0]['s']

    print('1) القائمة الجانبية والرئيسية')
    s, _h, b = adm.text('GET', '/admin/')
    pend = int(q("SELECT COUNT(*) n FROM competition_applications WHERE status = 'pending'")[0]['n'])
    m = re.search(r'طلبات الترشح\s*<span class="badge admin-badge">(\d+)</span>', b)
    check(m is not None and int(m.group(1)) == pend, f'شارة «طلبات الترشح» = عدد الطلبات قيد الانتظار ({pend})')
    check('نتائج المسابقات' in b and 'سجل الاطلاع على الملفات' in b, 'المدير العام: «نتائج المسابقات» و«سجل الاطلاع على الملفات»')
    check('جداول غير موجودة' not in b and 'ترقية قاعدة البيانات ناقصة' not in b, 'لا تنبيه ترقية والجداول موجودة')
    groups = re.findall(r'<div class="admin-nav-group">([^<]+)</div>', b)
    check(groups.count('مسابقات التوظيف') == 1, 'الصفحات الخاصة في مجموعة «مسابقات التوظيف» نفسها (مجموعة واحدة)')

    print('2) البحث والتصفية')
    _s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=c26kmz4n7q2')
    check(rows_in_list(b) == ['C26-KMZ4N7Q2'], 'رقم متابعة بحروف صغيرة وبلا شرطة ← الطلب وحده')
    _s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=' + urllib.parse.quote('1099 3110 2000 2158 90'))
    check(rows_in_list(b) == ['C26-KMZ4N7Q2'], 'رقم التعريف (18 رقماً، ولو بمسافات) ← طلبه، بالبصمة')
    _s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=109931102000215891')
    check(rows_in_list(b) == [] and 'مطابقة للتصفية' in b, 'رقم تعريف لم يترشح به أحد ← لا شيء')
    s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=%25')
    check(s == 200 and rows_in_list(b) == [], 'البحث بـ % ← لا خطأ ولا كل الطلبات')
    _s, _h, b = adm.text('GET', '/admin/recruitment/applications/?q=' + urllib.parse.quote('مزياني'))
    check(rows_in_list(b) == ['C26-KMZ4N7Q2'], 'جزء من اللقب ← الطلبات التي فيه')
    _s, _h, b = adm.text('GET', LIST)
    check(len(rows_in_list(b)) == 6 and 'name="post"' in b and 'المقابلة الشفهية — الفوج الأول' in b and '(0)</option>' in b,
          'اختيار المسابقة: قائمة الرتب، والموعد بعدد مستدعين 0')
    check(re.search(r'قيد الانتظار <span class="badge">6</span>', b) is not None, 'ألسنة الحالات بأعدادها للمسابقة المختارة')
    sql_audit = int(q('SELECT COUNT(*) n FROM recruitment_audit WHERE id > ? AND action = ?', [mark, 'export'])[0]['n'])
    check(sql_audit == 0, 'فتح القائمة لا يُكتب في السجل')

    print('3) الملف والوثيقة')
    karim = int(q("SELECT id FROM competition_applications WHERE tracking_code = 'C26-KMZ4N7Q2'")[0]['id'])
    n0 = int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE application_id = ? AND action = 'view'", [karim])[0]['n'])
    adm.text('GET', f'/admin/recruitment/applications/{karim}/')
    _s, _h, b = adm.text('GET', f'/admin/recruitment/applications/{karim}/')
    n1 = int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE application_id = ? AND action = 'view'", [karim])[0]['n'])
    check(n1 == n0 + 2, 'كل فتح للملف سطر view في السجل')
    check('109931102000215890' in b and 'سنة يوم الترشح' in b and 'tel:0770112233' in b, 'المعطيات المفكوكة: رقم التعريف، العمر يوم الترشح، رابط tel:')
    check('الطلب السابق' in b and 'الطلب التالي' in b and 'btn-group' not in b.split('admin-header')[1][:600], 'زرّا التنقل بلا btn-group')
    fid = int(q('SELECT id FROM competition_application_files WHERE application_id = ? ORDER BY id LIMIT 1', [karim])[0]['id'])
    s, h, raw = adm.req('GET', f'/admin/recruitment/files/{fid}/')
    csp = h.get('content-security-policy', '')
    check(s == 200 and raw.startswith(b'%PDF') and 'no-store' in h.get('cache-control', '') and h.get('content-disposition', '').startswith('inline'),
          'عرض: PDF مفكوك، no-store، inline')
    check("default-src 'none'" in csp and "object-src 'self'" in csp and "form-action 'none'" in csp and csp.count('Content-Security-Policy') == 0,
          'سياسة محتوى خاصة بالوثيقة')
    s, h, raw = adm.req('GET', f'/admin/recruitment/files/{fid}/?dl=1')
    cd = urllib.parse.unquote(h.get('content-disposition', ''))
    check(cd.startswith('attachment') and "filename*=UTF-8''C26-KMZ4N7Q2 - " in cd, 'تحميل: attachment باسم «رقم المتابعة - الاسم الأصلي»')
    det = [r['details'] for r in q("SELECT details FROM recruitment_audit WHERE application_id = ? AND action = 'file' AND id > ? ORDER BY id", [karim, mark])]
    check(len(det) == 2 and det[0].startswith('عرض: ') and det[1].startswith('تحميل: '), 'سطرا «فتح وثيقة»: عرض ثم تحميل')
    old = q('SELECT sha256 FROM competition_application_files WHERE id = ?', [fid])[0]['sha256']
    x('UPDATE competition_application_files SET sha256 = ? WHERE id = ?', [old[:-1] + ('0' if old[-1] != '0' else '1'), fid])
    s, _h, b = adm.text('GET', f'/admin/recruitment/files/{fid}/')
    x('UPDATE competition_application_files SET sha256 = ? WHERE id = ?', [old, fid])
    log = open(sec_log, encoding='utf-8').read()[sec_size:] if os.path.exists(sec_log) else ''
    check(s == 404 and 'فشل التحقق من سلامة الملف' in b and 'recruitment_integrity_failed' in log, 'بصمة معدّلة ← 404 وسطر في سجل الأمان')
    s, _h, _b = adm.req('GET', f'/admin/recruitment/files/{fid}/')
    check(s == 200, 'البصمة الأصلية ← تفتح من جديد')

    print('4) القرار')
    def review(app_id, status, until=None, msg='', notes=''):
        t, _ = adm.token(f'/admin/recruitment/applications/{app_id}/')
        d = {'csrfmiddlewaretoken': t, 'form': 'review', 'status': status, 'candidate_message': msg, 'internal_notes': notes}
        if until is not None:
            d['complete_until'] = until
        return adm.post_follow(f'/admin/recruitment/applications/{app_id}/', d)
    b, al = review(A1, 'incomplete', '', 'كشف النقاط غير واضح', 'ملاحظة')
    week = (datetime.date.today() + datetime.timedelta(days=7)).isoformat()
    row = q('SELECT status, complete_until, candidate_message FROM competition_applications WHERE id = ?', [A1])[0]
    check(row['status'] == 'incomplete' and row['complete_until'] == week, f'«ملف ناقص» بلا تاريخ ← الأجل الافتراضي بعد أسبوع ({week})')
    hist = [(r['action'], r['details']) for r in q('SELECT action, details FROM recruitment_audit WHERE application_id = ? AND id > ? ORDER BY id', [A1, mark])]
    check(('status', 'قيد الانتظار ← ملف ناقص — أجل الإكمال: ' + datetime.date.fromisoformat(week).strftime('%d/%m/%Y')) in hist
          and ('update', 'الرسالة للمترشح والملاحظات الداخلية') in hist, 'السجل: تغيير الحالة بالأجل، وتعديل الرسالة والملاحظات')
    check(any('تم حفظ التعديلات على طلب' in a for a in al), 'رسالة الحفظ')
    yesterday = (datetime.date.today() - datetime.timedelta(days=1)).isoformat()
    b, al = review(A3, 'incomplete', yesterday)
    check(status_of(A3) == 'pending' and any('أجل إكمال الملف يجب أن يكون تاريخاً صحيحاً اليوم أو بعده.' in a for a in al), 'أجل مضى ← مرفوض في الخادم')
    b, al = review(A1, 'incomplete', '', 'كشف النقاط غير واضح', 'ملاحظة')
    check(any('لا توجد تعديلات.' in a for a in al), 'حفظ بلا تغيير ← «لا توجد تعديلات.»')

    print('5) الإجراءات الجماعية والاستدعاء')
    def bulk(do, ids, exam=None, new_status=None):
        t, _ = adm.token(LIST)
        d = {'csrfmiddlewaretoken': t, 'do': do, 'ids[]': [str(i) for i in ids]}
        if exam is not None:
            d['exam_id'] = str(exam)
        if new_status is not None:
            d['new_status'] = new_status
        return adm.post_follow(LIST, d)
    b, al = bulk('status', [A1, A2, A3, A4, B1], new_status='accepted')
    check(any('تم تغيير حالة 5 طلب إلى «ملف مقبول».' in a for a in al), 'تغيير جماعي للحالة (5 طلبات)')
    b, al = bulk('convoke', [A1, A2, A3, A4, A5, B1], exam=EX)
    check(any('تم استدعاء 5 مترشح' in a and 'تم تجاهل 1 طلب لأن ملفه غير مقبول.' in a for a in al), 'استدعاء: 5 مستدعين و1 متجاهل')
    seats = {i: seat(i) for i in (A1, A2, A3, A4, B1)}
    check([seats[A2], seats[A4], seats[A1], seats[B1], seats[A3]] == ['001', '002', '003', '004', '005'],
          f'أرقام التسجيل بترتيب اللقب: بوعلام، دحماني، زروقي، شريفي، عمراني ← {seats}')
    check('(5)</option>' in b, 'عدد المستدعين في قائمة المواعيد صار 5')
    review(A5, 'accepted')
    b, al = bulk('convoke', [A1, A2, A3, A4, A5, B1], exam=EX)
    check(any(a.startswith('تم استدعاء 1 مترشح') and 'تجاهل' not in a for a in al) and seat(A5) == '006' and seat(A2) == '001',
          'استدعاء جديد: مترشح واحد، والجديد 006 ولو سبق في الترتيب الأبجدي، والقدامى كما هم')
    b, al = bulk('unconvoke', [A1])
    check(exam_of(A1) is None and seat(A1) == '003' and any('تم إلغاء استدعاء 1 مترشح.' in a for a in al), 'إلغاء الاستدعاء يترك الرقم')
    bulk('convoke', [A1], exam=EX)
    check(int(exam_of(A1)) == EX and seat(A1) == '003', 'إعادة الاستدعاء ← الرقم القديم')
    review(A2, 'under_review')
    det = [r['details'] for r in q("SELECT details FROM recruitment_audit WHERE application_id = ? AND action = 'unconvoke' AND id > ?", [A2, mark])]
    check(exam_of(A2) is None and 'بسبب تغيير الحالة' in det, 'سحب القبول ← «إلغاء الاستدعاء — بسبب تغيير الحالة»')
    review(A2, 'accepted')
    t, _ = adm.token(f'/admin/recruitment/applications/{A2}/')
    b, al = adm.post_follow(f'/admin/recruitment/applications/{A2}/', {'csrfmiddlewaretoken': t, 'form': 'outcome', 'exam_id': str(EX), 'result': '', 'score': '', 'result_rank': '', 'result_note': ''})
    check(int(exam_of(A2)) == EX and seat(A2) == '001' and any('تم حفظ الاستدعاء والنتيجة لطلب' in a for a in al), 'الاستدعاء من صندوق «الاستدعاء والنتيجة» برقمه القديم')
    check('رقم التسجيل: <strong class="recruit-code">001</strong>' in b, 'الصندوق يعرض رقم التسجيل')

    print('6) النتائج')
    RES = f'/admin/recruitment/results/?competition={CID}'
    _s, _h, b = adm.text('GET', '/admin/recruitment/results/')
    check(re.search(r'مسابقة اختبار الفصل 48</strong></a>\s*</td>\s*<td class="small">[^<]+</td>\s*<td>6</td>\s*<td>0</td>', b) is not None,
          'قائمة المسابقات: 6 ملفات مقبولة و0 نتيجة')
    def res(action, rows=None):
        t, _ = adm.token(RES)
        d = {'csrfmiddlewaretoken': t, 'action': action}
        for i, vals in (rows or {}).items():
            for k, v in vals.items():
                d[f'r[{i}][{k}]'] = v
        return adm.post_follow(RES, d)
    b, al = res('publish')
    check(any('سجّل نتائج المترشحين أولاً ثم انشرها.' in a for a in al) and q('SELECT results_published_at p FROM competitions WHERE id = ?', [CID])[0]['p'] is None,
          'نشر بلا نتيجة ← مرفوض')
    base = {A1: ('passed', '15,5'), A2: ('passed', '15.50'), A3: ('passed', '12'), A4: ('passed', '15,50'), A5: ('absent', ''), B1: ('passed', '11')}
    rows = {i: {'result': r, 'score': sc, 'rank': '', 'note': ''} for i, (r, sc) in base.items()}
    b, al = res('save', rows)
    check(any('تم حفظ نتائج 6 مترشح.' in a for a in al), 'حفظ 6 نتائج')
    check('15,50' in b and '12,00' in b and 'recruit-res-passed' in b and 'recruit-res-absent' in b, 'العلامات بالفاصلة وخانتين، وصنف لون كل نتيجة')
    nres = int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE competition_id = ? AND action = 'result'", [CID])[0]['n'])
    rows2 = {i: {'result': r, 'score': sc, 'rank': '', 'note': ''} for i, (r, sc) in base.items()}
    b, al = res('save', rows2)
    nres2 = int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE competition_id = ? AND action = 'result'", [CID])[0]['n'])
    check(any('لا توجد تعديلات.' in a for a in al) and nres2 == nres, 'حفظ بلا تعديل ← «لا توجد تعديلات.» ولا سطر جديد')
    x('UPDATE competition_applications SET result_rank = 2 WHERE id = ?', [A5])
    b, al = res('autorank')
    rk = {i: q('SELECT result_rank r FROM competition_applications WHERE id = ?', [i])[0]['r'] for i in T['ids']}
    check([rk[A1], rk[A2], rk[A4], rk[A3], rk[B1]] == ['1', '1', '1', '4', '1'] and rk[A5] is None,
          f'ترتيب تلقائي: تعادل 1، 1، 1 ثم 4، والرتبة الأخرى 1، والغائب بلا ترتيب ← {rk}')
    rows3 = dict(rows); rows3[A2] = {'result': 'passed', 'score': '14', 'rank': '1', 'note': ''}
    res('save', rows3)
    res('autorank')
    rk = {i: q('SELECT result_rank r FROM competition_applications WHERE id = ?', [i])[0]['r'] for i in (A1, A2, A3, A4)}
    check([rk[A1], rk[A4], rk[A2], rk[A3]] == ['1', '1', '3', '4'], f'بعد تغيير علامة: 1، 1، 3، 4 ← {rk}')
    rows4 = {A1: {'result': 'passed', 'score': '21', 'rank': '1', 'note': ''}}
    b, al = res('save', rows4)
    code1 = T['codes'][0]
    check(any(code1 in a and 'زروقي أمين' in a and 'العلامة يجب أن تكون عدداً بين 0 و20' in a for a in al)
          and q('SELECT score s FROM competition_applications WHERE id = ?', [A1])[0]['s'] == '15.50', 'علامة 21 ← خطأ باسم المترشح ورقمه، ولا تغيير')
    b, al = res('publish')
    check(q('SELECT results_published_at p FROM competitions WHERE id = ?', [CID])[0]['p'] is not None and 'is-published' in b, 'نشر النتائج')
    b, al = res('unpublish')
    check(q('SELECT results_published_at p FROM competitions WHERE id = ?', [CID])[0]['p'] is None, 'إلغاء النشر')

    print('7) التصدير')
    def sheet(path):
        s, h, raw = adm.req('GET', path)
        if raw[:2] != b'PK':
            return None, ''
        xml = zipfile.ZipFile(io.BytesIO(raw)).read('xl/worksheets/sheet1.xml').decode()
        first = re.search(r'<row r="1"[^>]*>(.*?)</row>', xml, re.S).group(1)
        return len(re.findall(r'<c ', first)), xml
    ncol, xml = sheet(f'/admin/recruitment/export/?competition={CID}')
    check(ncol == 19 and '109900101000111001' not in xml and '0550000100' not in xml, 'المختصرة: 19 عموداً بلا رقم تعريف ولا هاتف')
    ncol, xml = sheet(f'/admin/recruitment/export/?mode=full&competition={CID}')
    check(ncol == 29, f'الكاملة: 29 عموداً ({ncol})')
    check('<t xml:space="preserve">009900101000111005</t>' in xml and '<v>009900101000111005</v>' not in xml and '<t xml:space="preserve">0550000104</t>' in xml, 'رقم التعريف والهاتف نصوص (بصفرها الأول)')
    ncol, xml = sheet(f'/admin/recruitment/export/?competition={CID}&status=accepted')
    check(xml.count('<row ') == 1 + 6, 'التصدير يتبع التصفية (المقبولون فقط)')
    ex_rows = [r['details'] for r in q("SELECT details FROM recruitment_audit WHERE competition_id = ? AND action = 'export' ORDER BY id", [CID])]
    check(len(ex_rows) == 3 and ex_rows[1].startswith('قائمة كاملة — 6 طلب — المسابقة #') and 'الحالة: ملف مقبول' in ex_rows[2], 'سطر export لكل تصدير بنوعه وعدده وتصفيته')

    print('8) سجل الاطلاع والصلاحيات')
    _s, _h, b = adm.text('GET', '/admin/recruitment/audit/?action=export')
    labels = re.findall(r'<td><span class="label label-(\w+)">([^<]+)</span></td>', b)
    check(labels and all(l == ('danger', 'تصدير قائمة') for l in labels), 'تصفية السجل بالحدث (التصدير بالأحمر)')
    st = Client()
    st.login('hicham41', 'StaffDemo2026')
    s, _h, b = st.text('GET', '/admin/')
    check('طلبات الترشح' not in b and 'مسابقات التوظيف' not in b, 'موظف بلا صلاحية: لا مجموعة «مسابقات التوظيف»')
    s, _h, b = st.text('GET', '/admin/recruitment/applications/')
    log = open(sec_log, encoding='utf-8').read()[sec_size:]
    check(s == 403 and 'صلاحية مسابقات التوظيف' in b and '"event":"recruitment_forbidden"' in log and '"user":"hicham41"' in log and '/admin/recruitment/applications/' in log,
          f'الرابط مباشرة ← 403 وسطر recruitment_forbidden باسمه والرابط ({s})')
    x("UPDATE users SET can_recruit = 1 WHERE username = 'hicham41'")
    s, _h, b = st.text('GET', '/admin/')
    check('طلبات الترشح' in b and 'نتائج المسابقات' in b and 'سجل الاطلاع على الملفات' not in b, 'بالصلاحية: الطلبات والنتائج، بلا سجل الاطلاع')
    s, _h, b = st.text('GET', LIST)
    check(s == 200 and 'admin:recruit_purge' not in b and 'حذف معطيات المترشحين' not in b, 'بالصلاحية: لا رابط حذف المعطيات')
    s, _h, b = st.text('GET', '/admin/recruitment/audit/')
    check(s == 403 and 'سجل الاطلاع للمدير العام فقط.' in b, f'بالصلاحية: سجل الاطلاع ← 403 ({s})')
    s, _h, b = st.text('GET', f'/admin/recruitment/purge/{CID}/')
    check(s == 403, f'بالصلاحية: صفحة الحذف ← 403 ({s})')
    st.text('GET', f'/admin/recruitment/applications/{A1}/')
    check(int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE application_id = ? AND action = 'view' AND username = 'hicham41'", [A1])[0]['n']) == 1,
          'فتح الموظف ملفاً ← سطر باسمه')
    x("UPDATE users SET can_recruit = 0 WHERE username = 'hicham41'")

    print('9) الحذف بعد المسابقة')
    _s, _h, b = adm.text('GET', f'/admin/recruitment/purge/{CID}/')
    check('أجل الترشح لم ينته بعد' in b and 'name="confirm"' not in b, 'قبل الأجل: تنبيه بلا نموذج')
    x('UPDATE competitions SET closes_at = ? WHERE id = ?', [yesterday, CID])
    t, b = adm.token(f'/admin/recruitment/purge/{CID}/')
    check('name="confirm"' in b and 'حذف 6 طلب ووثائقه نهائياً' in b, 'بعد الأجل: التحذير ونموذج التأكيد')
    b, al = adm.post_follow(f'/admin/recruitment/purge/{CID}/', {'csrfmiddlewaretoken': t, 'confirm': 'نعم'})
    check(any('اكتب كلمة «حذف» في خانة التأكيد.' in a for a in al), 'كلمة غير «حذف» ← مرفوض')
    d = os.path.join(SITE, 'storage', 'private', 'recruitment', str(CID))
    check(os.path.isdir(d) and len(os.listdir(d)) == 6, 'قبل الحذف: 6 ملفات مشفّرة في مجلد المسابقة')
    t, _ = adm.token(f'/admin/recruitment/purge/{CID}/')
    b, al = adm.post_follow(f'/admin/recruitment/purge/{CID}/', {'csrfmiddlewaretoken': t, 'confirm': 'DELETE'})
    left = int(q('SELECT COUNT(*) n FROM competition_applications WHERE competition_id = ?', [CID])[0]['n'])
    check(left == 0 and not os.path.isdir(d) and any('تم حذف 6 طلب ترشح ووثائقه نهائياً' in a for a in al), 'DELETE بحروف كبيرة ← حُذفت الطلبات والمجلد')
    _s, _h, b = adm.text('GET', f'/admin/recruitment/audit/?application={A1}')
    check(f'#{A1} (محذوف)' in b, 'السجل باقٍ: الطلب برقمه وكلمة «محذوف»')
    check(int(q("SELECT COUNT(*) n FROM recruitment_audit WHERE competition_id = ? AND action = 'purge'", [CID])[0]['n']) == 1, 'سطر purge')
    t, _ = adm.token(f'/admin/competitions/{CID}/delete/')
    s, h, _b = adm.text('POST', f'/admin/competitions/{CID}/delete/', {'csrfmiddlewaretoken': t})
    check(int(q('SELECT COUNT(*) n FROM competitions WHERE id = ?', [CID])[0]['n']) == 0, 'المسابقة نفسها صارت قابلة للحذف')
finally:
    if T:
        php(f"DB::delete('competitions', 'id = ?', [{T['cid']}]);")
        x('DELETE FROM recruitment_audit WHERE competition_id = ?', [T['cid']])
        d = os.path.join(SITE, 'storage', 'private', 'recruitment', str(T['cid']))
        if os.path.isdir(d):
            for f in os.listdir(d):
                os.remove(os.path.join(d, f))
            os.rmdir(d)
    x('DELETE FROM recruitment_audit WHERE id > ?', [mark])
    x(f"UPDATE users SET can_recruit = {old_perm} WHERE username = 'hicham41'")
    clear_rl()
print(f'\n{"✓ كل الفحوص نجحت" if fails == 0 else f"✗ {fails} فحص فشل"}')
sys.exit(1 if fails else 0)
