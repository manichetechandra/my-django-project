<?php
/**
 * البحث الشامل في الموقع (search/views.py)
 */

/** تطبيع البحث: حذف كلمات الوقف (العربية والفرنسية والإنجليزية) وإضافة الكلمة بدون "ال" */
function normalize_arabic_search(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }
    $stopWords = ['في', 'من', 'إلى', 'على', 'عن', 'هذا', 'هذه', 'ذلك', 'تلك', 'أن', 'أو', 'لا', 'ما', 'هل',
        // الفرنسية والإنجليزية: كلمات قصيرة تطابق كل شيء تقريباً
        'de', 'du', 'des', 'la', 'le', 'les', 'un', 'une', 'et', 'en', 'au', 'aux', 'pour', 'par', 'sur', 'dans', 'est', 'ou', 'que', 'qui', 'ce', 'ces', 'se', 'sa', 'son', 'ses',
        'the', 'of', 'and', 'to', 'in', 'on', 'for', 'an', 'is', 'are', 'at', 'by', 'or', 'with', 'from', 'as', 'be', 'it', 'its', 'this', 'that'];
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $expanded = [];
    foreach ($words as $w) {
        $w = preg_replace("/^(?:l|d|qu|j|n|s|c)['’]/iu", '', $w); // الفرنسية: l'université → université
        if (in_array(mb_strtolower($w), $stopWords, true) || mb_strlen($w) < 2) {
            continue;
        }
        $expanded[] = $w;
        if (mb_substr($w, 0, 2) === 'ال' && mb_strlen($w) > 2) {
            $expanded[] = mb_substr($w, 2);
        }
    }
    return array_values(array_unique($expanded));
}

/** (field1 LIKE ? OR field2 LIKE ? ...) لكل كلمة، مجمّعة بـ OR */
function build_search_query(array $fields, array $terms, array &$params): string
{
    $parts = [];
    foreach ($terms as $term) {
        $like = DB::like($term);
        foreach ($fields as $f) {
            $parts[] = "`$f` LIKE ?";
            $params[] = $like;
        }
    }
    return $parts ? '(' . implode(' OR ', $parts) . ')' : '0';
}

function search_view(): void
{
    $query = query('q');
    $searchType = query('type', 'all') ?: 'all';
    $sections = [
        // النوع => [النموذج, الحقول, شرط إضافي, الترتيب, الحد]
        'news'           => [News::class, ['title', 'content', 'summary'], "status = 'published'", 'COALESCE(published_date, created_at) DESC', 30],
        'activities'     => [Activity::class, ['title', 'description', 'short_description', 'location'], 'is_active = 1', '`date` DESC', 30],
        'services'       => [Service::class, ['title', 'short_description', 'detailed_description', 'requirements', 'how_to_apply'], 'is_active = 1', 'created_at DESC', 30],
        'downloads'      => [DownloadFile::class, ['title', 'description'], 'is_active = 1', 'created_at DESC', 30],
        'faqs'           => [Faq::class, ['question', 'answer'], 'is_active = 1', 'views_count DESC', 30],
        'departements'   => [Departement::class, ['name', 'description', 'head_of_department'], 'is_active = 1', '`order`, name', 30],
        'cites'          => [Cite::class, ['name', 'address', 'description'], 'is_active = 1', '`order`, name', 30],
        'sliders'        => [Slider::class, ['title', 'subtitle'], 'is_active = 1', '`order`', 20],
        'director_words' => [DirectorWord::class, ['word_title', 'word_content', 'director_name', 'director_title'], 'is_active = 1', 'created_at DESC', 20],
        'history'        => [DirectorateHistory::class, ['title', 'introduction', 'content'], 'is_active = 1', 'id', 20],
        'organization'   => [OrganizationStructure::class, ['title', 'description'], 'is_active = 1', 'id', 20],
    ];
    $results = array_fill_keys(array_keys($sections), []);
    $counts = array_fill_keys(array_merge(['total'], array_keys($sections)), 0);
    $searchTerms = [];

    if ($query !== '' && mb_strlen($query) >= 2) {
        $searchTerms = normalize_arabic_search($query);
        foreach ($sections as $type => [$model, $fields, $cond, $order, $limit]) {
            if ($searchType !== 'all' && $searchType !== $type) {
                continue;
            }
            // نبحث أيضاً في الترجمات (title_fr، title_en ...): زائر الموقع الفرنسي يكتب بالفرنسية
            foreach (array_intersect($fields, $model::translatable()) as $f) {
                foreach (array_diff(array_keys(languages()), [default_lang()]) as $code) {
                    $fields[] = $f . '_' . $code;
                }
            }
            $params = [];
            $where = build_search_query($fields, $searchTerms, $params) . ' AND ' . $cond;
            $results[$type] = $model::where($where, $params, $order, $limit);
            $counts[$type] = count($results[$type]);
        }
        $counts['total'] = array_sum(array_diff_key($counts, ['total' => 0]));
    }

    render('search/search_results', [
        'query'        => $query,
        'search_type'  => $searchType,
        'results'      => $results,
        'counts'       => $counts,
        'page_title'   => $query !== '' ? __('نتائج البحث عن: {query}', ['query' => $query]) : __('البحث'),
        'search_terms' => $searchTerms,
    ]);
}
