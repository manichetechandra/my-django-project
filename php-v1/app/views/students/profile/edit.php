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
            <i class="fa fa-edit"></i> تعديل معلوماتي الشخصية
        </h1>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>">لوحة التحكم</a></li>
        <li><a href="<?= e(url('students:profile_view')) ?>">معلوماتي</a></li>
        <li class="active">تعديل المعلومات</li>
    </ol>
    
    <div class="row">
        
        <div class="col-md-8 col-md-offset-2">
            
            <form method="post" enctype="multipart/form-data" class="form-card">
                <?= csrf_field() ?>
                
                <!-- قسم الصورة الشخصية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-camera"></i> الصورة الشخصية
                    </h3>
                    
                    <div class="image-preview-section">
                        <?php if (!empty($student['photo'])): ?>
                        <img src="<?= e(media_url($student['photo'])) ?>" alt="الصورة الحالية" class="current-image" id="currentImage">
                        <br>
                        <button type="button" class="btn-delete-image" onclick="deleteImage()">
                            <i class="fa fa-trash"></i> حذف الصورة
                        </button>
                        <?php else: ?>
                        <div class="current-image" style="background: #bdc3c7; display: flex; align-items: center; justify-content: center; font-size: 60px; color: white;">
                            <i class="fa fa-user"></i>
                        </div>
                        <p style="color: #7f8c8d;">لا توجد صورة شخصية</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>رفع صورة جديدة:</label>
                        <?= $profile_form->widget('photo') ?>
                        <small class="help-block">الصيغ المدعومة: JPG, PNG (الحد الأقصى: 2MB)</small>
                    </div>
                </div>
                
                <!-- المعلومات الشخصية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-user"></i> المعلومات الشخصية
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>الاسم الكامل بالعربية: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('full_name_arabic') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>تاريخ الميلاد: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('birth_date') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>مكان الميلاد: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('birth_place') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الجنس: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('gender') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- معلومات الاتصال -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-phone"></i> معلومات الاتصال
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>رقم الهاتف: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('phone') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>البريد الإلكتروني:</label>
                                <?= $email_form->widget('email') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>العنوان: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('address') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الولاية: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('wilaya') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- المعلومات الأكاديمية -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fa fa-graduation-cap"></i> المعلومات الأكاديمية
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>الكلية: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('faculty') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>القسم/التخصص: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('department') ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>السنة الدراسية: <span class="text-danger">*</span></label>
                                <?= $profile_form->widget('current_year') ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- أزرار الحفظ -->
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 50px;">
                        <i class="fa fa-save"></i> حفظ التعديلات
                    </button>
                    
                    <a href="<?= e(url('students:profile_view')) ?>" class="btn btn-default btn-lg" style="padding: 12px 50px;">
                        <i class="fa fa-times"></i> إلغاء
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
    if (!confirm('هل أنت متأكد من حذف الصورة الشخصية؟')) {
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
            alert('تم حذف الصورة بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + data.error);
        }
    })
    .catch(error => {
        alert('حدث خطأ في الاتصال');
        console.error(error);
    });
}
</script>
<?php endblock(); ?>