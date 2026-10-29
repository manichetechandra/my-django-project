<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .org-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 50px 0;
        margin-bottom: 40px;
        margin-top: 0;
    }
    
    .org-chart-img {
        width: 100%;
        max-width: 1000px;
        margin: 30px auto;
        display: block;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .content-section {
        line-height: 2;
        font-size: 17px;
        color: #333;
        text-align: justify;
        margin-bottom: 30px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<?php if (!empty($organization)): ?>

<!-- Header -->
<div class="org-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 36px;">
            <i class="fa fa-sitemap"></i> <?= e($organization['title']) ?>
        </h1>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="#"><?= t('المديرية') ?></a></li>
        <li class="active"><?= e($organization['title']) ?></li>
    </ol>

    <!-- المقدمة -->
    <?php if (!empty($organization['description'])): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 style="margin: 10px 0;">
                <i class="fa fa-info-circle"></i> <?= t('نبذة') ?>
            </h3>
        </div>
        <div class="panel-body content-section">
            <?= linebreaks($organization['description']) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- صورة الهيكل التنظيمي -->
    <?php if (!empty($organization['organization_chart'])): ?>
    <div class="text-center" style="margin: 40px 0;">
        <h3 style="color: #2c3e50; margin-bottom: 30px;">
            <i class="fa fa-sitemap"></i> <?= t('مخطط الهيكل التنظيمي') ?>
        </h3>
        <img src="<?= e(media_url($organization['organization_chart'])) ?>" 
             alt="<?= t('الهيكل التنظيمي') ?>" 
             class="org-chart-img">
    </div>
    <?php endif; ?>

    <!-- المحتوى التفصيلي -->
    <?php if (!empty($organization['content'])): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 style="margin: 10px 0;">
                <i class="fa fa-list-alt"></i> <?= t('التفاصيل') ?>
            </h3>
        </div>
        <div class="panel-body content-section">
            <?= linebreaks($organization['content']) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- رسالة: لا توجد بيانات -->
<div class="container" style="margin-top: 100px;">
    <div class="alert alert-warning text-center" style="padding: 40px;">
        <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #f39c12;"></i>
        <h3 style="margin: 20px 0;"><?= t('لا توجد معلومات عن الهيكل التنظيمي حالياً') ?></h3>
        <p><?= t('سيتم إضافة المعلومات قريباً') ?></p>
        <a href="<?= e(url('homepage:index')) ?>" class="btn btn-success">
            <i class="fa fa-home"></i> <?= t('العودة للرئيسية') ?>
        </a>
    </div>
</div>

<?php endif; ?>

<?php endblock(); ?>