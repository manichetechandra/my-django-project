<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<div class="admin-header">
    <h1 class="admin-title"><i class="fa <?= e($r['icon']) ?>"></i> <?= e($r['plural']) ?> <small>(<?= e($page->total) ?>)</small></h1>
    <?php if ($r['can_add']): ?>
        <a href="<?= e(url('admin:add', $r['key'])) ?>" class="btn btn-success"><i class="fa fa-plus"></i> إضافة <?= e($r['label']) ?></a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="<?= $filter_options ? 'col-md-9' : 'col-md-12' ?>">
        <?php if ($r['search']): ?>
            <form method="get" class="admin-search">
                <?php foreach ($active_filters as $fk => $fv): ?><input type="hidden" name="f_<?= e($fk) ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                <div class="input-group">
                    <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="بحث...">
                    <span class="input-group-btn"><button class="btn btn-default" type="submit"><i class="fa fa-search"></i> بحث</button></span>
                </div>
                <?php if ($q !== '' || $active_filters): ?>
                    <small><a href="<?= e(url('admin:list', $r['key'])) ?>"><i class="fa fa-times"></i> إلغاء البحث والفلاتر</a> — <?= e($page->total) ?> نتيجة من أصل <?= e($total_all) ?></small>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <form method="post" id="admin-list-form">
            <?= csrf_field() ?>
            <?php if ($r['can_delete'] || $r['actions']): ?>
                <div class="admin-actions form-inline">
                    <select name="action" class="form-control input-sm">
                        <option value="">— اختر إجراءً —</option>
                        <?php foreach ($r['actions'] as $ak => $a): ?><option value="<?= e($ak) ?>"><?= e($a['label']) ?></option><?php endforeach; ?>
                        <?php if ($r['can_delete']): ?><option value="delete">🗑 حذف السجلات المحددة</option><?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-default" data-confirm-delete>تنفيذ</button>
                    <span class="text-muted small" data-selected-count></span>
                </div>
            <?php endif; ?>

            <div class="table-responsive admin-table-wrap">
                <table class="table table-striped table-hover admin-table">
                    <thead>
                    <tr>
                        <th style="width:30px"><input type="checkbox" data-select-all></th>
                        <?php foreach ($r['list'] as $col): ?><th><?= e(admin_column_label($r, $col)) ?></th><?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($page as $obj): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= e($obj['id']) ?>" data-row-check></td>
                            <?php foreach ($r['list'] as $col): ?>
                                <td>
                                    <?php if (in_array($col, $r['editable'], true)): ?>
                                        <?php if (($r['fields'][$col]['type'] ?? '') === 'bool'): ?>
                                            <input type="hidden" name="edit[<?= e($obj['id']) ?>][<?= e($col) ?>]" value="0">
                                            <input type="checkbox" name="edit[<?= e($obj['id']) ?>][<?= e($col) ?>]" value="1" <?= $obj[$col] ? 'checked' : '' ?> data-editable>
                                        <?php else: ?>
                                            <input type="number" class="form-control input-sm admin-inline-num" name="edit[<?= e($obj['id']) ?>][<?= e($col) ?>]" value="<?= e($obj[$col]) ?>" data-editable>
                                        <?php endif; ?>
                                    <?php elseif (in_array($col, $r['links'], true)): ?>
                                        <a href="<?= e(url('admin:edit', $r['key'], $obj['id'])) ?>"><strong><?= admin_cell($r, $obj, $col) ?: '(بدون عنوان)' ?></strong></a>
                                        <?php if ($col === $r['links'][0] && admin_translatable($r)): // حالة الترجمة: أخضر = مترجم ?>
                                            <div class="admin-tr-badges">
                                                <?php foreach (admin_translation_status($r, $obj) as $lang => $complete): ?>
                                                    <span class="admin-tr-badge <?= $complete ? 'is-done' : 'is-missing' ?>" title="<?= $complete ? 'مترجم' : 'تنقصه ترجمة: يظهر النص العربي مكانها' ?>"><?= e(strtoupper($lang)) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= admin_cell($r, $obj, $col) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($page->isEmpty()): ?>
                        <tr><td colspan="<?= count($r['list']) + 1 ?>" class="text-center text-muted" style="padding:40px">لا توجد سجلات</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($r['editable'] && !$page->isEmpty()): ?>
                <button type="submit" name="save_list" value="1" class="btn btn-primary"><i class="fa fa-save"></i> حفظ التعديلات</button>
            <?php endif; ?>
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
    </div>

    <?php if ($filter_options): ?>
        <div class="col-md-3">
            <div class="panel panel-default admin-panel admin-filters">
                <div class="panel-heading"><strong><i class="fa fa-filter"></i> تصفية</strong></div>
                <div class="panel-body">
                    <?php foreach ($filter_options as $f => $fo): ?>
                        <h5><?= e($fo['label']) ?></h5>
                        <ul class="list-unstyled">
                            <li><a href="<?= e(admin_list_url($r['key'], ['f_' . $f => null])) ?>" class="<?= !isset($active_filters[$f]) ? 'active' : '' ?>">الكل</a></li>
                            <?php foreach ($fo['options'] as $ov => $ol): ?>
                                <li><a href="<?= e(admin_list_url($r['key'], ['f_' . $f => (string) $ov])) ?>" class="<?= ($active_filters[$f] ?? null) === (string) $ov ? 'active' : '' ?>"><?= e($ol) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endblock(); ?>
