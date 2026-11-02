<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php block('title'); ?>لوحة الإدارة<?php endblock(); ?> | مديرية الخدمات الجامعية سطيف</title>
    <link rel="icon" href="<?= asset('images/logo.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@3.4.0/dist/css/bootstrap-rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
<nav class="admin-topbar">
    <button class="admin-menu-toggle visible-xs-inline-block" type="button" onclick="document.body.classList.toggle('menu-open')"><i class="fa fa-bars"></i></button>
    <a class="admin-brand" href="<?= e(url('admin:index')) ?>"><img src="<?= asset('images/logo.svg') ?>" alt=""> لوحة الإدارة</a>
    <div class="admin-topbar-links">
        <a href="<?= e(url('homepage:index')) ?>" target="_blank"><i class="fa fa-external-link"></i> <span class="hidden-xs">عرض الموقع</span></a>
        <a href="<?= e(url('students:admin_dashboard')) ?>"><i class="fa fa-dashboard"></i> <span class="hidden-xs">متابعة الطلبات</span></a>
        <span class="admin-user"><i class="fa fa-user-circle"></i> <?= e(current_user()->display_name()) ?></span>
        <a href="<?= e(url('admin:password')) ?>" title="تغيير كلمة المرور"><i class="fa fa-key"></i></a>
        <a href="<?= e(url('admin:logout')) ?>" title="تسجيل الخروج"><i class="fa fa-sign-out"></i></a>
    </div>
</nav>
<aside class="admin-sidebar">
    <a href="<?= e(url('admin:index')) ?>" class="admin-nav-link <?= request_path() === '/admin/' ? 'active' : '' ?>"><i class="fa fa-home"></i> الرئيسية</a>
    <?php foreach ($admin_menu as $group => $items): ?>
        <div class="admin-nav-group"><?= e($group) ?></div>
        <?php foreach ($items as $item): ?>
            <a href="<?= e(url('admin:list', $item['key'])) ?>" class="admin-nav-link <?= str_starts_with(request_path(), '/admin/' . $item['key'] . '/') ? 'active' : '' ?>">
                <i class="fa <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
                <?php if ($item['badge']): ?><span class="badge admin-badge"><?= e($item['badge']) ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
</aside>
<main class="admin-main">
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?= e(alert_class($message['tags'])) ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
            <?= e($message['message']) ?>
        </div>
    <?php endforeach; ?>
    <?php block('content'); ?><?php endblock(); ?>
</main>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
