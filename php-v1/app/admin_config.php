<?php
/**
 * إعدادات لوحة الإدارة (بديل ملفات admin.py في Django).
 *
 * كل عنصر = جدول يمكن إدارته من /admin/<المفتاح>/
 *   list     : الأعمدة في قائمة السجلات (list_display)
 *   links    : الأعمدة القابلة للنقر لفتح السجل (list_display_links)
 *   editable : أعمدة قابلة للتعديل مباشرة من القائمة (list_editable)
 *   filters  : الفلاتر الجانبية (list_filter)
 *   search   : حقول البحث (search_fields) — "fk_col>table.col" للبحث في جدول مرتبط
 *   actions  : إجراءات جماعية إضافية (actions)
 *   fields   : حقول نموذج الإضافة/التعديل
 *   inlines  : صور/ملفات مرتبطة تُدار داخل صفحة السجل (inlines)
 *
 * أنواع الحقول: text, textarea, email, url, int, decimal, bool, select, fk, date, datetime,
 *               image, file, slug, color, icon, password, readonly
 */

$bool = fn(string $label, bool $default = true) => ['type' => 'bool', 'label' => $label, 'default' => $default ? 1 : 0];
$order = fn(string $label = 'الترتيب') => ['type' => 'int', 'label' => $label, 'default' => 0, 'required' => false];
$created = ['type' => 'readonly', 'label' => 'تاريخ الإنشاء'];
$updated = ['type' => 'readonly', 'label' => 'آخر تحديث'];
$imageInline = fn(string $model, string $fk, string $dir) => [
    'model' => $model, 'fk' => $fk, 'label' => 'صور إضافية (المعرض)', 'file' => 'image', 'dir' => $dir,
    'kind' => 'image', 'text' => ['caption' => 'التعليق'], 'order' => 'order',
];

return [
    // ================================================================ الصفحة الرئيسية
    'sliders' => [
        'model' => Slider::class, 'group' => 'الصفحة الرئيسية', 'icon' => 'fa-picture-o',
        'label' => 'صورة سلايدر', 'plural' => 'السلايدر',
        'list' => ['image', 'title', 'order', 'is_active', 'created_at'], 'links' => ['title'],
        'editable' => ['order', 'is_active'], 'filters' => ['is_active', 'created_at'], 'search' => ['title', 'subtitle'],
        'order_by' => '`order`, created_at DESC',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200],
            'subtitle' => ['type' => 'text', 'label' => 'العنوان الفرعي', 'max' => 300, 'required' => false],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'slider', 'help' => 'المقاس المقترح: 1920×500 بكسل'],
            'link' => ['type' => 'text', 'label' => 'الرابط', 'max' => 255, 'required' => false, 'nullable' => true, 'help' => 'رابط كامل (https://...) أو داخلي مثل /news/'],
            'button_text' => ['type' => 'text', 'label' => 'نص الزر', 'max' => 50, 'required' => false],
            'order' => $order(),
            'is_active' => $bool('نشط'),
            'created_at' => $created,
        ],
    ],
    'statistics' => [
        'model' => Statistic::class, 'group' => 'الصفحة الرئيسية', 'icon' => 'fa-bar-chart',
        'label' => 'إحصائية', 'plural' => 'الإحصائيات',
        'list' => ['icon', 'number', 'label', 'order', 'is_active'], 'links' => ['label'], 'editable' => ['order', 'is_active'],
        'order_by' => '`order`',
        'fields' => [
            'icon' => ['type' => 'icon', 'label' => 'الأيقونة', 'max' => 50, 'help' => 'اسم أيقونة Font Awesome مثل fa-users'],
            'number' => ['type' => 'int', 'label' => 'الرقم'],
            'label' => ['type' => 'text', 'label' => 'التسمية', 'max' => 100],
            'order' => $order(),
            'is_active' => $bool('نشط'),
        ],
    ],
    'director_words' => [
        'model' => DirectorWord::class, 'group' => 'الصفحة الرئيسية', 'icon' => 'fa-user',
        'label' => 'كلمة المدير', 'plural' => 'كلمة المدير',
        'list' => ['director_name', 'director_title', 'is_active', 'updated_at'], 'links' => ['director_name'],
        'editable' => ['is_active'], 'filters' => ['is_active'], 'order_by' => 'created_at DESC',
        'fields' => [
            'director_name' => ['type' => 'text', 'label' => 'اسم المدير', 'max' => 200],
            'director_title' => ['type' => 'text', 'label' => 'المنصب', 'max' => 200, 'default' => 'مدير الخدمات الجامعية'],
            'director_image' => ['type' => 'image', 'label' => 'صورة المدير', 'dir' => 'director', 'required' => false, 'nullable' => true],
            'word_title' => ['type' => 'text', 'label' => 'عنوان الكلمة', 'max' => 200, 'default' => 'كلمة المدير'],
            'word_content' => ['type' => 'textarea', 'label' => 'نص الكلمة', 'rows' => 12],
            'signature' => ['type' => 'image', 'label' => 'التوقيع', 'dir' => 'director/signatures', 'required' => false, 'nullable' => true],
            'is_active' => $bool('نشط'),
            'created_at' => $created, 'updated_at' => $updated,
        ],
    ],
    'organization_structures' => [
        'model' => OrganizationStructure::class, 'group' => 'الصفحة الرئيسية', 'icon' => 'fa-sitemap',
        'label' => 'هيكل تنظيمي', 'plural' => 'الهيكل التنظيمي',
        'list' => ['title', 'is_active', 'updated_at'], 'links' => ['title'], 'editable' => ['is_active'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200, 'default' => 'الهيكل التنظيمي'],
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 4, 'required' => false],
            'organization_chart' => ['type' => 'image', 'label' => 'صورة الهيكل التنظيمي', 'dir' => 'organization', 'required' => false, 'nullable' => true],
            'content' => ['type' => 'textarea', 'label' => 'المحتوى', 'rows' => 10, 'required' => false],
            'is_active' => $bool('نشط'),
            'updated_at' => $updated,
        ],
    ],
    'directorate_histories' => [
        'model' => DirectorateHistory::class, 'group' => 'الصفحة الرئيسية', 'icon' => 'fa-history',
        'label' => 'تاريخ المديرية', 'plural' => 'تاريخ المديرية',
        'list' => ['title', 'founding_year', 'is_active', 'updated_at'], 'links' => ['title'], 'editable' => ['is_active'],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200, 'default' => 'تاريخ المديرية'],
            'introduction' => ['type' => 'textarea', 'label' => 'المقدمة', 'rows' => 4],
            'content' => ['type' => 'textarea', 'label' => 'المحتوى', 'rows' => 12],
            'main_image' => ['type' => 'image', 'label' => 'الصورة الرئيسية', 'dir' => 'history', 'required' => false, 'nullable' => true],
            'founding_year' => ['type' => 'int', 'label' => 'سنة التأسيس', 'required' => false, 'nullable' => true],
            'is_active' => $bool('نشط'),
            'updated_at' => $updated,
        ],
    ],

    // ================================================================ المحتوى
    'news' => [
        'model' => News::class, 'group' => 'المحتوى', 'icon' => 'fa-newspaper-o',
        'label' => 'خبر', 'plural' => 'الأخبار', 'per_page' => 25,
        'list' => ['main_image', 'title', 'author_id', 'status', 'is_featured', 'show_in_ticker', 'published_date', 'views_count'], 'links' => ['title'],
        'editable' => ['is_featured', 'show_in_ticker'], 'filters' => ['status', 'is_featured', 'show_in_ticker', 'published_date', 'author_id'],
        'search' => ['title', 'summary', 'content'], 'order_by' => 'COALESCE(published_date, created_at) DESC',
        'view_url' => fn($o) => url('news:news_detail', $o['slug']),
        'actions' => [
            'publish' => ['label' => 'نشر الأخبار المحددة', 'set' => ['status' => 'published'], 'message' => 'تم نشر %d خبر'],
            'draft' => ['label' => 'تحويل إلى مسودة', 'set' => ['status' => 'draft'], 'message' => 'تم تحويل %d خبر إلى مسودة'],
            'feature' => ['label' => 'تمييز الأخبار المحددة', 'set' => ['is_featured' => 1], 'message' => 'تم تمييز %d خبر'],
        ],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 300],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'title', 'max' => 300, 'unique' => true],
            'summary' => ['type' => 'textarea', 'label' => 'الملخص', 'rows' => 3, 'max' => 500, 'required' => false],
            'content' => ['type' => 'textarea', 'label' => 'المحتوى', 'rows' => 14],
            'main_image' => ['type' => 'image', 'label' => 'الصورة الرئيسية', 'dir' => 'news/main', 'required' => false, 'nullable' => true],
            'author_id' => ['type' => 'fk', 'label' => 'الكاتب', 'model' => User::class, 'display' => 'username', 'required' => false, 'default' => 'current_user'],
            'published_date' => ['type' => 'datetime', 'label' => 'تاريخ النشر', 'required' => false, 'nullable' => true, 'default' => 'now', 'help' => 'اتركه فارغاً ليُعتمد تاريخ الإنشاء'],
            'status' => ['type' => 'select', 'label' => 'الحالة', 'default' => 'published'],
            'is_featured' => $bool('خبر مميز', false),
            'show_in_ticker' => $bool('عرض في شريط الأخبار', false),
            'order' => $order(),
            'views_count' => ['type' => 'readonly', 'label' => 'عدد المشاهدات'],
            'created_at' => $created, 'updated_at' => $updated,
        ],
        'inlines' => [$imageInline(NewsImage::class, 'news_id', 'news/gallery')],
    ],
    'news_images' => [
        'model' => NewsImage::class, 'group' => 'المحتوى', 'icon' => 'fa-image', 'hidden' => true,
        'label' => 'صورة خبر', 'plural' => 'صور الأخبار',
        'list' => ['image', 'news_id', 'caption', 'order', 'uploaded_at'], 'links' => ['caption'], 'editable' => ['order'],
        'filters' => ['news_id'], 'search' => ['caption', 'news_id>news.title'], 'order_by' => 'news_id, `order`',
        'fields' => [
            'news_id' => ['type' => 'fk', 'label' => 'الخبر', 'model' => News::class, 'display' => 'title'],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'news/gallery'],
            'caption' => ['type' => 'text', 'label' => 'التعليق', 'max' => 300, 'required' => false],
            'order' => $order(),
        ],
    ],
    'latest_news' => [
        'model' => LatestNews::class, 'group' => 'المحتوى', 'icon' => 'fa-bullhorn', 'hidden' => true,
        'label' => 'آخر خبر', 'plural' => 'آخر الأخبار (جانبي)',
        'list' => ['image', 'description', 'order', 'is_active', 'created_at'], 'links' => ['description'],
        'editable' => ['order', 'is_active'], 'filters' => ['is_active', 'created_at'], 'search' => ['description'],
        'fields' => [
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 3, 'max' => 500],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'latest_news'],
            'link' => ['type' => 'text', 'label' => 'الرابط', 'max' => 255, 'required' => false, 'nullable' => true],
            'order' => $order(),
            'is_active' => $bool('نشط'),
        ],
    ],
    'activities' => [
        'model' => Activity::class, 'group' => 'المحتوى', 'icon' => 'fa-calendar',
        'label' => 'نشاط', 'plural' => 'النشاطات',
        'list' => ['main_image', 'title', 'activity_type', 'date', 'location', 'participants_count', 'is_featured', 'is_active', 'views_count'], 'links' => ['title'],
        'editable' => ['is_featured', 'is_active'], 'filters' => ['activity_type', 'is_featured', 'is_active', 'date'],
        'search' => ['title', 'description', 'location'], 'order_by' => '`date` DESC, created_at DESC',
        'view_url' => fn($o) => $o->get_absolute_url(),
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 300],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'title', 'max' => 300, 'unique' => true],
            'activity_type' => ['type' => 'select', 'label' => 'نوع النشاط', 'default' => 'culture'],
            'short_description' => ['type' => 'textarea', 'label' => 'وصف مختصر', 'rows' => 2, 'max' => 500],
            'description' => ['type' => 'textarea', 'label' => 'الوصف الكامل', 'rows' => 10],
            'main_image' => ['type' => 'image', 'label' => 'الصورة الرئيسية', 'dir' => 'activities/main'],
            'date' => ['type' => 'date', 'label' => 'التاريخ'],
            'location' => ['type' => 'text', 'label' => 'المكان', 'max' => 300],
            'organizer' => ['type' => 'text', 'label' => 'المنظم', 'max' => 200, 'required' => false],
            'participants_count' => ['type' => 'int', 'label' => 'عدد المشاركين', 'default' => 0, 'required' => false],
            'is_featured' => $bool('نشاط مميز', false),
            'is_active' => $bool('نشط'),
            'order' => $order(),
            'views_count' => ['type' => 'readonly', 'label' => 'عدد المشاهدات'],
        ],
        'inlines' => [$imageInline(ActivityImage::class, 'activity_id', 'activities/gallery')],
    ],
    'activity_images' => [
        'model' => ActivityImage::class, 'group' => 'المحتوى', 'icon' => 'fa-image', 'hidden' => true,
        'label' => 'صورة نشاط', 'plural' => 'صور النشاطات',
        'list' => ['image', 'activity_id', 'caption', 'order', 'uploaded_at'], 'links' => ['caption'], 'editable' => ['order'],
        'filters' => ['activity_id'], 'search' => ['caption', 'activity_id>activities.title'], 'order_by' => 'activity_id, `order`',
        'fields' => [
            'activity_id' => ['type' => 'fk', 'label' => 'النشاط', 'model' => Activity::class, 'display' => 'title'],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'activities/gallery'],
            'caption' => ['type' => 'text', 'label' => 'التعليق', 'max' => 300, 'required' => false],
            'order' => $order(),
        ],
    ],
    'cites' => [
        'model' => Cite::class, 'group' => 'المحتوى', 'icon' => 'fa-building',
        'label' => 'إقامة', 'plural' => 'الإقامات الجامعية',
        'list' => ['main_image', 'name', 'capacity', 'address', 'phone', 'order', 'is_active'], 'links' => ['name'],
        'editable' => ['order', 'is_active'], 'filters' => ['is_active'], 'search' => ['name', 'description', 'address'],
        'order_by' => '`order`, name', 'view_url' => fn($o) => $o->get_absolute_url(),
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'الاسم', 'max' => 200, 'unique' => true],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'name', 'max' => 200, 'unique' => true],
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 8],
            'main_image' => ['type' => 'image', 'label' => 'الصورة الرئيسية', 'dir' => 'cites/main'],
            'address' => ['type' => 'text', 'label' => 'العنوان', 'max' => 300, 'required' => false, 'nullable' => true],
            'capacity' => ['type' => 'int', 'label' => 'السعة (عدد الأسرة)', 'required' => false, 'nullable' => true],
            'phone' => ['type' => 'text', 'label' => 'الهاتف', 'max' => 20, 'required' => false, 'nullable' => true],
            'order' => $order(),
            'is_active' => $bool('نشط'),
        ],
        'inlines' => [$imageInline(CiteImage::class, 'cite_id', 'cites/gallery')],
    ],
    'cite_images' => [
        'model' => CiteImage::class, 'group' => 'المحتوى', 'icon' => 'fa-image', 'hidden' => true,
        'label' => 'صورة إقامة', 'plural' => 'صور الإقامات',
        'list' => ['image', 'cite_id', 'caption', 'order', 'uploaded_at'], 'links' => ['caption'], 'editable' => ['order'],
        'filters' => ['cite_id'], 'search' => ['caption', 'cite_id>cites.name'], 'order_by' => 'cite_id, `order`',
        'fields' => [
            'cite_id' => ['type' => 'fk', 'label' => 'الإقامة', 'model' => Cite::class, 'display' => 'name'],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'cites/gallery'],
            'caption' => ['type' => 'text', 'label' => 'التعليق', 'max' => 200, 'required' => false],
            'order' => $order(),
        ],
    ],
    'departements' => [
        'model' => Departement::class, 'group' => 'المحتوى', 'icon' => 'fa-briefcase',
        'label' => 'مصلحة', 'plural' => 'المصالح والأقسام',
        'list' => ['image', 'name', 'head_of_department', 'order', 'is_active'], 'links' => ['name'],
        'editable' => ['order', 'is_active'], 'filters' => ['is_active'], 'search' => ['name', 'description', 'head_of_department'],
        'order_by' => '`order`, name', 'view_url' => fn($o) => $o->get_absolute_url(),
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'الاسم', 'max' => 200, 'unique' => true],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'name', 'max' => 200, 'unique' => true],
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 8],
            'image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'departements'],
            'head_of_department' => ['type' => 'text', 'label' => 'رئيس المصلحة', 'max' => 200, 'required' => false, 'nullable' => true],
            'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'nullable' => true],
            'phone' => ['type' => 'text', 'label' => 'الهاتف', 'max' => 20, 'required' => false, 'nullable' => true],
            'website' => ['type' => 'url', 'label' => 'الموقع الإلكتروني', 'required' => false, 'nullable' => true],
            'order' => $order(),
            'is_active' => $bool('نشط'),
        ],
    ],
    'services' => [
        'model' => Service::class, 'group' => 'المحتوى', 'icon' => 'fa-cogs',
        'label' => 'خدمة', 'plural' => 'الخدمات',
        'list' => ['title', 'service_type', 'featured', 'is_active', 'display_order', 'updated_at'], 'links' => ['title'],
        'editable' => ['is_active', 'featured', 'display_order'], 'filters' => ['service_type', 'is_active', 'featured'],
        'search' => ['title', 'short_description'], 'order_by' => 'display_order, created_at DESC',
        'view_url' => fn($o) => $o->get_absolute_url(),
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'title', 'max' => 200, 'unique' => true],
            'service_type' => ['type' => 'select', 'label' => 'نوع الخدمة'],
            'short_description' => ['type' => 'textarea', 'label' => 'وصف مختصر', 'rows' => 3, 'max' => 500],
            'detailed_description' => ['type' => 'textarea', 'label' => 'الوصف المفصل', 'rows' => 10],
            'main_image' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'services', 'required' => false, 'nullable' => true],
            'icon' => ['type' => 'icon', 'label' => 'الأيقونة', 'max' => 50, 'default' => 'fa-info-circle'],
            'requirements' => ['type' => 'textarea', 'label' => 'الشروط والوثائق المطلوبة', 'rows' => 5, 'required' => false],
            'how_to_apply' => ['type' => 'textarea', 'label' => 'كيفية التقديم', 'rows' => 5, 'required' => false],
            'contact_info' => ['type' => 'textarea', 'label' => 'معلومات الاتصال', 'rows' => 3, 'required' => false],
            'featured' => $bool('خدمة مميزة', false),
            'is_active' => $bool('نشط'),
            'display_order' => $order('ترتيب العرض'),
            'created_at' => $created, 'updated_at' => $updated,
        ],
    ],
    'download_files' => [
        'model' => DownloadFile::class, 'group' => 'المحتوى', 'icon' => 'fa-download',
        'label' => 'ملف', 'plural' => 'مركز التحميلات',
        'list' => ['id', 'title', 'category', 'file_size', 'downloads_count', 'featured', 'is_active', 'created_at'], 'links' => ['id', 'title'],
        'editable' => ['is_active', 'featured'], 'filters' => ['category', 'is_active', 'featured', 'created_at'],
        'search' => ['title', 'description'], 'order_by' => 'display_order, created_at DESC',
        'actions' => [
            'feature' => ['label' => '⭐ وضع علامة "مميز"', 'set' => ['featured' => 1], 'message' => 'تم تمييز %d ملف'],
            'unfeature' => ['label' => '☆ إلغاء علامة "مميز"', 'set' => ['featured' => 0], 'message' => 'تم إلغاء تمييز %d ملف'],
        ],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200],
            'slug' => ['type' => 'slug', 'label' => 'الرابط (slug)', 'from' => 'title', 'max' => 200, 'unique' => true],
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 4],
            'category' => ['type' => 'select', 'label' => 'التصنيف'],
            'file' => ['type' => 'file', 'label' => 'الملف', 'dir' => 'downloads/{Y}/{m}', 'ext' => Upload::FILES, 'max_mb' => 'uploads.max_file_mb'],
            'featured' => $bool('ملف مميز', false),
            'is_active' => $bool('نشط'),
            'display_order' => $order('ترتيب العرض'),
            'file_size' => ['type' => 'readonly', 'label' => 'حجم الملف (بايت)'],
            'downloads_count' => ['type' => 'readonly', 'label' => 'عدد التحميلات'],
        ],
    ],
    'faq_categories' => [
        'model' => FaqCategory::class, 'group' => 'المحتوى', 'icon' => 'fa-folder-open',
        'label' => 'تصنيف أسئلة', 'plural' => 'تصنيفات الأسئلة',
        'list' => ['icon', 'name', 'order', 'faq_count', 'is_active'], 'links' => ['name'], 'editable' => ['order', 'is_active'],
        'search' => ['name'], 'order_by' => '`order`, name',
        'columns' => ['faq_count' => ['label' => 'عدد الأسئلة', 'value' => fn($o) => Faq::count('category_id = ?', [(int) $o['id']])]],
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'الاسم', 'max' => 100],
            'icon' => ['type' => 'icon', 'label' => 'الأيقونة', 'max' => 50, 'default' => 'fa-question-circle'],
            'order' => $order(),
            'is_active' => $bool('نشط'),
        ],
    ],
    'faqs' => [
        'model' => Faq::class, 'group' => 'المحتوى', 'icon' => 'fa-question-circle',
        'label' => 'سؤال', 'plural' => 'الأسئلة الشائعة',
        'list' => ['id', 'question', 'category_id', 'views_count', 'order', 'is_active', 'updated_at'], 'links' => ['id', 'question'],
        'editable' => ['order', 'is_active'], 'filters' => ['category_id', 'is_active', 'created_at'],
        'search' => ['question', 'answer'], 'order_by' => 'category_id, `order`, created_at DESC',
        'view_url' => fn($o) => $o->get_absolute_url(),
        'fields' => [
            'category_id' => ['type' => 'fk', 'label' => 'التصنيف', 'model' => FaqCategory::class, 'display' => 'name'],
            'question' => ['type' => 'text', 'label' => 'السؤال', 'max' => 300],
            'answer' => ['type' => 'textarea', 'label' => 'الإجابة', 'rows' => 8],
            'order' => $order(),
            'is_active' => $bool('نشط'),
            'views_count' => ['type' => 'readonly', 'label' => 'عدد المشاهدات'],
        ],
    ],
    'notifications' => [
        'model' => Notification::class, 'group' => 'المحتوى', 'icon' => 'fa-bell',
        'label' => 'إشعار عام', 'plural' => 'شريط الإشعارات',
        'list' => ['id', 'title', 'notification_type', 'priority', 'start_date', 'end_date', 'is_active', 'is_dismissible'], 'links' => ['id', 'title'],
        'editable' => ['is_active', 'priority'], 'filters' => ['notification_type', 'is_active', 'is_dismissible', 'created_at'],
        'search' => ['title', 'message'], 'order_by' => 'priority DESC, created_at DESC',
        'actions' => [
            'activate' => ['label' => '✓ تفعيل الإشعارات المحددة', 'set' => ['is_active' => 1], 'message' => 'تم تفعيل %d إشعار'],
            'deactivate' => ['label' => '✗ إلغاء تفعيل الإشعارات المحددة', 'set' => ['is_active' => 0], 'message' => 'تم إلغاء تفعيل %d إشعار'],
        ],
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200],
            'message' => ['type' => 'textarea', 'label' => 'الرسالة', 'rows' => 3],
            'notification_type' => ['type' => 'select', 'label' => 'النوع', 'default' => 'info'],
            'link' => ['type' => 'text', 'label' => 'الرابط', 'max' => 255, 'required' => false],
            'link_text' => ['type' => 'text', 'label' => 'نص الرابط', 'max' => 50, 'required' => false, 'default' => 'المزيد'],
            'start_date' => ['type' => 'datetime', 'label' => 'تاريخ البداية', 'default' => 'now'],
            'end_date' => ['type' => 'datetime', 'label' => 'تاريخ النهاية', 'required' => false, 'nullable' => true, 'help' => 'اتركه فارغاً ليبقى الإشعار ظاهراً'],
            'priority' => ['type' => 'int', 'label' => 'الأولوية', 'default' => 0, 'required' => false, 'help' => 'الأعلى يظهر أولاً'],
            'is_active' => $bool('نشط'),
            'is_dismissible' => $bool('قابل للإغلاق'),
        ],
    ],

    // ================================================================ التواصل
    'contact_messages' => [
        'model' => ContactMessage::class, 'group' => 'التواصل', 'icon' => 'fa-envelope', 'can_add' => false,
        'label' => 'رسالة', 'plural' => 'رسائل اتصل بنا',
        'list' => ['id', 'full_name', 'email', 'phone', 'subject', 'status', 'created_at'], 'links' => ['id', 'full_name'],
        'filters' => ['is_read', 'is_replied', 'subject', 'created_at'], 'search' => ['full_name', 'email', 'phone', 'message'],
        'order_by' => 'created_at DESC',
        'badge' => fn() => ContactMessage::count('is_read = 0'),
        'columns' => ['status' => ['label' => 'الحالة', 'value' => fn($o) => $o->get_status_display_ar()]],
        'on_view' => function ($o) {
            if (!$o['is_read']) {
                $o->update(['is_read' => 1]);
            }
        },
        'actions' => [
            'read' => ['label' => '✓ وضع علامة "مقروءة"', 'set' => ['is_read' => 1], 'message' => 'تم وضع علامة مقروءة على %d رسالة'],
            'replied' => ['label' => '✓ وضع علامة "تم الرد"', 'set' => ['is_replied' => 1, 'is_read' => 1], 'message' => 'تم وضع علامة "تم الرد" على %d رسالة'],
            'unread' => ['label' => '✗ وضع علامة "غير مقروءة"', 'set' => ['is_read' => 0, 'is_replied' => 0], 'message' => 'تم وضع علامة غير مقروءة على %d رسالة'],
        ],
        'fields' => [
            'full_name' => ['type' => 'readonly', 'label' => 'الاسم الكامل'],
            'email' => ['type' => 'readonly', 'label' => 'البريد الإلكتروني'],
            'phone' => ['type' => 'readonly', 'label' => 'الهاتف'],
            'subject' => ['type' => 'readonly', 'label' => 'الموضوع', 'choices' => 'subject'],
            'message' => ['type' => 'readonly', 'label' => 'الرسالة', 'multiline' => true],
            'ip_address' => ['type' => 'readonly', 'label' => 'عنوان IP'],
            'created_at' => $created,
            'is_read' => $bool('مقروءة', false),
            'is_replied' => $bool('تم الرد', false),
            'admin_notes' => ['type' => 'textarea', 'label' => 'ملاحظات الإدارة', 'rows' => 4, 'required' => false],
        ],
    ],

    // ================================================================ فضاء الطالب
    'student_profiles' => [
        'model' => StudentProfile::class, 'group' => 'فضاء الطالب', 'icon' => 'fa-graduation-cap',
        'label' => 'طالب', 'plural' => 'الطلاب',
        'list' => ['photo', 'registration_number', 'full_name_arabic', 'faculty', 'current_year', 'phone', 'is_verified', 'is_active', 'created_at'],
        'links' => ['registration_number', 'full_name_arabic'], 'filters' => ['is_verified', 'is_active', 'current_year', 'gender'],
        'search' => ['registration_number', 'full_name_arabic', 'phone', 'user_id>users.email'], 'order_by' => 'created_at DESC',
        'fields' => [
            'user_id' => ['type' => 'fk', 'label' => 'حساب المستخدم', 'model' => User::class, 'display' => 'username', 'unique' => true],
            'registration_number' => ['type' => 'text', 'label' => 'رقم التسجيل', 'max' => 20, 'unique' => true],
            'full_name_arabic' => ['type' => 'text', 'label' => 'الاسم الكامل بالعربية', 'max' => 100],
            'birth_date' => ['type' => 'date', 'label' => 'تاريخ الميلاد'],
            'birth_place' => ['type' => 'text', 'label' => 'مكان الميلاد', 'max' => 100],
            'gender' => ['type' => 'select', 'label' => 'الجنس'],
            'phone' => ['type' => 'text', 'label' => 'الهاتف', 'max' => 10, 'pattern' => StudentProfile::PHONE_PATTERN, 'pattern_message' => StudentProfile::PHONE_MESSAGE],
            'address' => ['type' => 'textarea', 'label' => 'العنوان', 'rows' => 2],
            'wilaya' => ['type' => 'text', 'label' => 'الولاية', 'max' => 100],
            'university' => ['type' => 'text', 'label' => 'الجامعة', 'max' => 200, 'default' => 'جامعة سطيف'],
            'faculty' => ['type' => 'text', 'label' => 'الكلية', 'max' => 200],
            'department' => ['type' => 'text', 'label' => 'القسم/التخصص', 'max' => 200],
            'current_year' => ['type' => 'select', 'label' => 'السنة الدراسية'],
            'average' => ['type' => 'decimal', 'label' => 'المعدل', 'required' => false, 'nullable' => true, 'min' => 0, 'max_value' => 20],
            'photo' => ['type' => 'image', 'label' => 'الصورة', 'dir' => 'students/photos', 'required' => false, 'nullable' => true],
            'is_verified' => $bool('حساب موثق', false),
            'is_active' => $bool('نشط'),
            'created_at' => $created, 'updated_at' => $updated,
        ],
    ],
    'application_types' => [
        'model' => ApplicationType::class, 'group' => 'فضاء الطالب', 'icon' => 'fa-list-alt',
        'label' => 'نوع طلب', 'plural' => 'أنواع الطلبات',
        'list' => ['icon', 'name', 'color', 'is_active', 'order'], 'links' => ['name'], 'editable' => ['is_active', 'order'],
        'order_by' => '`order`, name',
        'fields' => [
            'name' => ['type' => 'text', 'label' => 'الاسم', 'max' => 100],
            'description' => ['type' => 'textarea', 'label' => 'الوصف', 'rows' => 3],
            'icon' => ['type' => 'icon', 'label' => 'الأيقونة', 'max' => 50, 'default' => 'fa-file-text'],
            'color' => ['type' => 'color', 'label' => 'اللون', 'default' => '#3498db'],
            'required_documents' => ['type' => 'textarea', 'label' => 'الوثائق المطلوبة', 'rows' => 5, 'required' => false, 'help' => 'وثيقة في كل سطر'],
            'is_active' => $bool('نشط'),
            'order' => $order(),
        ],
    ],
    'student_applications' => [
        'model' => StudentApplication::class, 'group' => 'فضاء الطالب', 'icon' => 'fa-file-text',
        'label' => 'طلب', 'plural' => 'طلبات الطلاب',
        'list' => ['application_number', 'student_id', 'application_type_id', 'status', 'submitted_at', 'reviewed_at'], 'links' => ['application_number'],
        'filters' => ['status', 'application_type_id', 'submitted_at'],
        'search' => ['application_number', 'student_id>student_profiles.registration_number', 'student_id>student_profiles.full_name_arabic'],
        'order_by' => 'submitted_at DESC',
        'badge' => fn() => StudentApplication::count("status = 'pending'"),
        'view_url' => fn($o) => url('students:admin_application_detail', $o['id']),
        'on_save' => function (StudentApplication $o, ?array $old) {
            if ($old !== null && ($old['status'] ?? null) !== $o['status']) {
                if (in_array($o['status'], ['approved', 'rejected'], true)) {
                    $o->update(['reviewed_by_id' => (int) current_user()['id'], 'reviewed_at' => now()]);
                }
                $o->notify_status_change();
            }
        },
        'fields' => [
            'application_number' => ['type' => 'readonly', 'label' => 'رقم الطلب'],
            'student_id' => ['type' => 'fk', 'label' => 'الطالب', 'model' => StudentProfile::class, 'display' => 'full_name_arabic'],
            'application_type_id' => ['type' => 'fk', 'label' => 'نوع الطلب', 'model' => ApplicationType::class, 'display' => 'name'],
            'status' => ['type' => 'select', 'label' => 'الحالة', 'default' => 'pending'],
            'details' => ['type' => 'textarea', 'label' => 'تفاصيل الطلب', 'rows' => 4, 'required' => false, 'nullable' => true],
            'admin_notes' => ['type' => 'textarea', 'label' => 'ملاحظات الإدارة', 'rows' => 3, 'required' => false, 'nullable' => true],
            'reviewed_by_id' => ['type' => 'readonly', 'label' => 'راجعه', 'model' => User::class, 'display' => 'username'],
            'submitted_at' => ['type' => 'readonly', 'label' => 'تاريخ التقديم'],
            'reviewed_at' => ['type' => 'readonly', 'label' => 'تاريخ المراجعة'],
            'updated_at' => $updated,
        ],
        'inlines' => [[
            'model' => ApplicationDocument::class, 'fk' => 'application_id', 'label' => 'الوثائق المرفقة', 'file' => 'document_file',
            'dir' => 'documents/{Y}/{m}', 'private' => true, 'kind' => 'document', 'ext' => Upload::DOCUMENTS,
            'text' => ['document_name' => 'اسم الوثيقة'], 'order' => null,
        ]],
    ],
    'student_notifications' => [
        'model' => StudentNotification::class, 'group' => 'فضاء الطالب', 'icon' => 'fa-bell-o',
        'label' => 'إشعار طالب', 'plural' => 'إشعارات الطلاب',
        'list' => ['title', 'student_id', 'notification_type', 'is_read', 'created_at'], 'links' => ['title'],
        'filters' => ['is_read', 'notification_type', 'created_at'], 'search' => ['title', 'message', 'student_id>student_profiles.full_name_arabic'],
        'order_by' => 'created_at DESC',
        'fields' => [
            'student_id' => ['type' => 'fk', 'label' => 'الطالب', 'model' => StudentProfile::class, 'display' => 'full_name_arabic'],
            'title' => ['type' => 'text', 'label' => 'العنوان', 'max' => 200],
            'message' => ['type' => 'textarea', 'label' => 'الرسالة', 'rows' => 4],
            'link' => ['type' => 'text', 'label' => 'الرابط', 'max' => 500, 'required' => false, 'nullable' => true, 'help' => 'مثال: /students/applications/'],
            'notification_type' => ['type' => 'select', 'label' => 'النوع', 'default' => 'info'],
            'is_read' => $bool('مقروء', false),
            'created_at' => $created,
            'read_at' => ['type' => 'readonly', 'label' => 'تاريخ القراءة'],
        ],
    ],

    // ================================================================ الإحصائيات
    'site_visits' => [
        'model' => SiteVisit::class, 'group' => 'الإحصائيات', 'icon' => 'fa-line-chart', 'can_add' => false, 'can_edit' => false,
        'label' => 'زيارة', 'plural' => 'زيارات الموقع', 'per_page' => 50,
        'list' => ['ip_address', 'page_visited', 'visited_at'], 'links' => ['ip_address'], 'filters' => ['visited_at'],
        'search' => ['ip_address', 'page_visited'], 'order_by' => 'visited_at DESC',
        'fields' => [
            'ip_address' => ['type' => 'readonly', 'label' => 'عنوان IP'],
            'user_agent' => ['type' => 'readonly', 'label' => 'المتصفح', 'multiline' => true],
            'page_visited' => ['type' => 'readonly', 'label' => 'الصفحة'],
            'visited_at' => ['type' => 'readonly', 'label' => 'وقت الزيارة'],
        ],
    ],
    'site_statistics' => [
        'model' => SiteStatistics::class, 'group' => 'الإحصائيات', 'icon' => 'fa-pie-chart', 'can_add' => false, 'can_delete' => false,
        'label' => 'إحصائيات', 'plural' => 'إحصائيات الزوار',
        'list' => ['total_visitors', 'total_page_views', 'updated_at'], 'links' => ['total_visitors'],
        'fields' => [
            'total_visitors' => ['type' => 'int', 'label' => 'إجمالي الزوار', 'min' => 0],
            'total_page_views' => ['type' => 'int', 'label' => 'مشاهدات الصفحات', 'min' => 0],
            'updated_at' => $updated,
        ],
    ],

    // ================================================================ المستخدمون (للمدير العام فقط)
    'users' => [
        'model' => User::class, 'group' => 'المستخدمون والصلاحيات', 'icon' => 'fa-users', 'superuser_only' => true,
        'label' => 'مستخدم', 'plural' => 'المستخدمون',
        'list' => ['username', 'full_name', 'email', 'is_staff', 'is_superuser', 'is_active', 'last_login'], 'links' => ['username'],
        'filters' => ['is_staff', 'is_superuser', 'is_active'], 'search' => ['username', 'first_name', 'last_name', 'email'],
        'order_by' => 'username',
        'columns' => ['full_name' => ['label' => 'الاسم الكامل', 'value' => fn($o) => $o->get_full_name()]],
        'fields' => [
            'username' => ['type' => 'text', 'label' => 'اسم المستخدم', 'max' => 150, 'unique' => true, 'pattern' => '/^[\w.@+-]+$/u', 'pattern_message' => 'أحرف وأرقام و @/./+/-/_ فقط'],
            'password' => ['type' => 'password', 'label' => 'كلمة المرور', 'help' => 'اتركها فارغة للإبقاء على كلمة المرور الحالية'],
            'first_name' => ['type' => 'text', 'label' => 'الاسم', 'max' => 150, 'required' => false],
            'last_name' => ['type' => 'text', 'label' => 'اللقب', 'max' => 150, 'required' => false],
            'email' => ['type' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false],
            'is_active' => $bool('نشط'),
            'is_staff' => $bool('موظف (دخول لوحة الإدارة)', false),
            'is_superuser' => $bool('مدير عام (كل الصلاحيات)', false),
            'last_login' => ['type' => 'readonly', 'label' => 'آخر دخول'],
            'date_joined' => ['type' => 'readonly', 'label' => 'تاريخ التسجيل'],
        ],
    ],
];
