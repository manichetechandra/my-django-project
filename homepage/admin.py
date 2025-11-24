from django.contrib import admin
from .models import Slider, DirectorWord, OrganizationStructure, DirectorateHistory


@admin.register(Slider)
class SliderAdmin(admin.ModelAdmin):
    list_display = ['title', 'order', 'is_active', 'created_at']
    list_editable = ['order', 'is_active']
    list_filter = ['is_active', 'created_at']
    search_fields = ['title', 'subtitle']

@admin.register(DirectorWord)
class DirectorWordAdmin(admin.ModelAdmin):
    list_display = ['director_name', 'director_title', 'is_active', 'updated_at']
    list_editable = ['is_active']
    list_filter = ['is_active']
    
    fieldsets = (
        ('معلومات المدير', {
            'fields': ('director_name', 'director_title', 'director_image')
        }),
        ('الكلمة', {
            'fields': ('word_title', 'word_content', 'signature')
        }),
        ('الإعدادات', {
            'fields': ('is_active',)
        }),
    )
@admin.register(OrganizationStructure)
class OrganizationStructureAdmin(admin.ModelAdmin):
    list_display = ['title', 'is_active', 'updated_at']
    list_editable = ['is_active']
    
    fieldsets = (
        ('المعلومات الأساسية', {
            'fields': ('title', 'description')
        }),
        ('الهيكل', {
            'fields': ('organization_chart', 'content')
        }),
        ('الإعدادات', {
            'fields': ('is_active',)
        }),
    )
    
      
    
@admin.register(DirectorateHistory)
class DirectorateHistoryAdmin(admin.ModelAdmin):
    list_display = ['title', 'founding_year', 'is_active', 'updated_at']
    list_editable = ['is_active']
    
    fieldsets = (
        ('المعلومات الأساسية', {
            'fields': ('title', 'founding_year')
        }),
        ('المحتوى', {
            'fields': ('introduction', 'content', 'main_image')
        }),
        ('الإعدادات', {
            'fields': ('is_active',)
        }),
    )
    
    