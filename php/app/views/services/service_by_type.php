<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .type-header {
        <?php if ($service_type == 'transport'): ?>
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        <?php elseif ($service_type == 'housing'): ?>
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        <?php elseif ($service_type == 'catering'): ?>
        background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
        <?php elseif ($service_type == 'scholarship'): ?>
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        <?php endif; ?>
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .service-card {
        border: 2px solid #ddd;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: all 0.3s;
        height: 100%;
    }
    
    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="type-header">
    <div class="container text-center">
        <h1 style="margin: 0; font-size: 42px;">
            <?php if ($service_type == 'transport'): ?>
                <i class="fa fa-bus"></i> خدمات النقل
            <?php elseif ($service_type == 'housing'): ?>
                <i class="fa fa-home"></i> خدمات الإيواء
            <?php elseif ($service_type == 'catering'): ?>
                <i class="fa fa-cutlery"></i> خدمات الإطعام
            <?php elseif ($service_type == 'scholarship'): ?>
                <i class="fa fa-money"></i> المنح والإعانات
            <?php endif; ?>
        </h1>
    </div>
</div>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('services:service_list')) ?>">الخدمات الجامعية</a></li>
        <li class="active"><?= e($page_title) ?></li>
    </ol>
    
    <?php if (!empty($services)): ?>
        <div class="row">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6">
                    <?php include view_path('services/service_card'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center" style="padding: 60px;">
            <i class="fa fa-exclamation-triangle" style="font-size: 48px; display: block; margin-bottom: 20px;"></i>
            <h3>لا توجد خدمات في هذا القسم حالياً</h3>
            <br>
            <a href="<?= e(url('services:service_list')) ?>" class="btn btn-success">
                <i class="fa fa-arrow-right"></i> العودة لكل الخدمات
            </a>
        </div>
    <?php endif; ?>
</div>

<br><br>

<?php endblock(); ?>