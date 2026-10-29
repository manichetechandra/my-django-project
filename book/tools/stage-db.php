<?php
/**
 * قاعدة بيانات تجريبية للمراحل (لقطات الشاشة واختبار الكود المؤقت) — ليست جزءاً من الموقع.
 *   php tools/stage-db.php DBNAME file1.sql [file2.sql ...]   يعيد إنشاء القاعدة وينفّذ الملفات بالترتيب
 * عادةً عبر:  bash tools/stage-db.sh 15    (قاعدة dou_stage كما تكون عند القارئ في نهاية الفصل 15)
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$m = new mysqli('127.0.0.1', 'root', '', '', 3306);
$m->set_charset('utf8mb4');
$name = $argv[1];
$m->query("DROP DATABASE IF EXISTS `$name`");
$m->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$m->select_db($name);
foreach (array_slice($argv, 2) as $f) {
    $m->multi_query(file_get_contents($f));
    do {
        if ($r = $m->store_result()) {
            $r->free();
        }
    } while ($m->more_results() && $m->next_result());
}
$t = array_map(fn($r) => $r[0], $m->query('SHOW TABLES')->fetch_all());
echo "$name: " . count($t) . " جدول (" . implode(', ', $t) . ")\n";
