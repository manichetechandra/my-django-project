<?php
$error_code = 404;
$error_icon = 'fa-search';
$error_title = __('الصفحة غير موجودة');
$error_text = $message ?: __('عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.');
include view_path('errors/_error');
