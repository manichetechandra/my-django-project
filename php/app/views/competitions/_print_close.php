<?php
/** نهاية الوثيقة الرسمية: الباركود (رقم المتابعة) والتوقيع والتذييل. المتغيرات: $barcode، $app، $footer_note */
?>
    <div class="doc-bottom">
        <div class="doc-barcode">
            <img src="<?= e($barcode) ?>" alt="<?= e($app['tracking_code']) ?>">
            <div dir="ltr"><?= e($app['tracking_code']) ?></div>
        </div>
        <div class="doc-sign">
            <div><?= t('المدير') ?></div>
            <div class="doc-sign-space"></div>
        </div>
    </div>
    <footer class="doc-foot">
        <?= e($footer_note) ?>
        <?= t('وثيقة مستخرجة من موقع مديرية الخدمات الجامعية سطيف؛ يمكن التحقق منها لدى مصلحة المستخدمين برقم المتابعة.') ?>
    </footer>
</main>
</body>
</html>
