<?php extend('base/base'); ?>
<?php block('inline_messages'); ?>1<?php endblock(); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .register-header {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .register-container {
        background: white;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
        border-inline-start: 4px solid #3498db;
    }
    
    .form-section h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        font-weight: bold;
        color: #34495e;
        margin-bottom: 5px;
        display: block;
    }
    
    .required:after {
        content: " *";
        color: red;
    }
    
    .btn-register {
        background: #3498db;
        color: white;
        padding: 12px 40px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-register:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }
    
    .login-link {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    .errorlist {
        list-style: none;
        padding: 0;
        margin: 10px 0 0 0;
    }
    
    .errorlist li {
        background: #e74c3c;
        color: white;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 5px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="register-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-user-plus"></i> تسجيل طالب جديد
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            أنشئ حسابك للاستفادة من الخدمات الجامعية
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li class="active">تسجيل طالب جديد</li>
    </ol>
    
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="register-container">
                
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    
                    <!-- معلومات الحساب -->
                    <div class="form-section">
                        <h4><i class="fa fa-key"></i> معلومات الحساب</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">رقم التسجيل الجامعي</label>
                                    <?= $form->widget('registration_number') ?>
                                    <?= $form->errors_html('registration_number') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">البريد الإلكتروني</label>
                                    <?= $form->widget('email') ?>
                                    <?= $form->errors_html('email') ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">كلمة المرور</label>
                                    <?= $form->widget('password1') ?>
                                    <?= $form->errors_html('password1') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">تأكيد كلمة المرور</label>
                                    <?= $form->widget('password2') ?>
                                    <?= $form->errors_html('password2') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- معلومات شخصية -->
                    <div class="form-section">
                        <h4><i class="fa fa-user"></i> المعلومات الشخصية</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="required">الاسم الكامل بالعربية</label>
                                    <?= $form->widget('full_name_arabic') ?>
                                    <?= $form->errors_html('full_name_arabic') ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">تاريخ الميلاد</label>
                                    <?= $form->widget('birth_date') ?>
                                    <?= $form->errors_html('birth_date') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">مكان الميلاد</label>
                                    <?= $form->widget('birth_place') ?>
                                    <?= $form->errors_html('birth_place') ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الجنس</label>
                                    <?= $form->widget('gender') ?>
                                    <?= $form->errors_html('gender') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- معلومات الاتصال -->
                    <div class="form-section">
                        <h4><i class="fa fa-phone"></i> معلومات الاتصال</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">رقم الهاتف</label>
                                    <?= $form->widget('phone') ?>
                                    <?= $form->errors_html('phone') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الولاية</label>
                                    <?= $form->widget('wilaya') ?>
                                    <?= $form->errors_html('wilaya') ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="required">العنوان الكامل</label>
                                    <?= $form->widget('address') ?>
                                    <?= $form->errors_html('address') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- معلومات أكاديمية -->
                    <div class="form-section">
                        <h4><i class="fa fa-graduation-cap"></i> المعلومات الأكاديمية</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الكلية</label>
                                    <?= $form->widget('faculty') ?>
                                    <?= $form->errors_html('faculty') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">القسم/التخصص</label>
                                    <?= $form->widget('department') ?>
                                    <?= $form->errors_html('department') ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">السنة الدراسية</label>
                                    <?= $form->widget('current_year') ?>
                                    <?= $form->errors_html('current_year') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- زر التسجيل -->
                    <div class="text-center" style="margin-top: 30px;">
                        <button type="submit" class="btn-register">
                            <i class="fa fa-check"></i> إنشاء الحساب
                        </button>
                    </div>
                    
                    <!-- رابط تسجيل الدخول -->
                    <div class="login-link">
                        <p>لديك حساب بالفعل؟ 
                            <a href="<?= e(url('students:login')) ?>" style="color: #3498db; font-weight: bold;">
                                سجّل الدخول من هنا
                            </a>
                        </p>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
</div>

<br><br>
<div class="container">
    
    <!-- عرض رسائل الأخطاء -->
    <?php if (!empty($messages)): ?>
    <div class="alert-container">
        <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?= e(alert_class($message['tags'] ?: 'info')) ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>
                <?php if ($message['tags'] == 'error'): ?>
                    <i class="fa fa-exclamation-circle"></i> خطأ!
                <?php elseif ($message['tags'] == 'success'): ?>
                    <i class="fa fa-check-circle"></i> نجاح!
                <?php elseif ($message['tags'] == 'warning'): ?>
                    <i class="fa fa-warning"></i> تحذير!
                <?php else: ?>
                    <i class="fa fa-info-circle"></i> معلومة!
                <?php endif; ?>
            </strong>
            <?= e($message['message']) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- عرض أخطاء النموذج العامة -->
    <?php if (!empty($form->non_field_errors())): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong><i class="fa fa-exclamation-circle"></i> أخطاء في النموذج:</strong>
        <?= $form->non_field_errors_html() ?>
    </div>
    <?php endif; ?>
    
</div>
<?php endblock(); ?>