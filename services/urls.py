from django.urls import path
from . import views

app_name = 'services'

urlpatterns = [
    # قائمة كل الخدمات
    path('', views.service_list, name='service_list'),
    
    # قائمة حسب النوع
    path('type/<str:service_type>/', views.service_by_type, name='service_by_type'),
    
    # تفاصيل خدمة محددة
    path('<slug:slug>/', views.service_detail, name='service_detail'),
]