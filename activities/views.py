from django.shortcuts import render, get_object_or_404
from django.core.paginator import Paginator
from django.db.models import Q
from .models import Activity, ActivityImage

def activity_list(request):
    """قائمة جميع النشاطات"""
    activities_list = Activity.objects.filter(is_active=True).order_by('-date')
    
    # التصفية حسب النوع
    activity_type = request.GET.get('type')
    if activity_type:
        activities_list = activities_list.filter(activity_type=activity_type)
    
    # البحث
    search_query = request.GET.get('q')
    if search_query:
        activities_list = activities_list.filter(
            Q(title__icontains=search_query) |
            Q(description__icontains=search_query) |
            Q(location__icontains=search_query)
        )
    
    # الترقيم
    paginator = Paginator(activities_list, 9)  # 9 نشاطات لكل صفحة
    page = request.GET.get('page')
    activities = paginator.get_page(page)
    
    # أنواع النشاطات للفلترة
    activity_types = Activity.ACTIVITY_TYPES
    
    context = {
        'activities': activities,
        'activity_types': activity_types,
        'selected_type': activity_type,
        'search_query': search_query,
        'page_title': 'النشاطات الثقافية والرياضية'
    }
    return render(request, 'activities/activity_list.html', context)

def activity_detail(request, slug):
    """تفاصيل النشاط"""
    activity = get_object_or_404(Activity, slug=slug, is_active=True)
    
    # زيادة عدد المشاهدات
    activity.views_count += 1
    activity.save(update_fields=['views_count'])
    
    # النشاطات ذات الصلة (نفس النوع)
    related_activities = Activity.objects.filter(
        is_active=True,
        activity_type=activity.activity_type
    ).exclude(id=activity.id).order_by('-date')[:4]
    
    # صور النشاط الإضافية
    gallery_images = activity.images.all().order_by('order')
    
    context = {
        'activity': activity,
        'related_activities': related_activities,
        'gallery_images': gallery_images,
        'page_title': activity.title
    }
    return render(request, 'activities/activity_detail.html', context)