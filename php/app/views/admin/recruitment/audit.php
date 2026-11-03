<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<div class="admin-header">
    <h1 class="admin-title"><i class="fa fa-history"></i> <?= e($page_title) ?> <small>(<?= e($page->total) ?>)</small></h1>
</div>
<p class="text-muted">كل اطلاع على ملف مترشح أو وثيقة، وكل تغيير حالة أو تصدير قائمة، مع اسم الموظف وعنوان IP. هذا السجل لا يمكن تعديله من لوحة الإدارة.</p>

<form method="get" class="recruit-filters form-inline">
    <select name="action" class="form-control" onchange="this.form.submit()">
        <option value="">كل الأحداث</option>
        <?php foreach ($actions as $k => $label): ?><option value="<?= e($k) ?>"<?= $filter['action'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <select name="user" class="form-control" onchange="this.form.submit()">
        <option value="">كل الموظفين</option>
        <?php foreach ($users as $u): ?><option value="<?= e($u['user_id']) ?>"<?= $filter['user'] === (int) $u['user_id'] ? ' selected' : '' ?>><?= e($u['username']) ?></option><?php endforeach; ?>
    </select>
    <?php if ($filter['action'] !== '' || $filter['user'] || $filter['application']): ?><a href="<?= e(url('admin:recruit_audit')) ?>" class="small"><i class="fa fa-times"></i> إلغاء التصفية</a><?php endif; ?>
</form>

<div class="table-responsive admin-table-wrap">
    <table class="table table-striped table-condensed admin-table">
        <thead><tr><th>الوقت</th><th>المستخدم</th><th>الحدث</th><th>الطلب</th><th>التفاصيل</th><th>عنوان IP</th></tr></thead>
        <tbody>
        <?php foreach ($page as $h): $aid = (int) $h['application_id']; ?>
            <tr>
                <td class="small" style="white-space:nowrap"><?= e(format_date($h['created_at'], 'd/m/Y H:i:s')) ?></td>
                <td><?= $h['username'] !== '' ? e($h['username']) : '<span class="text-muted">المترشح</span>' ?></td>
                <td><span class="label label-<?= in_array($h['action'], ['export', 'purge'], true) ? 'danger' : (in_array($h['action'], ['view', 'file'], true) ? 'info' : 'default') ?>"><?= e($actions[$h['action']] ?? $h['action']) ?></span></td>
                <td><?php if ($aid && isset($codes[$aid])): ?><a href="<?= e(url('admin:recruit_application', $aid)) ?>" class="recruit-code"><?= e($codes[$aid]) ?></a><?php elseif ($aid): ?><span class="text-muted">#<?= $aid ?> (محذوف)</span><?php else: ?>—<?php endif; ?></td>
                <td class="small"><?= e($h['details']) ?></td>
                <td class="small" dir="ltr"><?= e($h['ip_address']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($page->isEmpty()): ?><tr><td colspan="6" class="text-center text-muted" style="padding:30px">لا توجد أحداث.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($page->has_other_pages()): ?>
    <nav class="text-center"><ul class="pagination">
        <?php if ($page->has_previous()): ?><li><a href="<?= e($page->url($page->previous_page_number())) ?>">&raquo; السابق</a></li><?php endif; ?>
        <li class="disabled"><span><?= e($page->number) ?> / <?= e($page->numPages) ?></span></li>
        <?php if ($page->has_next()): ?><li><a href="<?= e($page->url($page->next_page_number())) ?>">التالي &laquo;</a></li><?php endif; ?>
    </ul></nav>
<?php endif; ?>
<?php endblock(); ?>
