<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('وصل الترشح') ?> - <?= e($app['tracking_code']) ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>

<?php block('content'); ?>
<div class="container cmp-page">
    <div class="cmp-receipt">
        <div class="cmp-receipt-head">
            <img src="<?= asset('images/logo.svg') ?>" alt="">
            <div>
                <h1><?= t('وصل الترشح الإلكتروني') ?></h1>
                <div class="cmp-muted"><?= t('مديرية الخدمات الجامعية سطيف') ?></div>
            </div>
        </div>

        <div class="alert alert-success cmp-no-print"><i class="fa fa-check-circle"></i> <strong><?= t('تم استلام طلب ترشحك بنجاح.') ?></strong></div>

        <div class="cmp-codes">
            <div class="cmp-code-box">
                <small><?= t('رقم المتابعة') ?></small>
                <span class="cmp-code" id="cmp-tracking"><?= e($app['tracking_code']) ?></span>
                <img class="cmp-barcode" src="<?= e(barcode_data_uri($app['tracking_code'], 2, 50)) ?>" alt="<?= e($app['tracking_code']) ?>">
            </div>
            <div class="cmp-code-box is-secret">
                <small><?= t('الرمز السري') ?></small>
                <span class="cmp-code" id="cmp-secret"><?= e($secret) ?></span>
                <small><i class="fa fa-lock"></i> <?= t('لا تعطه لأي أحد') ?></small>
            </div>
        </div>

        <div class="cmp-warn">
            <i class="fa fa-exclamation-triangle"></i> <strong><?= t('احتفظ بهذين الرمزين الآن:') ?></strong>
            <?= t('اطبع هذا الوصل أو احفظه، أو انسخ الرمزين. الرمز السري لا يظهر مرة أخرى ولا يمكن لأحد استرجاعه — ولا حتى الإدارة — لأنه محفوظ بطريقة لا تسمح بقراءته. ستحتاجهما لمتابعة طلبك ومعرفة النتيجة.') ?>
        </div>

        <h4 style="margin-top:22px;color:#2c3e50"><i class="fa fa-file-text-o"></i> <?= t('ملخص الطلب') ?></h4>
        <table class="cmp-kv">
            <tr><th><?= t('المسابقة') ?></th><td><strong><?= e($c['title']) ?></strong><?= $c->reference_number() !== '' ? ' — ' . t('إعلان رقم {ref}', ['ref' => $c->reference_number()]) : '' ?></td></tr>
            <tr><th><?= t('الرتبة المترشح لها') ?></th><td><?= e($app->post_label()) ?></td></tr>
            <tr><th><?= t('المترشح') ?></th><td><?= e($app->full_name()) ?> — <span dir="ltr"><?= e($app->full_name_latin()) ?></span></td></tr>
            <tr><th><?= t('تاريخ الإرسال') ?></th><td><?= e(format_date($app['submitted_at'], 'j F Y - H:i')) ?></td></tr>
            <tr><th><?= t('الوثائق المرفوعة') ?></th><td>
                <?php foreach ($files as $f): ?><div><i class="fa fa-check text-success"></i> <?= e($f['label']) ?> <small class="cmp-muted"><bdi><?= e($f['original_name']) ?></bdi> · <bdi><?= e(human_filesize($f['size'])) ?></bdi></small></div><?php endforeach; ?>
                <?php if (!$files): ?>—<?php endif; ?>
            </td></tr>
        </table>
        <p class="cmp-muted small"><?= t('تُنشر قوائم المقبولين والنتائج في صفحة المسابقة، ويمكنك معرفة حالة طلبك في أي وقت من صفحة «متابعة طلب ترشح».') ?></p>

        <div class="cmp-no-print" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px">
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> <?= t('طباعة الوصل') ?></button>
            <button type="button" class="btn btn-default" id="cmp-copy" data-done="<?= t('تم النسخ') ?>"><i class="fa fa-copy"></i> <?= t('نسخ الرمزين') ?></button>
            <a href="<?= e($c->get_absolute_url()) ?>" class="btn btn-default"><i class="fa fa-trophy"></i> <?= t('صفحة المسابقة') ?></a>
            <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <button type="submit" name="done" value="1" class="btn btn-success"><i class="fa fa-check"></i> <?= t('احتفظت بالرمزين، أخفِ الوصل') ?></button>
            </form>
        </div>
    </div>
</div>
<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
(function () {
    var btn = document.getElementById('cmp-copy');
    btn.addEventListener('click', function () {
        var text = <?= js_t('رقم المتابعة') ?> + ': ' + document.getElementById('cmp-tracking').textContent + '\n'
            + <?= js_t('الرمز السري') ?> + ': ' + document.getElementById('cmp-secret').textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () { btn.innerHTML = '<i class="fa fa-check"></i> ' + btn.getAttribute('data-done'); });
        }
    });
})();
</script>
<?php endblock(); ?>
