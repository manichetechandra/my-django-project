<?php
/**
 * نقطة الدخول الوحيدة للموقع (Front Controller).
 * كل الطلبات تمر من هنا عبر .htaccess ثم تُوجَّه حسب app/routes.php
 */

// خادم PHP المدمج (php -S): تقديم الملفات الثابتة مباشرة
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $rel = str_replace('\\', '/', substr(realpath($file) ?: '', strlen(__DIR__)));
    if (is_file($file) && preg_match('#^/(assets|uploads)/#', $rel) && !preg_match('/\.php$/i', $file)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

$router = new Router();
$GLOBALS['__router'] = $router;
require APP_PATH . '/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path(); // يحدد لغة الطلب أيضاً: /fr/news/ => lang() = 'fr' و $path = '/news/'

// لوحة الإدارة عربية دائماً: /fr/admin/... => /admin/...
// والعربية بلا بادئة:        /ar/news/    => /news/
$qs = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
if (lang() !== default_lang() && preg_match('#^/admin(/|$)#', $path)) {
    redirect(base_url() . route_prefix() . $path . $qs, 301);
}
if (preg_match('#^/' . default_lang() . '(/.*)?$#', $path, $m)) {
    redirect(base_url() . route_prefix() . ($m[1] ?? '/') . $qs, 301);
}

// حماية CSRF لكل طلبات POST (مثل CsrfViewMiddleware)
if ($method === 'POST' && !verify_csrf()) {
    if (is_ajax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_response(['success' => false, 'error' => 'انتهت صلاحية الجلسة. أعد تحميل الصفحة.'], 403);
    }
    render('errors/403_csrf', [], 403);
    exit;
}

// إحصائيات الزوار (مثل VisitorTrackingMiddleware)
if (config('app.track_visits', true) && $method === 'GET') {
    track_visit($path);
}

$router->dispatch($method, $path);
