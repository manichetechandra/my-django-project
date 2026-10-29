<?php
/**
 * دوال مساعدة عامة (روابط، أمان، رسائل، تنسيق التواريخ والنصوص...)
 * أغلبها مكافئ لفلاتر ووسوم قوالب Django.
 */

// ====================================================================== الإعدادات

function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function is_debug(): bool
{
    static $debug = null;
    if ($debug === null) {
        $d = config('app.debug');
        if ($d === null) {
            // تلقائي: مفعّل على جهازك المحلي فقط
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $debug = PHP_SAPI === 'cli' || in_array($ip, ['127.0.0.1', '::1'], true);
        } else {
            $debug = (bool) $d;
        }
    }
    return $debug;
}

// ====================================================================== الروابط

/** المسار الأساسي للموقع (يعمل حتى لو كان داخل مجلد فرعي مثل /dou-setif) */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $configured = config('app.base_url');
        if ($configured) {
            $base = rtrim($configured, '/');
        } else {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
        }
    }
    return $base;
}

/** "/index.php" إذا لم يكن mod_rewrite متاحاً */
function route_prefix(): string
{
    return config('app.pretty_urls', true) ? '' : '/index.php';
}

/** رابط مسار باسمه: url('news:news_detail', $slug) */
function url(string $name, ...$params): string
{
    return $GLOBALS['__router']->url($name, $params);
}

/** رابط ملف ثابت داخل assets/ */
function asset(string $path): string
{
    // توافق مع مسارات static/ القديمة في Django
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/assets/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return base_url() . '/assets/' . $path . $v;
}

/** رابط ملف مرفوع داخل uploads/ (مع صورة بديلة إن كان الملف مفقوداً) */
function media_url($path): string
{
    $path = (string) $path;
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if (!is_file(UPLOAD_PATH . '/' . $path)) {
        return base_url() . '/assets/images/placeholder.svg';
    }
    return base_url() . '/uploads/' . implode('/', array_map('rawurlencode', explode('/', $path)));
}

/** الروابط المخزنة في قاعدة البيانات مثل "/students/dashboard/" تُضاف لها بادئة الموقع */
function link_url($link): string
{
    $link = (string) $link;
    if ($link === '' || preg_match('#^(https?:|mailto:|tel:|\#)#i', $link)) {
        return $link;
    }
    if ($link[0] === '/') {
        $prefix = preg_match('#^/(admin|uploads|assets)(/|$)#', $link) ? '' : lang_prefix();
        return base_url() . route_prefix() . $prefix . $link;
    }
    return $link;
}

/** المسار الحالي داخل الموقع (بدون المجلد الفرعي ولا بادئة اللغة) مثل /news/ */
function request_path(): string
{
    static $path = null;
    if ($path === null) {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $p = parse_url($uri, PHP_URL_PATH) ?: '/';
        $p = rawurldecode($p);
        $base = base_url();
        if ($base !== '' && strpos($p, $base) === 0) {
            $p = substr($p, strlen($base));
        }
        if (strpos($p, '/index.php') === 0) {
            $p = substr($p, strlen('/index.php'));
        }
        // /fr/news/ => اللغة fr والمسار /news/ (انظر app/i18n.php)
        $path = strip_lang_prefix('/' . ltrim($p, '/'));
    }
    return $path;
}

function current_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
}

function redirect(string $to, int $status = 302): void
{
    header('Location: ' . $to, true, $status);
    exit;
}

function redirect_to(string $routeName, ...$params): void
{
    redirect(url($routeName, ...$params));
}

/**
 * رابط "next" بعد تسجيل الدخول: يُقبل فقط مسار داخلي مثل /students/notifications/
 * (لمنع إعادة التوجيه لمواقع خارجية) ويُضاف له مجلد الموقع.
 */
function safe_next(?string $next, string $fallback): string
{
    $next = (string) $next;
    if ($next !== '' && $next[0] === '/' && (strlen($next) < 2 || ($next[1] !== '/' && $next[1] !== '\\'))) {
        $base = base_url() . route_prefix();
        // إن كان الرابط يحتوي مسبقاً على مجلد الموقع فلا نكرره
        if ($base !== '' && strpos($next, $base . '/') === 0) {
            return $next;
        }
        return $base . $next;
    }
    return $fallback;
}

// ====================================================================== الطلب والاستجابة

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function query(string $key, $default = '')
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function client_ip(): string
{
    $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($fwd !== '') {
        return trim(explode(',', $fwd)[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** يمنع تخزين الصفحة في ذاكرة المتصفح (مثل @never_cache) */
function never_cache(): void
{
    header('Cache-Control: no-cache, no-store, must-revalidate, private, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

class HttpException extends Exception
{
}

function abort(int $code, string $message = ''): void
{
    throw new HttpException($message, $code);
}

/** عرض قالب */
function render(string $view, array $context = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo View::render($view, array_merge(template_globals(), $context));
}

/** المتغيرات المتاحة في كل القوالب (مثل context processors) */
function template_globals(): array
{
    return [
        'user'            => current_user(),
        'student_profile' => current_student(),
        'messages'        => get_flashes(),
        'current_year'    => date('Y'),
    ];
}

// ====================================================================== الأمان

/** تهريب HTML (بديل الـ autoescape في Django) */
function e($value): string
{
    if ($value === null || $value === false) {
        return '';
    }
    if ($value === true) {
        return 'True';
    }
    if (is_array($value)) {
        $value = implode(', ', array_map('strval', $value));
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** حقل CSRF مخفي — نفس اسم Django ليعمل مع الـ JavaScript الموجود */
function csrf_field(): string
{
    return '<input type="hidden" name="csrfmiddlewaretoken" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrfmiddlewaretoken'] ?? $_POST['_token'] ?? $_SERVER['HTTP_X_CSRFTOKEN'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && $token !== '' && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

// ====================================================================== الرسائل (django.contrib.messages)

function flash(string $tag, string $message): void
{
    $_SESSION['_flash'][] = ['tags' => $tag, 'message' => $message];
}

function get_flashes(): array
{
    $m = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $m;
}

/** صنف Bootstrap المناسب لنوع الرسالة (error => danger) */
function alert_class(string $tag): string
{
    return $tag === 'error' ? 'danger' : ($tag === 'debug' ? 'info' : $tag);
}

// ====================================================================== المستخدم الحالي

function current_user(): ?User
{
    return Auth::user();
}

function current_student(): ?StudentProfile
{
    static $cache = [];
    $u = Auth::user();
    if (!$u) {
        return null;
    }
    if (!array_key_exists($u['id'], $cache)) {
        $cache[$u['id']] = StudentProfile::firstWhere('user_id = ?', [(int) $u['id']]);
    }
    return $cache[$u['id']];
}

function is_authenticated(): bool
{
    return Auth::user() !== null;
}

function is_staff(): bool
{
    $u = Auth::user();
    return $u !== null && $u['is_active'] && ($u['is_staff'] || $u['is_superuser']);
}

function is_superuser(): bool
{
    $u = Auth::user();
    return $u !== null && $u['is_active'] && $u['is_superuser'];
}

/** مثل @login_required(login_url='students:login') */
function require_login(): User
{
    $u = Auth::user();
    if (!$u) {
        $next = lang_prefix() . request_path() . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
        redirect(url('students:login') . '?next=' . rawurlencode($next));
    }
    return $u;
}

/** مثل @staff_required في students/decorators.py */
function require_staff(): User
{
    $u = Auth::user();
    if (!$u) {
        flash('warning', __('يجب تسجيل الدخول أولاً'));
        redirect(url('students:login') . '?next=' . rawurlencode(lang_prefix() . request_path()));
    }
    if (!is_staff()) {
        flash('error', __('ليس لديك صلاحية للوصول لهذه الصفحة. هذه الصفحة للإداريين فقط.'));
        redirect_to('homepage:index');
    }
    return $u;
}

// ====================================================================== التواريخ

function to_datetime($value): ?DateTimeImmutable
{
    if ($value instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($value);
    }
    if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return null;
    }
    try {
        return new DateTimeImmutable((string) $value);
    } catch (Exception $e) {
        return null;
    }
}

/** الوقت الحالي بصيغة MySQL */
function now(): string
{
    return date('Y-m-d H:i:s');
}

const AR_MONTHS = [1 => 'جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
const AR_DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

/** أسماء الأشهر والأيام بكل لغة (العربية كما تُكتب في الجزائر) */
const MONTH_NAMES = [
    'ar' => AR_MONTHS,
    'fr' => [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
    'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
];
const DAY_NAMES = [
    'ar' => AR_DAYS,
    'fr' => ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
    'en' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
];

/** فلتر date في Django: format_date($news['published_date'], 'd/m/Y') — مع أسماء الأشهر والأيام بلغة الصفحة */
function format_date($value, string $format = 'd/m/Y'): string
{
    $dt = to_datetime($value);
    if (!$dt) {
        return '';
    }
    $out = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $ch = $format[$i];
        if ($ch === '\\' && $i + 1 < $len) {
            $out .= $format[++$i];
            continue;
        }
        switch ($ch) {
            case 'F':
            case 'N':
            case 'M':
            case 'E':
                $out .= (MONTH_NAMES[lang()] ?? AR_MONTHS)[(int) $dt->format('n')];
                break;
            case 'l':
            case 'D':
                $out .= (DAY_NAMES[lang()] ?? AR_DAYS)[(int) $dt->format('w')];
                break;
            case 'A':
            case 'a':
                $out .= is_rtl() ? ($dt->format('H') < 12 ? 'ص' : 'م') : $dt->format('A');
                break;
            default:
                $out .= preg_match('/[a-zA-Z]/', $ch) ? $dt->format($ch) : $ch;
        }
    }
    return $out;
}

/** صيغة الجمع العربية: ar_plural(3, ['يوم','يومان','أيام']) => "3 أيام" */
function ar_plural(int $n, array $forms): string
{
    [$one, $two, $few] = $forms;
    if ($n === 1) {
        return $one;
    }
    if ($n === 2) {
        return $two;
    }
    if ($n >= 3 && $n <= 10) {
        return $n . ' ' . $few;
    }
    return $n . ' ' . $one;
}

/** فلتر timesince في Django */
function timesince($value, $now = null): string
{
    $dt = to_datetime($value);
    if (!$dt) {
        return '';
    }
    $now = $now ? to_datetime($now) : new DateTimeImmutable();
    $seconds = max(0, $now->getTimestamp() - $dt->getTimestamp());
    $names = [
        'ar' => [['سنة', 'سنتان', 'سنوات'], ['شهر', 'شهران', 'أشهر'], ['أسبوع', 'أسبوعان', 'أسابيع'], ['يوم', 'يومان', 'أيام'], ['ساعة', 'ساعتان', 'ساعات'], ['دقيقة', 'دقيقتان', 'دقائق']],
        'fr' => [['an', 'ans'], ['mois', 'mois'], ['semaine', 'semaines'], ['jour', 'jours'], ['heure', 'heures'], ['minute', 'minutes']],
        'en' => [['year', 'years'], ['month', 'months'], ['week', 'weeks'], ['day', 'days'], ['hour', 'hours'], ['minute', 'minutes']],
    ];
    $forms = $names[lang()] ?? $names['ar'];
    $sep = is_rtl() ? '، ' : ', ';
    $sizes = [365 * 86400, 30 * 86400, 7 * 86400, 86400, 3600, 60];
    foreach ($sizes as $idx => $size) {
        $count = intdiv($seconds, $size);
        if ($count > 0) {
            $result = plural_unit($count, $forms[$idx]);
            if (isset($sizes[$idx + 1])) {
                $count2 = intdiv($seconds - $count * $size, $sizes[$idx + 1]);
                if ($count2 > 0) {
                    $result .= $sep . plural_unit($count2, $forms[$idx + 1]);
                }
            }
            return $result;
        }
    }
    return lang() === 'en' ? '0 minutes' : (is_rtl() ? '0 دقيقة' : '0 minute');
}

/** عدد + وحدة بصيغة اللغة: العربية (مفرد، مثنى، جمع) أو الفرنسية والإنجليزية (مفرد، جمع) */
function plural_unit(int $n, array $forms): string
{
    if (count($forms) === 3) {
        return ar_plural($n, $forms);
    }
    return $n . ' ' . ($n === 1 || ($n === 0 && lang() === 'fr') ? $forms[0] : $forms[1]);
}

// ====================================================================== النصوص (فلاتر Django)

function truncatewords($value, int $count): string
{
    $words = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) <= $count) {
        return implode(' ', $words);
    }
    return implode(' ', array_slice($words, 0, $count)) . ' …';
}

function truncatechars($value, int $length): string
{
    $value = (string) $value;
    if (mb_strlen($value) <= $length) {
        return $value;
    }
    return rtrim(mb_substr($value, 0, max(0, $length - 1))) . '…';
}

/** فلتر linebreaks: فقرات <p> وأسطر <br> (مع التهريب) */
function linebreaks($value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
    $paras = preg_split("/\n{2,}/", $value);
    $out = [];
    foreach ($paras as $p) {
        $out[] = '<p>' . str_replace("\n", '<br>', e(trim($p, "\n"))) . '</p>';
    }
    return implode("\n\n", $out);
}

/** فلتر slice: slice_($list, ':6') أو slice_($text, '1:') */
function slice_($value, string $spec)
{
    [$start, $end] = array_pad(explode(':', $spec, 2), 2, null);
    $start = ($start === '' || $start === null) ? 0 : (int) $start;
    if (is_string($value)) {
        $len = mb_strlen($value);
        $endPos = ($end === '' || $end === null) ? $len : (int) $end;
        if ($endPos < 0) {
            $endPos = $len + $endPos;
        }
        return mb_substr($value, $start, max(0, $endPos - $start));
    }
    $arr = $value instanceof Traversable ? iterator_to_array($value, false) : array_values((array) $value);
    $len = count($arr);
    $endPos = ($end === '' || $end === null) ? $len : (int) $end;
    if ($endPos < 0) {
        $endPos = $len + $endPos;
    }
    return array_slice($arr, $start, max(0, $endPos - $start));
}

/** فلتر length */
function length($value): int
{
    if (is_string($value)) {
        return mb_strlen($value);
    }
    if (is_array($value) || $value instanceof Countable) {
        return count($value);
    }
    return 0;
}

/** عامل in في قوالب Django */
function in_($needle, $haystack): bool
{
    if (is_string($haystack)) {
        return $needle !== null && $needle !== '' && mb_strpos($haystack, (string) $needle) !== false;
    }
    if (is_array($haystack)) {
        return in_array($needle, $haystack);
    }
    return false;
}

/** فلتر escapejs */
function escapejs($value): string
{
    $json = json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $json = substr($json, 1, -1);
    return str_replace('`', '\\u0060', $json);
}

/** تمرير بيانات PHP إلى JavaScript بأمان */
function json_js($value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/** حجم ملف مقروء */
function human_filesize($bytes): string
{
    $bytes = (float) $bytes;
    if ($bytes < 1024) {
        return ((int) $bytes) . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}

/**
 * توليد slug مطابق لـ prepopulated_fields في لوحة Django
 * (نفس جدول التحويل العربي في urlify.js) — مثال: "افتتاح المكتبة الجديدة" => "ftth-lmktb-lgdyd"
 */
function slugify($text): string
{
    static $map = [
        'أ' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'g', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd',
        'ذ' => 'th', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't',
        'ظ' => 'th', 'ع' => 'aa', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'k', 'ك' => 'k', 'ل' => 'l', 'م' => 'm',
        'ن' => 'n', 'ه' => 'h', 'و' => 'o', 'ي' => 'y',
    ];
    $text = strtr((string) $text, $map);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t !== false) {
            $text = $t;
        }
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return trim($text, '-');
}

/** slug فريد داخل جدول */
function unique_slug(string $table, string $source, ?int $ignoreId = null, string $column = 'slug'): string
{
    $base = slugify($source);
    if ($base === '') {
        $base = 'item-' . substr(bin2hex(random_bytes(4)), 0, 6);
    }
    $base = substr($base, 0, 190);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?" . ($ignoreId ? ' AND id <> ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        if ((int) DB::value($sql, $params) === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}
