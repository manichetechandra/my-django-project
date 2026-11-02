<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<h1 class="admin-title"><i class="fa fa-key"></i> تغيير كلمة المرور</h1>
<div class="row">
    <div class="col-md-6">
        <form method="post" class="panel panel-default admin-panel">
            <div class="panel-body">
                <?= csrf_field() ?>
                <?= $form->non_field_errors_html() ?>
                <?php foreach (['old_password', 'new_password1', 'new_password2'] as $f): ?>
                    <div class="form-group <?= $form->errors($f) ? 'has-error' : '' ?>">
                        <label for="id_<?= $f ?>"><?= e($form->label($f)) ?></label>
                        <?= $form->widget($f) ?>
                        <?php foreach ($form->errors($f) as $err): ?><span class="help-block"><?= e($err) ?></span><?php endforeach; ?>
                        <?php if ($form->help_text($f)): ?><span class="help-block small text-muted"><?= e($form->help_text($f)) ?></span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> تغيير كلمة المرور</button>
            </div>
        </form>
    </div>
</div>
<?php endblock(); ?>
