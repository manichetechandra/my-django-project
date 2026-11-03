<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('متابعة طلب ترشح') ?> <?= t('- مديرية الخدمات الجامعية') ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>

<?php block('content'); ?>
<div class="container cmp-page">
    <div class="cmp-title">
        <h1><i class="fa fa-search"></i> <?= t('متابعة طلب ترشح') ?></h1>
        <a href="<?= e(url('competitions:list')) ?>" class="btn btn-default"><i class="fa fa-trophy"></i> <?= t('مسابقات التوظيف') ?></a>
    </div>

    <?php if ($app): ?>
        <?php
        $info = [
            'pending'      => ['fa-inbox', 'تم استلام طلبك', 'طلبك مسجّل وفي انتظار دراسته من طرف لجنة المسابقة.'],
            'under_review' => ['fa-search', 'طلبك قيد الدراسة', 'لجنة المسابقة تدرس ملفك حالياً.'],
            'incomplete'   => ['fa-exclamation-triangle', 'ملفك ناقص', 'اطّلع على رسالة الإدارة أدناه لمعرفة ما ينقص ملفك.'],
            'accepted'     => ['fa-check-circle', 'قُبل ملفك', 'تهانينا! تابع صفحة المسابقة لمعرفة مواعيد المراحل القادمة.'],
            'rejected'     => ['fa-times-circle', 'لم يُقبل ملفك', 'نأسف، لم يُقبل ملفك في هذه المسابقة. اطّلع على رسالة الإدارة أدناه إن وُجدت.'],
        ][$app['status']] ?? ['fa-info-circle', '', ''];
        $step = ['pending' => 1, 'under_review' => 2, 'incomplete' => 2, 'accepted' => 3, 'rejected' => 3][$app['status']] ?? 1;
        ?>
        <div class="row">
            <div class="col-md-8">
                <div class="cmp-status is-<?= e($app['status']) ?>">
                    <i class="fa <?= e($info[0]) ?>"></i>
                    <div><h3><?= t($info[1]) ?></h3><p><?= t($info[2]) ?></p></div>
                </div>
                <ol class="cmp-steps">
                    <?php foreach (['استلام الطلب', 'دراسة الملف', 'القرار'] as $n => $label): ?>
                        <li class="<?= $n < $step ? 'done' : '' ?>"><?= t($label) ?></li>
                    <?php endforeach; ?>
                </ol>

                <?php if (trim((string) $app['candidate_message']) !== ''): ?>
                    <h4 style="margin-top:26px;color:#2c3e50"><i class="fa fa-envelope-o"></i> <?= t('رسالة الإدارة') ?></h4>
                    <div class="cmp-message" dir="auto"><?= nl2br(e($app['candidate_message'])) ?></div>
                <?php endif; ?>

                <h4 style="margin-top:26px;color:#2c3e50"><i class="fa fa-file-text-o"></i> <?= t('معلومات الطلب') ?></h4>
                <table class="cmp-kv">
                    <tr><th><?= t('رقم المتابعة') ?></th><td><span class="cmp-code" style="font-size:18px;display:inline"><?= e($app['tracking_code']) ?></span></td></tr>
                    <tr><th><?= t('المسابقة') ?></th><td><?php if ($c && $c['status'] !== 'draft'): ?><a href="<?= e($c->get_absolute_url()) ?>"><?= e($c['title']) ?></a><?php else: ?><?= e($c['title'] ?? '') ?><?php endif; ?></td></tr>
                    <tr><th><?= t('الرتبة المترشح لها') ?></th><td><?= e($app->post_label()) ?></td></tr>
                    <tr><th><?= t('المترشح') ?></th><td><?= e($app->full_name()) ?></td></tr>
                    <tr><th><?= t('تاريخ الإرسال') ?></th><td><?= e(format_date($app['submitted_at'], 'j F Y - H:i')) ?></td></tr>
                    <?php if ($c && !empty($c['exam_date'])): ?><tr><th><?= t('تاريخ الاختبار أو المقابلة') ?></th><td><?= e(format_date($c['exam_date'], 'j F Y')) ?></td></tr><?php endif; ?>
                    <tr><th><?= t('الوثائق المرفوعة') ?></th><td>
                        <?php foreach ($files as $f): ?><div><i class="fa fa-file-pdf-o text-danger"></i> <?= e($f['label']) ?></div><?php endforeach; ?>
                        <?php if (!$files): ?>—<?php endif; ?>
                    </td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="cmp-side-box">
                    <h4><i class="fa fa-lock"></i> <?= t('أمان حسابك') ?></h4>
                    <p class="cmp-muted" style="line-height:1.9"><?= t('لا تشارك رقم المتابعة والرمز السري مع أحد. أغلق هذه الصفحة بعد الاطلاع إن كنت تستعمل جهازاً عاماً (مقهى إنترنت).') ?></p>
                    <a href="<?= e(url('competitions:track')) ?>" class="btn btn-default btn-block"><i class="fa fa-sign-out"></i> <?= t('إغلاق') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="cmp-track-form" autocomplete="off">
            <?= csrf_field() ?>
            <p class="cmp-muted" style="line-height:1.9"><?= t('أدخل رقم المتابعة والرمز السري المكتوبين في وصل الترشح لمعرفة حالة طلبك ورسائل الإدارة.') ?></p>
            <?php if ($error): ?><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>
            <div class="form-group">
                <label for="tracking_code"><?= t('رقم المتابعة') ?></label>
                <input type="text" name="tracking_code" id="tracking_code" class="form-control" dir="ltr" value="<?= e($code) ?>" placeholder="C26-XXXXXXXX" maxlength="20" required autocapitalize="characters" spellcheck="false">
            </div>
            <div class="form-group">
                <label for="secret"><?= t('الرمز السري') ?></label>
                <input type="password" name="secret" id="secret" class="form-control" dir="ltr" placeholder="XXXXX-XXXXX" maxlength="20" required autocomplete="off" autocapitalize="characters" spellcheck="false">
            </div>
            <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-search"></i> <?= t('عرض حالة الطلب') ?></button>
            <p class="help-block small" style="margin-top:14px;line-height:1.8"><i class="fa fa-question-circle"></i>
                <?= t('أضعت الرمز السري؟ لا يمكن استرجاعه لأسباب أمنية. اتصل بمصلحة المستخدمين بالمديرية مع بطاقة تعريفك.') ?></p>
        </form>
    <?php endif; ?>
</div>
<?php endblock(); ?>
