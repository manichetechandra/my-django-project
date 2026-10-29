<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" dir="<?= text_dir() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t('خطأ في الخادم - مديرية الخدمات الجامعية سطيف') ?></title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fa; color: #2c3e50; margin: 0; padding: 40px 15px; text-align: center; }
        .box { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,.08); }
        .code { font-size: 80px; font-weight: bold; color: #e74c3c; line-height: 1; }
        a { color: #8cc040; }
        pre { direction: ltr; text-align: left; background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow: auto; font-size: 12px; max-height: 500px; }
        .msg { direction: ltr; text-align: left; background: #fdecea; color: #c0392b; padding: 12px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
<div class="box">
    <div class="code">500</div>
    <h2><?= t('حدث خطأ في الخادم') ?></h2>
    <p><?= t('نعتذر، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.') ?></p>
    <p><a href="<?= e(base_url() . '/') ?>"><?= t('العودة للرئيسية') ?></a></p>
    <?php if (!empty($exception)): ?>
        <hr>
        <p style="color:#7f8c8d"><?= t('تفاصيل الخطأ (تظهر فقط في وضع التطوير):') ?></p>
        <div class="msg"><?= e(get_class($exception)) ?>: <?= e($exception->getMessage()) ?></div>
        <pre><?= e($exception->getFile()) ?>:<?= e($exception->getLine()) ?>

<?= e($exception->getTraceAsString()) ?></pre>
    <?php endif; ?>
</div>
</body>
</html>
