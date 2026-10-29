<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .history-header {
        background: linear-gradient(135deg, #8e44ad 0%, #3498db 100%);
        color: white;
        padding: 50px 0;
        margin-bottom: 40px;
        margin-top: 0;
    }
    
    .timeline-badge {
        background-color: #8e44ad;
        color: white;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        margin: 0 auto 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .history-content {
        line-height: 2.2;
        font-size: 17px;
        color: #333;
        text-align: justify;
    }
    
    .intro-section {
        background: #f8f9fa;
        padding: 30px;
        border-inline-start: 5px solid #8e44ad;
        border-radius: 5px;
        margin-bottom: 30px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<?php if (!empty($history)): ?>

<!-- Header -->
<div class="history-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 36px;">
            <i class="fa fa-history"></i> <?= e($history['title']) ?>
        </h1>
        <?php if (!empty($history['founding_year'])): ?>
        <p class="text-center" style="font-size: 20px; margin-top: 15px; opacity: 0.9;">
            <?= t('تأسست عام') ?> <?= e($history['founding_year']) ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li class="active"><?= e($history['title']) ?></li>
    </ol>
    
    <!-- الصورة الرئيسية -->
    <?php if (!empty($history['main_image'])): ?>
    <div class="row" style="margin-bottom: 40px;">
        <div class="col-md-12">
            <img src="<?= e(media_url($history['main_image'])) ?>" 
                 alt="<?= e($history['title']) ?>" 
                 class="img-responsive" 
                 style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
        </div>
    </div>
    <?php endif; ?>
    
    <!-- قسم المقدمة -->
    <div class="row">
        <div class="col-md-12">
            <div class="intro-section">
                <h3 style="color: #8e44ad; margin-top: 0;">
                    <i class="fa fa-book"></i> <?= t('نبذة تاريخية') ?>
                </h3>
                <p style="font-size: 18px; line-height: 2; color: #555;">
                    <?= linebreaks($history['introduction']) ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- المحتوى التفصيلي -->
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12">
            
            <!-- شارة السنة -->
            <?php if (!empty($history['founding_year'])): ?>
            <div class="timeline-badge">
                <?= e($history['founding_year']) ?>
            </div>
            <?php endif; ?>
            
            <div class="history-content">
                <h3 style="color: #8e44ad; border-bottom: 3px solid #8e44ad; padding-bottom: 10px; display: inline-block;">
                    <i class="fa fa-file-text"></i> <?= t('التاريخ التفصيلي') ?>
                </h3>
                
                <div style="margin-top: 30px;">
                    <?= linebreaks($history['content']) ?>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- معلومات إضافية -->
    <div class="row" style="margin-top: 50px; margin-bottom: 30px;">
        <div class="col-md-12">
            <div class="alert alert-info" style="border-inline-start: 5px solid #3498db;">
                <i class="fa fa-info-circle"></i>
                <strong><?= t('آخر تحديث:') ?></strong> 
                <?= e(format_date($history['updated_at'], 'd/m/Y - H:i')) ?>
            </div>
        </div>
    </div>
    
</div>

<?php else: ?>

<!-- رسالة عدم وجود بيانات -->
<div class="container" style="margin-top: 100px; margin-bottom: 100px;">
    <div class="alert alert-warning text-center" style="padding: 50px; font-size: 18px;">
        <i class="fa fa-exclamation-triangle" style="font-size: 48px; display: block; margin-bottom: 20px;"></i>
        <h3><?= t('عذراً، لا توجد معلومات متاحة حالياً') ?></h3>
        <p><?= t('يتم العمل على إضافة تاريخ المديرية قريباً.') ?></p>
    </div>
</div>

<?php endif; ?>

<?php endblock(); ?>