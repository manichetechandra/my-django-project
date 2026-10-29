<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($service['title']) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .service-detail-header {
        background: linear-gradient(135deg, <?= e($service->get_type_color()) ?> 0%, #2c3e50 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .service-icon-large {
        font-size: 80px;
        margin-bottom: 20px;
        display: inline-block;
        background: rgba(255,255,255,0.2);
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .content-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .content-section h3 {
        color: #2c3e50;
        border-inline-start: 5px solid <?= e($service->get_type_color()) ?>;
        padding-inline-start: 15px;
        margin-bottom: 20px;
    }
    
    .content-section p,
    .content-section ul {
        line-height: 2;
        font-size: 16px;
        color: #555;
        text-align: justify;
    }
    
    .content-section ul {
        list-style: none;
        padding-inline-start: 0;
    }
    
    .content-section ul li {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .content-section ul li:before {
        content: "\f00c";
        font-family: FontAwesome;
        color: <?= e($service->get_type_color()) ?>;
        margin-inline-end: 10px;
        font-weight: bold;
    }
    
    .info-box {
        background: #f8f9fa;
        border-inline-start: 5px solid <?= e($service->get_type_color()) ?>;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .info-box h4 {
        color: #2c3e50;
        margin-top: 0;
    }
    
    .related-service {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .related-service:hover {
        border-color: #8cc040;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .badge-type {
        background-color: <?= e($service->get_type_color()) ?>;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        display: inline-block;
        margin-top: 10px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="service-detail-header">
    <div class="container">
        <div class="service-icon-large">
            <i class="fa <?= e($service['icon']) ?>" style="font-size: 80px;"></i>
        </div>
        <h1 class="text-center" style="margin: 0; font-size: 36px;">
            <?= e($service['title']) ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= e($service['short_description']) ?>
        </p>
        <div class="text-center">
            <span class="badge-type">
                <i class="fa <?= e($service->get_type_icon_default()) ?>"></i>
                <?= e($service->get_service_type_display()) ?>
            </span>
        </div>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('services:service_list')) ?>"><?= t('الخدمات الجامعية') ?></a></li>
        <li class="active"><?= e($service['title']) ?></li>
    </ol>
    
    <div class="row">
        <!-- المحتوى الرئيسي -->
        <div class="col-md-8">
            
            <!-- الصورة الرئيسية -->
            <?php if (!empty($service['main_image'])): ?>
            <div style="margin-bottom: 30px;">
                <img src="<?= e(media_url($service['main_image'])) ?>" 
                     alt="<?= e($service['title']) ?>" 
                     class="img-responsive" 
                     style="width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            </div>
            <?php endif; ?>
            
            <!-- الوصف التفصيلي -->
            <div class="content-section">
                <h3><i class="fa fa-info-circle"></i> <?= t('نبذة عن الخدمة') ?></h3>
                <p><?= linebreaks($service['detailed_description']) ?></p>
            </div>
            
            <!-- الشروط والمتطلبات -->
            <?php if (!empty($service['requirements'])): ?>
            <div class="content-section">
                <h3><i class="fa fa-list-ul"></i> <?= t('الشروط والمتطلبات') ?></h3>
                <p><?= linebreaks($service['requirements']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- كيفية التقديم -->
            <?php if (!empty($service['how_to_apply'])): ?>
            <div class="content-section">
                <h3><i class="fa fa-file-text"></i> <?= t('كيفية التقديم') ?></h3>
                <p><?= linebreaks($service['how_to_apply']) ?></p>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            
            <!-- معلومات الاتصال -->
            <?php if (!empty($service['contact_info'])): ?>
            <div class="info-box">
                <h4><i class="fa fa-phone"></i> <?= t('معلومات الاتصال') ?></h4>
                <p style="margin: 0;"><?= linebreaks($service['contact_info']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- معلومات إضافية -->
            <div class="info-box">
                <h4><i class="fa fa-info"></i> <?= t('معلومات عامة') ?></h4>
                <p style="margin-bottom: 10px;">
                    <i class="fa fa-tag"></i>
                    <strong><?= t('النوع:') ?></strong> <?= e($service->get_service_type_display()) ?>
                </p>
                <p style="margin-bottom: 10px;">
                    <i class="fa fa-calendar"></i>
                    <strong><?= t('آخر تحديث:') ?></strong> <?= e(format_date($service['updated_at'], 'd/m/Y')) ?>
                </p>
            </div>
            
            <!-- خدمات ذات صلة -->
            <?php if (!empty($related_services)): ?>
            <div class="content-section">
                <h4 style="color: #2c3e50; margin-top: 0;">
                    <i class="fa fa-link"></i> <?= t('خدمات ذات صلة') ?>
                </h4>
                <?php foreach ($related_services as $related): ?>
                <div class="related-service">
                    <h5 style="margin: 0 0 10px 0;">
                        <i class="fa <?= e($related['icon']) ?>" style="color: <?= e($related->get_type_color()) ?>;"></i>
                        <?= e($related['title']) ?>
                    </h5>
                    <p style="font-size: 14px; color: #666; margin-bottom: 10px;">
                        <?= e(truncatewords($related['short_description'], 15)) ?>
                    </p>
                    <a href="<?= e($related->get_absolute_url()) ?>" class="btn btn-sm btn-success">
                        <i class="fa fa-arrow-left"></i> <?= t('التفاصيل') ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- العودة للقائمة -->
            <a href="<?= e(url('services:service_list')) ?>" class="btn btn-default btn-block">
                <i class="fa fa-arrow-right"></i> <?= t('العودة لقائمة الخدمات') ?>
            </a>
            
        </div>
    </div>
</div>

<br><br>

<?php endblock(); ?>