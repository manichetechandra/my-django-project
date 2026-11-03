<?php
/**
 * المسابقات (مسابقات التوظيف): الإعلانات + الترشح الإلكتروني + متابعة الطلب.
 *
 * حماية المعطيات الشخصية (انظر SECURITY.md — قسم «المسابقات»):
 *  - المعطيات الحساسة تُشفّر قبل الحفظ (Crypto)، ورقم التعريف لا يُحفظ واضحاً (بصمة HMAC لمنع التكرار).
 *  - الوثائق: PDF فقط بعد فحص محتواها (PdfGuard)، ثم تُشفّر وتُحفظ خارج المجلد العام بأسماء عشوائية.
 *  - رقم متابعة عشوائي + رمز سري يُعرض مرة واحدة (لا تُحفظ إلا بصمته) — لا يمكن تخمين طلبات الآخرين.
 *  - حدود للمحاولات، حقل فخ للبرامج الآلية، توقيع لوقت فتح الاستمارة، CSRF، وأجل الترشح يُفرض في الخادم.
 *  - صفحات المعطيات الشخصية لا تُخزَّن في ذاكرة المتصفح ولا تفهرسها محركات البحث.
 */

// ====================================================================== الصفحات العامة

function competitions_list(): void
{
    $counts = [];
    foreach (['open', 'upcoming', 'closed', 'all'] as $t) {
        [$w, $p] = Competition::phaseWhere($t);
        $counts[$t] = Competition::count($w, $p);
    }
    $tab = query('tab');
    if (!isset($counts[$tab])) {
        $tab = $counts['open'] ? 'open' : 'all';
    }
    [$w, $p] = Competition::phaseWhere($tab);
    $order = ['open' => 'closes_at ASC, id DESC', 'upcoming' => 'opens_at ASC, id DESC'][$tab] ?? 'closes_at DESC, id DESC';
    render('competitions/list', [
        'competitions' => Competition::paginate($w, $p, $order, 10),
        'tab'          => $tab,
        'counts'       => $counts,
        'page_title'   => __('مسابقات التوظيف'),
    ]);
}

/** مسابقة ظاهرة للزوار (المسودة يراها فقط الموظفون المخوّلون للمعاينة) */
function competition_or_404(string $slug): Competition
{
    $c = Competition::firstWhere('slug = ?', [$slug]);
    if (!$c || ($c['status'] === 'draft' && !can_recruit())) {
        abort(404);
    }
    return $c;
}

function competitions_detail(string $slug): void
{
    $c = competition_or_404($slug);
    if ($c['status'] !== 'draft') {
        DB::query('UPDATE competitions SET views_count = views_count + 1 WHERE id = ?', [(int) $c['id']]);
    }
    [$w, $p] = Competition::phaseWhere('open');
    render('competitions/detail', [
        'c'            => $c,
        'posts'        => $c['posts'],
        'requirements' => $c['requirements'],
        'files'        => $c->grouped_files(),
        'others'       => Competition::where($w . ' AND id <> ?', array_merge($p, [(int) $c['id']]), 'closes_at', 3),
        'limits'       => recruit_limits(),
    ]);
}

/** ملف منشور (الإعلان، الاستمارة، القوائم...) مع عدّاد التحميلات */
function competitions_file($id): void
{
    $f = CompetitionFile::find((int) $id);
    $c = $f ? $f['competition'] : null;
    if (!$f || !$c || ($c['status'] === 'draft' && !can_recruit())) {
        abort(404);
    }
    $path = $f->full_path();
    if (!$path) {
        abort(404, __('الملف غير موجود'));
    }
    DB::query('UPDATE competition_files SET downloads_count = downloads_count + 1 WHERE id = ?', [(int) $f['id']]);
    $ext = $f->extension();
    Upload::send($path, trim((string) $f['title']) . '.' . $ext, $ext === 'pdf');
}

// ====================================================================== الترشح الإلكتروني

/** حدود الرفع الفعلية: الأصغر بين config.php وإعدادات PHP (upload_max_filesize و post_max_size) */
function recruit_limits(): array
{
    $file = (int) ((float) config('recruitment.max_file_mb', 3) * 1048576);
    $total = (int) ((float) config('recruitment.max_total_mb', 15) * 1048576);
    if (($ini = ini_bytes((string) ini_get('upload_max_filesize'))) > 0) {
        $file = min($file, $ini);
    }
    if (($post = ini_bytes((string) ini_get('post_max_size'))) > 0) {
        $total = min($total, $post - 256 * 1024); // هامش لحقول الاستمارة
    }
    $total = max($file, $total);
    $mb = fn(int $b) => rtrim(rtrim(number_format($b / 1048576, 1), '0'), '.');
    return ['file' => $file, 'total' => $total, 'file_mb' => $mb($file), 'total_mb' => $mb($total),
            'max_files' => max(1, (int) ini_get('max_file_uploads') ?: 20)];
}

/** الترشح يتطلب اتصالاً مشفّراً (HTTPS) إلا على جهاز التطوير */
function recruit_https_ok(): bool
{
    return !config('recruitment.require_https', true) || is_https() || is_local_request();
}

/** سبب منع الترشح الآن (أو null إن كان مسموحاً) */
function recruit_block_reason(Competition $c, array $posts): ?string
{
    if ($c['status'] === 'cancelled') {
        return __('هذه المسابقة ملغاة.');
    }
    if (empty($c['online_apply'])) {
        return __('الترشح لهذه المسابقة يتم بإيداع الملف الورقي فقط (انظر تفاصيل الإيداع في صفحة المسابقة).');
    }
    $phase = $c->phase();
    if ($phase === 'upcoming') {
        return __('لم يُفتح الترشح بعد. يبدأ الإيداع يوم {date}.', ['date' => format_date($c['opens_at'], 'j F Y')]);
    }
    if ($phase === 'closed' || $c['status'] !== 'published') {
        return __('انتهى أجل الترشح لهذه المسابقة.');
    }
    if (!$posts) {
        return __('لم تُحدَّد رتب هذه المسابقة بعد. أعد المحاولة لاحقاً.');
    }
    if (!recruit_https_ok()) {
        return __('الترشح الإلكتروني متاح فقط عبر اتصال آمن (https://) لحماية معطياتك الشخصية.');
    }
    if (!Crypto::available()) {
        return __('الترشح الإلكتروني غير متاح حالياً بسبب إعدادات الخادم. يرجى المحاولة لاحقاً.');
    }
    return null;
}

/** رمز موقَّع لوقت فتح الاستمارة: يكشف البرامج الآلية التي ترسلها في أقل من 3 ثوانٍ */
function recruit_form_token(int $competitionId): string
{
    $t = time();
    return $t . '.' . substr(Crypto::sign('apply|' . $competitionId . '|' . $t . '|' . session_id()), 0, 32);
}

function recruit_bot_error(int $competitionId): ?string
{
    $hp = $_POST['website_url'] ?? '';
    $hp = is_string($hp) ? $hp : 'array';
    [$t, $sig] = array_pad(explode('.', post_text('_ft', 100), 2), 2, '');
    $age = time() - (int) $t;
    $valid = ctype_digit($t) && hash_equals(substr(Crypto::sign('apply|' . $competitionId . '|' . $t . '|' . session_id()), 0, 32), $sig);
    if ($hp !== '' || !$valid || $age < 3 || $age > 4 * 3600) {
        security_log('recruitment_bot_suspect', ['honeypot' => $hp !== '', 'token' => $valid, 'age' => $age]);
        return __('انتهت صلاحية الاستمارة أو أُرسلت بطريقة غير عادية. راجع معلوماتك ثم أعد الإرسال.');
    }
    return null;
}

/** تنظيف المدخلات النصية: مسافات زائدة، أحرف تحكم، الأرقام الهندية ← العربية (0-9) */
function recruit_clean_input(array $post): array
{
    $digits = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];
    $out = [];
    foreach ($post as $k => $v) {
        if (!is_string($v) || strlen($v) > 2000) {
            continue;
        }
        $v = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
        $v = trim((string) preg_replace('/\s+/u', ' ', $v));
        if (in_array($k, ['nin', 'phone', 'graduation_year', 'experience_years'], true)) {
            $v = str_replace(' ', '', strtr($v, $digits));
        }
        $out[$k] = $v;
    }
    return $out;
}

const RECRUIT_AR_NAME = '/^[\x{0621}-\x{064A}\x{064B}-\x{0652}\x{0670}-\x{06D3}]+(?:[ \'\-][\x{0621}-\x{064A}\x{064B}-\x{0652}\x{0670}-\x{06D3}]+)*$/u';
const RECRUIT_LAT_NAME = '/^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:[ \'\-][A-Za-zÀ-ÖØ-öø-ÿ]+)*$/u';
const RECRUIT_TEXT = '/^[^<>{}\x00-\x1F\x7F]+$/u';

/** استمارة الترشح: حقول ثابتة + خانة PDF لكل وثيقة مطلوبة */
function recruit_apply_form(Competition $c, array $posts, array $requirements, array $limits, ?array $data = null, ?array $files = null): Form
{
    $cls = ['class' => 'form-control'];
    $postChoices = [];
    foreach ($posts as $p) {
        $postChoices[(string) $p['id']] = (string) $p['title'];
    }
    $text = fn(string $label, int $max, array $attrs = [], bool $required = true) => [
        'type' => 'text', 'label' => $label, 'max_length' => $max, 'min_length' => 2, 'required' => $required,
        'pattern' => RECRUIT_TEXT, 'pattern_message' => 'يحتوي النص على رموز غير مسموح بها.', 'attrs' => $cls + $attrs,
    ];
    $maxBirth = date('Y-m-d', strtotime('-18 years'));
    $fields = [
        'post_id' => ['type' => 'select', 'label' => 'الرتبة المترشح لها', 'choices' => $postChoices, 'empty_label' => '— اختر الرتبة —', 'attrs' => $cls],
        'last_name' => ['type' => 'text', 'label' => 'اللقب (بالعربية)', 'max_length' => 60, 'pattern' => RECRUIT_AR_NAME,
            'pattern_message' => 'اكتب الاسم واللقب بحروف عربية كما في بطاقة التعريف.', 'attrs' => $cls + ['dir' => 'rtl', 'lang' => 'ar', 'autocomplete' => 'family-name']],
        'first_name' => ['type' => 'text', 'label' => 'الاسم (بالعربية)', 'max_length' => 60, 'pattern' => RECRUIT_AR_NAME,
            'pattern_message' => 'اكتب الاسم واللقب بحروف عربية كما في بطاقة التعريف.', 'attrs' => $cls + ['dir' => 'rtl', 'lang' => 'ar', 'autocomplete' => 'given-name']],
        'last_name_latin' => ['type' => 'text', 'label' => 'اللقب (بالحروف اللاتينية)', 'max_length' => 60, 'pattern' => RECRUIT_LAT_NAME,
            'pattern_message' => 'اكتب الاسم واللقب بالحروف اللاتينية كما في بطاقة التعريف.', 'attrs' => $cls + ['dir' => 'ltr', 'lang' => 'fr', 'autocomplete' => 'off']],
        'first_name_latin' => ['type' => 'text', 'label' => 'الاسم (بالحروف اللاتينية)', 'max_length' => 60, 'pattern' => RECRUIT_LAT_NAME,
            'pattern_message' => 'اكتب الاسم واللقب بالحروف اللاتينية كما في بطاقة التعريف.', 'attrs' => $cls + ['dir' => 'ltr', 'lang' => 'fr', 'autocomplete' => 'off']],
        'gender' => ['type' => 'select', 'label' => 'الجنس', 'choices' => CompetitionApplication::choiceMap('gender'), 'empty_label' => '—', 'attrs' => $cls],
        'birth_date' => ['type' => 'date', 'label' => 'تاريخ الميلاد', 'attrs' => $cls + ['max' => $maxBirth, 'min' => '1950-01-01'],
            'validators' => [fn($v) => $v > $maxBirth ? 'يجب أن يكون عمر المترشح 18 سنة على الأقل.' : ($v < '1950-01-01' ? 'أدخل تاريخاً صالحاً.' : null)]],
        'birth_place' => $text('مكان الميلاد', 100),
        'nin' => ['type' => 'text', 'label' => 'رقم التعريف الوطني (NIN)', 'pattern' => '/^\d{18}$/',
            'pattern_message' => 'رقم التعريف الوطني يتكون من 18 رقماً (تجده على بطاقة التعريف البيومترية).',
            'attrs' => $cls + ['inputmode' => 'numeric', 'maxlength' => 18, 'dir' => 'ltr', 'autocomplete' => 'off', 'spellcheck' => 'false']],
        'phone' => ['type' => 'text', 'label' => 'رقم الهاتف', 'pattern' => StudentProfile::PHONE_PATTERN, 'pattern_message' => StudentProfile::PHONE_MESSAGE,
            'attrs' => $cls + ['inputmode' => 'tel', 'maxlength' => 10, 'dir' => 'ltr', 'autocomplete' => 'tel', 'placeholder' => '06XXXXXXXX']],
        'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'max_length' => 120, 'attrs' => $cls + ['dir' => 'ltr', 'autocomplete' => 'email']],
        'address' => $text('العنوان الشخصي', 255, ['autocomplete' => 'street-address']),
        'wilaya' => $text('الولاية', 60),
        'national_service' => ['type' => 'select', 'label' => 'الوضعية تجاه الخدمة الوطنية', 'choices' => CompetitionApplication::choiceMap('national_service'), 'empty_label' => '—', 'attrs' => $cls],
        'diploma' => $text('الشهادة المتحصل عليها', 150, ['placeholder' => 'مثال: ليسانس في العلوم القانونية']),
        'specialty' => $text('التخصص', 150),
        'graduation_year' => ['type' => 'number', 'label' => 'سنة التخرج', 'min' => 1970, 'max' => (int) date('Y'), 'attrs' => $cls + ['dir' => 'ltr', 'min' => 1970, 'max' => (int) date('Y')]],
        'experience_years' => ['type' => 'number', 'label' => 'سنوات الخبرة المهنية', 'required' => false, 'min' => 0, 'max' => 45, 'initial' => '0', 'attrs' => $cls + ['dir' => 'ltr', 'min' => 0, 'max' => 45]],
    ];
    foreach ($requirements as $r) {
        $fields['doc_' . $r['id']] = [
            'type' => 'file', 'label' => (string) $r['label'], 'required' => (bool) $r['is_required'],
            'attrs' => ['class' => 'form-control cmp-file', 'accept' => '.pdf,application/pdf', 'data-max' => $limits['file']],
            'validators' => [fn($file) => recruit_check_pdf($file, $limits)],
        ];
    }
    $fields['declaration'] = ['type' => 'checkbox', 'label' => 'أصرّح بشرفي بصحة المعلومات والوثائق المقدّمة، وأعلم أن كل تصريح كاذب يؤدي إلى إلغاء ترشحي.',
        'validators' => [fn($v) => $v ? null : 'يجب التصريح بصحة المعلومات.']];
    $fields['consent'] = ['type' => 'checkbox', 'label' => 'أوافق على معالجة معطياتي الشخصية لغرض هذه المسابقة فقط، وفق القانون 18-07 المتعلق بحماية الأشخاص الطبيعيين في مجال معالجة المعطيات ذات الطابع الشخصي.',
        'validators' => [fn($v) => $v ? null : 'يجب الموافقة على معالجة المعطيات الشخصية لإتمام الترشح.']];

    $form = new Form($fields, $data, $files);
    $form->clean = function (Form $f) use ($limits, $requirements) {
        if (($f->cleaned['gender'] ?? '') === 'M' && ($f->cleaned['national_service'] ?? '') === 'na') {
            $f->add_error('national_service', 'حدد وضعيتك تجاه الخدمة الوطنية.');
        }
        if (($f->cleaned['gender'] ?? '') === 'F') {
            $f->cleaned['national_service'] = 'na';
        }
        $total = 0;
        foreach ($requirements as $r) {
            $total += (int) ($f->cleaned['doc_' . $r['id']]['size'] ?? 0);
        }
        if ($total > $limits['total']) {
            $f->add_error(null, __('مجموع أحجام الوثائق أكبر من {mb} ميغابايت. قلّل حجم الملفات (مسح ضوئي بدقة 150 نقطة يكفي).', ['mb' => $limits['total_mb']]));
        }
    };
    return $form;
}

/** فحص وثيقة مرفوعة: ملف رُفع فعلاً عبر النموذج، امتداده pdf، ومحتواه PDF سليم */
function recruit_check_pdf($file, array $limits): ?string
{
    if (!is_array($file) || !is_string($file['tmp_name'] ?? null) || !is_string($file['name'] ?? null)) {
        return __('ملف غير صالح.');
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
        return __('ملف غير صالح.');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        return __('يُقبل ملف PDF فقط.');
    }
    $error = PdfGuard::check($file['tmp_name'], $limits['file']);
    if ($error !== null && (str_starts_with(PdfGuard::$reason, 'active') || PdfGuard::$reason === 'hidden_object')) {
        security_log('recruitment_pdf_rejected', ['reason' => PdfGuard::$reason, 'size' => (int) ($file['size'] ?? 0)]);
    }
    return $error;
}

/** اسم الملف الأصلي للعرض فقط (لا يُستعمل أبداً كاسم على القرص) */
function recruit_safe_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = (string) preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', '_', $name);
    $base = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 100);
    return ($base !== '' ? $base : 'document') . '.pdf';
}

class RecruitDuplicate extends RuntimeException
{
}

/**
 * حفظ الطلب: البيانات الحساسة مشفّرة، والوثائق تُشفّر في storage/private/recruitment/<المسابقة>/
 * كل شيء داخل معاملة واحدة، وتُحذف الملفات المكتوبة إن فشلت أي خطوة.
 * يرجع [الطلب, الرمز السري الواضح] — الرمز لا يُحفظ إلا بصمته.
 */
function recruit_store_application(Competition $c, array $d, array $posts, array $requirements): array
{
    $ninHash = Crypto::blindIndex($d['nin']);
    if (CompetitionApplication::exists('competition_id = ? AND nin_hash = ?', [(int) $c['id'], $ninHash])) {
        throw new RecruitDuplicate();
    }
    $post = null;
    foreach ($posts as $p) {
        if ((int) $p['id'] === (int) $d['post_id']) {
            $post = $p;
        }
    }
    $dir = CandidateFile::dir((int) $c['id']);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('تعذر إنشاء مجلد وثائق المسابقة في storage/private/recruitment');
    }
    $root = dirname($dir);
    if (!is_file($root . '/.htaccess')) {
        @file_put_contents($root . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    $tracking = CompetitionApplication::newTrackingCode();
    $secret = CompetitionApplication::newSecret();
    $personal = [
        'nin' => $d['nin'], 'birth_date' => $d['birth_date'], 'birth_place' => $d['birth_place'],
        'address' => $d['address'], 'wilaya' => $d['wilaya'], 'phone' => $d['phone'],
        'email' => mb_strtolower((string) $d['email']), 'national_service' => $d['national_service'],
    ];
    $written = [];
    try {
        $app = DB::transaction(function () use ($c, $d, $post, $requirements, $tracking, $secret, $personal, $ninHash, $dir, &$written) {
            $app = CompetitionApplication::create([
                'competition_id'   => (int) $c['id'],
                'post_id'          => (int) $post['id'],
                'post_title'       => (string) $post->toArray()['title'],
                'tracking_code'    => $tracking,
                'secret_hash'      => CompetitionApplication::hashSecret($secret),
                'nin_hash'         => $ninHash,
                'last_name'        => $d['last_name'],
                'first_name'       => $d['first_name'],
                'last_name_latin'  => mb_strtoupper($d['last_name_latin']),
                'first_name_latin' => mb_convert_case(mb_strtolower($d['first_name_latin']), MB_CASE_TITLE),
                'gender'           => $d['gender'],
                'diploma'          => $d['diploma'],
                'specialty'        => $d['specialty'],
                'graduation_year'  => (int) $d['graduation_year'],
                'experience_years' => (int) ($d['experience_years'] ?? 0),
                'personal_data'    => Crypto::encryptArray($personal, 'application:' . $tracking),
                'status'           => 'pending',
                'submitted_at'     => now(),
                'updated_at'       => now(),
            ]);
            foreach ($requirements as $r) {
                $file = $d['doc_' . $r['id']] ?? null;
                if (!$file) {
                    continue;
                }
                $plain = (string) file_get_contents($file['tmp_name']);
                $stored = CandidateFile::newStoredName();
                Crypto::encryptToFile($plain, $dir . '/' . $stored, CandidateFile::cryptoContext((int) $app['id'], $stored));
                $written[] = $dir . '/' . $stored;
                CandidateFile::create([
                    'application_id' => (int) $app['id'],
                    'requirement_id' => (int) $r['id'],
                    'label'          => mb_substr((string) $r->toArray()['label'], 0, 200),
                    'original_name'  => recruit_safe_filename($file['name']),
                    'stored_name'    => $stored,
                    'size'           => strlen($plain),
                    'sha256'         => hash('sha256', $plain),
                ]);
                @unlink($file['tmp_name']); // النسخة غير المشفّرة المؤقتة
            }
            return $app;
        });
    } catch (Throwable $e) {
        foreach ($written as $w) {
            @unlink($w);
        }
        if ($e instanceof PDOException && str_contains($e->getMessage(), 'uq_competition_applications_nin')) {
            throw new RecruitDuplicate();
        }
        throw $e;
    }
    RecruitmentAudit::log('submit', $app, null, 'الرتبة: ' . $app['post_title'] . ' — الوثائق: ' . count($written), true);
    return [$app, $secret];
}

function competitions_apply(string $slug): void
{
    never_cache();
    header('X-Robots-Tag: noindex, nofollow');
    $c = competition_or_404($slug);
    $posts = $c['posts'];
    $requirements = $c['requirements'];
    $limits = recruit_limits();
    $blocked = recruit_block_reason($c, $posts);
    $form = null;
    $status = 200;

    if ($blocked === null) {
        $form = recruit_apply_form($c, $posts, $requirements, $limits, is_post() ? recruit_clean_input($_POST) : null, is_post() ? $_FILES : null);
        if (is_post()) {
            $ip = client_ip();
            $attemptsKey = 'apply-ip:' . $ip;
            $successKey = 'apply-ok:' . $ip;
            $wait = max(
                RateLimit::retryAfter($attemptsKey, (int) config('recruitment.apply_attempts_max', 30), 3600),
                RateLimit::retryAfter($successKey, (int) config('recruitment.apply_success_max', 20), 86400)
            );
            if ($wait > 0) {
                security_log('recruitment_apply_throttled', ['competition' => (int) $c['id']]);
                header('Retry-After: ' . $wait);
                $form->add_error(null, too_many_attempts_message($wait));
                $status = 429;
            } else {
                RateLimit::hit($attemptsKey, 3600);
                if ($bot = recruit_bot_error((int) $c['id'])) {
                    $form->add_error(null, $bot);
                } elseif ($form->is_valid()) {
                    try {
                        [$app, $secret] = recruit_store_application($c, $form->cleaned, $posts, $requirements);
                        RateLimit::hit($successKey, 86400);
                        // الرمز السري يبقى في جلسة هذا المتصفح 15 دقيقة فقط (أجهزة مقاهي الإنترنت المشتركة)
                        $_SESSION['recruit_receipt'] = ['id' => (int) $app['id'], 'secret' => $secret, 'until' => time() + 900];
                        redirect(url('competitions:receipt'));
                    } catch (RecruitDuplicate $e) {
                        $form->add_error('nin', 'يوجد طلب ترشح مسجّل بهذا الرقم في هذه المسابقة. لا يمكن الترشح أكثر من مرة؛ تابع طلبك من صفحة «متابعة طلب».');
                    }
                }
            }
        }
    }
    render('competitions/apply', [
        'c'            => $c,
        'posts'        => $posts,
        'requirements' => $requirements,
        'form'         => $form,
        'blocked'      => $blocked,
        'limits'       => $limits,
        'form_token'   => $blocked === null ? recruit_form_token((int) $c['id']) : '',
        'page_title'   => __('الترشح الإلكتروني'),
    ], $status);
}

/** وصل الترشح: يُعرض الرمز السري في نفس المتصفح فقط، 15 دقيقة كحد أقصى أو حتى يضغط المترشح «احتفظت بالرمزين» */
function competitions_receipt(): void
{
    never_cache();
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    $r = $_SESSION['recruit_receipt'] ?? null;
    if (is_post() && isset($_POST['done'])) {
        unset($_SESSION['recruit_receipt']);
        redirect(url('competitions:track'));
    }
    $app = is_array($r) && ($r['until'] ?? 0) > time() ? CompetitionApplication::find((int) $r['id']) : null;
    if (!$app) {
        unset($_SESSION['recruit_receipt']);
        flash('info', __('انتهت صلاحية عرض الوصل. يمكنك متابعة طلبك برقم المتابعة والرمز السري.'));
        redirect(url('competitions:track'));
    }
    render('competitions/receipt', [
        'app'        => $app,
        'c'          => $app['competition'],
        'secret'     => (string) $r['secret'],
        'files'      => $app['files'],
        'page_title' => __('وصل الترشح'),
    ]);
}

/** متابعة الطلب برقم المتابعة + الرمز السري (POST فقط، بلا وضع الرمز في الرابط) */
function competitions_track(): void
{
    never_cache();
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    $app = null;
    $error = null;
    $status = 200;
    $code = '';
    if (is_post()) {
        $code = post_text('tracking_code', 40);
        $secret = post_text('secret', 40);
        $norm = substr(CompetitionApplication::normalizeCode($code), 0, 20);
        $window = 900;
        $ipKey = 'track-ip:' . client_ip();
        $codeKey = 'track-code:' . $norm;
        $wait = max(
            RateLimit::retryAfter($ipKey, (int) config('recruitment.track_max_per_ip', 10), $window),
            $norm !== '' ? RateLimit::retryAfter($codeKey, (int) config('recruitment.track_max_per_code', 5), $window) : 0
        );
        if ($wait > 0) {
            header('Retry-After: ' . $wait);
            $error = too_many_attempts_message($wait);
            $status = 429;
            security_log('recruitment_track_throttled', ['code' => $norm]);
        } else {
            $found = CompetitionApplication::findByTracking($code);
            if (CompetitionApplication::verifySecret($found, $secret)) {
                $app = $found;
                RecruitmentAudit::log('track', $app, null, '', true);
            } else {
                RateLimit::hit($ipKey, $window);
                if ($norm !== '') {
                    RateLimit::hit($codeKey, $window);
                }
                $error = __('رقم المتابعة أو الرمز السري غير صحيح.');
                security_log('recruitment_track_failed', ['code' => $norm]);
            }
        }
    }
    render('competitions/track', [
        'app'        => $app,
        'c'          => $app ? $app['competition'] : null,
        'files'      => $app ? $app['files'] : [],
        'error'      => $error,
        'code'       => $app ? '' : $code,
        'page_title' => __('متابعة طلب ترشح'),
    ], $status);
}
