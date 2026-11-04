<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($c['title']) ?> <?= t('- مديرية الخدمات الجامعية') ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>

<?php block('content'); ?>
<?php $phase = $c->phase(); $canApply = $c->accepts_applications() && $posts; ?>
<div class="container cmp-page">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('competitions:list')) ?>"><?= t('مسابقات التوظيف') ?></a></li>
        <li class="active"><?= e(truncatechars((string) $c['title'], 60)) ?></li>
    </ol>

    <?php if ($c['status'] === 'draft'): ?>
        <div class="alert alert-warning"><i class="fa fa-eye-slash"></i> <?= t('معاينة: هذه المسابقة مسودة غير منشورة، لا يراها الزوار.') ?></div>
    <?php elseif ($phase === 'cancelled'): ?>
        <div class="alert alert-danger"><i class="fa fa-ban"></i> <?= t('أُلغيت هذه المسابقة.') ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <header class="cmp-head">
                <div class="cmp-tags">
                    <span class="cmp-badge is-<?= e($c['status'] === 'draft' ? 'draft' : $phase) ?>"><?= e($c->get_phase_display()) ?></span>
                    <span class="cmp-mode"><i class="fa fa-briefcase"></i> <?= e($c->get_recruitment_mode_display()) ?></span>
                </div>
                <h1><?= e($c['title']) ?></h1>
                <?php if ($c->reference_number() !== ''): ?><div class="cmp-muted"><i class="fa fa-hashtag"></i> <?= t('إعلان رقم {ref}', ['ref' => $c->reference_number()]) ?></div><?php endif; ?>
            </header>

            <div class="cmp-facts">
                <div class="cmp-fact"><small><?= t('بداية الإيداع') ?></small><strong><?= e(format_date($c['opens_at'], 'j F Y')) ?></strong></div>
                <div class="cmp-fact"><small><?= t('آخر أجل للإيداع') ?></small><strong><?= e(format_date($c['closes_at'], 'j F Y')) ?></strong></div>
                <?php if (!empty($c['exam_date'])): ?>
                    <div class="cmp-fact"><small><?= t('تاريخ الاختبار أو المقابلة') ?></small><strong><?= e(format_date($c['exam_date'], 'j F Y')) ?></strong></div>
                <?php endif; ?>
                <div class="cmp-fact"><small><?= t('عدد المناصب') ?></small><strong><?= e($c->total_positions()) ?></strong></div>
            </div>

            <?php if (trim((string) $c['description']) !== ''): ?>
                <section class="cmp-section">
                    <h3><i class="fa fa-bullhorn"></i> <?= t('نص الإعلان') ?></h3>
                    <div class="cmp-text rich-text"><?= rich_text($c['description']) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($posts): ?>
                <section class="cmp-section">
                    <h3><i class="fa fa-sitemap"></i> <?= t('الرتب والمناصب المفتوحة') ?></h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped cmp-posts">
                            <thead><tr><th><?= t('الرتبة') ?></th><th style="width:110px" class="text-center"><?= t('عدد المناصب') ?></th><th><?= t('الشهادة أو التخصص المطلوب') ?></th><th><?= t('مكان التعيين') ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr><td><strong><?= e($p['title']) ?></strong></td><td class="num"><?= e($p['positions']) ?></td><td><?= e($p['requirements']) ?></td><td><?= e($p['location']) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($c->conditions_list()): ?>
                <section class="cmp-section">
                    <h3><i class="fa fa-check-square-o"></i> <?= t('شروط المشاركة') ?></h3>
                    <ul class="cmp-list">
                        <?php foreach ($c->conditions_list() as $line): ?><li><i class="fa fa-check"></i> <?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($requirements): ?>
                <section class="cmp-section">
                    <h3><i class="fa fa-folder-open"></i> <?= t('ملف الترشح') ?></h3>
                    <ul class="cmp-list">
                        <?php foreach ($requirements as $r): ?>
                            <li><i class="fa fa-file-pdf-o"></i> <?= e($r['label']) ?> <?php if (!$r['is_required']): ?><small class="cmp-muted">(<?= t('اختيارية') ?>)</small><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!empty($c['online_apply']) && in_array($phase, ['open', 'upcoming'], true)): ?>
                        <p class="help-block"><i class="fa fa-info-circle"></i> <?= t('في الترشح الإلكتروني تُرفع كل وثيقة بصيغة PDF (الحد الأقصى {mb} ميغابايت للملف).', ['mb' => $limits['file_mb']]) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (trim((string) $c['deposit_info']) !== ''): ?>
                <section class="cmp-section">
                    <h3><i class="fa fa-map-marker"></i> <?= t('إيداع الملفات') ?></h3>
                    <div class="cmp-text rich-text"><?= rich_text($c['deposit_info']) ?></div>
                </section>
            <?php endif; ?>

            <?php foreach (['results' => [$files['results'], 'القوائم والنتائج', 'fa-list-ol'], 'documents' => [$files['documents'], 'الإعلان والوثائق للتحميل', 'fa-download']] as $group => [$list, $title, $icon]): ?>
                <?php if ($list): ?>
                    <section class="cmp-section">
                        <h3><i class="fa <?= e($icon) ?>"></i> <?= t($title) ?></h3>
                        <ul class="cmp-files<?= $group === 'results' ? ' is-results' : '' ?>">
                            <?php foreach ($list as $f): ?>
                                <li>
                                    <i class="fa <?= e($f->icon()) ?>"></i>
                                    <div class="cmp-file-info">
                                        <strong><?= e($f['title']) ?></strong>
                                        <small><?= e($f->get_kind_display()) ?> · <bdi><?= e(strtoupper($f->extension())) ?></bdi> · <bdi><?= e(human_filesize($f['file_size'])) ?></bdi> · <bdi><?= e(format_date($f['uploaded_at'], 'd/m/Y')) ?></bdi></small>
                                    </div>
                                    <a href="<?= e($f->get_download_url()) ?>" class="btn btn-sm btn-success" target="_blank" rel="noopener"><i class="fa fa-download"></i> <?= t('تحميل') ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <aside class="col-md-4">
            <div class="cmp-cta">
                <?php if ($canApply): ?>
                    <h4><i class="fa fa-hourglass-half"></i> <?= t('الترشح مفتوح') ?></h4>
                    <div class="cmp-clock" data-deadline="<?= e($c->deadline_ts()) ?>" data-now="<?= time() ?>">
                        <div><b data-u="d">--</b><span><?= t('يوم') ?></span></div>
                        <div><b data-u="h">--</b><span><?= t('ساعة') ?></span></div>
                        <div><b data-u="m">--</b><span><?= t('دقيقة') ?></span></div>
                        <div><b data-u="s">--</b><span><?= t('ثانية') ?></span></div>
                    </div>
                    <p><?= t('آخر أجل: {date} على الساعة 23:59', ['date' => format_date($c['closes_at'], 'j F Y')]) ?></p>
                    <a href="<?= e(url('competitions:apply', $c['slug'])) ?>" class="btn btn-success btn-lg btn-block"><i class="fa fa-pencil-square-o"></i> <?= t('الترشح الإلكتروني') ?></a>
                <?php elseif ($phase === 'upcoming'): ?>
                    <h4><i class="fa fa-clock-o"></i> <?= t('قريباً') ?></h4>
                    <p><?= t('يبدأ إيداع الملفات يوم {date}.', ['date' => format_date($c['opens_at'], 'j F Y')]) ?></p>
                <?php elseif ($phase === 'open'): ?>
                    <h4><i class="fa fa-folder-open"></i> <?= t('الإيداع مفتوح') ?></h4>
                    <p><?= t('الترشح لهذه المسابقة بإيداع الملف الورقي (انظر «إيداع الملفات»). آخر أجل: {date}.', ['date' => format_date($c['closes_at'], 'j F Y')]) ?></p>
                <?php else: ?>
                    <h4><i class="fa fa-lock"></i> <?= $phase === 'cancelled' ? t('ملغاة') : t('انتهى أجل الترشح') ?></h4>
                    <p><?= $files['results'] ? t('القوائم والنتائج منشورة في هذه الصفحة.') : t('تُنشر قوائم المقبولين والنتائج في هذه الصفحة.') ?></p>
                <?php endif; ?>
                <a href="<?= e(url('competitions:track')) ?>" class="btn btn-default btn-block"><i class="fa fa-search"></i> <?= t('متابعة طلب ترشح') ?></a>
            </div>

            <div class="cmp-side-box">
                <h4><i class="fa fa-shield"></i> <?= t('معطياتك محمية') ?></h4>
                <p class="cmp-muted" style="line-height:1.9;margin:0"><?= t('رقم التعريف والهاتف والعنوان ووثائقك تُحفظ مشفّرة، ولا يطلع عليها إلا الموظفون المكلفون بهذه المسابقة، ويُسجَّل كل اطلاع عليها.') ?></p>
            </div>

            <?php if ($others): ?>
                <div class="cmp-side-box">
                    <h4><i class="fa fa-trophy"></i> <?= t('مسابقات أخرى مفتوحة') ?></h4>
                    <ul class="list-unstyled" style="margin:0">
                        <?php foreach ($others as $o): ?>
                            <li style="padding:6px 0;border-bottom:1px dashed #e6eaee"><a href="<?= e($o->get_absolute_url()) ?>"><?= e($o['title']) ?></a>
                                <div class="small cmp-countdown"><?= t('يتبقى: {time}', ['time' => $o->remaining_text()]) ?></div></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// العد التنازلي لآخر أجل الترشح (بتوقيت الخادم حتى لو كانت ساعة الجهاز خاطئة)
(function () {
    var clock = document.querySelector('.cmp-clock');
    if (!clock) { return; }
    var deadline = parseInt(clock.getAttribute('data-deadline'), 10) * 1000;
    var offset = parseInt(clock.getAttribute('data-now'), 10) * 1000 - Date.now();
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var left = Math.max(0, deadline - (Date.now() + offset));
        var s = Math.floor(left / 1000);
        clock.querySelector('[data-u="d"]').textContent = Math.floor(s / 86400);
        clock.querySelector('[data-u="h"]').textContent = pad(Math.floor(s % 86400 / 3600));
        clock.querySelector('[data-u="m"]').textContent = pad(Math.floor(s % 3600 / 60));
        clock.querySelector('[data-u="s"]').textContent = pad(s % 60);
        if (left > 0) { setTimeout(tick, 1000); }
    }
    tick();
})();
</script>
<?php endblock(); ?>
