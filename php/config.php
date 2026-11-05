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

    'uploads' => [
        'max_image_mb'    => 5,   // الصور (صور الطلاب، الأخبار...)
        'max_document_mb' => 10,  // وثائق الطلبات
        'max_file_mb'     => 20,  // ملفات مركز التحميلات
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
        // عناوين الخدمتين (لا تغيّرها إلا للاختبار بخادم محلي: dev/mock_translate.py)
        'gemini_endpoint'   => $env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'mymemory_endpoint' => $env('MYMEMORY_ENDPOINT', 'https://api.mymemory.translated.net/get'),
    ],

    // محرر النصوص المنسّقة في لوحة الإدارة (TinyMCE): يُبحث عنه تلقائياً (مجلد tinymce في مجلد الموقع، أو assets/vendor/tinymce...)
    // وإن وضعته في مكان آخر داخل مجلد الموقع اكتب مساره هنا، مثل 'editors/tinymce' (المجلد الذي فيه tinymce.min.js أو فوقه).
    // أسفل كل نموذج فيه محرر سطر يبيّن النسخة المستعملة ومسارها
    'editor' => [
        'tinymce' => $env('TINYMCE_PATH', ''),
    ],

    // مسابقات التوظيف والترشح الإلكتروني (انظر SECURITY.md — قسم «المسابقات»)
    'recruitment' => [
        // مفتاح تشفير معطيات المترشحين ووثائقهم: 64 حرفاً من 0-9 و a-f (ولّده بـ: php -r "echo bin2hex(random_bytes(32));")
        // اتركه فارغاً ليُنشأ تلقائياً عند أول ترشح في storage/private/recruitment.key
        // ⚠️ احتفظ بنسخة من المفتاح في مكان آمن: إن ضاع لا يمكن قراءة ملفات المترشحين أبداً (ولا حتى من المبرمج)
        'key'          => $env('RECRUITMENT_KEY', ''),
        'max_file_mb'  => 3,   // حجم كل وثيقة PDF
        'max_total_mb' => 15,  // مجموع وثائق الطلب الواحد
        // من نفس العنوان: محاولات الإرسال في الساعة، والطلبات المقبولة في اليوم (مقاهي الإنترنت يتشاركها عدة مترشحين)
        'apply_attempts_max' => 30,
        'apply_success_max'  => 20,
        // صفحة «متابعة طلب»: المحاولات كل 15 دقيقة من نفس العنوان، ولنفس رقم المتابعة من كل العناوين
        'track_max_per_ip'   => 10,
        'track_max_per_code' => 5,
        // الترشح الإلكتروني يتطلب HTTPS (إلا على جهازك http://localhost). ضع 0 للاختبار فقط
        'require_https' => $env('RECRUITMENT_REQUIRE_HTTPS', '1') === '1',
    ],
];
