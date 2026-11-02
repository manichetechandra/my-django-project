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
<main class="admin-login">
    <div class="admin-login-card">
        <div class="admin-login-head">
            <img src="<?= asset('images/logo.svg') ?>" alt="">
            <h1>لوحة الإدارة</h1>
            <p>مديرية الخدمات الجامعية سطيف</p>
        </div>

        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= e(alert_class($message['tags'])) ?>" role="alert"><?= e($message['message']) ?></div>
        <?php endforeach; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-circle"></i> <?= e($error) ?></div>
        <?php elseif ($next !== '' && !is_post()): ?>
            <div class="alert alert-info" role="status"><i class="fa fa-info-circle"></i> سجّل الدخول بحساب موظف للمتابعة.</div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="form-group <?= $form->errors('username') ? 'has-error' : '' ?>">
                <label for="id_username"><i class="fa fa-user"></i> اسم المستخدم</label>
                <?= $form->widget('username') ?>
                <?php foreach ($form->errors('username') as $err): ?><span class="help-block"><?= e($err) ?></span><?php endforeach; ?>
            </div>
            <div class="form-group <?= $form->errors('password') ? 'has-error' : '' ?>">
                <label for="id_password"><i class="fa fa-lock"></i> كلمة المرور</label>
                <?= $form->widget('password') ?>
                <?php foreach ($form->errors('password') as $err): ?><span class="help-block"><?= e($err) ?></span><?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-sign-in"></i> دخول</button>
        </form>
        <?php if (config('security.web_recovery', true)): ?>
            <p class="admin-login-forgot"><a href="<?= e(url('admin:recover')) ?>"><i class="fa fa-key"></i> نسيت كلمة مرور المدير؟</a></p>
        <?php endif; ?>

        <p class="admin-login-note">
            <i class="fa fa-shield"></i> هذه الواجهة مخصصة لموظفي المديرية فقط.
            الطلبة يدخلون من <a href="<?= e(url('students:login')) ?>">فضاء الطالب</a>.
        </p>
    </div>
    <a class="admin-login-back" href="<?= e(url('homepage:index')) ?>"><i class="fa fa-arrow-right"></i> العودة إلى الموقع</a>
</main>
</body>
</html>
