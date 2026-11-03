<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<?php if (!$c): ?>
    <div class="admin-header">
        <h1 class="admin-title"><i class="fa fa-list-ol"></i> نتائج المسابقات</h1>
    </div>
    <p class="text-muted">اختر المسابقة لتسجيل نتائج المترشحين الذين قُبلت ملفاتهم، ثم انشرها ليرى كل مترشح نتيجته في صفحة متابعة طلبه.</p>
    <div class="table-responsive admin-table-wrap">
        <table class="table table-striped table-hover admin-table">
            <thead><tr><th>المسابقة</th><th>آخر أجل</th><th>ملفات مقبولة</th><th>نتائج مسجّلة</th><th>النشر</th></tr></thead>
            <tbody>
            <?php foreach ($competitions as $cmp): $st = $stats[(int) $cmp['id']] ?? ['accepted' => 0, 'with_result' => 0]; ?>
                <tr>
                    <td><a href="<?= e(url('admin:recruit_results') . '?competition=' . (int) $cmp['id']) ?>"><strong><?= e($cmp['title']) ?></strong></a>
                        <?= $cmp['reference'] !== '' ? '<span class="small text-muted"> — ' . e($cmp['reference']) . '</span>' : '' ?></td>
                    <td class="small"><?= e(format_date($cmp['closes_at'], 'd/m/Y')) ?></td>
                    <td><?= $st['accepted'] ?></td>
                    <td><?= $st['with_result'] ?></td>
                    <td><?= $cmp->results_published() ? '<span class="label label-success"><i class="fa fa-globe"></i> منشورة</span>' : '<span class="label label-default">غير منشورة</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$competitions): ?><tr><td colspan="5" class="text-center text-muted" style="padding:30px">لا توجد مسابقات.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <ol class="breadcrumb">
        <li><a href="<?= e(url('admin:index')) ?>">لوحة الإدارة</a></li>
        <li><a href="<?= e(url('admin:recruit_results')) ?>">نتائج المسابقات</a></li>
        <li class="active"><?= e(truncatechars((string) $c['title'], 60)) ?></li>
    </ol>
    <div class="admin-header">
        <h1 class="admin-title"><i class="fa fa-list-ol"></i> نتائج: <?= e(truncatechars((string) $c['title'], 70)) ?></h1>
        <div class="recruit-export">
            <a href="<?= e(url('admin:recruit_applications') . '?competition=' . (int) $c['id']) ?>" class="btn btn-default"><i class="fa fa-id-card-o"></i> طلبات الترشح</a>
            <a href="<?= e(url('admin:recruit_export') . '?' . http_build_query(['competition' => (int) $c['id'], 'status' => 'accepted'])) ?>" class="btn btn-default"><i class="fa fa-file-excel-o text-success"></i> تصدير النتائج</a>
        </div>
    </div>

    <div class="recruit-publish <?= $c->results_published() ? 'is-published' : '' ?>">
        <form method="post" class="recruit-publish-form">
            <?= csrf_field() ?>
            <?php if ($c->results_published()): ?>
                <span><i class="fa fa-globe"></i> <strong>النتائج منشورة</strong> منذ <?= e(format_date($c['results_published_at'], 'd/m/Y H:i')) ?>:
                    كل مترشح يرى نتيجته (فقط) في صفحة متابعة طلبه ويطبع إشعار النتيجة.</span>
                <button type="submit" name="action" value="unpublish" class="btn btn-sm btn-default" data-confirm="إلغاء نشر النتائج؟ لن يراها المترشحون حتى تنشرها من جديد.">إلغاء النشر</button>
            <?php else: ?>
                <span><i class="fa fa-eye-slash"></i> <strong>النتائج غير منشورة:</strong> المترشحون لا يرون شيئاً مما تسجّله هنا حتى تنشره.</span>
                <button type="submit" name="action" value="publish" class="btn btn-sm btn-success" data-confirm="نشر النتائج الآن؟ سيرى كل مترشح نتيجته وعلامته وترتيبه في صفحة متابعة طلبه."><i class="fa fa-globe"></i> نشر النتائج للمترشحين</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="recruit-results-summary small">
        <?php foreach ($results as $k => $label): ?>
            <span class="label label-<?= e(CompetitionApplication::RESULT_COLORS[$k]) ?>"><?= e($label) ?>: <?= (int) ($summary[$k] ?? 0) ?></span>
        <?php endforeach; ?>
        <span class="label label-warning">بلا نتيجة: <?= (int) ($summary[''] ?? 0) ?></span>
        <?php if ($posts): ?>
            <form method="get" class="form-inline recruit-results-filter">
                <input type="hidden" name="competition" value="<?= (int) $c['id'] ?>">
                <select name="post" class="form-control input-sm" onchange="this.form.submit()" aria-label="الرتبة">
                    <option value="">كل الرتب</option>
                    <?php foreach ($posts as $p): ?><option value="<?= e($p['id']) ?>"<?= $post_id === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <form method="post" id="recruit-results-form">
        <?= csrf_field() ?>
        <div class="table-responsive admin-table-wrap">
            <table class="table table-condensed table-hover admin-table recruit-results-table">
                <thead>
                <tr>
                    <th>رقم التسجيل</th>
                    <th>المترشح</th>
                    <th>الرتبة</th>
                    <th>الاستدعاء</th>
                    <th style="width:150px">النتيجة</th>
                    <th style="width:90px">العلامة /20</th>
                    <th style="width:80px">الترتيب</th>
                    <th>ملاحظة للمترشح</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($apps as $a): $id = (int) $a['id']; $ex = $exams[(int) $a['exam_id']] ?? null; ?>
                    <tr class="<?= $a['result'] ? 'recruit-res-' . e($a['result']) : '' ?>">
                        <td><strong class="recruit-code"><?= e($a['seat_number'] ?: '—') ?></strong></td>
                        <td><a href="<?= e(url('admin:recruit_application', $id)) ?>"><strong><?= e($a->full_name()) ?></strong></a>
                            <div class="small text-muted"><span class="recruit-code"><?= e($a['tracking_code']) ?></span> · <bdi><?= e($a->full_name_latin()) ?></bdi></div></td>
                        <td class="small"><?= e($a->post_label()) ?></td>
                        <td class="small"><?= $ex ? e(format_date($ex['starts_at'], 'd/m H:i')) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <select name="r[<?= $id ?>][result]" class="form-control input-sm" aria-label="النتيجة">
                                <option value="">—</option>
                                <?php foreach ($results as $k => $label): ?><option value="<?= e($k) ?>"<?= (string) $a['result'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="r[<?= $id ?>][score]" class="form-control input-sm" inputmode="decimal" maxlength="5" dir="ltr" placeholder="14,50" value="<?= e($a->score_text()) ?>" aria-label="العلامة"></td>
                        <td><input type="number" name="r[<?= $id ?>][rank]" class="form-control input-sm" min="1" max="65535" value="<?= e($a['result_rank']) ?>" aria-label="الترتيب"></td>
                        <td><input type="text" name="r[<?= $id ?>][note]" class="form-control input-sm" maxlength="500" value="<?= e($a['result_note']) ?>" aria-label="ملاحظة"></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$apps): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:40px">لا يوجد مترشحون قُبلت ملفاتهم في هذه المسابقة<?= $post_id ? ' لهذه الرتبة' : '' ?>.
                        النتائج تُسجَّل للملفات المقبولة فقط.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($apps): ?>
            <div class="recruit-results-actions">
                <button type="submit" name="action" value="save" class="btn btn-primary"><i class="fa fa-save"></i> حفظ النتائج</button>
                <button type="submit" name="action" value="autorank" class="btn btn-default" data-confirm="حساب الترتيب تلقائياً حسب العلامة داخل كل رتبة؟ (احفظ العلامات أولاً) سيُستبدل الترتيب الحالي."><i class="fa fa-sort-numeric-asc"></i> ترتيب تلقائي حسب العلامة</button>
                <span class="small text-muted">العلامة بفاصلة أو نقطة (14,50). الترتيب التلقائي داخل كل رتبة، والعلامات المتساوية تأخذ نفس الترتيب، والغائبون بلا ترتيب.</span>
            </div>
        <?php endif; ?>
    </form>
    <p class="small text-muted"><i class="fa fa-shield"></i> كل تسجيل أو تعديل لنتيجة، وكل نشر أو إلغاء نشر، يُسجَّل باسمك في سجل الاطلاع.</p>
<?php endif; ?>
<?php endblock(); ?>
