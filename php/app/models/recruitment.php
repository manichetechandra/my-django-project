<?php
/**
 * مسابقات التوظيف والترشح الإلكتروني.
 *
 *  Competition            ← الإعلان: الرتب، الشروط، الآجال، الملفات المنشورة
 *  CompetitionPost        ← رتبة/منصب داخل المسابقة (عدد المناصب، الشهادة المطلوبة)
 *  CompetitionRequirement ← وثيقة مطلوبة في ملف الترشح (= خانة رفع PDF في الاستمارة)
 *  CompetitionFile        ← ملف منشور للجميع (الإعلان، الاستمارة، قائمة المقبولين، النتائج)
 *  CompetitionApplication ← طلب ترشح (المعطيات الحساسة مشفّرة في personal_data)
 *  CandidateFile          ← وثيقة مترشح (مشفّرة في storage/private/recruitment/)
 *  RecruitmentAudit       ← سجل الاطلاع على ملفات المترشحين
 */

class Competition extends Model
{
    protected static string $table = 'competitions';
    protected static array $translatable = ['title', 'summary', 'description', 'conditions', 'deposit_info'];
    protected static array $choices = [
        'recruitment_mode' => [
            'qualification' => 'توظيف على أساس الشهادة', 'exam' => 'مسابقة على أساس الاختبار',
            'professional' => 'امتحان مهني', 'contract' => 'توظيف بالتعاقد',
        ],
        'status' => ['draft' => 'مسودة (غير منشورة)', 'published' => 'منشورة', 'cancelled' => 'ملغاة'],
    ];
    /** المرحلة المعروضة للزوار */
    public const PHASES = ['open' => 'الترشح مفتوح', 'upcoming' => 'قريباً', 'closed' => 'انتهى أجل الترشح', 'cancelled' => 'ملغاة'];
    protected static array $booleans = ['online_apply'];
    protected static array $hasMany = [
        'posts'        => [CompetitionPost::class, 'competition_id', 'ORDER BY `order`, id'],
        'requirements' => [CompetitionRequirement::class, 'competition_id', 'ORDER BY `order`, id'],
        'files'        => [CompetitionFile::class, 'competition_id', 'ORDER BY `order`, id'],
    ];
    protected static array $defaults = ['reference' => '', 'summary' => '', 'description' => '', 'conditions' => '',
        'recruitment_mode' => 'qualification', 'online_apply' => 1, 'status' => 'published', 'views_count' => 0];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    /** شرط SQL للمسابقات الظاهرة للزوار (المسودات مخفية) */
    public const VISIBLE = "status IN ('published', 'cancelled')";

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('competitions', (string) $this->attributes['title'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('competitions:detail', $this->attributes['slug']);
    }

    /** اليوم بتوقيت الجزائر (config.php ← app.timezone) */
    public static function today(): string
    {
        return date('Y-m-d');
    }

    /** شروط SQL لكل مرحلة (مع المعاملات) */
    public static function phaseWhere(string $phase): array
    {
        $t = self::today();
        switch ($phase) {
            case 'open':
                return ["status = 'published' AND opens_at <= ? AND closes_at >= ?", [$t, $t]];
            case 'upcoming':
                return ["status = 'published' AND opens_at > ?", [$t]];
            case 'closed':
                return ["(status = 'cancelled' OR (status = 'published' AND closes_at < ?))", [$t]];
            default:
                return [self::VISIBLE, []];
        }
    }

    /** المسابقات المفتوحة الآن (للقائمة العلوية والصفحة الرئيسية) — لا تُسقط الموقع إن لم تُطبَّق الترقية بعد */
    public static function openNow(int $limit = 3): array
    {
        try {
            [$w, $p] = self::phaseWhere('open');
            return self::where($w, $p, 'closes_at, id DESC', $limit);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function openCount(): int
    {
        static $n = null;
        if ($n === null) {
            try {
                [$w, $p] = self::phaseWhere('open');
                $n = self::count($w, $p);
            } catch (Throwable $e) {
                $n = 0; // جداول المسابقات غير موجودة بعد (database/migrations/2026_11_recruitment.sql)
            }
        }
        return $n;
    }

    public function phase(): string
    {
        if ($this->attributes['status'] === 'cancelled') {
            return 'cancelled';
        }
        $t = self::today();
        if ($t < $this->attributes['opens_at']) {
            return 'upcoming';
        }
        return $t > $this->attributes['closes_at'] ? 'closed' : 'open';
    }

    public function get_phase_display(): string
    {
        return __(self::PHASES[$this->phase()]);
    }

    /** هل يقبل الترشح الإلكتروني الآن؟ (يُتحقق منه في الخادم عند فتح الاستمارة وعند الإرسال) */
    public function accepts_applications(): bool
    {
        return $this->attributes['status'] === 'published' && !empty($this->attributes['online_apply']) && $this->phase() === 'open';
    }

    /** آخر لحظة للترشح: نهاية يوم closes_at بتوقيت الجزائر */
    public function deadline_ts(): int
    {
        return (int) strtotime($this->attributes['closes_at'] . ' 23:59:59');
    }

    public function opens_ts(): int
    {
        return (int) strtotime($this->attributes['opens_at'] . ' 00:00:00');
    }

    /** الوقت المتبقي بلغة الصفحة: «5 أيام و3 ساعات» (فارغ إن انتهى الأجل) */
    public function remaining_text(): string
    {
        $left = $this->deadline_ts() - time();
        return $left > 0 ? time_left($left) : '';
    }

    /** رقم الإعلان وحده (07/2026) حتى لو كتبه الموظف «إعلان رقم 07/2026»، ليُعرض بكل اللغات */
    public function reference_number(): string
    {
        // إعلان|مقرر|قرار ثم رقم|N°|No (بترميز \x{...} حتى لا تُعدّ نصاً للترجمة)
        return trim((string) preg_replace('/^\s*(?:\x{0625}\x{0639}\x{0644}\x{0627}\x{0646}|\x{0645}\x{0642}\x{0631}\x{0631}|\x{0642}\x{0631}\x{0627}\x{0631})?\s*(?:\x{0631}\x{0642}\x{0645}|N°|No\.?|n°)?\s*:?\s*/u', '', (string) $this->attributes['reference']));
    }

    public function total_positions(): int
    {
        return (int) DB::value('SELECT COALESCE(SUM(positions), 0) FROM competition_posts WHERE competition_id = ?', [(int) $this->attributes['id']]);
    }

    /** شروط المشاركة كقائمة (سطر = شرط) */
    public function conditions_list(): array
    {
        return array_values(array_filter(array_map(fn($l) => trim(ltrim(trim($l), '-•*·')), preg_split('/\R/u', (string) $this['conditions']))));
    }

    /** الملفات المنشورة مجمّعة: ['documents' => [...], 'results' => [...]] */
    public function grouped_files(): array
    {
        $out = ['documents' => [], 'results' => []];
        foreach ($this['files'] as $f) {
            $out[in_array($f['kind'], ['admitted', 'results'], true) ? 'results' : 'documents'][] = $f;
        }
        return $out;
    }

    public function has_results(): bool
    {
        return (bool) DB::value("SELECT 1 FROM competition_files WHERE competition_id = ? AND kind IN ('admitted', 'results') LIMIT 1", [(int) $this->attributes['id']]);
    }

    public function applications_count(?string $status = null): int
    {
        return CompetitionApplication::count('competition_id = ?' . ($status ? ' AND status = ?' : ''), $status ? [(int) $this->attributes['id'], $status] : [(int) $this->attributes['id']]);
    }
}

class CompetitionPost extends Model
{
    protected static string $table = 'competition_posts';
    protected static array $translatable = ['title', 'requirements', 'location'];
    protected static array $belongsTo = ['competition' => [Competition::class, 'competition_id']];
    protected static array $defaults = ['positions' => 1, 'requirements' => '', 'location' => '', 'order' => 0];
}

class CompetitionRequirement extends Model
{
    protected static string $table = 'competition_requirements';
    protected static array $translatable = ['label'];
    protected static array $booleans = ['is_required'];
    protected static array $defaults = ['is_required' => 1, 'order' => 0];
}

class CompetitionFile extends Model
{
    protected static string $table = 'competition_files';
    protected static array $translatable = ['title'];
    protected static array $choices = [
        'kind' => ['announcement' => 'الإعلان الرسمي', 'form' => 'استمارة الترشح', 'admitted' => 'قائمة المقبولين',
                   'results' => 'النتائج النهائية', 'other' => 'وثيقة أخرى'],
    ];
    protected static array $belongsTo = ['competition' => [Competition::class, 'competition_id']];
    protected static array $defaults = ['kind' => 'announcement', 'file_size' => 0, 'downloads_count' => 0, 'order' => 0];
    protected static array $createdFields = ['uploaded_at'];

    protected function beforeSave(bool $isNew): void
    {
        $path = $this->full_path();
        if ($path) {
            $this->attributes['file_size'] = filesize($path);
        }
    }

    /** المسار الكامل داخل uploads/ (مع منع الخروج من المجلد) أو null */
    public function full_path(): ?string
    {
        $root = realpath(UPLOAD_PATH);
        $full = realpath(UPLOAD_PATH . '/' . ltrim((string) ($this->attributes['file'] ?? ''), '/'));
        return $root && $full && str_starts_with($full, $root . DIRECTORY_SEPARATOR) && is_file($full) ? $full : null;
    }

    public function get_download_url(): string
    {
        return url('competitions:file', $this->attributes['id']);
    }

    public function extension(): string
    {
        return strtolower(pathinfo((string) $this->attributes['file'], PATHINFO_EXTENSION));
    }

    public function icon(): string
    {
        return ['pdf' => 'fa-file-pdf-o', 'doc' => 'fa-file-word-o', 'docx' => 'fa-file-word-o', 'xls' => 'fa-file-excel-o',
                'xlsx' => 'fa-file-excel-o', 'jpg' => 'fa-file-image-o', 'jpeg' => 'fa-file-image-o', 'png' => 'fa-file-image-o'][$this->extension()] ?? 'fa-file-o';
    }
}

class CompetitionApplication extends Model
{
    protected static string $table = 'competition_applications';
    protected static array $choices = [
        'status' => ['pending' => 'قيد الانتظار', 'under_review' => 'قيد الدراسة', 'incomplete' => 'ملف ناقص',
                     'accepted' => 'مقبول', 'rejected' => 'مرفوض'],
        'gender' => ['M' => 'ذكر', 'F' => 'أنثى'],
        'national_service' => ['done' => 'مؤداة', 'exempt' => 'معفى', 'deferred' => 'مؤجل', 'na' => 'غير معني'],
    ];
    public const STATUS_COLORS = ['pending' => 'warning', 'under_review' => 'info', 'incomplete' => 'danger', 'accepted' => 'success', 'rejected' => 'default'];
    protected static array $belongsTo = [
        'competition' => [Competition::class, 'competition_id'],
        'post'        => [CompetitionPost::class, 'post_id'],
        'reviewed_by' => [User::class, 'reviewed_by_id'],
    ];
    protected static array $hasMany = ['files' => [CandidateFile::class, 'application_id', 'ORDER BY id']];

    /** حروف رقم المتابعة والرمز السري: بلا 0/O و 1/I/L حتى لا يخطئ المترشح في كتابتها */
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function randomCode(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }
        return $out;
    }

    /** رقم متابعة جديد غير مستعمل: C26-7KQ9M4XA (8 حروف عشوائية ≈ 850 مليار احتمال) */
    public static function newTrackingCode(): string
    {
        do {
            $code = 'C' . date('y') . '-' . self::randomCode(8);
        } while (self::exists('tracking_code = ?', [$code]));
        return $code;
    }

    /** الرمز السري: 10 حروف (≈ 800 ألف مليار احتمال) يُعرض مرة واحدة ولا تُحفظ إلا بصمته */
    public static function newSecret(): string
    {
        $s = self::randomCode(10);
        return substr($s, 0, 5) . '-' . substr($s, 5);
    }

    /** توحيد ما يكتبه المترشح: أحرف كبيرة بلا مسافات ولا شرطات */
    public static function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    public static function findByTracking(string $code): ?self
    {
        $n = self::normalizeCode($code);
        if (!preg_match('/^C\d{2}[' . self::ALPHABET . ']{8}$/', $n)) {
            return null;
        }
        return self::firstWhere('tracking_code = ?', [substr($n, 0, 3) . '-' . substr($n, 3)]);
    }

    /** التحقق من الرمز السري (مع وقت ثابت تقريباً حتى لو لم يوجد الطلب، كي لا يُعرف وجود رقم المتابعة من زمن الاستجابة) */
    public static function verifySecret(?self $app, string $secret): bool
    {
        // بصمة وهمية لنص عشوائي لا يعرفه أحد: نفس كلفة التحقق الحقيقي
        $dummy = '$2y$10$9jUTH/ouVHqwvKHYr1UWo.PrXn4LEaGNR/EuGF80Q5tk69kIt4n2C';
        $ok = password_verify(self::normalizeCode($secret), $app ? (string) $app['secret_hash'] : $dummy);
        return $app !== null && $ok;
    }

    public static function hashSecret(string $secret): string
    {
        return password_hash(self::normalizeCode($secret), PASSWORD_DEFAULT);
    }

    /** سياق التشفير: يربط البيانات المشفّرة بهذا الطلب بالذات */
    public function cryptoContext(): string
    {
        return 'application:' . $this->attributes['tracking_code'];
    }

    /** المعطيات الشخصية بعد فك التشفير (لا تُستدعى إلا في صفحات الموظفين المخوّلين، وتُسجَّل في سجل الاطلاع) */
    public function personal(): array
    {
        if (!array_key_exists('__personal', $this->loaded)) {
            $this->loaded['__personal'] = Crypto::decryptArray((string) $this->attributes['personal_data'], $this->cryptoContext());
        }
        return $this->loaded['__personal'];
    }

    public function full_name(): string
    {
        return trim($this->attributes['last_name'] . ' ' . $this->attributes['first_name']);
    }

    public function full_name_latin(): string
    {
        return trim($this->attributes['last_name_latin'] . ' ' . $this->attributes['first_name_latin']);
    }

    /** اسم الرتبة: الحالي إن وُجد، وإلا الاسم المحفوظ يوم الترشح */
    public function post_label(): string
    {
        $post = $this['post'];
        return $post ? (string) $post['title'] : (string) $this->attributes['post_title'];
    }

    public function status_color(): string
    {
        return self::STATUS_COLORS[$this->attributes['status']] ?? 'default';
    }

    /** نص اختيار من القائمة (للقيم داخل البيانات المشفّرة مثل الخدمة الوطنية) */
    public static function choiceLabel(string $field, ?string $value): string
    {
        return isset(self::$choices[$field][(string) $value]) ? __(self::$choices[$field][(string) $value]) : (string) $value;
    }
}

class CandidateFile extends Model
{
    protected static string $table = 'competition_application_files';
    protected static array $belongsTo = ['application' => [CompetitionApplication::class, 'application_id']];
    protected static array $createdFields = ['uploaded_at'];

    /** مجلد وثائق مسابقة (خارج المجلد العام، ومحمي بـ .htaccess) */
    public static function dir(int $competitionId): string
    {
        return STORAGE_PATH . '/private/recruitment/' . $competitionId;
    }

    public static function newStoredName(): string
    {
        return bin2hex(random_bytes(16)) . '.enc';
    }

    /** المسار الكامل — يُبنى من رقم المسابقة واسم عشوائي مُتحقق منه (لا مسار من قاعدة البيانات) */
    public function full_path(int $competitionId): ?string
    {
        $name = (string) $this->attributes['stored_name'];
        if (!preg_match('/^[a-f0-9]{32}\.enc$/', $name)) {
            return null;
        }
        $path = self::dir($competitionId) . '/' . $name;
        return is_file($path) ? $path : null;
    }

    public static function cryptoContext(int $applicationId, string $storedName): string
    {
        return 'file:' . $applicationId . ':' . $storedName;
    }
}

class RecruitmentAudit extends Model
{
    protected static string $table = 'recruitment_audit';
    protected static array $createdFields = ['created_at'];

    /** تسجيل حدث قام به موظف، أو المترشح نفسه ($byCandidate: بلا حساب حتى لو كان المتصفح مسجلاً بحساب طالب) */
    public static function log(string $action, ?CompetitionApplication $app = null, ?int $competitionId = null, string $details = '', bool $byCandidate = false): void
    {
        $u = $byCandidate ? null : current_user();
        DB::insert('recruitment_audit', [
            'user_id'        => $u ? (int) $u['id'] : null,
            'username'       => $u ? (string) $u['username'] : '',
            'action'         => $action,
            'competition_id' => $app ? (int) $app['competition_id'] : $competitionId,
            'application_id' => $app ? (int) $app['id'] : null,
            'details'        => mb_substr($details, 0, 500),
            'ip_address'     => substr(client_ip(), 0, 45),
            'created_at'     => now(),
        ]);
    }
}
