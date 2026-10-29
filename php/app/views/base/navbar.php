<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#myNavbar" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?= e(url('homepage:index')) ?>"><?= t('مديرية الخدمات الجامعية لولاية سطيف') ?></a>
        </div>
        
        <div class="collapse navbar-collapse" id="myNavbar">
            <ul class="nav navbar-nav navbar-right">
                <li class="active">
                    <a href="<?= e(url('homepage:index')) ?>">
                        <i class="fa fa-home"></i> <?= t('الرئيسية') ?>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">
                        <i class="fa fa-building"></i> <?= t('المديرية') ?> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right" style="width: 300px; max-height: 450px; overflow-y: auto; inset-inline-end: auto; inset-inline-start: 0;">
                        <li><a href="<?= e(url('homepage:director_word')) ?>">
                            <i class="fa fa-quote-left"></i> <?= t('كلمة المدير') ?>
                        </a></li>
                        <li><a href="<?= e(url('homepage:organization')) ?>">
                            <i class="fa fa-sitemap"></i> <?= t('الهيكل التنظيمي') ?>
                        </a></li>
                        <li><a href="<?= e(url('homepage:history')) ?>">
                            <i class="fa fa-history"></i> <?= t('تاريخ المديرية') ?>
                        </a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="#cite">
                        <i class="fa fa-university"></i> <?= tx('nav', 'الإقامات الجامعية') ?>
                    </a>
                </li>
                
                <li>
                    <a href="https://progres.mesrs.dz/webonou/" target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-external-link"></i> webonou
                    </a>
                </li>
                
                <li><a href="<?= e(url('news:news_list')) ?>"><i class="fa fa-newspaper-o"></i> <?= t('الأخبار') ?></a></li>
                
                <li>
                    <a href="#">
                        <i class="fa fa-trophy"></i> <?= t('المسابقات') ?>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-cogs"></i> <?= tx('nav', 'الخدمات الجامعية') ?> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= e(url('services:service_by_type', 'transport')) ?>"><i class="fa fa-bus"></i> <?= t('النقل') ?></a></li>
                        <li><a href="<?= e(url('services:service_by_type', 'housing')) ?>"><i class="fa fa-bed"></i> <?= t('الإيواء') ?></a></li>
                        <li><a href="<?= e(url('services:service_by_type', 'catering')) ?>"><i class="fa fa-cutlery"></i> <?= t('الإطعام') ?></a></li>
                        <li><a href="<?= e(url('services:service_by_type', 'scholarship')) ?>"><i class="fa fa-money"></i> <?= t('المنح') ?></a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="<?= e(url('downloads:download_center')) ?>">
                        <i class="fa fa-download"></i> <?= t('التحميلات') ?>
                    </a>
                </li>
                
                <li>
                    <a href="<?= e(url('faq:faq_list')) ?>">
                        <i class="fa fa-question-circle"></i> <?= tx('nav', 'الأسئلة الشائعة') ?>
                    </a>
                </li>

                <?php if (is_authenticated()): ?>
                    <?php if (!empty($user['is_staff'])): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">
                                <i class="fa fa-shield"></i> <?= tx('nav', 'لوحة المشرف') ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?= e(url('students:admin_dashboard')) ?>">
                                    <i class="fa fa-dashboard"></i> <?= t('لوحة المشرف') ?>
                                </a></li>
                                <li><a href="<?= e(url('students:admin_applications_list')) ?>">
                                    <i class="fa fa-list"></i> <?= t('إدارة الطلبات') ?>
                                </a></li>
                                <li><a href="<?= e(url('students:admin_applications_list')) ?>?status=pending">
                                    <i class="fa fa-clock-o"></i> <?= t('الطلبات المعلقة') ?>
                                </a></li>
                                <li><a href="<?= e(url('admin:index')) ?>">
                                    <i class="fa fa-cog"></i> <?= t('إعدادات النظام') ?>
                                </a></li>
                                <li class="divider"></li>
                                <?php if (!empty($student_profile)): ?>
                                <li><a href="<?= e(url('students:dashboard')) ?>">
                                    <i class="fa fa-user"></i> <?= t('حسابي كطالب') ?>
                                </a></li>
                                 
                                <li class="divider"></li>
                                <?php endif; ?>
                                <li><a href="<?= e(url('students:logout')) ?>">
                                    <i class="fa fa-sign-out"></i> <?= t('تسجيل الخروج') ?>
                                </a></li>
                            </ul>
                        </li>
                        
                    <?php elseif (!empty($student_profile)): ?>
                   
                        
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">
                                <i class="fa fa-user-circle"></i> <?= e(truncatewords($student_profile['full_name_arabic'], 2)) ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?= e(url('students:dashboard')) ?>">
                                    <i class="fa fa-dashboard"></i> <?= t('لوحة التحكم') ?>
                                </a></li>
                                <li><a href="<?= e(url('students:my_applications')) ?>">
                                    <i class="fa fa-list"></i> <?= t('طلباتي') ?>
                                </a></li>
                                <li><a href="<?= e(url('students:create_application')) ?>">
                                    <i class="fa fa-plus"></i> <?= t('طلب جديد') ?>
                                </a></li>
                                <li><a href="<?= e(url('students:profile_view')) ?>">
                                <i class="fa fa-user"></i> <?= t('معلوماتي الشخصية') ?>
                                </a></li>
                                 <!-- ← جديد! البطاقة الرقمية -->
                                <li><a href="<?= e(url('students:student_card')) ?>"target="_blank">
                                 <i class="fa fa-id-card"></i> <?= t('بطاقتي الرقمية') ?>
                                 </a></li>
                                <li class="divider"></li>
                                <li><a href="<?= e(url('students:logout')) ?>">
                                    <i class="fa fa-sign-out"></i> <?= t('تسجيل الخروج') ?>
                                </a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown notifications-dropdown">
                            <?php $unread_count_nav = $student_profile->get_unread_notifications_count(); ?>
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" style="position: relative;" title="<?= t('الإشعارات') ?>">
                                <i class="fa fa-bell" style="font-size: 18px;"></i>
                                <span class="badge badge-danger notifications-badge" style="position: absolute; top: 5px; inset-inline-start: 8px; font-size: 10px; padding: 2px 5px;<?= $unread_count_nav ? '' : ' display: none;' ?>"><?= e($unread_count_nav) ?></span>
                                <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu notifications-menu" style="width: 280px; max-height: 360px; overflow-y: auto;">
                                <li class="dropdown-header" style="background: #34495e; color: white; font-weight: bold;">
                                    <i class="fa fa-bell"></i> <?= t('الإشعارات') ?>
                                    <span class="badge badge-danger pull-left notifications-count" style="<?= $unread_count_nav ? '' : 'display: none;' ?>"><?= e($unread_count_nav) ?></span>
                                </li>
                                <li class="divider"></li>
                                <?php $recent_notifications = $student_profile->get_recent_notifications(); ?>
                                <?php foreach ($recent_notifications as $notif): ?>
                                <li class="notif-item">
                                    <a href="<?= e($notif['link'] ? link_url($notif['link']) : url('students:notifications_list')) ?>"
                                       style="white-space: normal; padding: 10px 15px; <?php if (empty($notif['is_read'])): ?>background: #f0f8ff; border-inline-start: 3px solid #3498db;<?php endif; ?>">
                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex-shrink: 0;">
                                                <i class="fa <?= e($notif->get_icon()) ?>" style="color: <?= e($notif->get_color()) ?>; font-size: 20px;"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <strong style="display: block; font-size: 13px; color: #2c3e50;">
                                                    <?= e($notif['title']) ?>
                                                    <?php if (empty($notif['is_read'])): ?>
                                                    <span class="badge badge-primary" style="font-size: 9px;"><?= t('جديد') ?></span>
                                                    <?php endif; ?>
                                                </strong>
                                                <small style="display: block; color: #7f8c8d; margin-top: 3px;">
                                                    <?= e(truncatewords($notif['message'], 10)) ?>
                                                </small>
                                                <small style="display: block; color: #95a5a6; margin-top: 3px;">
                                                    <i class="fa fa-clock-o"></i> <?= t('{time} مضت', ['time' => timesince($notif['created_at'])]) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li class="text-center notif-empty" style="padding: 30px;<?= $recent_notifications ? ' display: none;' : '' ?>">
                                    <i class="fa fa-bell-slash" style="font-size: 40px; color: #bdc3c7; display: block; margin-bottom: 10px;"></i>
                                    <span style="color: #7f8c8d;"><?= t('لا توجد إشعارات') ?></span>
                                </li>
                                <li class="divider notif-footer-divider"></li>
                                <li class="text-center notif-footer">
                                    <a href="<?= e(url('students:notifications_list')) ?>" style="font-weight: bold; color: #3498db;">
                                        <i class="fa fa-list"></i> <?= t('عرض كل الإشعارات') ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li>
                        <a href="<?= e(url('students:login')) ?>">
                            <i class="fa fa-sign-in"></i> <?= t('فضاء الطالب') ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="<?= e(url('contact:contact')) ?>">
                        <i class="fa fa-envelope"></i> <?= t('إتصل بنا') ?>
                    </a>
                </li>
            </ul>
            
            <form class="navbar-form navbar-left" role="search" method="get" action="<?= e(url('search:search')) ?>">
                <!-- اختيار اللغة: نفس الصفحة بلغة أخرى -->
                <div class="btn-group lang-switch">
                    <button type="button" class="btn btn-lang dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?= t('اللغة') ?>">
                        <i class="fa fa-globe"></i> <?= e(strtoupper(lang())) ?> <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach (languages() as $code => $label): ?>
                            <li class="<?= $code === lang() ? 'active' : '' ?>">
                                <a href="<?= e(lang_url($code)) ?>" hreflang="<?= e($code) ?>" lang="<?= e($code) ?>"><?= e($label) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= t('ابحث في الموقع...') ?>" style="width: 250px;">
                </div>
                <button type="submit" class="btn btn-default">
                    <i class="fa fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<style>
    .navbar-right {
        float: right !important; float: inline-start !important;
    }
    
    .navbar-nav > li {
        float: right; float: inline-start;
    }
    
    .dropdown-menu {
        inset-inline-start: 0;
        inset-inline-end: auto;
    }
    
    .navbar-form.navbar-left {
        float: left !important; float: inline-end !important;
        margin-inline-end: 15px;
    }

    /* زر اختيار اللغة بجانب البحث */
    .navbar-form .btn-lang {
        background: transparent;
        color: #ecf0f1;
        border: 1px solid rgba(255,255,255,0.35);
    }

    .navbar-form .btn-lang:hover,
    .navbar-form .btn-lang:focus,
    .navbar-form .open > .btn-lang {
        background: rgba(255,255,255,0.12);
        color: white;
    }

    .lang-switch .dropdown-menu {
        min-width: 140px;
    }

    /* الكلمات الفرنسية والإنجليزية أطول: حشو أقل حتى تبقى القائمة في سطر واحد كالعربية */
    @media (min-width: 768px) {
        html[dir="ltr"] .navbar-nav > li > a {
            padding-left: 11px;
            padding-right: 11px;
        }
    }
    
    .navbar-form .form-control {
        background: rgba(255,255,255,0.9);
        border: none;
        color: #333;
    }
    
    .navbar-form .form-control:focus {
        background: white;
        box-shadow: 0 0 5px rgba(255,255,255,0.5);
    }
    
    .navbar-form .btn-default {
        background: #8cc040;
        color: white;
        border: none;
    }
    
    .navbar-form .btn-default:hover {
        background: #7ab030;
    }
    
    /* الإشعارات */
    .notifications-dropdown .dropdown-toggle {
        position: relative;
        padding: 15px 20px;
    }
    
    .notifications-badge {
        position: absolute;
        top: 8px;
        inset-inline-start: 10px;
        background: #e74c3c;
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 3px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
        line-height: 1;
    }
    
    .dropdown-menu-notifications {
        width: 360px;
        max-height: 500px;
        padding: 0;
        border-radius: 8px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        border: none;
        overflow: hidden;
        inset-inline-start: 0;
        inset-inline-end: auto;
    }
    
    .notifications-header {
        padding: 12px 15px;
        background: #34495e;
        color: white;
        font-weight: bold;
        border-bottom: 2px solid #2c3e50;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notifications-header .unread-count {
        background: #e74c3c;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
    }
    
    .notifications-list-wrapper {
        padding: 0;
    }
    
    .notifications-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 15px;
        border-bottom: 1px solid #ecf0f1;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
    }
    
    .notification-item:hover {
        background: #f8f9fa;
        text-decoration: none;
    }
    
    .notification-item.unread {
        background: #f0f8ff;
        border-inline-start: 3px solid #3498db;
    }
    
    .notification-item.unread:hover {
        background: #e8f4fd;
    }
    
    .notif-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notif-icon i {
        font-size: 20px;
    }
    
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    
    .notif-title {
        font-size: 13px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    
    .notif-message {
        font-size: 12px;
        color: #7f8c8d;
        line-height: 1.4;
        margin-bottom: 4px;
    }
    
    .notif-time {
        font-size: 11px;
        color: #95a5a6;
    }
    
    .new-label {
        display: inline-block;
        background: #e74c3c;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: bold;
        margin-inline-start: 4px;
    }
    
    .notifications-footer a {
        display: block;
        padding: 12px;
        text-align: center;
        background: #f8f9fa;
        color: #3498db;
        font-weight: bold;
        border-top: 1px solid #ecf0f1;
        text-decoration: none;
    }
    
    .notifications-footer a:hover {
        background: #ecf0f1;
        text-decoration: none;
    }
    
    .notifications-empty {
        padding: 40px 20px;
        text-align: center;
        color: #95a5a6;
    }
    
    .notifications-empty i {
        font-size: 48px;
        display: block;
        margin-bottom: 10px;
        opacity: 0.3;
    }
    
    .notifications-empty p {
        margin: 0;
        font-size: 14px;
    }
    
    .notifications-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .notifications-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .notifications-list::-webkit-scrollbar-thumb {
        background: #bdc3c7;
        border-radius: 3px;
    }
    
    .notifications-list::-webkit-scrollbar-thumb:hover {
        background: #95a5a6;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
    }
    
    .notifications-badge.new-notification {
        animation: pulse 0.5s ease 3;
    }
    
    @media (max-width: 767px) {
        .navbar-nav {
            float: none !important;
            margin: 0;
        }
        
        .navbar-nav > li {
            float: none;
        }
        
        .dropdown-menu {
            position: static;
            float: none;
            width: auto;
        }
        
        .navbar-form.navbar-left {
            float: none !important;
            margin: 10px 0;
        }
        
        .navbar-form .form-control {
            width: 100% !important;
        }
        
        .dropdown-menu-notifications {
            width: 100%;
            max-width: 100vw;
            inset-inline-end: 0;
            inset-inline-start: 0;
            border-radius: 0;
        }
        
        .notification-item {
            padding: 10px;
        }
        
        .notif-icon {
            width: 35px;
            height: 35px;
        }
        
        .notif-icon i {
            font-size: 18px;
        }
    }

    /* إصلاح القائمة المنسدلة للإشعارات */
.dropdown-menu {
    border: 1px solid rgba(0,0,0,.15);
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}

.dropdown-menu > li > a {
    display: block;
    padding: 3px 20px;
    clear: both;
    font-weight: normal;
    line-height: 1.42857143;
    color: #333;
    white-space: nowrap;
}

.dropdown-menu > li > a:hover,
.dropdown-menu > li > a:focus {
    text-decoration: none;
    color: #262626;
    background-color: #f5f5f5;
}

.dropdown-header {
    display: block;
    padding: 10px 20px;
    font-size: 12px;
    line-height: 1.42857143;
    color: #777;
}

.badge-danger {
    background-color: #e74c3c;
}

.badge-primary {
    background-color: #3498db;
}

    /* إصلاح موضع قائمة الإشعارات */
    .navbar-nav > li > .dropdown-menu {
        inset-inline-start: 0;
        inset-inline-end: auto;
    }
    
    .dropdown-menu-right {
        inset-inline-start: 0;
        inset-inline-end: auto;
    }
    
    /* للشاشات الصغيرة */
    @media (max-width: 767px) {
        .navbar-nav .dropdown-menu {
            position: static;
            float: none;
            width: auto;
            margin-top: 0;
            background-color: transparent;
            border: 0;
            box-shadow: none;
        }
    }
</style>
