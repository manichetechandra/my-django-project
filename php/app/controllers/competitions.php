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

/** مجلد وثائق مسابقة (يُنشأ عند أول وثيقة، بصلاحيات 0700، ومحمي بـ .htaccess) */
function recruit_files_dir(int $competitionId): string
{
    $dir = CandidateFile::dir($competitionId);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('تعذر إنشاء مجلد وثائق المسابقة في storage/private/recruitment');
    }
    $root = dirname($dir);
    if (!is_file($root . '/.htaccess')) {
        @file_put_contents($root . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

/**
 * تشفير الوثائق وحفظها: $items = [[الوثيقة المطلوبة, الملف المرفوع], ...]
 * يُستدعى داخل معاملة؛ $written يجمع الملفات المكتوبة لتُحذف إن فشلت المعاملة.
 * $later: وثائق أضافها المترشح بعد الإرسال (إكمال ملف ناقص)
 */
function recruit_store_files(CompetitionApplication $app, array $items, string $dir, array &$written, bool $later = false): int
{
    foreach ($items as [$r, $file]) {
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
            'added_later'    => $later ? 1 : 0,
        ]);
        @unlink($file['tmp_name']); // النسخة غير المشفّرة المؤقتة
    }
    return count($items);
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
    $dir = recruit_files_dir((int) $c['id']);
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
            $items = [];
            foreach ($requirements as $r) {
                if (!empty($d['doc_' . $r['id']])) {
                    $items[] = [$r, $d['doc_' . $r['id']]];
                }
            }
            recruit_store_files($app, $items, $dir, $written);
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

/** مدة «فضاء المترشح» بعد إدخال الرمزين (ثابتة لا تتجدد: أجهزة مقاهي الإنترنت المشتركة) */
const RECRUIT_GRANT_TTL = 1200;

/**
 * الطلب المفتوح في هذه الجلسة بعد التحقق من رقم المتابعة والرمز السري، أو null.
 * لا رقم طلب في أي رابط: الإذن محفوظ في الجلسة فقط ومربوط برقم المتابعة.
 * $grace: مهلة إضافية لإرسال وثائق بدأ رفعها قبل انتهاء الجلسة (اتصال بطيء).
 */
function recruit_grant(int $grace = 0): ?CompetitionApplication
{
    $g = $_SESSION['recruit_track'] ?? null;
    if (!is_array($g) || (int) ($g['until'] ?? 0) + $grace < time()) {
        unset($_SESSION['recruit_track']);
        return null;
    }
    $app = CompetitionApplication::find((int) ($g['id'] ?? 0));
    if (!$app || !hash_equals((string) $app['tracking_code'], (string) ($g['code'] ?? ''))) {
        unset($_SESSION['recruit_track']);
        return null;
    }
    return $app;
}

/** ترويسات كل صفحات فضاء المترشح: لا تخزين، لا فهرسة، لا Referer */
function recruit_private_headers(): void
{
    never_cache();
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

/** إعادة إلى فضاء المترشح بعد انتهاء الجلسة */
function recruit_grant_or_login(int $grace = 0): CompetitionApplication
{
    $app = recruit_grant($grace);
    if (!$app) {
        flash('info', __('انتهت مدة الجلسة. أدخل رقم المتابعة والرمز السري من جديد.'));
        redirect(url('competitions:track'));
    }
    return $app;
}

/**
 * فضاء المترشح: إدخال رقم المتابعة + الرمز السري (POST فقط، بلا وضع الرمز في الرابط)
 * ثم صفحة الطلب: الحالة، رسالة الإدارة، إكمال الملف الناقص، الاستدعاء، النتيجة.
 */
function competitions_track(): void
{
    recruit_private_headers();
    $error = null;
    $status = 200;
    $code = '';
    if (is_post()) {
        if (isset($_POST['logout'])) {
            unset($_SESSION['recruit_track']);
            flash('info', __('تم إغلاق صفحة طلبك.'));
            redirect(url('competitions:track'));
        }
        // محاولة دخول جديدة تُغلق أي طلب مفتوح في هذا المتصفح أولاً (جهاز مشترك)
        unset($_SESSION['recruit_track']);
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
                // معرّف جلسة جديد عند فتح الطلب (منع تثبيت الجلسة Session Fixation)
                session_regenerate_id(true);
                $_SESSION['recruit_track'] = ['id' => (int) $found['id'], 'code' => (string) $found['tracking_code'], 'until' => time() + RECRUIT_GRANT_TTL];
                RecruitmentAudit::log('track', $found, null, '', true);
                // PRG: إعادة التحميل لا تعيد إرسال الرمز، وزر «رجوع» لا يعرض نموذجاً مملوءاً
                redirect(url('competitions:track'));
            }
            RateLimit::hit($ipKey, $window);
            if ($norm !== '') {
                RateLimit::hit($codeKey, $window);
            }
            $error = __('رقم المتابعة أو الرمز السري غير صحيح.');
            security_log('recruitment_track_failed', ['code' => $norm]);
        }
    }
    $app = $error === null ? recruit_grant() : null;
    $c = $app ? $app['competition'] : null;
    render('competitions/track', [
        'app'          => $app,
        'c'            => $c,
        'files'        => $app ? $app['files'] : [],
        'requirements' => $app && $app->can_complete() ? $c['requirements'] : [],
        'limits'       => recruit_limits(),
        'exam'         => $app && $app->is_convoked() ? $app['exam'] : null,
        'show_result'  => $app && $app->result_visible(),
        'expires_in'   => $app ? max(0, (int) $_SESSION['recruit_track']['until'] - time()) : 0,
        'error'        => $error,
        'code'         => $code,
        'page_title'   => __('متابعة طلب ترشح'),
    ], $status);
}

/** المترشح يرفع الوثائق الناقصة بنفسه (ملف ناقص + قبل الأجل الذي حدده الموظف) */
function competitions_track_complete(): void
{
    recruit_private_headers();
    if (!is_post()) {
        redirect(url('competitions:track')); // بعد رسالة «حجم البيانات أكبر من الحد» في index.php
    }
    $app = recruit_grant_or_login(600);
    if (!$app->can_complete()) {
        flash('error', __('لا يمكن إضافة وثائق إلى هذا الملف الآن.'));
        redirect(url('competitions:track'));
    }
    $ipKey = 'complete-ip:' . client_ip();
    $appKey = 'complete-app:' . (int) $app['id'];
    $wait = max(RateLimit::retryAfter($ipKey, 20, 3600), RateLimit::retryAfter($appKey, 10, 86400));
    if ($wait > 0) {
        security_log('recruitment_complete_throttled', ['application' => (int) $app['id']]);
        flash('error', too_many_attempts_message($wait));
        redirect(url('competitions:track'));
    }
    RateLimit::hit($ipKey, 3600);
    RateLimit::hit($appKey, 86400);

    $c = $app['competition'];
    $limits = recruit_limits();
    $items = [];
    $errors = [];
    $total = 0;
    foreach ($c['requirements'] as $r) {
        $file = $_FILES['doc_' . $r['id']] ?? null;
        if (!is_array($file) || is_array($file['error'] ?? null) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $label = (string) $r['label'];
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $label . ': ' . ((int) $file['error'] === UPLOAD_ERR_INI_SIZE || (int) $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? __('حجم الملف أكبر من {mb} ميغابايت.', ['mb' => $limits['file_mb']]) : __('تعذر رفع الملف، أعد المحاولة.'));
            continue;
        }
        if ($err = recruit_check_pdf($file, $limits)) {
            $errors[] = $label . ': ' . $err;
            continue;
        }
        $total += (int) $file['size'];
        $items[] = [$r, $file];
    }
    if (!$items && !$errors) {
        $errors[] = __('اختر وثيقة واحدة على الأقل (ملف PDF).');
    }
    if ($total > $limits['total']) {
        $errors[] = __('مجموع أحجام الوثائق أكبر من {mb} ميغابايت. قلّل حجم الملفات (مسح ضوئي بدقة 150 نقطة يكفي).', ['mb' => $limits['total_mb']]);
    }
    // سقف لعدد الوثائق في ملف واحد (منع ملء القرص بإعادة الرفع)
    $existing = CandidateFile::count('application_id = ?', [(int) $app['id']]);
    if (!$errors && $existing + count($items) > max(20, 3 * count($c['requirements']))) {
        $errors[] = __('بلغ ملفك الحد الأقصى لعدد الوثائق. اتصل بمصلحة المستخدمين.');
    }
    if ($errors) {
        foreach ($errors as $e) {
            flash('error', $e);
        }
        redirect(url('competitions:track'));
    }

    $dir = recruit_files_dir((int) $c['id']);
    $written = [];
    try {
        DB::transaction(function () use ($app, $items, $dir, &$written) {
            recruit_store_files($app, $items, $dir, $written, true);
            // يعود الملف إلى «قيد الانتظار» ليُدرس من جديد (يظهر للموظفين في عدد الطلبات الجديدة)
            $app->update(['status' => 'pending', 'completed_at' => now(), 'updated_at' => now()]);
        });
    } catch (Throwable $e) {
        foreach ($written as $w) {
            @unlink($w);
        }
        throw $e;
    }
    RecruitmentAudit::log('complete', $app, null, count($items) . ' — ' . implode('، ', array_map(fn($it) => (string) $it[0]->toArray()['label'], $items)), true);
    flash('success', __('تم استلام وثائقك. عاد ملفك إلى «قيد الانتظار» وستدرسه اللجنة من جديد.'));
    redirect(url('competitions:track'));
}

/** وثيقة للطباعة (الاستدعاء أو إشعار النتيجة) للمترشح صاحب الجلسة فقط */
function recruit_print_context(CompetitionApplication $app): array
{
    $personal = [];
    try {
        $p = $app->personal();
        // تاريخ ومكان الميلاد فقط: يكفيان للتعرف على المترشح يوم الاختبار (لا رقم تعريف ولا عنوان على الورقة)
        $personal = ['birth_date' => (string) ($p['birth_date'] ?? ''), 'birth_place' => (string) ($p['birth_place'] ?? '')];
    } catch (Throwable $e) {
        security_log('recruitment_decrypt_failed', ['application' => (int) $app['id']]);
    }
    return [
        'app'      => $app,
        'c'        => $app['competition'],
        'personal' => $personal,
        'barcode'  => barcode_data_uri((string) $app['tracking_code'], 2, 46),
    ];
}

function competitions_track_convocation(): void
{
    recruit_private_headers();
    $app = recruit_grant_or_login();
    if (!$app->is_convoked()) {
        abort(404);
    }
    render('competitions/print_convocation', recruit_print_context($app) + ['exam' => $app['exam']]);
}

function competitions_track_result(): void
{
    recruit_private_headers();
    $app = recruit_grant_or_login();
    if (!$app->result_visible()) {
        abort(404);
    }
    render('competitions/print_result', recruit_print_context($app));
}
