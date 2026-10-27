<?php
/**
 * إحصائيات الزوار (site_stats/middleware.py + context_processors.py)
 */

/** تسجيل زيارة الصفحة الحالية */
function track_visit(string $path): void
{
    // لا نحسب طلبات AJAX (مثل فحص الإشعارات كل بضع ثوانٍ) ولا الملفات
    if (is_ajax() || str_starts_with($path, '/students/api/') || preg_match('#\.(ico|png|jpe?g|gif|svg|css|js|map|txt|xml)$#i', $path)) {
        return;
    }
    try {
        $ip = substr(client_ip(), 0, 45);
        // زائر جديد = لم يزر الموقع من نفس العنوان خلال 24 ساعة
        $isNew = !DB::value('SELECT 1 FROM site_visits WHERE ip_address = ? AND visited_at >= ? LIMIT 1', [$ip, date('Y-m-d H:i:s', time() - 86400)]);
        DB::insert('site_visits', [
            'ip_address'   => $ip,
            'user_agent'   => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
            'page_visited' => mb_substr($path, 0, 255),
            'visited_at'   => now(),
        ]);
        SiteStatistics::get_stats();
        DB::query('UPDATE site_statistics SET total_page_views = total_page_views + 1, total_visitors = total_visitors + ?, updated_at = ? WHERE id = 1', [$isNew ? 1 : 0, now()]);
    } catch (Throwable $e) {
        error_log('خطأ في تسجيل الزيارة: ' . $e->getMessage());
    }
}

/** أرقام الموقع للصفحة الرئيسية */
function site_stats(): array
{
    $s = SiteStatistics::get_stats();
    return [
        'total_visitors'   => (int) $s['total_visitors'],
        'total_page_views' => (int) $s['total_page_views'],
        'total_news'       => News::count("status = 'published'"),
        'total_services'   => Service::count('is_active = 1'),
        'total_downloads'  => DownloadFile::count('is_active = 1'),
        'total_faqs'       => Faq::count('is_active = 1'),
        'total_cites'      => Cite::count('is_active = 1'),
    ];
}
