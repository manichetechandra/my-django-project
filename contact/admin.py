from django.contrib import admin
from django.utils.html import format_html
from .models import ContactMessage

@admin.register(ContactMessage)
class ContactMessageAdmin(admin.ModelAdmin):
    
    list_display = [
        'id',
        'full_name_link',  # ← قابل للنقر
        'email_with_copy',
        'phone_with_copy',
        'subject_badge',
        'status_badge',
        'created_at_short'
    ]
    
    list_filter = [
        'is_read',
        'is_replied',
        'subject',
        'created_at'
    ]
    
    search_fields = [
        'full_name',
        'email',
        'phone',
        'message'
    ]
    
    readonly_fields = [
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'ip_address',
        'created_at',
        'updated_at'
    ]
    
    fieldsets = (
        ('معلومات المرسل', {
            'fields': (
                'full_name',
                'email',
                'phone',
                'ip_address'
            )
        }),
        ('محتوى الرسالة', {
            'fields': (
                'subject',
                'message',
                'created_at'
            )
        }),
        ('إدارة الرسالة', {
            'fields': (
                'is_read',
                'is_replied',
                'admin_notes',
                'updated_at'
            )
        }),
    )
    
    actions = [
        'mark_as_read',
        'mark_as_replied',
        'mark_as_unread'
    ]
    
    # دوال العرض المخصصة
    def full_name_link(self, obj):
        """الاسم مع رابط (قابل للنقر)"""
        url = f'/admin/contact/contactmessage/{obj.id}/change/'
        return format_html('<a href="{}">{}</a>', url, obj.full_name)
    full_name_link.short_description = 'الاسم الكامل'
    full_name_link.admin_order_field = 'full_name'  # للسماح بالترتيب
    
    def email_with_copy(self, obj):
        """البريد مع زر نسخ"""
        return format_html(
            '{}<br><button onclick="navigator.clipboard.writeText(\'{}\'); alert(\'تم النسخ!\'); return false;" '
            'style="font-size: 10px; padding: 2px 5px; margin-top: 3px; cursor: pointer;">📋 نسخ</button>',
            obj.email,
            obj.email
        )
    email_with_copy.short_description = 'البريد الإلكتروني'
    
    def phone_with_copy(self, obj):
        """الهاتف مع زر نسخ"""
        if obj.phone:
            return format_html(
                '{}<br><button onclick="navigator.clipboard.writeText(\'{}\'); alert(\'تم النسخ!\'); return false;" '
                'style="font-size: 10px; padding: 2px 5px; margin-top: 3px; cursor: pointer;">📋 نسخ</button>',
                obj.phone,
                obj.phone
            )
        return '-'
    phone_with_copy.short_description = 'الهاتف'
    
    def subject_badge(self, obj):
        """الموضوع مع لون"""
        color = obj.get_subject_color()
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; '
            'border-radius: 3px; font-size: 12px;">{}</span>',
            color,
            obj.get_subject_display()
        )
    subject_badge.short_description = 'الموضوع'
    subject_badge.admin_order_field = 'subject'
    
    def status_badge(self, obj):
        """الحالة مع لون"""
        if obj.is_replied:
            color = '#2ecc71'
            icon = '✓'
            text = 'تم الرد'
        elif obj.is_read:
            color = '#3498db'
            icon = '👁'
            text = 'مقروءة'
        else:
            color = '#e74c3c'
            icon = '🔴'
            text = 'جديدة'
        
        return format_html(
            '<span style="background-color: {}; color: white; padding: 5px 10px; '
            'border-radius: 3px; font-size: 12px;">{} {}</span>',
            color,
            icon,
            text
        )
    status_badge.short_description = 'الحالة'
    
    def created_at_short(self, obj):
        """تاريخ مختصر"""
        return obj.created_at.strftime('%d/%m/%Y - %H:%M')
    created_at_short.short_description = 'تاريخ الإرسال'
    created_at_short.admin_order_field = 'created_at'
    
    # الإجراءات الجماعية
    def mark_as_read(self, request, queryset):
        """وضع علامة مقروءة"""
        updated = queryset.update(is_read=True)
        self.message_user(request, f'تم وضع علامة مقروءة على {updated} رسالة')
    mark_as_read.short_description = '✓ وضع علامة "مقروءة"'
    
    def mark_as_replied(self, request, queryset):
        """وضع علامة تم الرد"""
        updated = queryset.update(is_read=True, is_replied=True)
        self.message_user(request, f'تم وضع علامة "تم الرد" على {updated} رسالة')
    mark_as_replied.short_description = '✓ وضع علامة "تم الرد"'
    
    def mark_as_unread(self, request, queryset):
        """إلغاء القراءة"""
        updated = queryset.update(is_read=False)
        self.message_user(request, f'تم وضع علامة "غير مقروءة" على {updated} رسالة')
    mark_as_unread.short_description = '✗ وضع علامة "غير مقروءة"'
    
    # تلوين الصفوف حسب الحالة
    def changelist_view(self, request, extra_context=None):
        extra_context = extra_context or {}
        extra_context['title'] = 'رسائل الاتصال'
        return super().changelist_view(request, extra_context=extra_context)
    
    class Media:
        css = {
            'all': ('admin/css/rtl.css',)
        }