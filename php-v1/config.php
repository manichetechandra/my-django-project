<?php
/**
 * ================================================================
 *  إعدادات الموقع — عدّل هذا الملف حسب جهازك أو استضافتك
 * ================================================================
 *  XAMPP (الافتراضي): المستخدم root بدون كلمة مرور.
 *  في الاستضافة: ضع بيانات قاعدة البيانات التي تعطيك إياها لوحة التحكم (cPanel).
 *  يمكن أيضاً استعمال متغيرات البيئة DB_HOST, DB_NAME, DB_USER, DB_PASSWORD...
 */

$env = function (string $key, $default = null) {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

return [
    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => $env('DB_PORT', '3306'),
        'name'     => $env('DB_NAME', 'dou_setif'),
        'user'     => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'app' => [
        'name'     => 'مديرية الخدمات الجامعية سطيف',
        'timezone' => 'Africa/Algiers',

        // عرض تفاصيل الأخطاء: null = تلقائي (مفعّل فقط عند فتح الموقع من نفس الجهاز عبر http://localhost)
        // true أو false لفرض الإعداد. ⚠️ ضع false في الاستضافة الحقيقية (أو APP_DEBUG=0)
        'debug' => $env('APP_DEBUG') === null ? null : $env('APP_DEBUG') === '1',

        // رابط الموقع الأساسي: اتركه null ليُكتشف تلقائياً (يعمل حتى داخل مجلد فرعي مثل localhost/dou-setif)
        'base_url' => $env('BASE_URL'),

        // روابط نظيفة /news/ تتطلب mod_rewrite (ملف .htaccess). إن لم يكن متاحاً ضع false => /index.php/news/
        'pretty_urls' => $env('PRETTY_URLS', '1') === '1',

        // تسجيل زيارات الموقع (إحصائيات الزوار)
        'track_visits' => true,

        // خوادم وسيطة موثوقة (Cloudflare، Nginx أمام PHP...) — فقط منها نقبل ترويسة X-Forwarded-For
        // لمعرفة عنوان الزائر الحقيقي. اتركها فارغة إن لم يكن هناك خادم وسيط (XAMPP، استضافة عادية).
        // مثال: ['127.0.0.1', '10.0.0.0/8'] أو متغير البيئة TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
        'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) $env('TRUSTED_PROXIES', ''))))),
    ],

    // الحماية (انظر SECURITY.md)
    'security' => [
        // محاولات الدخول الفاشلة المسموحة خلال login_window ثانية (فضاء الطالب + لوحة الإدارة)
        'login_window'       => 900, // 15 دقيقة
        'login_max_per_user' => 5,   // لنفس اسم المستخدم من نفس العنوان
        'login_max_per_ip'   => 30,  // من نفس العنوان لكل الحسابات (أكبر لأن طلبة الإقامة قد يتشاركون عنواناً واحداً)
        // محاولات إنشاء حساب طالب من نفس العنوان في الساعة، ورسائل «اتصل بنا» كل 10 دقائق
        'register_max'       => 20,
        'contact_max'        => 5,
        // منع عرض الموقع داخل إطار في موقع آخر: SAMEORIGIN (الافتراضي) أو DENY أو off
        'frame_options'      => $env('FRAME_OPTIONS', 'SAMEORIGIN'),
        // HSTS: بعد تفعيل HTTPS نهائياً ضع مثلاً 31536000 (سنة) ليفرض المتصفح HTTPS دائماً
        'hsts_seconds'       => (int) $env('HSTS_SECONDS', '0'),
        // «نسيت كلمة مرور المدير؟» من المتصفح (/admin/recover/): تتطلب إنشاء ملف داخل storage/ لإثبات ملكية الموقع.
        // ضع false لتعطيلها (تبقى أداة سطر الأوامر database/tools/create_admin.php)
        'web_recovery'       => filter_var($env('WEB_RECOVERY', '1'), FILTER_VALIDATE_BOOLEAN),
    ],

    'uploads' => [
        'max_image_mb'    => 5,   // الصور (صور الطلاب، الأخبار...)
        'max_document_mb' => 10,  // وثائق الطلبات
        'max_file_mb'     => 20,  // ملفات مركز التحميلات
    ],
];
