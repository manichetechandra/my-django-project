<?php
/**
 * بيانات تجريبية كاملة للقطات شاشة الكتاب (ليست جزءاً من الموقع).
 *   php demo-seed.php /tmp/site
 * 1) تعيد إنشاء قاعدة dou_setif (أو DB_NAME من البيئة) من database/schema.sql في مجلد الموقع المعطى
 * 2) تنفّذ book/demo/demo-content.sql (محتوى الموقع العام: نفس بيانات الفصل 14 + بقية الجداول)
 * 3) تضيف الحسابات: المدير miloudsellam / AdminDemo2026، الموظف hicham41 / StaffDemo2026،
 *    والطلبة (كلمة المرور Talib2026x) مع طلبات بحالات مختلفة وإشعارات
 * 4) تنشئ ملفات PDF تجريبية (مركز التحميلات + وثيقة طلب) داخل مجلد الموقع المعطى
 * الاتصال: 127.0.0.1:3306 بالمستخدم root بلا كلمة مرور.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$site = rtrim($argv[1] ?? '/tmp/site', '/');
$book = realpath(__DIR__ . '/..');
$m = new mysqli('127.0.0.1', 'root', '', '', 3306);
$m->set_charset('utf8mb4');
$db = getenv('DB_NAME') ?: 'dou_setif';
$m->query("DROP DATABASE IF EXISTS `$db`");
$m->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$m->select_db($db);
foreach (["$site/database/schema.sql", "$book/demo/demo-content.sql"] as $f) {
    $m->multi_query(file_get_contents($f));
    do {
        if ($r = $m->store_result()) {
            $r->free();
        }
    } while ($m->more_results() && $m->next_result());
}

$q = function (string $sql, array $p = []) use ($m) {
    $st = $m->prepare($sql);
    if ($p) {
        $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($v) => $v === null ? null : (string) $v, $p));
    }
    $st->execute();
    return $m->insert_id;
};
$at = fn(int $daysAgo, string $hm) => date('Y-m-d', strtotime("-$daysAgo days")) . " $hm:00";
$today = fn(int $minutesAgo) => date('Y-m-d H:i:s', max(strtotime('today 00:05'), time() - $minutesAgo * 60));

// ------------------------------------------------------------------ الحسابات
$admin = $q('INSERT INTO users (username, password, first_name, last_name, email, is_staff, is_superuser, date_joined) VALUES (?, ?, ?, ?, ?, 1, 1, ?)',
    ['miloudsellam', password_hash('AdminDemo2026', PASSWORD_DEFAULT), 'ميلود', 'سلام', 'admin@dou-setif.dz', $at(60, '09:00')]);
$q('INSERT INTO users (username, password, first_name, last_name, email, is_staff, date_joined) VALUES (?, ?, ?, ?, ?, 1, ?)',
    ['hicham41', password_hash('StaffDemo2026', PASSWORD_DEFAULT), 'هشام', 'محمد', 'hicham@dou-setif.dz', $at(50, '09:00')]);
$studentPass = password_hash('Talib2026x', PASSWORD_DEFAULT);
$students = [
    ['1234566', 'أحمد بن جدي', 'M', '0661234500', 'التكنولوجيا', 'الإعلام الآلي', 'M1', 'سطيف'],
    ['22345671', 'محمد أمين بوزيد', 'M', '0661234501', 'العلوم الاقتصادية', 'علوم التسيير', '2', 'سطيف'],
    ['22345672', 'فاطمة الزهراء حمادي', 'F', '0771234502', 'الطب', 'الصيدلة', '3', 'برج بوعريريج'],
    ['22345673', 'ياسين بن عيسى', 'M', '0551234503', 'التكنولوجيا', 'الإلكترونيك', 'M1', 'بجاية'],
    ['22345674', 'خديجة مرابط', 'F', '0661234504', 'الآداب واللغات', 'اللغة الإنجليزية', '1', 'المسيلة'],
    ['22345675', 'عبد الرحمن قاسمي', 'M', '0771234505', 'الحقوق', 'القانون الخاص', 'M2', 'سطيف'],
    ['22345676', 'سارة بلعيد', 'F', '0551234506', 'العلوم', 'الرياضيات', '2', 'جيجل'],
];
$sid = [];
foreach ($students as $i => [$reg, $name, $g, $phone, $fac, $dep, $year, $wil]) {
    [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
    $uid = $q('INSERT INTO users (username, password, first_name, last_name, email, date_joined) VALUES (?, ?, ?, ?, ?, ?)',
        [$reg, $studentPass, $first, $last, "student$reg@example.dz", $at(30 - $i, '10:00')]);
    $sid[] = $q('INSERT INTO student_profiles (user_id, registration_number, full_name_arabic, birth_date, birth_place, gender, phone, address, wilaya, university, faculty, department, current_year, average, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
        [$uid, $reg, $name, '200' . (2 + $i % 3) . '-0' . ($i % 9 + 1) . '-1' . $i, $wil, $g, $phone, "حي 500 مسكن، $wil", $wil, 'جامعة سطيف', $fac, $dep, $year, '13.' . (20 + $i * 7), $at(30 - $i, '10:00'), $at(30 - $i, '10:00')]);
}
[$ahmed, $s5, $s6, $s7, $s8, $s9, $s10] = $sid;

// ------------------------------------------------------------------ أنواع الطلبات والطلبات
$q("INSERT INTO application_types (name, description, icon, color, required_documents, `order`) VALUES
    ('طلب المنحة الجامعية', 'طلب الحصول على المنحة الدراسية', 'fa-file-text', '#3498db', 'شهادة الدخل', 1),
    ('طلب السكن الجامعي', 'طلب الاستفادة من سكن جامعي', 'fa-home', '#8cc040', 'شهادة الإقامة', 2)");
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
    ['202600011', $ahmed, 2, 'approved', $at(9, '10:00'), 'طلب غرفة في إقامة دباغين.', ''],
];
$ids = [];
foreach ($apps as [$num, $student, $type, $status, $when, $details, $notes]) {
    $final = in_array($status, ['approved', 'rejected'], true);
    $reviewedAt = $final ? date('Y-m-d H:i:s', strtotime($when . ' +1 day')) : null;
    $ids[$num] = $q('INSERT INTO student_applications (student_id, application_type_id, application_number, status, details, admin_notes, submitted_at, reviewed_at, reviewed_by_id, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ' . ($final ? '?, 1' : 'NULL, NULL') . ', ?)',
        array_merge([$student, $type, $num, $status, $details, $notes, $when], $final ? [$reviewedAt] : [], [$reviewedAt ?? $when]));
}

// ------------------------------------------------------------------ إشعارات أحمد (الطالب الذي ندخل بحسابه في اللقطات)
foreach ([
    ['تم استلام طلبك', 'تم استلام طلبك رقم 202600007 (طلب المنحة الجامعية) وهو قيد الانتظار.', 'info', 0, $at(1, '09:06')],
    ['تم اعتماد طلبك ✅', 'تم اعتماد طلبك رقم 202600011 (طلب السكن الجامعي).', 'success', 1, $at(8, '11:00')],
    ['مرحباً بك في فضاء الطالب', 'يمكنك الآن تقديم طلباتك ومتابعتها من حسابك.', 'info', 1, $at(29, '10:05')],
] as [$title, $msg, $type, $read, $when]) {
    $q('INSERT INTO student_notifications (student_id, title, message, notification_type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$ahmed, $title, $msg, $type, '/students/applications/', $read, $when]);
}

// ------------------------------------------------------------------ عدّادات الزوار
$q('INSERT INTO site_statistics (id, total_visitors, total_page_views, updated_at) VALUES (1, 15230, 48975, ?)', [date('Y-m-d H:i:s')]);

// ------------------------------------------------------------------ ملفات PDF تجريبية
$pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
foreach ($m->query('SELECT id, file FROM download_files')->fetch_all(MYSQLI_ASSOC) as $row) {
    $abs = "$site/uploads/" . $row['file'];
    @mkdir(dirname($abs), 0775, true);
    file_put_contents($abs, $pdf . str_repeat("% demo\n", 2000 + 300 * (int) $row['id']));
    $q('UPDATE download_files SET file_size = ? WHERE id = ?', [filesize($abs), $row['id']]);
}
$rel = 'documents/' . date('Y/m') . '/shahadat-dakhl-demo.pdf';
$abs = "$site/storage/private/$rel";
@mkdir(dirname($abs), 0775, true);
file_put_contents($abs, $pdf);
foreach (['202600007', '202600009'] as $num) {
    $q('INSERT INTO application_documents (application_id, document_name, document_file, uploaded_at) VALUES (?, ?, ?, ?)',
        [$ids[$num], 'شهادة الدخل', $rel, $apps[array_search($num, array_column($apps, 0))][4]]);
}

$t = array_map(fn($r) => $r[0], $m->query('SHOW TABLES')->fetch_all());
$n = $m->query('SELECT COUNT(*) FROM student_applications')->fetch_row()[0];
echo "$db: ", count($t), " جدول، $n طلب\n";
