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

    // اللغات: العربية أساسية على الروابط العادية، والبقية تحت /fr/ و /en/
    'i18n' => [
        'default'   => 'ar',
        'languages' => ['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English'],
    ],

    // الترجمة التلقائية في لوحة الإدارة (زر «ترجمة تلقائية» + ترجمة الحقول الفارغة عند الحفظ)
    'translation' => [
        // gemini (الأفضل) أو mymemory (بلا تسجيل، محدود) أو none (يدوياً فقط)
        'provider'       => $env('TRANSLATE_PROVIDER', 'gemini'),
        // مفتاح مجاني من https://aistudio.google.com/apikey — بدونه يُستعمل البديل
        'gemini_key'     => $env('GEMINI_API_KEY', ''),
        'gemini_model'   => $env('GEMINI_MODEL', 'gemini-2.5-flash'),
        // البديل عند غياب المفتاح أو فشل الخدمة الأولى (none لتعطيله)
        'fallback'       => $env('TRANSLATE_FALLBACK', 'mymemory'),
        // اختياري: بريدك يرفع حصة MyMemory اليومية من 5 آلاف إلى 50 ألف حرف
        'mymemory_email' => $env('MYMEMORY_EMAIL', ''),
        // ترجمة الحقول الفرنسية والإنجليزية الفارغة تلقائياً عند حفظ السجل
        'auto_on_save'   => true,
        'timeout'        => 45,
    ],
];
