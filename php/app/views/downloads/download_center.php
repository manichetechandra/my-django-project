<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<?php include view_path('downloads/_styles'); ?>
<?php endblock(); ?>

<?php block('content'); ?>
<div class="downloads-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-cloud-download"></i> مركز التحميلات
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            استمارات، دلائل، لوائح وتقارير المديرية في مكان واحد
        </p>
    </div>
</div>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li class="active">مركز التحميلات</li>
    </ol>

    <!-- الإحصائيات -->
    <div class="row">
        <div class="col-sm-4">
            <div class="stats-box">
                <i class="fa fa-file-text-o"></i>
                <h3><?= e($total_files) ?></h3>
                <p>ملف متاح</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stats-box">
                <i class="fa fa-folder-open-o"></i>
                <h3><?= e(count($categories_data)) ?></h3>
                <p>تصنيف</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stats-box">
                <i class="fa fa-download"></i>
                <h3><?= e($total_downloads) ?></h3>
                <p>عملية تحميل</p>
            </div>
        </div>
    </div>

    <!-- البحث والفلترة -->
    <div class="search-box">
        <form method="get" action="<?= e(url('downloads:download_center')) ?>">
            <div class="row">
                <div class="col-md-6" style="margin-bottom: 10px;">
                    <input type="text" name="q" class="form-control" placeholder="ابحث عن ملف... (مثال: استمارة المنحة)" value="<?= e($search_query) ?>">
                </div>
                <div class="col-md-4" style="margin-bottom: 10px;">
                    <select name="category" class="form-control">
                        <option value="">كل التصنيفات</option>
                        <?php foreach ($categories as [$code, $name]): ?>
                            <option value="<?= e($code) ?>" <?php if ($current_category === $code): ?>selected<?php endif; ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-block btn-download" style="height: 46px;">
                        <i class="fa fa-search"></i> بحث
                    </button>
                </div>
            </div>
            <?php if ($search_query !== '' || $current_category !== ''): ?>
                <p style="margin: 10px 0 0;">
                    <a href="<?= e(url('downloads:download_center')) ?>"><i class="fa fa-times"></i> إلغاء الفلترة</a>
                </p>
            <?php endif; ?>
        </form>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($featured_files) && $search_query === '' && $current_category === ''): ?>
                <div class="category-section">
                    <div class="category-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="fa fa-star" style="font-size: 24px; margin-inline-end: 12px;"></i>
                        <h3>ملفات مميزة</h3>
                    </div>
                    <?php foreach ($featured_files as $file): ?>
                        <?php include view_path('downloads/_file_card'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($categories_data)): ?>
                <?php foreach ($categories_data as $cat): ?>
                    <div class="category-section">
                        <div class="category-header" style="background-color: <?= e((new DownloadFile(['category' => $cat['code']]))->get_category_color()) ?>;">
                            <i class="fa fa-folder-open" style="font-size: 24px; margin-inline-end: 12px;"></i>
                            <h3><?= e($cat['name']) ?></h3>
                            <span class="badge"><?= e($cat['count']) ?> ملف</span>
                        </div>
                        <?php foreach ($cat['files'] as $file): ?>
                            <?php include view_path('downloads/_file_card'); ?>
                        <?php endforeach; ?>
                        <?php if ($cat['count'] > count($cat['files'])): ?>
                            <div class="text-center">
                                <a href="<?= e(url('downloads:download_by_category', $cat['code'])) ?>" class="btn btn-default">
                                    عرض كل ملفات "<?= e($cat['name']) ?>" (<?= e($cat['count']) ?>) <i class="fa fa-arrow-left"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="category-section empty-state">
                    <i class="fa fa-folder-open-o"></i>
                    <h3>لا توجد ملفات</h3>
                    <p><?= $search_query !== '' ? 'لم نجد ملفات تطابق بحثك.' : 'لم تتم إضافة ملفات بعد.' ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="sidebar-box">
                <h4><i class="fa fa-th-list"></i> التصنيفات</h4>
                <ul class="category-list">
                    <?php foreach ($categories as [$code, $name]): ?>
                        <li>
                            <a href="<?= e(url('downloads:download_by_category', $code)) ?>">
                                <span class="category-dot" style="background: <?= e((new DownloadFile(['category' => $code]))->get_category_color()) ?>;"></span>
                                <?= e($name) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sidebar-box">
                <h4><i class="fa fa-question-circle"></i> تحتاج مساعدة؟</h4>
                <p style="color: #7f8c8d;">إذا لم تجد الملف الذي تبحث عنه، يمكنك التواصل معنا أو الاطلاع على الأسئلة الشائعة.</p>
                <a href="<?= e(url('contact:contact')) ?>" class="btn btn-download btn-block"><i class="fa fa-envelope"></i> اتصل بنا</a>
                <a href="<?= e(url('faq:faq_list')) ?>" class="btn btn-default btn-block"><i class="fa fa-question"></i> الأسئلة الشائعة</a>
            </div>
        </div>
    </div>
</div>
<?php endblock(); ?>
