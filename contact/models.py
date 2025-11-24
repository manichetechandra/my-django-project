from django.db import models
from django.utils import timezone

class ContactMessage(models.Model):
    """رسائل الاتصال والاستفسارات"""
    
    # خيارات المواضيع
    SUBJECT_CHOICES = [
        ('general', 'استفسار عام'),
        ('transport', 'النقل الجامعي'),
        ('housing', 'الإقامة الجامعية'),
        ('catering', 'المطاعم والإطعام'),
        ('scholarship', 'المنح والإعانات'),
        ('complaint', 'شكوى'),
        ('suggestion', 'اقتراح'),
        ('other', 'أخرى'),
    ]
    
    # معلومات المرسل
    full_name = models.CharField(
        max_length=100,
        verbose_name="الاسم الكامل"
    )
    
    email = models.EmailField(
        verbose_name="البريد الإلكتروني"
    )
    
    phone = models.CharField(
        max_length=20,
        blank=True,
        verbose_name="رقم الهاتف",
        help_text="اختياري"
    )
    
    # محتوى الرسالة
    subject = models.CharField(
        max_length=20,
        choices=SUBJECT_CHOICES,
        verbose_name="الموضوع"
    )
    
    message = models.TextField(
        verbose_name="الرسالة",
        help_text="اكتب رسالتك أو استفسارك بالتفصيل"
    )
    
    # حالة الرسالة
    is_read = models.BooleanField(
        default=False,
        verbose_name="مقروءة؟"
    )
    
    is_replied = models.BooleanField(
        default=False,
        verbose_name="تم الرد؟"
    )
    
    # ملاحظات الإدارة
    admin_notes = models.TextField(
        blank=True,
        verbose_name="ملاحظات الإدارة",
        help_text="ملاحظات داخلية للموظفين (لا تُرسل للمرسل)"
    )
    
    # معلومات تقنية
    ip_address = models.GenericIPAddressField(
        null=True,
        blank=True,
        verbose_name="عنوان IP"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإرسال"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ آخر تحديث"
    )
    
    class Meta:
        verbose_name = "رسالة اتصال"
        verbose_name_plural = "رسائل الاتصال"
        ordering = ['-created_at']  # الأحدث أولاً
    
    def __str__(self):
        return f"{self.full_name} - {self.get_subject_display()}"
    
    def mark_as_read(self):
        """وضع علامة مقروءة"""
        if not self.is_read:
            self.is_read = True
            self.save()
    
    def mark_as_replied(self):
        """وضع علامة تم الرد"""
        if not self.is_replied:
            self.is_replied = True
            self.is_read = True  # إذا تم الرد فهي مقروءة تلقائياً
            self.save()
    
    def get_status_display_ar(self):
        """عرض الحالة بالعربية"""
        if self.is_replied:
            return "تم الرد ✓"
        elif self.is_read:
            return "مقروءة"
        else:
            return "جديدة"
    
    def get_subject_color(self):
        """لون حسب الموضوع"""
        colors = {
            'general': '#3498db',
            'transport': '#9b59b6',
            'housing': '#e74c3c',
            'catering': '#f39c12',
            'scholarship': '#2ecc71',
            'complaint': '#e67e22',
            'suggestion': '#1abc9c',
            'other': '#95a5a6',
        }
        return colors.get(self.subject, '#95a5a6')