from django.shortcuts import render, get_object_or_404
from django.core.paginator import Paginator
from .models import Cite, CiteImage

def cite_list(request):
    """قائمة جميع الإقامات"""
    cites_list = Cite.objects.filter(is_active=True).order_by('order', 'name')
    
    # الترقيم (Pagination)
    paginator = Paginator(cites_list, 12)  # 12 إقامة لكل صفحة
    page = request.GET.get('page')
    cites = paginator.get_page(page)
    
    context = {
        'cites': cites,
        'page_title': 'الإقامات الجامعية'
    }
    return render(request, 'cities/cite_list.html', context)

def cite_detail(request, slug):
    """تفاصيل الإقامة"""
    cite = get_object_or_404(Cite, slug=slug, is_active=True)
    
    # الإقامات الأخرى
    other_cites = Cite.objects.filter(
        is_active=True
    ).exclude(id=cite.id).order_by('?')[:4]
    
    # صور الإقامة الإضافية
    gallery_images = cite.images.all().order_by('order')
    
    context = {
        'cite': cite,
        'other_cites': other_cites,
        'gallery_images': gallery_images,
        'page_title': cite.name
    }
    return render(request, 'cities/cite_detail.html', context)