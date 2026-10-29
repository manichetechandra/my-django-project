<?php /* بطاقة ملف واحد — يتطلب المتغير $file (DownloadFile) */ ?>
<div class="file-card">
    <div class="file-icon" style="background: <?= e($file->get_file_color()) ?>;">
        <i class="fa <?= e($file->get_file_icon()) ?>"></i>
    </div>
    <div class="file-info">
        <h4>
            <?= e($file['title']) ?>
            <?php if (!empty($file['featured'])): ?><span class="featured-badge"><i class="fa fa-star"></i> <?= t('مميز') ?></span><?php endif; ?>
        </h4>
        <?php if (!empty($file['description'])): ?>
            <p><?= e(truncatewords($file['description'], 20)) ?></p>
        <?php endif; ?>
        <div class="file-meta">
            <span><i class="fa fa-folder-o"></i> <?= e($file->get_category_display()) ?></span>
            <span><i class="fa fa-file-o"></i> <?= e(mb_strtoupper(slice_($file->get_file_extension(), '1:'))) ?: '—' ?></span>
            <span><i class="fa fa-hdd-o"></i> <?= e($file->get_file_size_display()) ?></span>
            <span><i class="fa fa-download"></i> <?= t('{count} تحميل', ['count' => $file['downloads_count']]) ?></span>
            <span><i class="fa fa-calendar"></i> <?= e(format_date($file['created_at'], 'd/m/Y')) ?></span>
        </div>
    </div>
    <a href="<?= e($file->get_absolute_url()) ?>" class="btn btn-download">
        <i class="fa fa-download"></i> <?= t('تحميل') ?>
    </a>
</div>
