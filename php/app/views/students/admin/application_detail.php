<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    /* ========================================
       تصميم صفحة مراجعة الطلب
       ======================================== */
    
    .detail-header {
        background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .detail-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 40px;
    }
    
    .detail-section {
        padding: 30px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .detail-section:last-child {
        border-bottom: none;
    }
    
    .detail-section h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
        padding-bottom: 10px;
        border-bottom: 2px solid #8e44ad;
    }
    
    /* Grid للمعلومات */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border-inline-start: 3px solid #8e44ad;
    }
    
    .info-item label {
        display: block;
        font-weight: bold;
        color: #7f8c8d;
        font-size: 13px;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    
    .info-item .value {
        color: #2c3e50;
        font-size: 16px;
        font-weight: 500;
    }
    
    /* شارات الحالة */
    .status-badge {
        display: inline-block;
        padding: 10px 25px;
        border-radius: 25px;
        color: white;
        font-size: 16px;
        font-weight: bold;
    }
    
    .status-pending { background: #f39c12; }
    .status-under_review { background: #3498db; }
    .status-approved { background: #2ecc71; }
    .status-rejected { background: #e74c3c; }
    .status-cancelled { background: #95a5a6; }
    
    /* قسم الإجراءات - مهم جداً! */
    .action-section {
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    .action-section h4 {
        color: #856404;
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    /* أزرار الإجراءات الكبيرة */
    .action-btn {
        padding: 15px 30px;
        font-size: 16px;
        font-weight: bold;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
        margin-inline-end: 10px;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-approve {
        background: #2ecc71;
        color: white;
    }
    
    .btn-approve:hover {
        background: #27ae60;
    }
    
    .btn-reject {
        background: #e74c3c;
        color: white;
    }
    
    .btn-reject:hover {
        background: #c0392b;
    }
    
    /* قائمة الوثائق */
    .documents-list {
        list-style: none;
        padding: 0;
    }
    
    .documents-list li {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-inline-start: 3px solid #3498db;
    }
    
    .documents-list li:hover {
        background: #ecf0f1;
    }
    
    /* ملاحظات الإدارة */
    .admin-notes-box {
        background: #e8f8f5;
        border: 2px solid #1abc9c;
        border-radius: 5px;
        padding: 20px;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- ========================================
     رأس الصفحة
     ======================================== -->
<div class="detail-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-clipboard"></i> مراجعة الطلب
        </h1>
        <p style="margin-top: 10px; opacity: 0.9;">
            رقم الطلب: <?= e($application['application_number']) ?>
        </p>
    </div>
</div>

<div class="container">
    
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:admin_dashboard')) ?>">لوحة المشرف</a></li>
        <li><a href="<?= e(url('students:admin_applications_list')) ?>">الطلبات</a></li>
        <li class="active"><?= e($application['application_number']) ?></li>
    </ol>
    
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            
            <!-- ========================================
                 قسم الإجراءات السريعة - في الأعلى
                 
                 الشرح:
                 - فقط إذا كان الطلب "معلق"
                 - نموذج POST لتغيير الحالة
                 - action="<?= e(request_path()) ?>" = نفس الصفحة
                 ======================================== -->
            <?php if ($application['status'] == 'pending'): ?>
            <div class="action-section">
                <h4><i class="fa fa-flash"></i> إجراءات سريعة</h4>
                
                <!-- نموذج الاعتماد -->
                <form method="post" style="display: inline;">
                    <!-- 
                        csrf_field()
                        
                        الشرح:
                        - CSRF = Cross-Site Request Forgery
                        - حماية ضد الهجمات
                        - مطلوبة في كل نموذج POST
                        - تُنشئ token سري خاص بالجلسة
                    -->
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="approve">
                    <!-- 
                        input hidden
                        لماذا؟
                        - لنخبر الـ View أي إجراء نريد
                        - action=approve يعني "اعتماد"
                    -->
                    <button type="submit" class="action-btn btn-approve" 
                            onclick="return confirm('هل أنت متأكد من اعتماد هذا الطلب؟');">
                        <i class="fa fa-check-circle"></i> اعتماد الطلب
                    </button>
                    <!-- 
                        onclick="return confirm(...)"
                        
                        الشرح:
                        - JavaScript للتأكيد
                        - confirm() يعرض نافذة تأكيد
                        - return false يلغي الإرسال إذا ضغط "إلغاء"
                    -->
                </form>
                
                <!-- نموذج الرفض -->
                <button type="button" class="action-btn btn-reject" data-toggle="modal" data-target="#rejectModal">
                    <i class="fa fa-times-circle"></i> رفض الطلب
                </button>
                <!-- 
                    data-toggle="modal"
                    
                    الشرح:
                    - Bootstrap modal
                    - يفتح نافذة منبثقة
                    - لإدخال سبب الرفض
                -->
            </div>
            <?php endif; ?>
            
            <div class="detail-container">
                
                <!-- ========================================
                     معلومات الطلب
                     ======================================== -->
                <div class="detail-section">
                    <h3><i class="fa fa-info-circle"></i> معلومات الطلب</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>نوع الطلب</label>
                            <div class="value">
                                <i class="fa <?= e($application['application_type']['icon']) ?>" 
                                   style="color: <?= e($application['application_type']['color']) ?>;"></i>
                                <?= e($application['application_type']['name']) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <label>رقم الطلب</label>
                            <div class="value"><?= e($application['application_number']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>الحالة</label>
                            <div class="value">
                                <span class="status-badge status-<?= e($application['status']) ?>">
                                    <i class="fa <?= e($application->get_status_icon()) ?>"></i>
                                    <?= e($application->get_status_display()) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <label>تاريخ التقديم</label>
                            <div class="value"><?= e(format_date($application['submitted_at'], 'd/m/Y - H:i')) ?></div>
                        </div>
                        
                        <?php if (!empty($application['reviewed_at'])): ?>
                        <div class="info-item">
                            <label>تاريخ المراجعة</label>
                            <div class="value"><?= e(format_date($application['reviewed_at'], 'd/m/Y - H:i')) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($application['reviewed_by'])): ?>
                        <div class="info-item">
                            <label>راجعه</label>
                            <div class="value">
                                <i class="fa fa-user"></i>
                                <?= e(($application['reviewed_by']->get_full_name() ?: $application['reviewed_by']['username'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ========================================
                     معلومات الطالب - مهم للمشرف!
                     ======================================== -->
                <div class="detail-section" style="background: #f8f9fa;">
                    <h3><i class="fa fa-user"></i> معلومات الطالب</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>الاسم الكامل</label>
                            <div class="value"><?= e($student['full_name_arabic']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>رقم التسجيل</label>
                            <div class="value">
                                <code style="background: white; padding: 5px 10px; border-radius: 3px;">
                                    <?= e($student['registration_number']) ?>
                                </code>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <label>البريد الإلكتروني</label>
                            <div class="value">
                                <a href="mailto:<?= e($student['user']['email']) ?>">
                                    <?= e($student['user']['email']) ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <label>رقم الهاتف</label>
                            <div class="value">
                                <a href="tel:<?= e($student['phone']) ?>">
                                    <i class="fa fa-phone"></i> <?= e($student['phone']) ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <label>الكلية</label>
                            <div class="value"><?= e($student['faculty']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>القسم</label>
                            <div class="value"><?= e($student['department']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>السنة الدراسية</label>
                            <div class="value"><?= e($student->get_current_year_display()) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>الولاية</label>
                            <div class="value"><?= e($student['wilaya']) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- ========================================
                     تفاصيل الطلب
                     ======================================== -->
                <?php if (!empty($application['details'])): ?>
                <div class="detail-section">
                    <h3><i class="fa fa-comment"></i> تفاصيل الطلب</h3>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px; line-height: 1.8; border-inline-start: 3px solid #3498db;">
                        <?= linebreaks($application['details']) ?>
                        <!-- 
                            |linebreaks
                            
                            الشرح:
                            - filter يحول النص إلى <p> و <br>
                            - يحترم الأسطر الجديدة
                        -->
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ========================================
                     الوثائق المرفقة
                     ======================================== -->
                <div class="detail-section">
                    <h3><i class="fa fa-paperclip"></i> الوثائق المرفقة (<?= e(count($documents)) ?>)</h3>
                    
                    <?php if (!empty($documents)): ?>
                    <ul class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                        <li>
                            <div>
                                <i class="fa fa-file-pdf-o" style="color: #e74c3c; font-size: 24px; margin-inline-end: 10px;"></i>
                                <strong><?= e($doc['document_name']) ?></strong>
                                <br>
                                <small style="color: #7f8c8d;">
                                    <i class="fa fa-calendar"></i> <?= e(format_date($doc['uploaded_at'], 'd/m/Y - H:i')) ?>
                                </small>
                            </div>
                            <div>
                                <!-- زر تحميل -->
                                <a href="<?= e($doc->get_download_url()) ?>" 
                                   class="btn btn-sm btn-primary" 
                                   target="_blank"
                                   download>
                                    <i class="fa fa-download"></i> تحميل
                                </a>
                                <!-- زر معاينة -->
                                <a href="<?= e($doc->get_download_url()) ?>" 
                                   class="btn btn-sm btn-info" 
                                   target="_blank">
                                    <i class="fa fa-eye"></i> معاينة
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 40px;">
                        <i class="fa fa-inbox" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                        لا توجد وثائق مرفقة
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- ========================================
                     ملاحظات الإدارة - إن وجدت
                     ======================================== -->
                <?php if (!empty($application['admin_notes'])): ?>
                <div class="detail-section">
                    <h3><i class="fa fa-comment-o"></i> ملاحظات الإدارة</h3>
                    <div class="admin-notes-box">
                        <p style="margin: 0; white-space: pre-line;"><?= e($application['admin_notes']) ?></p>
                        <!-- 
                            white-space: pre-line
                            
                            الشرح:
                            - CSS property
                            - يحترم الأسطر الجديدة في النص
                        -->
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ========================================
                     أزرار تغيير الحالة
                     ======================================== -->
                <div class="detail-section" style="background: #ecf0f1;">
                    <h3><i class="fa fa-cog"></i> تغيير الحالة</h3>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <!-- زر: قيد المراجعة -->
                        <form method="post" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="review">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> قيد المراجعة
                            </button>
                        </form>
                        
                        <!-- زر: قيد الانتظار -->
                        <form method="post" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="pending">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-clock-o"></i> قيد الانتظار
                            </button>
                        </form>
                        
                        <!-- زر: اعتماد -->
                        <?php if ($application['status'] != 'approved'): ?>
                        <form method="post" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success" 
                                    onclick="return confirm('اعتماد هذا الطلب؟');">
                                <i class="fa fa-check"></i> اعتماد
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <!-- زر: رفض -->
                        <?php if ($application['status'] != 'rejected'): ?>
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                            <i class="fa fa-times"></i> رفض
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ========================================
                     أزرار الإجراءات الأخرى
                     ======================================== -->
                <div style="padding: 20px; background: #f8f9fa; text-align: center;">
                    <a href="<?= e(url('students:admin_applications_list')) ?>" class="btn btn-default btn-lg">
                        <i class="fa fa-arrow-right"></i> العودة للقائمة
                    </a>
                    
                    <a href="<?= e(url('students:application_print', $application['id'])) ?>" 
                       class="btn btn-primary btn-lg" 
                       target="_blank">
                        <i class="fa fa-print"></i> طباعة
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     Modal الرفض - نافذة منبثقة
     
     الشرح:
     - Bootstrap Modal
     - يظهر عند الضغط على "رفض"
     - يطلب إدخال سبب الرفض
     ======================================== -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- رأس الـ Modal -->
            <div class="modal-header" style="background: #e74c3c; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-times-circle"></i> رفض الطلب
                </h4>
            </div>
            
            <!-- نموذج الرفض -->
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reject">
                
                <div class="modal-body">
                    <p style="color: #e74c3c; font-weight: bold;">
                        <i class="fa fa-warning"></i> هل أنت متأكد من رفض هذا الطلب؟
                    </p>
                    
                    <div class="form-group">
                        <label>سبب الرفض <span style="color: red;">*</span></label>
                        <textarea name="admin_notes" 
                                  class="form-control" 
                                  rows="5" 
                                  placeholder="اكتب سبب رفض الطلب ليتمكن الطالب من معرفته..."
                                  required></textarea>
                        <!-- 
                            required
                            
                            الشرح:
                            - HTML5 validation
                            - يجبر المستخدم على ملء الحقل
                        -->
                    </div>
                    
                    <div class="alert alert-warning" style="margin-bottom: 0;">
                        <i class="fa fa-info-circle"></i>
                        <strong>ملاحظة:</strong>
                        سيتم إرسال إشعار تلقائي للطالب مع سبب الرفض.
                    </div>
                </div>
                
                <!-- Footer الـ Modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> إلغاء
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-check"></i> تأكيد الرفض
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<br><br>

<?php endblock(); ?>