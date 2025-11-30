from django.shortcuts import render, get_object_or_404
from django.http import FileResponse, Http404
from django.db.models import Q
from .models import DownloadFile

def download_center(request):
    """مركز التحميلات"""
    # الحصول على التصنيف من URL
    category = request.GET.get('category', None)
    search_query = request.GET.get('q', None)
    
    # جلب الملفات النشطة
    files = DownloadFile.objects.filter(is_active=True)
    
    # فلترة حسب التصنيف
    if category:
        files = files.filter(category=category)
    
    # بحث
    if search_query:
        files = files.filter(
            Q(title__icontains=search_query) |
            Q(description__icontains=search_query)
        )
    
    # تجميع حسب التصنيف
    categories_data = []
    for cat_code, cat_name in DownloadFile.CATEGORY_CHOICES:
        cat_files = files.filter(category=cat_code)
        if cat_files.exists():
            categories_data.append({
                'code': cat_code,
                'name': cat_name,
                'count': cat_files.count(),
                'files': cat_files[:3]  # أول 3 فقط للعرض
            })
    
    # الملفات المميزة
    featured_files = files.filter(featured=True)[:4]
    
    # إحصائيات
    total_files = files.count()
    total_downloads = sum(f.downloads_count for f in files)
    
    context = {
        'files': files,
        'categories_data': categories_data,
        'featured_files': featured_files,
        'total_files': total_files,
        'total_downloads': total_downloads,
        'current_category': category,
        'search_query': search_query,
        'page_title': 'مركز التحميلات',
    }
    
    return render(request, 'downloads/download_center.html', context)


def download_file(request, slug):
    """تحميل ملف"""
    file_obj = get_object_or_404(DownloadFile, slug=slug, is_active=True)
    
    # زيادة عداد التحميلات
    file_obj.increment_downloads()
    
    try:
        # فتح الملف
        response = FileResponse(file_obj.file.open('rb'), as_attachment=True)
        response['Content-Disposition'] = f'attachment; filename="{file_obj.file.name}"'
        return response
    except Exception as e:
        raise Http404("الملف غير موجود")


def download_by_category(request, category):
    """عرض ملفات حسب التصنيف"""
    files = DownloadFile.objects.filter(
        category=category,
        is_active=True
    )
    
    # اسم التصنيف
    category_name = dict(DownloadFile.CATEGORY_CHOICES).get(category, 'ملفات')
    
    context = {
        'files': files,
        'category': category,
        'page_title': category_name,
    }
    
    return render(request, 'downloads/download_by_category.html', context)