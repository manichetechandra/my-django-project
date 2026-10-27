<?php
/**
 * الصفحة الرئيسية وصفحات المديرية (homepage/views.py)
 */

function homepage_index(): void
{
    render('homepage/index', [
        'slider_images'       => Slider::where('is_active = 1', [], '`order`, created_at DESC', 4),
        'ticker_news'         => News::where("status = 'published' AND show_in_ticker = 1", [], '`order`', 2),
        'latest_news'         => News::where("status = 'published'", [], 'COALESCE(published_date, created_at) DESC', 5),
        'featured_news'       => News::where("status = 'published' AND is_featured = 1", [], 'COALESCE(published_date, created_at) DESC', 3),
        'featured_activities' => Activity::where('is_active = 1 AND is_featured = 1', [], '`date` DESC', 3),
        'cites_list'          => Cite::where('is_active = 1', [], '`order`, name', 12),
        'departements_list'   => Departement::where('is_active = 1', [], '`order`, name', 4),
        'statistics'          => Statistic::where('is_active = 1', [], '`order`', 4),
        'director_word'       => DirectorWord::firstWhere('is_active = 1', [], 'created_at DESC'),
        'site_stats'          => site_stats(),
    ]);
}

function homepage_director_word(): void
{
    render('homepage/director_word', [
        'director_word' => DirectorWord::firstWhere('is_active = 1', [], 'created_at DESC'),
        'page_title'    => 'كلمة المدير',
    ]);
}

function homepage_organization(): void
{
    render('homepage/organization_structure', [
        'organization' => OrganizationStructure::firstWhere('is_active = 1', [], 'id'),
        'page_title'   => 'الهيكل التنظيمي',
    ]);
}

function homepage_history(): void
{
    render('homepage/directorate_history', [
        'history'    => DirectorateHistory::firstWhere('is_active = 1', [], 'id'),
        'page_title' => 'تاريخ المديرية',
    ]);
}
