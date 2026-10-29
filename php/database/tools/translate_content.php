<?php
/**
 * ترجمة المحتوى العربي الموجود إلى الفرنسية والإنجليزية (بعد الترقية إلى النسخة 2.0)
 * — نفس عمل صفحة «لوحة الإدارة ← ترجمة المحتوى»، لكن من سطر الأوامر.
 *
 *   php database/tools/translate_content.php              يترجم كل السجلات التي تنقصها ترجمة
 *   php database/tools/translate_content.php --dry-run    يعرض العدد فقط، دون ترجمة
 *   php database/tools/translate_content.php --limit=20   يترجم 20 سجلاً فقط (لتوزيع الحصة المجانية على أيام)
 *   php database/tools/translate_content.php --force      يعيد ترجمة كل شيء (يستبدل الترجمات الموجودة!)
 *
 * يستعمل إعدادات 'translation' في config.php (مفتاح Gemini، أو MyMemory بديلاً).
 * في XAMPP على Windows (من داخل مجلد المشروع):
 *   C:\xampp\php\php.exe database\tools\translate_content.php --dry-run
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

$opts = getopt('', ['dry-run', 'limit:', 'force', 'help']);
if (isset($opts['help'])) {
    echo "php database/tools/translate_content.php [--dry-run] [--limit=N] [--force]\n";
    exit(0);
}
$force = isset($opts['force']);
$limit = isset($opts['limit']) ? max(1, (int) $opts['limit']) : PHP_INT_MAX;

$counts = Translator::missingCounts($force);
if (!$counts) {
    echo "كل المحتوى مترجم: لا يوجد سجل ينقصه ترجمة.\n";
    exit(0);
}
echo $force ? "سجلات ستُعاد ترجمتها:\n" : "سجلات تنقصها ترجمة:\n";
foreach ($counts as $table => $n) {
    printf("  %-26s %d\n", $table, $n);
}
echo '  المجموع: ' . array_sum($counts) . "\n";
if (isset($opts['dry-run'])) {
    exit(0);
}
if (!Translator::enabled()) {
    fwrite(STDERR, "الترجمة التلقائية غير مفعّلة: ضع مفتاح Gemini في config.php ('translation' => 'gemini_key') أو اجعل 'provider' => 'mymemory'.\n");
    exit(1);
}
echo 'الخدمة: ' . implode(' ← ', Translator::providers()) . "\n\n";

Translator::$patient = true; // عند تجاوز حد الدقيقة ننتظر ثم نكمل
$done = 0;
$skip = []; // السجلات التي عولجت (حتى لا نعيدها مع --force أو إن رجعت الخدمة نصاً فارغاً)
while ($done < $limit) {
    try {
        $res = Translator::fillMissing((int) min(5, $limit - $done), $force, $skip);
    } catch (Throwable $e) {
        fwrite(STDERR, "\nتوقفت الترجمة (السجلات المترجمة حتى الآن: $done): " . $e->getMessage() . "\nأعد تشغيل الأمر لاحقاً: يكمل من حيث توقف.\n");
        exit(1);
    }
    foreach ($res['log'] as $line) {
        echo "✓ $line\n";
    }
    foreach ($res['ids'] as $table => $ids) {
        $skip[$table] = array_merge($skip[$table] ?? [], $ids);
    }
    $done += $res['done'];
    if ($res['done'] === 0) {
        break;
    }
}
echo "\nانتهى. السجلات المترجمة: $done" . (Translator::$usedProvider ? ' (' . Translator::$usedProvider . ')' : '') . ".\n";
