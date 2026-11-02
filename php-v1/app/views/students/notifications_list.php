<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .notifications-header {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .filter-tabs {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .filter-tabs .btn-group {
        width: 100%;
    }
    
    .filter-tabs .btn {
        flex: 1;
    }
    
    .notification-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        border-right: 4px solid;
        transition: all 0.3s;
        position: relative;
    }
    
    .notification-item:hover {
        transform: translateX(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .notification-item.unread {
        background: #f8f9fa;
        border-right-color: #3498db;
    }
    
    .notification-item.read {
        border-right-color: #95a5a6;
        opacity: 0.8;
    }
    
    .notification-icon {
        font-size: 32px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(52, 152, 219, 0.1);
    }
    
    .notification-content {
        flex: 1;
        margin-right: 20px;
    }
    
    .notification-title {
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .notification-message {
        color: #7f8c8d;
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .notification-time {
        font-size: 12px;
        color: #95a5a6;
    }
    
    .notification-actions {
        display: flex;
        gap: 5px;
    }
    
    .unread-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #3498db;
        color: white;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
    }
    
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
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="notifications-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-bell"></i> إشعاراتي
        </h1>
        <p style="margin-top: 10px; opacity: 0.9;">
            تتبع كل الإشعارات والتحديثات الخاصة بك
        </p>
    </div>
</div>

<div class="container">
    
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('students:dashboard')) ?>">لوحة التحكم</a></li>
        <li class="active">إشعاراتي</li>
    </ol>
    
    <!-- ========================================
         الفلاتر والإحصائيات
         ======================================== -->
    <div class="filter-tabs">
        <div class="row">
            <div class="col-md-8">
                <div class="btn-group" role="group">
                    <a href="<?= e(url('students:notifications_list')) ?>" 
                       class="btn <?php if ($filter_type == 'all'): ?>btn-primary<?php else: ?>btn-default<?php endif; ?>">
                        <i class="fa fa-list"></i> الكل (<?= e($total_count) ?>)
                    </a>
                    <a href="<?= e(url('students:notifications_list')) ?>?filter=unread" 
                       class="btn <?php if ($filter_type == 'unread'): ?>btn-warning<?php else: ?>btn-default<?php endif; ?>">
                        <i class="fa fa-envelope"></i> غير مقروء (<?= e($unread_count) ?>)
                    </a>
                    <a href="<?= e(url('students:notifications_list')) ?>?filter=read" 
                       class="btn <?php if ($filter_type == 'read'): ?>btn-success<?php else: ?>btn-default<?php endif; ?>">
                        <i class="fa fa-envelope-open"></i> مقروء (<?= e($read_count) ?>)
                    </a>
                </div>
            </div>
            <div class="col-md-4 text-left">
                <?php if ($unread_count > 0): ?>
                <button type="button" class="btn btn-info" id="markAllReadBtn">
                    <i class="fa fa-check-double"></i> تمييز الكل كمقروء
                </button>
                <?php endif; ?>
                
                <?php if ($read_count > 0): ?>
                <button type="button" class="btn btn-danger" id="deleteReadBtn">
                    <i class="fa fa-trash"></i> حذف المقروءة
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         قائمة الإشعارات
         ======================================== -->
    <?php if (!empty($notifications)): ?>
    <div id="notificationsList">
        <?php foreach ($notifications as $notification): ?>
        <div class="notification-item <?php if (empty($notification['is_read'])): ?>unread<?php else: ?>read<?php endif; ?>" 
             data-id="<?= e($notification['id']) ?>">
            
            <?php if (empty($notification['is_read'])): ?>
            <span class="unread-badge">جديد</span>
            <?php endif; ?>
            
            <div style="display: flex; align-items: flex-start;">
                <div class="notification-icon" style="color: <?= e($notification->get_color()) ?>;">
                    <i class="fa <?= e($notification->get_icon()) ?>"></i>
                </div>
                
                <div class="notification-content">
                    <div class="notification-title"><?= e($notification['title']) ?></div>
                    <div class="notification-message"><?= e($notification['message']) ?></div>
                    <div class="notification-time">
                        <i class="fa fa-clock-o"></i>
                        <?= e(format_date($notification['created_at'], 'd/m/Y H:i')) ?>
                        <?php if (!empty($notification['is_read']) && !empty($notification['read_at'])): ?>
                        - قُرئ في <?= e(format_date($notification['read_at'], 'd/m/Y H:i')) ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="notification-actions">
                    <?php if (!empty($notification['link'])): ?>
                    <a href="<?= e(link_url($notification['link'])) ?>" class="btn btn-sm btn-primary" title="عرض التفاصيل">
                        <i class="fa fa-eye"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (empty($notification['is_read'])): ?>
                    <button type="button" 
                            class="btn btn-sm btn-success mark-read-btn" 
                            data-id="<?= e($notification['id']) ?>"
                            title="تمييز كمقروء">
                        <i class="fa fa-check"></i>
                    </button>
                    <?php endif; ?>
                    
                    <button type="button" 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="<?= e($notification['id']) ?>"
                            title="حذف">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fa fa-bell-slash"></i>
        <h3>لا توجد إشعارات</h3>
        <p><?php if ($filter_type == 'unread'): ?>لا توجد إشعارات غير مقروءة<?php elseif ($filter_type == 'read'): ?>لا توجد إشعارات مقروءة<?php else: ?>لم تستلم أي إشعارات بعد<?php endif; ?></p>
        <a href="<?= e(url('students:dashboard')) ?>" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fa fa-arrow-right"></i> العودة للوحة التحكم
        </a>
    </div>
    <?php endif; ?>
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
// ========================================
// JavaScript للإشعارات
// ========================================

// رمز CSRF وروابط الإجراءات (تعمل حتى لو كان الموقع داخل مجلد فرعي)
const csrftoken = <?= json_js(csrf_token()) ?>;
const NOTIF_BASE = <?= json_js(base_url() . route_prefix() . '/students/notifications/') ?>;

// ========================================
// تمييز إشعار كمقروء
// ========================================
document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const notificationId = this.getAttribute('data-id');
        
        fetch(`${NOTIF_BASE}${notificationId}/read/`, {
            method: 'POST',
            headers: {
                'X-CSRFToken': csrftoken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // إعادة تحميل الصفحة
                location.reload();
            } else {
                alert('حدث خطأ: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    });
});

// ========================================
// تمييز الكل كمقروء
// ========================================
const markAllReadBtn = document.getElementById('markAllReadBtn');
if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', function() {
        if (!confirm('هل تريد تمييز كل الإشعارات كمقروءة؟')) {
            return;
        }
        
        fetch(NOTIF_BASE + 'mark-all-read/', {
            method: 'POST',
            headers: {
                'X-CSRFToken': csrftoken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('حدث خطأ: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    });
}

// ========================================
// حذف إشعار
// ========================================
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('هل تريد حذف هذا الإشعار؟')) {
            return;
        }
        
        const notificationId = this.getAttribute('data-id');
        
        fetch(`${NOTIF_BASE}${notificationId}/delete/`, {
            method: 'POST',
            headers: {
                'X-CSRFToken': csrftoken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // إخفاء الإشعار بـ animation
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                item.style.opacity = '0';
                setTimeout(() => {
                    item.remove();
                    // إعادة تحميل إذا لم يبق إشعارات
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        location.reload();
                    }
                }, 300);
            } else {
                alert('حدث خطأ: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    });
});

// ========================================
// حذف كل المقروءة
// ========================================
const deleteReadBtn = document.getElementById('deleteReadBtn');
if (deleteReadBtn) {
    deleteReadBtn.addEventListener('click', function() {
        if (!confirm('هل تريد حذف كل الإشعارات المقروءة؟')) {
            return;
        }
        
        fetch(NOTIF_BASE + 'delete-read/', {
            method: 'POST',
            headers: {
                'X-CSRFToken': csrftoken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('حدث خطأ: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    });
}
</script>
<?php endblock(); ?>