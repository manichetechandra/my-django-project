<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($news['title']) ?> - مديرية الخدمات الجامعية<?php endblock(); ?>

<?php block('content'); ?>

<div class="container" style="margin-top: 30px;">
    <div class="row">
        <!-- المحتوى الرئيسي -->
        <div class="col-md-8">
            <!-- عنوان الخبر -->
            <div style="border-bottom: 3px solid #8cc040; padding-bottom: 15px; margin-bottom: 20px;">
                <h1 style="color: #2c3e50; margin: 0;"><?= e($news['title']) ?></h1>
            </div>
            
            <!-- معلومات الخبر -->
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <div class="row">
                    <div class="col-sm-6">
                        <i class="fa fa-calendar"></i> 
                        <strong>تاريخ النشر:</strong> 
                        <?= e(format_date($news['published_date'], 'd/m/Y - H:i')) ?>
                    </div>
                    <div class="col-sm-6 text-left">
                        <i class="fa fa-eye"></i> 
                        <strong>المشاهدات:</strong> 
                        <?= e($news['views_count']) ?>
                    </div>
                </div>
                <?php if (!empty($news['author'])): ?>
                <div style="margin-top: 10px;">
                    <i class="fa fa-user"></i> 
                    <strong>الكاتب:</strong> 
                    <?= e($news['author']['username']) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- الصورة الرئيسية -->
            <?php if (!empty($news['main_image'])): ?>
            <div style="margin-bottom: 30px;">
                <img src="<?= e(media_url($news['main_image'])) ?>" alt="<?= e($news['title']) ?>" 
                     style="width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            </div>
            <?php endif; ?>
            
            <!-- ملخص الخبر -->
            <?php if (!empty($news['summary'])): ?>
            <div class="alert alert-info" style="border-inline-start: 4px solid #8cc040;">
                <h4><i class="fa fa-info-circle"></i> ملخص:</h4>
                <p style="font-size: 16px; line-height: 1.8;"><?= e($news['summary']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- محتوى الخبر -->
            <div style="font-size: 17px; line-height: 1.9; color: #333; text-align: justify;">
                <?= linebreaks($news['content']) ?>
            </div>
            
            <!-- الصور الإضافية -->
            <?php if (!empty($news['images'])): ?>
            <div style="margin-top: 40px;">
                <h3 style="color: #2c3e50; border-bottom: 2px solid #8cc040; padding-bottom: 10px;">
                    <i class="fa fa-images"></i> صور إضافية
                </h3>
                <div class="row" style="margin-top: 20px;">
                    <?php foreach ($news['images'] as $image): ?>
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">
                        <div style="border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                            <img src="<?= e(media_url($image['image'])) ?>" alt="<?= e($image['caption']) ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px;">
                            <?php if (!empty($image['caption'])): ?>
                            <p style="text-align: center; margin-top: 10px; color: #666;">
                                <?= e($image['caption']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- أزرار المشاركة -->
            <div style="margin-top: 40px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                <h4><i class="fa fa-share-alt"></i> شارك الخبر:</h4>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(current_url()) ?>" 
                   target="_blank" class="btn btn-primary" style="margin: 5px;">
                    <i class="fa fa-facebook"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= e(current_url()) ?>&text=<?= e($news['title']) ?>" 
                   target="_blank" class="btn btn-info" style="margin: 5px;">
                    <i class="fa fa-twitter"></i> Twitter
                </a>
                <a href="https://wa.me/?text=<?= e($news['title']) ?> <?= e(current_url()) ?>" 
                   target="_blank" class="btn btn-success" style="margin: 5px;">
                    <i class="fa fa-whatsapp"></i> WhatsApp
                </a>
            </div>
            
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            
            <!-- أخبار ذات صلة -->
            <?php if (!empty($related_news)): ?>
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-newspaper-o"></i> أخبار ذات صلة
                    </h3>
                </div>
                <div class="panel-body" style="padding: 0;">
                    <ul class="list-group" style="margin: 0;">
                        <?php foreach ($related_news as $item): ?>
                        <li class="list-group-item">
                            <a href="<?= e(url('news:news_detail', $item['slug'])) ?>" 
                               style="text-decoration: none; color: #2c3e50;">
                                <h5 style="margin: 5px 0;"><?= e($item['title']) ?></h5>
                                <small style="color: #999;">
                                    <i class="fa fa-calendar"></i> 
                                    <?= e(format_date($item['published_date'], 'd/m/Y')) ?>
                                </small>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- زر العودة للأخبار -->
            <a href="<?= e(url('news:news_list')) ?>" class="btn btn-success btn-block btn-lg">
                <i class="fa fa-arrow-right"></i> العودة لكل الأخبار
            </a>
            
        </div>
    </div>
</div>

<?php endblock(); ?>