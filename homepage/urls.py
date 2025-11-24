from django.urls import path
from . import views

app_name = 'homepage'

urlpatterns = [
    path('', views.index, name='index'),
    path('director-word/', views.director_word_page, name='director_word'),
    path('organization/', views.organization_structure, name='organization'),
     path('history/', views.directorate_history, name='history'),
]