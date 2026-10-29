<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('الإقامات الجامعية - مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .page-header {
        border-bottom: 3px solid #8cc040;
        padding-bottom: 15px;
        margin-bottom: 30px;
    }
    
    .cite-card {
        margin-bottom: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }
    
    .cite-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .cite-image {
        height: 200px;
        overflow: hidden;
    }
    
    .cite-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cite-image-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>
<div class="container" style="margin-top: 80px;">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li class="active"><?= t('الإقامات الجامعية') ?></li>
    </ol>

    <!-- العنوان -->
    <div class="page-header">
        <h1>
            <i class="fa fa-building"></i> <?= t('الإقامات الجامعية') ?>
            <small><?= t('({count} إقامة)', ['count' => $cites['paginator']['count']]) ?></small>
        </h1>
    </div>

    <!-- قائمة الإقامات -->
    <div class="row">
        <?php if (count($cites) > 0): ?><?php foreach ($cites as $cite): ?>
        <div class="col-md-4 col-sm-6">
            <div class="panel panel-default cite-card">
                <!-- الصورة -->
                <div class="cite-image">
                    <?php if (!empty($cite['main_image'])): ?>
                    <img src="<?= e(media_url($cite['main_image'])) ?>" alt="<?= e($cite['name']) ?>">
                    <?php else: ?>
                    <div class="cite-image-placeholder">
                        <i class="fa fa-building" style="font-size: 64px; color: white;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- المحتوى -->
                <div class="panel-body" style="min-height: 200px;">
                    <h3 style="color: #2c3e50;"><?= e($cite['name']) ?></h3>
                    <p><?= e(truncatewords($cite['description'], 20)) ?></p>
                    
                    <?php if (!empty($cite['capacity'])): ?>
                    <p><i class="fa fa-users"></i> <strong><?= t('السعة:') ?></strong> <?= t('{count} طالب', ['count' => $cite['capacity']]) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- الزر -->
                <div class="panel-footer text-center">
                    <a href="<?= e(url('cities:cite_detail', $cite['slug'])) ?>" class="btn btn-success">
                        <?= t('عرض التفاصيل') ?> <i class="fa fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?><?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fa fa-info-circle"></i> <?= t('لا توجد إقامات جامعية حالياً') ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (!empty($cites->has_other_pages())): ?>
    <div class="text-center">
        <ul class="pagination">
            <?php if (!empty($cites->has_previous())): ?>
            <li><a href="?page=<?= e($cites->previous_page_number()) ?>"><?= t('السابق') ?></a></li>
            <?php endif; ?>
            
            <?php foreach ($cites['paginator']['page_range'] as $num): ?>
                <?php if ($cites['number'] == $num): ?>
                <li class="active"><span><?= e($num) ?></span></li>
                <?php elseif ($num > ($cites['number'] + -3) && $num < ($cites['number'] + 3)): ?>
                <li><a href="?page=<?= e($num) ?>"><?= e($num) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (!empty($cites->has_next())): ?>
            <li><a href="?page=<?= e($cites->next_page_number()) ?>"><?= t('التالي') ?></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endblock(); ?>