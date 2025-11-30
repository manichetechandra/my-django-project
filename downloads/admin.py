from django.contrib import admin
from django.utils.html import format_html
from .models import DownloadFile

@admin.register(DownloadFile)
class DownloadFileAdmin(admin.ModelAdmin):
    
    list_display = [
        'id',
        'title',  # ← قابل للنقر
        'file_icon_display',
        'category_badge',
        'file_info',
        'downloads_badge',
        'featured',
        'is_active',
        'created_at_short'
    ]
    
    list_display_links = ['id', 'title']  # ← كلاهما قابل للنقر
    
    list_filter = [
        'category',
        'is_active',
        'featured',
        'created_at'
    ]
    
    search_fields = [
        'title',
        'description'
    ]
    
    list_editable = [
        'is_active',
        'featured'
    ]
    
    prepopulated_fields = {
        'slug': ('title',)
    }
    
    fieldsets = (
        ('المعلومات الأساسية', {
            'fields': ('title', 'slug', 'category', 'description')
        }),
        ('الملف', {
            'fields': ('file',)
        }),
        ('الإحصائيات', {
            'fields': ('file_size', 'downloads_count'),
            'classes': ('collapse',)
        }),
        ('إعدادات النشر', {
            'fields': ('is_active', 'featured', 'display_order')
        }),
    )
    
    readonly_fields = ['file_size', 'downloads_count']
    
    # دوال العرض المخصصة
    def file_icon_display(self, obj):
        """عرض أيقونة الملف"""
        icon = obj.get_file_icon()
        color = obj.get_file_color()
        ext = obj.get_file_extension().upper().replace('.', '')
        return format_html(
            '<div style="text-align: center;">'
            '<i class="fa {}" style="color: {}; font-size: 24px; display: block; margin-bottom: 5px;"></i>'
            '<small style="color: {}; font-weight: bold;">{}</small>'
            '</div>',
            icon,
            color,
            color,
            ext
        )
    file_icon_display.short_description = 'النوع'
    
    def category_badge(self, obj):
        """التصنيف مع لون"""
        color = obj.get_category_color()
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; '
            'border-radius: 3px; font-size: 12px; white-space: nowrap;">{}</span>',
            color,
            obj.get_category_display()
        )
    category_badge.short_description = 'التصنيف'
    category_badge.admin_order_field = 'category'
    
    def file_info(self, obj):
        """معلومات الملف"""
        size = obj.get_file_size_display()
        return format_html(
            '<strong style="color: #2c3e50;">{}</strong>',
            size
        )
    file_info.short_description = 'الحجم'
    
    def downloads_badge(self, obj):
        """عدد التحميلات"""
        if obj.downloads_count > 100:
            color = '#2ecc71'
        elif obj.downloads_count > 50:
            color = '#f39c12'
        elif obj.downloads_count > 0:
            color = '#3498db'
        else:
            color = '#95a5a6'
        
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; '
            'border-radius: 3px; font-size: 12px; font-weight: bold;">'
            '<i class="fa fa-download"></i> {}</span>',
            color,
            obj.downloads_count
        )
    downloads_badge.short_description = 'التحميلات'
    downloads_badge.admin_order_field = 'downloads_count'
    
    def created_at_short(self, obj):
        """تاريخ مختصر"""
        return obj.created_at.strftime('%d/%m/%Y')
    created_at_short.short_description = 'تاريخ الرفع'
    created_at_short.admin_order_field = 'created_at'
    
    # إجراءات مخصصة
    actions = ['mark_as_featured', 'unmark_as_featured']
    
    def mark_as_featured(self, request, queryset):
        """وضع علامة مميز"""
        updated = queryset.update(featured=True)
        self.message_user(request, f'تم وضع علامة "مميز" على {updated} ملف')
    mark_as_featured.short_description = '⭐ وضع علامة "مميز"'
    
    def unmark_as_featured(self, request, queryset):
        """إلغاء علامة مميز"""
        updated = queryset.update(featured=False)
        self.message_user(request, f'تم إلغاء علامة "مميز" من {updated} ملف')
    unmark_as_featured.short_description = '☆ إلغاء علامة "مميز"'
    
    class Media:
        css = {
            'all': ('admin/css/rtl.css',)
        }