from django.db import models
from django.urls import reverse
from django.utils.text import slugify

class Service(models.Model):
    """الخدمات الجامعية"""
    
    # خيارات أنواع الخدمات
    SERVICE_TYPES = [
        ('transport', 'النقل'),
        ('housing', 'الإيواء'),
        ('catering', 'الإطعام'),
        ('scholarship', 'المنح'),
    ]
    
    # الحقول الأساسية
    title = models.CharField(
        max_length=200,
        verbose_name="عنوان الخدمة"
    )
    
    slug = models.SlugField(
        max_length=200,
        unique=True,
        blank=True,
        verbose_name="الرابط"
    )
    
    service_type = models.CharField(
        max_length=20,
        choices=SERVICE_TYPES,
        verbose_name="نوع الخدمة"
    )
    
    # المحتوى
    short_description = models.TextField(
        max_length=500,
        verbose_name="وصف مختصر",
        help_text="نبذة قصيرة عن الخدمة (حد أقصى 500 حرف)"
    )
    
    detailed_description = models.TextField(
        verbose_name="الوصف التفصيلي",
        help_text="شرح كامل عن الخدمة"
    )
    
    # الصور
    main_image = models.ImageField(
        upload_to='services/',
        verbose_name="الصورة الرئيسية",
        blank=True,
        null=True
    )
    
    icon = models.CharField(
        max_length=50,
        verbose_name="أيقونة Font Awesome",
        default="fa-info-circle",
        help_text="مثال: fa-bus, fa-home, fa-cutlery, fa-money"
    )
    
    # معلومات إضافية
    requirements = models.TextField(
        verbose_name="الشروط والمتطلبات",
        blank=True,
        help_text="شروط الاستفادة من الخدمة"
    )
    
    how_to_apply = models.TextField(
        verbose_name="كيفية التقديم",
        blank=True,
        help_text="خطوات التقديم للحصول على الخدمة"
    )
    
    contact_info = models.TextField(
        verbose_name="معلومات الاتصال",
        blank=True,
        help_text="جهة الاتصال للاستفسارات"
    )
    
    # إعدادات النشر
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    featured = models.BooleanField(
        default=False,
        verbose_name="مميز؟",
        help_text="يظهر في الصفحة الرئيسية"
    )
    
    display_order = models.IntegerField(
        default=0,
        verbose_name="ترتيب العرض",
        help_text="رقم أقل = يظهر أولاً"
    )
    
    # تواريخ
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "خدمة جامعية"
        verbose_name_plural = "الخدمات الجامعية"
        ordering = ['display_order', '-created_at']
    
    def __str__(self):
        return f"{self.get_service_type_display()} - {self.title}"
    
    def save(self, *args, **kwargs):
        """إنشاء slug تلقائياً"""
        if not self.slug:
            base_slug = slugify(self.title, allow_unicode=True)
            slug = base_slug
            counter = 1
            while Service.objects.filter(slug=slug).exists():
                slug = f"{base_slug}-{counter}"
                counter += 1
            self.slug = slug
        super().save(*args, **kwargs)
    
    def get_absolute_url(self):
        """رابط تفاصيل الخدمة"""
        return reverse('services:service_detail', kwargs={'slug': self.slug})
    
    def get_type_color(self):
        """لون حسب نوع الخدمة"""
        colors = {
            'transport': '#3498db',  # أزرق
            'housing': '#e74c3c',    # أحمر
            'catering': '#f39c12',   # برتقالي
            'scholarship': '#2ecc71' # أخضر
        }
        return colors.get(self.service_type, '#95a5a6')
    
    def get_type_icon_default(self):
        """أيقونة افتراضية حسب النوع"""
        icons = {
            'transport': 'fa-bus',
            'housing': 'fa-home',
            'catering': 'fa-cutlery',
            'scholarship': 'fa-money'
        }
        return icons.get(self.service_type, 'fa-info-circle')