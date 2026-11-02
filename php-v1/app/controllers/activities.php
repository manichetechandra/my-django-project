<?php
/**
 * النشاطات الثقافية والرياضية (activities/views.py)
 */

function activity_list(): void
{
    $where = 'is_active = 1';
    $params = [];
    $activityType = query('type');
    if ($activityType !== '') {
        $where .= ' AND activity_type = ?';
        $params[] = $activityType;
    }
    $searchQuery = query('q');
    if ($searchQuery !== '') {
        $where .= ' AND (title LIKE ? OR description LIKE ? OR location LIKE ?)';
        $like = DB::like($searchQuery);
        array_push($params, $like, $like, $like);
    }

    render('activities/activity_list', [
        'activities'     => Activity::paginate($where, $params, '`date` DESC, created_at DESC', 9),
        'activity_types' => Activity::choices('activity_type'),
        'selected_type'  => $activityType,
        'search_query'   => $searchQuery,
        'page_title'     => 'النشاطات الثقافية والرياضية',
    ]);
}

function activity_detail(string $slug): void
{
    $activity = Activity::firstWhere('slug = ? AND is_active = 1', [$slug]);
    if (!$activity) {
        abort(404);
    }
    DB::query('UPDATE activities SET views_count = views_count + 1 WHERE id = ?', [(int) $activity['id']]);
    $activity['views_count'] = (int) $activity['views_count'] + 1;

    render('activities/activity_detail', [
        'activity'           => $activity,
        'related_activities' => Activity::where('is_active = 1 AND activity_type = ? AND id <> ?', [$activity['activity_type'], (int) $activity['id']], '`date` DESC', 4),
        'gallery_images'     => ActivityImage::where('activity_id = ?', [(int) $activity['id']], '`order`, uploaded_at DESC'),
        'page_title'         => $activity['title'],
    ]);
}
