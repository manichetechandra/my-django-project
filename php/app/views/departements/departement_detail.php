<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($departement['name']) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .dept-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 30px;
        margin-top: 70px;
    }
    
    .dept-header h1 {
        margin: 0;
        font-size: 32px;
    }
    
    .dept-main-image {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .info-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .info-card h4 {
        color: #2c3e50;
        border-bottom: 2px solid #8cc040;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .info-item {
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-item i {
        color: #8cc040;
        width: 25px;
        margin-inline-end: 5px;
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
    }
    
    .other-dept-card {
        margin-bottom: 20px;
        transition: transform 0.2s;
        border-inline-start: 3px solid #8cc040;
    }
    
    .other-dept-card:hover {
        transform: translateX(-5px);
        background: #f8f9fa;
    }

    html[dir="ltr"] .other-dept-card:hover {
        transform: translateX(5px);
    }
    
    .other-dept-card h4 {
        margin: 0 0 5px 0;
        color: #2c3e50;
    }
    
    .other-dept-card small {
        color: #999;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>
<!-- Header -->
<div class="dept-header">
    <div class="container">
        <h1>
            <i class="fa fa-sitemap"></i> <?= e($departement['name']) ?>
        </h1>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('departements:departement_list')) ?>">الأقسام</a></li>
        <li class="active"><?= e($departement['name']) ?></li>
    </ol>

    <div class="row">
        <!-- المحتوى الرئيسي -->
        <div class="col-md-8">
            <!-- الصورة الرئيسية -->
            <?php if (!empty($departement['image'])): ?>
            <img src="<?= e(media_url($departement['image'])) ?>" alt="<?= e($departement['name']) ?>" class="dept-main-image">
            <?php endif; ?>
            
            <!-- الوصف -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-info-circle"></i> عن القسم
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="description-box">
                        <?= linebreaks($departement['description']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            <!-- معلومات القسم -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-info"></i> معلومات الاتصال
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (!empty($departement['head_of_department'])): ?>
                    <div class="info-item">
                        <i class="fa fa-user"></i>
                        <strong>رئيس القسم:</strong><br>
                        <?= e($departement['head_of_department']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($departement['email'])): ?>
                    <div class="info-item">
                        <i class="fa fa-envelope"></i>
                        <strong>البريد الإلكتروني:</strong><br>
                        <a href="mailto:<?= e($departement['email']) ?>"><?= e($departement['email']) ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($departement['phone'])): ?>
                    <div class="info-item">
                        <i class="fa fa-phone"></i>
                        <strong>الهاتف:</strong><br>
                        <span dir="ltr"><?= e($departement['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($departement['website'])): ?>
                    <div class="info-item">
                        <i class="fa fa-globe"></i>
                        <strong>الموقع الإلكتروني:</strong><br>
                        <a href="<?= e($departement['website']) ?>" target="_blank" rel="noopener noreferrer">
                            زيارة الموقع <i class="fa fa-external-link"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($departement['head_of_department']) && empty($departement['email']) && empty($departement['phone']) && empty($departement['website'])): ?>
                    <p class="text-muted text-center">
                        <i class="fa fa-info-circle"></i><br>
                        لا توجد معلومات اتصال متاحة حالياً
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- أقسام أخرى -->
            <?php if (!empty($other_departements)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-sitemap"></i> أقسام أخرى
                    </h3>
                </div>
                <div class="panel-body">
                    <?php foreach ($other_departements as $other): ?>
                    <a href="<?= e(url('departements:departement_detail', $other['slug'])) ?>" style="text-decoration: none;">
                        <div class="other-dept-card" style="padding: 15px;">
                            <h4><?= e($other['name']) ?></h4>
                            <small>
                                <i class="fa fa-arrow-circle-left"></i> 
                                <?= e(truncatewords($other['description'], 10)) ?>
                            </small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="panel-footer text-center">
                    <a href="<?= e(url('departements:departement_list')) ?>" class="btn btn-sm btn-success">
                        عرض كل الأقسام
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endblock(); ?>