<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('الأخبار - مديرية الخدمات الجامعية') ?><?php endblock(); ?>

<?php block('content'); ?>

<div class="container" style="margin-top: 30px;">
    
    <!-- العنوان -->
    <div class="row">
        <div class="col-md-12">
            <h2 style="color: #2c3e50; border-bottom: 3px solid #8cc040; padding-bottom: 15px; margin-bottom: 30px;">
                <i class="fa fa-newspaper-o"></i> <?= t('جميع الأخبار') ?>
            </h2>
        </div>
    </div>
    
    <!-- قائمة الأخبار -->
    <div class="row">
        <?php if (count($news) > 0): ?><?php foreach ($news as $item): ?>
        <div class="col-md-4 col-sm-6">
            <div class="card" style="margin-bottom: 30px; height: auto;">
                <!-- الصورة -->
                <div class="inner" style="height: 200px; overflow: hidden;">
                    <?php if (!empty($item['main_image'])): ?>
                        <img src="<?= e(media_url($item['main_image'])) ?>" alt="<?= e($item['title']) ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?= asset('images/default-news.svg') ?>" alt="<?= e($item['title']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                    
                    <!-- Badge للأخبار المميزة -->
                    <?php if (!empty($item['is_featured'])): ?>
                    <span style="position: absolute; top: 10px; inset-inline-end: 10px; background-color: #e74c3c; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px;">
                        <i class="fa fa-star"></i> <?= t('مميز') ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- المحتوى -->
                <div style="padding: 15px;">
                    <h4 style="margin: 10px 0; min-height: 50px;">
                        <a href="<?= e(url('news:news_detail', $item['slug'])) ?>" 
                           style="color: #2c3e50; text-decoration: none;">
                            <?= e($item['title']) ?>
                        </a>
                    </h4>
                    
                    <p style="color: #666; font-size: 14px; min-height: 60px;">
                        <?= e(truncatewords($item['summary'], 15)) ?>
                    </p>
                    
                    <div style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;">
                        <small style="color: #999;">
                            <i class="fa fa-calendar"></i> <?= e(format_date($item['published_date'], 'd/m/Y')) ?>
                            &nbsp;&nbsp;
                            <i class="fa fa-eye"></i> <?= e($item['views_count']) ?>
                        </small>
                    </div>
                    
                    <a href="<?= e(url('news:news_detail', $item['slug'])) ?>" 
                       class="btn btn-success btn-sm" 
                       style="margin-top: 10px;">
                        <i class="fa fa-arrow-left"></i> <?= t('قراءة المزيد') ?>
                    </a>
                </div>
            </div>
        </div>
        
        <?php endforeach; ?><?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <h4><i class="fa fa-info-circle"></i> <?= t('لا توجد أخبار منشورة حالياً') ?></h4>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (!empty($news->has_other_pages())): ?>
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="Page navigation" style="text-align: center;">
                <ul class="pagination">
                    <?php if (!empty($news->has_previous())): ?>
                    <li>
                        <a href="?page=<?= e($news->previous_page_number()) ?>" aria-label="Previous">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php foreach ($news['paginator']['page_range'] as $num): ?>
                    <li <?php if ($news['number'] == $num): ?>class="active"<?php endif; ?>>
                        <a href="?page=<?= e($num) ?>"><?= e($num) ?></a>
                    </li>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($news->has_next())): ?>
                    <li>
                        <a href="?page=<?= e($news->next_page_number()) ?>" aria-label="Next">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<?php endblock(); ?>