from django.contrib import admin
from .models import Departement

@admin.register(Departement)
class DepartementAdmin(admin.ModelAdmin):
    list_display = ['name', 'head_of_department', 'order', 'is_active']
    list_editable = ['order', 'is_active']
    list_filter = ['is_active']
    search_fields = ['name', 'description', 'head_of_department']
    prepopulated_fields = {'slug': ('name',)}
    
    fieldsets = (
        ('معلومات أساسية', {
            'fields': ('name', 'slug', 'description', 'image')
        }),
        ('معلومات الاتصال', {
            'fields': ('head_of_department', 'email', 'phone', 'website')
        }),
        ('الإعدادات', {
            'fields': ('order', 'is_active')
        }),
    )