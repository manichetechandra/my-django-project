from django.contrib import admin
from django.utils.html import format_html
from django.utils import timezone
from .models import Notification

@admin.register(Notification)
class NotificationAdmin(admin.ModelAdmin):
    
    list_display = [
        'id',
        'title',
        'type_badge',
        'status_badge',
        'priority',
        'date_range',
        'is_active',
        'is_dismissible'
    ]
    list_display_links = ['id', 'title'] 
    list_filter = [
        'notification_type',
        'is_active',
        'is_dismissible',
        'created_at'
    ]
    
    search_fields = [
        'title',
        'message'
    ]
    
    list_editable = [
        'is_active',
        'priority'
    ]
    
    fieldsets = (
        ('المحتوى', {
            'fields': ('title', 'message', 'notification_type')
        }),
        ('الرابط (اختياري)', {
            'fields': ('link', 'link_text'),
            'classes': ('collapse',)
        }),
        ('التواريخ', {
            'fields': ('start_date', 'end_date')
        }),
        ('الإعدادات', {
            'fields': ('is_active', 'is_dismissible', 'priority')
        }),
    )
    
    date_hierarchy = 'created_at'
    
    # دوال العرض المخصصة
    def type_badge(self, obj):
        """نوع الإشعار مع لون"""
        color = obj.get_color()
        icon = obj.get_icon()
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; '
            'border-radius: 3px; font-size: 12px;">'
            '<i class="fa {}"></i> {}</span>',
            color,
            icon,
            obj.get_notification_type_display()
        )
    type_badge.short_description = 'النوع'
    type_badge.admin_order_field = 'notification_type'
    
    def status_badge(self, obj):
        """حالة الإشعار"""
        now = timezone.now()
        
        if not obj.is_active:
            return format_html(
                '<span style="background-color: #95a5a6; color: white; padding: 5px 10px; '
                'border-radius: 3px; font-size: 12px;">⚫ غير نشط</span>'
            )
        elif obj.start_date > now:
            return format_html(
                '<span style="background-color: #f39c12; color: white; padding: 5px 10px; '
                'border-radius: 3px; font-size: 12px;">⏳ قريباً</span>'
            )
        elif obj.end_date and obj.end_date < now:
            return format_html(
                '<span style="background-color: #e74c3c; color: white; padding: 5px 10px; '
                'border-radius: 3px; font-size: 12px;">⏹ انتهى</span>'
            )
        else:
            return format_html(
                '<span style="background-color: #2ecc71; color: white; padding: 5px 10px; '
                'border-radius: 3px; font-size: 12px;">✓ نشط الآن</span>'
            )
    status_badge.short_description = 'الحالة'
    
    def date_range(self, obj):
        """فترة العرض"""
        start = obj.start_date.strftime('%d/%m/%Y')
        end = obj.end_date.strftime('%d/%m/%Y') if obj.end_date else 'دائم'
        return f"{start} → {end}"
    date_range.short_description = 'فترة العرض'
    
    # إجراءات مخصصة
    actions = ['activate_notifications', 'deactivate_notifications']
    
    def activate_notifications(self, request, queryset):
        """تفعيل الإشعارات"""
        updated = queryset.update(is_active=True)
        self.message_user(request, f'تم تفعيل {updated} إشعار')
    activate_notifications.short_description = '✓ تفعيل الإشعارات المحددة'
    
    def deactivate_notifications(self, request, queryset):
        """إلغاء تفعيل الإشعارات"""
        updated = queryset.update(is_active=False)
        self.message_user(request, f'تم إلغاء تفعيل {updated} إشعار')
    deactivate_notifications.short_description = '✗ إلغاء تفعيل الإشعارات المحددة'
    
    class Media:
        css = {
            'all': ('admin/css/rtl.css',)
        }