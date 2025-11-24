from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('admin/', admin.site.urls),
    path('', include('homepage.urls')),  # ← الصفحة الرئيسية
    path('news/', include('news.urls')),
    path('activities/', include('activities.urls')),
    path('departements/', include('departements.urls')),
    path('cities/', include('cities.urls')),
    path('services/', include('services.urls')),
    path('contact/', include('contact.urls')),
    path('downloads/', include('downloads.urls')),
    path('faq/', include('faq.urls')),
    path('search/', include('search.urls')), 
    path('students/', include('students.urls')), 
   
]

# لعرض الصور أثناء التطوير
if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)