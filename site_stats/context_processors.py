from .models import SiteStatistics
from news.models import News
from services.models import Service
from downloads.models import DownloadFile
from faq.models import FAQ
from cities.models import Cite

def site_statistics(request):
    """إضافة الإحصائيات لكل الصفحات"""
    
    stats = SiteStatistics.get_stats()
    
    return {
        'site_stats': {
            'total_visitors': stats.total_visitors,
            'total_page_views': stats.total_page_views,
            'total_news': News.objects.filter(status='published').count(),
            'total_services': Service.objects.filter(is_active=True).count(),
            'total_downloads': DownloadFile.objects.filter(is_active=True).count(),
            'total_faqs': FAQ.objects.filter(is_active=True).count(),
            'total_cites': Cite.objects.filter(is_active=True).count(),
        }
    }