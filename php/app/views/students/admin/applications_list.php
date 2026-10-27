<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    /* نفس الـ CSS السابق + إضافات */
    
    .list-header {
        background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .filter-box {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .filter-box h4 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
        border-bottom: 2px solid #16a085;
        padding-bottom: 10px;
    }
    
    /* ========================================
       جديد: تصميم الإجراءات الجماعية
       ======================================== */
    
    .bulk-actions-bar {
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        display: none; /* مخفي افتراضياً */
    }
    
    .bulk-actions-bar.active {
        display: block; /* يظهر عند التحديد */
    }
    
    .bulk-actions-bar .selected-count {
        font-weight: bold;
        color: #856404;
        font-size: 16px;
        margin-left: 15px;
    }
    
    .bulk-action-select {
        padding: 8px 15px;
        border: 2px solid #ffc107;
        border-radius: 5px;
        font-size: 14px;
        margin: 0 10px;
    }
    
    .bulk-action-btn {
        background: #ffc107;
        color: #856404;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .bulk-action-btn:hover {
        background: #e0a800;
        transform: translateY(-2px);
    }
    
    /* Checkbox مخصص */
    .app-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .select-all-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    /* صف محدد */
    tr.selected {
        background: #fff3cd !important;
    }
    
    /* باقي الـ CSS السابق */
    .applications-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .applications-table h3 {
        background: #34495e;
        color: white;
        padding: 20px;
        margin: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table tbody tr {
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .status-badge {
        padding: 6px 14px;
        border-radius: 15px;
        color: white;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }
    
    .status-pending { background: #f39c12; }
    .status-under_review { background: #3498db; }
    .status-approved { background: #2ecc71; }
    .status-rejected { background: #e74c3c; }
    .status-cancelled { background: #95a5a6; }
    
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
    
    .action-buttons .btn {
        margin-left: 5px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="list-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-list"></i> إدارة الطلبات
        </h1>
        <p style="margin-top: 10px; opacity: 0.9;">
            عرض وإدارة كل طلبات الطلبة
        </p>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:admin_dashboard')) ?>">لوحة المشرف</a></li>
        <li class="active">إدارة الطلبات</li>
    </ol>
    
    <!-- ========================================
         قسم الفلترة والبحث
         ======================================== -->
    <div class="filter-box">
        <h4><i class="fa fa-filter"></i> تصفية وبحث</h4>
        
        <form method="get" class="form-inline">
            <div class="form-group" style="margin-left: 15px;">
                <label style="margin-left: 5px;">الحالة:</label>
                <select name="status" class="form-control">
                    <option value="">الكل</option>
                    <option value="pending" <?php if ($status_filter == 'pending'): ?>selected<?php endif; ?>>
                        قيد الانتظار
                    </option>
                    <option value="under_review" <?php if ($status_filter == 'under_review'): ?>selected<?php endif; ?>>
                        قيد المراجعة
                    </option>
                    <option value="approved" <?php if ($status_filter == 'approved'): ?>selected<?php endif; ?>>
                        معتمد
                    </option>
                    <option value="rejected" <?php if ($status_filter == 'rejected'): ?>selected<?php endif; ?>>
                        مرفوض
                    </option>
                    <option value="cancelled" <?php if ($status_filter == 'cancelled'): ?>selected<?php endif; ?>>
                        ملغي
                    </option>
                </select>
            </div>
            
            <div class="form-group" style="margin-left: 15px;">
                <label style="margin-left: 5px;">النوع:</label>
                <select name="type" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach ($application_types as $app_type): ?>
                    <option value="<?= e($app_type['id']) ?>" 
                            <?php if ($type_filter == (string)($app_type['id'])): ?>selected<?php endif; ?>>
                        <?= e($app_type['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-left: 15px;">
                <label style="margin-left: 5px;">بحث:</label>
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="رقم الطلب أو اسم الطالب"
                       value="<?= e($search_query) ?>"
                       style="width: 250px;">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> بحث
            </button>
            
            <a href="<?= e(url('students:admin_applications_list')) ?>" class="btn btn-default">
                <i class="fa fa-refresh"></i> إعادة تعيين
            </a>
        </form>
    </div>
    
        <!-- ========================================
         نموذج واحد يحتوي كل شيء ← الحل!
         ======================================== -->
    <form method="post" id="bulkActionsForm">
        <?= csrf_field() ?>
        
        <!-- ========================================
             شريط الإجراءات الجماعية
             ======================================== -->
        <div class="bulk-actions-bar" id="bulkActionsBar">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <div>
                    <i class="fa fa-check-square-o" style="font-size: 20px; color: #856404;"></i>
                    <span class="selected-count">
                        تم تحديد <strong id="selectedCount">0</strong> طلب
                    </span>
                </div>
                
                <div>
                    <select name="bulk_action" class="bulk-action-select" required>
                        <option value="">-- اختر الإجراء --</option>
                        <option value="approve">✅ اعتماد الكل</option>
                        <option value="reject">❌ رفض الكل</option>
                        <option value="review">📝 قيد المراجعة</option>
                        <option value="pending">⏳ قيد الانتظار</option>
                        <option value="delete" style="color: red;">🗑️ حذف</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="bulk-action-btn">
                        <i class="fa fa-bolt"></i> تطبيق
                    </button>
                    
                    <button type="button" class="btn btn-default btn-sm" onclick="clearSelection()">
                        <i class="fa fa-times"></i> إلغاء التحديد
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ========================================
             جدول الطلبات - داخل نفس الـ form!
             ======================================== -->
        <div class="applications-table">
                    <h3>
            <span>
                <i class="fa fa-list"></i> قائمة الطلبات
                <small style="opacity: 0.7; font-size: 14px; margin-right: 15px;">
                    (<?= e($total_count) ?> طلب)
                </small>
            </span>
            <span>
                <!-- ========================================
                     زر تصدير Excel ← جديد!
                     
                     الشرح:
                     - نفس URL parameters (status, type, search)
                     - يصدّر الطلبات المفلترة فقط!
                     ======================================== -->
                <a href="<?= e(url('students:export_applications_excel')) ?>?<?= e(http_build_query($_GET)) ?>" 
                   class="btn btn-success btn-sm"
                   title="تصدير إلى Excel">
                    <i class="fa fa-file-excel-o"></i> تصدير Excel
                </a>
                
                <a href="<?= e(url('students:admin_dashboard')) ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-right"></i> العودة
                </a>
            </span>
        </h3>
            
            <?php if (!empty($applications)): ?>
            <table class="table table-striped table-hover" style="margin: 0;">
                <thead style="background: #ecf0f1;">
                    <tr>
                        <th width="3%" style="text-align: center;">
                            <input type="checkbox" 
                                   class="select-all-checkbox" 
                                   id="selectAll"
                                   title="تحديد الكل">
                        </th>
                        <th width="10%">رقم الطلب</th>
                        <th width="18%">الطالب</th>
                        <th width="13%">رقم التسجيل</th>
                        <th width="18%">نوع الطلب</th>
                        <th width="12%">الحالة</th>
                        <th width="13%">التاريخ</th>
                        <th width="13%">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr class="app-row" data-id="<?= e($app['id']) ?>">
                        <td style="text-align: center;">
                            <input type="checkbox" 
                                   class="app-checkbox" 
                                   name="selected_applications" 
                                   value="<?= e($app['id']) ?>">
                        </td>
                        
                        <td>
                            <strong style="color: #3498db;">
                                <?= e($app['application_number']) ?>
                            </strong>
                        </td>
                        
                        <td>
                            <i class="fa fa-user" style="color: #95a5a6; margin-left: 5px;"></i>
                            <?= e($app['student']['full_name_arabic']) ?>
                        </td>
                        
                        <td>
                            <code style="background: #ecf0f1; padding: 3px 8px; border-radius: 3px;">
                                <?= e($app['student']['registration_number']) ?>
                            </code>
                        </td>
                        
                        <td>
                            <i class="fa <?= e($app['application_type']['icon']) ?>" 
                               style="color: <?= e($app['application_type']['color']) ?>; margin-left: 5px;"></i>
                            <?= e($app['application_type']['name']) ?>
                        </td>
                        
                        <td>
                            <span class="status-badge status-<?= e($app['status']) ?>">
                                <i class="fa <?= e($app->get_status_icon()) ?>"></i>
                                <?= e($app->get_status_display()) ?>
                            </span>
                        </td>
                        
                        <td>
                            <small style="color: #7f8c8d;">
                                <i class="fa fa-calendar"></i>
                                <?= e(format_date($app['submitted_at'], 'd/m/Y')) ?>
                            </small>
                        </td>
                        
                        <td class="action-buttons">
                            <a href="<?= e(url('students:admin_application_detail', $app['id'])) ?>" 
                               class="btn btn-xs btn-primary"
                               title="عرض التفاصيل">
                                <i class="fa fa-eye"></i>
                            </a>
                            
                            <?php if ($app['status'] == 'pending'): ?>
                            <a href="<?= e(url('students:admin_application_detail', $app['id'])) ?>" 
                               class="btn btn-xs btn-success"
                               title="مراجعة">
                                <i class="fa fa-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="padding: 15px; background: #f8f9fa; text-align: center; border-top: 1px solid #ddd;">
                <p style="margin: 0; color: #7f8c8d;">
                    عرض <?= e(count($applications)) ?> طلب من أصل <?= e($total_count) ?>
                </p>
            </div>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <h3>لا توجد طلبات</h3>
                <p>لا توجد طلبات تطابق معايير البحث</p>
                <a href="<?= e(url('students:admin_applications_list')) ?>" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fa fa-refresh"></i> عرض كل الطلبات
                </a>
            </div>
            <?php endif; ?>
        </div>
    </form>
    <!-- نهاية الـ form الموحد -->
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// ========================================
// JavaScript للإجراءات الجماعية
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // العناصر
    const selectAllCheckbox = document.getElementById('selectAll');
    const appCheckboxes = document.querySelectorAll('.app-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionsForm = document.getElementById('bulkActionsForm');
    
    // ========================================
    // 1. تحديد الكل / إلغاء تحديد الكل
    // ========================================
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            
            appCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                
                // تمييز الصف
                const row = checkbox.closest('tr');
                if (isChecked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            
            updateBulkActionsBar();
        });
    }
    
    // ========================================
    // 2. تحديد/إلغاء تحديد طلب واحد
    // ========================================
    appCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // تمييز الصف
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            
            // تحديث "تحديد الكل"
            const allChecked = Array.from(appCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(appCheckboxes).some(cb => cb.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
            
            updateBulkActionsBar();
        });
    });
    
    // ========================================
    // 3. تحديث شريط الإجراءات الجماعية
    // ========================================
    function updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll('.app-checkbox:checked').length;
        
        if (checkedCount > 0) {
            bulkActionsBar.classList.add('active');
            selectedCountSpan.textContent = checkedCount;
        } else {
            bulkActionsBar.classList.remove('active');
        }
    }
    
    // ========================================
    // 4. رسالة تأكيد عند الإرسال
    // ========================================
    if (bulkActionsForm) {
        bulkActionsForm.addEventListener('submit', function(e) {
            const action = this.querySelector('[name="bulk_action"]').value;
            const count = document.querySelectorAll('.app-checkbox:checked').length;
            
            if (!action) {
                e.preventDefault();
                alert('يرجى اختيار إجراء!');
                return false;
            }
            
            if (count === 0) {
                e.preventDefault();
                alert('يرجى تحديد طلب واحد على الأقل!');
                return false;
            }
            
            // رسائل تأكيد حسب الإجراء
            let message = '';
            
            if (action === 'approve') {
                message = `هل أنت متأكد من اعتماد ${count} طلب؟`;
            } else if (action === 'reject') {
                message = `هل أنت متأكد من رفض ${count} طلب؟\n\nملاحظة: يمكنك إضافة سبب الرفض في الخطوة التالية.`;
            } else if (action === 'delete') {
                message = `⚠️ تحذير!\n\nهل أنت متأكد من حذف ${count} طلب نهائياً؟\n\nلا يمكن التراجع عن هذا الإجراء!`;
            } else {
                message = `هل أنت متأكد من تطبيق هذا الإجراء على ${count} طلب؟`;
            }
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            // إذا كان الإجراء "رفض"، نطلب سبب الرفض
            if (action === 'reject') {
                const notes = prompt('أدخل سبب الرفض (اختياري):');
                if (notes !== null && notes.trim() !== '') {
                    // إضافة حقل مخفي للملاحظات
                    const notesInput = document.createElement('input');
                    notesInput.type = 'hidden';
                    notesInput.name = 'bulk_notes';
                    notesInput.value = notes;
                    this.appendChild(notesInput);
                }
            }
        });
    }
    
});

// ========================================
// دالة إلغاء التحديد (من زر "إلغاء التحديد")
// ========================================
function clearSelection() {
    document.querySelectorAll('.app-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('tr').classList.remove('selected');
    });
    
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
    
    document.getElementById('bulkActionsBar').classList.remove('active');
}
</script>
<?php endblock(); ?>