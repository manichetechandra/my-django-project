from django.contrib import admin
from .models import Activity, ActivityImage

class ActivityImageInline(admin.TabularInline):
    """لإضافة صور متعددة مباشرة من صفحة النشاط"""
    model = ActivityImage
    extra = 3
    fields = ['image', 'caption', 'order']

@admin.register(Activity)
class ActivityAdmin(admin.ModelAdmin):
    list_display = ['title', 'activity_type', 'date', 'location', 'participants_count', 'is_featured', 'is_active', 'views_count']
    list_filter = ['activity_type', 'is_featured', 'is_active', 'date']
    search_fields = ['title', 'description', 'location']
    prepopulated_fields = {'slug': ('title',)}
    list_editable = ['is_featured', 'is_active']
    date_hierarchy = 'date'
    
    inlines = [ActivityImageInline]
    
    fieldsets = (
        ('المعلومات الأساسية', {
            'fields': ('title', 'slug', 'activity_type', 'short_description')
        }),
        ('التفاصيل', {
            'fields': ('description', 'main_image')
        }),
        ('معلومات النشاط', {
            'fields': ('date', 'location', 'organizer', 'participants_count')
        }),
        ('الإعدادات', {
            'fields': ('is_featured', 'is_active', 'order', 'views_count')
        }),
    )
    
    readonly_fields = ['views_count']
    
    class Media:
        css = {
            'all': ('admin/css/custom_admin.css',)
        }

@admin.register(ActivityImage)
class ActivityImageAdmin(admin.ModelAdmin):
    list_display = ['activity', 'caption', 'order', 'uploaded_at']
    list_filter = ['activity', 'uploaded_at']
    search_fields = ['activity__title', 'caption']
    list_editable = ['order']