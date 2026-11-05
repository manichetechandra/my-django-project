<?php
/**
 * لقطات الفصل 46: اللقطة 46-receipt ترسل طلب ترشح حقيقياً إلى الموقع التجريبي (رقم التعريف SHOT_NIN).
 * بعد اللقطات يُحذف الطلب ووثائقه المشفّرة وسطور سجل الاطلاع:
 *   DB_NAME=dou_setif2 php tools/shots-recruit.php del /tmp/site2
 * لقطات الفصل 48: فتح ملفات المترشحين في لوحة الإدارة يضيف أسطراً إلى سجل الاطلاع. قبل اللقطات نحفظ آخر سطر،
 * وبعدها نحذف ما أُضيف بعده:
 *   DB_NAME=dou_setif2 php tools/shots-recruit.php mark /tmp/site2
 *   DB_NAME=dou_setif2 php tools/shots-recruit.php reset /tmp/site2
 */
$cmd = $argv[1] ?? '';
$site = $argv[2] ?? '/tmp/site2';
chdir($site);
require 'app/bootstrap.php';

const SHOT_NIN = '199804120000123456';

$markFile = STORAGE_PATH . '/audit-mark.txt';
if ($cmd === 'mark') {
    file_put_contents($markFile, (string) (int) DB::value('SELECT COALESCE(MAX(id), 0) FROM recruitment_audit'));
    exit('آخر سطر في سجل الاطلاع: ' . file_get_contents($markFile) . "\n");
}
if ($cmd === 'reset') {
    if (!is_file($markFile)) {
        exit("لا علامة محفوظة: شغّل mark قبل اللقطات\n");
    }
    $n = DB::query('DELETE FROM recruitment_audit WHERE id > ?', [(int) file_get_contents($markFile)])->rowCount();
    unlink($markFile);
    exit("حُذف $n سطراً من سجل الاطلاع\n");
}
if ($cmd !== 'del') {
    exit("الاستعمال: php tools/shots-recruit.php del|mark|reset [/tmp/site2]\n");
}
$n = 0;
foreach (CompetitionApplication::where('nin_hash = ?', [Crypto::blindIndex(SHOT_NIN)]) as $a) {
    foreach ($a['files'] as $f) {
        $path = $f->full_path((int) $a['competition_id']);
        if ($path) {
            unlink($path);
        }
    }
    DB::query('DELETE FROM recruitment_audit WHERE application_id = ?', [(int) $a['id']]);
    DB::query('DELETE FROM competition_applications WHERE id = ?', [(int) $a['id']]);
    echo 'حُذف الطلب ' . $a['tracking_code'] . "\n";
    $n++;
}
foreach (glob(STORAGE_PATH . '/ratelimit/*.json') as $f) {
    unlink($f);
}
echo $n ? '' : "لا طلب تجريبي\n";
