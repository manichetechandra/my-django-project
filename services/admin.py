from django.contrib import admin
from .models import Service

@admin.register(Service)
class ServiceAdmin(admin.ModelAdmin):
    list_display = [
        'title', 
        'service_type', 
        'featured', 
        'is_active', 
        'display_order',
        'updated_at'
    ]
    
    list_filter = ['service_type', 'is_active', 'featured']
    
    list_editable = ['is_active', 'featured', 'display_order']
    
    search_fields = ['title', 'short_description']
    
    prepopulated_fields = {'slug': ('title',)}
    
    fieldsets = (
        ('المعلومات الأساسية', {
            'fields': ('title', 'slug', 'service_type')
        }),
        ('المحتوى', {
            'fields': (
                'short_description', 
                'detailed_description',
                'requirements',
                'how_to_apply',
                'contact_info'
            )
        }),
        ('الوسائط', {
            'fields': ('main_image', 'icon')
        }),
        ('إعدادات النشر', {
            'fields': ('is_active', 'featured', 'display_order')
        }),
    )
    
    readonly_fields = ['created_at', 'updated_at']
    
    class Media:
        css = {
            'all': ('admin/css/rtl.css',)
        }