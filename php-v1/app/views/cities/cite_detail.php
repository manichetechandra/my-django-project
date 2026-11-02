<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($cite['name']) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .cite-header {
        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 30px;
        margin-top: 70px;
    }
    
    .cite-header h1 {
        margin: 0;
        font-size: 32px;
    }
    
    .cite-main-image {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-item i {
        color: #8cc040;
        width: 30px;
        margin-left: 5px;
        font-size: 18px;
    }
    
    .description-box {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        line-height: 1.8;
        font-size: 16px;
        color: #333;
        margin-bottom: 30px;
        direction: rtl;
        text-align: right;
    }
    
    .gallery-item {
        margin-bottom: 20px;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    
    .gallery-item:hover {
        transform: scale(1.05);
    }
    
    .gallery-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }
    
    .other-cite-card {
        margin-bottom: 20px;
        transition: transform 0.2s;
        border-right: 3px solid #8cc040;
        padding: 15px;
    }
    
    .other-cite-card:hover {
        transform: translateX(-5px);
        background: #f8f9fa;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>
<!-- Header -->
<div class="cite-header">
    <div class="container">
        <h1>
            <i class="fa fa-building"></i> <?= e($cite['name']) ?>
        </h1>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('cities:cite_list')) ?>">الإقامات الجامعية</a></li>
        <li class="active"><?= e($cite['name']) ?></li>
    </ol>

    <div class="row">
        <!-- المحتوى الرئيسي -->
        <div class="col-md-8">
            <!-- الصورة الرئيسية -->
            <?php if (!empty($cite['main_image'])): ?>
            <img src="<?= e(media_url($cite['main_image'])) ?>" alt="<?= e($cite['name']) ?>" class="cite-main-image">
            <?php endif; ?>
            
            <!-- الوصف -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-info-circle"></i> عن الإقامة
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="description-box">
                        <?= linebreaks($cite['description']) ?>
                    </div>
                </div>
            </div>
            
            <!-- معرض الصور -->
            <?php if (!empty($gallery_images)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-images"></i> معرض الصور
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <?php foreach ($gallery_images as $img): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="gallery-item">
                                <a href="<?= e(media_url($img['image'])) ?>" target="_blank">
                                    <img src="<?= e(media_url($img['image'])) ?>" alt="<?= e($img['caption']) ?>">
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            <!-- معلومات الإقامة -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-info"></i> معلومات الإقامة
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (!empty($cite['capacity'])): ?>
                    <div class="info-item">
                        <i class="fa fa-users"></i>
                        <strong>السعة الاستيعابية:</strong><br>
                        <span style="font-size: 24px; color: #8cc040;"><?= e($cite['capacity']) ?></span> طالب
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cite['address'])): ?>
                    <div class="info-item">
                        <i class="fa fa-map-marker"></i>
                        <strong>العنوان:</strong><br>
                        <?= e($cite['address']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cite['phone'])): ?>
                    <div class="info-item">
                        <i class="fa fa-phone"></i>
                        <strong>الهاتف:</strong><br>
                        <span dir="ltr" style="font-size: 18px; color: #2c3e50;"><?= e($cite['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($cite['capacity']) && empty($cite['address']) && empty($cite['phone'])): ?>
                    <p class="text-muted text-center">
                        <i class="fa fa-info-circle"></i><br>
                        لا توجد معلومات إضافية متاحة حالياً
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- إقامات أخرى -->
            <?php if (!empty($other_cites)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-building-o"></i> إقامات أخرى
                    </h3>
                </div>
                <div class="panel-body">
                    <?php foreach ($other_cites as $other): ?>
                    <a href="<?= e(url('cities:cite_detail', $other['slug'])) ?>" style="text-decoration: none;">
                        <div class="other-cite-card">
                            <h4 style="margin: 0 0 5px 0; color: #2c3e50;"><?= e($other['name']) ?></h4>
                            <small style="color: #999;">
                                <?php if (!empty($other['capacity'])): ?>
                                <i class="fa fa-users"></i> <?= e($other['capacity']) ?> طالب
                                <?php endif; ?>
                                <?php if (!empty($other['address'])): ?>
                                <br><i class="fa fa-map-marker"></i> <?= e(truncatewords($other['address'], 5)) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="panel-footer text-center">
                    <a href="<?= e(url('cities:cite_list')) ?>" class="btn btn-sm btn-success">
                        عرض كل الإقامات
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endblock(); ?>