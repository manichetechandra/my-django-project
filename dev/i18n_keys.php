<?php
/**
 * كل نصوص الواجهة العربية (مفاتيح الترجمة) ومقارنتها بملفات app/lang/*.php
 *   php -d extension=tokenizer dev/i18n_keys.php            ← ملخص + النصوص الناقصة في كل لغة
 *   php -d extension=tokenizer dev/i18n_keys.php --json     ← قائمة كل المفاتيح (JSON)
 */
$root = realpath(__DIR__ . '/../php');
$skipFiles = ['/app/admin_config.php', '/app/controllers/admin.php', '/app/i18n.php', '/app/core/DB.php', '/app/core/Router.php',
              '/app/core/View.php', '/app/core/XlsxWriter.php', '/app/bootstrap.php', '/app/lang/fr.php', '/app/lang/en.php'];
$skipKeys = ['في', 'من', 'إلى', 'على', 'عن', 'هذا', 'هذه', 'ذلك', 'تلك', 'أن', 'أو', 'لا', 'ما', 'هل', 'ال', '، ', 'ص', 'م',
             'خطأ في تسجيل الزيارة: ', '() غير موجودة', 'تعذر إنشاء مجلد الرفع: ', 'تعذر حفظ الملف المرفوع.', 'العربية'];
$files = [$root . '/index.php'];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app'));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($root));
    if ($f->isFile() && str_ends_with($rel, '.php') && !in_array($rel, $skipFiles, true) && !str_starts_with($rel, '/app/views/admin/')) {
        $files[] = $f->getPathname();
    }
}
sort($files);
$keys = [];
foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    foreach ($tokens as $i => $t) {
        // tx('السياق', 'النص') ← مفتاح إضافي «السياق|النص»
        if (is_array($t) && $t[0] === T_STRING && $t[1] === 'tx') {
            $str = array_values(array_filter(array_slice($tokens, $i + 1, 6), fn($u) => is_array($u) && $u[0] === T_CONSTANT_ENCAPSED_STRING));
            if (count($str) >= 2) {
                $keys[substr($str[0][1], 1, -1) . '|' . substr($str[1][1], 1, -1)][] = substr($file, strlen($root) + 1) . ':' . $t[2];
            }
        }
        if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING || !preg_match('/\p{Arabic}/u', $t[1])) continue;
        $raw = $t[1];
        $s = $raw[0] === "'" ? strtr(substr($raw, 1, -1), ["\\'" => "'", '\\\\' => '\\']) : stripcslashes(substr($raw, 1, -1));
        if (in_array($s, $skipKeys, true) || mb_strlen($s) === 1) continue;  // حروف مفردة (تحويل الأسماء إلى روابط)
        if (preg_match('/^[\x{0621}-\x{064A}]$/u', $s)) continue;
        $keys[$s][] = substr($file, strlen($root) + 1) . ':' . $t[2];
    }
}
// نصوص الأشهر والأيام ووحدات الوقت تُترجم في helpers.php مباشرة
foreach (array_keys($keys) as $k) {
    $where = $keys[$k];
    if (count($where) && array_reduce($where, fn($c, $w) => $c && str_starts_with($w, 'app/helpers.php:') && (int) explode(':', $w)[1] >= 380, true)) unset($keys[$k]);
}
if (in_array('--tsv', $argv, true)) {
    // مرقمة حسب أول ظهور (الملف ثم السطر): id <TAB> المكان <TAB> النص
    $rows = [];
    foreach ($keys as $k => $w) {
        [$f, $l] = explode(':', $w[0]);
        $rows[] = [$f, (int) $l, $k, $w[0]];
    }
    usort($rows, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    foreach ($rows as $i => $r) {
        printf("k%03d\t%s\t%s\n", $i + 1, $r[3], str_replace(["\\", "\n", "\t"], ['\\\\', '\\n', '\\t'], $r[2]));
    }
    exit;
}
if (in_array('--json', $argv, true)) {
    echo json_encode(array_keys($keys), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
    exit;
}
echo count($keys), " نص في الواجهة\n";
foreach (['fr', 'en'] as $code) {
    $map = require $root . "/app/lang/$code.php";
    $missing = array_diff_key($keys, $map);
    $unused = array_diff_key($map, $keys);
    echo "$code: " . count($map) . ' ترجمة، ناقص ' . count($missing) . '، غير مستعمل ' . count($unused) . "\n";
    if (in_array('--missing', $argv, true)) foreach ($missing as $k => $w) echo "   - $k    ← {$w[0]}\n";
    if (in_array('--unused', $argv, true)) foreach ($unused as $k => $v) echo "   ? $k\n";
}
