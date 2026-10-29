<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 0;
    }
    
    .form-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-section-title {
        font-size: 20px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    
    .current-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #ecf0f1;
        margin-bottom: 15px;
    }
    
    .image-preview-section {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .btn-delete-image {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .btn-delete-image:hover {
        background: #c0392b;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="profile-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-edit"></i> <?= t('تعديل معلوماتي الشخصية') ?>
        </h1>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>"><?= t('لوحة التحكم') ?></a></li>
        <li><a href="<?= e(url('students:profile_view')) ?>"><?= t('معلوماتي') ?></a></li>
        <li class="active"><?= t('تعديل المعلومات') ?></li>
    </ol>
    
    <div class="row">
        
        <div class="col-md-8 col-md-offset-2">
            
            <form method="post" enctype="multipart/form-data" class="form-card">
                <?= csrf_field() ?>
                
                <!-- قسم الصورة الشخصية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-camera"></i> <?= t('الصورة الشخصية') ?>
                    </h3>
                    
                    <div class="image-preview-section">
                        <?php if (!empty($student['photo'])): ?>
                        <img src="<?= e(media_url($student['photo'])) ?>" alt="<?= t('الصورة الحالية') ?>" class="current-image" id="currentImage">
                        <br>
                        <button type="button" class="btn-delete-image" onclick="deleteImage()">
                            <i class="fa fa-trash"></i> <?= t('حذف الصورة') ?>
                        </button>
                        <?php else: ?>
                        <div class="current-image" style="background: #bdc3c7; display: flex; align-items: center; justify-content: center; font-size: 60px; color: white;">
                            <i class="fa fa-user"></i>
                        </div>
                        <p style="color: #7f8c8d;"><?= t('لا توجد صورة شخصية') ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label><?= t('رفع صورة جديدة:') ?></label>
                        <?= $profile_form->widget('photo') ?>
                        <small class="help-block"><?= t('الصيغ المدعومة: JPG, PNG (الحد الأقصى: 2MB)') ?></small>
                    </div>
                </div>
                
                <!-- المعلومات الشخصية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-user"></i> <?= t('المعلومات الشخصية') ?>
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><?= t('الاسم الكامل بالعربية:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('full_name_arabic') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('تاريخ الميلاد:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('birth_date') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('مكان الميلاد:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('birth_place') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('الجنس:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('gender') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- معلومات الاتصال -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-phone"></i> <?= t('معلومات الاتصال') ?>
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('رقم الهاتف:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('phone') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('البريد الإلكتروني:') ?></label>
                                <?= $email_form->widget('email') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><?= t('العنوان:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('address') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('الولاية:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('wilaya') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- المعلومات الأكاديمية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-graduation-cap"></i> <?= t('المعلومات الأكاديمية') ?>
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('الكلية:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('faculty') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('القسم/التخصص:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('department') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= t('السنة الدراسية:') ?> <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('current_year') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- أزرار الحفظ -->
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 50px;">
                        <i class="fa fa-save"></i> <?= t('حفظ التعديلات') ?>
                    </button>
                    
                    <a href="<?= e(url('students:profile_view')) ?>" class="btn btn-default btn-lg" style="padding: 12px 50px;">
                        <i class="fa fa-times"></i> <?= t('إلغاء') ?>
                    </a>
                </div>
                
            </form>
            
        </div>
        
    </div>
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// حذف الصورة الشخصية
function deleteImage() {
    if (!confirm(<?= js_t('هل أنت متأكد من حذف الصورة الشخصية؟') ?>)) {
        return;
    }
    
    fetch('<?= e(url('students:delete_profile_image')) ?>', {
        method: 'POST',
        headers: {
            'X-CSRFToken': '<?= e(csrf_token()) ?>',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(<?= js_t('تم حذف الصورة بنجاح') ?>);
            location.reload();
        } else {
            alert(<?= js_t('خطأ: ') ?> + data.error);
        }
    })
    .catch(error => {
        alert(<?= js_t('حدث خطأ في الاتصال') ?>);
        console.error(error);
    });
}
</script>
<?php endblock(); ?>