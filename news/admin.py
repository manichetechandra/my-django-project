from django.contrib import admin
from django.utils.html import format_html
from .models import News, NewsImage, LatestNews


class NewsImageInline(admin.TabularInline):
    model = NewsImage
    extra = 2
    fields = ['image', 'caption', 'order']


@admin.register(News)
class NewsAdmin(admin.ModelAdmin):
    
    list_display = ['title', 'author', 'status_badge', 'is_featured', 'show_in_ticker', 'published_date']
    
    search_fields = ['title', 'summary', 'content']
    
    list_filter = ['status', 'is_featured', 'show_in_ticker', 'published_date', 'author']
    
    list_editable = ['is_featured', 'show_in_ticker']
    
    fieldsets = [
        ('المعلومات الأساسية', {
            'fields': ['title', 'slug', 'summary']
        }),
        ('المحتوى', {
            'fields': ['content', 'main_image'],
            'classes': ['wide']
        }),
        ('النشر', {
            'fields': ['author', 'status']
        }),
        ('الإعدادات', {
            'fields': ['is_featured', 'show_in_ticker', 'order'],
            'classes': ['collapse']
        }),
        ('معلومات النظام (للقراءة فقط)', {
            'fields': ['published_date', 'views_count', 'created_at', 'updated_at'],
            'classes': ['collapse']
        }),
    ]
    
    prepopulated_fields = {'slug': ['title']}
    
    readonly_fields = ['created_at', 'updated_at', 'views_count', 'published_date']
    
    inlines = [NewsImageInline]
    
    list_per_page = 25
    
    date_hierarchy = 'published_date'
    
    ordering = ['-published_date']
    
    def status_badge(self, obj):
        colors = {
            'draft': '#999999',
            'published': '#28a745',
            'archived': '#dc3545'
        }
        color = colors.get(obj.status, '#999999')
        return format_html(
            '<span style="background-color: {}; color: white; padding: 3px 10px; border-radius: 3px; font-weight: bold;">{}</span>',
            color,
            obj.get_status_display()
        )
    
    status_badge.short_description = 'الحالة'
    
    def save_model(self, request, obj, form, change):
        if not obj.author:
            obj.author = request.user
        super().save_model(request, obj, form, change)
    
    actions = ['make_published', 'make_draft', 'mark_as_featured']
    
    def make_published(self, request, queryset):
        updated = queryset.update(status='published')
        self.message_user(request, f'تم نشر {updated} خبر')
    
    make_published.short_description = 'نشر الأخبار المحددة'
    
    def make_draft(self, request, queryset):
        updated = queryset.update(status='draft')
        self.message_user(request, f'تم تحويل {updated} خبر إلى مسودة')
    
    make_draft.short_description = 'تحويل إلى مسودة'
    
    def mark_as_featured(self, request, queryset):
        updated = queryset.update(is_featured=True)
        self.message_user(request, f'تم تمييز {updated} خبر')
    
    mark_as_featured.short_description = 'تمييز الأخبار المحددة'


@admin.register(NewsImage)
class NewsImageAdmin(admin.ModelAdmin):
    list_display = ['news', 'caption', 'order', 'uploaded_at']
    list_filter = ['news', 'uploaded_at']
    search_fields = ['news__title', 'caption']
    list_editable = ['order']


@admin.register(LatestNews)
class LatestNewsAdmin(admin.ModelAdmin):
    list_display = ['description_short', 'order', 'is_active', 'created_at']
    list_editable = ['order', 'is_active']
    list_filter = ['is_active', 'created_at']
    search_fields = ['description']
    
    def description_short(self, obj):
        if len(obj.description) > 50:
            return obj.description[:50] + '...'
        return obj.description
    
    description_short.short_description = 'الوصف'