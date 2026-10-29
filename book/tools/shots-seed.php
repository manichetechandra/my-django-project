<?php
/**
 * بيانات تجريبية للقطات الشاشة في الكتاب (لا علاقة لها بالموقع نفسه).
 * تعيد إنشاء قاعدة البيانات من schema.sql و data.sql، ثم تضيف:
 *   - كلمة مرور معروفة للمدير miloudsellam وللطالبين الموجودين
 *   - ستة طلبة تجريبيين وعشرة طلبات موزعة على آخر 7 أيام بحالات مختلفة
 *   - وثيقة PDF تجريبية في storage/private (المجلد مستثنى من Git)
 * الاستعمال:  php tools/shots-seed.php [مجلد المشروع]   (الافتراضي: النسخة 1.0 المفكوكة في .build/project-v1)
 * يتطلب الامتداد mysqli. الاتصال: 127.0.0.1:3306 بالمستخدم root بلا كلمة مرور.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = realpath($argv[1] ?? __DIR__ . '/../.build/project-v1/dou-setif');
$m = new mysqli('127.0.0.1', 'root', '', '', 3306);
$m->set_charset('utf8mb4');
$m->query('DROP DATABASE IF EXISTS dou_setif');
$m->query('CREATE DATABASE dou_setif CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$m->select_db('dou_setif');
foreach (['schema.sql', 'data.sql'] as $f) {
    $m->multi_query(file_get_contents("$root/database/$f"));
    do {
        if ($r = $m->store_result()) {
            $r->free();
        }
    } while ($m->more_results() && $m->next_result());
}

$q = function (string $sql, array $p = []) use ($m) {
    $st = $m->prepare($sql);
    if ($p) {
        $st->bind_param(str_repeat('s', count($p)), ...array_map('strval', $p));
    }
    $st->execute();
    return $m->insert_id;
};
$at = fn(int $daysAgo, string $hm) => date('Y-m-d', strtotime("-$daysAgo days")) . " $hm:00";
// وقت اليوم في الماضي دائماً، مهما كانت ساعة التشغيل
$today = fn(int $minutesAgo) => date('Y-m-d H:i:s', max(strtotime('today 00:05'), time() - $minutesAgo * 60));

$q('UPDATE users SET password = ?, is_staff = 1, is_superuser = 1, is_active = 1 WHERE username = ?', [password_hash('AdminDemo2026', PASSWORD_DEFAULT), 'miloudsellam']);
$studentPass = password_hash('Talib2026x', PASSWORD_DEFAULT);
$q('UPDATE users SET password = ? WHERE id IN (2, 3)', [$studentPass]);
$q("UPDATE student_profiles SET photo = NULL WHERE photo = 'students/photos/hamdane.jpg'");

$students = [
    ['22345671', 'محمد أمين بوزيد', 'M', '0661234501', 'العلوم الاقتصادية', 'علوم التسيير', '2', 'سطيف'],
    ['22345672', 'فاطمة الزهراء حمادي', 'F', '0771234502', 'الطب', 'الصيدلة', '3', 'برج بوعريريج'],
    ['22345673', 'ياسين بن عيسى', 'M', '0551234503', 'التكنولوجيا', 'الإلكترونيك', 'M1', 'بجاية'],
    ['22345674', 'خديجة مرابط', 'F', '0661234504', 'الآداب واللغات', 'اللغة الإنجليزية', '1', 'المسيلة'],
    ['22345675', 'عبد الرحمن قاسمي', 'M', '0771234505', 'الحقوق', 'القانون الخاص', 'M2', 'سطيف'],
    ['22345676', 'سارة بلعيد', 'F', '0551234506', 'العلوم', 'الرياضيات', '2', 'جيجل'],
];
$sid = [];
foreach ($students as $i => [$reg, $name, $g, $phone, $fac, $dep, $year, $wil]) {
    $uid = $q('INSERT INTO users (username, password, email, is_active, date_joined) VALUES (?, ?, ?, 1, ?)',
        [$reg, $studentPass, "student$reg@example.dz", $at(20 + $i, '10:00')]);
    $sid[] = $q('INSERT INTO student_profiles (user_id, registration_number, full_name_arabic, birth_date, birth_place, gender, phone, address, wilaya, faculty, department, current_year, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
        [$uid, $reg, $name, '2003-0' . ($i + 1) . '-1' . $i, $wil, $g, $phone, "حي 500 مسكن، $wil", $wil, $fac, $dep, $year, $at(20 + $i, '10:00'), $at(20 + $i, '10:00')]);
}
[$s5, $s6, $s7, $s8, $s9, $s10] = $sid;
$ahmed = 3;

$apps = [
    // [رقم، طالب، نوع، حالة، تاريخ التقديم، تفاصيل، ملاحظات]
    ['202600001', $s5, 1, 'approved', $at(6, '09:15'), 'أطلب الاستفادة من المنحة الجامعية للسنة الدراسية الحالية.', ''],
    ['202600002', $s6, 2, 'approved', $at(5, '10:40'), 'أقطن في برج بوعريريج وأحتاج غرفة في الإقامة الجامعية.', ''],
    ['202600003', $s7, 1, 'rejected', $at(5, '14:05'), '', 'شهادة الدخل غير واضحة، يرجى إعادة رفعها بصيغة PDF.'],
    ['202600004', $s8, 2, 'under_review', $at(4, '11:20'), 'طالبة سنة أولى من ولاية المسيلة.', ''],
    ['202600005', $s9, 1, 'under_review', $at(3, '08:50'), '', ''],
    ['202600006', $s10, 2, 'pending', $at(2, '13:30'), 'أرجو منحي غرفة قريبة من كلية العلوم.', ''],
    ['202600007', $ahmed, 1, 'pending', $at(1, '09:05'), "أطلب المنحة الجامعية.\nأرفقت شهادة الدخل الخاصة بالوالد.", ''],
    ['202600008', $s5, 2, 'pending', $at(1, '15:45'), '', ''],
    ['202600009', $s6, 1, 'pending', $today(150), "أطلب الاستفادة من المنحة الجامعية.\nمرفق: شهادة الدخل.", ''],
    ['202600010', $s9, 2, 'pending', $today(40), '', ''],
];
$ids = [];
foreach ($apps as [$num, $student, $type, $status, $when, $details, $notes]) {
    $final = in_array($status, ['approved', 'rejected'], true);
    $reviewedAt = $final ? date('Y-m-d H:i:s', strtotime($when . ' +1 day')) : null;
    $ids[$num] = $q('INSERT INTO student_applications (student_id, application_type_id, application_number, status, details, admin_notes, submitted_at, reviewed_at, reviewed_by_id, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ' . ($final ? '?, 1' : 'NULL, NULL') . ', ?)',
        array_merge([$student, $type, $num, $status, $details, $notes, $when], $final ? [$reviewedAt] : [], [$reviewedAt ?? $when]));
}

// وثيقة تجريبية (ملف PDF صغير صالح) لطلبين معلقين من نوع المنحة
$rel = 'documents/' . date('Y/m') . '/shahadat-dakhl-demo.pdf';
$abs = "$root/storage/private/$rel";
@mkdir(dirname($abs), 0775, true);
file_put_contents($abs, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
foreach (['202600007', '202600009'] as $num) {
    $q('INSERT INTO application_documents (application_id, document_name, document_file, uploaded_at) VALUES (?, ?, ?, ?)',
        [$ids[$num], 'شهادة الدخل', $rel, $apps[array_search($num, array_column($apps, 0))][4]]);
}

$r = $m->query('SELECT status, COUNT(*) n FROM student_applications GROUP BY status')->fetch_all(MYSQLI_ASSOC);
echo 'تم: ', json_encode(array_column($r, 'n', 'status'), JSON_UNESCAPED_UNICODE), "\n";
