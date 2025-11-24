from django.shortcuts import render, get_object_or_404
from .models import Service

def service_list(request):
    """عرض كل الخدمات"""
    services = Service.objects.filter(is_active=True)
    
    # تجميع حسب النوع
    transport_services = services.filter(service_type='transport')
    housing_services = services.filter(service_type='housing')
    catering_services = services.filter(service_type='catering')
    scholarship_services = services.filter(service_type='scholarship')
    
    context = {
        'services': services,
        'transport_services': transport_services,
        'housing_services': housing_services,
        'catering_services': catering_services,
        'scholarship_services': scholarship_services,
        'page_title': 'الخدمات الجامعية',
    }
    return render(request, 'services/service_list.html', context)


def service_by_type(request, service_type):
    """عرض خدمات حسب النوع"""
    services = Service.objects.filter(
        service_type=service_type,
        is_active=True
    )
    
    # عنوان الصفحة حسب النوع
    type_names = {
        'transport': 'خدمات النقل',
        'housing': 'خدمات الإيواء',
        'catering': 'خدمات الإطعام',
        'scholarship': 'المنح والإعانات',
    }
    
    context = {
        'services': services,
        'service_type': service_type,
        'page_title': type_names.get(service_type, 'الخدمات الجامعية'),
    }
    return render(request, 'services/service_by_type.html', context)


def service_detail(request, slug):
    """تفاصيل خدمة محددة"""
    service = get_object_or_404(Service, slug=slug, is_active=True)
    
    # خدمات ذات صلة (نفس النوع)
    related_services = Service.objects.filter(
        service_type=service.service_type,
        is_active=True
    ).exclude(id=service.id)[:3]
    
    context = {
        'service': service,
        'related_services': related_services,
        'page_title': service.title,
    }
    return render(request, 'services/service_detail.html', context)