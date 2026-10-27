<?php
/**
 * نماذج المحتوى: الأخبار، النشاطات، الإقامات، المصالح، الخدمات، التحميلات، الأسئلة الشائعة، الإشعارات العامة.
 */

// ============================================================ news/models.py

class News extends Model
{
    protected static string $table = 'news';
    protected static array $choices = [
        'status' => ['draft' => 'مسودة', 'published' => 'منشور', 'archived' => 'مؤرشف'],
    ];
    protected static array $booleans = ['is_featured', 'show_in_ticker'];
    protected static array $belongsTo = ['author' => [User::class, 'author_id']];
    protected static array $hasMany = ['images' => [NewsImage::class, 'news_id', 'ORDER BY `order`, uploaded_at DESC']];
    protected static array $defaults = ['summary' => '', 'status' => 'published', 'views_count' => 0, 'is_featured' => 0, 'show_in_ticker' => 0, 'order' => 0];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('news', (string) $this->attributes['title'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('news:news_detail', $this->attributes['slug']);
    }
}

class NewsImage extends Model
{
    protected static string $table = 'news_images';
    protected static array $belongsTo = ['news' => [News::class, 'news_id']];
    protected static array $defaults = ['caption' => '', 'order' => 0];
    protected static array $createdFields = ['uploaded_at'];
}

class LatestNews extends Model
{
    protected static string $table = 'latest_news';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['order' => 0, 'is_active' => 1];
    protected static array $createdFields = ['created_at'];
}

// ============================================================ activities/models.py

class Activity extends Model
{
    protected static string $table = 'activities';
    protected static array $choices = [
        'activity_type' => ['sport' => 'رياضي', 'culture' => 'ثقافي', 'scientific' => 'علمي', 'social' => 'اجتماعي', 'artistic' => 'فني', 'other' => 'أخرى'],
    ];
    protected static array $booleans = ['is_featured', 'is_active'];
    protected static array $hasMany = ['images' => [ActivityImage::class, 'activity_id', 'ORDER BY `order`, uploaded_at DESC']];
    protected static array $defaults = ['activity_type' => 'culture', 'organizer' => '', 'participants_count' => 0, 'is_featured' => 0, 'is_active' => 1, 'views_count' => 0, 'order' => 0, 'main_image' => ''];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('activities', (string) $this->attributes['title'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('activities:activity_detail', $this->attributes['slug']);
    }

    public function get_type_icon(): string
    {
        $icons = ['sport' => 'fa-trophy', 'culture' => 'fa-book', 'scientific' => 'fa-flask', 'social' => 'fa-users', 'artistic' => 'fa-paint-brush', 'other' => 'fa-star'];
        return $icons[$this->attributes['activity_type'] ?? ''] ?? 'fa-star';
    }

    public function get_type_color(): string
    {
        $colors = ['sport' => '#667eea', 'culture' => '#f093fb', 'scientific' => '#4facfe', 'social' => '#43e97b', 'artistic' => '#fa709a', 'other' => '#8cc040'];
        return $colors[$this->attributes['activity_type'] ?? ''] ?? '#8cc040';
    }

    public function get_type_gradient(): string
    {
        $g = [
            'sport' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'culture' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'scientific' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'social' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'artistic' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'other' => 'linear-gradient(135deg, #8cc040 0%, #6fa832 100%)',
        ];
        return $g[$this->attributes['activity_type'] ?? ''] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }
}

class ActivityImage extends Model
{
    protected static string $table = 'activity_images';
    protected static array $belongsTo = ['activity' => [Activity::class, 'activity_id']];
    protected static array $defaults = ['caption' => '', 'order' => 0];
    protected static array $createdFields = ['uploaded_at'];
}

// ============================================================ cities/models.py

class Cite extends Model
{
    protected static string $table = 'cites';
    protected static array $booleans = ['is_active'];
    protected static array $hasMany = ['images' => [CiteImage::class, 'cite_id', 'ORDER BY `order`, uploaded_at DESC']];
    protected static array $defaults = ['is_active' => 1, 'order' => 0, 'main_image' => ''];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('cites', (string) $this->attributes['name'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('cities:cite_detail', $this->attributes['slug']);
    }
}

class CiteImage extends Model
{
    protected static string $table = 'cite_images';
    protected static array $belongsTo = ['cite' => [Cite::class, 'cite_id']];
    protected static array $defaults = ['caption' => '', 'order' => 0];
    protected static array $createdFields = ['uploaded_at'];
}

// ============================================================ departements/models.py

class Departement extends Model
{
    protected static string $table = 'departements';
    protected static array $booleans = ['is_active'];
    protected static array $defaults = ['is_active' => 1, 'order' => 0, 'image' => ''];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('departements', (string) $this->attributes['name'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('departements:departement_detail', $this->attributes['slug']);
    }
}

// ============================================================ services/models.py

class Service extends Model
{
    protected static string $table = 'services';
    protected static array $choices = [
        'service_type' => ['transport' => 'النقل', 'housing' => 'الإيواء', 'catering' => 'الإطعام', 'scholarship' => 'المنح'],
    ];
    protected static array $booleans = ['is_active', 'featured'];
    protected static array $defaults = ['icon' => 'fa-info-circle', 'requirements' => '', 'how_to_apply' => '', 'contact_info' => '', 'is_active' => 1, 'featured' => 0, 'display_order' => 0];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('services', (string) $this->attributes['title'], $isNew ? null : (int) $this->attributes['id']);
        }
    }

    public function get_absolute_url(): string
    {
        return url('services:service_detail', $this->attributes['slug']);
    }

    public function get_type_color(): string
    {
        $c = ['transport' => '#3498db', 'housing' => '#e74c3c', 'catering' => '#f39c12', 'scholarship' => '#2ecc71'];
        return $c[$this->attributes['service_type'] ?? ''] ?? '#95a5a6';
    }

    public function get_type_icon_default(): string
    {
        $i = ['transport' => 'fa-bus', 'housing' => 'fa-home', 'catering' => 'fa-cutlery', 'scholarship' => 'fa-money'];
        return $i[$this->attributes['service_type'] ?? ''] ?? 'fa-info-circle';
    }
}

// ============================================================ contact/models.py

class ContactMessage extends Model
{
    protected static string $table = 'contact_messages';
    protected static array $choices = [
        'subject' => [
            'general' => 'استفسار عام', 'transport' => 'النقل الجامعي', 'housing' => 'الإقامة الجامعية',
            'catering' => 'المطاعم والإطعام', 'scholarship' => 'المنح والإعانات', 'complaint' => 'شكوى',
            'suggestion' => 'اقتراح', 'other' => 'أخرى',
        ],
    ];
    protected static array $booleans = ['is_read', 'is_replied'];
    protected static array $defaults = ['phone' => '', 'is_read' => 0, 'is_replied' => 0, 'admin_notes' => ''];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    public function get_status_display_ar(): string
    {
        if ($this->attributes['is_replied'] ?? false) {
            return 'تم الرد ✓';
        }
        return ($this->attributes['is_read'] ?? false) ? 'مقروءة' : 'جديدة';
    }

    public function get_subject_color(): string
    {
        $c = ['general' => '#3498db', 'transport' => '#9b59b6', 'housing' => '#e74c3c', 'catering' => '#f39c12',
              'scholarship' => '#2ecc71', 'complaint' => '#e67e22', 'suggestion' => '#1abc9c', 'other' => '#95a5a6'];
        return $c[$this->attributes['subject'] ?? ''] ?? '#95a5a6';
    }
}

// ============================================================ downloads/models.py

class DownloadFile extends Model
{
    protected static string $table = 'download_files';
    protected static array $choices = [
        'category' => [
            'scholarship_forms' => 'نماذج المنح والإعانات', 'housing_forms' => 'نماذج السكن الجامعي',
            'transport_forms' => 'نماذج النقل', 'catering_forms' => 'نماذج المطاعم', 'guides' => 'دلائل إرشادية',
            'regulations' => 'لوائح وقوانين', 'reports' => 'تقارير وإحصائيات', 'other' => 'أخرى',
        ],
    ];
    protected static array $booleans = ['is_active', 'featured'];
    protected static array $defaults = ['file_size' => 0, 'downloads_count' => 0, 'is_active' => 1, 'featured' => 0, 'display_order' => 0];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    protected function beforeSave(bool $isNew): void
    {
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = unique_slug('download_files', (string) $this->attributes['title'], $isNew ? null : (int) $this->attributes['id']);
        }
        $path = UPLOAD_PATH . '/' . ltrim((string) ($this->attributes['file'] ?? ''), '/');
        if (!empty($this->attributes['file']) && is_file($path)) {
            $this->attributes['file_size'] = filesize($path);
        }
    }

    public function get_absolute_url(): string
    {
        return url('downloads:download_file', $this->attributes['slug']);
    }

    public function get_file_extension(): string
    {
        $ext = strtolower(pathinfo((string) ($this->attributes['file'] ?? ''), PATHINFO_EXTENSION));
        return $ext === '' ? '' : '.' . $ext;
    }

    public function get_file_icon(): string
    {
        $icons = ['.pdf' => 'fa-file-pdf-o', '.doc' => 'fa-file-word-o', '.docx' => 'fa-file-word-o', '.xls' => 'fa-file-excel-o',
                  '.xlsx' => 'fa-file-excel-o', '.ppt' => 'fa-file-powerpoint-o', '.pptx' => 'fa-file-powerpoint-o',
                  '.zip' => 'fa-file-archive-o', '.rar' => 'fa-file-archive-o'];
        return $icons[$this->get_file_extension()] ?? 'fa-file-o';
    }

    public function get_file_color(): string
    {
        $colors = ['.pdf' => '#e74c3c', '.doc' => '#3498db', '.docx' => '#3498db', '.xls' => '#2ecc71', '.xlsx' => '#2ecc71',
                   '.ppt' => '#e67e22', '.pptx' => '#e67e22', '.zip' => '#95a5a6', '.rar' => '#95a5a6'];
        return $colors[$this->get_file_extension()] ?? '#34495e';
    }

    public function get_file_size_display(): string
    {
        $size = (int) ($this->attributes['file_size'] ?? 0);
        if ($size < 1024) {
            return $size . ' بايت';
        }
        if ($size < 1024 * 1024) {
            return number_format($size / 1024, 1) . ' KB';
        }
        return number_format($size / (1024 * 1024), 1) . ' MB';
    }

    public function get_category_color(): string
    {
        $c = ['scholarship_forms' => '#2ecc71', 'housing_forms' => '#e74c3c', 'transport_forms' => '#3498db', 'catering_forms' => '#f39c12',
              'guides' => '#9b59b6', 'regulations' => '#34495e', 'reports' => '#1abc9c', 'other' => '#95a5a6'];
        return $c[$this->attributes['category'] ?? ''] ?? '#95a5a6';
    }

    public function increment_downloads(): void
    {
        DB::query('UPDATE download_files SET downloads_count = downloads_count + 1 WHERE id = ?', [(int) $this->attributes['id']]);
        $this->attributes['downloads_count'] = (int) ($this->attributes['downloads_count'] ?? 0) + 1;
    }
}

// ============================================================ faq/models.py

class FaqCategory extends Model
{
    protected static string $table = 'faq_categories';
    protected static array $booleans = ['is_active'];
    protected static array $hasMany = ['faqs' => [Faq::class, 'category_id', 'ORDER BY `order`, created_at DESC']];
    protected static array $defaults = ['icon' => 'fa-question-circle', 'order' => 0, 'is_active' => 1];

    public function get_color(): string
    {
        $c = ['عام' => '#3498db', 'المنح' => '#2ecc71', 'السكن' => '#e74c3c', 'النقل' => '#9b59b6', 'الإطعام' => '#f39c12'];
        return $c[$this->attributes['name'] ?? ''] ?? '#95a5a6';
    }
}

class Faq extends Model
{
    protected static string $table = 'faqs';
    protected static array $booleans = ['is_active'];
    protected static array $belongsTo = ['category' => [FaqCategory::class, 'category_id']];
    protected static array $defaults = ['order' => 0, 'is_active' => 1, 'views_count' => 0];
    protected static array $createdFields = ['created_at'];
    protected static array $updatedFields = ['updated_at'];

    public function get_absolute_url(): string
    {
        return url('faq:faq_detail', $this->attributes['id']);
    }

    public function increment_views(): void
    {
        DB::query('UPDATE faqs SET views_count = views_count + 1 WHERE id = ?', [(int) $this->attributes['id']]);
        $this->attributes['views_count'] = (int) ($this->attributes['views_count'] ?? 0) + 1;
    }
}

// ============================================================ notifications/models.py (شريط الإشعارات)

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static array $choices = [
        'notification_type' => ['info' => 'معلومة', 'success' => 'نجاح', 'warning' => 'تحذير', 'danger' => 'عاجل/خطر', 'primary' => 'عادي'],
    ];
    protected static array $booleans = ['is_active', 'is_dismissible'];
    protected static array $defaults = ['notification_type' => 'info', 'link' => '', 'link_text' => 'المزيد', 'is_active' => 1, 'is_dismissible' => 1, 'priority' => 0];
    protected static array $createdFields = ['created_at', 'start_date'];
    protected static array $updatedFields = ['updated_at'];

    /** الإشعارات الصالحة الآن (مثل {% show_notifications %}) */
    public static function valid(): array
    {
        return static::where('is_active = 1 AND start_date <= ? AND (end_date IS NULL OR end_date >= ?)', [now(), now()], 'priority DESC, created_at DESC');
    }

    public function is_valid(): bool
    {
        $now = now();
        if (!($this->attributes['is_active'] ?? false)) {
            return false;
        }
        if (($this->attributes['start_date'] ?? '') > $now) {
            return false;
        }
        return empty($this->attributes['end_date']) || $this->attributes['end_date'] >= $now;
    }

    public function get_icon(): string
    {
        $i = ['info' => 'fa-info-circle', 'success' => 'fa-check-circle', 'warning' => 'fa-exclamation-triangle', 'danger' => 'fa-exclamation-circle', 'primary' => 'fa-bell'];
        return $i[$this->attributes['notification_type'] ?? ''] ?? 'fa-bell';
    }

    public function get_color(): string
    {
        $c = ['info' => '#5bc0de', 'success' => '#5cb85c', 'warning' => '#f0ad4e', 'danger' => '#d9534f', 'primary' => '#337ab7'];
        return $c[$this->attributes['notification_type'] ?? ''] ?? '#337ab7';
    }

    public function get_bootstrap_class(): string
    {
        return 'alert-' . ($this->attributes['notification_type'] ?? 'info');
    }
}

// ============================================================ site_stats/models.py

class SiteVisit extends Model
{
    protected static string $table = 'site_visits';
}

class SiteStatistics extends Model
{
    protected static string $table = 'site_statistics';

    public static function get_stats(): self
    {
        $s = static::find(1);
        if (!$s) {
            DB::query('INSERT IGNORE INTO site_statistics (id, total_visitors, total_page_views, updated_at) VALUES (1, 0, 0, ?)', [now()]);
            $s = static::find(1);
        }
        return $s;
    }
}
