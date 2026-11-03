<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('مسابقات التوظيف') ?> <?= t('- مديرية الخدمات الجامعية') ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>

<?php block('content'); ?>
<div class="container cmp-page">
    <div class="cmp-title">
        <h2><i class="fa fa-trophy"></i> <?= t('مسابقات التوظيف') ?></h2>
        <a href="<?= e(url('competitions:track')) ?>" class="btn btn-success"><i class="fa fa-search"></i> <?= t('متابعة طلب ترشح') ?></a>
    </div>
    <p class="cmp-intro"><?= t('إعلانات مسابقات التوظيف في مديرية الخدمات الجامعية سطيف وإقاماتها: الرتب والمناصب، شروط المشاركة، آجال إيداع الملفات، والترشح الإلكتروني ومتابعة الطلب.') ?></p>

    <ul class="cmp-tabs">
        <?php foreach (['open' => 'الترشح مفتوح', 'upcoming' => 'قريباً', 'closed' => 'منتهية', 'all' => 'الكل'] as $key => $label): ?>
            <li><a href="<?= e(url('competitions:list') . '?tab=' . $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>"><?= t($label) ?> <span class="badge"><?= (int) $counts[$key] ?></span></a></li>
        <?php endforeach; ?>
    </ul>

    <?php foreach ($competitions as $c): $phase = $c->phase(); ?>
        <article class="cmp-card is-<?= e($phase) ?>">
            <div class="cmp-tags">
                <span class="cmp-badge is-<?= e($phase) ?>"><?= e($c->get_phase_display()) ?></span>
                <?php if ($c->has_results()): ?><span class="cmp-badge is-results"><i class="fa fa-list-ol"></i> <?= t('القوائم والنتائج منشورة') ?></span><?php endif; ?>
                <span class="cmp-mode"><i class="fa fa-briefcase"></i> <?= e($c->get_recruitment_mode_display()) ?></span>
            </div>
            <h3><a href="<?= e($c->get_absolute_url()) ?>"><?= e($c['title']) ?></a></h3>
            <?php if (trim((string) $c['summary']) !== ''): ?><p class="cmp-summary"><?= e($c['summary']) ?></p><?php endif; ?>
            <div class="cmp-meta">
                <?php if ($c->reference_number() !== ''): ?><span><i class="fa fa-hashtag"></i> <?= t('إعلان رقم {ref}', ['ref' => $c->reference_number()]) ?></span><?php endif; ?>
                <span><i class="fa fa-users"></i> <?= t('عدد المناصب: {n}', ['n' => $c->total_positions()]) ?></span>
                <span><i class="fa fa-calendar"></i> <?= t('الإيداع: من {from} إلى {to}', ['from' => format_date($c['opens_at'], 'd/m/Y'), 'to' => format_date($c['closes_at'], 'd/m/Y')]) ?></span>
                <?php if ($phase === 'open'): ?><span class="cmp-countdown"><i class="fa fa-hourglass-half"></i> <?= t('يتبقى: {time}', ['time' => $c->remaining_text()]) ?></span><?php endif; ?>
            </div>
            <div class="cmp-card-actions">
                <a class="btn btn-default btn-sm" href="<?= e($c->get_absolute_url()) ?>"><i class="fa fa-info-circle"></i> <?= t('التفاصيل والوثائق') ?></a>
                <?php if ($c->accepts_applications()): ?>
                    <a class="btn btn-success btn-sm" href="<?= e(url('competitions:apply', $c['slug'])) ?>"><i class="fa fa-pencil-square-o"></i> <?= t('ترشح الآن') ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if ($competitions->isEmpty()): ?>
        <div class="cmp-empty">
            <i class="fa fa-folder-open-o"></i>
            <h4><?= $tab === 'open' ? t('لا توجد مسابقات مفتوحة حالياً') : t('لا توجد مسابقات في هذا القسم') ?></h4>
            <p><?= t('تابع هذه الصفحة: تُنشر هنا كل مسابقات التوظيف فور الإعلان عنها.') ?></p>
        </div>
    <?php endif; ?>

    <?php if ($competitions->has_other_pages()): ?>
        <nav class="text-center">
            <ul class="pagination">
                <?php if ($competitions->has_previous()): ?><li><a href="<?= e($competitions->url($competitions->previous_page_number())) ?>" aria-label="<?= t('السابق') ?>">&laquo;</a></li><?php endif; ?>
                <?php foreach ($competitions->page_range() as $n): ?>
                    <li class="<?= $n === $competitions->number ? 'active' : '' ?>"><a href="<?= e($competitions->url($n)) ?>"><?= $n ?></a></li>
                <?php endforeach; ?>
                <?php if ($competitions->has_next()): ?><li><a href="<?= e($competitions->url($competitions->next_page_number())) ?>" aria-label="<?= t('التالي') ?>">&raquo;</a></li><?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<?php endblock(); ?>
