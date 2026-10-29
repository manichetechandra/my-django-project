<?php
/**
 * تعدد اللغات: العربية لغة الموقع الأساسية، والفرنسية والإنجليزية ترجمات لها.
 *
 * الروابط:   /news/  بالعربية،  /fr/news/  بالفرنسية،  /en/news/  بالإنجليزية
 * النصوص:    تبقى بالعربية في القوالب، وترجمتها في app/lang/fr.php و app/lang/en.php
 *
 *   __('آخر الأخبار')                    النص بلغة الصفحة الحالية (خام: للكود والرسائل)
 *   t('آخر الأخبار')                     نفسه مهرَّباً للعرض في القوالب:  <?= t('آخر الأخبار') ?>
 *   __('مرحباً {name}', ['name' => $n])  مع متغيرات
 *   js_t('هل أنت متأكد؟')                نص جاهز داخل JavaScript (بعلامات التنصيص)
 *   lang()  is_rtl()  text_dir()  lang_url('fr')
 *
 * النص الذي لا ترجمة له يظهر بالعربية كما هو (وفي وضع التطوير يُسجَّل في storage/logs/i18n-missing.log).
 */

/** اللغات المتاحة: الرمز => الاسم بلغته */
function languages(): array
{
    return config('i18n.languages', ['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English']);
}

/** اللغة الأساسية (روابطها بلا بادئة) */
function default_lang(): string
{
    return config('i18n.default', 'ar');
}

/** لغة الصفحة الحالية */
function lang(): string
{
    return $GLOBALS['__lang'] ?? default_lang();
}

function set_lang(string $code): void
{
    if (isset(languages()[$code])) {
        $GLOBALS['__lang'] = $code;
    }
}

/** هل اللغة تُكتب من اليمين إلى اليسار؟ */
function is_rtl(?string $code = null): bool
{
    return in_array($code ?? lang(), ['ar', 'fa', 'he', 'ur'], true);
}

/** قيمة الخاصية dir في HTML */
function text_dir(?string $code = null): string
{
    return is_rtl($code) ? 'rtl' : 'ltr';
}

/** بادئة اللغة في الروابط: '' للغة الأساسية، و '/fr' للفرنسية... */
function lang_prefix(?string $code = null): string
{
    $code = $code ?? lang();
    return $code === default_lang() ? '' : '/' . $code;
}

/**
 * يقرأ بادئة اللغة من بداية المسار ويحذفها:  /fr/news/  ← اللغة fr والمسار /news/
 * (تستدعيها request_path() مرة واحدة لكل طلب)
 */
function strip_lang_prefix(string $path): string
{
    if (preg_match('#^/([a-z]{2})(/.*)?$#', $path, $m) && $m[1] !== default_lang() && isset(languages()[$m[1]])) {
        set_lang($m[1]);
        return $m[2] ?? '/';
    }
    set_lang(default_lang());
    return $path;
}

/** ترجمات لغة: مصفوفة النص العربي => الترجمة */
function translations(string $code): array
{
    static $cache = [];
    if (!isset($cache[$code])) {
        $file = APP_PATH . '/lang/' . basename($code) . '.php';
        $cache[$code] = is_file($file) ? require $file : [];
    }
    return $cache[$code];
}

/** ترجمة نص (خام). $code لفرض لغة معينة بدل لغة الصفحة */
function __(string $text, array $params = [], ?string $code = null): string
{
    $code = $code ?? lang();
    if ($code !== default_lang() && $text !== '') {
        $map = translations($code);
        if (isset($map[$text])) {
            $text = $map[$text];
        } else {
            i18n_missing($text, $code);
        }
    }
    if ($params) {
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs['{' . $k . '}'] = (string) $v;
        }
        $text = strtr($text, $pairs);
    }
    return $text;
}

/**
 * ترجمة حسب السياق: نفس النص العربي لكن بترجمة مختلفة في مكان معين.
 * مثال: tx('nav', 'الإقامات الجامعية') ← «Résidences» في القائمة (والمفتاح في ملف اللغة 'nav|الإقامات الجامعية')
 * إن لم توجد ترجمة السياق نستعمل الترجمة العادية. النتيجة مهرَّبة للعرض.
 */
function tx(string $context, string $text, array $params = []): string
{
    $code = lang();
    if ($code !== default_lang() && isset(translations($code)[$context . '|' . $text])) {
        return e(__($context . '|' . $text, $params));
    }
    return t($text, $params);
}

/** ترجمة مهرَّبة للعرض داخل HTML */
function t(string $text, array $params = []): string
{
    return e(__($text, $params));
}

/** ترجمة جاهزة داخل JavaScript:  confirm(<?= js_t('هل أنت متأكد؟') ?>) */
function js_t(string $text, array $params = []): string
{
    return json_encode(__($text, $params), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/** تسجيل النصوص الناقصة في وضع التطوير (لإكمال ملفات الترجمة) */
function i18n_missing(string $text, string $code): void
{
    static $seen = [];
    if (!is_debug() || isset($seen[$code][$text]) || !preg_match('/\p{Arabic}/u', $text)) {
        return;
    }
    $seen[$code][$text] = true;
    $dir = STORAGE_PATH . '/logs';
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($dir . '/i18n-missing.log', $code . "\t" . str_replace(["\r", "\n"], ['\\r', '\\n'], $text) . "\n", FILE_APPEND);
    }
}

/** رابط الصفحة الحالية بلغة أخرى (لقائمة اللغات و hreflang) */
function lang_url(string $code, bool $absolute = false): string
{
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $path = base_url() . route_prefix() . lang_prefix($code) . request_path() . ($qs !== '' ? '?' . $qs : '');
    if (!$absolute) {
        return $path;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}

/** لغة تنسيق الأرقام والتواريخ في JavaScript (toLocaleString) */
function intl_locale(string $arabic = 'ar-DZ'): string
{
    return ['fr' => 'fr-FR', 'en' => 'en-GB'][lang()] ?? $arabic;
}

/** الحقل المترجم من صف مصفوفة (للاستعلامات التي لا ترجع كائنات Model) */
function tr_field(array $row, string $field): string
{
    $l = lang();
    if ($l !== default_lang() && !empty($row[$field . '_' . $l])) {
        return (string) $row[$field . '_' . $l];
    }
    return (string) ($row[$field] ?? '');
}
