<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($faq['question']) ?> - الأسئلة الشائعة<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .faq-detail-header {
        background: linear-gradient(135deg, #16a085 0%, #3498db 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    .answer-box {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        border-inline-start: 5px solid #16a085;
    }
    
    .answer-box h3 {
        color: #2c3e50;
        margin-top: 0;
    }
    
    .answer-box p {
        line-height: 2;
        font-size: 17px;
        color: #555;
        text-align: justify;
    }
    
    .related-faq {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .related-faq h4 {
        color: #2c3e50;
        margin-top: 0;
        border-bottom: 3px solid #16a085;
        padding-bottom: 10px;
    }
    
    .related-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: all 0.3s;
    }
    
    .related-item:hover {
        background: #f8f9fa;
    }
    
    .related-item a {
        color: #2c3e50;
        text-decoration: none;
    }
    
    .related-item a:hover {
        color: #16a085;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<div class="faq-detail-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 32px;">
            <i class="fa fa-question-circle"></i> <?= e($faq['question']) ?>
        </h1>
        <p style="margin-top: 15px; opacity: 0.9;">
            <i class="fa <?= e($faq['category']['icon']) ?>"></i> <?= e($faq['category']['name']) ?>
        </p>
    </div>
</div>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="<?= e(url('faq:faq_list')) ?>">الأسئلة الشائعة</a></li>
        <li class="active"><?= e(truncatewords($faq['question'], 5)) ?></li>
    </ol>
    
    <div class="row">
        <div class="col-md-8">
            <div class="answer-box">
                <h3><i class="fa fa-check-circle" style="color: #16a085;"></i> الإجابة:</h3>
                <p><?= linebreaks($faq['answer']) ?></p>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <span style="color: #999;">
                        <i class="fa fa-eye"></i> <?= e($faq['views_count']) ?> مشاهدة
                    </span>
                    <span style="color: #999; margin-inline-start: 20px;">
                        <i class="fa fa-calendar"></i> آخر تحديث: <?= e(format_date($faq['updated_at'], 'd/m/Y')) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if (!empty($related_faqs)): ?>
            <div class="related-faq">
                <h4><i class="fa fa-link"></i> أسئلة ذات صلة</h4>
                <?php foreach ($related_faqs as $related): ?>
                <div class="related-item">
                    <a href="<?= e(url('faq:faq_detail', $related['id'])) ?>">
                        <i class="fa fa-question-circle" style="color: #16a085; margin-inline-end: 10px;"></i>
                        <?= e($related['question']) ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <br>
            <a href="<?= e(url('faq:faq_list')) ?>" class="btn btn-default btn-block">
                <i class="fa fa-arrow-right"></i> العودة لكل الأسئلة
            </a>
        </div>
    </div>
</div>

<br><br>

<?php endblock(); ?>