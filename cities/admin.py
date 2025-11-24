from django.contrib import admin
from .models import Cite, CiteImage

class CiteImageInline(admin.TabularInline):
    """لإضافة صور متعددة مباشرة من صفحة الإقامة"""
    model = CiteImage
    extra = 3
    fields = ['image', 'caption', 'order']

@admin.register(Cite)
class CiteAdmin(admin.ModelAdmin):
    list_display = ['name', 'capacity', 'address', 'phone', 'order', 'is_active']
    list_editable = ['order', 'is_active']
    list_filter = ['is_active']
    search_fields = ['name', 'description', 'address']
    prepopulated_fields = {'slug': ('name',)}
    
    inlines = [CiteImageInline]
    
    fieldsets = (
        ('معلومات أساسية', {
            'fields': ('name', 'slug', 'description', 'main_image')
        }),
        ('معلومات إضافية', {
            'fields': ('address', 'capacity', 'phone')
        }),
        ('الإعدادات', {
            'fields': ('order', 'is_active')
        }),
    )

@admin.register(CiteImage)
class CiteImageAdmin(admin.ModelAdmin):
    list_display = ['cite', 'caption', 'order', 'uploaded_at']
    list_filter = ['cite', 'uploaded_at']
    search_fields = ['cite__name', 'caption']
    list_editable = ['order']