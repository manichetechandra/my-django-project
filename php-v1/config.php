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

        // عرض تفاصيل الأخطاء: null = تلقائي (مفعّل على localhost فقط)، true أو false لفرض الإعداد
        // ⚠️ ضع false في الاستضافة الحقيقية
        'debug' => $env('APP_DEBUG') === null ? null : $env('APP_DEBUG') === '1',

        // رابط الموقع الأساسي: اتركه null ليُكتشف تلقائياً (يعمل حتى داخل مجلد فرعي مثل localhost/dou-setif)
        'base_url' => $env('BASE_URL'),

        // روابط نظيفة /news/ تتطلب mod_rewrite (ملف .htaccess). إن لم يكن متاحاً ضع false => /index.php/news/
        'pretty_urls' => $env('PRETTY_URLS', '1') === '1',

        // تسجيل زيارات الموقع (إحصائيات الزوار)
        'track_visits' => true,
    ],

    'uploads' => [
        'max_image_mb'    => 5,   // الصور (صور الطلاب، الأخبار...)
        'max_document_mb' => 10,  // وثائق الطلبات
        'max_file_mb'     => 20,  // ملفات مركز التحميلات
    ],
];
