<?php
/**
 * قاموس الترجمة الوهمية للقطات الكتاب (الفصل 40): كل نص عربي في الموقع التجريبي مع ترجمته الموجودة
 * (من dev/demo-i18n.sql)، فيعيدها خادم dev/mock_translate.py كما لو ترجمها Gemini.
 *
 *   DB_NAME=dou_setif2 php book/tools/mock-words.php /tmp/site2 > /tmp/mock-words.json
 *   MOCK_WORDS=/tmp/mock-words.json python3 dev/mock_translate.py 8999
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$site = $argv[1] ?? '/tmp/site2';
require $site . '/app/bootstrap.php';

$out = ['fr' => [], 'en' => []];
foreach (Translator::contentModels() as $class) {
    foreach (DB::all('SELECT * FROM `' . $class::table() . '`') as $row) {
        foreach ($class::translatable() as $f) {
            $ar = trim((string) ($row[$f] ?? ''));
            if ($ar === '') {
                continue;
            }
            foreach (['fr', 'en'] as $lang) {
                $tr = trim((string) ($row[$f . '_' . $lang] ?? ''));
                if ($tr !== '') {
                    $out[$lang][$ar] = $tr;
                }
            }
        }
    }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
