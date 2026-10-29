<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .services-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 50px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    /* التبويبات */
    .nav-tabs {
        border-bottom: 3px solid #8cc040;
        margin-bottom: 30px;
    }
    
    .nav-tabs > li > a {
        color: #2c3e50;
        font-weight: bold;
        font-size: 16px;
        border-radius: 0;
    }
    
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:hover,
    .nav-tabs > li.active > a:focus {
        color: white;
        background-color: #8cc040;
        border: 1px solid #8cc040;
    }
    
    .nav-tabs > li > a:hover {
        background-color: #f8f9fa;
        border-color: #ddd;
    }
    
    /* بطاقات الخدمات */
    .service-card {
        border: 2px solid #ddd;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: all 0.3s;
        height: 100%;
        text-align: start;
    }
    
    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border-color: #8cc040;
    }
    
    .service-card-header {
        padding: 20px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .service-card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        inset-inline-start: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(45deg);
    }
    
    .service-icon {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    .service-card-body {
        padding: 20px;
    }
    
    .service-card h3 {
        color: #2c3e50;
        margin-top: 0;
        font-size: 20px;
    }
    
    .service-card p {
        color: #666;
        line-height: 1.8;
        text-align: justify;
    }
    
    .service-meta {
        border-top: 1px solid #eee;
        padding-top: 15px;
        margin-top: 15px;
    }
    
    .service-meta i {
        color: #8cc040;
        margin-inline-end: 5px;
    }
    
    /* ألوان حسب النوع */
    .service-transport { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
    .service-housing { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
    .service-catering { background: linear-gradient(135deg, #f39c12 0%, #d68910 100%); }
    .service-scholarship { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        display: block;
        color: #ddd;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="services-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 36px;">
            <i class="fa fa-cogs"></i> <?= e($page_title) ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= t('نوفر لكم مجموعة شاملة من الخدمات لتسهيل حياتكم الجامعية') ?>
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li class="active"><?= t('الخدمات الجامعية') ?></li>
    </ol>
    
    <!-- التبويبات -->
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#all" data-toggle="tab">
                <i class="fa fa-th"></i> <?= t('كل الخدمات') ?>
                <span class="badge" style="background-color: #8cc040;"><?= e(count($services)) ?></span>
            </a>
        </li>
        <li role="presentation">
            <a href="#transport" data-toggle="tab">
                <i class="fa fa-bus"></i> <?= t('النقل') ?>
                <span class="badge" style="background-color: #3498db;"><?= e(count($transport_services)) ?></span>
            </a>
        </li>
        <li role="presentation">
            <a href="#housing" data-toggle="tab">
                <i class="fa fa-home"></i> <?= t('الإيواء') ?>
                <span class="badge" style="background-color: #e74c3c;"><?= e(count($housing_services)) ?></span>
            </a>
        </li>
        <li role="presentation">
            <a href="#catering" data-toggle="tab">
                <i class="fa fa-cutlery"></i> <?= t('الإطعام') ?>
                <span class="badge" style="background-color: #f39c12;"><?= e(count($catering_services)) ?></span>
            </a>
        </li>
        <li role="presentation">
            <a href="#scholarship" data-toggle="tab">
                <i class="fa fa-money"></i> <?= t('المنح') ?>
                <span class="badge" style="background-color: #2ecc71;"><?= e(count($scholarship_services)) ?></span>
            </a>
        </li>
    </ul>
    
    <!-- محتوى التبويبات -->
    <div class="tab-content">
        
        <!-- كل الخدمات -->
        <div role="tabpanel" class="tab-pane active" id="all">
            <?php if (!empty($services)): ?>
                <div class="row">
                    <?php foreach ($services as $service): ?>
                        <div class="col-md-6">
                            <?php include view_path('services/service_card'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <h3><?= t('لا توجد خدمات حالياً') ?></h3>
                    <p><?= t('يتم العمل على إضافة الخدمات قريباً') ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- النقل -->
        <div role="tabpanel" class="tab-pane" id="transport">
            <?php if (!empty($transport_services)): ?>
                <div class="row">
                    <?php foreach ($transport_services as $service): ?>
                        <div class="col-md-6">
                            <?php include view_path('services/service_card'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-bus"></i>
                    <h3><?= t('لا توجد خدمات نقل حالياً') ?></h3>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- الإيواء -->
        <div role="tabpanel" class="tab-pane" id="housing">
            <?php if (!empty($housing_services)): ?>
                <div class="row">
                    <?php foreach ($housing_services as $service): ?>
                        <div class="col-md-6">
                            <?php include view_path('services/service_card'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-home"></i>
                    <h3><?= t('لا توجد خدمات إيواء حالياً') ?></h3>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- الإطعام -->
        <div role="tabpanel" class="tab-pane" id="catering">
            <?php if (!empty($catering_services)): ?>
                <div class="row">
                    <?php foreach ($catering_services as $service): ?>
                        <div class="col-md-6">
                            <?php include view_path('services/service_card'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-cutlery"></i>
                    <h3><?= t('لا توجد خدمات إطعام حالياً') ?></h3>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- المنح -->
        <div role="tabpanel" class="tab-pane" id="scholarship">
            <?php if (!empty($scholarship_services)): ?>
                <div class="row">
                    <?php foreach ($scholarship_services as $service): ?>
                        <div class="col-md-6">
                            <?php include view_path('services/service_card'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-money"></i>
                    <h3><?= t('لا توجد معلومات عن المنح حالياً') ?></h3>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
    $(document).ready(function() {
        // حفظ التبويب النشط في localStorage
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('activeTab', $(e.target).attr('href'));
        });
        
        // استرجاع التبويب النشط
        var activeTab = localStorage.getItem('activeTab');
        if(activeTab){
            $('.nav-tabs a[href="' + activeTab + '"]').tab('show');
        }
    });
</script>
<?php endblock(); ?>