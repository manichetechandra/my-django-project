<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<ol class="breadcrumb">
    <li><a href="<?= e(url('admin:index')) ?>">لوحة الإدارة</a></li>
    <li><a href="<?= e(url('admin:list', $r['key'])) ?>"><?= e($r['plural']) ?></a></li>
    <li class="active"><?= $obj ? e(truncatechars((string) $obj, 60)) : 'إضافة' ?></li>
</ol>
<div class="admin-header">
    <h1 class="admin-title"><i class="fa <?= e($r['icon']) ?>"></i> <?= e($page_title) ?></h1>
    <div>
        <?php if ($obj && isset($r['view_url'])): ?>
            <a href="<?= e(($r['view_url'])($obj)) ?>" class="btn btn-default" target="_blank"><i class="fa fa-external-link"></i> عرض في الموقع</a>
        <?php endif; ?>
    </div>
</div>

<?php $tr_fields = admin_translatable($r); ?>
<form method="post" enctype="multipart/form-data" class="admin-form" novalidate>
    <?= csrf_field() ?>
    <div class="panel panel-default admin-panel">
        <div class="panel-body">
            <?php if ($tr_fields && !$readonly): ?>
                <!-- الترجمة: كل نص عربي له حقلان FR و EN تحته -->
                <div class="admin-tr-toolbar">
                    <button type="button" class="btn btn-info btn-sm" data-auto-translate data-url="<?= e(url('admin:translate')) ?>">
                        <i class="fa fa-language"></i> ترجمة تلقائية
                    </button>
                    <span class="small text-muted">
                        <?php if (Translator::enabled()): ?>
                            تملأ حقول الفرنسية (FR) والإنجليزية (EN) من النص العربي. والحقول التي تبقى فارغة تُترجم تلقائياً عند الحفظ.
                        <?php else: ?>
                            <i class="fa fa-exclamation-triangle text-warning"></i> الترجمة التلقائية غير مفعّلة: أضف مفتاح Gemini المجاني في config.php، أو اكتب الترجمة بنفسك. الحقول الفارغة يظهر مكانها النص العربي.
                        <?php endif; ?>
                    </span>
                    <span class="admin-tr-status small" data-tr-status></span>
                </div>
            <?php endif; ?>
            <?php foreach ($r['fields'] as $f => $spec): ?>
                <?php if ($spec['type'] === 'readonly' && !$obj) { continue; } ?>
                <div class="form-group <?= isset($errors[$f]) ? 'has-error' : '' ?>">
                    <?php if ($spec['type'] !== 'bool'): ?>
                        <label for="f_<?= e($f) ?>" class="control-label">
                            <?= e($spec['label'] ?? $f) ?>
                            <?php if (($spec['required'] ?? !in_array($spec['type'], ['bool', 'readonly'], true)) && !in_array($spec['type'], ['readonly', 'slug', 'password'], true)): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                    <?php endif; ?>
                    <?= admin_input($r, $f, $spec, $values[$f] ?? null, $readonly, $obj) ?>
                    <?php if (isset($errors[$f])): ?><span class="help-block"><i class="fa fa-exclamation-circle"></i> <?= e($errors[$f]) ?></span><?php endif; ?>
                    <?php if (!empty($spec['help'])): ?><span class="help-block small text-muted"><?= e($spec['help']) ?></span><?php endif; ?>
                    <?php if ($spec['type'] === 'slug'): ?><span class="help-block small text-muted">يُولَّد تلقائياً من "<?= e($r['fields'][$spec['from']]['label'] ?? $spec['from']) ?>" إذا تُرك فارغاً.</span><?php endif; ?>
                    <?php if (in_array($f, $tr_fields, true)): ?>
                        <div class="admin-translations">
                            <?php foreach (admin_other_langs() as $lang): $col = $f . '_' . $lang; $max = admin_translation_max($spec); ?>
                                <div class="admin-tr-row <?= isset($errors[$col]) ? 'has-error' : '' ?>" data-lang="<?= e($lang) ?>">
                                    <label class="admin-tr-lang" for="f_<?= e($col) ?>" title="<?= $lang === 'fr' ? 'الفرنسية' : 'الإنجليزية' ?>"><?= e(strtoupper($lang)) ?></label>
                                    <?php if ($spec['type'] === 'textarea'): ?>
                                        <textarea name="<?= e($col) ?>" id="f_<?= e($col) ?>" class="form-control input-sm" rows="<?= max(2, (int) ceil(($spec['rows'] ?? 4) / 2)) ?>" dir="ltr" lang="<?= e($lang) ?>" data-tr-of="<?= e($f) ?>" data-tr-lang="<?= e($lang) ?>"<?= $readonly ? ' disabled' : '' ?>><?= e($values[$col] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="<?= e($col) ?>" id="f_<?= e($col) ?>" class="form-control input-sm" dir="ltr" lang="<?= e($lang) ?>" value="<?= e($values[$col] ?? '') ?>"<?= $max ? ' maxlength="' . $max . '"' : '' ?> data-tr-of="<?= e($f) ?>" data-tr-lang="<?= e($lang) ?>"<?= $readonly ? ' disabled' : '' ?>>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($errors[$col])): ?><span class="help-block"><i class="fa fa-exclamation-circle"></i> <?= strtoupper($lang) ?>: <?= e($errors[$col]) ?></span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($r['inlines'] as $i => $inl): ?>
        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa <?= $inl['kind'] === 'document' ? 'fa-paperclip' : 'fa-picture-o' ?>"></i> <?= e($inl['label']) ?></strong></div>
            <div class="panel-body">
                <?php if ($obj && !empty($inline_rows[$i])): ?>
                    <table class="table table-condensed admin-inline-table">
                        <thead><tr><th>الملف</th><?php foreach ($inl['text'] as $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?><?php if ($inl['order']): ?><th style="width:90px">الترتيب</th><?php endif; ?><th style="width:60px">حذف</th></tr></thead>
                        <tbody>
                        <?php foreach ($inline_rows[$i] as $row): ?>
                            <tr>
                                <td>
                                    <?php if ($inl['kind'] === 'image'): ?>
                                        <a href="<?= e(media_url($row[$inl['file']])) ?>" target="_blank"><img src="<?= e(media_url($row[$inl['file']])) ?>" class="admin-thumb" alt=""></a>
                                    <?php else: ?>
                                        <a href="<?= e($row->get_download_url()) ?>" target="_blank"><i class="fa fa-file-o"></i> <?= e(basename((string) $row[$inl['file']])) ?></a>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($inl['text'] as $col => $lbl): $iname = 'inline[' . $i . '][' . $row['id'] . '][' . $col . ']'; ?>
                                    <td>
                                        <input type="text" class="form-control input-sm" name="<?= e($iname) ?>" value="<?= e($row[$col]) ?>">
                                        <?php if (in_array($col, $inl['model']::translatable(), true)): ?>
                                            <div class="admin-translations admin-translations-inline">
                                                <?php foreach (admin_other_langs() as $lang): ?>
                                                    <div class="admin-tr-row" data-lang="<?= e($lang) ?>">
                                                        <span class="admin-tr-lang"><?= e(strtoupper($lang)) ?></span>
                                                        <input type="text" class="form-control input-sm" dir="ltr" lang="<?= e($lang) ?>" name="inline[<?= $i ?>][<?= e($row['id']) ?>][<?= e($col . '_' . $lang) ?>]" value="<?= e($row->toArray()[$col . '_' . $lang] ?? '') ?>" data-tr-of="<?= e($iname) ?>" data-tr-lang="<?= e($lang) ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($inl['order']): ?>
                                    <td><input type="number" class="form-control input-sm" name="inline[<?= $i ?>][<?= e($row['id']) ?>][<?= e($inl['order']) ?>]" value="<?= e($row[$inl['order']]) ?>"></td>
                                <?php endif; ?>
                                <td class="text-center"><input type="checkbox" name="inline[<?= $i ?>][<?= e($row['id']) ?>][_delete]" value="1"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($obj): ?>
                    <p class="text-muted">لا توجد ملفات مرفقة بعد.</p>
                <?php endif; ?>
                <?php if (!$readonly): ?>
                    <label>إضافة ملفات جديدة (يمكن اختيار عدة ملفات):</label>
                    <input type="file" name="inline_new_<?= $i ?>[]" multiple class="form-control" accept="<?= $inl['kind'] === 'image' ? 'image/*' : '.' . e(implode(',.', $inl['ext'] ?? Upload::DOCUMENTS)) ?>">
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!$readonly): ?>
        <div class="admin-submit-row">
            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> حفظ</button>
            <button type="submit" name="_continue" value="1" class="btn btn-default">حفظ ومتابعة التعديل</button>
            <?php if ($r['can_add']): ?><button type="submit" name="_addanother" value="1" class="btn btn-default">حفظ وإضافة آخر</button><?php endif; ?>
            <?php if ($obj && $r['can_delete']): ?>
                <a href="<?= e(url('admin:delete', $r['key'], $obj['id'])) ?>" class="btn btn-danger pull-left"><i class="fa fa-trash"></i> حذف</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="admin-submit-row">
            <a href="<?= e(url('admin:list', $r['key'])) ?>" class="btn btn-default"><i class="fa fa-arrow-right"></i> رجوع للقائمة</a>
            <?php if ($obj && $r['can_delete']): ?>
                <a href="<?= e(url('admin:delete', $r['key'], $obj['id'])) ?>" class="btn btn-danger pull-left"><i class="fa fa-trash"></i> حذف</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</form>
<?php endblock(); ?>
