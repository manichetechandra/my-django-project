<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('الترشح الإلكتروني') ?> - <?= e($c['title']) ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>

<?php block('content'); ?>
<?php
/** حقل بعنوانه وأخطائه: $field('nin', 'نص مساعد') */
$field = function (string $name, string $help = '') use ($form): string {
    $f = $form->fields[$name];
    $req = ($f['required'] ?? true) ? ' <span class="req">*</span>' : '';
    return '<div class="form-group' . ($form->errors($name) ? ' has-error' : '') . '">'
        . '<label for="' . e($form->id($name)) . '">' . e($form->label($name)) . $req . '</label>'
        . $form->widget($name)
        . ($help !== '' ? '<span class="help-block">' . e(__($help)) . '</span>' : '')
        . $form->errors_html($name) . '</div>';
};
?>
<div class="container cmp-page">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('competitions:list')) ?>"><?= t('مسابقات التوظيف') ?></a></li>
        <li><a href="<?= e($c->get_absolute_url()) ?>"><?= e(truncatechars((string) $c['title'], 50)) ?></a></li>
        <li class="active"><?= t('الترشح الإلكتروني') ?></li>
    </ol>
    <div class="cmp-title">
        <h1><i class="fa fa-pencil-square-o"></i> <?= t('الترشح الإلكتروني') ?></h1>
        <a href="<?= e(url('competitions:track')) ?>" class="btn btn-default"><i class="fa fa-search"></i> <?= t('متابعة طلب ترشح') ?></a>
    </div>
    <p class="cmp-intro"><strong><?= e($c['title']) ?></strong>
        <?php if ($blocked === null): ?><br><span class="cmp-countdown"><i class="fa fa-hourglass-half"></i> <?= t('آخر أجل: {date} على الساعة 23:59', ['date' => format_date($c['closes_at'], 'j F Y')]) ?> — <?= t('يتبقى: {time}', ['time' => $c->remaining_text()]) ?></span><?php endif; ?>
    </p>

    <?php if ($blocked !== null): ?>
        <div class="alert alert-warning" style="font-size:16px"><i class="fa fa-info-circle"></i> <?= e($blocked) ?></div>
        <a href="<?= e($c->get_absolute_url()) ?>" class="btn btn-default"><i class="fa fa-arrow-right"></i> <?= t('العودة إلى صفحة المسابقة') ?></a>
    <?php else: ?>
        <div class="cmp-privacy">
            <i class="fa fa-shield"></i> <strong><?= t('كيف نحمي معطياتك الشخصية؟') ?></strong>
            <ul>
                <li><?= t('رقم التعريف وتاريخ ومكان الميلاد والعنوان والهاتف تُحفظ مشفّرة، ولا يُحفظ رقم التعريف واضحاً أبداً.') ?></li>
                <li><?= t('وثائقك تُشفّر فور استلامها وتُحفظ خارج الموقع العام، ولا يطلع عليها إلا الموظفون المكلفون بالمسابقة، ويُسجَّل كل اطلاع.') ?></li>
                <li><?= t('بعد الإرسال تحصل على رقم متابعة ورمز سري لمتابعة طلبك. لن نطلب منك الرمز السري أبداً عبر الهاتف أو البريد.') ?></li>
            </ul>
        </div>

        <form method="post" enctype="multipart/form-data" class="cmp-form" id="cmp-apply" accept-charset="UTF-8">
            <?= csrf_field() ?>
            <input type="hidden" name="_ft" value="<?= e($form_token) ?>">
            <div class="cmp-hp" aria-hidden="true">
                <label for="website_url">Website</label>
                <input type="text" name="website_url" id="website_url" value="" tabindex="-1" autocomplete="off">
            </div>
            <?= $form->non_field_errors_html() ?>
            <?php if ($form->has_errors()): ?>
                <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= t('يرجى تصحيح الأخطاء المبيّنة أدناه.') ?>
                    <?php if ($requirements): ?><?= t('لأسباب أمنية يجب إعادة اختيار ملفات PDF قبل الإرسال من جديد.') ?><?php endif; ?></div>
            <?php endif; ?>

            <fieldset>
                <legend><span class="cmp-step">1</span> <?= t('المنصب المطلوب') ?></legend>
                <?= $field('post_id') ?>
            </fieldset>

            <fieldset>
                <legend><span class="cmp-step">2</span> <?= t('الهوية') ?></legend>
                <div class="row">
                    <div class="col-sm-6"><?= $field('last_name') ?></div>
                    <div class="col-sm-6"><?= $field('first_name') ?></div>
                    <div class="col-sm-6"><?= $field('last_name_latin') ?></div>
                    <div class="col-sm-6"><?= $field('first_name_latin') ?></div>
                    <div class="col-sm-4"><?= $field('gender') ?></div>
                    <div class="col-sm-4"><?= $field('birth_date') ?></div>
                    <div class="col-sm-4"><?= $field('birth_place') ?></div>
                    <div class="col-sm-6"><?= $field('nin', 'رقم من 18 رقماً مكتوب على بطاقة التعريف الوطنية البيومترية.') ?></div>
                    <div class="col-sm-6"><?= $field('national_service', 'للذكور. اختر «غير معني» إن لم تكن معنياً.') ?></div>
                </div>
            </fieldset>

            <fieldset>
                <legend><span class="cmp-step">3</span> <?= t('معلومات الاتصال') ?></legend>
                <div class="row">
                    <div class="col-sm-6"><?= $field('phone') ?></div>
                    <div class="col-sm-6"><?= $field('email') ?></div>
                    <div class="col-sm-8"><?= $field('address') ?></div>
                    <div class="col-sm-4"><?= $field('wilaya') ?></div>
                </div>
            </fieldset>

            <fieldset>
                <legend><span class="cmp-step">4</span> <?= t('المؤهلات') ?></legend>
                <div class="row">
                    <div class="col-sm-6"><?= $field('diploma') ?></div>
                    <div class="col-sm-6"><?= $field('specialty') ?></div>
                    <div class="col-sm-6"><?= $field('graduation_year') ?></div>
                    <div class="col-sm-6"><?= $field('experience_years') ?></div>
                </div>
            </fieldset>

            <?php if ($requirements): ?>
                <fieldset>
                    <legend><span class="cmp-step">5</span> <?= t('الوثائق (PDF)') ?></legend>
                    <p class="help-block"><i class="fa fa-info-circle"></i>
                        <?= t('ملف PDF واحد لكل وثيقة، الحد الأقصى {mb} ميغابايت للملف و{total} ميغابايت للمجموع. امسح الوثائق ضوئياً بوضوح (150 نقطة تكفي). لا تُقبل الملفات المحمية بكلمة مرور.', ['mb' => $limits['file_mb'], 'total' => $limits['total_mb']]) ?></p>
                    <?php foreach ($requirements as $r): $name = 'doc_' . $r['id']; ?>
                        <div class="cmp-doc<?= $r['is_required'] ? ' is-required' : '' ?><?= $form->errors($name) ? ' has-error' : '' ?>">
                            <label for="<?= e($form->id($name)) ?>"><i class="fa fa-file-pdf-o text-danger"></i> <?= e($r['label']) ?>
                                <?= $r['is_required'] ? '<span class="req">*</span>' : '<small class="cmp-muted">(' . t('اختيارية') . ')</small>' ?></label>
                            <?= $form->widget($name) ?>
                            <div class="cmp-file-state" aria-live="polite"></div>
                            <?= $form->errors_html($name) ?>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>

            <fieldset>
                <legend><span class="cmp-step"><?= $requirements ? 6 : 5 ?></span> <?= t('التصريح والموافقة') ?></legend>
                <?php foreach (['declaration', 'consent'] as $name): ?>
                    <div class="form-group<?= $form->errors($name) ? ' has-error' : '' ?>">
                        <label class="cmp-check"><?= $form->widget($name) ?> <span><?= e($form->label($name)) ?> <span class="req">*</span></span></label>
                        <?= $form->errors_html($name) ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <div class="cmp-submit">
                <button type="submit" class="btn btn-success btn-lg" data-sending="<?= t('جارٍ الإرسال والتشفير...') ?>"><i class="fa fa-paper-plane"></i> <?= t('إرسال طلب الترشح') ?></button>
                <p class="help-block"><?= t('تأكد من صحة كل المعلومات: لا يمكن تعديل الطلب بعد إرساله.') ?></p>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endblock(); ?>

<?php block('extra_js'); ?>
<?php if ($blocked === null): ?>
<script>
// فحص أولي في المتصفح (الحجم والنوع) قبل رفع الملفات — الفحص الحقيقي يتم في الخادم
(function () {
    var form = document.getElementById('cmp-apply');
    var maxTotal = <?= (int) $limits['total'] ?>;
    var msgBig = <?= js_t('الملف أكبر من الحد المسموح ({mb} ميغابايت).', ['mb' => $limits['file_mb']]) ?>;
    var msgType = <?= js_t('يُقبل ملف PDF فقط.') ?>;
    var msgTotal = <?= js_t('مجموع أحجام الوثائق أكبر من {mb} ميغابايت. قلّل حجم الملفات (مسح ضوئي بدقة 150 نقطة يكفي).', ['mb' => $limits['total_mb']]) ?>;
    function size(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.ceil(b / 1024) + ' KB'; }
    form.querySelectorAll('input.cmp-file').forEach(function (input) {
        input.addEventListener('change', function () {
            var state = input.parentNode.querySelector('.cmp-file-state');
            var f = input.files[0];
            input.setCustomValidity('');
            state.className = 'cmp-file-state';
            state.textContent = '';
            if (!f) { return; }
            var bad = !/\.pdf$/i.test(f.name) ? msgType : (f.size > parseInt(input.getAttribute('data-max'), 10) ? msgBig : '');
            input.setCustomValidity(bad);
            state.className = 'cmp-file-state ' + (bad ? 'is-bad' : 'is-ok');
            state.textContent = bad ? '✗ ' + bad : '✓ ' + f.name + ' (' + size(f.size) + ')';
        });
    });
    form.addEventListener('submit', function (ev) {
        var total = 0;
        form.querySelectorAll('input.cmp-file').forEach(function (i) { if (i.files[0]) { total += i.files[0].size; } });
        if (total > maxTotal) { ev.preventDefault(); alert(msgTotal); return; }
        var btn = form.querySelector('button[type=submit]');
        setTimeout(function () { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + btn.getAttribute('data-sending'); }, 0);
    });
})();
</script>
<?php endif; ?>
<?php endblock(); ?>
