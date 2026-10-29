<?php extend('base/base'); ?>
<?php block('inline_messages'); ?>1<?php endblock(); ?>
<?php block('title'); ?><?= e($page_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    .contact-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
        margin-top: 0;
    }
    
    .contact-form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .contact-info-box {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        border-inline-start: 5px solid #8cc040;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .contact-info-box:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateX(-5px);
    }

    html[dir="ltr"] .contact-info-box:hover {
        transform: translateX(5px);
    }
    
    .contact-info-box h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 20px;
    }
    
    .contact-info-box i {
        color: #8cc040;
        font-size: 24px;
        margin-inline-end: 10px;
        float: right; float: inline-start;
        margin-top: -5px;
    }
    
    .contact-info-box p {
        margin: 10px 0;
        color: #666;
        line-height: 1.8;
    }
    
    .form-group label {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
    }
    
    .form-control {
        border: 2px solid #ddd;
        border-radius: 5px;
        padding: 12px; height: auto;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #8cc040;
        box-shadow: 0 0 0 0.2rem rgba(140, 192, 64, 0.25);
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #8cc040 0%, #6da32b 100%);
        border: none;
        color: white;
        padding: 12px 40px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 5px;
        transition: all 0.3s;
        width: 100%;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(140, 192, 64, 0.4);
    }
    
    .required-field::after {
        content: " *";
        color: #e74c3c;
        font-weight: bold;
    }
    
    .alert {
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-inline-start: 5px solid;
    }
    
    .alert-success {
        background-color: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }
    
    .errorlist {
        list-style: none;
        padding: 0;
        margin: 5px 0 0 0;
    }
    
    .errorlist li {
        color: #e74c3c;
        font-size: 13px;
        margin-top: 5px;
    }
    
    .stats-box {
        background: linear-gradient(135deg, #8cc040 0%, #6da32b 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .stats-box h3 {
        margin: 0;
        font-size: 36px;
        font-weight: bold;
    }
    
    .stats-box p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    
    .map-container {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    
    .working-hours {
        background: #fff3cd;
        border-inline-start: 5px solid #ffc107;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }
    
    .working-hours h5 {
        color: #856404;
        margin-top: 0;
        font-weight: bold;
    }
    
    .working-hours p {
        color: #856404;
        margin: 5px 0;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- Header -->
<div class="contact-header">
    <div class="container">
        <h1 class="text-center" style="margin: 0; font-size: 42px;">
            <i class="fa fa-envelope"></i> <?= t('اتصل بنا') ?>
        </h1>
        <p class="text-center" style="font-size: 18px; margin-top: 15px; opacity: 0.9;">
            <?= t('نحن هنا للإجابة على استفساراتكم ومساعدتكم') ?>
        </p>
    </div>
</div>

<div class="container">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        <li><a href="<?= e(url('homepage:index')) ?>"><?= t('الرئيسية') ?></a></li>
        <li class="active"><?= t('اتصل بنا') ?></li>
    </ol>
    
    <!-- رسائل النجاح/الخطأ -->
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= e(alert_class($message['tags'])) ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php if ($message['tags'] == 'success'): ?>
                    <i class="fa fa-check-circle"></i>
                <?php else: ?>
                    <i class="fa fa-exclamation-triangle"></i>
                <?php endif; ?>
                <?= e($message['message']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- النموذج -->
        <div class="col-md-7">
            <div class="contact-form-container">
                <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 25px;">
                    <i class="fa fa-paper-plane"></i> <?= t('أرسل لنا رسالة') ?>
                </h3>
                
                <form method="post" action="<?= e(url('contact:contact')) ?>">
                    <?= csrf_field() ?>
                    
                    <!-- الاسم الكامل -->
                    <div class="form-group">
                        <label for="id_full_name" class="required-field"><?= t('الاسم الكامل') ?></label>
                        <?= $form->widget('full_name') ?>
                        <?php if (!empty($form->errors('full_name'))): ?>
                            <ul class="errorlist">
                                <?php foreach ($form->errors('full_name') as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- البريد الإلكتروني -->
                    <div class="form-group">
                        <label for="id_email" class="required-field"><?= t('البريد الإلكتروني') ?></label>
                        <?= $form->widget('email') ?>
                        <?php if (!empty($form->errors('email'))): ?>
                            <ul class="errorlist">
                                <?php foreach ($form->errors('email') as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- رقم الهاتف -->
                    <div class="form-group">
                        <label for="id_phone"><?= t('رقم الهاتف') ?> <small style="color: #999;"><?= t('(اختياري)') ?></small></label>
                        <?= $form->widget('phone') ?>
                        <?php if (!empty($form->errors('phone'))): ?>
                            <ul class="errorlist">
                                <?php foreach ($form->errors('phone') as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- الموضوع -->
                    <div class="form-group">
                        <label for="id_subject" class="required-field"><?= t('الموضوع') ?></label>
                        <?= $form->widget('subject') ?>
                        <?php if (!empty($form->errors('subject'))): ?>
                            <ul class="errorlist">
                                <?php foreach ($form->errors('subject') as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- الرسالة -->
                    <div class="form-group">
                        <label for="id_message" class="required-field"><?= t('رسالتك') ?></label>
                        <?= $form->widget('message') ?>
                        <?php if (!empty($form->errors('message'))): ?>
                            <ul class="errorlist">
                                <?php foreach ($form->errors('message') as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- زر الإرسال -->
                    <button type="submit" class="btn btn-submit">
                        <i class="fa fa-send"></i> <?= t('إرسال الرسالة') ?>
                    </button>
                    
                    <p style="text-align: center; margin-top: 15px; color: #999; font-size: 13px;">
                        <i class="fa fa-lock"></i> <?= t('معلوماتك آمنة ولن تُستخدم إلا للرد على استفسارك') ?>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- معلومات الاتصال -->
        <div class="col-md-5">
            
            <!-- إحصائية -->
            <?php if ($total_messages > 0): ?>
            <div class="stats-box">
                <h3><i class="fa fa-envelope-o"></i> <?= e($total_messages) ?></h3>
                <p><?= t('رسالة تم استقبالها') ?></p>
            </div>
            <?php endif; ?>
            
            <!-- العنوان -->
            <div class="contact-info-box">
                <i class="fa fa-map-marker"></i>
                <h4><?= t('العنوان') ?></h4>
                <p>
                    <?= t('مديرية الخدمات الجامعية') ?><br>
                    <?= t('جامعة فرحات عباس - سطيف 1') ?><br>
                    <?= t('سطيف، الجزائر') ?>
                </p>
            </div>
            
            <!-- الهاتف -->
            <div class="contact-info-box">
                <i class="fa fa-phone"></i>
                <h4><?= t('الهاتف') ?></h4>
                <p>
                    <strong><?= t('الخط الرئيسي:') ?></strong> 036 xx xx xx<br>
                    <strong><?= t('الفاكس:') ?></strong> 036 xx xx xx
                </p>
            </div>
            
            <!-- البريد الإلكتروني -->
            <div class="contact-info-box">
                <i class="fa fa-envelope"></i>
                <h4><?= t('البريد الإلكتروني') ?></h4>
                <p>
                    <a href="mailto:contact@univ-setif.dz" style="color: #3498db;">
                        contact@univ-setif.dz
                    </a>
                </p>
            </div>
            
            <!-- ساعات العمل -->
            <div class="working-hours">
                <h5><i class="fa fa-clock-o"></i> <?= t('ساعات العمل') ?></h5>
                <p>
                    <strong><?= t('الأحد - الخميس:') ?></strong> <?= t('8:00 صباحاً - 4:00 مساءً') ?><br>
                    <strong><?= t('الجمعة والسبت:') ?></strong> <?= t('مغلق') ?>
                </p>
            </div>
            
            <!-- روابط سريعة -->
            <div class="contact-info-box">
                <i class="fa fa-link"></i>
                <h4><?= t('روابط مفيدة') ?></h4>
                <p>
                    <a href="<?= e(url('services:service_list')) ?>" style="color: #3498db;">
                        <i class="fa fa-arrow-left"></i> <?= t('الخدمات الجامعية') ?>
                    </a><br>
                    <a href="<?= e(url('news:news_list')) ?>" style="color: #3498db; margin-top: 5px; display: inline-block;">
                        <i class="fa fa-arrow-left"></i> <?= t('آخر الأخبار') ?>
                    </a>
                </p>
            </div>
            
        </div>
    </div>
    
    <!-- خريطة الموقع (اختياري) -->
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">
                <i class="fa fa-map"></i> <?= t('موقعنا على الخريطة') ?>
            </h3>
            <div class="map-container">
                <!-- خريطة Google Maps -->
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3233.8333333333!2d5.4166666666667!3d36.191666666667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzbCsDExJzMwLjAiTiA1wrAyNScwMC4wIkU!5e0!3m2!1sar!2sdz!4v1234567890123!5m2!1sar!2sdz" 
                    width="100%" 
                    height="400" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
            <p style="text-align: center; margin-top: 10px; color: #999; font-size: 13px;">
                <i class="fa fa-info-circle"></i> <?= t('يمكنك استخدام الخريطة للوصول إلينا بسهولة') ?>
            </p>
        </div>
    </div>
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<script>
    $(document).ready(function() {
        // إخفاء رسائل النجاح تلقائياً بعد 5 ثوانٍ
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
        
        // تأكيد قبل الإرسال
        $('#id_message').closest('form').on('submit', function(e) {
            var message = $('#id_message').val();
            if (message.length < 10) {
                e.preventDefault();
                alert(<?= js_t('يرجى كتابة رسالة أطول (10 أحرف على الأقل)') ?>);
                return false;
            }
        });
        
        // عداد الأحرف للرسالة
        $('#id_message').on('input', function() {
            var length = $(this).val().length;
            var label = $('label[for="id_message"]');
            
            if (length > 0) {
                label.html(<?= js_t('رسالتك') ?> + ' <span class="required-field"></span> <small style="color: #999;">(' + length + ' ' + <?= js_t('حرف') ?> + ')</small>');
            } else {
                label.html(<?= js_t('رسالتك') ?> + ' <span class="required-field"></span>');
            }
        });
    });
</script>
<?php endblock(); ?>