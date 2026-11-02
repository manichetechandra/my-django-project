<?php
$error_code = 403;
$error_icon = 'fa-lock';
$error_title = 'غير مسموح';
$error_text = $message ?: 'ليس لديك صلاحية للوصول إلى هذه الصفحة.';
include view_path('errors/_error');
