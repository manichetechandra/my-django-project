<?php
/**
 * المصالح والأقسام (departements/views.py)
 */

function departement_list(): void
{
    render('departements/departement_list', [
        'departements' => Departement::where('is_active = 1', [], '`order`, name'),
        'page_title'   => __('أقسام المديرية'),
    ]);
}

function departement_detail(string $slug): void
{
    $departement = Departement::firstWhere('slug = ? AND is_active = 1', [$slug]);
    if (!$departement) {
        abort(404);
    }
    render('departements/departement_detail', [
        'departement'        => $departement,
        'other_departements' => Departement::where('is_active = 1 AND id <> ?', [(int) $departement['id']], 'RAND()', 3),
        'page_title'         => $departement['name'],
    ]);
}
