<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<ol class="breadcrumb">
    <li><a href="<?= e(url('admin:index')) ?>">لوحة الإدارة</a></li>
    <li><a href="<?= e(url('admin:recruit_applications') . '?competition=' . (int) $app['competition_id']) ?>">طلبات الترشح</a></li>
    <li class="active"><?= e($app['tracking_code']) ?></li>
</ol>
<div class="admin-header">
    <h1 class="admin-title"><i class="fa fa-id-card-o"></i> <?= e($app->full_name()) ?>
        <span class="label label-<?= e($app->status_color()) ?> recruit-status-big"><?= e($app->get_status_display()) ?></span>
    </h1>
    <div>
        <?php // القائمة مرتبة من الأحدث إلى الأقدم: «السابق» = الأحدث، «التالي» = الأقدم ?>
        <?php if ($neighbours['next']): ?><a class="btn btn-default" href="<?= e(url('admin:recruit_application', $neighbours['next'])) ?>"><i class="fa fa-arrow-right"></i> الطلب السابق</a><?php endif; ?>
        <?php if ($neighbours['prev']): ?><a class="btn btn-default" href="<?= e(url('admin:recruit_application', $neighbours['prev'])) ?>">الطلب التالي <i class="fa fa-arrow-left"></i></a><?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-trophy"></i> المسابقة والرتبة</strong></div>
            <table class="table recruit-kv">
                <tr><th>رقم المتابعة</th><td><span class="recruit-code"><?= e($app['tracking_code']) ?></span></td></tr>
                <tr><th>المسابقة</th><td><?php if ($c): ?><a href="<?= e(url('admin:edit', 'competitions', $c['id'])) ?>"><?= e($c['title']) ?></a><?= $c['reference'] !== '' ? ' <span class="text-muted">(' . e($c['reference']) . ')</span>' : '' ?><?php endif; ?></td></tr>
                <tr><th>الرتبة المترشح لها</th><td><strong><?= e($app->post_label()) ?></strong></td></tr>
                <tr><th>تاريخ الترشح</th><td><?= e(format_date($app['submitted_at'], 'd/m/Y H:i')) ?></td></tr>
                <?php if ($app['reviewed_at']): ?>
                    <tr><th>آخر مراجعة</th><td><?= e(format_date($app['reviewed_at'], 'd/m/Y H:i')) ?><?= $app['reviewed_by'] ? ' — ' . e($app['reviewed_by']->display_name()) : '' ?></td></tr>
                <?php endif; ?>
                <?php if ($app['status'] === 'incomplete' && $app['complete_until']): ?>
                    <tr><th>أجل إكمال الملف</th><td><?= e(format_date($app['complete_until'], 'd/m/Y')) ?>
                        <?= $app->can_complete() ? '<span class="label label-info">المترشح يستطيع رفع الوثائق الناقصة</span>' : '<span class="label label-default">انتهى الأجل</span>' ?></td></tr>
                <?php endif; ?>
                <?php if ($app['completed_at']): ?>
                    <tr><th>أكمل المترشح ملفه</th><td><?= e(format_date($app['completed_at'], 'd/m/Y H:i')) ?></td></tr>
                <?php endif; ?>
                <?php if ($app->is_convoked()): ?>
                    <tr><th>الاستدعاء</th><td><i class="fa fa-calendar-check-o text-success"></i> <?= e($app['exam']->label()) ?> — رقم التسجيل <strong class="recruit-code"><?= e($app['seat_number']) ?></strong></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="panel panel-default admin-panel recruit-private">
            <div class="panel-heading"><strong><i class="fa fa-lock"></i> المعطيات الشخصية</strong>
                <span class="small text-muted">— محفوظة مشفّرة، وقد سُجّل اطلاعك عليها</span></div>
            <?php if ($decrypt_error): ?>
                <div class="panel-body"><div class="alert alert-danger" style="margin:0"><i class="fa fa-exclamation-triangle"></i> <?= e($decrypt_error) ?>
                    <div class="small">تأكد أن مفتاح التشفير (storage/private/recruitment.key أو RECRUITMENT_KEY) هو نفسه الذي استُعمل عند الترشح.</div></div></div>
            <?php else: ?>
                <?php $birth = to_datetime($personal['birth_date'] ?? null); ?>
                <table class="table recruit-kv">
                    <tr><th>اللقب والاسم</th><td><strong><?= e($app->full_name()) ?></strong></td></tr>
                    <tr><th>Nom et prénom</th><td><bdi><?= e($app->full_name_latin()) ?></bdi></td></tr>
                    <tr><th>الجنس</th><td><?= e($app->get_gender_display()) ?></td></tr>
                    <tr><th>تاريخ ومكان الميلاد</th><td><?= e($birth ? $birth->format('d/m/Y') : '') ?> — <?= e($personal['birth_place'] ?? '') ?>
                        <?php if ($birth): ?><span class="text-muted">(<?= (int) $birth->diff(new DateTimeImmutable($app['submitted_at']))->y ?> سنة يوم الترشح)</span><?php endif; ?></td></tr>
                    <tr><th>رقم التعريف الوطني</th><td><code class="recruit-nin" dir="ltr"><?= e($personal['nin'] ?? '') ?></code></td></tr>
                    <tr><th>الهاتف</th><td><a href="tel:<?= e($personal['phone'] ?? '') ?>" dir="ltr"><?= e($personal['phone'] ?? '') ?></a></td></tr>
                    <tr><th>البريد الإلكتروني</th><td><?php if (!empty($personal['email'])): ?><a href="mailto:<?= e($personal['email']) ?>" dir="ltr"><?= e($personal['email']) ?></a><?php else: ?><span class="text-muted">—</span><?php endif; ?></td></tr>
                    <tr><th>العنوان</th><td><?= e($personal['address'] ?? '') ?> — <?= e($personal['wilaya'] ?? '') ?></td></tr>
                    <tr><th>الخدمة الوطنية</th><td><?= e(CompetitionApplication::choiceLabel('national_service', $personal['national_service'] ?? '')) ?></td></tr>
                </table>
            <?php endif; ?>
        </div>

        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-graduation-cap"></i> المؤهلات</strong></div>
            <table class="table recruit-kv">
                <tr><th>الشهادة</th><td><?= e($app['diploma']) ?></td></tr>
                <tr><th>التخصص</th><td><?= e($app['specialty']) ?></td></tr>
                <tr><th>سنة التخرج</th><td><?= e($app['graduation_year']) ?></td></tr>
                <tr><th>الخبرة المهنية</th><td><?= (int) $app['experience_years'] ?> سنة</td></tr>
            </table>
        </div>

        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-file-pdf-o"></i> الوثائق المرفوعة (<?= count($files) ?>)</strong></div>
            <table class="table table-condensed recruit-files">
                <thead><tr><th>الوثيقة</th><th>اسم الملف</th><th>الحجم</th><th>الرفع</th><th style="width:170px"></th></tr></thead>
                <tbody>
                <?php foreach ($files as $f): $late = !empty($f['added_later']); ?>
                    <tr<?= $late ? ' class="recruit-late-file"' : '' ?>>
                        <td><strong><?= e($f['label']) ?></strong><?php if ($late): ?> <span class="label label-info" title="رفعها المترشح من صفحة متابعة طلبه بعد أن كان ملفه ناقصاً">إكمال</span><?php endif; ?></td>
                        <td class="small" dir="auto"><?= e($f['original_name']) ?></td>
                        <td class="small"><bdi><?= e(human_filesize($f['size'])) ?></bdi></td>
                        <td class="small"><?= e(format_date($f['uploaded_at'], 'd/m/Y H:i')) ?></td>
                        <td>
                            <a href="<?= e(url('admin:recruit_file', $f['id'])) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i> عرض</a>
                            <a href="<?= e(url('admin:recruit_file', $f['id']) . '?dl=1') ?>" class="btn btn-xs btn-default"><i class="fa fa-download"></i> تحميل</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$files): ?><tr><td colspan="5" class="text-muted text-center">لا توجد وثائق.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        <form method="post" class="panel panel-default admin-panel recruit-decision">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="review">
            <div class="panel-heading"><strong><i class="fa fa-gavel"></i> دراسة الطلب</strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="r_status">الحالة</label>
                    <select name="status" id="r_status" class="form-control">
                        <?php foreach ($statuses as $k => $label): ?>
                            <option value="<?= e($k) ?>"<?= $app['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" data-show-when="status=incomplete">
                    <label for="r_until">آخر أجل لإكمال الملف</label>
                    <input type="date" name="complete_until" id="r_until" class="form-control" min="<?= e(date('Y-m-d')) ?>"
                           value="<?= e($app['status'] === 'incomplete' && $app['complete_until'] ? $app['complete_until'] : $default_until) ?>">
                    <span class="help-block small">قبل نهاية هذا اليوم يستطيع المترشح رفع الوثائق الناقصة بنفسه من صفحة «متابعة طلب»، فيعود طلبه إلى «قيد الانتظار». اكتب في الرسالة ما ينقص ملفه.</span>
                </div>
                <div class="form-group">
                    <label for="r_msg">رسالة للمترشح</label>
                    <textarea name="candidate_message" id="r_msg" class="form-control" rows="4" maxlength="1000" placeholder="مثال: ملفك ناقص: يرجى إحضار شهادة الإقامة يوم المقابلة."><?= e($app['candidate_message']) ?></textarea>
                    <span class="help-block small">تظهر للمترشح في صفحة «متابعة طلب» (برقم المتابعة والرمز السري).</span>
                </div>
                <div class="form-group">
                    <label for="r_notes">ملاحظات داخلية</label>
                    <textarea name="internal_notes" id="r_notes" class="form-control" rows="4" maxlength="2000"><?= e($app['internal_notes']) ?></textarea>
                    <span class="help-block small">للموظفين فقط، لا يراها المترشح.</span>
                </div>
                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-save"></i> حفظ</button>
            </div>
        </form>

        <form method="post" class="panel panel-default admin-panel recruit-outcome" id="outcome">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="outcome">
            <div class="panel-heading"><strong><i class="fa fa-calendar-check-o"></i> الاستدعاء والنتيجة</strong></div>
            <div class="panel-body">
                <?php if ($app['status'] !== 'accepted'): ?>
                    <p class="small text-muted"><i class="fa fa-info-circle"></i> الاستدعاء والنتيجة للمترشحين الذين قُبلت ملفاتهم فقط.</p>
                <?php endif; ?>
                <div class="form-group">
                    <label for="r_exam">الاستدعاء إلى</label>
                    <select name="exam_id" id="r_exam" class="form-control"<?= $app['status'] !== 'accepted' && !$app['exam_id'] ? ' disabled' : '' ?>>
                        <option value="0">— غير مستدعى —</option>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?= e($ex['id']) ?>"<?= (int) $app['exam_id'] === (int) $ex['id'] ? ' selected' : '' ?>><?= e($ex->label()) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$exams && $c): ?>
                        <span class="help-block small">لا توجد مواعيد. أضفها في <a href="<?= e(url('admin:edit', 'competitions', $c['id'])) ?>#inline-exams">صفحة المسابقة ← مواعيد الاختبارات</a>.</span>
                    <?php elseif ($app['seat_number']): ?>
                        <span class="help-block small">رقم التسجيل: <strong class="recruit-code"><?= e($app['seat_number']) ?></strong></span>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-xs-7 form-group">
                        <label for="r_result">النتيجة</label>
                        <select name="result" id="r_result" class="form-control">
                            <option value="">— لم تُسجَّل —</option>
                            <?php foreach ($results as $k => $label): ?>
                                <option value="<?= e($k) ?>"<?= (string) $app['result'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xs-5 form-group">
                        <label for="r_score">العلامة /20</label>
                        <input type="text" name="score" id="r_score" class="form-control" inputmode="decimal" maxlength="5" dir="ltr" placeholder="14,50" value="<?= e($app->score_text()) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="r_rank">الترتيب</label>
                    <input type="number" name="result_rank" id="r_rank" class="form-control" min="1" max="65535" value="<?= e($app['result_rank']) ?>">
                </div>
                <div class="form-group">
                    <label for="r_rnote">ملاحظة تظهر للمترشح مع النتيجة</label>
                    <textarea name="result_note" id="r_rnote" class="form-control" rows="2" maxlength="500" placeholder="مثال: يرجى الحضور يوم 1 ديسمبر لاستكمال ملف التوظيف."><?= e($app['result_note']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i> حفظ الاستدعاء والنتيجة</button>
                <?php if ($c): ?>
                    <p class="small text-muted" style="margin:10px 0 0">
                        <?= $c->results_published() ? '<i class="fa fa-globe text-success"></i> نتائج هذه المسابقة منشورة: المترشح يرى نتيجته.' : '<i class="fa fa-eye-slash"></i> النتائج غير منشورة بعد: لا يراها المترشح.' ?>
                        <a href="<?= e(url('admin:recruit_results') . '?competition=' . (int) $c['id']) ?>">صفحة نتائج المسابقة</a>
                    </p>
                <?php endif; ?>
            </div>
        </form>

        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-history"></i> سجل الطلب</strong></div>
            <ul class="list-group recruit-history">
                <?php foreach ($history as $h): ?>
                    <li class="list-group-item">
                        <div><strong><?= e($actions[$h['action']] ?? $h['action']) ?></strong><?= $h['details'] !== '' ? ' — ' . e($h['details']) : '' ?></div>
                        <div class="small text-muted"><?= e(format_date($h['created_at'], 'd/m/Y H:i')) ?> — <?= $h['username'] !== '' ? e($h['username']) : 'المترشح' ?> — <span dir="ltr"><?= e($h['ip_address']) ?></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endblock(); ?>
