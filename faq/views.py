from django.shortcuts import render, get_object_or_404
from django.db.models import Q
from .models import FAQCategory, FAQ

def faq_list(request):
    """صفحة الأسئلة الشائعة"""
    
    # البحث
    search_query = request.GET.get('q', '')
    
    # جلب التصنيفات النشطة
    categories = FAQCategory.objects.filter(is_active=True)
    
    # جلب الأسئلة النشطة
    faqs = FAQ.objects.filter(is_active=True)
    
    # البحث في الأسئلة والأجوبة
    if search_query:
        faqs = faqs.filter(
            Q(question__icontains=search_query) |
            Q(answer__icontains=search_query)
        )
    
    # تجميع حسب التصنيف
    faqs_by_category = {}
    for category in categories:
        category_faqs = faqs.filter(category=category)
        if category_faqs.exists():
            faqs_by_category[category] = category_faqs
    
    # الأكثر مشاهدة
    popular_faqs = FAQ.objects.filter(is_active=True).order_by('-views_count')[:5]
    
    # إحصائيات
    total_faqs = faqs.count()
    total_categories = len(faqs_by_category)
    
    context = {
        'categories': categories,
        'faqs_by_category': faqs_by_category,
        'popular_faqs': popular_faqs,
        'total_faqs': total_faqs,
        'total_categories': total_categories,
        'search_query': search_query,
        'page_title': 'الأسئلة الشائعة',
    }
    
    return render(request, 'faq/faq_list.html', context)


def faq_detail(request, faq_id):
    """تفاصيل سؤال محدد"""
    faq = get_object_or_404(FAQ, id=faq_id, is_active=True)
    
    # زيادة عداد المشاهدات
    faq.increment_views()
    
    # أسئلة ذات صلة (من نفس التصنيف)
    related_faqs = FAQ.objects.filter(
        category=faq.category,
        is_active=True
    ).exclude(id=faq.id)[:3]
    
    context = {
        'faq': faq,
        'related_faqs': related_faqs,
        'page_title': faq.question,
    }
    
    return render(request, 'faq/faq_detail.html', context)