<?php extend('base/base'); ?>
<?php block('title'); ?>أقسام المديرية - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .page-header {
        border-bottom: 3px solid #8cc040;
        padding-bottom: 15px;
        margin-bottom: 30px;
    }
    
    .dept-card {
        margin-bottom: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }
    
    .dept-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .dept-image {
        height: 200px;
        overflow: hidden;
        position: relative;
    }
    
    .dept-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .dept-image-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dept-body {
        padding: 20px;
        min-height: 200px;
    }
    
    .dept-title {
        color: #2c3e50;
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 15px;
        min-height: 50px;
    }
    
    .dept-description {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
        min-height: 80px;
    }
    
    .dept-info {
        border-top: 1px solid #eee;
        padding-top: 15px;
        margin-top: 15px;
    }
    
    .dept-info p {
        margin: 5px 0;
        font-size: 14px;
        color: #999;
    }
    
    .dept-info i {
        color: #8cc040;
        width: 20px;
        margin-inline-end: 5px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>
<div class="container" style="margin-top: 80px;">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li class="active">أقسام المديرية</li>
    </ol>

    <!-- العنوان -->
    <div class="page-header">
        <h1>
            <i class="fa fa-sitemap"></i> أقسام المديرية
            <small>(<?= e(count($departements)) ?> قسم)</small>
        </h1>
    </div>

    <!-- قائمة الأقسام -->
    <div class="row">
        <?php if (!empty($departements)): ?><?php foreach ($departements as $dept): ?>
        <div class="col-md-4 col-sm-6">
            <div class="panel panel-default dept-card">
                <!-- الصورة -->
                <div class="dept-image">
                    <?php if (!empty($dept['image'])): ?>
                    <img src="<?= e(media_url($dept['image'])) ?>" alt="<?= e($dept['name']) ?>">
                    <?php else: ?>
                    <div class="dept-image-placeholder">
                        <i class="fa fa-sitemap" style="font-size: 64px; color: white;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- المحتوى -->
                <div class="dept-body">
                    <h3 class="dept-title"><?= e($dept['name']) ?></h3>
                    <p class="dept-description"><?= e(truncatewords($dept['description'], 25)) ?></p>
                    
                    <?php if (!empty($dept['head_of_department']) || !empty($dept['email']) || !empty($dept['phone'])): ?>
                    <div class="dept-info">
                        <?php if (!empty($dept['head_of_department'])): ?>
                        <p>
                            <i class="fa fa-user"></i>
                            <strong>رئيس القسم:</strong> <?= e($dept['head_of_department']) ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($dept['email'])): ?>
                        <p>
                            <i class="fa fa-envelope"></i>
                            <strong>البريد:</strong> <?= e(truncatechars($dept['email'], 30)) ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($dept['phone'])): ?>
                        <p>
                            <i class="fa fa-phone"></i>
                            <strong>الهاتف:</strong> <?= e($dept['phone']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- الزر -->
                <div class="panel-footer text-center">
                    <a href="<?= e(url('departements:departement_detail', $dept['slug'])) ?>" class="btn btn-success">
                        عرض التفاصيل <i class="fa fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?><?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fa fa-info-circle"></i> لا توجد أقسام حالياً
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endblock(); ?>