<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<!-- Meta tags لمنع cache -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .welcome-box {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .student-info {
        display: flex;
        align-items: center;
    }
    
    .student-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 36px;
        margin-inline-end: 20px;
    }
    
    .student-details h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .student-details p {
        margin: 5px 0 0 0;
        color: #7f8c8d;
    }
    
    /* بطاقات الإحصائيات */
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        text-align: center;
        transition: all 0.3s;
        border-top: 4px solid;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card.pending { border-top-color: #f39c12; }
    .stat-card.under-review { border-top-color: #3498db; }
    .stat-card.approved { border-top-color: #2ecc71; }
    .stat-card.rejected { border-top-color: #e74c3c; }
    
    .stat-card i {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .stat-card.pending i { color: #f39c12; }
    .stat-card.under-review i { color: #3498db; }
    .stat-card.approved i { color: #2ecc71; }
    .stat-card.rejected i { color: #e74c3c; }
    
    .stat-card h2 {
        margin: 10px 0;
        font-size: 42px;
        font-weight: bold;
    }
    
    .stat-card p {
        margin: 0;
        color: #7f8c8d;
        font-size: 16px;
    }
    
    /* قائمة الخيارات */
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    
    .action-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        text-decoration: none;
    }
    
    .action-card i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    .action-card h4 {
        margin: 0;
        color: #2c3e50;
    }
    
    /* آخر الطلبات */
    .application-list {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .application-item {
        padding: 15px;
        border-bottom: 1px solid #ecf0f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .application-item:last-child {
        border-bottom: none;
    }
    
    .application-info h5 {
        margin: 0 0 5px 0;
        color: #2c3e50;
    }
    
    .application-info p {
        margin: 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .application-status {
        padding: 8px 15px;
        border-radius: 20px;
        color: white;
        font-size: 14px;
        white-space: nowrap;
    }
    
    .status-pending { background: #f39c12; }
    .status-under_review { background: #3498db; }
    .status-approved { background: #2ecc71; }
    .status-rejected { background: #e74c3c; }
    .status-cancelled { background: #95a5a6; }
    
    /* الإشعارات */
    .notification-list {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    }
    
    .notification-item {
        padding: 15px;
        border-inline-start: 4px solid #3498db;
        background: #ecf0f1;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    
    .notification-item:last-child {
        margin-bottom: 0;
    }
    
    .notification-item h5 {
        margin: 0 0 5px 0;
        color: #2c3e50;
    }
    
    .notification-item p {
        margin: 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .notification-item.unread {
        background: #e3f2fd;
        border-inline-start-color: #2196F3;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="dashboard-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-dashboard"></i> لوحة التحكم
        </h1>
    </div>
</div>

<div class="container">
    
    <!-- رسالة الترحيب -->
    <div class="welcome-box">
        <div class="student-info">
            <div class="student-avatar">
                <i class="fa fa-user"></i>
            </div>
            <div class="student-details">
                <h3>مرحباً، <?= e($student['full_name_arabic']) ?>!</h3>
                <p>
                    <i class="fa fa-id-card"></i> رقم التسجيل: <?= e($student['registration_number']) ?> |
                    <i class="fa fa-graduation-cap"></i> <?= e($student->get_current_year_display()) ?> |
                    <i class="fa fa-university"></i> <?= e($student['faculty']) ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- إحصائيات الطلبات -->
    <h3 style="color: #2c3e50; margin-bottom: 20px;">
        <i class="fa fa-bar-chart"></i> إحصائيات الطلبات
    </h3>
    
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card pending">
                <i class="fa fa-clock-o"></i>
                <h2><?= e($pending_count) ?></h2>
                <p>قيد الانتظار</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="stat-card under-review">
                <i class="fa fa-search"></i>
                <h2><?= e($under_review_count) ?></h2>
                <p>قيد المراجعة</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="stat-card approved">
                <i class="fa fa-check-circle"></i>
                <h2><?= e($approved_count) ?></h2>
                <p>معتمدة</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="stat-card rejected">
                <i class="fa fa-times-circle"></i>
                <h2><?= e($rejected_count) ?></h2>
                <p>مرفوضة</p>
            </div>
        </div>
    </div>
    
    <!-- قائمة الخيارات السريعة -->
<h3 style="color: #2c3e50; margin: 40px 0 20px 0;">
    <i class="fa fa-th-large"></i> الخيارات السريعة
</h3>

<div class="action-grid">
    <a href="<?= e(url('students:create_application')) ?>" class="action-card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
        <i class="fa fa-plus-circle"></i>
        <h4>تقديم طلب جديد</h4>
    </a>
    
    <a href="<?= e(url('students:my_applications')) ?>" class="action-card" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white;">
        <i class="fa fa-list"></i>
        <h4>طلباتي</h4>
    </a>
    
    <a href="#" class="action-card" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
        <i class="fa fa-user"></i>
        <h4>معلوماتي الشخصية</h4>
    </a>
    
     <!-- ← جديد! البطاقة الرقمية -->
    <a href="<?= e(url('students:student_card')) ?>" target="_blank"class="action-card" style="background: linear-gradient(135deg, #f39c, #e67e22); color: white;">
        <i class="fa fa-id-card"></i><h4> بطاقتي الرقمية</h4>
    </a>
    <a href="#" class="action-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">
        <i class="fa fa-bell"></i>
        <h4>الإشعارات</h4>
    </a>
</div>
    
    <div class="row" style="margin-top: 40px;">
        
       <!-- آخر الطلبات -->
<div class="col-md-7">
    <h3 style="color: #2c3e50; margin-bottom: 20px;">
        <i class="fa fa-history"></i> آخر الطلبات
    </h3>
    
    <div class="application-list">
        <?php if (!empty($recent_applications)): ?>
            <?php foreach ($recent_applications as $app): ?>
            <div class="application-item">
                <div class="application-info">
                    <h5>
                        <i class="fa <?= e($app['application_type']['icon']) ?>"></i>
                        <?= e($app['application_type']['name']) ?>
                    </h5>
                    <p>
                        <i class="fa fa-hashtag"></i> <?= e($app['application_number']) ?> |
                        <i class="fa fa-calendar"></i> <?= e(format_date($app['submitted_at'], 'd/m/Y')) ?>
                    </p>
                </div>
                <div>
                    <span class="application-status status-<?= e($app['status']) ?>">
                        <i class="fa <?= e($app->get_status_icon()) ?>"></i>
                        <?= e($app->get_status_display()) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fa fa-inbox" style="font-size: 64px; display: block; margin-bottom: 20px;"></i>
                <p>لم تقدم أي طلبات بعد</p>
                <a href="<?= e(url('students:create_application')) ?>" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fa fa-plus"></i> قدّم طلبك الأول
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
        
        <!-- الإشعارات -->
        <div class="col-md-5">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">
                <i class="fa fa-bell"></i> الإشعارات
                <?php if (!empty($unread_notifications)): ?>
                <span class="badge" style="background: #e74c3c; font-size: 14px;">
                    <?= e(count($unread_notifications)) ?>
                </span>
                <?php endif; ?>
            </h3>
            
            <div class="notification-list">
                <?php if (!empty($unread_notifications)): ?>
                    <?php foreach ($unread_notifications as $notif): ?>
                    <div class="notification-item <?php if (empty($notif['is_read'])): ?>unread<?php endif; ?>">
                        <h5>
                            <i class="fa fa-info-circle"></i> <?= e($notif['title']) ?>
                        </h5>
                        <p><?= e(truncatewords($notif['message'], 15)) ?></p>
                        <small style="color: #95a5a6;">
                            <i class="fa fa-clock-o"></i> <?= e(format_date($notif['created_at'], 'd/m/Y H:i')) ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fa fa-bell-slash-o" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        <p>لا توجد إشعارات جديدة</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
   <!-- أنواع الطلبات المتاحة -->
<h3 style="color: #2c3e50; margin: 40px 0 20px 0;">
    <i class="fa fa-file-text"></i> الطلبات المتاحة
</h3>

<div class="row">
    <?php foreach ($available_application_types as $app_type): ?>
    <div class="col-md-4 col-sm-6">
        <div class="action-card" style="border-top: 4px solid <?= e($app_type['color']) ?>;">
            <i class="fa <?= e($app_type['icon']) ?>" style="color: <?= e($app_type['color']) ?>;"></i>
            <h4><?= e($app_type['name']) ?></h4>
            <p style="color: #7f8c8d; margin: 10px 0;"><?= e(truncatewords($app_type['description'], 10)) ?></p>
            <a href="<?= e(url('students:create_application_type', $app_type['id'])) ?>" class="btn btn-sm" style="background: <?= e($app_type['color']) ?>; color: white; border: none;">
                <i class="fa fa-plus"></i> تقديم الطلب
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// منع العودة بعد تسجيل الخروج
(function() {
    // تحقق من localStorage للتأكد من حالة تسجيل الدخول
    if (localStorage.getItem('loggedOut') === 'true') {
        localStorage.removeItem('loggedOut');
        window.location.href = "<?= e(url('students:login')) ?>";
    }
    
    // منع cache للصفحة
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
})();
</script>
<?php endblock(); ?>