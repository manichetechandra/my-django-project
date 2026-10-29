<?php
$error_code = 403;
$error_icon = 'fa-lock';
$error_title = __('غير مسموح');
$error_text = $message ?: __('ليس لديك صلاحية للوصول إلى هذه الصفحة.');
include view_path('errors/_error');
