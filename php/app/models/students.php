<?php
/**
 * المستخدمون وفضاء الطالب (django.contrib.auth + students/models.py)
 */

class User extends Model
{
    protected static string $table = 'users';
    protected static array $booleans = ['is_staff', 'is_superuser', 'is_active'];
    protected static array $defaults = ['email' => '', 'first_name' => '', 'last_name' => '', 'is_staff' => 0, 'is_superuser' => 0, 'is_active' => 1];
    protected static array $createdFields = ['date_joined'];

    public function get_full_name(): string
    {
        return trim(($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? ''));
    }

    /** الاسم المعروض: الاسم الكامل أو اسم المستخدم */
    public function display_name(): string
    {
        return $this->get_full_name() !== '' ? $this->get_full_name() : (string) ($this->attributes['username'] ?? '');
    }

    public function set_password(string $plain): void
    {
        $this->attributes['password'] = Auth::hashPassword($plain);
    }

    public function check_password(string $plain): bool
    {
        return Auth::verifyPassword($plain, (string) ($this->attributes['password'] ?? ''));
    }

    public function student_profile(): ?StudentProfile
    {
        return StudentProfile::firstWhere('user_id = ?', [(int) $this->attributes['id']]);
    }
}

class StudentProfile extends Model
{
    protected static string $table = 'student_profiles';
    protected static array $choices = [
        'gender' => ['M' => 'ذكر', 'F' => 'أنثى'],
        'current_year' => ['1' => 'السنة الأولى', '2' => 'السنة الثانية', '3' => 'السنة الثالثة', 'M1' => 'ماستر 1', 'M2' => 'ماستر 2', 'D' => 'دكتوراه'],
    ];
    protected static array $booleans = ['is_verified', 'is_active'];
    protected static array $belongsTo = ['user' => [User::class, 'user_id']];
    protected static array $hasMany = [
        'applications' => [StudentApplication::class, 'student_id', 'ORDER BY submitted_at DESC'],
        'student_notifications' => [StudentNotification::class, 'student_id', 'ORDER BY created_at DESC'],
    ];
    protected static array $defaults = ['university' => 'جامعة سطيف', 'is_verified' => 0, 'is_active' => 1];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    public const PHONE_PATTERN = '/^(05|06|07)\d{8}$/';
    public const PHONE_MESSAGE = 'رقم الهاتف يجب أن يبدأ بـ 05 أو 06 أو 07 ويحتوي على 10 أرقام';

    public function get_unread_notifications_count(): int
    {
        return StudentNotification::count('student_id = ? AND is_read = 0', [(int) $this->attributes['id']]);
    }

    public function get_recent_notifications(int $limit = 5): array
    {
        return StudentNotification::where('student_id = ?', [(int) $this->attributes['id']], 'created_at DESC, id DESC', $limit);
    }

    /** إنشاء إشعار للطالب */
    /**
     * إشعار للطالب يُحفظ بالعربية والفرنسية والإنجليزية معاً، فيقرؤه بلغة تصفحه:
     *   $student->notify('مرحباً {name}', '...', '/students/dashboard/', 'info', ['name' => $name]);
     * النصوص عربية (مفاتيح ملفات app/lang)، وقيمة المتغير يمكن أن تكون مصفوفة لكل لغة:
     *   ['type' => ['ar' => 'منحة', 'fr' => 'Bourse', 'en' => 'Grant']]
     */
    public function notify(string $title, string $message, ?string $link = null, string $type = 'info', array $params = []): StudentNotification
    {
        $data = [
            'student_id' => (int) $this->attributes['id'],
            'link' => $link,
            'notification_type' => $type,
        ];
        foreach (array_keys(languages()) as $code) {
            $p = array_map(fn($v) => is_array($v) ? ($v[$code] ?? $v[default_lang()] ?? '') : $v, $params);
            $suffix = $code === default_lang() ? '' : '_' . $code;
            $data['title' . $suffix] = __($title, $p, $code);
            $data['message' . $suffix] = __($message, $p, $code);
        }
        return StudentNotification::create($data);
    }
}

class ApplicationType extends Model
{
    protected static string $table = 'application_types';
    protected static array $translatable = ['name', 'description', 'required_documents'];
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['icon' => 'fa-file-text', 'color' => '#3498db', 'required_documents' => '', 'is_active' => 1, 'order' => 0];

    public static function active(): array
    {
        return static::where('is_active = 1', [], '`order`, name');
    }
}

class StudentApplication extends Model
{
    protected static string $table = 'student_applications';
    protected static array $choices = [
        'status' => ['pending' => 'قيد الانتظار', 'under_review' => 'قيد المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض', 'cancelled' => 'ملغي'],
    ];
    protected static array $belongsTo = [
        'student' => [StudentProfile::class, 'student_id'],
        'application_type' => [ApplicationType::class, 'application_type_id'],
        'reviewed_by' => [User::class, 'reviewed_by_id'],
    ];
    protected static array $hasMany = ['documents' => [ApplicationDocument::class, 'application_id', 'ORDER BY uploaded_at DESC']];
    protected static array $defaults = ['status' => 'pending'];
    protected static array $createdFields = ['submitted_at'];
    protected static array $updatedFields = ['updated_at'];

    /** رقم الطلب: السنة + رقم تسلسلي من 5 أرقام (مثل 202500001) — بدون تكرار */
    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['application_number'])) {
            $year = date('Y');
            $last = DB::value('SELECT MAX(CAST(SUBSTRING(application_number, 5) AS UNSIGNED)) FROM student_applications WHERE application_number LIKE ?', [$year . '%']);
            $this->attributes['application_number'] = $year . str_pad((string) ((int) $last + 1), 5, '0', STR_PAD_LEFT);
        }
    }

    public function save(): void
    {
        // إعادة المحاولة في حال تزامن طلبين بنفس الرقم
        for ($attempt = 0; ; $attempt++) {
            try {
                parent::save();
                return;
            } catch (PDOException $e) {
                if ($attempt < 3 && $e->getCode() === '23000' && strpos($e->getMessage(), 'application_number') !== false) {
                    $this->attributes['application_number'] = null;
                    continue;
                }
                throw $e;
            }
        }
    }

    public function get_status_color(): string
    {
        $c = ['pending' => '#f39c12', 'under_review' => '#3498db', 'approved' => '#2ecc71', 'rejected' => '#e74c3c', 'cancelled' => '#95a5a6'];
        return $c[$this->attributes['status'] ?? ''] ?? '#95a5a6';
    }

    public function get_status_icon(): string
    {
        $i = ['pending' => 'fa-clock-o', 'under_review' => 'fa-search', 'approved' => 'fa-check-circle', 'rejected' => 'fa-times-circle', 'cancelled' => 'fa-ban'];
        return $i[$this->attributes['status'] ?? ''] ?? 'fa-question';
    }

    /**
     * تغيير حالة الطلب + إشعار الطالب (إشعار واحد فقط لكل تغيير).
     * يجمع منطق signals.py و views.py في Django.
     */
    public function change_status(string $newStatus, ?User $reviewer = null, ?string $notes = null): bool
    {
        $old = $this->attributes['status'] ?? '';
        $data = ['status' => $newStatus];
        if ($reviewer && in_array($newStatus, ['approved', 'rejected'], true)) {
            $data['reviewed_by_id'] = (int) $reviewer['id'];
            $data['reviewed_at'] = now();
        }
        if ($notes !== null && trim($notes) !== '') {
            $data['admin_notes'] = trim($notes);
        }
        $data['updated_at'] = now();
        $this->update($data);
        if (isset($data['reviewed_by_id'])) {
            $this->setRelation('reviewed_by', $reviewer);
        }
        if ($old === $newStatus) {
            return false;
        }
        $this->notify_status_change();
        return true;
    }

    /** إشعار الطالب بالحالة الحالية للطلب (نفس نصوص students/signals.py) */
    public function notify_status_change(): void
    {
        $type = $this->application_type;
        // اسم نوع الطلب بكل لغة (من حقول الترجمة في جدول application_types)
        $typeNames = [];
        foreach (array_keys(languages()) as $code) {
            $typeNames[$code] = $type ? (string) $type->tr('name', $code) : '';
        }
        $params = ['type' => $typeNames, 'number' => $this->attributes['application_number']];
        $messages = [
            'approved'     => ['تم اعتماد طلبك ✅', 'تم اعتماد طلب {type} برقم {number}', 'success'],
            'rejected'     => ['تم رفض طلبك ❌', 'تم رفض طلب {type} برقم {number}. يرجى مراجعة الملاحظات.', 'error'],
            'under_review' => ['طلبك قيد المراجعة 📝', 'طلب {type} برقم {number} قيد المراجعة من قبل الإدارة.', 'info'],
            'pending'      => ['تم تغيير حالة طلبك ⏳', 'تم تغيير حالة طلب {type} برقم {number} إلى "قيد الانتظار".', 'warning'],
            'cancelled'    => ['تم إلغاء طلبك 🚫', 'تم إلغاء طلب {type} برقم {number}.', 'warning'],
        ];
        [$title, $msg, $ntype] = $messages[$this->attributes['status'] ?? ''] ?? ['تحديث على طلبك 📢', 'تم تحديث حالة طلب {type} برقم {number}.', 'info'];
        $student = $this->student;
        if ($student) {
            $student->notify($title, $msg, '/students/applications/' . $this->attributes['id'] . '/', $ntype, $params);
        }
    }
}

class ApplicationDocument extends Model
{
    protected static string $table = 'application_documents';
    protected static array $belongsTo = ['application' => [StudentApplication::class, 'application_id']];
    protected static array $createdFields = ['uploaded_at'];

    /** رابط تحميل محمي (المالك أو الموظفون فقط) */
    public function get_download_url(): string
    {
        return url('students:document_download', $this->attributes['id']);
    }

    public function file_name(): string
    {
        return basename((string) ($this->attributes['document_file'] ?? ''));
    }

    public function full_path(): ?string
    {
        $rel = ltrim((string) ($this->attributes['document_file'] ?? ''), '/');
        foreach ([STORAGE_PATH . '/private/' . $rel, UPLOAD_PATH . '/' . $rel] as $p) {
            if ($rel !== '' && is_file($p)) {
                return $p;
            }
        }
        return null;
    }
}

class StudentNotification extends Model
{
    protected static string $table = 'student_notifications';
    protected static array $translatable = ['title', 'message'];
    protected static array $choices = [
        'notification_type' => ['info' => 'معلومة', 'success' => 'نجاح', 'warning' => 'تحذير', 'error' => 'خطأ'],
    ];
    protected static array $booleans = ['is_read'];
    protected static array $belongsTo = ['student' => [StudentProfile::class, 'student_id']];
    protected static array $defaults = ['notification_type' => 'info', 'is_read' => 0];
    protected static array $createdFields = ['created_at'];

    public function mark_as_read(): void
    {
        if (!($this->attributes['is_read'] ?? false)) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public function get_icon(): string
    {
        $i = ['info' => 'fa-info-circle', 'success' => 'fa-check-circle', 'warning' => 'fa-exclamation-triangle', 'error' => 'fa-times-circle'];
        return $i[$this->attributes['notification_type'] ?? ''] ?? 'fa-bell';
    }

    public function get_color(): string
    {
        $c = ['info' => '#3498db', 'success' => '#2ecc71', 'warning' => '#f39c12', 'error' => '#e74c3c'];
        return $c[$this->attributes['notification_type'] ?? ''] ?? '#3498db';
    }

    /** الرابط مع بادئة الموقع */
    public function url(): string
    {
        return link_url($this->attributes['link'] ?? '');
    }
}
