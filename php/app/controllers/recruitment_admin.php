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
    'complete' => 'إكمال الملف (المترشح)', 'convoke' => 'استدعاء', 'unconvoke' => 'إلغاء الاستدعاء',
    'result' => 'تسجيل النتيجة', 'publish' => 'نشر النتائج', 'unpublish' => 'إلغاء نشر النتائج',
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

/** أجل إكمال الملف الافتراضي: بعد 7 أيام */
function recruit_default_complete_until(): string
{
    return date('Y-m-d', strtotime('+' . CompetitionApplication::COMPLETE_DAYS . ' days'));
}

/**
 * تغيير حالة طلب (مع المراجع ووقت المراجعة + سجل الاطلاع).
 * «ملف ناقص»: يُحدَّد أجل يرفع المترشح قبله الوثائق الناقصة بنفسه ($completeUntil أو بعد 7 أيام).
 * سحب القبول من مترشح مستدعى يلغي استدعاءه.
 */
function recruit_set_status(CompetitionApplication $app, string $status, int $userId, ?string $completeUntil = null): bool
{
    $old = (string) $app['status'];
    if ($old === $status) {
        return false;
    }
    $data = ['status' => $status, 'reviewed_by_id' => $userId, 'reviewed_at' => now(), 'updated_at' => now()];
    $details = '';
    if ($status === 'incomplete') {
        $data['complete_until'] = $completeUntil ?? recruit_default_complete_until();
        $details = ' — أجل الإكمال: ' . format_date($data['complete_until'], 'd/m/Y');
    }
    $wasConvoked = $old === 'accepted' && !empty($app['exam_id']);
    if ($wasConvoked) {
        $data['exam_id'] = null;
        $data['convoked_at'] = null;
    }
    $app->update($data);
    $labels = CompetitionApplication::choiceMap('status');
    RecruitmentAudit::log('status', $app, null, ($labels[$old] ?? $old) . ' ← ' . ($labels[$status] ?? $status) . $details);
    if ($wasConvoked) {
        RecruitmentAudit::log('unconvoke', $app, null, 'بسبب تغيير الحالة');
    }
    return true;
}

/**
 * استدعاء مترشحين إلى موعد: الملفات المقبولة فقط، من نفس المسابقة.
 * رقم التسجيل يُمنح مرة واحدة لكل مترشح، بالتسلسل في المسابقة وحسب الترتيب الأبجدي للقب (001، 002...).
 * يرجع [عدد المستدعين, عدد المتجاهَلين]
 */
function recruit_convoke(CompetitionExam $exam, array $ids): array
{
    $cid = (int) $exam['competition_id'];
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [0, 0];
    }
    return DB::transaction(function () use ($exam, $cid, $ids) {
        // قفل صفوف المسابقة حتى لا يحصل مترشحان على نفس الرقم إن استدعى موظفان في نفس اللحظة
        $next = (int) DB::value('SELECT COALESCE(MAX(CAST(seat_number AS UNSIGNED)), 0) FROM competition_applications WHERE competition_id = ? FOR UPDATE', [$cid]) + 1;
        $apps = CompetitionApplication::where('id IN (' . DB::in($ids) . ') AND competition_id = ?', array_merge($ids, [$cid]), 'last_name, first_name, id');
        $done = 0;
        $skipped = count($ids) - count($apps);
        foreach ($apps as $app) {
            if ($app['status'] !== 'accepted') {
                $skipped++;
                continue;
            }
            if ((int) $app['exam_id'] === (int) $exam['id']) {
                continue; // مستدعى مسبقاً لنفس الموعد
            }
            $seat = trim((string) $app['seat_number']) !== '' ? (string) $app['seat_number'] : str_pad((string) $next++, 3, '0', STR_PAD_LEFT);
            $app->update(['exam_id' => (int) $exam['id'], 'seat_number' => $seat, 'convoked_at' => now(), 'updated_at' => now()]);
            RecruitmentAudit::log('convoke', $app, null, $exam->label() . ' — رقم التسجيل ' . $seat);
            $done++;
        }
        return [$done, $skipped];
    });
}

/** إلغاء استدعاء (رقم التسجيل يبقى محجوزاً للمترشح إن استُدعي من جديد) */
function recruit_unconvoke(array $ids): int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $n = 0;
    foreach ($ids ? CompetitionApplication::where('id IN (' . DB::in($ids) . ') AND exam_id IS NOT NULL', $ids) : [] as $app) {
        $exam = $app['exam'];
        $app->update(['exam_id' => null, 'convoked_at' => null, 'updated_at' => now()]);
        RecruitmentAudit::log('unconvoke', $app, null, $exam ? $exam->label() : '');
        $n++;
    }
    return $n;
}

/** تاريخ من خانة <input type="date"> (YYYY-MM-DD) أو null */
function recruit_parse_date(string $v): ?string
{
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    return $d && $d->format('Y-m-d') === $v ? $v : null;
}

/** العلامة من 20: «14,5» أو «14.50» → 14.50 | '' → null | غير صالحة → false */
function recruit_parse_score(string $v)
{
    $v = str_replace([',', ' '], ['.', ''], trim($v));
    if ($v === '') {
        return null;
    }
    if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $v) || (float) $v > 20) {
        return false;
    }
    return number_format((float) $v, 2, '.', '');
}

/** الترتيب: '' → null | عدد بين 1 و 65535 | غير صالح → false */
function recruit_parse_rank(string $v)
{
    $v = trim($v);
    if ($v === '') {
        return null;
    }
    return preg_match('/^\d{1,5}$/', $v) && (int) $v >= 1 && (int) $v <= 65535 ? (int) $v : false;
}

/**
 * تسجيل نتيجة مترشح (ملفه مقبول). يرجع null إن نجح، أو رسالة الخطأ.
 * $changed يصبح true إن تغيّر شيء (ويُسجَّل في سجل الاطلاع).
 */
function recruit_save_result(CompetitionApplication $app, string $result, string $score, string $rank, string $note, ?bool &$changed = null): ?string
{
    $changed = false;
    $results = CompetitionApplication::choiceMap('result');
    if ($result !== '' && !isset($results[$result])) {
        return 'نتيجة غير صالحة.';
    }
    $s = recruit_parse_score($score);
    if ($s === false) {
        return 'العلامة يجب أن تكون عدداً بين 0 و20 (مثل 14,50).';
    }
    $r = recruit_parse_rank($rank);
    if ($r === false) {
        return 'الترتيب يجب أن يكون عدداً صحيحاً موجباً.';
    }
    $note = mb_substr($note, 0, 500);
    $new = ['result' => $result === '' ? null : $result, 'score' => $s, 'result_rank' => $r, 'result_note' => $note === '' ? null : $note];
    $old = [
        'result' => ($app['result'] ?? '') === '' ? null : (string) $app['result'],
        'score' => $app['score'] === null ? null : number_format((float) $app['score'], 2, '.', ''),
        'result_rank' => $app['result_rank'] === null ? null : (int) $app['result_rank'],
        'result_note' => ($app['result_note'] ?? '') === '' ? null : (string) $app['result_note'],
    ];
    if ($new === $old) {
        return null;
    }
    if ($app['status'] !== 'accepted' && ($new['result'] !== null || $new['score'] !== null || $new['result_rank'] !== null)) {
        return 'تُسجَّل النتيجة فقط للمترشحين الذين قُبلت ملفاتهم (' . $app['tracking_code'] . ').';
    }
    $app->update($new + ['updated_at' => now()]);
    $parts = [$new['result'] !== null ? $results[$new['result']] : 'بلا نتيجة'];
    if ($new['score'] !== null) {
        $parts[] = 'العلامة ' . str_replace('.', ',', $new['score']);
    }
    if ($new['result_rank'] !== null) {
        $parts[] = 'الترتيب ' . $new['result_rank'];
    }
    RecruitmentAudit::log('result', $app, null, implode(' — ', $parts));
    $changed = true;
    return null;
}

function recruit_admin_applications(): void
{
    $user = recruit_admin_guard();
    [$where, $params, $active] = recruit_admin_filters();

    if (is_post()) {
        $back = url('admin:recruit_applications') . ($_GET ? '?' . http_build_query($_GET) : '');
        $ids = array_values(array_filter(array_map('intval', array_filter((array) ($_POST['ids'] ?? []), 'is_scalar'))));
        $do = post_text('do', 20);
        if (!$ids) {
            flash('warning', 'حدد طلباً واحداً على الأقل.');
        } elseif ($do === 'convoke') {
            $exam = CompetitionExam::find((int) post_text('exam_id', 10));
            if (!$exam || !isset($active['competition']) || (int) $exam['competition_id'] !== $active['competition']) {
                flash('warning', 'اختر موعد الاستدعاء (اختر المسابقة أولاً من التصفية).');
            } else {
                [$n, $skipped] = recruit_convoke($exam, $ids);
                flash($n ? 'success' : 'warning', 'تم استدعاء ' . $n . ' مترشح إلى «' . $exam->label() . '».'
                    . ($skipped ? ' تم تجاهل ' . $skipped . ' طلب لأن ملفه غير مقبول.' : ''));
            }
        } elseif ($do === 'unconvoke') {
            flash('success', 'تم إلغاء استدعاء ' . recruit_unconvoke($ids) . ' مترشح.');
        } else {
            $status = post_text('new_status', 30);
            if (!isset(CompetitionApplication::choiceMap('status')[$status])) {
                flash('warning', 'اختر الحالة الجديدة.');
            } else {
                $n = 0;
                foreach (CompetitionApplication::where('id IN (' . DB::in($ids) . ')', $ids) as $app) {
                    $n += recruit_set_status($app, $status, (int) $user['id']) ? 1 : 0;
                }
                flash('success', 'تم تغيير حالة ' . $n . ' طلب إلى «' . CompetitionApplication::choiceMap('status')[$status] . '».'
                    . ($status === 'incomplete' && $n ? ' أجل الإكمال: ' . format_date(recruit_default_complete_until(), 'd/m/Y') . ' (يمكن تعديله من صفحة كل طلب).' : ''));
            }
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
    $examIds = array_values(array_unique(array_filter(array_map(fn($a) => (int) $a['exam_id'], $page->items))));
    $examsById = [];
    foreach ($examIds ? CompetitionExam::where('id IN (' . DB::in($examIds) . ')', $examIds) : [] as $ex) {
        $examsById[(int) $ex['id']] = $ex;
    }
    admin_render('recruitment/applications', [
        'page_title'   => 'طلبات الترشح',
        'page'         => $page,
        'exams'        => $current ? $current['exams'] : [],
        'exams_by_id'  => $examsById,
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

/** صفحة الطلب ← نموذج «الاستدعاء والنتيجة» */
function recruit_admin_save_outcome(CompetitionApplication $app): void
{
    $back = url('admin:recruit_application', $app['id']) . '#outcome';
    $examId = (int) post_text('exam_id', 10);
    $changed = false;
    if ($examId !== (int) $app['exam_id']) {
        if ($examId === 0) {
            $changed = recruit_unconvoke([(int) $app['id']]) > 0;
        } else {
            $exam = CompetitionExam::firstWhere('id = ? AND competition_id = ?', [$examId, (int) $app['competition_id']]);
            if (!$exam) {
                flash('error', 'موعد غير صالح لهذه المسابقة.');
                redirect($back);
            }
            if ($app['status'] !== 'accepted') {
                flash('error', 'لا يُستدعى إلا مترشح قُبل ملفه. غيّر الحالة إلى «ملف مقبول» أولاً.');
                redirect($back);
            }
            [$n] = recruit_convoke($exam, [(int) $app['id']]);
            $changed = $n > 0;
        }
        $app = CompetitionApplication::find((int) $app['id']);
    }
    $error = recruit_save_result($app, post_text('result', 20), post_text('score', 10), post_text('result_rank', 6), post_text('result_note', 500), $resultChanged);
    if ($error !== null) {
        flash('error', $error);
    } elseif ($changed || $resultChanged) {
        flash('success', 'تم حفظ الاستدعاء والنتيجة لطلب ' . $app['tracking_code'] . '.');
    } else {
        flash('info', 'لا توجد تعديلات.');
    }
    redirect($back);
}

function recruit_admin_application($id): void
{
    $user = recruit_admin_guard();
    $app = CompetitionApplication::findOrFail($id);

    if (is_post() && post_text('form', 20) === 'outcome') {
        recruit_admin_save_outcome($app);
    }
    if (is_post()) {
        $status = post_text('status', 30);
        $message = post_text('candidate_message', 1000);
        $notes = post_text('internal_notes', 2000);
        if (!isset(CompetitionApplication::choiceMap('status')[$status])) {
            flash('error', 'حالة غير صالحة.');
            redirect_to('admin:recruit_application', $app['id']);
        }
        $until = null;
        if ($status === 'incomplete') {
            $raw = post_text('complete_until', 10);
            $until = $raw === '' ? null : recruit_parse_date($raw);
            if ($raw !== '' && ($until === null || $until < date('Y-m-d'))) {
                flash('error', 'أجل إكمال الملف يجب أن يكون تاريخاً صحيحاً اليوم أو بعده.');
                redirect_to('admin:recruit_application', $app['id']);
            }
        }
        $changed = recruit_set_status($app, $status, (int) $user['id'], $until);
        if (!$changed && $status === 'incomplete' && $until !== null && $until !== (string) $app['complete_until']) {
            $app->update(['complete_until' => $until, 'updated_at' => now()]);
            RecruitmentAudit::log('update', $app, null, 'أجل إكمال الملف: ' . format_date($until, 'd/m/Y'));
            $changed = true;
        }
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
        'results'        => CompetitionApplication::choiceMap('result'),
        'exams'          => $app['competition'] ? $app['competition']['exams'] : [],
        'default_until'  => recruit_default_complete_until(),
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
    $headers = ['#', 'رقم المتابعة', 'اللقب', 'الاسم', 'Nom', 'Prénom', 'الجنس', 'الرتبة', 'الشهادة', 'التخصص', 'سنة التخرج', 'الخبرة (سنوات)', 'الحالة', 'تاريخ الترشح',
        'الاستدعاء', 'رقم التسجيل', 'النتيجة', 'العلامة', 'الترتيب'];
    $results = CompetitionApplication::choiceMap('result');
    $examLabels = [];
    foreach (DB::all('SELECT DISTINCT exam_id FROM competition_applications WHERE exam_id IS NOT NULL AND ' . $where, $params) as $row) {
        $ex = CompetitionExam::find((int) $row['exam_id']);
        if ($ex) {
            $examLabels[(int) $ex['id']] = $ex->label();
        }
    }
    if ($full) {
        array_push($headers, 'رقم التعريف الوطني', 'تاريخ الميلاد', 'مكان الميلاد', 'الهاتف', 'البريد الإلكتروني', 'العنوان', 'الولاية', 'الخدمة الوطنية', 'الرسالة للمترشح', 'المسابقة');
    }
    $rows = [];
    foreach ($apps as $i => $a) {
        $row = [$i + 1, (string) $a['tracking_code'], $a['last_name'], $a['first_name'], $a['last_name_latin'], $a['first_name_latin'],
            CompetitionApplication::choiceLabel('gender', $a['gender']), $a->post_label(), $a['diploma'], $a['specialty'],
            (int) $a['graduation_year'], (int) $a['experience_years'], $statuses[$a['status']] ?? $a['status'], format_date($a['submitted_at'], 'Y-m-d H:i'),
            $examLabels[(int) $a['exam_id']] ?? '', (string) $a['seat_number'], $results[(string) $a['result']] ?? '', $a->score_text(),
            $a['result_rank'] === null ? '' : (int) $a['result_rank']];
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
        $x->setColumnWidth($col + 1, in_array($h, ['العنوان', 'المسابقة', 'الرسالة للمترشح', 'الرتبة', 'الشهادة', 'التخصص', 'الاستدعاء'], true) ? 32 : ($h === 'رقم التعريف الوطني' ? 22 : 15));
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

/**
 * نتائج مسابقة: جدول لكل المترشحين المقبولة ملفاتهم (النتيجة، العلامة، الترتيب، ملاحظة)
 * + ترتيب تلقائي حسب العلامة + نشر النتائج للمترشحين (كلٌّ يرى نتيجته فقط في صفحة متابعة طلبه).
 */
function recruit_admin_results(): void
{
    recruit_admin_guard();
    $c = ($cid = (int) query('competition')) > 0 ? Competition::find($cid) : null;
    if (!$c) {
        $rows = DB::all("SELECT c.id, COUNT(a.id) accepted, SUM(a.result IS NOT NULL) with_result
                           FROM competitions c LEFT JOIN competition_applications a ON a.competition_id = c.id AND a.status = 'accepted'
                          GROUP BY c.id");
        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row['id']] = ['accepted' => (int) $row['accepted'], 'with_result' => (int) $row['with_result']];
        }
        admin_render('recruitment/results', [
            'page_title'   => 'نتائج المسابقات',
            'c'            => null,
            'competitions' => Competition::all('closes_at DESC, id DESC'),
            'stats'        => $stats,
        ]);
        return;
    }
    $cid = (int) $c['id'];
    $postId = (int) query('post');
    $back = url('admin:recruit_results') . '?' . http_build_query(array_filter(['competition' => $cid, 'post' => $postId ?: null]));

    if (is_post()) {
        $action = post_text('action', 20);
        if ($action === 'publish' || $action === 'unpublish') {
            $withResult = (int) DB::value("SELECT COUNT(*) FROM competition_applications WHERE competition_id = ? AND status = 'accepted' AND result IS NOT NULL", [$cid]);
            if ($action === 'publish' && $withResult === 0) {
                flash('warning', 'سجّل نتائج المترشحين أولاً ثم انشرها.');
            } elseif ($action === 'publish') {
                $c->update(['results_published_at' => now()]);
                RecruitmentAudit::log('publish', null, $cid, $c['title'] . ' — ' . $withResult . ' نتيجة');
                flash('success', 'نُشرت النتائج: كل مترشح يرى الآن نتيجته في صفحة متابعة طلبه ويطبع إشعار النتيجة.');
            } else {
                $c->update(['results_published_at' => null]);
                RecruitmentAudit::log('unpublish', null, $cid, (string) $c['title']);
                flash('success', 'أُلغي نشر النتائج: لم تعد تظهر للمترشحين.');
            }
        } elseif ($action === 'autorank') {
            // الترتيب حسب العلامة داخل كل رتبة (تساوي العلامة = نفس الترتيب: 1، 2، 2، 4)
            $n = 0;
            DB::transaction(function () use ($cid, &$n) {
                $byPost = [];
                foreach (CompetitionApplication::where("competition_id = ? AND status = 'accepted' AND score IS NOT NULL AND (result IS NULL OR result <> 'absent')", [$cid], 'post_id, score DESC, id') as $a) {
                    $byPost[(int) $a['post_id']][] = $a;
                }
                foreach ($byPost as $apps) {
                    $rank = 0;
                    $prev = null;
                    foreach ($apps as $i => $a) {
                        if ($prev === null || (string) $a['score'] !== $prev) {
                            $rank = $i + 1;
                            $prev = (string) $a['score'];
                        }
                        if ((int) $a['result_rank'] !== $rank) {
                            $a->update(['result_rank' => $rank, 'updated_at' => now()]);
                            $n++;
                        }
                    }
                }
                DB::query("UPDATE competition_applications SET result_rank = NULL WHERE competition_id = ? AND result = 'absent' AND result_rank IS NOT NULL", [$cid]);
            });
            RecruitmentAudit::log('result', null, $cid, 'ترتيب تلقائي حسب العلامة — ' . $n . ' تعديل');
            flash('success', 'تم الترتيب حسب العلامة داخل كل رتبة (' . $n . ' تعديل).');
        } else {
            $saved = 0;
            $errors = [];
            foreach ((array) ($_POST['r'] ?? []) as $id => $vals) {
                if (!is_array($vals)) {
                    continue;
                }
                $app = CompetitionApplication::firstWhere('id = ? AND competition_id = ?', [(int) $id, $cid]);
                if (!$app) {
                    continue;
                }
                $v = fn($k, $max) => isset($vals[$k]) && is_scalar($vals[$k]) ? mb_substr(trim((string) $vals[$k]), 0, $max) : '';
                $err = recruit_save_result($app, $v('result', 20), $v('score', 10), $v('rank', 6), $v('note', 500), $changed);
                if ($err !== null) {
                    $errors[] = $app['tracking_code'] . ' (' . $app->full_name() . '): ' . $err;
                } elseif ($changed) {
                    $saved++;
                }
            }
            foreach (array_slice($errors, 0, 10) as $e) {
                flash('error', $e);
            }
            flash($saved ? 'success' : 'info', $saved ? 'تم حفظ نتائج ' . $saved . ' مترشح.' : 'لا توجد تعديلات.');
        }
        redirect($back);
    }

    $where = "competition_id = ? AND status = 'accepted'";
    $params = [$cid];
    if ($postId > 0) {
        $where .= ' AND post_id = ?';
        $params[] = $postId;
    }
    $apps = CompetitionApplication::where($where, $params, 'post_id, (seat_number IS NULL), CAST(seat_number AS UNSIGNED), last_name, first_name', 2000);
    $summary = [];
    foreach (DB::all("SELECT COALESCE(result, '') r, COUNT(*) n FROM competition_applications WHERE competition_id = ? AND status = 'accepted' GROUP BY result", [$cid]) as $row) {
        $summary[$row['r']] = (int) $row['n'];
    }
    $exams = [];
    foreach ($c['exams'] as $ex) {
        $exams[(int) $ex['id']] = $ex;
    }
    admin_render('recruitment/results', [
        'page_title' => 'نتائج المسابقة',
        'c'          => $c,
        'apps'       => $apps,
        'posts'      => $c['posts'],
        'post_id'    => $postId,
        'exams'      => $exams,
        'results'    => CompetitionApplication::choiceMap('result'),
        'summary'    => $summary,
    ]);
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
