from django.contrib import admin
from django.utils.html import format_html
from .models import FAQCategory, FAQ

@admin.register(FAQCategory)
class FAQCategoryAdmin(admin.ModelAdmin):
    list_display = ['name', 'icon_display', 'order', 'faq_count', 'is_active']
    list_editable = ['order', 'is_active']
    search_fields = ['name']
    
    def icon_display(self, obj):
        """عرض الأيقونة"""
        return format_html(
            '<i class="fa {} fa-2x" style="color: {};"></i>',
            obj.icon,
            obj.get_color()
        )
    icon_display.short_description = 'الأيقونة'
    
    def faq_count(self, obj):
        """عدد الأسئلة"""
        count = obj.faqs.filter(is_active=True).count()
        return format_html(
            '<span style="background-color: #3498db; color: white; padding: 5px 10px; border-radius: 3px;">{}</span>',
            count
        )
    faq_count.short_description = 'عدد الأسئلة'


@admin.register(FAQ)
class FAQAdmin(admin.ModelAdmin):
    list_display = [
        'id',
        'question_short',
        'category_badge',
        'views_badge',
        'order',
        'is_active',
        'updated_at_short'
    ]
    list_display_links = ['id', 'question_short']
    list_filter = ['category', 'is_active', 'created_at']
    search_fields = ['question', 'answer']
    list_editable = ['order', 'is_active']
    
    fieldsets = (
        ('السؤال', {
            'fields': ('category', 'question', 'answer')
        }),
        ('الإعدادات', {
            'fields': ('order', 'is_active')
        }),
        ('الإحصائيات', {
            'fields': ('views_count',),
            'classes': ('collapse',)
        }),
    )
    
    readonly_fields = ['views_count']
    
    def question_short(self, obj):
        """السؤال مختصر"""
        return obj.question[:70] + '...' if len(obj.question) > 70 else obj.question
    question_short.short_description = 'السؤال'
    
    def category_badge(self, obj):
        """التصنيف مع لون"""
        color = obj.category.get_color()
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px;">'
            '<i class="fa {}"></i> {}</span>',
            color,
            obj.category.icon,
            obj.category.name
        )
    category_badge.short_description = 'التصنيف'
    category_badge.admin_order_field = 'category'
    
    def views_badge(self, obj):
        """عدد المشاهدات"""
        if obj.views_count > 100:
            color = '#2ecc71'
        elif obj.views_count > 50:
            color = '#f39c12'
        else:
            color = '#3498db'
        
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; border-radius: 3px;">'
            '<i class="fa fa-eye"></i> {}</span>',
            color,
            obj.views_count
        )
    views_badge.short_description = 'المشاهدات'
    views_badge.admin_order_field = 'views_count'
    
    def updated_at_short(self, obj):
        """تاريخ مختصر"""
        return obj.updated_at.strftime('%d/%m/%Y')
    updated_at_short.short_description = 'آخر تحديث'
    updated_at_short.admin_order_field = 'updated_at'
    
    class Media:
        css = {
            'all': ('admin/css/rtl.css',)
        }