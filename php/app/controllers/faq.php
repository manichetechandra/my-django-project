<?php
/**
 * الأسئلة الشائعة (faq/views.py)
 */

function faq_list(): void
{
    $searchQuery = query('q');
    $categories = FaqCategory::where('is_active = 1', [], '`order`, name');
    $where = 'f.is_active = 1';
    $params = [];
    if ($searchQuery !== '') {
        $fields = ['question', 'answer'];
        if (lang() !== default_lang()) {
            // نبحث أيضاً في ترجمة لغة الصفحة (question_fr و answer_fr ...)
            array_push($fields, 'question_' . lang(), 'answer_' . lang());
        }
        $where .= ' AND (' . implode(' OR ', array_map(fn($f) => "f.`$f` LIKE ?", $fields)) . ')';
        $params = array_fill(0, count($fields), DB::like($searchQuery));
    }
    $faqs = Faq::query("SELECT f.* FROM faqs f JOIN faq_categories c ON c.id = f.category_id WHERE $where ORDER BY c.`order`, f.`order`, f.created_at DESC", $params);

    // [[التصنيف, [الأسئلة...]], ...]
    $faqsByCategory = [];
    foreach ($categories as $category) {
        $catFaqs = array_values(array_filter($faqs, fn($f) => (int) $f['category_id'] === (int) $category['id']));
        if ($catFaqs) {
            foreach ($catFaqs as $f) {
                $f->setRelation('category', $category);
            }
            $faqsByCategory[] = [$category, $catFaqs];
        }
    }

    render('faq/faq_list', [
        'categories'       => $categories,
        'faqs_by_category' => $faqsByCategory,
        'popular_faqs'     => Faq::where('is_active = 1', [], 'views_count DESC', 5),
        'total_faqs'       => count($faqs),
        'total_categories' => count($faqsByCategory),
        'search_query'     => $searchQuery,
        'page_title'       => __('الأسئلة الشائعة'),
    ]);
}

function faq_detail(string $faqId): void
{
    $faq = Faq::firstWhere('id = ? AND is_active = 1', [(int) $faqId]);
    if (!$faq) {
        abort(404);
    }
    $faq->increment_views();
    render('faq/faq_detail', [
        'faq'          => $faq,
        'related_faqs' => Faq::where('category_id = ? AND is_active = 1 AND id <> ?', [(int) $faq['category_id'], (int) $faq['id']], '`order`, created_at DESC', 3),
        'page_title'   => $faq['question'],
    ]);
}
