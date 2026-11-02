<?php
/**
 * تحويل بيانات Django (ملف dumpdata بصيغة JSON) إلى أوامر INSERT لقاعدة MySQL الجديدة.
 *
 * 1) في مشروع Django القديم:
 *      python manage.py dumpdata --natural-foreign --natural-primary \
 *          --exclude contenttypes --exclude auth.permission --exclude admin.logentry --exclude sessions \
 *          --indent 1 -o data.json
 * 2) هنا:
 *      php database/tools/django_json_to_sql.php path/to/data.json > database/data.sql
 * 3) استورد database/schema.sql ثم database/data.sql في phpMyAdmin.
 *
 * - التواريخ تُحوَّل من UTC إلى توقيت الجزائر (Africa/Algiers).
 * - كلمات مرور Django تبقى كما هي، والموقع الجديد يتعرف عليها ويحدّثها تلقائياً عند أول دخول.
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
if ($argc < 2 || !is_file($argv[1])) {
    fwrite(STDERR, "الاستعمال: php django_json_to_sql.php data.json > data.sql\n");
    exit(1);
}

$raw = file_get_contents($argv[1]);
// دعم ملفات UTF-16 / BOM (تصدير Windows PowerShell)
if (substr($raw, 0, 2) === "\xFF\xFE" || substr($raw, 0, 2) === "\xFE\xFF") {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
}
$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
$objects = json_decode($raw, true);
if (!is_array($objects)) {
    fwrite(STDERR, "ملف JSON غير صالح: " . json_last_error_msg() . "\n");
    exit(1);
}

$tz = new DateTimeZone('Africa/Algiers');

/**
 * model => [table, [columns...], [fk field => 'user'|'pk'], [nullable columns...]]
 */
$MAP = [
    'auth.user' => ['users', ['password', 'last_login', 'is_superuser', 'username', 'first_name', 'last_name', 'email', 'is_staff', 'is_active', 'date_joined'], [], ['last_login']],
    'homepage.slider' => ['sliders', ['title', 'subtitle', 'image', 'link', 'button_text', 'order', 'is_active', 'created_at'], [], ['link']],
    'homepage.statistics' => ['statistics', ['icon', 'number', 'label', 'order', 'is_active'], [], []],
    'homepage.directorword' => ['director_words', ['director_name', 'director_title', 'director_image', 'word_title', 'word_content', 'signature', 'is_active', 'created_at', 'updated_at'], [], ['director_image', 'signature']],
    'homepage.organizationstructure' => ['organization_structures', ['title', 'description', 'organization_chart', 'content', 'is_active', 'updated_at'], [], ['organization_chart']],
    'homepage.directoratehistory' => ['directorate_histories', ['title', 'introduction', 'content', 'main_image', 'founding_year', 'is_active', 'updated_at'], [], ['main_image', 'founding_year']],
    'news.news' => ['news', ['title', 'slug', 'summary', 'content', 'main_image', 'author', 'published_date', 'status', 'views_count', 'is_featured', 'show_in_ticker', 'order', 'created_at', 'updated_at'], ['author' => 'user'], ['main_image', 'author', 'published_date']],
    'news.newsimage' => ['news_images', ['news', 'image', 'caption', 'order', 'uploaded_at'], ['news' => 'pk'], []],
    'news.latestnews' => ['latest_news', ['description', 'image', 'link', 'order', 'is_active', 'created_at'], [], ['link']],
    'activities.activity' => ['activities', ['title', 'slug', 'activity_type', 'short_description', 'description', 'main_image', 'date', 'location', 'organizer', 'participants_count', 'is_featured', 'is_active', 'views_count', 'order', 'created_at', 'updated_at'], [], []],
    'activities.activityimage' => ['activity_images', ['activity', 'image', 'caption', 'order', 'uploaded_at'], ['activity' => 'pk'], []],
    'cities.cite' => ['cites', ['name', 'slug', 'description', 'main_image', 'address', 'capacity', 'phone', 'is_active', 'order', 'created_at', 'updated_at'], [], ['address', 'capacity', 'phone']],
    'cities.citeimage' => ['cite_images', ['cite', 'image', 'caption', 'order', 'uploaded_at'], ['cite' => 'pk'], []],
    'departements.departement' => ['departements', ['name', 'slug', 'description', 'image', 'head_of_department', 'email', 'phone', 'website', 'is_active', 'order', 'created_at', 'updated_at'], [], ['head_of_department', 'email', 'phone', 'website']],
    'services.service' => ['services', ['title', 'slug', 'service_type', 'short_description', 'detailed_description', 'main_image', 'icon', 'requirements', 'how_to_apply', 'contact_info', 'is_active', 'featured', 'display_order', 'created_at', 'updated_at'], [], ['main_image']],
    'contact.contactmessage' => ['contact_messages', ['full_name', 'email', 'phone', 'subject', 'message', 'is_read', 'is_replied', 'admin_notes', 'ip_address', 'created_at', 'updated_at'], [], ['ip_address']],
    'downloads.downloadfile' => ['download_files', ['title', 'slug', 'description', 'category', 'file', 'file_size', 'downloads_count', 'is_active', 'featured', 'display_order', 'created_at', 'updated_at'], [], []],
    'faq.faqcategory' => ['faq_categories', ['name', 'icon', 'order', 'is_active'], [], []],
    'faq.faq' => ['faqs', ['category', 'question', 'answer', 'order', 'is_active', 'views_count', 'created_at', 'updated_at'], ['category' => 'pk'], []],
    'notifications.notification' => ['notifications', ['title', 'message', 'notification_type', 'link', 'link_text', 'start_date', 'end_date', 'is_active', 'is_dismissible', 'priority', 'created_at', 'updated_at'], [], ['end_date']],
    'site_stats.sitestatistics' => ['site_statistics', ['total_visitors', 'total_page_views', 'updated_at'], [], []],
    'site_stats.sitevisit' => ['site_visits', ['ip_address', 'user_agent', 'page_visited', 'visited_at'], [], ['user_agent', 'page_visited']],
    'students.studentprofile' => ['student_profiles', ['user', 'registration_number', 'full_name_arabic', 'birth_date', 'birth_place', 'gender', 'phone', 'address', 'wilaya', 'university', 'faculty', 'department', 'current_year', 'average', 'photo', 'is_verified', 'is_active', 'created_at', 'updated_at'], ['user' => 'user'], ['average', 'photo']],
    'students.applicationtype' => ['application_types', ['name', 'description', 'icon', 'color', 'required_documents', 'is_active', 'order'], [], []],
    'students.studentapplication' => ['student_applications', ['student', 'application_type', 'application_number', 'status', 'details', 'admin_notes', 'submitted_at', 'reviewed_at', 'reviewed_by', 'updated_at'], ['student' => 'pk', 'application_type' => 'pk', 'reviewed_by' => 'user'], ['details', 'admin_notes', 'reviewed_at', 'reviewed_by']],
    'students.applicationdocument' => ['application_documents', ['application', 'document_name', 'document_file', 'uploaded_at'], ['application' => 'pk'], []],
    'students.studentnotification' => ['student_notifications', ['student', 'title', 'message', 'link', 'is_read', 'created_at', 'read_at', 'notification_type'], ['student' => 'pk'], ['link', 'read_at']],
];

// ---------------------------------------------------------------- معرّفات المستخدمين
$userIds = [];
$nextUserId = 1;
foreach ($objects as $o) {
    if (($o['model'] ?? '') === 'auth.user' && isset($o['pk'])) {
        $nextUserId = max($nextUserId, (int) $o['pk'] + 1);
    }
}
foreach ($objects as $i => $o) {
    if (($o['model'] ?? '') !== 'auth.user') {
        continue;
    }
    $id = isset($o['pk']) ? (int) $o['pk'] : $nextUserId++;
    $objects[$i]['pk'] = $id;
    $userIds[$o['fields']['username']] = $id;
}

function sql_value($v): string
{
    if ($v === null) {
        return 'NULL';
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    $s = (string) $v;
    $s = str_replace(['\\', "\0", "\n", "\r", "'", "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\Z'], $s);
    return "'" . $s . "'";
}

function convert_datetime($v, DateTimeZone $tz)
{
    if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $v)) {
        return $v;
    }
    try {
        $dt = new DateTime($v); // يتعرف على Z و +01:00
        $dt->setTimezone($tz);
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $v;
    }
}

$out = [];
$out[] = '-- =====================================================================';
$out[] = '--  بيانات الموقع المنقولة من Django/PostgreSQL';
$out[] = '--  أُنشئ بواسطة database/tools/django_json_to_sql.php في ' . date('Y-m-d H:i');
$out[] = '--  استورد هذا الملف بعد schema.sql';
$out[] = '-- =====================================================================';
$out[] = 'SET NAMES utf8mb4;';
$out[] = "SET time_zone = '+01:00';";
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

$grouped = [];
$skipped = [];
foreach ($objects as $o) {
    $model = $o['model'] ?? '';
    if (!isset($MAP[$model])) {
        $skipped[$model] = ($skipped[$model] ?? 0) + 1;
        continue;
    }
    $grouped[$model][] = $o;
}

foreach ($MAP as $model => [$table, $columns, $fks, $nullable]) {
    if (empty($grouped[$model])) {
        continue;
    }
    $sqlCols = ['`id`'];
    foreach ($columns as $c) {
        $sqlCols[] = '`' . (isset($fks[$c]) ? $c . '_id' : $c) . '`';
    }
    $rows = [];
    foreach ($grouped[$model] as $o) {
        $f = $o['fields'];
        $vals = [sql_value((int) $o['pk'])];
        foreach ($columns as $c) {
            $v = $f[$c] ?? null;
            if (isset($fks[$c])) {
                if (is_array($v)) {
                    $v = $fks[$c] === 'user' ? ($userIds[$v[0]] ?? null) : ($v[0] ?? null);
                }
                $vals[] = $v === null ? 'NULL' : sql_value((int) $v);
                continue;
            }
            if ($v === null && !in_array($c, $nullable, true)) {
                $v = '';
            }
            if ($c === 'average' && $v !== null && $v !== '') {
                $vals[] = sql_value((string) $v);
                continue;
            }
            $vals[] = sql_value(convert_datetime($v, $tz));
        }
        $rows[] = '(' . implode(', ', $vals) . ')';
    }
    $out[] = "-- $model => $table (" . count($rows) . ')';
    foreach (array_chunk($rows, 200) as $chunk) {
        $out[] = "INSERT INTO `$table` (" . implode(', ', $sqlCols) . ") VALUES\n" . implode(",\n", $chunk) . ';';
    }
    $out[] = '';
}

$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
echo implode("\n", $out), "\n";

foreach ($skipped as $model => $n) {
    fwrite(STDERR, "تم تجاهل $model ($n)\n");
}
