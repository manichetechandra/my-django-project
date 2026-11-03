<?php
/** إشعار بالنتيجة الفردية (للمترشح صاحب الجلسة فقط، وبعد نشر نتائج المسابقة) */
$doc_title = __('إشعار بنتيجة المسابقة');
$footer_note = __('هذا الإشعار للإعلام؛ المرجع الرسمي هو محضر لجنة المسابقة.');
$r = (string) $app['result'];
include view_path('competitions/_print_open');
?>
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
            <?php if (trim((string) $app['seat_number']) !== ''): ?>
                <tr><th><?= t('رقم التسجيل') ?></th><td><span class="doc-seat"><?= e($app['seat_number']) ?></span></td></tr>
            <?php endif; ?>
        </table>
    </section>

    <section class="doc-result is-<?= e($r) ?>">
        <div class="doc-result-label"><?= t('النتيجة') ?></div>
        <div class="doc-result-value"><?= e(CompetitionApplication::choiceLabel('result', $r)) ?></div>
        <?php if ($app->score_text() !== '' || !empty($app['result_rank'])): ?>
            <div class="doc-result-figures">
                <?php if ($app->score_text() !== ''): ?><span><?= t('العلامة') . colon() ?> <b dir="ltr"><?= e($app->score_text()) ?> / 20</b></span><?php endif; ?>
                <?php if (!empty($app['result_rank'])): ?><span><?= t('الترتيب') . colon() ?> <b><?= (int) $app['result_rank'] ?></b></span><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (trim((string) $app['result_note']) !== ''): ?>
        <section class="doc-rules">
            <h2><?= t('ملاحظة') ?></h2>
            <p dir="auto"><?= nl2br(e($app['result_note'])) ?></p>
        </section>
    <?php endif; ?>
    <p class="doc-published"><?= t('تاريخ نشر النتائج') . colon() ?> <?= e(format_date($c['results_published_at'], 'j F Y')) ?></p>
<?php include view_path('competitions/_print_close'); ?>
