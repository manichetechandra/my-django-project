from django.urls import path
from . import views

app_name = 'downloads'

urlpatterns = [
    # الصفحة الرئيسية
    path('', views.download_center, name='download_center'),
    
    # حسب التصنيف
    path('category/<str:category>/', views.download_by_category, name='download_by_category'),
    
    # تحميل ملف
    path('file/<slug:slug>/', views.download_file, name='download_file'),
]