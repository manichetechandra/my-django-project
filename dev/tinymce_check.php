<?php
/**
 * أين يجد الموقع TinyMCE؟ فحص admin_tinymce_locate (php/app/controllers/admin.php) بلا خادم ولا قاعدة بيانات:
 *   /tmp/tools/bin/php dev/tinymce_check.php
 * يبني موقعاً مؤقتاً htdocs/dou-setif (مثل XAMPP) ويضع نسخة المستخدم في كل مكان معقول، ثم يتحقق:
 *   أي نسخة اختيرت (نسخة المستخدم دائماً قبل نسخة المشروع)، رابطها، ملف العربية، وعلامة «نسختك / نسخة المشروع».
 */
require dirname(__DIR__) . '/php/app/controllers/admin.php';

$fail = 0;
$pass = 0;
function check(bool $ok, string $label, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) { $pass++; return; }
    $fail++;
    echo "  ✗ $label" . ($detail !== '' ? "\n      " . $detail : '') . "\n";
}

$REAL = dirname(__DIR__) . '/php/' . ADMIN_TINYMCE_BUNDLED_DIR;
$B = ADMIN_TINYMCE_BUNDLED_DIR; // assets/vendor/tinymce-8.10.1
$TMP = sys_get_temp_dir() . '/tinymce_check_' . getmypid();
$HT = "$TMP/htdocs";
$SITE = "$HT/dou-setif";

function put(string $path, string $content = "/* user tinymce */\n"): void
{
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $content);
}
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}
/** موقع جديد فيه نسخة المشروع فقط (tinymce.min.js الحقيقي + langs/ar.js + بعض مجلداتها) */
function fresh(): void
{
    global $TMP, $SITE, $REAL, $B;
    rrmdir($TMP);
    foreach (['tinymce.min.js', 'langs/ar.js', 'themes/silver/theme.min.js', 'plugins/lists/plugin.min.js', 'plugins/image/plugin.min.js', 'skins/ui/oxide/skin.min.css', 'icons/default/icons.min.js', 'models/dom/model.min.js'] as $f) {
        put("$SITE/$B/$f", file_get_contents("$REAL/$f"));
    }
    foreach (['app', 'uploads', 'storage', 'database', 'assets/css', 'assets/js'] as $d) {
        mkdir("$SITE/$d", 0777, true);
    }
}
/** نسخة مستخدم بالشكل الرسمي: tinymce.min.js + plugins/ (منها إضافة بلا ملف) + langs/README.md (بلا العربية) */
function user_copy(string $dir, string $file = 'tinymce.min.js'): void
{
    put("$dir/$file");
    put("$dir/plugins/lists/plugin.min.js");
    put("$dir/plugins/emoticons/plugin.min.js");
    put("$dir/plugins/media/plugin.js");
    put("$dir/plugins/notaplugin/readme.txt", 'x');
    put("$dir/themes/silver/theme.min.js");
    put("$dir/langs/README.md", "langs\n");
}
function locate(string $custom = '', ?string $docRoot = null, string $baseUrl = '/dou-setif'): ?array
{
    global $SITE, $HT;
    return admin_tinymce_locate($SITE, $baseUrl, $docRoot ?? $HT, $custom);
}
/** المسار المختار مع التحقق من بقية الحقول لنسخة المستخدم */
function expect_user(string $label, string $path, ?array $t, string $lang = ''): void
{
    check($t !== null && $t['path'] === $path, "$label: المسار $path", var_export($t['path'] ?? null, true));
    if ($t === null) {
        return;
    }
    check($t['bundled'] === false, "$label: «نسختك» لا «نسخة المشروع»");
    check($t['lang'] === $lang, "$label: ملف العربية " . ($lang ?: '(من ملفات المشروع حسب الإصدار)'), $t['lang']);
    check($t['plugins'] === ['emoticons', 'lists', 'media'], "$label: إضافات مجلدها (plugin.min.js أو plugin.js)", implode(' ', $t['plugins']));
}

echo "TinyMCE: البحث عن نسخة المستخدم\n";

// 1) نسخة المشروع وحدها
fresh();
$t = locate();
check($t !== null && $t['path'] === "$B/tinymce.min.js", 'نسخة المشروع وحدها: المسار', var_export($t, true));
check(($t['bundled'] ?? null) === true, 'نسخة المشروع: علامة «النسخة المرفقة بالمشروع» (بصمة sha256)');
check(($t['lang'] ?? '') === 'ar', 'نسخة المشروع: langs/ar.js الخاص بها');
check(($t['plugins'] ?? []) === ['image', 'lists'], 'نسخة المشروع: قائمة إضافاتها', implode(' ', $t['plugins'] ?? []));
check(($t['base'] ?? '') === "/dou-setif/$B" && ($t['suffix'] ?? '') === '.min', 'نسخة المشروع: base و suffix');
check(preg_match('#^/dou-setif/assets/vendor/tinymce-8\.10\.1/tinymce\.min\.js\?v=\d+$#', $t['url'] ?? '') === 1, 'نسخة المشروع: الرابط مع ?v=', $t['url'] ?? '');
$t = locate('', null, '');
check(($t['url'] ?? '') !== '' && strpos($t['url'], "/$B/tinymce.min.js?v=") === 0, 'موقع في جذر الخادم (base_url فارغ)', $t['url'] ?? '');
$real = admin_tinymce_locate(dirname(__DIR__) . '/php', '', '', '');
check(($real['plugins'] ?? []) !== [] && count($real['plugins']) === 29 && in_array('image', $real['plugins'], true) && in_array('media', $real['plugins'], true)
    && ($real['path'] ?? '') === "$B/tinymce.min.js" && ($real['bundled'] ?? null) === true, 'نسخة المشروع الحقيقية: كل الإضافات الـ 29', implode(' ', $real['plugins'] ?? []));

// 2) لا شيء
rrmdir("$SITE/$B");
check(locate() === null, 'لا TinyMCE إطلاقاً ← null (رسالة في النموذج)');

// 3) أماكن نسخة المستخدم (التحميل الرسمي: tinymce/js/tinymce/tinymce.min.js)
$places = [
    'tinymce/js/tinymce/tinymce.min.js'                                   => 'مجلد التحميل الرسمي كما هو في مجلد الموقع',
    'tinymce/tinymce.min.js'                                              => 'محتوى js/tinymce في مجلد الموقع/tinymce',
    'tinymce_7.9.3/tinymce/js/tinymce/tinymce.min.js'                     => 'مجلد باسم الإصدار (tinymce_7.9.3) فيه التحميل الرسمي',
    'TinyMCE/js/tinymce/tinymce.min.js'                                   => 'اسم بحروف كبيرة',
    'assets/tinymce/tinymce.min.js'                                       => 'assets/tinymce',
    'assets/js/tinymce/tinymce.min.js'                                    => 'assets/js/tinymce',
    'assets/vendor/tinymce/tinymce.min.js'                                => 'حالة المستخدم: حذف مجلد المشروع ووضع مجلده مكانه (assets/vendor/tinymce)',
    'assets/vendor/tinymce/js/tinymce/tinymce.min.js'                     => 'مجلد js من التحميل داخل assets/vendor/tinymce',
    'assets/vendor/tinymce/tinymce/js/tinymce/tinymce.min.js'             => 'التحميل الرسمي كله داخل assets/vendor/tinymce',
    'assets/vendor/tinymce_6.8.6/tinymce/js/tinymce/tinymce.min.js'       => 'مجلد باسم الإصدار داخل assets/vendor',
    'assets/vendor/editor/tinymce.min.js'                                 => 'أي اسم داخل assets/vendor (editor)',
    'assets/vendor/js/tinymce/tinymce.min.js'                             => 'مجلد js من التحميل مباشرة في assets/vendor',
    'assets/vendor/tinymce-8.10.1/tinymce_7/js/tinymce/tinymce.min.js'    => 'نسختك داخل مجلد نسخة المشروع',
    'js/tinymce/tinymce.min.js'                                           => 'js/tinymce',
    'lib/tinymce/tinymce.min.js'                                          => 'lib/tinymce',
    'libs/tinymce/js/tinymce/tinymce.min.js'                              => 'libs/tinymce',
    'vendor/tinymce/tinymce/tinymce.min.js'                               => 'Composer: vendor/tinymce/tinymce',
    'node_modules/tinymce/tinymce.min.js'                                 => 'npm: node_modules/tinymce',
    'tinymce/node_modules/tinymce/tinymce.min.js'                         => 'مشروع npm داخل مجلد tinymce',
];
foreach ($places as $rel => $label) {
    fresh();
    user_copy(dirname("$SITE/$rel"));
    expect_user($label, $rel, locate());
}

// 4) ملف العربية في نسخة المستخدم نفسها
fresh();
user_copy("$SITE/tinymce/js/tinymce");
put("$SITE/tinymce/js/tinymce/langs/ar.js", "tinymce.addI18n('ar',{});");
expect_user('نسخة المستخدم مع langs/ar.js', 'tinymce/js/tinymce/tinymce.min.js', locate(), 'ar');
unlink("$SITE/tinymce/js/tinymce/langs/ar.js");
put("$SITE/tinymce/js/tinymce/langs/ar_SA.js", "tinymce.addI18n('ar_SA',{});");
expect_user('نسخة المستخدم مع langs/ar_SA.js', 'tinymce/js/tinymce/tinymce.min.js', locate(), 'ar_SA');

// 5) ملفات المستخدم مكان ملفات نسخة المشروع (assets/vendor/tinymce/tinymce.min.js مختلف)
fresh();
put("$SITE/$B/tinymce.min.js", "/* TinyMCE version 7.9.3 */\n");
$t = locate();
check(($t['path'] ?? '') === "$B/tinymce.min.js" && ($t['bundled'] ?? null) === false, 'ملفات المستخدم مكان ملفات المشروع ← «نسختك»', var_export($t, true));
check(($t['lang'] ?? '') === 'ar', 'ملفات المستخدم مكان ملفات المشروع: langs/ar.js في المجلد نفسه');

// 5أ) بعد التحديث: النسخة الصغيرة القديمة assets/vendor/tinymce (من الإصدار السابق، نفس tinymce.min.js) + نسخة المشروع الجديدة
fresh();
foreach (['tinymce.min.js', 'langs/ar.js', 'plugins/lists/plugin.min.js'] as $f) {
    put("$SITE/assets/vendor/tinymce/$f", file_get_contents("$REAL/$f"));
}
$t = locate();
check(($t['path'] ?? '') === "$B/tinymce.min.js" && ($t['bundled'] ?? null) === true && ($t['plugins'] ?? []) === ['image', 'lists'],
    'النسخة القديمة الصغيرة المطابقة لا تغلب نسخة المشروع الجديدة (كل الإضافات)', var_export($t['path'] ?? null, true));

// 5ب) نسخة احتياطية مطابقة لنسخة المشروع لا تغلب ملفاتك أبداً
fresh();
rename("$SITE/$B", "$SITE/assets/vendor/tinymce.bak");
put("$SITE/$B/tinymce.min.js", "/* TinyMCE version 7.9.3 */\n");
put("$SITE/$B/langs/ar.js", file_get_contents("$REAL/langs/ar.js"));
$t = locate();
check(($t['path'] ?? '') === "$B/tinymce.min.js" && ($t['bundled'] ?? null) === false, 'نسخة احتياطية assets/vendor/tinymce.bak + ملفاتك مكان ملفات المشروع ← ملفاتك', var_export($t['path'] ?? null, true));
fresh();
put("$SITE/tinymce_backup/tinymce.min.js", file_get_contents("$REAL/tinymce.min.js"));
$t = locate();
check(($t['path'] ?? '') === "$B/tinymce.min.js" && ($t['bundled'] ?? null) === true, 'نسخة احتياطية مطابقة في مجلد الموقع ← نسخة المشروع في مكانها الأصلي', var_export($t['path'] ?? null, true));
user_copy("$SITE/js/tinymce");
check((locate()['path'] ?? '') === 'js/tinymce/tinymce.min.js', 'نسختك تغلب نسخة احتياطية مطابقة أسبق منها في الترتيب');
fresh();
rrmdir("$SITE/$B");
put("$SITE/tinymce/tinymce.min.js", file_get_contents("$REAL/tinymce.min.js"));
$t = locate();
check(($t['path'] ?? '') === 'tinymce/tinymce.min.js' && ($t['bundled'] ?? null) === true, 'نسخة مطابقة وحدها خارج مكانها الأصلي ← تُستعمل', var_export($t, true));

// 6) الأولوية
fresh();
user_copy("$SITE/$B/tinymce/js/tinymce");
user_copy("$SITE/tinymce/js/tinymce");
check((locate()['path'] ?? '') === 'tinymce/js/tinymce/tinymce.min.js', 'الأولوية: مجلد الموقع/tinymce قبل نسخة داخل مجلد نسخة المشروع');
user_copy("$SITE/editors/my/js/tinymce");
check((locate('editors/my')['path'] ?? '') === 'editors/my/js/tinymce/tinymce.min.js', 'الأولوية: المسار في config.php قبل كل شيء');
check((locate('editors\\my\\')['path'] ?? '') === 'editors/my/js/tinymce/tinymce.min.js', 'config.php بشرطات ويندوز \\ وشرطة في الآخر');
check((locate('/editors/my/js/tinymce/')['path'] ?? '') === 'editors/my/js/tinymce/tinymce.min.js', 'config.php: المجلد الذي فيه tinymce.min.js مباشرة');
check((locate('not/there')['path'] ?? '') === 'tinymce/js/tinymce/tinymce.min.js', 'config.php بمسار غير موجود ← البحث التلقائي');
fresh();
user_copy("$TMP/outside/tinymce");
check((locate('../../outside/tinymce')['path'] ?? '') === "$B/tinymce.min.js", 'config.php بـ .. للخروج من مجلد الموقع ← مرفوض');
foreach (['uploads/tinymce', 'Uploads\\tinymce', 'storage/tinymce', 'app/tinymce', 'database/tinymce'] as $bad) {
    user_copy("$SITE/" . strtolower(str_replace('\\', '/', $bad)));
    check((locate($bad)['path'] ?? '') === "$B/tinymce.min.js", "config.php يشير إلى $bad ← مرفوض");
}

// 7) مجلد tinymce بجانب مجلد الموقع (htdocs/tinymce في XAMPP)
fresh();
user_copy("$HT/tinymce/js/tinymce");
$t = locate();
expect_user('htdocs/tinymce بجانب الموقع', '../tinymce/js/tinymce/tinymce.min.js', $t);
check(preg_match('#^/tinymce/js/tinymce/tinymce\.min\.js\?v=\d+$#', $t['url'] ?? '') === 1 && ($t['base'] ?? '') === '/tinymce/js/tinymce', 'htdocs/tinymce: الرابط من جذر الخادم', var_export($t, true));
check((locate('', $SITE)['path'] ?? '') === "$B/tinymce.min.js", 'جذر الخادم هو مجلد الموقع (VirtualHost) ← لا نستعمل ما خارجه');
check((locate('', '')['path'] ?? '') === "$B/tinymce.min.js", 'جذر الخادم مجهول ← لا نستعمل ما خارج الموقع');
user_copy("$SITE/tinymce/js/tinymce");
check((locate()['path'] ?? '') === 'tinymce/js/tinymce/tinymce.min.js', 'الأولوية: داخل مجلد الموقع قبل بجانبه');

// 8) حالات خاصة
fresh();
user_copy("$SITE/tinymce/js/tinymce", 'tinymce.js');
$t = locate();
check(($t['path'] ?? '') === 'tinymce/js/tinymce/tinymce.js' && ($t['suffix'] ?? 'x') === '', 'tinymce.js فقط (نسخة التطوير) ← suffix فارغ', var_export($t, true));
put("$SITE/tinymce/js/tinymce/tinymce.min.js");
check((locate()['suffix'] ?? '') === '.min', 'tinymce.min.js و tinymce.js معاً ← المصغَّر');
fresh();
user_copy("$SITE/tinymce 7/js/tinymce");
$t = locate();
check(($t['path'] ?? '') === 'tinymce 7/js/tinymce/tinymce.min.js' && strpos($t['url'] ?? '', '/dou-setif/tinymce%207/js/tinymce/tinymce.min.js?v=') === 0, 'اسم مجلد فيه مسافة ← رابط مُرمَّز %20', $t['url'] ?? '');
fresh();
user_copy("$SITE/tinymce/a/b/c/d/e");
check((locate()['path'] ?? '') === "$B/tinymce.min.js", 'أعمق من 4 مستويات ← لا يُبحث فيه');
fresh();
put("$SITE/tinymce/README.md", 'empty');
user_copy("$SITE/assets/js/tinymce");
check((locate()['path'] ?? '') === 'assets/js/tinymce/tinymce.min.js', 'مجلد tinymce فارغ يُتجاوز إلى النسخة التالية');
fresh();
user_copy("$SITE/uploads/tinymce");
user_copy("$SITE/app/tinymce");
user_copy("$SITE/storage/tinymce");
check((locate()['path'] ?? '') === "$B/tinymce.min.js", 'لا بحث في uploads/ و app/ و storage/');
fresh();
user_copy("$SITE/tinymce/js/tinymce");
$t = admin_tinymce_locate(str_replace('/', '\\', $SITE) . '\\', '/dou-setif/', str_replace('/', '\\', $HT), '');
check(($t['path'] ?? '') === 'tinymce/js/tinymce/tinymce.min.js' && strpos($t['url'] ?? '', '/dou-setif/tinymce/') === 0, 'مسارات بشرطات ويندوز (C:\\xampp\\htdocs\\...) ', var_export($t, true));

rrmdir($TMP);
echo $fail ? "TinyMCE: $fail فشل، $pass نجح\n" : "TinyMCE: كل الفحوص ناجحة ($pass)\n";
exit($fail ? 1 : 0);
