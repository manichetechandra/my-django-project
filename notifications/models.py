from django.db import models
from django.utils import timezone

class Notification(models.Model):
    """الإشعارات والتنبيهات"""
    
    # أنواع الإشعارات
    TYPE_CHOICES = [
        ('info', 'معلومة'),
        ('success', 'نجاح'),
        ('warning', 'تحذير'),
        ('danger', 'عاجل/خطر'),
        ('primary', 'عادي'),
    ]
    
    # الحقول الأساسية
    title = models.CharField(
        max_length=200,
        verbose_name="عنوان الإشعار"
    )
    
    message = models.TextField(
        verbose_name="نص الإشعار",
        help_text="الرسالة التي ستظهر للمستخدمين"
    )
    
    notification_type = models.CharField(
        max_length=10,
        choices=TYPE_CHOICES,
        default='info',
        verbose_name="نوع الإشعار"
    )
    
    # رابط اختياري
    link = models.URLField(
        blank=True,
        verbose_name="رابط (اختياري)",
        help_text="رابط للمزيد من المعلومات"
    )
    
    link_text = models.CharField(
        max_length=50,
        blank=True,
        default="المزيد",
        verbose_name="نص الرابط"
    )
    
    # التواريخ
    start_date = models.DateTimeField(
        default=timezone.now,
        verbose_name="تاريخ البداية",
        help_text="متى يبدأ عرض الإشعار"
    )
    
    end_date = models.DateTimeField(
        null=True,
        blank=True,
        verbose_name="تاريخ النهاية",
        help_text="متى ينتهي عرض الإشعار (اتركه فارغاً لعرض دائم)"
    )
    
    # الإعدادات
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    is_dismissible = models.BooleanField(
        default=True,
        verbose_name="قابل للإغلاق؟",
        help_text="هل يمكن للمستخدم إغلاقه؟"
    )
    
    priority = models.IntegerField(
        default=0,
        verbose_name="الأولوية",
        help_text="رقم أعلى = أولوية أعلى (يظهر أولاً)"
    )
    
    # تتبع
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "إشعار"
        verbose_name_plural = "الإشعارات"
        ordering = ['-priority', '-created_at']
    
    def __str__(self):
        return self.title
    
    def is_valid(self):
        """هل الإشعار صالح للعرض؟"""
        now = timezone.now()
        
        # يجب أن يكون نشط
        if not self.is_active:
            return False
        
        # بدأ العرض؟
        if self.start_date > now:
            return False
        
        # انتهى العرض؟
        if self.end_date and self.end_date < now:
            return False
        
        return True
    
    def get_icon(self):
        """أيقونة حسب النوع"""
        icons = {
            'info': 'fa-info-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'danger': 'fa-exclamation-circle',
            'primary': 'fa-bell',
        }
        return icons.get(self.notification_type, 'fa-bell')
    
    def get_color(self):
        """لون حسب النوع"""
        colors = {
            'info': '#5bc0de',      # سماوي
            'success': '#5cb85c',   # أخضر
            'warning': '#f0ad4e',   # برتقالي
            'danger': '#d9534f',    # أحمر
            'primary': '#337ab7',   # أزرق
        }
        return colors.get(self.notification_type, '#337ab7')
    
    def get_bootstrap_class(self):
        """كلاس Bootstrap للتنبيه"""
        return f"alert-{self.notification_type}"