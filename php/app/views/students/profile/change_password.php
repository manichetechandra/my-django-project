<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .profile-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .form-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        padding: 40px;
        margin-bottom: 30px;
    }
    
    .security-icon {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .security-icon i {
        font-size: 80px;
        color: #e74c3c;
        opacity: 0.8;
    }
    
    .password-requirements {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .password-requirements ul {
        margin: 10px 0 0 0;
        padding-inline-start: 20px;
    }
    
    .password-requirements li {
        color: #856404;
        margin: 5px 0;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="profile-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-lock"></i> <?= t('تغيير كلمة المرور') ?>
        </h1>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>"><?= t('لوحة التحكم') ?></a></li>
        <li><a href="<?= e(url('students:profile_view')) ?>"><?= t('معلوماتي') ?></a></li>
        <li class="active"><?= t('تغيير كلمة المرور') ?></li>
    </ol>
    
    <div class="row">
        
        <div class="col-md-6 col-md-offset-3">
            
            <div class="form-card">
                
                <div class="security-icon">
                    <i class="fa fa-shield"></i>
                    <h3 style="margin-top: 15px; color: #2c3e50;"><?= t('تأمين حسابك') ?></h3>
                </div>
                
                <div class="password-requirements">
                    <strong><i class="fa fa-info-circle"></i> <?= t('متطلبات كلمة المرور:') ?></strong>
                    <ul>
                        <li><?= t('يجب أن تحتوي على 8 أحرف على الأقل') ?></li>
                        <li><?= t('لا تستخدم معلومات شخصية واضحة') ?></li>
                        <li><?= t('استخدم مزيج من الأحرف والأرقام') ?></li>
                    </ul>
                </div>
                
                <form method="post">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label><?= e($form->label('old_password')) ?>: <span class="text-danger">*</span></label>
                        <?= $form->widget('old_password') ?>
                        <?php if (!empty($form->help_text('old_password'))): ?>
                        <small class="help-block"><?= $form->help_text('old_password') ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <hr style="margin: 30px 0;">
                    
                    <div class="form-group">
                        <label><?= e($form->label('new_password1')) ?>: <span class="text-danger">*</span></label>
                        <?= $form->widget('new_password1') ?>
                        <?php if (!empty($form->help_text('new_password1'))): ?>
                        <small class="help-block"><?= $form->help_text('new_password1') ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label><?= e($form->label('new_password2')) ?>: <span class="text-danger">*</span></label>
                        <?= $form->widget('new_password2') ?>
                        <?php if (!empty($form->help_text('new_password2'))): ?>
                        <small class="help-block"><?= $form->help_text('new_password2') ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-danger btn-lg" style="padding: 12px 50px;">
                            <i class="fa fa-lock"></i> <?= t('تغيير كلمة المرور') ?>
                        </button>
                        
                        <a href="<?= e(url('students:profile_view')) ?>" class="btn btn-default btn-lg" style="padding: 12px 50px;">
                            <i class="fa fa-times"></i> <?= t('إلغاء') ?>
                        </a>
                    </div>
                    
                </form>
                
            </div>
            
        </div>
        
    </div>
    
</div>

<br><br>

<?php endblock(); ?>