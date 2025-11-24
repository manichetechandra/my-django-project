from django.contrib import admin
from django.utils.html import format_html
from .models import SiteVisit, SiteStatistics

@admin.register(SiteVisit)
class SiteVisitAdmin(admin.ModelAdmin):
    list_display = ['ip_address', 'page_visited', 'visited_at_display']
    list_filter = ['visited_at']
    search_fields = ['ip_address', 'page_visited']
    readonly_fields = ['ip_address', 'user_agent', 'page_visited', 'visited_at']
    
    def visited_at_display(self, obj):
        return obj.visited_at.strftime('%Y-%m-%d %H:%M:%S')
    visited_at_display.short_description = 'وقت الزيارة'
    
    def has_add_permission(self, request):
        return False
    
    def has_change_permission(self, request, obj=None):
        return False


@admin.register(SiteStatistics)
class SiteStatisticsAdmin(admin.ModelAdmin):
    list_display = ['total_visitors_display', 'total_page_views_display', 'updated_at']
    readonly_fields = ['total_visitors', 'total_page_views', 'updated_at']
    
    def total_visitors_display(self, obj):
        return format_html(
            '<span style="font-size: 18px; font-weight: bold; color: #2ecc71;">'
            '<i class="fa fa-users"></i> {}</span>',
            obj.total_visitors
        )
    total_visitors_display.short_description = 'إجمالي الزوار'
    
    def total_page_views_display(self, obj):
        return format_html(
            '<span style="font-size: 18px; font-weight: bold; color: #3498db;">'
            '<i class="fa fa-eye"></i> {}</span>',
            obj.total_page_views
        )
    total_page_views_display.short_description = 'مشاهدات الصفحات'
    
    def has_add_permission(self, request):
        return not SiteStatistics.objects.exists()
    
    def has_delete_permission(self, request, obj=None):
        return False