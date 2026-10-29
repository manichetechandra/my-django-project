<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($activity['title']) ?> <?= t('- مديرية الخدمات الجامعية') ?><?php endblock(); ?>

<?php block('content'); ?>
<div class="container" style="margin-top: 30px;">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('activities:activity_list')) ?>"><?= t('النشاطات') ?></a></li>
        <li class="active"><?= e($activity['title']) ?></li>
    </ol>

    <div class="row">
        <!-- المحتوى الرئيسي -->
        <div class="col-md-8">
            <!-- الصورة الرئيسية -->
            <?php if (!empty($activity['main_image'])): ?>
            <img src="<?= e(media_url($activity['main_image'])) ?>" alt="<?= e($activity['title']) ?>" class="img-responsive" style="width: 100%; margin-bottom: 20px; border-radius: 5px;">
            <?php endif; ?>
            
            <!-- العنوان -->
            <h1 style="color: #2c3e50; margin-bottom: 20px;">
                <i class="fa <?= e($activity->get_type_icon()) ?>"></i> <?= e($activity['title']) ?>
            </h1>
            
            <!-- معلومات سريعة -->
            <div class="well" style="background-color: #f8f9fa;">
                <div class="row">
                    <div class="col-sm-6">
                        <p style="margin: 5px 0;">
                            <i class="fa fa-calendar" style="color: #8cc040;"></i>
                            <strong><?= t('التاريخ:') ?></strong> <?= e(format_date($activity['date'], 'd/m/Y')) ?>
                        </p>
                        <p style="margin: 5px 0;">
                            <i class="fa fa-map-marker" style="color: #e74c3c;"></i>
                            <strong><?= t('المكان:') ?></strong> <?= e($activity['location']) ?>
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p style="margin: 5px 0;">
                            <i class="fa fa-tag" style="color: #3498db;"></i>
                            <strong><?= t('النوع:') ?></strong> <?= e($activity->get_activity_type_display()) ?>
                        </p>
                        <p style="margin: 5px 0;">
                            <i class="fa fa-users" style="color: #9b59b6;"></i>
                            <strong><?= t('المشاركون:') ?></strong> <?= t('{count} شخص', ['count' => $activity['participants_count']]) ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($activity['organizer'])): ?>
                <p style="margin: 5px 0;">
                    <i class="fa fa-building" style="color: #f39c12;"></i>
                    <strong><?= t('المنظم:') ?></strong> <?= e($activity['organizer']) ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- الوصف -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= t('عن النشاط') ?></h3>
                </div>
                <div class="panel-body">
                    <p style="line-height: 1.8; font-size: 16px;"><?= linebreaks($activity['description']) ?></p>
                </div>
            </div>
            
            <!-- معرض الصور -->
            <?php if (!empty($gallery_images)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= t('معرض الصور') ?></h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <?php foreach ($gallery_images as $img): ?>
                        <div class="col-md-4 col-sm-6" style="margin-bottom: 15px;">
                            <a href="<?= e(media_url($img['image'])) ?>" target="_blank">
                                <img src="<?= e(media_url($img['image'])) ?>" alt="<?= e($img['caption']) ?>" class="img-responsive" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">
                            </a>
                            <?php if (!empty($img['caption'])): ?>
                            <small class="text-muted"><?= e($img['caption']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            <!-- نشاطات أخرى -->
            <?php if (!empty($related_activities)): ?>
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= t('نشاطات مشابهة') ?></h3>
                </div>
                <div class="panel-body">
                    <?php foreach ($related_activities as $related): ?>
                    <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <a href="<?= e($related->get_absolute_url()) ?>" style="text-decoration: none;">
                            <h4 style="color: #2c3e50; margin: 0;"><?= e($related['title']) ?></h4>
                            <small class="text-muted">
                                <i class="fa fa-calendar"></i> <?= e(format_date($related['date'], 'd/m/Y')) ?>
                                &nbsp;|&nbsp;
                                <i class="fa fa-tag"></i> <?= e($related->get_activity_type_display()) ?>
                            </small>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="panel-footer">
                    <a href="<?= e(url('activities:activity_list')) ?>?type=<?= e($activity['activity_type']) ?>" class="btn btn-sm btn-success btn-block">
                        <?= t('عرض كل النشاطات {type}ة', ['type' => $activity->get_activity_type_display()]) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- مشاركة -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= t('مشاركة النشاط') ?></h3>
                </div>
                <div class="panel-body text-center">
                    <p><?= t('شارك هذا النشاط مع الآخرين:') ?></p>
                    <div class="btn-group btn-group-justified">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(current_url()) ?>" target="_blank" class="btn btn-primary">
                            <i class="fa fa-facebook"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= e($activity['title']) ?>&url=<?= e(current_url()) ?>" target="_blank" class="btn btn-info">
                            <i class="fa fa-twitter"></i> Twitter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endblock(); ?>