from django.shortcuts import render
from .models import Slider, Statistics, DirectorWord, OrganizationStructure, DirectorateHistory
from news.models import News
from cities.models import Cite
from departements.models import Departement
from activities.models import Activity


def index(request):
    """الصفحة الرئيسية"""
    
    # السلايدر (4 صور نشطة)
    slider_images = Slider.objects.filter(is_active=True).order_by('order')[:4]
    
    # الأخبار العاجلة (للشريط العلوي)
    ticker_news = News.objects.filter(
        status='published',
        show_in_ticker=True
    ).order_by('order')[:2]
    
    # آخر الأخبار (للصندوق الجانبي المتحرك)
    latest_news = News.objects.filter(
        status='published'
    ).order_by('-published_date')[:5]
    
    # الأخبار المميزة (للصفحة الرئيسية)
    featured_news = News.objects.filter(
        status='published',
        is_featured=True
    ).order_by('-published_date')[:3]
    
    # النشاطات المميزة
    featured_activities = Activity.objects.filter(
        is_active=True,
        is_featured=True
    ).order_by('-date')[:3]
    
    # الإقامات الجامعية (12 إقامة)
    cites_list = Cite.objects.filter(is_active=True).order_by('order')[:12]
    
    # الأقسام (4 أقسام)
    departements_list = Departement.objects.filter(is_active=True).order_by('order')[:4]
    
    # الإحصائيات
    statistics = Statistics.objects.filter(is_active=True).order_by('order')[:4]
    
    # كلمة المدير
    try:
        director_word = DirectorWord.objects.filter(is_active=True).latest('created_at')
    except DirectorWord.DoesNotExist:
        director_word = None
    
    context = {
        'slider_images': slider_images,
        'ticker_news': ticker_news,
        'latest_news': latest_news,
        'featured_news': featured_news,
        'featured_activities': featured_activities,
        'cites_list': cites_list,
        'departements_list': departements_list,
        'statistics': statistics,
        'director_word': director_word,
    }
    
    return render(request, 'homepage/index.html', context)


def director_word_page(request):
    """صفحة كلمة المدير"""
    try:
        director_word = DirectorWord.objects.filter(is_active=True).latest('created_at')
    except DirectorWord.DoesNotExist:
        director_word = None
    
    context = {
        'director_word': director_word,
        'page_title': 'كلمة المدير'
    }
    
    return render(request, 'homepage/director_word.html', context)

def organization_structure(request):
    """صفحة الهيكل التنظيمي"""
    try:
        organization = OrganizationStructure.objects.filter(is_active=True).first()
    except OrganizationStructure.DoesNotExist:
        organization = None
    
    context = {
        'organization': organization,
        'page_title': 'الهيكل التنظيمي'
    }
    return render(request, 'homepage/organization_structure.html', context)



def directorate_history(request):
    """صفحة تاريخ المديرية"""
    try:
        history = DirectorateHistory.objects.filter(is_active=True).first()
    except DirectorateHistory.DoesNotExist:
        history = None
    
    context = {
        'history': history,
        'page_title': 'تاريخ المديرية'
    }
    return render(request, 'homepage/directorate_history.html', context)