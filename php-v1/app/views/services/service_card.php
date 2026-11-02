<div class="service-card">
    <!-- Header ملون حسب النوع -->
    <div class="service-card-header service-<?= e($service['service_type']) ?>">
        <i class="fa <?= e($service['icon']) ?> service-icon"></i>
        <h4 style="margin: 0; color: white; font-size: 22px;">
            <?= e($service->get_service_type_display()) ?>
        </h4>
    </div>
    
    <!-- الصورة (اختياري) -->
    <?php if (!empty($service['main_image'])): ?>
    <div style="height: 200px; overflow: hidden;">
        <img src="<?= e(media_url($service['main_image'])) ?>" 
             alt="<?= e($service['title']) ?>" 
             style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <?php endif; ?>
    
    <!-- المحتوى -->
    <div class="service-card-body">
        <h3><?= e($service['title']) ?></h3>
        <p><?= e(truncatewords($service['short_description'], 30)) ?></p>
        
        <div class="service-meta">
            <small style="color: #999;">
                <i class="fa fa-clock-o"></i> آخر تحديث: <?= e(format_date($service['updated_at'], 'd/m/Y')) ?>
            </small>
            <br><br>
            <a href="<?= e($service->get_absolute_url()) ?>" class="btn btn-success btn-block">
                <i class="fa fa-arrow-left"></i> التفاصيل الكاملة
            </a>
        </div>
    </div>
</div>