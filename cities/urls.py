from django.urls import path
from . import views

app_name = 'cities'

urlpatterns = [
    path('', views.cite_list, name='cite_list'),
    path('<slug:slug>/', views.cite_detail, name='cite_detail'),
]