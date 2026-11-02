<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($page_title) ?> | مديرية الخدمات الجامعية سطيف</title>
    <link rel="icon" href="<?= asset('images/logo.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha512-SfTiTlX6kk+qitfevl/7LibUOeJWlt9rbyDn92a1DqWOw9vWG2MFoays0sgObmWazO5BQPiFucnnEAjpAB+/Sw==" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-login-body">
<main class="admin-login admin-recover">
    <div class="admin-login-card">
        <div class="admin-login-head">
            <img src="<?= asset('images/logo.svg') ?>" alt="">
            <h1><i class="fa fa-key"></i> استعادة دخول المدير</h1>
            <p>نسيت كلمة مرور المدير العام؟</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-circle"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$verified): ?>
            <p class="admin-recover-intro">أثبت أنك صاحب الموقع بإنشاء ملف على الخادم، ثم عيّن كلمة مرور جديدة.</p>
            <ol class="admin-recover-steps">
                <li>
                    افتح مجلد الموقع على الخادم: <b>مدير الملفات</b> (File Manager) في cPanel،
                    أو <b>مستكشف الملفات</b> في XAMPP، مثلاً:<br><code dir="ltr">C:\xampp\htdocs\dou-setif</code>
                </li>
                <li>
                    ادخل المجلد <code dir="ltr">storage</code> (بجانب <code dir="ltr">config.php</code>)
                    وأنشئ فيه ملفاً فارغاً بهذا الاسم بالضبط:
                    <div class="admin-recover-file">
                        <code dir="ltr" id="recover-file"><?= e($file_name) ?></code>
                        <button type="button" class="btn btn-default btn-xs" data-copy="#recover-file"><i class="fa fa-copy"></i> نسخ</button>
                    </div>
                    <small class="text-muted">في Windows: زر الفأرة الأيمن ← جديد ← مستند نصي، ثم أعد تسميته بهذا الاسم.</small>
                </li>
                <li>ارجع إلى هذه الصفحة واضغط «تحقّق».</li>
            </ol>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-check-circle"></i> تحقّق</button>
            </form>
            <p class="admin-login-note">
                <i class="fa fa-shield"></i> الاسم صالح <?= (int) $minutes ?> دقيقة ولهذا المتصفح فقط. من لا يملك الوصول إلى ملفات الخادم
                لا يستطيع إنشاء الملف، لذلك لا يستطيع غيرك استعمال هذه الصفحة.
                <br>بديل من سطر الأوامر: <code dir="ltr">php database/tools/create_admin.php</code>
            </p>
        <?php else: ?>
            <div class="alert alert-success" role="status"><i class="fa fa-check"></i> تم التحقق. عيّن كلمة المرور الجديدة.</div>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <?php foreach (['username', 'password1', 'password2'] as $field): ?>
                    <div class="form-group <?= $form->errors($field) ? 'has-error' : '' ?>">
                        <label for="id_<?= $field ?>"><?= e($form->label($field)) ?></label>
                        <?= $form->widget($field) ?>
                        <?php foreach ($form->errors($field) as $err): ?><span class="help-block"><?= e($err) ?></span><?php endforeach; ?>
                        <?php if ($field === 'username'): ?>
                            <span class="help-block">
                                <?php if ($supers): ?>
                                    حسابات المدير العام الموجودة: <b dir="ltr"><?= e(implode('، ', $supers)) ?></b>.
                                    اسم جديد يُنشئ مديراً عاماً جديداً.
                                <?php else: ?>
                                    لا يوجد مدير عام بعد: سيُنشأ حساب جديد بهذا الاسم.
                                <?php endif; ?>
                            </span>
                        <?php elseif ($field === 'password1'): ?>
                            <span class="help-block">8 أحرف على الأقل، ولا تكون أرقاماً فقط. يُنصح بحروف لاتينية وأرقام ورموز.</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-save"></i> حفظ كلمة المرور</button>
            </form>
            <p class="admin-login-note"><i class="fa fa-info-circle"></i> بعد الحفظ يُحذف الملف تلقائياً، وتُغلق كل الجلسات المفتوحة لهذا الحساب.</p>
        <?php endif; ?>

        <p class="admin-login-note">
            موظف ولست المدير العام؟ اطلب من المدير العام تعيين كلمة مرور جديدة لك من «المستخدمون» في لوحة الإدارة.
        </p>
    </div>
    <a class="admin-login-back" href="<?= e(url('admin:login')) ?>"><i class="fa fa-arrow-right"></i> العودة إلى صفحة الدخول</a>
</main>
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var el = document.querySelector(btn.getAttribute('data-copy'));
        var select = function () {
            var r = document.createRange();
            r.selectNodeContents(el);
            var s = window.getSelection();
            s.removeAllRanges();
            s.addRange(r);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(el.textContent).then(function () {
                btn.innerHTML = '<i class="fa fa-check"></i> نُسخ';
            }, select);
        } else {
            select();
        }
    });
});
</script>
</body>
</html>
