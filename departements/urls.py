from django.urls import path
from . import views

app_name = 'departements'  # ← مهم جداً!

urlpatterns = [
    path('', views.departement_list, name='departement_list'),
    path('<slug:slug>/', views.departement_detail, name='departement_detail'),
]