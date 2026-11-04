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

<?php $tr_fields = admin_translatable($r); $has_rich = !$readonly && array_filter($r['fields'], fn($spec) => $spec['type'] === 'richtext'); ?>
<?php if ($has_rich && !admin_tinymce()): ?>
    <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>
        محرر النصوص <strong>TinyMCE</strong> غير موجود: ضع مجلد TinyMCE (الذي فيه <code>tinymce.min.js</code> في أي مستوى) داخل مجلد الموقع،
        مثلاً <code dir="ltr">tinymce/</code> أو <code dir="ltr">assets/vendor/tinymce/</code>، أو اكتب مساره في <code>config.php</code>
        (<code dir="ltr">'editor' => ['tinymce' => '...']</code>). إلى ذلك الحين تعمل الحقول المنسّقة كخانات نص (HTML).
    </div>
<?php endif; ?>
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
                                    <?php if ($spec['type'] === 'richtext' && $readonly): ?>
                                        <div class="form-control-static admin-richtext-view" dir="ltr" lang="<?= e($lang) ?>"><?= RichText::render($values[$col] ?? '') ?></div>
                                    <?php elseif ($spec['type'] === 'richtext'): ?>
                                        <textarea name="<?= e($col) ?>" id="f_<?= e($col) ?>" class="form-control input-sm admin-richtext" rows="<?= max(3, (int) ceil(($spec['rows'] ?? 8) / 2)) ?>" dir="ltr" lang="<?= e($lang) ?>" data-richtext="<?= e($lang) ?>" data-tr-of="<?= e($f) ?>" data-tr-lang="<?= e($lang) ?>"><?= e(RichText::forEditor($values[$col] ?? '')) ?></textarea>
                                    <?php elseif ($spec['type'] === 'textarea'): ?>
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
            <?php if ($has_rich && ($tinymce = admin_tinymce())): ?>
                <p class="admin-editor-source small text-muted" data-editor-source>
                    <i class="fa fa-pencil-square-o"></i> محرر النصوص: <bdi dir="ltr">TinyMCE <span data-tinymce-version></span></bdi>
                    — <?= $tinymce['bundled'] ? 'النسخة المرفقة بالمشروع' : 'نسختك' ?>: <code dir="ltr"><?= e($tinymce['path']) ?></code>
                    <?php if ($tinymce['lang_url'] !== ''): ?>(ملف العربية من نسخة المشروع)<?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($r['inlines'] as $i => $inl): $cols = admin_inline_columns($inl); $isRows = ($inl['kind'] ?? '') === 'rows'; ?>
        <div class="panel panel-default admin-panel" id="inline-<?= e($inl['anchor'] ?? $i) ?>">
            <div class="panel-heading"><strong><i class="fa <?= e($inl['icon'] ?? ($inl['kind'] === 'document' ? 'fa-paperclip' : ($isRows ? 'fa-list' : 'fa-picture-o'))) ?>"></i> <?= e($inl['label']) ?></strong>
                <?php if (!empty($inl['help'])): ?><div class="small text-muted"><?= e($inl['help']) ?></div><?php endif; ?>
            </div>
            <div class="panel-body">
                <?php $newRows = $isRows && !$readonly ? (int) ($inl['new_rows'] ?? 3) : 0; $postedNew = (array) ($_POST['inline_new'][$i] ?? []); ?>
                <?php if (($obj && !empty($inline_rows[$i])) || $newRows): ?>
                    <div class="table-responsive">
                    <table class="table table-condensed admin-inline-table">
                        <thead><tr>
                            <?php if (!$isRows): ?><th>الملف</th><?php endif; ?>
                            <?php foreach ($cols as $spec): ?><th<?= ($spec['type'] ?? 'text') === 'text' ? '' : ' style="width:' . (($spec['type'] ?? '') === 'select' ? 170 : 100) . 'px"' ?>><?= e($spec['label']) ?><?= !empty($spec['required']) ? ' <span class="text-danger">*</span>' : '' ?></th><?php endforeach; ?>
                            <?php if ($inl['order']): ?><th style="width:90px">الترتيب</th><?php endif; ?>
                            <th style="width:60px">حذف</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($obj ? ($inline_rows[$i] ?? []) : [] as $row): $raw = $row->toArray(); $name = 'inline[' . $i . '][' . $row['id'] . ']'; ?>
                            <tr>
                                <?php if (!$isRows): ?>
                                <td>
                                    <?php if ($inl['kind'] === 'image'): ?>
                                        <a href="<?= e(media_url($row[$inl['file']])) ?>" target="_blank"><img src="<?= e(media_url($row[$inl['file']])) ?>" class="admin-thumb" alt=""></a>
                                    <?php else: ?>
                                        <a href="<?= e($row->get_download_url()) ?>" target="_blank"><i class="fa fa-file-o"></i> <?= e(basename((string) $row[$inl['file']])) ?></a>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php foreach ($cols as $col => $spec): ?>
                                    <td><?= admin_inline_input($name, $col, $spec, $raw[$col] ?? '', $inl['model'], $raw) ?></td>
                                <?php endforeach; ?>
                                <?php if ($inl['order']): ?>
                                    <td><input type="number" class="form-control input-sm" name="<?= e($name . '[' . $inl['order'] . ']') ?>" value="<?= e($raw[$inl['order']] ?? 0) ?>"></td>
                                <?php endif; ?>
                                <td class="text-center"><input type="checkbox" name="<?= e($name . '[_delete]') ?>" value="1" aria-label="حذف"></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php for ($k = 0; $k < $newRows; $k++): $vals = is_array($postedNew[$k] ?? null) ? $postedNew[$k] : []; $name = 'inline_new[' . $i . '][' . $k . ']'; ?>
                            <tr class="admin-inline-new">
                                <?php foreach ($cols as $col => $spec): $def = ($spec['type'] ?? 'text') === 'bool' && !$vals ? ($spec['default'] ?? 0) : ($vals[$col] ?? ($spec['default'] ?? '')); ?>
                                    <td><?= admin_inline_input($name, $col, $spec + ($k === 0 && !empty($spec['example']) ? ['placeholder' => $spec['example']] : []), $def, $inl['model'], $vals) ?></td>
                                <?php endforeach; ?>
                                <?php if ($inl['order']): ?>
                                    <td><input type="number" class="form-control input-sm" name="<?= e($name . '[' . $inl['order'] . ']') ?>" value="<?= e($vals[$inl['order']] ?? (count($inline_rows[$i] ?? []) + $k + 1)) ?>"></td>
                                <?php endif; ?>
                                <td class="text-center text-muted small"><i class="fa fa-plus" title="سطر جديد"></i></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php if ($newRows): ?><p class="help-block small text-muted"><i class="fa fa-info-circle"></i> الأسطر الفارغة تُتجاهل. احفظ ثم «حفظ ومتابعة التعديل» لإضافة أسطر أخرى.</p><?php endif; ?>
                <?php elseif ($obj): ?>
                    <p class="text-muted">لا توجد ملفات مرفقة بعد.</p>
                <?php endif; ?>
                <?php if (!$readonly && !$isRows): ?>
                    <label>إضافة ملفات جديدة (يمكن اختيار عدة ملفات):</label>
                    <div class="admin-inline-upload">
                        <input type="file" name="inline_new_<?= $i ?>[]" multiple class="form-control" accept="<?= $inl['kind'] === 'image' ? 'image/*' : '.' . e(implode(',.', $inl['ext'] ?? Upload::DOCUMENTS)) ?>">
                        <?php foreach ($cols as $col => $spec): if (($spec['type'] ?? 'text') !== 'select') { continue; } ?>
                            <label class="small"><?= e($spec['label']) ?>:
                                <?= admin_inline_input('inline_new_defaults[' . $i . ']', $col, $spec, $spec['default'] ?? '', $inl['model']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
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

<?php if ($has_rich && ($tinymce = admin_tinymce())): ?>
<?php /* محرر النصوص المنسّقة: TinyMCE — نسخة المستخدم أينما وضعها في مجلد الموقع، وإلا assets/vendor/tinymce (admin_tinymce؛ محلي بلا CDN) */ ?>
<?php block('extra_js'); ?>
<script src="<?= e($tinymce['url']) ?>"></script>
<script src="<?= asset('js/admin-richtext.js') ?>" data-base="<?= e($tinymce['base']) ?>" data-suffix="<?= e($tinymce['suffix']) ?>" data-lang="<?= e($tinymce['lang']) ?>" data-lang-url="<?= e($tinymce['lang_url']) ?>"></script>
<?php endblock(); ?>
<?php endif; ?>
