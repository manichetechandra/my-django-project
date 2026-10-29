<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('النشاطات - مديرية الخدمات الجامعية') ?><?php endblock(); ?>

<?php block('content'); ?>
<div class="container" style="margin-top: 30px;">
    <!-- العنوان -->
    <div class="page-header">
        <h1 style="color: #2c3e50;">
            <i class="fa fa-star"></i> <?= t('النشاطات الثقافية والرياضية') ?>
            <small><?= t('({count} نشاط)', ['count' => $activities['paginator']['count']]) ?></small>
        </h1>
    </div>
    
    <!-- الفلترة والبحث -->
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <!-- البحث -->
                <div class="col-md-6">
                    <form method="get" class="form-inline">
                        <div class="form-group">
                            <input type="text" name="q" class="form-control" placeholder="<?= t('ابحث في النشاطات...') ?>" value="<?= e($search_query) ?>" style="width: 300px; max-width: 100%;">
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-search"></i> <?= t('بحث') ?>
                        </button>
                        <?php if (!empty($search_query) || !empty($selected_type)): ?>
                        <a href="<?= e(url('activities:activity_list')) ?>" class="btn btn-default">
                            <i class="fa fa-times"></i> <?= t('إلغاء') ?>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- التصفية حسب النوع -->
                <div class="col-md-6">
                    <div class="btn-group pull-left">
                        <a href="<?= e(url('activities:activity_list')) ?>" class="btn btn-default <?php if (empty($selected_type)): ?>active<?php endif; ?>">
                            <?= t('الكل') ?>
                        </a>
                        <?php foreach ($activity_types as [$type_value, $type_label]): ?>
                        <a href="?type=<?= e($type_value) ?>" class="btn btn-default <?php if ($selected_type == $type_value): ?>active<?php endif; ?>">
                            <?= e($type_label) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- قائمة النشاطات -->
    <div class="row">
        <?php if (count($activities) > 0): ?><?php foreach ($activities as $activity): ?>
        <div class="col-md-4" style="margin-bottom: 30px;">
            <div class="panel panel-default" style="height: 100%; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <!-- الصورة -->
                <div style="height: 200px; overflow: hidden; position: relative;">
                    <?php if (!empty($activity['main_image'])): ?>
                    <img src="<?= e(media_url($activity['main_image'])) ?>" alt="<?= e($activity['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; background: <?= e($activity->get_type_gradient()) ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fa <?= e($activity->get_type_icon()) ?>" style="font-size: 64px; color: white;"></i>
                    </div>
                    <?php endif; ?>
                    <!-- شارة النوع -->
                    <span class="label label-primary" style="position: absolute; top: 10px; inset-inline-start: 10px;">
                        <?= e($activity->get_activity_type_display()) ?>
                    </span>
                </div>
                
                <div class="panel-body">
                    <h3 style="margin-top: 0; height: 60px; overflow: hidden;">
                        <a href="<?= e($activity->get_absolute_url()) ?>" style="text-decoration: none; color: #2c3e50;">
                            <?= e($activity['title']) ?>
                        </a>
                    </h3>
                    <p class="text-muted" style="height: 60px; overflow: hidden;">
                        <?= e(truncatewords($activity['short_description'], 15)) ?>
                    </p>
                    <hr>
                    <small style="color: #999;">
                        <i class="fa fa-calendar"></i> <?= e(format_date($activity['date'], 'd/m/Y')) ?>
                        &nbsp;&nbsp;
                        <i class="fa fa-map-marker"></i> <?= e(truncatewords($activity['location'], 3)) ?>
                        <br>
                        <i class="fa fa-users"></i> <?= t('{count} مشارك', ['count' => $activity['participants_count']]) ?> &nbsp;&nbsp;
                        <i class="fa fa-eye"></i> <?= e($activity['views_count']) ?>
                    </small>
                    <br><br>
                    <a href="<?= e($activity->get_absolute_url()) ?>" class="btn btn-success btn-block">
                        <i class="fa fa-arrow-left"></i> <?= t('التفاصيل') ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?><?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fa fa-info-circle"></i> <?= t('لا توجد نشاطات') ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- الترقيم -->
    <?php if (!empty($activities->has_other_pages())): ?>
    <div class="text-center">
        <ul class="pagination">
            <?php if (!empty($activities->has_previous())): ?>
            <li><a href="?page=<?= e($activities->previous_page_number()) ?><?php if (!empty($selected_type)): ?>&type=<?= e($selected_type) ?><?php endif; ?><?php if (!empty($search_query)): ?>&q=<?= e($search_query) ?><?php endif; ?>"><?= t('السابق') ?></a></li>
            <?php endif; ?>
            
            <?php foreach ($activities['paginator']['page_range'] as $num): ?>
                <?php if ($activities['number'] == $num): ?>
                <li class="active"><span><?= e($num) ?></span></li>
                <?php elseif ($num > ($activities['number'] + -3) && $num < ($activities['number'] + 3)): ?>
                <li><a href="?page=<?= e($num) ?><?php if (!empty($selected_type)): ?>&type=<?= e($selected_type) ?><?php endif; ?><?php if (!empty($search_query)): ?>&q=<?= e($search_query) ?><?php endif; ?>"><?= e($num) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (!empty($activities->has_next())): ?>
            <li><a href="?page=<?= e($activities->next_page_number()) ?><?php if (!empty($selected_type)): ?>&type=<?= e($selected_type) ?><?php endif; ?><?php if (!empty($search_query)): ?>&q=<?= e($search_query) ?><?php endif; ?>"><?= t('التالي') ?></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endblock(); ?>