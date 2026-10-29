<?php
/**
 * مركز التحميلات (downloads/views.py)
 */

function download_center(): void
{
    $category = query('category');
    $searchQuery = query('q');
    $where = 'is_active = 1';
    $params = [];
    if ($category !== '') {
        $where .= ' AND category = ?';
        $params[] = $category;
    }
    if ($searchQuery !== '') {
        $where .= ' AND (title LIKE ? OR description LIKE ?)';
        $like = DB::like($searchQuery);
        array_push($params, $like, $like);
    }
    $files = DownloadFile::where($where, $params, 'display_order, created_at DESC');

    $categoriesData = [];
    foreach (DownloadFile::choiceMap('category') as $code => $name) {
        $catFiles = array_values(array_filter($files, fn($f) => $f['category'] === $code));
        if ($catFiles) {
            $categoriesData[] = [
                'code'  => $code,
                'name'  => $name,
                'count' => count($catFiles),
                'files' => array_slice($catFiles, 0, 3),
            ];
        }
    }

    render('downloads/download_center', [
        'files'            => $files,
        'categories_data'  => $categoriesData,
        'categories'       => DownloadFile::choices('category'),
        'featured_files'   => array_slice(array_values(array_filter($files, fn($f) => $f['featured'])), 0, 4),
        'total_files'      => count($files),
        'total_downloads'  => array_sum(array_map(fn($f) => (int) $f['downloads_count'], $files)),
        'current_category' => $category,
        'search_query'     => $searchQuery,
        'page_title'       => __('مركز التحميلات'),
    ]);
}

function download_by_category(string $category): void
{
    $names = DownloadFile::choiceMap('category');
    render('downloads/download_by_category', [
        'files'      => DownloadFile::where('category = ? AND is_active = 1', [$category], 'display_order, created_at DESC'),
        'category'   => $category,
        'categories' => DownloadFile::choices('category'),
        'page_title' => $names[$category] ?? __('ملفات'),
    ]);
}

function download_file(string $slug): void
{
    $file = DownloadFile::firstWhere('slug = ? AND is_active = 1', [$slug]);
    if (!$file) {
        abort(404);
    }
    $path = UPLOAD_PATH . '/' . ltrim((string) $file['file'], '/');
    if (!is_file($path)) {
        abort(404, __('الملف غير موجود'));
    }
    $file->increment_downloads();
    Upload::send($path, basename((string) $file['file']));
}
