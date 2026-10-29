<?php extend('base/base'); ?>
<?php block('title'); ?>كلمة المدير - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .director-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 50px 0;
        margin-bottom: 40px;
        margin-top: 70px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .director-header h1 {
        margin: 0;
        font-size: 36px;
        text-align: center;
    }
    
    .director-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 40px;
    }
    
    .director-image-section {
        padding: 30px;
        text-align: center;
        background: #f8f9fa;
    }
    
    .director-image {
        width: 250px;
        height: 250px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid white;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        margin-bottom: 20px;
    }
    
    .director-name {
        font-size: 28px;
        color: #2c3e50;
        font-weight: bold;
        margin: 15px 0 5px 0;
    }
    
    .director-title-pos {
        font-size: 18px;
        color: #8cc040;
        font-weight: 600;
    }
    
    .word-content {
        padding: 40px;
        line-height: 2.2;
        font-size: 17px;
        color: #333;
        text-align: justify;
    }
    
    .quote-icon {
        font-size: 48px;
        color: #8cc040;
        opacity: 0.3;
    }
    
    .signature-section {
        text-align: end;
        padding: 0 40px 40px;
    }
    
    .signature-image {
        max-height: 100px;
        margin-bottom: 10px;
    }
    
    .signature-text {
        font-size: 18px;
        color: #2c3e50;
        font-weight: bold;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<?php if (!empty($director_word)): ?>

<!-- Header -->
<div class="director-header">
    <div class="container">
        <h1>
            <i class="fa fa-quote-right"></i>
            <?= e($director_word['word_title']) ?>
            <i class="fa fa-quote-left"></i>
        </h1>
    </div>
</div>

<!-- المحتوى -->
<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>">الرئيسية</a></li>
        <li><a href="#">المديرية</a></li>
        <li class="active">كلمة المدير</li>
    </ol>

    <div class="row">
        <div class="col-md-12">
            <div class="director-card">
                <div class="row">
                    <!-- صورة المدير -->
                    <?php if (!empty($director_word['director_image'])): ?>
                    <div class="col-md-4">
                        <div class="director-image-section">
                            <img src="<?= e(media_url($director_word['director_image'])) ?>" 
                                 alt="<?= e($director_word['director_name']) ?>" 
                                 class="director-image">
                            <h3 class="director-name"><?= e($director_word['director_name']) ?></h3>
                            <p class="director-title-pos"><?= e($director_word['director_title']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-8">
                    <?php else: ?>
                    <div class="col-md-12">
                    <?php endif; ?>
                        <!-- نص الكلمة -->
                        <div class="word-content">
                            <i class="fa fa-quote-right quote-icon"></i>
                            <div style="margin-top: -30px;">
                                <?= linebreaks($director_word['word_content']) ?>
                            </div>
                        </div>
                        
                        <!-- التوقيع -->
                        <div class="signature-section">
                            <?php if (!empty($director_word['signature'])): ?>
                                <img src="<?= e(media_url($director_word['signature'])) ?>" 
                                     alt="التوقيع" 
                                     class="signature-image">
                            <?php endif; ?>
                            
                            <?php if (empty($director_word['director_image'])): ?>
                            <p class="signature-text"><?= e($director_word['director_name']) ?></p>
                            <p style="color: #8cc040; font-size: 16px;"><?= e($director_word['director_title']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- إذا لم توجد كلمة مدير -->
<div class="container" style="margin-top: 100px;">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning text-center" style="padding: 40px; font-size: 18px;">
                <i class="fa fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h3>لا توجد كلمة للمدير حالياً</h3>
                <p>سيتم إضافة كلمة المدير قريباً</p>
                <a href="<?= e(url('homepage:index')) ?>" class="btn btn-success">
                    <i class="fa fa-home"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php endblock(); ?>