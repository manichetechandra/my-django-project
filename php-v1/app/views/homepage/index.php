<?php extend('base/base'); ?>
<?php block('title'); ?>الصفحة الرئيسية - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    /* السلايدر */
    .carousel-inner > .item > img {
        width: 100%;
        height: 500px;
        object-fit: cover;
    }
    
    /* شريط الأخبار */
    #list-news {
        background-color: #f8f9fa;
        padding: 10px;
        margin: 20px 0;
        border-radius: 5px;
        list-style: none;
        overflow: hidden;
    }
    
    #list-news li {
        display: inline-block;
        margin: 0 15px;
    }
    
    #list-news a {
        color: #2c3e50;
        font-weight: bold;
        text-decoration: none;
    }
    
    #list-news a:hover {
        color: #8cc040;
    }
    
    .blink_me {
        animation: blinker 1.5s linear infinite;
    }
    
    @keyframes blinker {
        50% { opacity: 0.5; }
    }
    
    /* صناديق الأخبار */
    .demo {
        list-style: none;
        padding: 0;
    }
    
    .news-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.3s;
    }
    
    .news-item:hover {
        background-color: #f8f9fa;
    }
    
    .news-item img {
        border-radius: 50%;
    }
    
    /* بطاقات النشاطات - RTL FIX */
    .card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: transform 0.3s;
        height: 100%;
        direction: rtl;
        text-align: right;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .card .inner {
        position: relative;
        overflow: hidden;
        height: 200px;
    }
    
    .card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .card h4 {
        padding: 15px;
        margin: 0;
        background-color: #2c3e50;
        color: white;
        text-align: right;
        direction: rtl;
    }
    
    .card h4 a {
        color: white;
        text-decoration: none;
    }
    
    .card h4 a:hover {
        color: #8cc040;
    }
    
    .card p {
        padding: 15px;
        color: #666;
        text-align: right;
        direction: rtl;
    }
    
    .card small {
        text-align: right;
        direction: rtl;
    }
    
    .card .btn {
        float: right;
        margin-right: 0;
        margin-left: 5px;
    }
    
    /* إصلاح الأعمدة RTL */
    .activities-row .col-md-4 {
        float: right !important;
    }
    
    /* بطاقات الأقسام */
    .panel-default {
        margin-bottom: 20px;
        text-align: right;
    }
    
    .outer {
        height: 100%;
    }
    
    /* السلايدر الثاني للإقامات */
    .thumbnails {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .thumbnails li {
        padding: 10px;
        float: right;
    }
    
    .thumbnail {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 5px;
    }
    
    .thumbnail img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 5px;
    }
    
    .caption-box {
        padding: 10px;
        text-align: center;
    }
    
    .caption-box h4 {
        color: #2c3e50;
        font-size: 16px;
        margin: 10px 0;
    }
    
    .caption-box p {
        font-size: 13px;
        color: #666;
    }
    /* تصغير أسهم سلايدر الإقامات */
.media-carousel .carousel-control {
    width: 30px;
    height: 50px;
    background: rgba(44, 62, 80, 0.6);
    border-radius: 5px;
    opacity: 0.8;
    z-index: 10;
    top: 50%;  /* ← في الوسط */
    transform: translateY(-50%);  /* ← محاذاة مثالية */
}

.media-carousel .carousel-control:hover {
    background: rgba(44, 62, 80, 0.9);
    opacity: 1;
}

.media-carousel .carousel-control .glyphicon {
    font-size: 16px;
}

.media-carousel .carousel-control.left {
    right: 5px;
    left: auto;
}

.media-carousel .carousel-control.right {
    left: 5px;
    right: auto;
}
</style>
<?php endblock(); ?>
<?php block('content'); ?>

<!-- السلايدر الرئيسي -->
<div id="myCarousel" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <?php $__list = array_values($slider_images); $__n = count($__list); foreach ($__list as $__i => $slide): ?>
            <li data-target="#myCarousel" data-slide-to="<?= e($__i) ?>" <?php if (($__i === 0)): ?>class="active"<?php endif; ?>></li>
        <?php endforeach; ?>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">
        <?php if (!empty($slider_images)): ?><?php $__list = array_values($slider_images); $__n = count($__list); foreach ($__list as $__i => $slide): ?>
            <div class="item <?php if (($__i === 0)): ?>active<?php endif; ?>">
                <?php if (!empty($slide['link'])): ?>
                    <a href="<?= e(link_url($slide['link'])) ?>" <?php if (in_('http', $slide['link'])): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
                        <img src="<?= e(media_url($slide['image'])) ?>" alt="<?= e($slide['title']) ?>">
                    </a>
                <?php else: ?>
                    <img src="<?= e(media_url($slide['image'])) ?>" alt="<?= e($slide['title']) ?>">
                <?php endif; ?>
                
                <?php if (!empty($slide['title']) || !empty($slide['subtitle'])): ?>
                <div class="carousel-caption">
                    <?php if (!empty($slide['title'])): ?>
                        <h3><?= e($slide['title']) ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($slide['subtitle'])): ?>
                        <p><?= e($slide['subtitle']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($slide['button_text']) && !empty($slide['link'])): ?>
                        <a href="<?= e(link_url($slide['link'])) ?>" class="btn btn-success"><?= e($slide['button_text']) ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?><?php else: ?>
            <div class="item active">
                <img src="<?= asset('images/default-slide.svg') ?>" alt="مرحباً بكم" style="width: 100%; height: 500px; object-fit: cover; background-color: #2c3e50;">
                <div class="carousel-caption">
                    <h3>مرحباً بكم في مديرية الخدمات الجامعية</h3>
                    <p>في خدمتكم دائماً</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Controls -->
    <a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#myCarousel" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>

<!-- شريط الأخبار المتحرك -->
<div class="container">
    <div class="row">
        <div class="col-lg-1"></div>
        <div class="col-lg-11">
            <ul id="list-news">
                <?php if (!empty($ticker_news)): ?><?php $__list = array_values($ticker_news); $__n = count($__list); foreach ($__list as $__i => $news): ?>
                    <li class="blink_me">
                        <a href="<?= e(url('news:news_detail', $news['slug'])) ?>" title="<?= e($news['title']) ?>">
                            <i class="fa fa-newspaper-o"></i> <?= e($news['title']) ?>
                        </a>
                    </li>
                    <?php if (!($__i === $__n - 1)): ?>
                        <span id="separation" style="line-height: 35px;"> | </span>
                    <?php endif; ?>
                <?php endforeach; ?><?php else: ?>
                    <li>
                        <a href="<?= e(url('news:news_list')) ?>" style="font-size:21px;">
                            <i class="fa fa-newspaper-o"></i> آخر المستجدات
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- عنوان آخر الأخبار -->
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h3 style="color: #2c3e50; border-bottom: 3px solid #8cc040; padding-bottom: 10px;">
                <i class="fa fa-newspaper-o"></i> آخر الأخبـــار
                <a href="<?= e(url('news:news_list')) ?>" class="btn btn-sm btn-success" style="float: left; margin-top: 5px;">
                    <i class="fa fa-arrow-left"></i> عرض الكل
                </a>
            </h3>
        </div>
    </div>
</div>
<br>

<!-- صندوق آخر الأخبار -->
<div class="container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-list-alt"></span>
            <b>أخر الأخبار</b>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="demo" id="latest-news-ticker">
                        <?php if (!empty($latest_news)): ?><?php foreach ($latest_news as $news_item): ?>
                            <li class="news-item">
                                <table cellpadding="4" style="width: 100%;">
                                    <tr>
                                        <td style="width: 80px;">
                                            <?php if (!empty($news_item['main_image'])): ?>
                                                <img src="<?= e(media_url($news_item['main_image'])) ?>" width="60" class="img-circle" alt="<?= e(truncatewords($news_item['title'], 5)) ?>"/>
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #8cc040; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-newspaper-o" style="font-size: 24px; color: white;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= e(url('news:news_detail', $news_item['slug'])) ?>" style="text-decoration: none; color: #2c3e50;">
                                                <h4 style="margin: 0;"><?= e($news_item['title']) ?></h4>
                                                <small style="color: #999;">
                                                    <i class="fa fa-calendar"></i> <?= e(format_date($news_item['published_date'], 'd/m/Y')) ?>
                                                    &nbsp;&nbsp;
                                                    <i class="fa fa-eye"></i> <?= e($news_item['views_count']) ?>
                                                </small>
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </li>
                        <?php endforeach; ?><?php else: ?>
                            <li class="news-item text-center">
                                <p class="text-muted">
                                    <i class="fa fa-info-circle"></i> لا توجد أخبار حالياً
                                </p>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="panel-footer"></div>
    </div>
</div>
<!-- عنوان النشاطات -->
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h3 style="color: #2c3e50; border-bottom: 3px solid #8cc040; padding-bottom: 10px;">
                <i class="fa fa-calendar-check-o"></i> آخر النشاطـــات
                <a href="<?= e(url('activities:activity_list')) ?>" class="btn btn-sm btn-success" style="float: left; margin-top: 5px;">
                    <i class="fa fa-arrow-left"></i> عرض الكل
                </a>
            </h3>
        </div>
    </div>
</div>
<br>
<!-- النشاطات -->
<div class="container">
    <div class="row activities-row">
        <?php if (!empty($featured_activities)): ?><?php foreach ($featured_activities as $activity): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="inner">
                    <?php if (!empty($activity['main_image'])): ?>
                    <img src="<?= e(media_url($activity['main_image'])) ?>" alt="<?= e($activity['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; background: <?= e($activity->get_type_gradient()) ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fa <?= e($activity->get_type_icon()) ?>" style="font-size: 64px; color: white;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <h4>
                    <a href="<?= e($activity->get_absolute_url()) ?>">
                        <?= e($activity['title']) ?>
                    </a>
                </h4>
                <p><?= e(truncatewords($activity['short_description'], 20)) ?></p>
                <div style="padding: 0 15px 15px;">
                    <small style="color: #999;">
                        <i class="fa fa-calendar"></i> <?= e(format_date($activity['date'], 'd/m/Y')) ?>
                        &nbsp;&nbsp;
                        <i class="fa fa-map-marker"></i> <?= e($activity['location']) ?>
                        <br>
                        <i class="fa fa-tag"></i> <?= e($activity->get_activity_type_display()) ?>
                        &nbsp;&nbsp;
                        <i class="fa fa-users"></i> <?= e($activity['participants_count']) ?> مشارك
                    </small>
                    <br><br>
                    <a class="btn btn-success" href="<?= e($activity->get_absolute_url()) ?>" style="margin: 0;">
                        المزيد <i class="fa fa-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?><?php else: ?>
        <div class="col-md-12">
            <div class="alert alert-info text-center">
                <i class="fa fa-info-circle"></i> لا توجد نشاطات حالياً
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<br>
<!-- الإحصائيات الحية -->
<section class="statistics-section" style="background: #f8f9fa; padding: 60px 0; margin: 40px 0;">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-12">
                <h2 style="margin-bottom: 40px; color: #2c3e50;">
                    <i class="fa fa-bar-chart"></i> إحصائيات الموقع
                </h2>
            </div>
        </div>
        
        <div class="row">
            <!-- الزوار -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-users stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_visitors']) ?>">0</h3>
                    <p class="stat-label">زائر</p>
                </div>
            </div>
            
            <!-- المشاهدات -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-eye stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_page_views']) ?>">0</h3>
                    <p class="stat-label">مشاهدة</p>
                </div>
            </div>
            
            <!-- الأخبار -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-newspaper-o stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_news']) ?>">0</h3>
                    <p class="stat-label">خبر</p>
                </div>
            </div>
            
            <!-- الخدمات -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-cogs stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_services']) ?>">0</h3>
                    <p class="stat-label">خدمة</p>
                </div>
            </div>
        </div>
        
        <div class="row" style="margin-top: 30px;">
            <!-- الملفات -->
            <div class="col-md-4 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-download stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_downloads']) ?>">0</h3>
                    <p class="stat-label">ملف للتحميل</p>
                </div>
            </div>
            
            <!-- الأسئلة -->
            <div class="col-md-4 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-question-circle stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_faqs']) ?>">0</h3>
                    <p class="stat-label">سؤال شائع</p>
                </div>
            </div>
            
            <!-- الإقامات -->
            <div class="col-md-4 col-sm-6">
                <div class="stat-box">
                    <i class="fa fa-university stat-icon"></i>
                    <h3 class="stat-number" data-count="<?= e($site_stats['total_cites']) ?>">0</h3>
                    <p class="stat-label">إقامة جامعية</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .stat-box {
        background: white;
        padding: 40px 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    
    .stat-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .stat-icon {
        font-size: 48px;
        color: #3498db;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .stat-box:hover .stat-icon {
        transform: scale(1.2);
        color: #2ecc71;
    }
    
    .stat-number {
        font-size: 42px;
        font-weight: bold;
        color: #2c3e50;
        margin: 15px 0;
    }
    
    .stat-label {
        font-size: 16px;
        color: #7f8c8d;
        margin: 0;
    }
</style>

<script>
    // رسم متحرك للأرقام
    document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000;
            const step = target / (duration / 16);
            
            let current = 0;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    counter.textContent = target.toLocaleString('ar-EG');
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current).toLocaleString('ar-EG');
                }
            }, 16);
        });
    });
</script>

<!-- مواقع مهمة -->
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-1"></div>
            <div class="col-md-10">
                <div class="panel panel-default outer">
                    <div class="panel-heading" style="background-color: #2c3e50; color: white;">
                        <h4 class="text-center" style="font-size:24px; margin: 10px 0;">
                            <i class="fa fa-link"></i> مــواقــع مهمـــــة
                        </h4>
                    </div>
                    <div class="panel-body">
                        <a class="btn btn-md btn-success" target="_blank" rel="noopener noreferrer" 
                           href="https://arabe.univ-setif.dz/" 
                           style="font-size:18px; font-weight:bold; margin: 5px;">
                            <i class="fa fa-university"></i> جامعة فرحات عباس
                        </a>
                    </div>
                    <div class="panel-body">
                        <a class="btn btn-md btn-success" target="_blank" rel="noopener noreferrer" 
                           href="https://www.mesrs.dz/" 
                           style="font-size:18px; font-weight:bold; margin: 5px;">
                            <i class="fa fa-graduation-cap"></i> وزارة التعليم العالي والبحث العلمي
                        </a>
                    </div>
                    <div class="panel-body">
                        <a class="btn btn-md btn-success" target="_blank" rel="noopener noreferrer" 
                           href="https://progres.mesrs.dz/webonou/" 
                           style="font-size:18px; font-weight:bold; margin: 5px;">
                            <i class="fa fa-external-link"></i> منصة PROGRES
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- عنوان أقسام المديرية -->
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h3 style="color: #2c3e50; border-bottom: 3px solid #8cc040; padding-bottom: 10px;">
                <i class="fa fa-sitemap"></i> أقسام المديرية
            </h3>
        </div>
    </div>
</div>
<br>

<!-- أقسام المديرية -->
<div class="container">
    <div class="row">
        <?php if (!empty($departements_list)): ?><?php foreach ($departements_list as $dept): ?>
            <div class="col-md-3">
                <div class="panel panel-default outer">
                    <div class="panel-heading" style="background-color: #2c3e50; color: white;">
                        <h4 class="text-center"><?= e($dept['name']) ?></h4>
                    </div>
                    <div class="panel-body text-justify">
                        <p><?= e(truncatewords($dept['description'], 20)) ?></p>
                        <a class="btn btn-md btn-success" href="<?= e(url('departements:departement_detail', $dept['slug'])) ?>" style="float: right;">
                            المزيد <i class="fa fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?><?php else: ?>
            <div class="col-md-12">
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> لا توجد أقسام معروضة حالياً
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- عنوان الإقامات -->
<div class="cite" id="cite">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 style="color: #2c3e50; border-bottom: 3px solid #8cc040; padding-bottom: 10px;">
                    <i class="fa fa-building"></i> الإقامات الجامعية
                    <a href="<?= e(url('cities:cite_list')) ?>" class="btn btn-sm btn-success" style="float: left; margin-top: 5px;">
                        <i class="fa fa-arrow-left"></i> عرض الكل
                    </a>
                </h3>
            </div>
        </div>
    </div>
</div>
<br>

<!-- سلايدر الإقامات -->
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="carousel slide media-carousel" id="media" data-ride="carousel">
                
                <!-- Wrapper for slides -->
                <div class="carousel-inner">
                    
                    <!-- Slide 1 -->
                    <div class="item active">
                        <ul class="thumbnails row">
                            <?php foreach (slice_($cites_list, ':6') as $cite): ?>
                                <li class="col-sm-2">
                                    <div class="thumbnail">
                                        <?php if (!empty($cite['main_image'])): ?>
                                            <img src="<?= e(media_url($cite['main_image'])) ?>" alt="<?= e($cite['name']) ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 150px; background-color: #34495e; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                <i class="fa fa-building" style="font-size: 48px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- في سلايدر الإقامات -->
<div class="caption-box">
    <h4><?= e($cite['name']) ?></h4>
    <p>
        <?php if (!empty($cite['phone'])): ?>الهاتف: <?= e($cite['phone']) ?><br><?php endif; ?>
        <?php if (!empty($cite['address'])): ?><?= e(truncatewords($cite['address'], 5)) ?><?php endif; ?>
    </p>
    <div style="text-align: center;">
        <a class="btn btn-success btn-sm" href="<?= e(url('cities:cite_detail', $cite['slug'])) ?>">
            المزيد <i class="fa fa-arrow-left"></i>
        </a>
    </div>
</div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Slide 2 (إذا كان هناك أكثر من 6 إقامات) -->
                    <?php if (length($cites_list) > 6): ?>
                    <div class="item">
                        <ul class="thumbnails row">
                            <?php foreach (slice_($cites_list, '6:12') as $cite): ?>
                                <li class="col-sm-2">
                                    <div class="thumbnail">
                                        <?php if (!empty($cite['main_image'])): ?>
                                            <img src="<?= e(media_url($cite['main_image'])) ?>" alt="<?= e($cite['name']) ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 150px; background-color: #34495e; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                <i class="fa fa-building" style="font-size: 48px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="caption-box">
                                        <h4><?= e($cite['name']) ?></h4>
                                        <p>
                                            <?php if (!empty($cite['phone'])): ?>الهاتف: <?= e($cite['phone']) ?><br><?php endif; ?>
                                            <?php if (!empty($cite['address'])): ?><?= e(truncatewords($cite['address'], 5)) ?><?php endif; ?>
                                        </p>
                                        <a class="btn btn-success btn-sm" href="<?= e(url('cities:cite_detail', $cite['slug'])) ?>">
                                            <i class="fa fa-arrow-left"></i> المزيد
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                </div>

                <!-- Controls -->
                <a data-slide="prev" href="#media" class="left carousel-control">
                    <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
                <a data-slide="next" href="#media" class="right carousel-control">
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </div>
        </div>
    </div>
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>

<script>
    $(document).ready(function() {
        // السلايدر الرئيسي
        $('#myCarousel').carousel({
            interval: 5000,
            pause: 'hover'
        });
        
        // سلايدر الإقامات
        $('#media').carousel({
            interval: 4000,
            pause: 'hover'
        });
        
        // News ticker animation
        var newsItems = $('#latest-news-ticker .news-item');
        var currentIndex = 0;
        
        function showNextNews() {
            newsItems.hide();
            newsItems.eq(currentIndex).fadeIn();
            currentIndex = (currentIndex + 1) % newsItems.length;
        }
        
        if(newsItems.length > 0) {
            setInterval(showNextNews, 4000);
            showNextNews();
        }
    });
</script>
<?php endblock(); ?>