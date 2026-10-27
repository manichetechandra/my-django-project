<?php
$error_code = 404;
$error_icon = 'fa-search';
$error_title = 'الصفحة غير موجودة';
$error_text = $message ?: 'عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.';
include view_path('errors/_error');
