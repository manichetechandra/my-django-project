from django.shortcuts import render, get_object_or_404
from .models import Departement

def departement_list(request):
    """قائمة جميع الأقسام"""
    departements = Departement.objects.filter(is_active=True).order_by('order', 'name')
    
    context = {
        'departements': departements,
        'page_title': 'أقسام المديرية'
    }
    return render(request, 'departements/departement_list.html', context)

def departement_detail(request, slug):
    """تفاصيل القسم"""
    departement = get_object_or_404(Departement, slug=slug, is_active=True)
    
    # الأقسام الأخرى
    other_departements = Departement.objects.filter(
        is_active=True
    ).exclude(id=departement.id).order_by('?')[:3]
    
    context = {
        'departement': departement,
        'other_departements': other_departements,
        'page_title': departement.name
    }
    return render(request, 'departements/departement_detail.html', context)