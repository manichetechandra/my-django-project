<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<!-- Meta tags لمنع cache -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
    .login-header {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .login-container {
        background: white;
        padding: 50px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 40px;
        max-width: 500px;
        margin-inline-end: auto;
        margin-inline-start: auto;
    }
    
    .login-icon {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-icon i {
        font-size: 64px;
        color: #2ecc71;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        font-weight: bold;
        color: #34495e;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-control {
        height: 45px;
        font-size: 16px;
    }
    
    .remember-me {
        display: flex;
        align-items: center;
        margin: 20px 0;
    }
    
    .remember-me input {
        margin-inline-end: 10px;
    }
    
    .btn-login {
        background: #2ecc71;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
    }
    
    .btn-login:hover {
        background: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
    }
    
    .register-link {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #ddd;
    }
    
    .forgot-password {
        text-align: center;
        margin-top: 15px;
    }
    
    .forgot-password a {
        color: #7f8c8d;
        text-decoration: none;
    }
    
    .forgot-password a:hover {
        color: #2ecc71;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="login-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-sign-in"></i> <?= t('تسجيل الدخول') ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= t('سجّل دخولك للوصول إلى لوحة التحكم') ?>
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li class="active"><?= t('تسجيل الدخول') ?></li>
    </ol>
    
    <div class="login-container">
        
        <!-- أيقونة -->
        <div class="login-icon">
            <i class="fa fa-user-circle"></i>
        </div>
        
        <!-- نموذج تسجيل الدخول -->
        <form method="post" novalidate>
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="id_username">
                    <i class="fa fa-user"></i> <?= t('رقم التسجيل أو اسم المستخدم') ?>
                </label>
                <?= $form->widget('username') ?>
            </div>
            
            <div class="form-group">
                <label for="id_password">
                    <i class="fa fa-lock"></i> <?= t('كلمة المرور') ?>
                </label>
                <?= $form->widget('password') ?>
            </div>
            
            <div class="remember-me">
                <?= $form->widget('remember_me') ?>
                <label for="id_remember_me" style="margin: 0; font-weight: normal;">
                    <?= t('تذكرني') ?>
                </label>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fa fa-sign-in"></i> <?= t('تسجيل الدخول') ?>
            </button>
            
            <div class="forgot-password">
                <a href="#">
                    <i class="fa fa-question-circle"></i> <?= t('نسيت كلمة المرور؟') ?>
                </a>
            </div>
            
        </form>
        
        <!-- رابط التسجيل -->
        <div class="register-link">
            <p><?= t('ليس لديك حساب؟') ?></p>
            <a href="<?= e(url('students:register')) ?>" 
               class="btn btn-default btn-lg" 
               style="background: #3498db; color: white; border: none;">
                <i class="fa fa-user-plus"></i> <?= t('سجّل الآن مجاناً') ?>
            </a>
        </div>
        
    </div>
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// منع إعادة إرسال النموذج عند الرجوع
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// مسح علامة logout عند الدخول لصفحة login
localStorage.removeItem('loggedOut');

// منع cache للصفحة
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};

// التعامل مع زر الرجوع
window.addEventListener('popstate', function(event) {
    window.location.reload();
});
</script>
<?php endblock(); ?>