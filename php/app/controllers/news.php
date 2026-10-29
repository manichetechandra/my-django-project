<?php
/**
 * الأخبار (news/views.py)
 */

function news_list(): void
{
    render('news/news_list', [
        'news'       => News::paginate("status = 'published'", [], 'COALESCE(published_date, created_at) DESC, id DESC', 9),
        'page_title' => __('الأخبار'),
    ]);
}

function news_detail(string $slug): void
{
    $news = News::firstWhere("slug = ? AND status = 'published'", [$slug]);
    if (!$news) {
        abort(404);
    }
    DB::query('UPDATE news SET views_count = views_count + 1 WHERE id = ?', [(int) $news['id']]);
    $news['views_count'] = (int) $news['views_count'] + 1;

    render('news/news_detail', [
        'news'         => $news,
        'related_news' => News::where("status = 'published' AND id <> ?", [(int) $news['id']], 'COALESCE(published_date, created_at) DESC', 3),
    ]);
}
