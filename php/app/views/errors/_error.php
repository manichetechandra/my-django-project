<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($error_title) ?> <?= t('- مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?>
<?php block('content'); ?>
<div class="container" style="padding: 80px 15px; text-align: center; min-height: 50vh;">
    <div style="font-size: 90px; font-weight: bold; color: #8cc040; line-height: 1;"><?= e($error_code) ?></div>
    <i class="fa <?= e($error_icon) ?>" style="font-size: 50px; color: #2c3e50; margin: 20px 0;"></i>
    <h2 style="color: #2c3e50;"><?= e($error_title) ?></h2>
    <p style="color: #7f8c8d; font-size: 16px; margin: 15px 0 30px;"><?= e($error_text) ?></p>
    <a href="<?= e(url('homepage:index')) ?>" class="btn btn-success btn-lg"><i class="fa fa-home"></i> <?= t('العودة للرئيسية') ?></a>
    <a href="javascript:history.back()" class="btn btn-default btn-lg"><i class="fa fa-arrow-right"></i> <?= t('رجوع') ?></a>
</div>
<?php endblock(); ?>
