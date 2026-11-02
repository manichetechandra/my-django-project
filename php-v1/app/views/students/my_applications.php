<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .applications-header {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    /* إحصائيات سريعة */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        text-align: center;
        border-top: 3px solid;
    }
    
    .stat-box.total { border-top-color: #3498db; }
    .stat-box.pending { border-top-color: #f39c12; }
    .stat-box.review { border-top-color: #9b59b6; }
    .stat-box.approved { border-top-color: #2ecc71; }
    .stat-box.rejected { border-top-color: #e74c3c; }
    
    .stat-box h3 {
        margin: 0;
        font-size: 36px;
        font-weight: bold;
    }
    
    .stat-box p {
        margin: 5px 0 0 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    /* الفلترة */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .filter-section h4 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #2c3e50;
    }
    
    /* قائمة الطلبات */
    .applications-list {
        background: white;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .application-card {
        padding: 20px;
        border-bottom: 1px solid #ecf0f1;
        transition: all 0.3s;
    }
    
    .application-card:hover {
        background: #f8f9fa;
    }
    
    .application-card:last-child {
        border-bottom: none;
    }
    
    .app-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .app-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .app-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .app-info h4 {
        margin: 0;
        color: #2c3e50;
    }
    
    .app-info p {
        margin: 5px 0 0 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .app-status {
        padding: 8px 20px;
        border-radius: 20px;
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    .status-pending { background: #f39c12; }
    .status-under_review { background: #3498db; }
    .status-approved { background: #2ecc71; }
    .status-rejected { background: #e74c3c; }
    .status-cancelled { background: #95a5a6; }
    
    .app-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #ecf0f1;
    }
    
    .app-meta {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .app-actions a {
        margin-right: 10px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #7f8c8d;
    }
    
    .empty-state i {
        font-size: 80px;
        margin-bottom: 20px;
        display: block;
        color: #bdc3c7;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="applications-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-list"></i> طلباتي
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            عرض وإدارة كل الطلبات المقدمة
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>">لوحة التحكم</a></li>
        <li class="active">طلباتي</li>
    </ol>
    
    <!-- إحصائيات سريعة -->
    <div class="quick-stats">
        <div class="stat-box total">
            <h3><?= e($total_count) ?></h3>
            <p>إجمالي الطلبات</p>
        </div>
        
        <div class="stat-box pending">
            <h3><?= e($pending_count) ?></h3>
            <p>قيد الانتظار</p>
        </div>
        
        <div class="stat-box review">
            <h3><?= e($under_review_count) ?></h3>
            <p>قيد المراجعة</p>
        </div>
        
        <div class="stat-box approved">
            <h3><?= e($approved_count) ?></h3>
            <p>معتمدة</p>
        </div>
        
        <div class="stat-box rejected">
            <h3><?= e($rejected_count) ?></h3>
            <p>مرفوضة</p>
        </div>
    </div>
    <a href="<?= e(url('students:print_all_applications')) ?>" 
       class="btn btn-lg btn-info" 
       target="_blank"
       style="margin: 5px;">
        <i class="fa fa-print"></i> طباعة كل الطلبات
    </a>
    <!-- الفلترة -->
    <div class="filter-section">
        <h4><i class="fa fa-filter"></i> تصفية الطلبات</h4>
        
        <form method="get" class="form-inline">
            <div class="form-group" style="margin-left: 15px;">
                <label style="margin-left: 5px;">الحالة:</label>
                <select name="status" class="form-control">
                    <option value="">الكل</option>
                    <option value="pending" <?php if ($status_filter == 'pending'): ?>selected<?php endif; ?>>قيد الانتظار</option>
                    <option value="under_review" <?php if ($status_filter == 'under_review'): ?>selected<?php endif; ?>>قيد المراجعة</option>
                    <option value="approved" <?php if ($status_filter == 'approved'): ?>selected<?php endif; ?>>معتمد</option>
                    <option value="rejected" <?php if ($status_filter == 'rejected'): ?>selected<?php endif; ?>>مرفوض</option>
                    <option value="cancelled" <?php if ($status_filter == 'cancelled'): ?>selected<?php endif; ?>>ملغي</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-left: 15px;">
                <label style="margin-left: 5px;">النوع:</label>
                <select name="type" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach ($application_types as $app_type): ?>
                    <option value="<?= e($app_type['id']) ?>" <?php if ($type_filter == (string)($app_type['id'])): ?>selected<?php endif; ?>>
                        <?= e($app_type['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> بحث
            </button>
            
            <a href="<?= e(url('students:my_applications')) ?>" class="btn btn-default">
                <i class="fa fa-refresh"></i> إعادة تعيين
            </a>
            
            <a href="<?= e(url('students:create_application')) ?>" class="btn btn-success" style="float: left;">
                <i class="fa fa-plus"></i> طلب جديد
            </a>
        </form>
    </div>
    
    <!-- قائمة الطلبات -->
    <div class="applications-list">
        <?php if (!empty($applications)): ?>
            <?php foreach ($applications as $app): ?>
            <div class="application-card">
                <div class="app-header">
                    <div class="app-title">
                        <div class="app-icon" style="background: <?= e($app['application_type']['color']) ?>;">
                            <i class="fa <?= e($app['application_type']['icon']) ?>"></i>
                        </div>
                        <div class="app-info">
                            <h4><?= e($app['application_type']['name']) ?></h4>
                            <p>
                                <i class="fa fa-hashtag"></i> <?= e($app['application_number']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <span class="app-status status-<?= e($app['status']) ?>">
                        <i class="fa <?= e($app->get_status_icon()) ?>"></i>
                        <?= e($app->get_status_display()) ?>
                    </span>
                </div>
                
                <?php if (!empty($app['details'])): ?>
                <div style="margin-bottom: 15px; color: #7f8c8d;">
                    <i class="fa fa-comment"></i> <?= e(truncatewords($app['details'], 20)) ?>
                </div>
                <?php endif; ?>
                
                <div class="app-details">
                    <div class="app-meta">
                        <i class="fa fa-calendar"></i> تاريخ التقديم: <?= e(format_date($app['submitted_at'], 'd/m/Y H:i')) ?>
                        <?php if (!empty($app['reviewed_at'])): ?>
                        | <i class="fa fa-check"></i> تاريخ المراجعة: <?= e(format_date($app['reviewed_at'], 'd/m/Y')) ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="app-actions">
    <a href="<?= e(url('students:application_detail', $app['id'])) ?>" class="btn btn-sm btn-primary">
        <i class="fa fa-eye"></i> التفاصيل
    </a>
    
    <!-- ← جديد! زر الطباعة -->
    <a href="<?= e(url('students:application_print', $app['id'])) ?>" 
       class="btn btn-sm btn-info" target="_blank"
       title="طباعة الطلب">
        <i class="fa fa-print"></i> طباعة
    </a>
    
    <!-- ← جديد! زر تحميل PDF -->
    <a href="<?= e(url('students:download_application_pdf', $app['id'])) ?>" 
       class="btn btn-sm btn-success"
       title="تحميل PDF">
        <i class="fa fa-file-pdf-o"></i> PDF
    </a>
    
    <?php if ($app['status'] == 'pending'): ?>
    <form method="post" action="<?= e(url('students:cancel_application', $app['id'])) ?>" style="display: inline;" 
          onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-danger">
            <i class="fa fa-times"></i> إلغاء
        </button>
    </form>
    <?php endif; ?>
</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <h3 style="color: #7f8c8d;">لا توجد طلبات</h3>
                <p>لم تقدم أي طلبات بعد</p>
                <a href="<?= e(url('students:create_application')) ?>" class="btn btn-primary btn-lg" style="margin-top: 20px;">
                    <i class="fa fa-plus"></i> تقديم طلب جديد
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<br><br>

<?php endblock(); ?>