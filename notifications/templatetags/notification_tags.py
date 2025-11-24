from django import template
from notifications.models import Notification

register = template.Library()

@register.inclusion_tag('notifications/notification_bar.html')
def show_notifications():
    """عرض الإشعارات النشطة"""
    notifications = Notification.objects.filter(is_active=True)
    
    # فلترة الإشعارات الصالحة فقط
    valid_notifications = [n for n in notifications if n.is_valid()]
    
    return {'notifications': valid_notifications}