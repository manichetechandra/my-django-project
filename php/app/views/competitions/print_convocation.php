<?php
/** الاستدعاء (للمترشح صاحب الجلسة فقط — لا رقم طلب في الرابط) */
$doc_title = __('استدعاء');
$footer_note = '';
include view_path('competitions/_print_open');
?>
    <p class="doc-subtitle"><?= t('لاجتياز') . colon() ?> <b><?= e($exam['title']) ?></b></p>
    <p class="doc-competition"><?= e($c['title']) ?><?php if ($c->reference_number() !== ''): ?> — <?= t('إعلان رقم {ref}', ['ref' => $c->reference_number()]) ?><?php endif; ?></p>

    <section class="doc-box">
        <h2><?= t('المترشح') ?></h2>
        <table class="doc-kv">
            <tr><th><?= t('اللقب والاسم') ?></th><td><b><?= e($app->full_name()) ?></b></td></tr>
            <tr><th><?= t('بالأحرف اللاتينية') ?></th><td><bdi dir="ltr" class="doc-latin"><?= e($app->full_name_latin()) ?></bdi></td></tr>
            <?php if (!empty($personal['birth_date'])): ?>
                <tr><th><?= t('تاريخ ومكان الميلاد') ?></th><td><?= e(format_date($personal['birth_date'], 'd/m/Y')) ?><?= $personal['birth_place'] !== '' ? ' — ' . e($personal['birth_place']) : '' ?></td></tr>
            <?php endif; ?>
            <tr><th><?= t('الرتبة المترشح لها') ?></th><td><?= e($app->post_label()) ?></td></tr>
            <tr><th><?= t('رقم التسجيل') ?></th><td><span class="doc-seat"><?= e($app['seat_number']) ?></span></td></tr>
        </table>
    </section>

    <section class="doc-box doc-box-strong">
        <h2><?= t('الموعد') ?></h2>
        <table class="doc-kv">
            <tr><th><?= t('اليوم') ?></th><td><b><?= e(format_date($exam['starts_at'], 'l j F Y')) ?></b></td></tr>
            <tr><th><?= t('الساعة') ?></th><td><b dir="ltr"><?= e(format_date($exam['starts_at'], 'H:i')) ?></b></td></tr>
            <?php if (trim((string) $exam['place']) !== ''): ?>
                <tr><th><?= t('المكان') ?></th><td><b><?= e($exam['place']) ?></b></td></tr>
            <?php endif; ?>
        </table>
    </section>

    <section class="doc-rules">
        <h2><?= t('تعليمات') ?></h2>
        <ul>
            <?php if (trim((string) $exam['instructions']) !== ''): ?><li><b><?= e($exam['instructions']) ?></b></li><?php endif; ?>
            <li><?= t('هذا الاستدعاء وبطاقة التعريف الوطنية الأصلية إلزاميان لدخول مكان الاختبار.') ?></li>
            <li><?= t('احضر قبل الموعد بثلاثين دقيقة على الأقل؛ لا يُقبل أي مترشح بعد بداية الاختبار.') ?></li>
            <li><?= t('يُمنع استعمال الهاتف النقال وكل وسائل الاتصال داخل قاعة الاختبار.') ?></li>
        </ul>
    </section>
<?php include view_path('competitions/_print_close'); ?>
