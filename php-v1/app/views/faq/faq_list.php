<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .faq-header {
        background: linear-gradient(135deg, #16a085 0%, #3498db 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .stats-box {
        background: white;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .stats-box h3 {
        font-size: 42px;
        margin: 0;
        color: #16a085;
        font-weight: bold;
    }
    
    .stats-box p {
        margin: 10px 0 0 0;
        color: #666;
    }
    
    .stats-box i {
        font-size: 48px;
        color: #16a085;
        margin-bottom: 15px;
    }
    
    /* البحث */
    .search-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .search-box input {
        border: 2px solid #ddd;
        border-radius: 5px;
        padding: 12px;
        font-size: 16px;
    }
    
    .search-box input:focus {
        border-color: #16a085;
        box-shadow: 0 0 0 0.2rem rgba(22, 160, 133, 0.25);
    }
    
    /* بطاقات التصنيف */
    .category-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .category-header {
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        margin-bottom: 20px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .category-header:hover {
        transform: translateX(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .category-header h3 {
        margin: 0;
        font-size: 24px;
    }
    
    .category-header .badge {
        background: rgba(255,255,255,0.3);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 16px;
        float: left;
        margin-top: 5px;
    }
    
    /* الأسئلة - Accordion */
    .faq-item {
        border-bottom: 1px solid #eee;
        padding: 15px 0;
    }
    
    .faq-item:last-child {
        border-bottom: none;
    }
    
    .faq-question {
        cursor: pointer;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        transition: all 0.3s;
        position: relative;
        padding-left: 50px;
    }
    
    .faq-question:hover {
        background: #e9ecef;
    }
    
    .faq-question h4 {
        margin: 0;
        color: #2c3e50;
        font-size: 18px;
    }
    
    .faq-question .toggle-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #16a085;
        transition: transform 0.3s;
    }
    
    .faq-question.active .toggle-icon {
        transform: translateY(-50%) rotate(180deg);
    }
    
    .faq-answer {
        display: none;
        padding: 20px;
        background: white;
        border-right: 3px solid #16a085;
        margin-top: 10px;
        border-radius: 5px;
        line-height: 1.8;
    }
    
    .faq-answer.active {
        display: block;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .faq-meta {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .faq-meta span {
        color: #999;
        font-size: 14px;
        margin-left: 15px;
    }
    
    /* الأكثر مشاهدة */
    .popular-faq {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .popular-faq h4 {
        color: #2c3e50;
        margin-top: 0;
        border-bottom: 3px solid #16a085;
        padding-bottom: 10px;
    }
    
    .popular-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .popular-item:last-child {
        border-bottom: none;
    }
    
    .popular-item a {
        color: #2c3e50;
        text-decoration: none;
        display: flex;
        align-items: center;
    }
    
    .popular-item a:hover {
        color: #16a085;
    }
    
    .popular-item .number {
        background: #16a085;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 10px;
        font-weight: bold;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="faq-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-question-circle"></i> الأسئلة الشائعة
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            إجابات سريعة على أكثر الأسئلة تكراراً
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li class="active">الأسئلة الشائعة</li>
    </ol>
    
    <!-- الإحصائيات -->
    <div class="row">
        <div class="col-md-6">
            <div class="stats-box">
                <i class="fa fa-question"></i>
                <h3><?= e($total_faqs) ?></h3>
                <p>سؤال وجواب</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stats-box">
                <i class="fa fa-folder-open"></i>
                <h3><?= e($total_categories) ?></h3>
                <p>تصنيف</p>
            </div>
        </div>
    </div>
    
    <!-- البحث -->
    <div class="search-box">
        <form method="get" action="<?= e(url('faq:faq_list')) ?>">
            <div class="row">
                <div class="col-md-10">
                    <input type="text" 
                           name="q" 
                           class="form-control" 
                           placeholder="ابحث في الأسئلة... (مثال: كيف أحصل على المنحة؟)"
                           value="<?= e(($search_query ?: '')) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-block" style="background: #16a085; color: white; padding: 12px;">
                        <i class="fa fa-search"></i> بحث
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="row">
        <!-- الأسئلة حسب التصنيف -->
        <div class="col-md-8">
            <?php if (!empty($faqs_by_category)): ?>
                <?php foreach ($faqs_by_category as [$category, $faqs]): ?>
                <div class="category-section">
                    <div class="category-header" style="background-color: <?= e($category->get_color()) ?>;">
                        <i class="fa <?= e($category['icon']) ?>" style="font-size: 28px; margin-left: 15px;"></i>
                        <h3 style="display: inline;"><?= e($category['name']) ?></h3>
                        <span class="badge"><?= e(count($faqs)) ?> سؤال</span>
                    </div>
                    
                    <?php foreach ($faqs as $faq): ?>
                    <div class="faq-item" data-faq-id="<?= e($faq['id']) ?>">
    <div class="faq-question" onclick="toggleFAQ(this)">
                            <h4><?= e($faq['question']) ?></h4>
                            <i class="fa fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p><?= linebreaks($faq['answer']) ?></p>
                            <div class="faq-meta">
                                <span>
                                    <i class="fa fa-eye"></i> <?= e($faq['views_count']) ?> مشاهدة
                                </span>
                                <span>
                                    <i class="fa fa-calendar"></i> آخر تحديث: <?= e(format_date($faq['updated_at'], 'd/m/Y')) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center" style="padding: 60px;">
                    <i class="fa fa-info-circle" style="font-size: 48px; display: block; margin-bottom: 20px;"></i>
                    <h3>لا توجد أسئلة <?php if (!empty($search_query)): ?>تطابق بحثك<?php endif; ?></h3>
                    <?php if (!empty($search_query)): ?>
                    <p>حاول البحث بكلمات أخرى</p>
                    <a href="<?= e(url('faq:faq_list')) ?>" class="btn btn-primary">عرض كل الأسئلة</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-md-4">
            <!-- الأكثر مشاهدة -->
            <?php if (!empty($popular_faqs)): ?>
            <div class="popular-faq">
                <h4><i class="fa fa-fire"></i> الأكثر مشاهدة</h4>
                <?php $__list = array_values($popular_faqs); $__n = count($__list); foreach ($__list as $__i => $faq): ?>
<div class="popular-item">
    <a href="#faq-<?= e($faq['id']) ?>" onclick="scrollToFAQ(<?= e($faq['id']) ?>); return false;">
        <span class="number"><?= e(($__i + 1)) ?></span>
        <span><?= e(truncatewords($faq['question'], 10)) ?></span>
    </a>
</div>
<?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- روابط مفيدة -->
            <div class="popular-faq">
                <h4><i class="fa fa-link"></i> روابط مفيدة</h4>
                <div class="popular-item">
                    <a href="<?= e(url('contact:contact')) ?>">
                        <i class="fa fa-envelope" style="margin-left: 10px; color: #16a085;"></i>
                        اتصل بنا
                    </a>
                </div>
                <div class="popular-item">
                    <a href="<?= e(url('downloads:download_center')) ?>">
                        <i class="fa fa-download" style="margin-left: 10px; color: #16a085;"></i>
                        مركز التحميلات
                    </a>
                </div>
                <div class="popular-item">
                    <a href="<?= e(url('services:service_list')) ?>">
                        <i class="fa fa-cogs" style="margin-left: 10px; color: #16a085;"></i>
                        الخدمات الجامعية
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
    function toggleFAQ(element) {
        // تبديل السؤال الحالي فقط
        element.classList.toggle('active');
        element.nextElementSibling.classList.toggle('active');
    }
    
    function scrollToFAQ(faqId) {
    // البحث عن السؤال الصحيح باستخدام data-faq-id
    var targetItem = document.querySelector('.faq-item[data-faq-id="' + faqId + '"]');
    
    if (targetItem) {
        var question = targetItem.querySelector('.faq-question');
        
        if (question) {
            // فتح السؤال إذا كان مغلقاً
            if (!question.classList.contains('active')) {
                question.click();
            }
            
            // التمرير إليه
            setTimeout(function() {
                question.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }, 100);
        }
    }
}

</script>
<?php endblock(); ?>