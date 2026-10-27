<?php
/**
 * تعريف النماذج (بديل contact/forms.py و students/forms.py)
 */

const FC = ['class' => 'form-control'];

/** التحقق من قوة كلمة المرور (مثل AUTH_PASSWORD_VALIDATORS في Django) */
function password_errors(string $password, string $username = ''): array
{
    $errors = [];
    if (mb_strlen($password) < 8) {
        $errors[] = 'كلمة المرور هذه قصيرة جدا. يجب أن تتكون من 8 أحرف على الأقل.';
    }
    if (preg_match('/^\d+$/', $password)) {
        $errors[] = 'كلمة المرور هذه تتكون بالكامل من أرقام.';
    }
    $common = ['password', '12345678', '123456789', '1234567890', 'qwerty123', 'azerty123', 'password1', 'iloveyou',
               '11111111', '00000000', 'abc12345', 'qwertyuiop', 'azertyuiop', '12341234', 'motdepasse', 'admin123', 'welcome1'];
    if (in_array(strtolower($password), $common, true)) {
        $errors[] = 'كلمة المرور هذه شائعة جدا.';
    }
    if ($username !== '' && mb_strlen($username) >= 3 && stripos($password, $username) !== false) {
        $errors[] = 'كلمة المرور مشابهة جداً لـ اسم المستخدم.';
    }
    return $errors;
}

// ====================================================================== contact/forms.py

function contact_form(?array $data = null): Form
{
    return new Form([
        'full_name' => ['type' => 'text', 'label' => 'الاسم الكامل', 'max_length' => 100,
            'attrs' => FC + ['placeholder' => 'الاسم الكامل'],
            'validators' => [fn($v) => mb_strlen($v) < 3 ? 'الاسم يجب أن يكون 3 أحرف على الأقل' : null]],
        'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'max_length' => 254,
            'attrs' => FC + ['placeholder' => 'example@email.com']],
        'phone' => ['type' => 'text', 'label' => 'رقم الهاتف', 'required' => false, 'max_length' => 20,
            'attrs' => FC + ['placeholder' => '0555123456 (اختياري)'],
            'validators' => [function ($v, Form $form) {
                $p = str_replace([' ', '-'], '', $v);
                if (!preg_match('/^\d+$/', $p)) {
                    return 'رقم الهاتف يجب أن يحتوي على أرقام فقط';
                }
                if (strlen($p) < 10) {
                    return 'رقم الهاتف غير صحيح';
                }
                return null;
            }]],
        'subject' => ['type' => 'select', 'label' => 'الموضوع', 'choices' => ContactMessage::choiceMap('subject'),
            'empty_label' => '---------', 'attrs' => FC],
        'message' => ['type' => 'textarea', 'label' => 'الرسالة',
            'attrs' => FC + ['rows' => 6, 'placeholder' => 'اكتب رسالتك أو استفسارك هنا...'],
            'validators' => [fn($v) => mb_strlen($v) < 10 ? 'الرسالة يجب أن تكون 10 أحرف على الأقل' : null]],
    ], $data);
}

// ====================================================================== students/forms.py

function student_registration_form(?array $data = null): Form
{
    $form = new Form([
        'registration_number' => ['type' => 'text', 'label' => 'رقم التسجيل الجامعي', 'max_length' => 20,
            'attrs' => FC + ['placeholder' => 'رقم التسجيل الجامعي'],
            'validators' => [function ($v) {
                if (StudentProfile::exists('registration_number = ?', [$v]) || User::exists('username = ?', [$v])) {
                    return 'رقم التسجيل هذا مسجل مسبقاً';
                }
                return null;
            }]],
        'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'max_length' => 254,
            'attrs' => FC + ['placeholder' => 'البريد الإلكتروني الجامعي'],
            'validators' => [fn($v) => User::exists('email = ?', [$v]) ? 'البريد الإلكتروني مسجل مسبقاً' : null]],
        'full_name_arabic' => ['type' => 'text', 'label' => 'الاسم الكامل بالعربية', 'max_length' => 100,
            'attrs' => FC + ['placeholder' => 'الاسم الكامل بالعربية']],
        'birth_date' => ['type' => 'date', 'label' => 'تاريخ الميلاد', 'attrs' => FC],
        'birth_place' => ['type' => 'text', 'label' => 'مكان الميلاد', 'max_length' => 100,
            'attrs' => FC + ['placeholder' => 'مكان الميلاد']],
        'gender' => ['type' => 'select', 'label' => 'الجنس', 'choices' => StudentProfile::choiceMap('gender'), 'attrs' => FC],
        'phone' => ['type' => 'text', 'label' => 'رقم الهاتف', 'max_length' => 10,
            'attrs' => FC + ['placeholder' => '0555123456'],
            'pattern' => StudentProfile::PHONE_PATTERN, 'pattern_message' => StudentProfile::PHONE_MESSAGE],
        'address' => ['type' => 'textarea', 'label' => 'العنوان', 'attrs' => FC + ['rows' => 3, 'placeholder' => 'العنوان الكامل']],
        'wilaya' => ['type' => 'text', 'label' => 'الولاية', 'max_length' => 100, 'attrs' => FC + ['placeholder' => 'الولاية']],
        'faculty' => ['type' => 'text', 'label' => 'الكلية', 'max_length' => 200, 'attrs' => FC + ['placeholder' => 'الكلية']],
        'department' => ['type' => 'text', 'label' => 'القسم/التخصص', 'max_length' => 200, 'attrs' => FC + ['placeholder' => 'القسم/التخصص']],
        'current_year' => ['type' => 'select', 'label' => 'السنة الدراسية', 'choices' => StudentProfile::choiceMap('current_year'), 'attrs' => FC],
        'password1' => ['type' => 'password', 'label' => 'كلمة المرور', 'attrs' => FC + ['placeholder' => 'كلمة المرور', 'autocomplete' => 'new-password']],
        'password2' => ['type' => 'password', 'label' => 'تأكيد كلمة المرور', 'attrs' => FC + ['placeholder' => 'تأكيد كلمة المرور', 'autocomplete' => 'new-password']],
    ], $data);
    $form->clean = function (Form $f) {
        $p1 = $f->cleaned['password1'] ?? '';
        $p2 = $f->cleaned['password2'] ?? '';
        if ($p1 !== $p2) {
            $f->add_error('password2', 'حقلا كلمة المرور غير متطابقين.');
            return;
        }
        foreach (password_errors($p1, (string) ($f->cleaned['registration_number'] ?? '')) as $err) {
            $f->add_error('password2', $err);
        }
    };
    return $form;
}

function student_login_form(?array $data = null): Form
{
    return new Form([
        'username' => ['type' => 'text', 'label' => 'رقم التسجيل أو اسم المستخدم', 'max_length' => 150,
            'attrs' => FC + ['placeholder' => 'رقم التسجيل أو اسم المستخدم', 'autocomplete' => 'username']],
        'password' => ['type' => 'password', 'label' => 'كلمة المرور',
            'attrs' => FC + ['placeholder' => 'كلمة المرور', 'autocomplete' => 'current-password']],
        'remember_me' => ['type' => 'checkbox', 'label' => 'تذكرني', 'required' => false, 'attrs' => ['class' => 'form-check-input']],
    ], $data);
}

function application_submission_form(?array $data = null, array $initial = []): Form
{
    $types = [];
    foreach (ApplicationType::active() as $t) {
        $types[$t['id']] = $t['name'];
    }
    return new Form([
        'application_type' => ['type' => 'select', 'label' => 'نوع الطلب', 'choices' => $types, 'empty_label' => '---------', 'attrs' => FC],
        'details' => ['type' => 'textarea', 'label' => 'تفاصيل الطلب', 'required' => false,
            'attrs' => FC + ['rows' => 5, 'placeholder' => 'اكتب تفاصيل إضافية عن الطلب (اختياري)']],
    ], $data, null, $initial);
}

function student_profile_form(?array $data = null, ?array $files = null, ?StudentProfile $student = null): Form
{
    $initial = [];
    if ($student) {
        foreach (['full_name_arabic', 'birth_date', 'birth_place', 'gender', 'phone', 'address', 'wilaya', 'faculty', 'department', 'current_year'] as $f) {
            $initial[$f] = $student[$f];
        }
    }
    return new Form([
        'full_name_arabic' => ['type' => 'text', 'label' => 'الاسم الكامل بالعربية', 'max_length' => 100, 'attrs' => FC + ['placeholder' => 'الاسم الكامل بالعربية']],
        'birth_date' => ['type' => 'date', 'label' => 'تاريخ الميلاد', 'attrs' => FC],
        'birth_place' => ['type' => 'text', 'label' => 'مكان الميلاد', 'max_length' => 100, 'attrs' => FC + ['placeholder' => 'مكان الميلاد']],
        'gender' => ['type' => 'select', 'label' => 'الجنس', 'choices' => StudentProfile::choiceMap('gender'), 'empty_label' => '---------', 'attrs' => FC],
        'phone' => ['type' => 'text', 'label' => 'رقم الهاتف', 'max_length' => 10, 'attrs' => FC + ['placeholder' => '0555123456'],
            'pattern' => StudentProfile::PHONE_PATTERN, 'pattern_message' => StudentProfile::PHONE_MESSAGE],
        'address' => ['type' => 'textarea', 'label' => 'العنوان', 'attrs' => FC + ['rows' => 3, 'placeholder' => 'العنوان الكامل']],
        'wilaya' => ['type' => 'text', 'label' => 'الولاية', 'max_length' => 100, 'attrs' => FC + ['placeholder' => 'الولاية']],
        'faculty' => ['type' => 'text', 'label' => 'الكلية', 'max_length' => 200, 'attrs' => FC + ['placeholder' => 'الكلية']],
        'department' => ['type' => 'text', 'label' => 'القسم/التخصص', 'max_length' => 200, 'attrs' => FC + ['placeholder' => 'القسم/التخصص']],
        'current_year' => ['type' => 'select', 'label' => 'السنة الدراسية', 'choices' => StudentProfile::choiceMap('current_year'), 'empty_label' => '---------', 'attrs' => FC],
        'photo' => ['type' => 'file', 'label' => 'الصورة الشخصية', 'required' => false, 'attrs' => FC + ['accept' => 'image/*'],
            'validators' => [fn($file) => Upload::validate($file, Upload::IMAGES, (float) config('uploads.max_image_mb', 5))]],
    ], $data, $files, $initial);
}

function user_email_form(?array $data = null, ?User $user = null): Form
{
    return new Form([
        'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'max_length' => 254,
            'attrs' => FC + ['placeholder' => 'البريد الإلكتروني (اختياري)'],
            'validators' => [fn($v) => $v !== '' && User::exists('email = ? AND id <> ?', [$v, (int) ($user['id'] ?? 0)]) ? 'البريد الإلكتروني مسجل مسبقاً' : null]],
    ], $data, null, $user ? ['email' => $user['email']] : []);
}

function password_change_form(?array $data = null): Form
{
    $form = new Form([
        'old_password' => ['type' => 'password', 'label' => 'كلمة المرور القديمة', 'attrs' => FC + ['placeholder' => 'أدخل كلمة المرور الحالية', 'autocomplete' => 'current-password']],
        'new_password1' => ['type' => 'password', 'label' => 'كلمة المرور الجديدة', 'help_text' => 'يجب أن تحتوي على 8 أحرف على الأقل',
            'attrs' => FC + ['placeholder' => 'أدخل كلمة المرور الجديدة', 'autocomplete' => 'new-password']],
        'new_password2' => ['type' => 'password', 'label' => 'تأكيد كلمة المرور الجديدة', 'attrs' => FC + ['placeholder' => 'أعد إدخال كلمة المرور الجديدة', 'autocomplete' => 'new-password']],
    ], $data);
    $form->clean = function (Form $f) {
        $p1 = $f->cleaned['new_password1'] ?? '';
        if ($p1 !== ($f->cleaned['new_password2'] ?? '')) {
            $f->add_error(null, 'كلمات المرور الجديدة غير متطابقة');
            return;
        }
        foreach (password_errors($p1, (string) (current_user()['username'] ?? '')) as $err) {
            $f->add_error('new_password1', $err);
        }
    };
    return $form;
}
