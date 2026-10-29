<?php
/**
 * الإقامات الجامعية (cities/views.py)
 */

function cite_list(): void
{
    render('cities/cite_list', [
        'cites'      => Cite::paginate('is_active = 1', [], '`order`, name', 12),
        'page_title' => __('الإقامات الجامعية'),
    ]);
}

function cite_detail(string $slug): void
{
    $cite = Cite::firstWhere('slug = ? AND is_active = 1', [$slug]);
    if (!$cite) {
        abort(404);
    }
    render('cities/cite_detail', [
        'cite'           => $cite,
        'other_cites'    => Cite::where('is_active = 1 AND id <> ?', [(int) $cite['id']], 'RAND()', 4),
        'gallery_images' => CiteImage::where('cite_id = ?', [(int) $cite['id']], '`order`, uploaded_at DESC'),
        'page_title'     => $cite['name'],
    ]);
}
