<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<?php
$qs = fn(array $changes) => url('admin:recruit_applications') . (($q = array_filter(array_merge($_GET, $changes, ['page' => null]), fn($v) => $v !== null && $v !== '')) ? '?' . http_build_query($q) : '');
$exportQs = http_build_query(array_filter(['competition' => $active['competition'] ?? null, 'post' => $active['post'] ?? null, 'status' => $active['status'] ?? null, 'q' => $active['q'] ?? null]));
?>
<div class="admin-header">
    <h1 class="admin-title"><i class="fa fa-id-card-o"></i> طلبات الترشح <small>(<?= e($page->total) ?>)</small></h1>
    <div class="recruit-export">
        <a href="<?= e(url('admin:recruit_export') . '?' . $exportQs) ?>" class="btn btn-default"><i class="fa fa-file-excel-o text-success"></i> تصدير قائمة مختصرة</a>
        <a href="<?= e(url('admin:recruit_export') . '?' . http_build_query(['mode' => 'full']) . ($exportQs ? '&' . $exportQs : '')) ?>" class="btn btn-default"
           data-confirm="القائمة الكاملة تحتوي على أرقام التعريف والهواتف والعناوين. احفظ الملف في مكان آمن واحذفه بعد الاستعمال. متابعة؟"><i class="fa fa-lock text-danger"></i> تصدير قائمة كاملة</a>
    </div>
</div>

<?php if ($key_backup): ?>
    <div class="alert alert-warning recruit-key-alert">
        <i class="fa fa-key"></i> <strong>نسخة احتياطية لمفتاح التشفير:</strong>
        معطيات المترشحين ووثائقهم مشفّرة بمفتاح محفوظ في الملف <code dir="ltr">storage/private/recruitment.key</code>.
        انسخ هذا الملف (عبر FTP أو مدير ملفات الاستضافة) واحفظه في مكان آمن خارج الموقع: إن ضاع لا يمكن قراءة أي ملف أبداً.
        للحماية القصوى ضع المفتاح في متغير البيئة <code dir="ltr">RECRUITMENT_KEY</code> خارج مجلد الموقع — انظر <code dir="ltr">SECURITY.md</code>.
    </div>
<?php endif; ?>

<form method="get" class="recruit-filters form-inline">
    <select name="competition" class="form-control" onchange="this.form.post && (this.form.post.value = ''); this.form.submit()">
        <option value="">كل المسابقات</option>
        <?php foreach ($competitions as $cmp): ?>
            <option value="<?= e($cmp['id']) ?>"<?= ($active['competition'] ?? 0) === (int) $cmp['id'] ? ' selected' : '' ?>><?= e(truncatechars((string) $cmp['title'], 70)) ?><?= $cmp['reference'] !== '' ? ' — ' . e($cmp['reference']) : '' ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($current && $posts): ?>
        <select name="post" class="form-control" onchange="this.form.submit()">
            <option value="">كل الرتب</option>
            <?php foreach ($posts as $p): ?>
                <option value="<?= e($p['id']) ?>"<?= ($active['post'] ?? 0) === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <?php foreach ($statuses as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= ($active['status'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="input-group">
        <input type="search" name="q" class="form-control" value="<?= e($active['q'] ?? '') ?>" placeholder="الاسم، رقم المتابعة، أو رقم التعريف (18 رقماً)">
        <span class="input-group-btn"><button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button></span>
    </div>
    <?php if ($active): ?><a href="<?= e(url('admin:recruit_applications')) ?>" class="small"><i class="fa fa-times"></i> إلغاء التصفية</a><?php endif; ?>
</form>

<div class="recruit-status-tabs">
    <a href="<?= e($qs(['status' => null])) ?>" class="<?= !isset($active['status']) ? 'active' : '' ?>">الكل <span class="badge"><?= array_sum($counts) ?></span></a>
    <?php foreach ($statuses as $k => $label): ?>
        <a href="<?= e($qs(['status' => $k])) ?>" class="recruit-st-<?= e($k) ?> <?= ($active['status'] ?? '') === $k ? 'active' : '' ?>"><?= e($label) ?> <span class="badge"><?= (int) ($counts[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
</div>

<?php if ($current): ?>
    <div class="recruit-current small text-muted">
        <i class="fa fa-trophy"></i> <a href="<?= e(url('admin:edit', 'competitions', $current['id'])) ?>"><?= e($current['title']) ?></a>
        — الإيداع من <?= e(format_date($current['opens_at'], 'd/m/Y')) ?> إلى <?= e(format_date($current['closes_at'], 'd/m/Y')) ?>
        (<?= e(Competition::PHASES[$current->phase()]) ?>)
        <?php if (is_superuser()): ?>
            — <a href="<?= e(url('admin:recruit_purge', $current['id'])) ?>" class="text-danger"><i class="fa fa-trash"></i> حذف معطيات المترشحين بعد انتهاء المسابقة</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="post" id="admin-list-form">
    <?= csrf_field() ?>
    <div class="admin-actions form-inline">
        <select name="new_status" class="form-control input-sm">
            <option value="">— تغيير حالة الطلبات المحددة إلى —</option>
            <?php foreach ($statuses as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">تطبيق</button>
        <span class="text-muted small" data-selected-count></span>
    </div>
    <div class="table-responsive admin-table-wrap">
        <table class="table table-striped table-hover admin-table recruit-table">
            <thead>
            <tr>
                <th style="width:30px"><input type="checkbox" data-select-all aria-label="تحديد الكل"></th>
                <th>رقم المتابعة</th>
                <th>المترشح</th>
                <th>الرتبة</th>
                <th>الشهادة والتخصص</th>
                <th>الوثائق</th>
                <th>تاريخ الترشح</th>
                <th>الحالة</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($page as $a): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= e($a['id']) ?>" data-row-check aria-label="تحديد"></td>
                    <td><a href="<?= e(url('admin:recruit_application', $a['id'])) ?>" class="recruit-code"><?= e($a['tracking_code']) ?></a></td>
                    <td>
                        <a href="<?= e(url('admin:recruit_application', $a['id'])) ?>"><strong><?= e($a->full_name()) ?></strong></a>
                        <div class="small text-muted"><bdi><?= e($a->full_name_latin()) ?></bdi></div>
                    </td>
                    <td><?= e($a->post_label()) ?><?php if (!$current): ?><div class="small text-muted"><?= e(truncatechars((string) ($a['competition']['title'] ?? ''), 45)) ?></div><?php endif; ?></td>
                    <td><?= e($a['diploma']) ?><div class="small text-muted"><?= e($a['specialty']) ?> (<?= e($a['graduation_year']) ?>)</div></td>
                    <td class="text-center"><i class="fa fa-file-pdf-o text-danger"></i> <?= (int) ($doc_counts[(int) $a['id']] ?? 0) ?></td>
                    <td class="small"><?= e(format_date($a['submitted_at'], 'd/m/Y H:i')) ?></td>
                    <td><span class="label label-<?= e($a->status_color()) ?>"><?= e($a->get_status_display()) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($page->isEmpty()): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px">لا توجد طلبات ترشح<?= $active ? ' مطابقة للتصفية' : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<?php if ($page->has_other_pages()): ?>
    <nav class="text-center">
        <ul class="pagination">
            <?php if ($page->has_previous()): ?><li><a href="<?= e($page->url($page->previous_page_number())) ?>">&raquo; السابق</a></li><?php endif; ?>
            <?php foreach ($page->page_range() as $n): ?>
                <?php if ($n === $page->number): ?>
                    <li class="active"><span><?= $n ?></span></li>
                <?php elseif ($n <= 2 || $n > $page->numPages - 2 || abs($n - $page->number) <= 2): ?>
                    <li><a href="<?= e($page->url($n)) ?>"><?= $n ?></a></li>
                <?php elseif ($n === 3 || $n === $page->numPages - 2): ?>
                    <li class="disabled"><span>…</span></li>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($page->has_next()): ?><li><a href="<?= e($page->url($page->next_page_number())) ?>">التالي &laquo;</a></li><?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
<p class="small text-muted"><i class="fa fa-shield"></i> كل اطلاع على ملف مترشح أو وثيقة، وكل تصدير، يُسجَّل باسمك في سجل الاطلاع.</p>
<?php endblock(); ?>
