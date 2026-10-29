<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .application-header {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .application-container {
        background: white;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 8px;
        border-inline-start: 4px solid #3498db;
    }
    
    .form-section h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        font-weight: bold;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        font-weight: bold;
        color: #34495e;
        margin-bottom: 8px;
        display: block;
    }
    
    .required-docs {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .required-docs h5 {
        color: #856404;
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .required-docs ul {
        margin: 0;
        padding-inline-start: 20px;
    }
    
    .required-docs li {
        color: #856404;
        margin-bottom: 5px;
    }
    
    .document-upload-area {
        border: 2px dashed #3498db;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        margin-bottom: 20px;
    }
    
    .document-item {
        background: white;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-submit {
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
    
    .btn-submit:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }
    
    .btn-add-document {
        background: #2ecc71;
        color: white;
        padding: 8px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .btn-remove {
        background: #e74c3c;
        color: white;
        padding: 5px 15px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="application-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-plus-circle"></i> <?= t('تقديم طلب جديد') ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= t('اختر نوع الطلب وأرفق الوثائق المطلوبة') ?>
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>"><?= t('لوحة التحكم') ?></a></li>
        <li class="active"><?= t('تقديم طلب جديد') ?></li>
    </ol>
    
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="application-container">
                
                <form method="post" enctype="multipart/form-data" id="applicationForm">
                    <?= csrf_field() ?>
                    
                    <!-- اختيار نوع الطلب -->
                    <div class="form-section">
                        <h4><i class="fa fa-file-text"></i> <?= t('نوع الطلب') ?></h4>
                        
                        <div class="form-group">
                            <label class="required"><?= t('اختر نوع الطلب') ?> <span style="color: red;">*</span></label>
                            <?= $form->widget('application_type') ?>
                            <?= $form->errors_html('application_type') ?>
                        </div>
                        
                        <!-- عرض الوثائق المطلوبة حسب النوع -->
                        <div id="required-documents-section" style="display: none;">
                            <div class="required-docs">
                                <h5><i class="fa fa-info-circle"></i> <?= t('الوثائق المطلوبة لهذا الطلب:') ?></h5>
                                <ul id="required-documents-list"></ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تفاصيل الطلب -->
                    <div class="form-section">
                        <h4><i class="fa fa-edit"></i> <?= t('تفاصيل الطلب (اختياري)') ?></h4>
                        
                        <div class="form-group">
                            <label><?= t('أضف تفاصيل أو ملاحظات إضافية') ?></label>
                            <?= $form->widget('details') ?>
                            <?= $form->errors_html('details') ?>
                        </div>
                    </div>
                    
                    <!-- رفع الوثائق -->
                    <div class="form-section">
                        <h4><i class="fa fa-upload"></i> <?= t('رفع الوثائق') ?></h4>
                        
                        <div class="document-upload-area">
                            <i class="fa fa-cloud-upload" style="font-size: 48px; color: #3498db; margin-bottom: 15px;"></i>
                            <p style="color: #7f8c8d; margin-bottom: 15px;">
                                <?= t('أرفق الوثائق المطلوبة (PDF, JPG, PNG)') ?>
                            </p>
                            <button type="button" class="btn-add-document" onclick="addDocumentField()">
                                <i class="fa fa-plus"></i> <?= t('إضافة وثيقة') ?>
                            </button>
                        </div>
                        
                        <div id="documents-container"></div>
                    </div>
                    
                    <!-- زر التقديم -->
                    <div class="text-center" style="margin-top: 40px;">
                        <button type="submit" class="btn-submit">
                            <i class="fa fa-paper-plane"></i> <?= t('تقديم الطلب') ?>
                        </button>
                        <a href="<?= e(url('students:dashboard')) ?>" class="btn btn-default btn-lg" style="margin-inline-start: 10px;">
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

<?php block('extra_js'); ?>
<script>
// بيانات أنواع الطلبات والوثائق المطلوبة
const applicationTypes = {
    <?php foreach ($application_types as $app_type): ?>
    '<?= e($app_type['id']) ?>': {
        name: '<?= e($app_type['name']) ?>',
        documents: `<?= escapejs($app_type['required_documents']) ?>`
    },
    <?php endforeach; ?>
};

// عداد الوثائق
let documentCounter = 0;

// عند تغيير نوع الطلب
document.getElementById('id_application_type').addEventListener('change', function() {
    const selectedType = this.value;
    const docsSection = document.getElementById('required-documents-section');
    const docsList = document.getElementById('required-documents-list');
    
    if (selectedType && applicationTypes[selectedType]) {
        const docs = applicationTypes[selectedType].documents;
        
        if (docs.trim()) {
            // تقسيم الوثائق إلى سطور
            const docArray = docs.split('\n').filter(d => d.trim());
            
            // بناء القائمة
            docsList.innerHTML = '';
            docArray.forEach(doc => {
                if (doc.trim()) {
                    const li = document.createElement('li');
                    li.textContent = doc.trim();
                    docsList.appendChild(li);
                }
            });
            
            docsSection.style.display = 'block';
        } else {
            docsSection.style.display = 'none';
        }
    } else {
        docsSection.style.display = 'none';
    }
});

// إضافة حقل وثيقة جديد
function addDocumentField() {
    documentCounter++;
    
    const container = document.getElementById('documents-container');
    const docItem = document.createElement('div');
    docItem.className = 'document-item';
    docItem.id = 'doc-' + documentCounter;
    
    docItem.innerHTML = `
        <div style="flex: 1;">
            <div class="row">
                <div class="col-md-5">
                    <input type="text" 
                           name="document_names[]" 
                           class="form-control" 
                           placeholder="<?= t('اسم الوثيقة') ?>"
                           required>
                </div>
                <div class="col-md-7">
                    <input type="file" 
                           name="documents[]" 
                           class="form-control" 
                           accept=".pdf,.jpg,.jpeg,.png"
                           required>
                </div>
            </div>
        </div>
        <button type="button" class="btn-remove" onclick="removeDocument(${documentCounter})">
            <i class="fa fa-trash"></i>
        </button>
    `;
    
    container.appendChild(docItem);
}

// حذف وثيقة
function removeDocument(id) {
    const element = document.getElementById('doc-' + id);
    if (element) {
        element.remove();
    }
}

// التحقق قبل الإرسال
document.getElementById('applicationForm').addEventListener('submit', function(e) {
    const appType = document.getElementById('id_application_type').value;
    
    if (!appType) {
        e.preventDefault();
        alert(<?= js_t('يرجى اختيار نوع الطلب') ?>);
        return false;
    }
});

// إذا كان نوع الطلب محدد مسبقاً
window.addEventListener('load', function() {
    const appTypeSelect = document.getElementById('id_application_type');
    if (appTypeSelect.value) {
        appTypeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
<?php endblock(); ?>