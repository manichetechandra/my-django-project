<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" dir="<?= text_dir() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="<?= t('مديرية الخدمات الجامعية سطيف, الخدمات الجامعية, الإقامات') ?>">
    <meta name="description" content="<?= t('الموقع الرسمي لمديرية الخدمات الجامعية سطيف') ?>">
    
    <title><?php block('title'); ?><?= t('مديرية الخدمات الجامعية سطيف') ?><?php endblock(); ?></title>
    
    <link rel="icon" href="<?= asset('images/logo.svg') ?>" type="image/svg+xml">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php foreach (languages() as $code => $label): ?>
    <link rel="alternate" hreflang="<?= e($code) ?>" href="<?= e(lang_url($code, true)) ?>">
    <?php endforeach; ?>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <?php if (is_rtl()): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@3.4.0/dist/css/bootstrap-rtl.min.css">
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- CSS الموقع: مكتوب بخصائص منطقية (start/end بدل right/left) فيعمل بالعربية والفرنسية والإنجليزية -->
    <style>
        /* الاتجاه من <html dir="rtl|ltr">: كل عنصر يرث اتجاه أبيه، والنص يبدأ من بداية السطر */
        :where(body) * {
            direction: inherit;
        }

        * {
            text-align: start;
        }

        /* المتصفح يفرض اتجاه ltr داخل حقول التاريخ والوقت: نحاذيها لليمين في العربية */
        html[dir="rtl"] input[type="date"],
        html[dir="rtl"] input[type="time"],
        html[dir="rtl"] input[type="datetime-local"],
        html[dir="rtl"] input[type="month"],
        html[dir="rtl"] input[type="week"] {
            text-align: right;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: start;
        }

        /* شريط التنقل ثابت أعلى الشاشة عند التمرير، لكنه يأخذ مكانه الحقيقي في الصفحة (sticky بدل fixed):
           ارتفاعه يتغير (100 إلى 200 بكسل حسب عرض الشاشة واللغة)، والهامش الثابت القديم (70px) كان يترك
           أول الصفحة — عنوان الخبر مثلاً — مختفياً تحته. لذلك صار margin-top لرؤوس الصفحات الملونة 0 بدل 70px
           (كان يعوّض الشريط الثابت)، و30px بدل 80px لصفحات النشاطات والإقامات والمصالح */
        .navbar-fixed-top {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            left: auto;
            right: auto;
            margin-bottom: 0;
        }
        
        /* Navbar RTL */
        .navbar-inverse {
            background-color: #2c3e50;
            border: none;
        }
        
        .navbar-inverse .navbar-brand {
            color: #fff;
            font-weight: bold;
            float: right; float: inline-start;
        }
        
        @media (min-width: 768px) {
            .navbar-inverse .navbar-nav {
                float: right; float: inline-start;
            }

            .navbar-inverse .navbar-nav > li {
                float: right; float: inline-start;
            }
        }

        /* الهاتف: اسم الموقع الطويل (بالفرنسية مثلاً) ينزل إلى سطر ثانٍ بدل أن يتداخل مع القائمة */
        @media (max-width: 767px) {
            .navbar-inverse .navbar-brand {
                height: auto;
                max-width: calc(100% - 60px);
                line-height: 20px;
            }
        }

        /* الشاشات الصغيرة جداً: نص الزر الطويل ينزل إلى سطر ثانٍ بدل أن يخرج من الشاشة (الزر القصير لا يتغير)،
           والكلمة الأطول من عرض الشاشة في العناوين («téléchargement» بخط كبير مثلاً) تنقسم بدل أن تخرج منها */
        @media (max-width: 480px) {
            .btn {
                white-space: normal;
                max-width: 100%;
            }

            h1, h2, h3 {
                overflow-wrap: break-word;
            }
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
            float: left; float: inline-end;
        }
        
        .navbar-header {
            float: none;
        }
        
        .navbar-collapse {
            text-align: start;
        }
        
        /* Dropdown RTL */
        .dropdown-menu {
            inset-inline-start: 0;
            inset-inline-end: auto;
            text-align: start;
        }
        
        .dropdown-menu > li > a {
            text-align: start;
        }
        
        .caret {
            margin-inline-start: 5px;
            margin-inline-end: 0;
        }
        
        /* Bootstrap Grid RTL */
        .row {
        }
        
        /* كل مقاس يطفو في العرض الذي يعمل فيه فقط، كما في Bootstrap: تطبيق الطفو على كل الشاشات
           كان يجعل أعمدة col-sm/md/lg في الهاتف تنكمش على محتواها بدل أن تأخذ عرض الشاشة */
        .col-xs-1, .col-xs-2, .col-xs-3,
        .col-xs-4, .col-xs-5, .col-xs-6,
        .col-xs-7, .col-xs-8, .col-xs-9,
        .col-xs-10, .col-xs-11, .col-xs-12 {
            float: right !important; float: inline-start !important;
        }

        @media (min-width: 768px) {
            .col-sm-1, .col-sm-2, .col-sm-3,
            .col-sm-4, .col-sm-5, .col-sm-6,
            .col-sm-7, .col-sm-8, .col-sm-9,
            .col-sm-10, .col-sm-11, .col-sm-12 {
                float: right !important; float: inline-start !important;
            }
        }

        @media (min-width: 992px) {
            .col-md-1, .col-md-2, .col-md-3,
            .col-md-4, .col-md-5, .col-md-6,
            .col-md-7, .col-md-8, .col-md-9,
            .col-md-10, .col-md-11, .col-md-12 {
                float: right !important; float: inline-start !important;
            }
        }

        @media (min-width: 1200px) {
            .col-lg-1, .col-lg-2, .col-lg-3,
            .col-lg-4, .col-lg-5, .col-lg-6,
            .col-lg-7, .col-lg-8, .col-lg-9,
            .col-lg-10, .col-lg-11, .col-lg-12 {
                float: right !important; float: inline-start !important;
            }
        }
        
        /* Panels RTL */
        .panel {
            text-align: start;
        }
        
        .panel-heading,
        .panel-body,
        .panel-footer {
            text-align: start;
        }
        
        /* Buttons RTL */
        .btn {
            margin-inline-end: 5px;
            margin-inline-start: 0;
        }
        
        .btn i {
            margin-inline-end: 5px;
            margin-inline-start: 0;
        }
        
        /* Text alignment */
        .text-left {
            text-align: start !important;
        }
        
        .text-right {
            text-align: end !important;
        }
        
        /* Breadcrumb RTL */
        .breadcrumb {
            text-align: start;
        }
        
        .breadcrumb > li {
            float: right; float: inline-start;
        }

        /* عناصر المسار عائمة: نجعل الشريط الرمادي يحتويها (clearfix)، وإلا ظهر النص نصفه خارج الشريط،
           وإذا نزل المسار إلى سطرين (في الهاتف) دخلت العناصر في ما تحته فضيّقته */
        .breadcrumb:after {
            content: "";
            display: table;
            clear: both;
        }
        
        .breadcrumb > li + li:before {
            content: "/ ";
            padding: 0 5px;
        }
        
        /* Forms RTL */
        .form-control {
            text-align: start;
        }
        
        /* Lists RTL */
        ul, ol {
            padding-inline-start: 0;
            padding-inline-end: 40px;
        }

        /* القوائم التي يصفّر Bootstrap حشوها الأيسر فقط: في العربية يقع ذلك على جهة النهاية فتصير 0 من الجهتين،
           فنفعل مثله في الفرنسية والإنجليزية (وإلا بقي حشو 40px على اليمين، كما في قائمة الهاتف) */
        html[dir="ltr"] .nav,
        html[dir="ltr"] .list-unstyled,
        html[dir="ltr"] .list-inline,
        html[dir="ltr"] .list-group,
        html[dir="ltr"] .media-list,
        html[dir="ltr"] .pagination {
            padding-right: 0;
        }
        
        /* Alerts RTL */
        .alert {
            text-align: start;
        }
        
        /* الأسهم مرسومة للعربية (← تعني التالي أو المزيد): نعكسها في الفرنسية والإنجليزية */
        html[dir="ltr"] .fa-arrow-left,
        html[dir="ltr"] .fa-arrow-right,
        html[dir="ltr"] .fa-arrow-circle-left,
        html[dir="ltr"] .fa-arrow-circle-right {
            transform: scaleX(-1);
        }
        
        /* Carousel RTL */
        .carousel-control.left {
            inset-inline-start: 0;
            inset-inline-end: auto;
        }
        
        .carousel-control.right {
            inset-inline-end: 0;
            inset-inline-start: auto;
        }
        
        .carousel-caption {
            inset-inline-start: 15%;
            inset-inline-end: 15%;
            text-align: start;
        }
        
        /* Pagination RTL */
        .pagination {
        }
        
        .pagination > li {
            float: right; float: inline-start;
        }
        
        /* Tables RTL */
        table {
            text-align: start;
        }
        
        th, td {
            text-align: start;
        }
    </style>
    
    <!-- إزاحة الأعمدة (col-md-offset-*) من اليمين، لأن bootstrap-rtl لا يُحمَّل -->
    <style>
        @media (min-width: 992px) {
            .col-md-offset-1 { margin-inline-end: 0; margin-inline-start: 8.33333333%; }
            .col-md-offset-2 { margin-inline-end: 0; margin-inline-start: 16.66666667%; }
            .col-md-offset-3 { margin-inline-end: 0; margin-inline-start: 25%; }
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