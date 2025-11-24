from django.urls import path
from . import views

app_name = 'faq'

urlpatterns = [
    path('', views.faq_list, name='faq_list'),
    path('<int:faq_id>/', views.faq_detail, name='faq_detail'),
]