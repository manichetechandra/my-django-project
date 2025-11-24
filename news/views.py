from django.shortcuts import render, get_object_or_404
from django.core.paginator import Paginator
from .models import News


def news_list(request):
    """
    قائمة كل الأخبار المنشورة
    """
    news_list = News.objects.filter(status='published').order_by('-published_date')
    
    # Pagination - 9 أخبار في كل صفحة
    paginator = Paginator(news_list, 9)
    page_number = request.GET.get('page')
    news = paginator.get_page(page_number)
    
    context = {
        'news': news,
        'page_title': 'الأخبار',
    }
    
    return render(request, 'news/news_list.html', context)


def news_detail(request, slug):
    """
    تفاصيل خبر واحد
    """
    news = get_object_or_404(News, slug=slug, status='published')
    
    # زيادة عدد المشاهدات
    news.views_count += 1
    news.save(update_fields=['views_count'])
    
    # أخبار ذات صلة (آخر 3 أخبار)
    related_news = News.objects.filter(
        status='published'
    ).exclude(id=news.id).order_by('-published_date')[:3]
    
    context = {
        'news': news,
        'related_news': related_news,
    }
    
    return render(request, 'news/news_detail.html', context)