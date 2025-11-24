from django.db import models
from django.utils import timezone

class SiteVisit(models.Model):
    """تسجيل زيارات الموقع"""
    
    ip_address = models.GenericIPAddressField(
        verbose_name="عنوان IP"
    )
    
    user_agent = models.TextField(
        blank=True,
        null=True,
        verbose_name="معلومات المتصفح"
    )
    
    page_visited = models.CharField(
        max_length=255,
        blank=True,
        null=True,
        verbose_name="الصفحة المزارة"
    )
    
    visited_at = models.DateTimeField(
        default=timezone.now,
        verbose_name="وقت الزيارة"
    )
    
    class Meta:
        verbose_name = "زيارة"
        verbose_name_plural = "الزيارات"
        ordering = ['-visited_at']
    
    def __str__(self):
        return f"{self.ip_address} - {self.visited_at.strftime('%Y-%m-%d %H:%M')}"


class SiteStatistics(models.Model):
    """إحصائيات الموقع"""
    
    total_visitors = models.PositiveIntegerField(
        default=0,
        verbose_name="إجمالي الزوار"
    )
    
    total_page_views = models.PositiveIntegerField(
        default=0,
        verbose_name="إجمالي مشاهدات الصفحات"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="آخر تحديث"
    )
    
    class Meta:
        verbose_name = "إحصائية الموقع"
        verbose_name_plural = "إحصائيات الموقع"
    
    def __str__(self):
        return f"الإحصائيات - {self.total_visitors} زائر"
    
    @classmethod
    def get_stats(cls):
        """الحصول على الإحصائيات"""
        stats, created = cls.objects.get_or_create(pk=1)
        return stats
    
    def increment_visitor(self):
        """زيادة عدد الزوار"""
        self.total_visitors += 1
        self.save(update_fields=['total_visitors', 'updated_at'])
    
    def increment_page_view(self):
        """زيادة عدد مشاهدات الصفحات"""
        self.total_page_views += 1
        self.save(update_fields=['total_page_views', 'updated_at'])