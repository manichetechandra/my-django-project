from django.contrib import admin
from django.utils.html import format_html
from .models import (
    StudentProfile, 
    ApplicationType, 
    StudentApplication, 
    ApplicationDocument,
    StudentNotification
)


@admin.register(StudentProfile)
class StudentProfileAdmin(admin.ModelAdmin):
    list_display = [
        'registration_number',
        'full_name_arabic',
        'faculty',
        'current_year',
        'phone',
        'is_verified_display',
        'created_at'
    ]
    
    list_filter = ['is_verified', 'is_active', 'current_year', 'faculty', 'gender']
    search_fields = ['registration_number', 'full_name_arabic', 'phone', 'user__email']
    readonly_fields = ['created_at', 'updated_at']
    
    fieldsets = (
        ('معلومات الحساب', {
            'fields': ('user', 'registration_number', 'is_verified', 'is_active')
        }),
        ('معلومات شخصية', {
            'fields': ('full_name_arabic', 'birth_date', 'birth_place', 'gender', 'photo')
        }),
        ('معلومات الاتصال', {
            'fields': ('phone', 'address', 'wilaya')
        }),
        ('معلومات أكاديمية', {
            'fields': ('university', 'faculty', 'department', 'current_year', 'average')
        }),
        ('تواريخ', {
            'fields': ('created_at', 'updated_at'),
            'classes': ('collapse',)
        }),
    )
    
    def is_verified_display(self, obj):
        if obj.is_verified:
            return format_html('<span style="color: green;">✓ مُفعّل</span>')
        return format_html('<span style="color: orange;">⏳ غير مُفعّل</span>')
    is_verified_display.short_description = 'الحالة'


@admin.register(ApplicationType)
class ApplicationTypeAdmin(admin.ModelAdmin):
    list_display = ['name', 'icon_display', 'is_active', 'order']
    list_editable = ['is_active', 'order']
    
    def icon_display(self, obj):
        return format_html(
            '<i class="fa {} fa-2x" style="color: {};"></i>',
            obj.icon,
            obj.color
        )
    icon_display.short_description = 'الأيقونة'


class ApplicationDocumentInline(admin.TabularInline):
    model = ApplicationDocument
    extra = 0
    readonly_fields = ['uploaded_at']


@admin.register(StudentApplication)
class StudentApplicationAdmin(admin.ModelAdmin):
    list_display = [
        'application_number',
        'student_name',
        'application_type',
        'status_display',
        'submitted_at'
    ]
    
    list_filter = ['status', 'application_type', 'submitted_at']
    search_fields = ['application_number', 'student__registration_number', 'student__full_name_arabic']
    readonly_fields = ['application_number', 'submitted_at', 'updated_at']
    
    fieldsets = (
        ('معلومات الطلب', {
            'fields': ('application_number', 'student', 'application_type', 'status')
        }),
        ('التفاصيل', {
            'fields': ('details', 'admin_notes')
        }),
        ('المراجعة', {
            'fields': ('reviewed_by', 'reviewed_at')
        }),
        ('التواريخ', {
            'fields': ('submitted_at', 'updated_at'),
            'classes': ('collapse',)
        }),
    )
    
    inlines = [ApplicationDocumentInline]
    
    def student_name(self, obj):
        return obj.student.full_name_arabic
    student_name.short_description = 'الطالب'
    
    def status_display(self, obj):
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; border-radius: 3px;">'
            '<i class="fa {}"></i> {}</span>',
            obj.get_status_color(),
            obj.get_status_icon(),
            obj.get_status_display()
        )
    status_display.short_description = 'الحالة'


@admin.register(StudentNotification)
class StudentNotificationAdmin(admin.ModelAdmin):
    list_display = ['title', 'student', 'is_read', 'created_at']
    list_filter = ['is_read', 'created_at']
    search_fields = ['title', 'message', 'student__full_name_arabic']
    readonly_fields = ['created_at']