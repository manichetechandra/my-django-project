<?php
/**
 * لوحة الموظفين لمتابعة طلبات الطلاب (admin_dashboard, admin_applications_list, ... في students/views.py)
 */

/** بناء شرط الفلترة المشترك بين القائمة والتصدير */
function staff_applications_filter(): array
{
    $where = '1';
    $params = [];
    $status = query('status');
    if ($status !== '') {
        $where .= ' AND a.status = ?';
        $params[] = $status;
    }
    $type = query('type');
    if ($type !== '') {
        $where .= ' AND a.application_type_id = ?';
        $params[] = (int) $type;
    }
    $search = query('search');
    if ($search !== '') {
        $like = DB::like($search);
        $where .= ' AND (a.application_number LIKE ? OR s.full_name_arabic LIKE ? OR s.registration_number LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    return [$where, $params, $status, $type, $search];
}

/** جلب الطلبات مع الطالب ونوع الطلب والمراجع دفعة واحدة (مثل select_related) */
function staff_fetch_applications(string $where, array $params, string $limit = ''): array
{
    $rows = DB::all("SELECT a.*,
            s.id AS s_id, s.user_id AS s_user_id, s.registration_number AS s_registration_number, s.full_name_arabic AS s_full_name_arabic,
            s.phone AS s_phone, s.faculty AS s_faculty, s.department AS s_department, s.current_year AS s_current_year, s.photo AS s_photo,
            u.email AS u_email,
            t.id AS t_id, t.name AS t_name, t.icon AS t_icon, t.color AS t_color,
            r.id AS r_id, r.username AS r_username, r.first_name AS r_first_name, r.last_name AS r_last_name
        FROM student_applications a
        JOIN student_profiles s ON s.id = a.student_id
        JOIN users u ON u.id = s.user_id
        JOIN application_types t ON t.id = a.application_type_id
        LEFT JOIN users r ON r.id = a.reviewed_by_id
        WHERE $where ORDER BY a.submitted_at DESC, a.id DESC $limit", $params);
    $apps = [];
    foreach ($rows as $row) {
        $pick = function (string $prefix) use (&$row) {
            $out = [];
            foreach ($row as $k => $v) {
                if (strpos($k, $prefix) === 0) {
                    $out[substr($k, strlen($prefix))] = $v;
                    unset($row[$k]);
                }
            }
            return $out;
        };
        $student = $pick('s_');
        $user = $pick('u_');
        $type = $pick('t_');
        $reviewer = $pick('r_');
        $app = new StudentApplication($row);
        $sp = new StudentProfile($student);
        $sp->setRelation('user', new User(['id' => $student['user_id'], 'email' => $user['email']]));
        $app->setRelation('student', $sp);
        $app->setRelation('application_type', new ApplicationType($type));
        $app->setRelation('reviewed_by', $reviewer['id'] ? new User($reviewer) : null);
        $apps[] = $app;
    }
    return $apps;
}

function staff_dashboard(): void
{
    never_cache();
    require_staff();
    $c = DB::one("SELECT COUNT(*) AS total,
            SUM(status = 'pending') AS pending, SUM(status = 'under_review') AS under_review,
            SUM(status = 'approved') AS approved, SUM(status = 'rejected') AS rejected,
            SUM(submitted_at >= ?) AS today, SUM(submitted_at >= ?) AS week, SUM(submitted_at >= ?) AS month
        FROM student_applications", [date('Y-m-d 00:00:00'), date('Y-m-d H:i:s', strtotime('-7 days')), date('Y-m-d H:i:s', strtotime('-30 days'))]);

    $byType = ApplicationType::query('SELECT t.*, COUNT(a.id) AS total FROM application_types t
        JOIN student_applications a ON a.application_type_id = t.id GROUP BY t.id ORDER BY t.`order`, t.name');

    // آخر 7 أيام
    $daysLabels = [];
    $daysData = [];
    $daily = [];
    foreach (DB::all('SELECT DATE(submitted_at) AS d, COUNT(*) AS n FROM student_applications WHERE submitted_at >= ? GROUP BY DATE(submitted_at)', [date('Y-m-d', strtotime('-6 days'))]) as $r) {
        $daily[$r['d']] = (int) $r['n'];
    }
    $dayNames = ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'];
    for ($i = 6; $i >= 0; $i--) {
        $ts = strtotime("-$i days");
        $daysLabels[] = $dayNames[(int) date('w', $ts)] . ' ' . date('d/m', $ts);
        $daysData[] = $daily[date('Y-m-d', $ts)] ?? 0;
    }

    render('students/admin/dashboard', [
        'total_applications'   => (int) $c['total'],
        'pending_count'        => (int) $c['pending'],
        'under_review_count'   => (int) $c['under_review'],
        'approved_count'       => (int) $c['approved'],
        'rejected_count'       => (int) $c['rejected'],
        'today_applications'   => (int) $c['today'],
        'week_applications'    => (int) $c['week'],
        'month_applications'   => (int) $c['month'],
        'recent_applications'  => staff_fetch_applications('1', [], 'LIMIT 10'),
        'applications_by_type' => $byType,
        'total_students'       => StudentProfile::count('is_active = 1'),
        'page_title'           => 'لوحة تحكم المشرف',
        'chart_status_labels'  => ['قيد الانتظار', 'قيد المراجعة', 'معتمد', 'مرفوض'],
        'chart_status_data'    => [(int) $c['pending'], (int) $c['under_review'], (int) $c['approved'], (int) $c['rejected']],
        'chart_status_colors'  => ['#f39c12', '#3498db', '#2ecc71', '#e74c3c'],
        'chart_type_labels'    => array_map(fn($t) => $t['name'], $byType),
        'chart_type_data'      => array_map(fn($t) => (int) $t['total'], $byType),
        'chart_type_colors'    => array_map(fn($t) => $t['color'] ?: '#3498db', $byType),
        'chart_days_labels'    => $daysLabels,
        'chart_days_data'      => $daysData,
    ]);
}

function staff_applications_list(): void
{
    never_cache();
    $staff = require_staff();
    [$where, $params, $status, $type, $search] = staff_applications_filter();

    if (is_post()) {
        $action = (string) ($_POST['bulk_action'] ?? '');
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['selected_applications'] ?? []))));
        if (!$ids) {
            flash('warning', 'لم تقم بتحديد أي طلبات!');
        } elseif ($action === '') {
            flash('warning', 'لم تقم باختيار إجراء!');
        } else {
            $selected = StudentApplication::where('id IN (' . DB::in($ids) . ')', $ids);
            $count = count($selected);
            DB::transaction(function () use ($action, $selected, $staff, $count) {
                switch ($action) {
                    case 'approve':
                        foreach ($selected as $app) {
                            $app->change_status('approved', $staff);
                        }
                        flash('success', "✅ تم اعتماد $count طلب بنجاح!");
                        break;
                    case 'reject':
                        $notes = (string) ($_POST['bulk_notes'] ?? 'تم رفض الطلب');
                        foreach ($selected as $app) {
                            $app->update(['admin_notes' => $notes]);
                            $app->change_status('rejected', $staff);
                        }
                        flash('warning', "⚠️ تم رفض $count طلب");
                        break;
                    case 'review':
                        foreach ($selected as $app) {
                            $app->change_status('under_review');
                        }
                        flash('info', "📝 تم تغيير حالة $count طلب إلى \"قيد المراجعة\"");
                        break;
                    case 'pending':
                        foreach ($selected as $app) {
                            $app->change_status('pending');
                        }
                        flash('info', "⏳ تم تغيير حالة $count طلب إلى \"قيد الانتظار\"");
                        break;
                    case 'delete':
                        foreach ($selected as $app) {
                            foreach ($app['documents'] as $doc) {
                                Upload::delete($doc['document_file'], true);
                            }
                            $app->delete();
                        }
                        flash('success', "🗑️ تم حذف $count طلب");
                        break;
                    default:
                        flash('warning', 'إجراء غير معروف: ' . $action);
                }
            });
            redirect(url('students:admin_applications_list') . ($_GET ? '?' . http_build_query($_GET) : ''));
        }
    }

    $applications = staff_fetch_applications($where, $params);
    render('students/admin/applications_list', [
        'applications'      => $applications,
        'total_count'       => count($applications),
        'application_types' => ApplicationType::active(),
        'status_filter'     => $status,
        'type_filter'       => $type,
        'search_query'      => $search,
        'page_title'        => 'إدارة الطلبات',
    ]);
}

function staff_application_detail($pk): void
{
    never_cache();
    $staff = require_staff();
    $apps = staff_fetch_applications('a.id = ?', [(int) $pk]);
    if (!$apps) {
        abort(404);
    }
    $application = $apps[0];

    if (is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        $notes = trim((string) ($_POST['admin_notes'] ?? ''));
        $back = url('students:admin_application_detail', $pk);
        $app = StudentApplication::find($pk);
        switch ($action) {
            case 'approve':
                $app->change_status('approved', $staff, $notes);
                flash('success', 'تم اعتماد الطلب بنجاح وإرسال إشعار للطالب');
                break;
            case 'reject':
                $app->update(['admin_notes' => $notes]);
                $app->change_status('rejected', $staff);
                flash('warning', 'تم رفض الطلب وإرسال إشعار للطالب');
                break;
            case 'pending':
                $app->change_status('pending', null, $notes);
                flash('info', 'تم تغيير حالة الطلب إلى "قيد الانتظار" وإرسال إشعار للطالب');
                break;
            case 'review':
                $app->change_status('under_review', null, $notes);
                flash('info', 'تم تغيير حالة الطلب إلى "قيد المراجعة" وإرسال إشعار للطالب');
                break;
            case '':
                flash('warning', 'لم يتم تحديد الإجراء المطلوب');
                break;
            default:
                flash('warning', 'إجراء غير معروف: ' . $action);
        }
        redirect($back);
    }

    render('students/admin/application_detail', [
        'application' => $application,
        'student'     => $application['student'],
        'documents'   => ApplicationDocument::where('application_id = ?', [(int) $application['id']], 'uploaded_at DESC'),
        'page_title'  => 'تفاصيل الطلب - ' . $application['application_number'],
    ]);
}

function staff_export_applications_excel(): void
{
    never_cache();
    $staff = require_staff();
    [$where, $params] = staff_applications_filter();
    $apps = staff_fetch_applications($where, $params);
    $headers = ['#', 'رقم الطلب', 'اسم الطالب', 'رقم التسجيل', 'نوع الطلب', 'الحالة', 'تاريخ التقديم', 'تاريخ المراجعة',
                'راجعه', 'الهاتف', 'البريد الإلكتروني', 'الكلية', 'القسم', 'السنة الدراسية'];
    $rows = [];
    foreach ($apps as $i => $app) {
        $s = $app['student'];
        $r = $app['reviewed_by'];
        $rows[] = [
            $i + 1,
            (string) $app['application_number'],
            $s['full_name_arabic'],
            (string) $s['registration_number'],
            $app['application_type']['name'],
            $app->get_status_display(),
            format_date($app['submitted_at'], 'Y-m-d H:i'),
            format_date($app['reviewed_at'], 'Y-m-d H:i'),
            $r ? $r->get_full_name() : '',
            (string) $s['phone'],
            $s['user']['email'],
            $s['faculty'],
            $s['department'],
            $s->get_current_year_display(),
        ];
    }
    $stamp = date('Y-m-d_H-i');
    $exportedBy = $staff->display_name();

    if (!XlsxWriter::available()) {
        // بديل CSV إذا لم يكن امتداد ZipArchive مفعّلاً
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="applications_' . $stamp . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fputcsv($out, []);
        fputcsv($out, ['الإجمالي:', count($rows)]);
        fputcsv($out, ['تاريخ التصدير:', date('Y-m-d H:i')]);
        fputcsv($out, ['صُدّر بواسطة:', $exportedBy]);
        fclose($out);
        exit;
    }

    $x = new XlsxWriter('الطلبات');
    $head = $x->style(['bold' => true, 'size' => 12, 'color' => 'FFFFFF', 'fill' => '2C3E50', 'align' => 'center', 'border' => true]);
    $statusFills = ['pending' => 'FFF3CD', 'under_review' => 'D1ECF1', 'approved' => 'D4EDDA', 'rejected' => 'F8D7DA', 'cancelled' => 'E2E3E5'];
    foreach ($headers as $col => $h) {
        $x->setCell(1, $col + 1, $h, $head);
    }
    $x->setRowHeight(1, 25);
    foreach ($rows as $i => $row) {
        $r = $i + 2;
        $fill = $r % 2 === 0 ? 'FFFFFF' : 'F8F9FA';
        foreach ($row as $col => $value) {
            $c = $col + 1;
            $cellFill = $c === 6 ? ($statusFills[$apps[$i]['status']] ?? $fill) : $fill;
            $x->setCell($r, $c, $value, $x->style(['border' => true, 'fill' => $cellFill, 'align' => $c > 1 ? 'right' : 'center']));
        }
    }
    foreach ([8, 15, 25, 15, 20, 15, 18, 18, 20, 15, 25, 25, 25, 15] as $col => $w) {
        $x->setColumnWidth($col + 1, $w);
    }
    $bold = $x->style(['bold' => true]);
    $sr = count($rows) + 3;
    $x->setCell($sr, 1, 'الإجمالي:', $bold);
    $x->setCell($sr, 2, count($rows), $bold);
    $x->setCell($sr + 1, 1, 'تاريخ التصدير:');
    $x->setCell($sr + 1, 2, date('Y-m-d H:i'));
    $x->setCell($sr + 2, 1, 'صُدّر بواسطة:');
    $x->setCell($sr + 2, 2, $exportedBy);
    $x->download('applications_' . $stamp . '.xlsx');
}
