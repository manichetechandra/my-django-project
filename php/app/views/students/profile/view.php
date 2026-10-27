<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .profile-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .profile-card-header {
        background: #34495e;
        color: white;
        padding: 20px;
        font-weight: bold;
        font-size: 18px;
    }
    
    .profile-card-body {
        padding: 30px;
    }
    
    .profile-image-section {
        text-align: center;
        padding: 30px;
        background: #f8f9fa;
    }
    
    .profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        margin-bottom: 15px;
    }
    
    .info-row {
        display: flex;
        padding: 15px 0;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        flex: 0 0 180px;
        font-weight: bold;
        color: #7f8c8d;
    }
    
    .info-value {
        flex: 1;
        color: #2c3e50;
    }
    
    .stats-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        border-right: 4px solid;
    }
    
    .stats-box.total { border-right-color: #3498db; }
    .stats-box.approved { border-right-color: #2ecc71; }
    .stats-box.pending { border-right-color: #f39c12; }
    
    .stats-number {
        font-size: 36px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .stats-label {
        color: #7f8c8d;
        font-size: 14px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="profile-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-user-circle"></i> معلوماتي الشخصية
        </h1>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>">لوحة التحكم</a></li>
        <li class="active">معلوماتي الشخصية</li>
    </ol>
    
    <div class="row">
        <!-- الصورة والإحصائيات -->
        <div class="col-md-4">
            <!-- الصورة الشخصية -->
            <!-- الصورة الشخصية -->
<div class="profile-card">
    <div class="profile-image-section">
        <?php if (!empty($student['photo'])): ?>
        <img src="<?= e(media_url($student['photo'])) ?>" alt="<?= e($student['full_name_arabic']) ?>" class="profile-image">
        <?php else: ?>
        <div class="profile-image" style="background: #bdc3c7; display: flex; align-items: center; justify-content: center; font-size: 60px; color: white;">
            <i class="fa fa-user"></i>
        </div>
        <?php endif; ?>
        
        <h4 style="margin: 10px 0; color: #2c3e50;"><?= e($student['full_name_arabic']) ?></h4>
        <p style="color: #7f8c8d; margin: 5px 0;"><?= e($student['registration_number']) ?></p>
        
        <div style="margin-top: 20px;">
            <a href="<?= e(url('students:profile_edit')) ?>" class="btn btn-primary btn-block">
                <i class="fa fa-edit"></i> تعديل المعلومات
            </a>
            <a href="<?= e(url('students:change_password')) ?>" class="btn btn-default btn-block">
                <i class="fa fa-lock"></i> تغيير كلمة المرور
            </a>
        </div>
    </div>
</div>
            
            <!-- إحصائيات سريعة -->
            <div class="stats-box total" style="margin-bottom: 15px;">
                <i class="fa fa-files-o" style="font-size: 30px; color: #3498db; opacity: 0.5;"></i>
                <div class="stats-number" style="color: #3498db;"><?= e($total_applications) ?></div>
                <div class="stats-label">إجمالي الطلبات</div>
            </div>
            
            <div class="stats-box approved" style="margin-bottom: 15px;">
                <i class="fa fa-check-circle" style="font-size: 30px; color: #2ecc71; opacity: 0.5;"></i>
                <div class="stats-number" style="color: #2ecc71;"><?= e($approved_count) ?></div>
                <div class="stats-label">طلبات معتمدة</div>
            </div>
            
            <div class="stats-box pending">
                <i class="fa fa-clock-o" style="font-size: 30px; color: #f39c12; opacity: 0.5;"></i>
                <div class="stats-number" style="color: #f39c12;"><?= e($pending_count) ?></div>
                <div class="stats-label">طلبات معلقة</div>
            </div>
        </div>
        
        <!-- المعلومات التفصيلية -->
        <div class="col-md-8">
            <!-- المعلومات الشخصية -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="fa fa-user"></i> المعلومات الشخصية
                </div>
                <div class="profile-card-body">
                    <div class="info-row">
                        <div class="info-label">الاسم الكامل:</div>
                        <div class="info-value"><?= e($student['full_name_arabic']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">تاريخ الميلاد:</div>
                        <div class="info-value"><?= e(format_date($student['birth_date'], 'd/m/Y')) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">مكان الميلاد:</div>
                        <div class="info-value"><?= e($student['birth_place']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">الجنس:</div>
                        <div class="info-value"><?= e($student->get_gender_display()) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- معلومات الاتصال -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="fa fa-phone"></i> معلومات الاتصال
                </div>
                <div class="profile-card-body">
                    <div class="info-row">
                        <div class="info-label">رقم الهاتف:</div>
                        <div class="info-value">
                            <i class="fa fa-phone" style="color: #2ecc71;"></i> <?= e($student['phone']) ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">البريد الإلكتروني:</div>
                        <div class="info-value">
                            <i class="fa fa-envelope" style="color: #3498db;"></i> 
                            <?= e(($student['user']['email'] ?: 'غير محدد')) ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">العنوان:</div>
                        <div class="info-value"><?= e($student['address']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">الولاية:</div>
                        <div class="info-value"><?= e($student['wilaya']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- المعلومات الأكاديمية -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="fa fa-graduation-cap"></i> المعلومات الأكاديمية
                </div>
                <div class="profile-card-body">
                    <div class="info-row">
                        <div class="info-label">رقم التسجيل:</div>
                        <div class="info-value">
                            <strong style="color: #3498db;"><?= e($student['registration_number']) ?></strong>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">الكلية:</div>
                        <div class="info-value"><?= e($student['faculty']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">القسم:</div>
                        <div class="info-value"><?= e($student['department']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">السنة الدراسية:</div>
                        <div class="info-value"><?= e($student->get_current_year_display()) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">تاريخ التسجيل:</div>
                        <div class="info-value"><?= e(format_date($student['created_at'], 'd/m/Y')) ?></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
</div>

<br><br>

<?php endblock(); ?>