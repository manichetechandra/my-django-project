<?php
/**
 * جدول الروابط — نفس روابط مشروع Django (config/urls.py + urls.py لكل تطبيق)
 * الصيغة: $router->get(المسار, الدالة, 'الاسم')
 * الاسم يُستعمل لتوليد الروابط في القوالب:  url('news:news_detail', $slug)
 *
 * @var Router $router
 */

// الصفحة الرئيسية (homepage/urls.py)
$router->get('/', 'homepage_index', 'homepage:index');
$router->get('/director-word/', 'homepage_director_word', 'homepage:director_word');
$router->get('/organization/', 'homepage_organization', 'homepage:organization');
$router->get('/history/', 'homepage_history', 'homepage:history');

// الأخبار
$router->get('/news/', 'news_list', 'news:news_list');
$router->get('/news/{slug}/', 'news_detail', 'news:news_detail');

// النشاطات
$router->get('/activities/', 'activity_list', 'activities:activity_list');
$router->get('/activities/{slug}/', 'activity_detail', 'activities:activity_detail');

// المصالح
$router->get('/departements/', 'departement_list', 'departements:departement_list');
$router->get('/departements/{slug}/', 'departement_detail', 'departements:departement_detail');

// الإقامات
$router->get('/cities/', 'cite_list', 'cities:cite_list');
$router->get('/cities/{slug}/', 'cite_detail', 'cities:cite_detail');

// الخدمات
$router->get('/services/', 'service_list', 'services:service_list');
$router->get('/services/type/{service_type}/', 'service_by_type', 'services:service_by_type');
$router->get('/services/{slug}/', 'service_detail', 'services:service_detail');

// اتصل بنا
$router->any('/contact/', 'contact_view', 'contact:contact');

// مركز التحميلات
$router->get('/downloads/', 'download_center', 'downloads:download_center');
$router->get('/downloads/category/{category}/', 'download_by_category', 'downloads:download_by_category');
$router->get('/downloads/file/{slug}/', 'download_file', 'downloads:download_file');

// الأسئلة الشائعة
$router->get('/faq/', 'faq_list', 'faq:faq_list');
$router->get('/faq/{faq_id:int}/', 'faq_detail', 'faq:faq_detail');

// البحث
$router->get('/search/', 'search_view', 'search:search');

// فضاء الطالب (students/urls.py)
$router->any('/students/register/', 'student_register', 'students:register');
$router->any('/students/login/', 'student_login', 'students:login');
$router->any('/students/logout/', 'student_logout', 'students:logout');
$router->get('/students/dashboard/', 'student_dashboard', 'students:dashboard');
$router->any('/students/applications/create/', 'student_create_application', 'students:create_application');
$router->any('/students/applications/create/{app_type_id:int}/', 'student_create_application', 'students:create_application_type');
$router->get('/students/applications/', 'student_my_applications', 'students:my_applications');
$router->get('/students/applications/print-all/', 'student_print_all_applications', 'students:print_all_applications');
$router->get('/students/applications/export-all/', 'student_export_all_pdf', 'students:export_all_pdf');
$router->get('/students/applications/{pk:int}/', 'student_application_detail', 'students:application_detail');
$router->get('/students/applications/{pk:int}/print/', 'student_application_print', 'students:application_print');
$router->get('/students/applications/{pk:int}/pdf/', 'student_download_application_pdf', 'students:download_application_pdf');
$router->post('/students/applications/{pk:int}/cancel/', 'student_cancel_application', 'students:cancel_application');
$router->get('/students/documents/{pk:int}/download/', 'student_document_download', 'students:document_download');
$router->get('/students/api/notifications/', 'student_notifications_api', 'students:notifications_api');
$router->get('/students/notifications/', 'student_notifications_list', 'students:notifications_list');
$router->post('/students/notifications/{pk:int}/read/', 'student_mark_notification_read', 'students:mark_notification_read');
$router->post('/students/notifications/mark-all-read/', 'student_mark_all_notifications_read', 'students:mark_all_notifications_read');
$router->post('/students/notifications/{pk:int}/delete/', 'student_delete_notification', 'students:delete_notification');
$router->post('/students/notifications/delete-read/', 'student_delete_read_notifications', 'students:delete_read_notifications');
$router->get('/students/profile/', 'student_profile_view', 'students:profile_view');
$router->any('/students/profile/edit/', 'student_profile_edit', 'students:profile_edit');
$router->any('/students/profile/change-password/', 'student_change_password', 'students:change_password');
$router->post('/students/profile/delete-image/', 'student_delete_profile_image', 'students:delete_profile_image');
$router->get('/students/student-card/', 'student_card', 'students:student_card');

// لوحة الموظفين لمتابعة الطلبات
$router->get('/students/admin/dashboard/', 'staff_dashboard', 'students:admin_dashboard');
$router->any('/students/admin/applications/', 'staff_applications_list', 'students:admin_applications_list');
$router->get('/students/admin/applications/export/excel/', 'staff_export_applications_excel', 'students:export_applications_excel');
$router->any('/students/admin/applications/{pk:int}/', 'staff_application_detail', 'students:admin_application_detail');

// لوحة الإدارة العامة (بديل Django admin)
$router->get('/admin/', 'admin_index', 'admin:index');
$router->any('/admin/login/', 'admin_login', 'admin:login');
$router->get('/admin/logout/', 'admin_logout', 'admin:logout');
$router->any('/admin/password/', 'admin_password', 'admin:password');
$router->post('/admin/translate/', 'admin_translate', 'admin:translate');          // زر «ترجمة تلقائية» (AJAX)
$router->any('/admin/translate-all/', 'admin_translate_all', 'admin:translate_all'); // ترجمة المحتوى القديم
$router->any('/admin/{model}/', 'admin_list', 'admin:list');
$router->any('/admin/{model}/add/', 'admin_add', 'admin:add');
$router->any('/admin/{model}/{id:int}/', 'admin_edit', 'admin:edit');
$router->any('/admin/{model}/{id:int}/delete/', 'admin_delete', 'admin:delete');
