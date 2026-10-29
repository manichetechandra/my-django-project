<?php
/**
 * تهيئة التطبيق: الإعدادات، معالجة الأخطاء، الجلسة، تحميل الملفات.
 */

if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('هذا الموقع يتطلب PHP 8.0 أو أحدث. الإصدار الحالي: ' . PHP_VERSION);
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');

$GLOBALS['__config'] = require BASE_PATH . '/config.php';

date_default_timezone_set($GLOBALS['__config']['app']['timezone'] ?? 'Africa/Algiers');
mb_internal_encoding('UTF-8');

require APP_PATH . '/core/DB.php';
require APP_PATH . '/core/Model.php';
require APP_PATH . '/core/Page.php';
require APP_PATH . '/core/Router.php';
require APP_PATH . '/core/View.php';
require APP_PATH . '/core/Auth.php';
require APP_PATH . '/core/Form.php';
require APP_PATH . '/core/Upload.php';
require APP_PATH . '/core/Translator.php';
require APP_PATH . '/core/XlsxWriter.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/i18n.php';

require APP_PATH . '/models/homepage.php';
require APP_PATH . '/models/content.php';
require APP_PATH . '/models/students.php';

require APP_PATH . '/forms.php';

foreach (glob(APP_PATH . '/controllers/*.php') as $controller) {
    require $controller;
}

// ------------------------------------------------------------------ الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', is_debug() ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(STORAGE_PATH . '/logs') && is_writable(STORAGE_PATH . '/logs')) {
    ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');
}

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // نحول التحذيرات إلى استثناءات في وضع التطوير لاكتشاف الأخطاء مبكراً
    if (is_debug() && !in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    error_log("PHP [$severity] $message in $file:$line");
    return true;
});

set_exception_handler(function (Throwable $e) {
    handle_exception($e);
});

function handle_exception(Throwable $e): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if ($e instanceof HttpException) {
        $code = $e->getCode() ?: 500;
        if (is_ajax() || str_starts_with(request_path(), '/students/api/')) {
            json_response(['success' => false, 'error' => $e->getMessage() ?: 'Error ' . $code], $code);
        }
        try {
            render('errors/' . (in_array($code, [403, 404, 405], true) ? $code : 404), ['message' => $e->getMessage()], $code);
        } catch (Throwable $inner) {
            http_response_code($code);
            echo '<h1>' . $code . '</h1>';
        }
        return;
    }
    error_log((string) $e);
    http_response_code(500);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string) $e . "\n");
        return;
    }
    $debug = is_debug();
    try {
        echo View::render('errors/500', ['exception' => $debug ? $e : null]);
    } catch (Throwable $inner) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1 dir="rtl">حدث خطأ في الخادم</h1>';
        if ($debug) {
            echo '<pre dir="ltr">' . e((string) $e) . '</pre>';
        }
    }
}

// ------------------------------------------------------------------ الجلسة
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $sessionDir = STORAGE_PATH . '/sessions';
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    ini_set('session.gc_maxlifetime', '1209600'); // أسبوعان (مثل Django)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('dou_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (base_url() ?: '') . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
