<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .search-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .search-box-large {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        margin-top: 30px;
    }
    
    .search-box-large input {
        border: none;
        color: #2c3e50;
        font-size: 18px;
        padding: 15px;
        width: 100%;
    }
    
    .search-box-large input:focus {
        outline: none;
    }
    
    .search-box-large button {
        background: #e74c3c;
        border: none;
        color: white;
        padding: 15px 30px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .search-box-large button:hover {
        background: #c0392b;
    }
    
    /* الإحصائيات */
    .stats-bar {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .stats-bar h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .stats-bar .badge {
        background: #e74c3c;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 16px;
        margin-inline-start: 10px;
    }
    
    .search-keywords {
        background: #ecf0f1;
        padding: 10px 15px;
        border-radius: 5px;
        margin-top: 15px;
        font-size: 14px;
        color: #7f8c8d;
    }
    
    .search-keywords span {
        background: white;
        padding: 5px 12px;
        border-radius: 15px;
        margin: 0 5px;
        border: 1px solid #bdc3c7;
        display: inline-block;
        margin-bottom: 5px;
    }
    
    /* الفلاتر */
    .filter-tabs {
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .filter-btn {
        display: inline-block;
        padding: 10px 20px;
        margin: 5px;
        border: 2px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #2c3e50;
        transition: all 0.3s;
    }
    
    .filter-btn:hover {
        border-color: #e74c3c;
        color: #e74c3c;
        text-decoration: none;
    }
    
    .filter-btn.active {
        background: #e74c3c;
        border-color: #e74c3c;
        color: white;
    }
    
    .filter-btn .count {
        background: rgba(0,0,0,0.1);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        margin-inline-start: 5px;
    }
    
    .filter-btn.active .count {
        background: rgba(255,255,255,0.3);
    }
    
    /* بطاقات النتائج */
    .result-section {
        margin-bottom: 40px;
    }
    
    .result-section h3 {
        color: #2c3e50;
        border-inline-start: 5px solid #e74c3c;
        padding-inline-start: 15px;
        margin-bottom: 20px;
    }
    
    .result-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        transition: all 0.3s;
        border-inline-start: 3px solid transparent;
    }
    
    .result-card:hover {
        border-inline-start-color: #e74c3c;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        transform: translateX(-5px);
    }

    html[dir="ltr"] .result-card:hover {
        transform: translateX(5px);
    }
    
    .result-card .type-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        color: white;
        margin-bottom: 10px;
    }
    
    .result-card h4 {
        margin: 10px 0;
        color: #2c3e50;
    }
    
    .result-card h4 a {
        color: #2c3e50;
        text-decoration: none;
    }
    
    .result-card h4 a:hover {
        color: #e74c3c;
    }
    
    .result-card p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .result-card .meta {
        color: #999;
        font-size: 14px;
    }
    
    .result-card .meta i {
        margin-inline-end: 5px;
        color: #e74c3c;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }
    
    .empty-state i {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        color: #999;
        margin-bottom: 20px;
    }
    
    .empty-state .suggestions {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-top: 30px;
        text-align: start;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="search-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-search"></i> البحث في الموقع
        </h1>
        
        <!-- صندوق البحث -->
        <div class="search-box-large">
            <form method="get" action="<?= e(url('search:search')) ?>">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text" 
                               name="q" 
                               value="<?= e($query) ?>"
                               placeholder="ابحث في الأخبار، الخدمات، الملفات، الأسئلة..."
                               autofocus
                               required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-block">
                            <i class="fa fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li class="active">البحث</li>
    </ol>
    
    <?php if (!empty($query)): ?>
    
    <!-- الإحصائيات -->
    <div class="stats-bar">
        <h3>
            <span class="badge"><?= e($counts['total']) ?></span>
            نتيجة للبحث عن: <strong>"<?= e($query) ?>"</strong>
        </h3>
        
        <?php if (!empty($search_terms)): ?>
        <div class="search-keywords">
            <i class="fa fa-tags"></i> الكلمات المفتاحية:
            <?php foreach ($search_terms as $term): ?>
            <span><?= e($term) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- الفلاتر -->
    <div class="filter-tabs">
        <a href="<?= e(url('search:search')) ?>?q=<?= e($query) ?>" 
           class="filter-btn <?php if ($search_type == 'all'): ?>active<?php endif; ?>">
            <i class="fa fa-th"></i> الكل
            <span class="count"><?= e($counts['total']) ?></span>
        </a>
        
        <a href="<?= e(url('search:search')) ?>?q=<?= e($query) ?>&type=news" 
           class="filter-btn <?php if ($search_type == 'news'): ?>active<?php endif; ?>">
            <i class="fa fa-newspaper-o"></i> أخبار
            <span class="count"><?= e($counts['news']) ?></span>
        </a>
        
        <a href="<?= e(url('search:search')) ?>?q=<?= e($query) ?>&type=services" 
           class="filter-btn <?php if ($search_type == 'services'): ?>active<?php endif; ?>">
            <i class="fa fa-cogs"></i> خدمات
            <span class="count"><?= e($counts['services']) ?></span>
        </a>
        
        <a href="<?= e(url('search:search')) ?>?q=<?= e($query) ?>&type=faqs" 
           class="filter-btn <?php if ($search_type == 'faqs'): ?>active<?php endif; ?>">
            <i class="fa fa-question-circle"></i> أسئلة
            <span class="count"><?= e($counts['faqs']) ?></span>
        </a>
        
        <a href="<?= e(url('search:search')) ?>?q=<?= e($query) ?>&type=downloads" 
           class="filter-btn <?php if ($search_type == 'downloads'): ?>active<?php endif; ?>">
            <i class="fa fa-download"></i> ملفات
            <span class="count"><?= e($counts['downloads']) ?></span>
        </a>
    </div>
    
    <?php if ($counts['total'] > 0): ?>
    
    <!-- 1️⃣ نتائج الأخبار -->
    <?php if (!empty($results['news'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-newspaper-o"></i> الأخبار (<?= e($counts['news']) ?>)</h3>
        <?php foreach ($results['news'] as $news): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #3498db;">
                <i class="fa fa-newspaper-o"></i> خبر
            </span>
            <h4><a href="<?= e(url('news:news_detail', $news['slug'])) ?>"><?= e($news['title']) ?></a></h4>
            <p><?= e(truncatewords($news['summary'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-calendar"></i> <?= e(format_date($news['published_date'], 'd/m/Y')) ?>
                <span style="margin-inline-start: 15px;">
                    <i class="fa fa-eye"></i> <?= e($news['views_count']) ?> مشاهدة
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 2️⃣ نتائج الخدمات -->
    <?php if (!empty($results['services'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-cogs"></i> الخدمات (<?= e($counts['services']) ?>)</h3>
        <?php foreach ($results['services'] as $service): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: <?= e($service->get_type_color()) ?>;">
                <i class="fa <?= e($service['icon']) ?>"></i> <?= e($service->get_service_type_display()) ?>
            </span>
            <h4><a href="<?= e($service->get_absolute_url()) ?>"><?= e($service['title']) ?></a></h4>
            <p><?= e(truncatewords($service['short_description'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-clock-o"></i> آخر تحديث: <?= e(format_date($service['updated_at'], 'd/m/Y')) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 3️⃣ نتائج الأسئلة -->
    <?php if (!empty($results['faqs'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-question-circle"></i> الأسئلة الشائعة (<?= e($counts['faqs']) ?>)</h3>
        <?php foreach ($results['faqs'] as $faq): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: <?= e($faq['category']->get_color()) ?>;">
                <i class="fa <?= e($faq['category']['icon']) ?>"></i> <?= e($faq['category']['name']) ?>
            </span>
            <h4><a href="<?= e(url('faq:faq_detail', $faq['id'])) ?>"><?= e($faq['question']) ?></a></h4>
            <p><?= e(truncatewords($faq['answer'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-eye"></i> <?= e($faq['views_count']) ?> مشاهدة
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 4️⃣ نتائج الملفات -->
    <?php if (!empty($results['downloads'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-download"></i> ملفات التحميل (<?= e($counts['downloads']) ?>)</h3>
        <?php foreach ($results['downloads'] as $file): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: <?= e($file->get_file_color()) ?>;">
                <i class="fa <?= e($file->get_file_icon()) ?>"></i> <?= e(slice_(mb_strtoupper((string)$file->get_file_extension()), '1:')) ?>
            </span>
            <h4><a href="<?= e(url('downloads:download_file', $file['slug'])) ?>"><?= e($file['title']) ?></a></h4>
            <p><?= e(truncatewords($file['description'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-hdd-o"></i> <?= e($file->get_file_size_display()) ?>
                <span style="margin-inline-start: 15px;">
                    <i class="fa fa-download"></i> <?= e($file['downloads_count']) ?> تحميل
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 5️⃣ نتائج النشاطات -->
    <?php if (!empty($results['activities'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-calendar"></i> النشاطات (<?= e($counts['activities']) ?>)</h3>
        <?php foreach ($results['activities'] as $activity): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: <?= e($activity->get_type_color()) ?>;">
                <i class="fa <?= e($activity->get_type_icon()) ?>"></i> <?= e($activity->get_activity_type_display()) ?>
            </span>
            <h4><a href="<?= e($activity->get_absolute_url()) ?>"><?= e($activity['title']) ?></a></h4>
            <p><?= e(truncatewords($activity['short_description'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-calendar"></i> <?= e(format_date($activity['date'], 'd/m/Y')) ?>
                <span style="margin-inline-start: 15px;">
                    <i class="fa fa-map-marker"></i> <?= e($activity['location']) ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 6️⃣ نتائج كلمة المدير ← المهم! كان ناقص -->
    <?php if (!empty($results['director_words'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-quote-left"></i> كلمة المدير (<?= e($counts['director_words']) ?>)</h3>
        <?php foreach ($results['director_words'] as $word): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #c0392b;">
                <i class="fa fa-quote-left"></i> كلمة
            </span>
            <h4><a href="<?= e(url('homepage:director_word')) ?>"><?= e($word['word_title']) ?></a></h4>
            <p><?= e(truncatewords($word['word_content'], 30)) ?></p>
            <div class="meta">
                <i class="fa fa-user"></i> <?= e($word['director_name']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 7️⃣ نتائج التاريخ ← المهم! كان ناقص -->
    <?php if (!empty($results['history'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-history"></i> تاريخ المديرية (<?= e($counts['history']) ?>)</h3>
        <?php foreach ($results['history'] as $hist): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #16a085;">
                <i class="fa fa-history"></i> تاريخ
            </span>
            <h4><a href="<?= e(url('homepage:history')) ?>"><?= e($hist['title']) ?></a></h4>
            <p><?= e(truncatewords($hist['introduction'], 30)) ?></p>
            <div class="meta">
                <?php if (!empty($hist['founding_year'])): ?>
                    <i class="fa fa-calendar"></i> تأسست عام <?= e($hist['founding_year']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 8️⃣ نتائج الهيكل -->
    <?php if (!empty($results['organization'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-sitemap"></i> الهيكل التنظيمي (<?= e($counts['organization']) ?>)</h3>
        <?php foreach ($results['organization'] as $org): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #d35400;">
                <i class="fa fa-sitemap"></i> هيكل
            </span>
            <h4><a href="<?= e(url('homepage:organization')) ?>"><?= e($org['title']) ?></a></h4>
            <p><?= e(truncatewords($org['description'], 30)) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 9️⃣ نتائج الإقامات -->
    <?php if (!empty($results['cites'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-university"></i> الإقامات الجامعية (<?= e($counts['cites']) ?>)</h3>
        <?php foreach ($results['cites'] as $cite): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #34495e;">
                <i class="fa fa-university"></i> إقامة
            </span>
            <h4><a href="<?= e(url('cities:cite_detail', $cite['slug'])) ?>"><?= e($cite['name']) ?></a></h4>
            <p><?= e((truncatewords($cite['description'], 30) ?: 'إقامة جامعية')) ?></p>
            <div class="meta">
                <?php if (!empty($cite['address'])): ?>
                    <i class="fa fa-map-marker"></i> <?= e($cite['address']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 🔟 نتائج الأقسام -->
    <?php if (!empty($results['departements'])): ?>
    <div class="result-section">
        <h3><i class="fa fa-building"></i> الأقسام (<?= e($counts['departements']) ?>)</h3>
        <?php foreach ($results['departements'] as $dept): ?>
        <div class="result-card">
            <span class="type-badge" style="background-color: #9b59b6;">
                <i class="fa fa-building"></i> قسم
            </span>
            <h4><a href="<?= e(url('departements:departement_detail', $dept['slug'])) ?>"><?= e($dept['name']) ?></a></h4>
            <p><?= e(truncatewords($dept['description'], 30)) ?></p>
            <div class="meta">
                <?php if (!empty($dept['phone'])): ?>
                    <i class="fa fa-phone"></i> <?= e($dept['phone']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    
    <!-- لا توجد نتائج -->
    <div class="empty-state">
        <i class="fa fa-search"></i>
        <h3>لم نجد أي نتائج لـ "<?= e($query) ?>"</h3>
        <p style="color: #666;">جرّب البحث بكلمات أخرى</p>
        
        <div class="suggestions">
            <h4 style="color: #2c3e50; margin-top: 0;">اقتراحات:</h4>
            <ul style="text-align: start; line-height: 2;">
                <li>تحقق من الإملاء</li>
                <li>استخدم كلمات أقل</li>
                <li>جرب كلمات مرادفة</li>
            </ul>
            
            <h5 style="color: #2c3e50;">روابط سريعة:</h5>
            <a href="<?= e(url('news:news_list')) ?>" class="btn btn-primary">الأخبار</a>
            <a href="<?= e(url('services:service_list')) ?>" class="btn btn-primary">الخدمات</a>
            <a href="<?= e(url('downloads:download_center')) ?>" class="btn btn-primary">التحميلات</a>
            <a href="<?= e(url('faq:faq_list')) ?>" class="btn btn-primary">الأسئلة الشائعة</a>
        </div>
    </div>
    
    <?php endif; ?>
    
    <?php else: ?>
    
    <!-- لم يتم البحث -->
    <div class="empty-state">
        <i class="fa fa-search"></i>
        <h3>ابدأ البحث في الموقع</h3>
        <p style="color: #666;">اكتب ما تبحث عنه في الأعلى</p>
        
        <div class="suggestions">
            <h4 style="color: #2c3e50; margin-top: 0;">أمثلة على البحث:</h4>
            <ul style="text-align: start; line-height: 2;">
                <li><strong>منحة</strong> - للبحث عن معلومات المنح</li>
                <li><strong>سكن</strong> - للبحث عن الإقامات الجامعية</li>
                <li><strong>نقل</strong> - للبحث عن النقل الجامعي</li>
                <li><strong>نموذج</strong> - للبحث في الملفات والوثائق</li>
            </ul>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<br><br>

<?php endblock(); ?>