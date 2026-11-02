<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مركز التحميلات - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<?php include view_path('downloads/_styles'); ?>
<?php endblock(); ?>

<?php block('content'); ?>
<?php $category_color = (new DownloadFile(['category' => $category]))->get_category_color(); ?>
<div class="downloads-header" style="background: linear-gradient(135deg, #2c3e50 0%, <?= e($category_color) ?> 100%);">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 38px;">
            <i class="fa fa-folder-open"></i> <?= e($page_title) ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= e(count($files)) ?> ملف في هذا التصنيف
        </p>
    </div>
</div>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('downloads:download_center')) ?>">مركز التحميلات</a></li>
        <li class="active"><?= e($page_title) ?></li>
    </ol>

    <div class="row">
        <div class="col-md-8">
            <div class="category-section">
                <?php if (!empty($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <?php include view_path('downloads/_file_card'); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-folder-open-o"></i>
                        <h3>لا توجد ملفات في هذا التصنيف حالياً</h3>
                        <a href="<?= e(url('downloads:download_center')) ?>" class="btn btn-download"><i class="fa fa-arrow-right"></i> العودة لمركز التحميلات</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="sidebar-box">
                <h4><i class="fa fa-th-list"></i> التصنيفات</h4>
                <ul class="category-list">
                    <?php foreach ($categories as [$code, $name]): ?>
                        <li>
                            <a href="<?= e(url('downloads:download_by_category', $code)) ?>" class="<?= $code === $category ? 'active' : '' ?>">
                                <span class="category-dot" style="background: <?= e((new DownloadFile(['category' => $code]))->get_category_color()) ?>;"></span>
                                <?= e($name) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="<?= e(url('downloads:download_center')) ?>" class="btn btn-default btn-block"><i class="fa fa-arrow-right"></i> كل الملفات</a>
        </div>
    </div>
</div>
<?php endblock(); ?>
