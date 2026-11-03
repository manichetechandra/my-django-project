<?php
/**
 * لوحة الإدارة: دراسة طلبات الترشح للمسابقات (/admin/recruitment/...).
 *
 * - للمدير العام وللموظفين الذين منحهم «صلاحية مسابقات التوظيف» فقط (حتى الموظف العادي لا يرى هذه الصفحات).
 * - كل اطلاع على ملف مترشح أو وثيقة، وكل تغيير حالة، وكل تصدير يُسجَّل في سجل الاطلاع (recruitment_audit).
 * - الوثائق تُفك تشفيرها في الذاكرة فقط وتُرسل بلا تخزين في المتصفح، مع التحقق من بصمتها (sha256).
 */

/** وصف أحداث سجل الاطلاع */
const RECRUIT_AUDIT_ACTIONS = [
    'submit' => 'إرسال طلب (المترشح)', 'track' => 'متابعة الطلب (المترشح)', 'view' => 'الاطلاع على ملف مترشح',
    'file' => 'فتح وثيقة', 'status' => 'تغيير الحالة', 'update' => 'تعديل الرسالة أو الملاحظات',
    'export' => 'تصدير قائمة', 'purge' => 'حذف معطيات مسابقة',
];

function recruit_admin_guard(): User
{
    $u = admin_guard();
    if (!can_recruit()) {
        security_log('recruitment_forbidden', ['user' => (string) $u['username'], 'path' => request_path()]);
        abort(403, 'ملفات المترشحين متاحة فقط للمدير العام وللموظفين الذين لهم «صلاحية مسابقات التوظيف».');
    }
    return $u;
}

/** عدد الطلبات قيد الانتظار (للقائمة الجانبية) */
function recruit_pending_count(): int
{
    try {
        return CompetitionApplication::count("status = 'pending'");
    } catch (Throwable $e) {
        return 0;
    }
}

/** شروط التصفية من الرابط: ?competition=&post=&status=&q= */
function recruit_admin_filters(): array
{
    $where = '1';
    $params = [];
    $active = [];
    if (($cid = (int) query('competition')) > 0) {
        $where .= ' AND competition_id = ?';
        $params[] = $cid;
        $active['competition'] = $cid;
        if (($pid = (int) query('post')) > 0) {
            $where .= ' AND post_id = ?';
            $params[] = $pid;
            $active['post'] = $pid;
        }
    }
    $status = query('status');
    if ($status !== '' && isset(CompetitionApplication::choiceMap('status')[$status])) {
        $where .= ' AND status = ?';
        $params[] = $status;
        $active['status'] = $status;
    }
    $q = mb_substr(query('q'), 0, 100);
    if ($q !== '') {
        $active['q'] = $q;
        $digits = preg_replace('/\s+/', '', $q);
        if (preg_match('/^\d{18}$/', $digits)) {
            // البحث برقم التعريف دون حفظه: نقارن بصمته
            $where .= ' AND nin_hash = ?';
            $params[] = Crypto::blindIndex($digits);
        } elseif (($app = CompetitionApplication::findByTracking($q)) !== null) {
            $where .= ' AND id = ?';
            $params[] = (int) $app['id'];
        } else {
            $parts = [];
            foreach (['last_name', 'first_name', 'last_name_latin', 'first_name_latin', 'diploma', 'specialty', 'tracking_code'] as $col) {
                $parts[] = "`$col` LIKE ?";
                $params[] = DB::like($q);
            }
            $where .= ' AND (' . implode(' OR ', $parts) . ')';
        }
    }
    return [$where, $params, $active];
}

/** وصف التصفية الحالية (لسجل الاطلاع) */
function recruit_filters_text(array $active): string
{
    $parts = [];
    if (isset($active['competition'])) {
        $parts[] = 'المسابقة #' . $active['competition'];
    }
    if (isset($active['status'])) {
        $parts[] = 'الحالة: ' . (CompetitionApplication::choiceMap('status')[$active['status']] ?? $active['status']);
    }
    if (isset($active['q'])) {
        $parts[] = 'بحث';
    }
    return $parts ? implode('، ', $parts) : 'كل الطلبات';
}

/** تغيير حالة طلب (مع المراجع ووقت المراجعة + سجل الاطلاع) */
function recruit_set_status(CompetitionApplication $app, string $status, int $userId): bool
{
    $old = (string) $app['status'];
    if ($old === $status) {
        return false;
    }
    $app->update(['status' => $status, 'reviewed_by_id' => $userId, 'reviewed_at' => now(), 'updated_at' => now()]);
    $labels = CompetitionApplication::choiceMap('status');
    RecruitmentAudit::log('status', $app, null, ($labels[$old] ?? $old) . ' ← ' . ($labels[$status] ?? $status));
    return true;
}

function recruit_admin_applications(): void
{
    $user = recruit_admin_guard();
    [$where, $params, $active] = recruit_admin_filters();

    if (is_post()) {
        $back = url('admin:recruit_applications') . ($_GET ? '?' . http_build_query($_GET) : '');
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $status = post_text('new_status', 30);
        if (!$ids) {
            flash('warning', 'حدد طلباً واحداً على الأقل.');
        } elseif (!isset(CompetitionApplication::choiceMap('status')[$status])) {
            flash('warning', 'اختر الحالة الجديدة.');
        } else {
            $n = 0;
            foreach (CompetitionApplication::where('id IN (' . DB::in($ids) . ')', $ids) as $app) {
                $n += recruit_set_status($app, $status, (int) $user['id']) ? 1 : 0;
            }
            flash('success', 'تم تغيير حالة ' . $n . ' طلب إلى «' . CompetitionApplication::choiceMap('status')[$status] . '».');
        }
        redirect($back);
    }

    $competitions = Competition::all('closes_at DESC, id DESC');
    $current = isset($active['competition']) ? Competition::find($active['competition']) : null;
    $counts = [];
    foreach (DB::all('SELECT status, COUNT(*) n FROM competition_applications' . ($current ? ' WHERE competition_id = ?' : '') . ' GROUP BY status',
        $current ? [(int) $current['id']] : []) as $row) {
        $counts[$row['status']] = (int) $row['n'];
    }
    $page = CompetitionApplication::paginate($where, $params, 'submitted_at DESC, id DESC', 30);
    $ids = array_map(fn($a) => (int) $a['id'], $page->items);
    $docCounts = [];
    foreach ($ids ? DB::all('SELECT application_id, COUNT(*) n FROM competition_application_files WHERE application_id IN (' . DB::in($ids) . ') GROUP BY application_id', $ids) : [] as $row) {
        $docCounts[(int) $row['application_id']] = (int) $row['n'];
    }
    admin_render('recruitment/applications', [
        'page_title'   => 'طلبات الترشح',
        'page'         => $page,
        'doc_counts'   => $docCounts,
        'active'       => $active,
        'competitions' => $competitions,
        'current'      => $current,
        'posts'        => $current ? $current['posts'] : [],
        'counts'       => $counts,
        'statuses'     => CompetitionApplication::choiceMap('status'),
        'key_backup'   => is_superuser() && !trim((string) config('recruitment.key', '')) && Crypto::hasKey(),
    ]);
}

function recruit_admin_application($id): void
{
    $user = recruit_admin_guard();
    $app = CompetitionApplication::findOrFail($id);

    if (is_post()) {
        $status = post_text('status', 30);
        $message = post_text('candidate_message', 1000);
        $notes = post_text('internal_notes', 2000);
        if (!isset(CompetitionApplication::choiceMap('status')[$status])) {
            flash('error', 'حالة غير صالحة.');
            redirect_to('admin:recruit_application', $app['id']);
        }
        $changed = recruit_set_status($app, $status, (int) $user['id']);
        if ($message !== (string) $app['candidate_message'] || $notes !== (string) $app['internal_notes']) {
            $what = [];
            if ($message !== (string) $app['candidate_message']) {
                $what[] = 'الرسالة للمترشح';
            }
            if ($notes !== (string) $app['internal_notes']) {
                $what[] = 'الملاحظات الداخلية';
            }
            $app->update(['candidate_message' => $message === '' ? null : $message, 'internal_notes' => $notes === '' ? null : $notes, 'updated_at' => now()]);
            RecruitmentAudit::log('update', $app, null, implode(' و', $what));
            $changed = true;
        }
        flash($changed ? 'success' : 'info', $changed ? 'تم حفظ التعديلات على طلب ' . $app['tracking_code'] . '.' : 'لا توجد تعديلات.');
        redirect_to('admin:recruit_application', $app['id']);
    }

    $personal = null;
    $decryptError = null;
    try {
        $personal = $app->personal();
    } catch (Throwable $e) {
        $decryptError = $e->getMessage();
        security_log('recruitment_decrypt_failed', ['application' => (int) $app['id']]);
    }
    RecruitmentAudit::log('view', $app);
    admin_render('recruitment/application', [
        'page_title'     => 'طلب ترشح ' . $app['tracking_code'],
        'app'            => $app,
        'c'              => $app['competition'],
        'personal'       => $personal,
        'decrypt_error'  => $decryptError,
        'files'          => $app['files'],
        'statuses'       => CompetitionApplication::choiceMap('status'),
        'history'        => RecruitmentAudit::where('application_id = ?', [(int) $app['id']], 'created_at DESC, id DESC', 50),
        'actions'        => RECRUIT_AUDIT_ACTIONS,
        'neighbours'     => [
            'prev' => DB::value('SELECT id FROM competition_applications WHERE competition_id = ? AND id < ? ORDER BY id DESC LIMIT 1', [(int) $app['competition_id'], (int) $app['id']]),
            'next' => DB::value('SELECT id FROM competition_applications WHERE competition_id = ? AND id > ? ORDER BY id LIMIT 1', [(int) $app['competition_id'], (int) $app['id']]),
        ],
    ]);
}

/** وثيقة مترشح: فك التشفير في الذاكرة + التحقق من البصمة + إرسال بلا تخزين */
function recruit_admin_file($id): void
{
    recruit_admin_guard();
    $f = CandidateFile::findOrFail($id);
    $app = $f['application'];
    $path = $app ? $f->full_path((int) $app['competition_id']) : null;
    if (!$path) {
        abort(404, 'الملف غير موجود على الخادم.');
    }
    try {
        $data = Crypto::decryptFile($path, CandidateFile::cryptoContext((int) $app['id'], (string) $f['stored_name']));
    } catch (Throwable $e) {
        security_log('recruitment_decrypt_failed', ['file' => (int) $f['id']]);
        abort(404, 'تعذر فك تشفير الملف: مفتاح التشفير غير مطابق أو الملف تالف.');
    }
    if (!hash_equals((string) $f['sha256'], hash('sha256', $data))) {
        security_log('recruitment_integrity_failed', ['file' => (int) $f['id']]);
        abort(404, 'فشل التحقق من سلامة الملف.');
    }
    $download = query('dl') === '1';
    RecruitmentAudit::log('file', $app, null, ($download ? 'تحميل: ' : 'عرض: ') . $f['label'] . ' — ' . $f['original_name']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $name = $app['tracking_code'] . ' - ' . $f['original_name'];
    $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $app['tracking_code'] . '_document.pdf');
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($data));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
    // عارض PDF في المتصفح يحتاج object-src: نسمح بالموقع نفسه فقط
    header("Content-Security-Policy: default-src 'none'; object-src 'self'; style-src 'unsafe-inline'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'");
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    echo $data;
    exit;
}

/** تصدير قائمة الطلبات (حسب التصفية الحالية): مختصرة بلا معطيات حساسة، أو كاملة */
function recruit_admin_export(): void
{
    $user = recruit_admin_guard();
    [$where, $params, $active] = recruit_admin_filters();
    $full = query('mode') === 'full';
    $apps = CompetitionApplication::where($where, $params, 'competition_id, post_id, last_name, first_name', 10000);
    $statuses = CompetitionApplication::choiceMap('status');
    $headers = ['#', 'رقم المتابعة', 'اللقب', 'الاسم', 'Nom', 'Prénom', 'الجنس', 'الرتبة', 'الشهادة', 'التخصص', 'سنة التخرج', 'الخبرة (سنوات)', 'الحالة', 'تاريخ الترشح'];
    if ($full) {
        array_push($headers, 'رقم التعريف الوطني', 'تاريخ الميلاد', 'مكان الميلاد', 'الهاتف', 'البريد الإلكتروني', 'العنوان', 'الولاية', 'الخدمة الوطنية', 'الرسالة للمترشح', 'المسابقة');
    }
    $rows = [];
    foreach ($apps as $i => $a) {
        $row = [$i + 1, (string) $a['tracking_code'], $a['last_name'], $a['first_name'], $a['last_name_latin'], $a['first_name_latin'],
            CompetitionApplication::choiceLabel('gender', $a['gender']), $a->post_label(), $a['diploma'], $a['specialty'],
            (int) $a['graduation_year'], (int) $a['experience_years'], $statuses[$a['status']] ?? $a['status'], format_date($a['submitted_at'], 'Y-m-d H:i')];
        if ($full) {
            try {
                $p = $a->personal();
                array_push($row, (string) $p['nin'], (string) $p['birth_date'], (string) $p['birth_place'], (string) $p['phone'], (string) $p['email'],
                    (string) $p['address'], (string) $p['wilaya'], CompetitionApplication::choiceLabel('national_service', $p['national_service'] ?? ''));
            } catch (Throwable $e) {
                array_push($row, 'تعذر فك التشفير', '', '', '', '', '', '', '');
            }
            array_push($row, (string) $a['candidate_message'], (string) ($a['competition']['title'] ?? ''));
        }
        $rows[] = $row;
    }
    RecruitmentAudit::log('export', null, $active['competition'] ?? null, ($full ? 'قائمة كاملة' : 'قائمة مختصرة') . ' — ' . count($rows) . ' طلب — ' . recruit_filters_text($active));
    $stamp = date('Y-m-d_H-i');
    $title = 'طلبات الترشح';

    if (!XlsxWriter::available()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('Content-Disposition: attachment; filename="candidatures_' . $stamp . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map('csv_safe', $row));
        }
        fclose($out);
        exit;
    }
    $x = new XlsxWriter($title, true);
    $head = $x->style(['bold' => true, 'size' => 11, 'color' => 'FFFFFF', 'fill' => '2C3E50', 'align' => 'center', 'border' => true]);
    $cell = $x->style(['border' => true]);
    $fills = ['pending' => 'FFF3CD', 'under_review' => 'D1ECF1', 'incomplete' => 'F8D7DA', 'accepted' => 'D4EDDA', 'rejected' => 'E2E3E5'];
    foreach ($headers as $col => $h) {
        $x->setCell(1, $col + 1, $h, $head);
        $x->setColumnWidth($col + 1, in_array($h, ['العنوان', 'المسابقة', 'الرسالة للمترشح', 'الرتبة', 'الشهادة', 'التخصص'], true) ? 32 : ($h === 'رقم التعريف الوطني' ? 22 : 15));
    }
    $x->setRowHeight(1, 24);
    foreach ($rows as $i => $row) {
        $status = array_search($row[12], $statuses, true) ?: '';
        $statusStyle = $x->style(['border' => true, 'fill' => $fills[$status] ?? 'FFFFFF', 'align' => 'center']);
        foreach ($row as $col => $v) {
            // رقم التعريف والهاتف كنص (لا يتحوّل إلى رقم علمي أو يفقد الصفر الأول)
            $x->setCell($i + 2, $col + 1, is_int($v) ? $v : (string) $v, $col === 12 ? $statusStyle : $cell);
        }
    }
    $x->download('candidatures_' . $stamp . '.xlsx');
}

/** سجل الاطلاع (للمدير العام فقط) */
function recruit_admin_audit(): void
{
    recruit_admin_guard();
    if (!is_superuser()) {
        abort(403, 'سجل الاطلاع للمدير العام فقط.');
    }
    $where = '1';
    $params = [];
    $action = query('action');
    if (isset(RECRUIT_AUDIT_ACTIONS[$action])) {
        $where .= ' AND action = ?';
        $params[] = $action;
    }
    if (($uid = (int) query('user')) > 0) {
        $where .= ' AND user_id = ?';
        $params[] = $uid;
    }
    if (($aid = (int) query('application')) > 0) {
        $where .= ' AND application_id = ?';
        $params[] = $aid;
    }
    $page = RecruitmentAudit::paginate($where, $params, 'created_at DESC, id DESC', 50);
    $ids = array_values(array_unique(array_filter(array_map(fn($r) => (int) $r['application_id'], $page->items))));
    $codes = [];
    foreach ($ids ? DB::all('SELECT id, tracking_code FROM competition_applications WHERE id IN (' . DB::in($ids) . ')', $ids) : [] as $row) {
        $codes[(int) $row['id']] = $row['tracking_code'];
    }
    admin_render('recruitment/audit', [
        'page_title' => 'سجل الاطلاع على ملفات المترشحين',
        'page'       => $page,
        'codes'      => $codes,
        'actions'    => RECRUIT_AUDIT_ACTIONS,
        'users'      => DB::all("SELECT DISTINCT user_id, username FROM recruitment_audit WHERE user_id IS NOT NULL ORDER BY username"),
        'filter'     => ['action' => $action, 'user' => $uid, 'application' => $aid],
    ]);
}

/** حذف معطيات المترشحين ووثائقهم نهائياً بعد انتهاء مسابقة (للمدير العام — القانون 18-07: لا نحتفظ بالمعطيات أكثر من اللازم) */
function recruit_admin_purge($id): void
{
    recruit_admin_guard();
    if (!is_superuser()) {
        abort(403, 'حذف معطيات المترشحين للمدير العام فقط.');
    }
    $c = Competition::findOrFail($id);
    $count = $c->applications_count();
    $allowed = in_array($c->phase(), ['closed', 'cancelled'], true) || $c['status'] === 'draft';
    if (is_post()) {
        $confirm = mb_strtolower(post_text('confirm', 20));
        if (!$allowed) {
            flash('error', 'لا يمكن حذف المعطيات قبل انتهاء أجل الترشح.');
        } elseif (!in_array($confirm, ['حذف', 'delete'], true)) {
            flash('error', 'اكتب كلمة «حذف» في خانة التأكيد.');
        } else {
            $cid = (int) $c['id'];
            $deleted = 0;
            foreach (CompetitionApplication::where('competition_id = ?', [$cid]) as $app) {
                foreach ($app['files'] as $f) {
                    if ($p = $f->full_path($cid)) {
                        @unlink($p);
                    }
                }
                $deleted++;
            }
            DB::delete('competition_applications', 'competition_id = ?', [$cid]);
            @rmdir(CandidateFile::dir($cid));
            RecruitmentAudit::log('purge', null, $cid, 'حذف ' . $deleted . ' طلب ووثائقه — ' . $c['title']);
            flash('success', 'تم حذف ' . $deleted . ' طلب ترشح ووثائقه نهائياً. يمكنك الآن حذف المسابقة نفسها إن أردت.');
            redirect(url('admin:recruit_applications') . '?competition=' . $cid);
        }
        redirect_to('admin:recruit_purge', $c['id']);
    }
    admin_render('recruitment/purge', [
        'page_title' => 'حذف معطيات المترشحين',
        'c'          => $c,
        'count'      => $count,
        'allowed'    => $allowed,
        'files'      => (int) DB::value('SELECT COUNT(*) FROM competition_application_files f JOIN competition_applications a ON a.id = f.application_id WHERE a.competition_id = ?', [(int) $c['id']]),
    ]);
}
