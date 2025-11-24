from django.db.models.signals import post_save, pre_save
from django.dispatch import receiver
from .models import StudentApplication, StudentNotification

# متغير لحفظ الحالة القديمة
old_status = {}

@receiver(pre_save, sender=StudentApplication)
def capture_old_status(sender, instance, **kwargs):
    """
    حفظ الحالة القديمة قبل التحديث
    
    الشرح:
    - pre_save = قبل الحفظ
    - نحفظ الحالة القديمة لمقارنتها بعد الحفظ
    """
    if instance.pk:  # إذا كان الطلب موجود (تحديث وليس إنشاء)
        try:
            old_app = StudentApplication.objects.get(pk=instance.pk)
            old_status[instance.pk] = old_app.status
        except StudentApplication.DoesNotExist:
            old_status[instance.pk] = None


@receiver(post_save, sender=StudentApplication)
def create_status_change_notification(sender, instance, created, **kwargs):
    """
    إنشاء إشعار تلقائي عند تغيير حالة الطلب
    
    الشرح:
    - post_save = بعد الحفظ
    - created = True إذا كان طلب جديد، False إذا كان تحديث
    - يعمل من أي مكان: Django Admin، Views، Shell، أي شيء!
    """
    
    # إذا كان طلب جديد (ليس تحديث)
    if created:
        print(f"✅ طلب جديد: {instance.application_number}")
        return
    
    # الحصول على الحالة القديمة
    previous_status = old_status.get(instance.pk)
    current_status = instance.status
    
    # إذا لم تتغير الحالة
    if previous_status == current_status:
        return
    
    print(f"\n{'='*60}")
    print(f"🔄 تغيير حالة الطلب!")
    print(f"   الطلب: {instance.application_number}")
    print(f"   الطالب: {instance.student.full_name_arabic}")
    print(f"   من: {previous_status} → إلى: {current_status}")
    print(f"{'='*60}\n")
    
    # ==========================================
    # إنشاء الإشعار حسب الحالة الجديدة
    # ==========================================
    
    notification_data = {
        'student': instance.student,
        'link': f'/students/applications/{instance.id}/'
    }
    
    # حالة: معتمد
    if current_status == 'approved':
        notification_data.update({
            'title': 'تم اعتماد طلبك ✅',
            'message': f'تم اعتماد طلب {instance.application_type.name} برقم {instance.application_number}',
            'notification_type': 'success'
        })
    
    # حالة: مرفوض
    elif current_status == 'rejected':
        notification_data.update({
            'title': 'تم رفض طلبك ❌',
            'message': f'تم رفض طلب {instance.application_type.name} برقم {instance.application_number}. يرجى مراجعة الملاحظات.',
            'notification_type': 'error'
        })
    
    # حالة: قيد المراجعة
    elif current_status == 'under_review':
        notification_data.update({
            'title': 'طلبك قيد المراجعة 📝',
            'message': f'طلب {instance.application_type.name} برقم {instance.application_number} قيد المراجعة من قبل الإدارة.',
            'notification_type': 'info'
        })
    
    # حالة: قيد الانتظار
    elif current_status == 'pending':
        notification_data.update({
            'title': 'تم تغيير حالة طلبك ⏳',
            'message': f'تم تغيير حالة طلب {instance.application_type.name} برقم {instance.application_number} إلى "قيد الانتظار".',
            'notification_type': 'warning'
        })
    
    # حالة: ملغي
    elif current_status == 'cancelled':
        notification_data.update({
            'title': 'تم إلغاء طلبك 🚫',
            'message': f'تم إلغاء طلب {instance.application_type.name} برقم {instance.application_number}.',
            'notification_type': 'warning'
        })
    
    else:
        # أي حالة أخرى
        notification_data.update({
            'title': 'تحديث على طلبك 📢',
            'message': f'تم تحديث حالة طلب {instance.application_type.name} برقم {instance.application_number}.',
            'notification_type': 'info'
        })
    
    # إنشاء الإشعار
    notif = StudentNotification.objects.create(**notification_data)
    
    print(f"✅ تم إنشاء إشعار رقم: {notif.id}")
    print(f"   العنوان: {notif.title}")
    print(f"   النوع: {notif.notification_type}")
    print(f"   للطالب: {instance.student.full_name_arabic}")
    print(f"\n⏰ يجب أن يظهر في المتصفح خلال 10 ثواني!\n")
    
    # تنظيف
    if instance.pk in old_status:
        del old_status[instance.pk]