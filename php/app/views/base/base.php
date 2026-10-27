<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="مديرية الخدمات الجامعية سطيف, الخدمات الجامعية, الإقامات">
    <meta name="description" content="الموقع الرسمي لمديرية الخدمات الجامعية سطيف">
    
    <title><?php block('title'); ?>مديرية الخدمات الجامعية سطيف<?php endblock(); ?></title>
    
    <link rel="icon" href="<?= asset('images/logo.svg') ?>" type="image/svg+xml">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@3.4.0/dist/css/bootstrap-rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- Custom CSS الشامل للعربية -->
    <style>
        * {
            direction: rtl;
            text-align: right;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 70px;
            direction: rtl;
            text-align: right;
        }
        
        /* Navbar RTL */
        .navbar-inverse {
            background-color: #2c3e50;
            border: none;
        }
        
        .navbar-inverse .navbar-brand {
            color: #fff;
            font-weight: bold;
            float: right;
        }
        
        .navbar-inverse .navbar-nav {
            float: right;
        }
        
        .navbar-inverse .navbar-nav > li {
            float: right;
        }
        
        .navbar-inverse .navbar-nav > li > a {
            color: #ecf0f1;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li > a:focus {
            background-color: #8cc040;
            color: #fff;
        }
        
        .navbar-inverse .navbar-nav > .active > a {
            background-color: #8cc040;
        }
        
        .navbar-toggle {
            float: left;
        }
        
        .navbar-header {
            float: none;
        }
        
        .navbar-collapse {
            text-align: right;
        }
        
        /* Dropdown RTL */
        .dropdown-menu {
            right: 0;
            left: auto;
            text-align: right;
        }
        
        .dropdown-menu > li > a {
            text-align: right;
        }
        
        .caret {
            margin-right: 5px;
            margin-left: 0;
        }
        
        /* Bootstrap Grid RTL */
        .row {
            direction: rtl;
        }
        
        .col-xs-1, .col-sm-1, .col-md-1, .col-lg-1,
        .col-xs-2, .col-sm-2, .col-md-2, .col-lg-2,
        .col-xs-3, .col-sm-3, .col-md-3, .col-lg-3,
        .col-xs-4, .col-sm-4, .col-md-4, .col-lg-4,
        .col-xs-5, .col-sm-5, .col-md-5, .col-lg-5,
        .col-xs-6, .col-sm-6, .col-md-6, .col-lg-6,
        .col-xs-7, .col-sm-7, .col-md-7, .col-lg-7,
        .col-xs-8, .col-sm-8, .col-md-8, .col-lg-8,
        .col-xs-9, .col-sm-9, .col-md-9, .col-lg-9,
        .col-xs-10, .col-sm-10, .col-md-10, .col-lg-10,
        .col-xs-11, .col-sm-11, .col-md-11, .col-lg-11,
        .col-xs-12, .col-sm-12, .col-md-12, .col-lg-12 {
            float: right !important;
        }
        
        /* Panels RTL */
        .panel {
            text-align: right;
            direction: rtl;
        }
        
        .panel-heading,
        .panel-body,
        .panel-footer {
            text-align: right;
            direction: rtl;
        }
        
        /* Buttons RTL */
        .btn {
            margin-left: 5px;
            margin-right: 0;
        }
        
        .btn i {
            margin-left: 5px;
            margin-right: 0;
        }
        
        /* Text alignment */
        .text-left {
            text-align: right !important;
        }
        
        .text-right {
            text-align: left !important;
        }
        
        /* Breadcrumb RTL */
        .breadcrumb {
            text-align: right;
            direction: rtl;
        }
        
        .breadcrumb > li {
            float: right;
        }
        
        .breadcrumb > li + li:before {
            content: "/ ";
            padding: 0 5px;
        }
        
        /* Forms RTL */
        .form-control {
            text-align: right;
            direction: rtl;
        }
        
        /* Lists RTL */
        ul, ol {
            padding-right: 0;
            padding-left: 40px;
        }
        
        /* Alerts RTL */
        .alert {
            text-align: right;
            direction: rtl;
        }
        
        /* Carousel RTL */
        .carousel-control.left {
            right: 0;
            left: auto;
        }
        
        .carousel-control.right {
            left: 0;
            right: auto;
        }
        
        .carousel-caption {
            right: 15%;
            left: 15%;
            text-align: right;
        }
        
        /* Pagination RTL */
        .pagination {
            direction: rtl;
        }
        
        .pagination > li {
            float: right;
        }
        
        /* Tables RTL */
        table {
            direction: rtl;
            text-align: right;
        }
        
        th, td {
            text-align: right;
        }
    </style>
    
    <?php block('extra_css'); ?><?php endblock(); ?>
</head>
<body>
<!-- ========================================
     رسائل التنبيه (Messages)
========================================= -->
<?php if (!empty($messages) && block_content('inline_messages') === ''): ?>
<div class="flash-messages" style="position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 9999; width: 90%; max-width: 600px;">
    <?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= e(alert_class($message['tags'])) ?> alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        
        <?php if ($message['tags'] == 'success'): ?>
            <i class="fa fa-check-circle"></i>
        <?php elseif ($message['tags'] == 'error'): ?>
            <i class="fa fa-times-circle"></i>
        <?php elseif ($message['tags'] == 'warning'): ?>
            <i class="fa fa-exclamation-triangle"></i>
        <?php else: ?>
            <i class="fa fa-info-circle"></i>
        <?php endif; ?>
        
        <strong><?= e($message['message']) ?></strong>
    </div>
    <?php endforeach; ?>
</div>

<script>
// إخفاء الرسائل تلقائياً بعد 5 ثواني
setTimeout(function() {
    $('.flash-messages .alert').fadeOut('slow');
}, 5000);
</script>
<?php endif; ?>
    <!-- Navbar -->
    <?php include view_path('base/navbar'); ?>

    <!-- الإشعارات -->
    
    <?php include view_path('notifications/notification_bar'); ?>

    <!-- المحتوى الرئيسي -->
    <?php block('content'); ?><?php endblock(); ?>

    <!-- Footer -->
    <?php include view_path('base/footer'); ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
    
    <?php block('extra_js'); ?><?php endblock(); ?>




    <?php if (is_authenticated() && !empty($student_profile)): ?>
    <?php include view_path('base/notifications_js'); ?>
    <?php endif; ?>

</body>
</html>