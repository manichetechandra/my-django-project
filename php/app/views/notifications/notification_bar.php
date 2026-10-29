<?php /* شريط الإشعارات العامة (مثل {% show_notifications %}) — متغيرات خاصة به حتى لا تتعارض مع الصفحة */ ?>
<?php $site_notifications = Notification::valid(); ?>
<?php if (!empty($site_notifications)): ?>
<div id="notification-container" style="position: relative; z-index: 9999;">
    <?php foreach ($site_notifications as $site_notification): ?>
    <div class="alert <?= e($site_notification->get_bootstrap_class()) ?> notification-alert" 
         role="alert" 
         style="margin: 0; border-radius: 0; border: none; padding: 15px; text-align: center; position: relative;">
        
        <!-- أيقونة -->
        <i class="fa <?= e($site_notification->get_icon()) ?>" style="font-size: 18px; margin-inline-end: 10px;"></i>
        
        <!-- العنوان -->
        <strong><?= e($site_notification['title']) ?>:</strong>
        
        <!-- الرسالة -->
        <?= e($site_notification['message']) ?>
        
        <!-- الرابط (إذا وُجد) -->
        <?php if (!empty($site_notification['link'])): ?>
        <a href="<?= e(link_url($site_notification['link'])) ?>" 
           style="color: inherit; text-decoration: underline; margin-inline-start: 10px; font-weight: bold;">
            <?= e($site_notification['link_text']) ?>
            <i class="fa fa-arrow-left"></i>
        </a>
        <?php endif; ?>
        
        <!-- زر الإغلاق -->
        <?php if (!empty($site_notification['is_dismissible'])): ?>
        <button type="button" 
                class="close" 
                data-dismiss="alert" 
                aria-label="<?= t('إغلاق') ?>"
                style="position: absolute; inset-inline-end: 15px; top: 12px; font-size: 24px; opacity: 0.8;">
            <span aria-hidden="true">&times;</span>
        </button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<style>
    .notification-alert {
        animation: slideDown 0.5s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .notification-alert .close:hover {
        opacity: 1;
    }
</style>
<?php endif; ?>