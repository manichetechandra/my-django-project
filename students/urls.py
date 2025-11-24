from django.urls import path
from . import views

app_name = 'students'

urlpatterns = [
    # ========================================
    # روابط الطلبة
    # ========================================
    
    # التسجيل والدخول
    path('register/', views.student_register, name='register'),
    path('login/', views.student_login, name='login'),
    path('logout/', views.student_logout, name='logout'),
    
    # لوحة التحكم
    path('dashboard/', views.student_dashboard, name='dashboard'),
    
    # الطلبات
    path('applications/create/', views.create_application, name='create_application'),
    path('applications/create/<int:app_type_id>/', views.create_application, name='create_application_type'),
    path('applications/', views.my_applications, name='my_applications'),
    path('applications/<int:pk>/', views.application_detail, name='application_detail'),
    path('applications/<int:pk>/print/', views.application_print, name='application_print'),
    path('applications/<int:pk>/cancel/', views.cancel_application, name='cancel_application'),
    path('api/notifications/', views.notifications_api, name='notifications_api'),
    
    # ========================================
    # روابط المشرف/الإدارة
    # admin/ هو بداية كل روابط المشرف
    # ========================================
    
    # لوحة تحكم المشرف
    path('admin/dashboard/', views.admin_dashboard, name='admin_dashboard'),
    
    # إدارة الطلبات
    path('admin/applications/', views.admin_applications_list, name='admin_applications_list'),
    path('admin/applications/<int:pk>/', views.admin_application_detail, name='admin_application_detail'),
    path('admin/applications/export/excel/', views.export_applications_excel, name='export_applications_excel'),




       # ========================================
    # الإشعارات
    # ========================================
    path('notifications/', views.notifications_list, name='notifications_list'),
    path('notifications/<int:pk>/read/', views.mark_notification_read, name='mark_notification_read'),
    path('notifications/mark-all-read/', views.mark_all_notifications_read, name='mark_all_notifications_read'),
    path('notifications/<int:pk>/delete/', views.delete_notification, name='delete_notification'),
    path('notifications/delete-read/', views.delete_read_notifications, name='delete_read_notifications'),


     # ========================================
    # إدارة الحساب الشخصي
    # ========================================
    path('profile/', views.profile_view, name='profile_view'),
    path('profile/edit/', views.profile_edit, name='profile_edit'),
    path('profile/change-password/', views.change_password, name='change_password'),
    path('profile/delete-image/', views.delete_profile_image, name='delete_profile_image'),



     
    # ========================================
    # الطباعة والتصدير
    # ========================================
    path('applications/<int:application_id>/print/', 
         views.print_application, 
         name='print_application'),
    
    path('applications/<int:application_id>/pdf/', 
         views.download_application_pdf, 
         name='download_application_pdf'),
    
    path('applications/print-all/', 
     views.print_all_applications, 
     name='print_all_applications'),
    
    path('applications/export-all/', 
         views.export_all_pdf, 
         name='export_all_pdf'),
    
    path('student-card/', 
         views.student_card, 
         name='student_card'),
]