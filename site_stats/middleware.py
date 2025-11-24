from .models import SiteVisit, SiteStatistics

class VisitorTrackingMiddleware:
    """Middleware لتتبع الزوار"""
    
    def __init__(self, get_response):
        self.get_response = get_response
    
    def __call__(self, request):
        # تسجيل الزيارة
        self.track_visitor(request)
        
        response = self.get_response(request)
        return response
    
    def track_visitor(self, request):
        """تسجيل زيارة جديدة"""
        
        # الحصول على IP
        ip = self.get_client_ip(request)
        
        # معلومات المتصفح
        user_agent = request.META.get('HTTP_USER_AGENT', '')
        
        # الصفحة المزارة
        page = request.path
        
        # تسجيل الزيارة
        try:
            SiteVisit.objects.create(
                ip_address=ip,
                user_agent=user_agent,
                page_visited=page
            )
            
            # تحديث الإحصائيات
            stats = SiteStatistics.get_stats()
            stats.increment_page_view()
            
            # إذا كان زائر جديد
            from django.utils import timezone
            from datetime import timedelta
            
            yesterday = timezone.now() - timedelta(days=1)
            is_new_visitor = not SiteVisit.objects.filter(
                ip_address=ip,
                visited_at__gte=yesterday
            ).exclude(page_visited=page).exists()
            
            if is_new_visitor:
                stats.increment_visitor()
        
        except Exception as e:
            print(f"خطأ في تسجيل الزيارة: {e}")
    
    def get_client_ip(self, request):
        """الحصول على IP الزائر"""
        x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
        if x_forwarded_for:
            ip = x_forwarded_for.split(',')[0]
        else:
            ip = request.META.get('REMOTE_ADDR')
        return ip