from django.db import models
from django.utils.text import slugify
from django.urls import reverse

class Activity(models.Model):
    
    """
    النشاطات الثقافية والرياضية
    """
    
    ACTIVITY_TYPES = [
        ('sport', 'رياضي'),
        ('culture', 'ثقافي'),
        ('scientific', 'علمي'),
        ('social', 'اجتماعي'),
        ('artistic', 'فني'),
        ('other', 'أخرى'),
    ]
    
    # المعلومات الأساسية
    title = models.CharField(
        max_length=300,
        verbose_name="عنوان النشاط"
    )
    
    slug = models.SlugField(
        max_length=300,
        unique=True,
        blank=True,
        verbose_name="الرابط الصديق"
    )
    
    activity_type = models.CharField(
        max_length=20,
        choices=ACTIVITY_TYPES,
        default='culture',
        verbose_name="نوع النشاط"
    )
    
    short_description = models.CharField(
        max_length=500,
        verbose_name="وصف مختصر",
        help_text="يظهر في الصفحة الرئيسية والبطاقات"
    )
    
    description = models.TextField(
        verbose_name="وصف تفصيلي"
    )
    
    # الصور
    main_image = models.ImageField(
        upload_to='activities/main/',
        verbose_name="الصورة الرئيسية"
    )
    
    # تفاصيل النشاط
    date = models.DateField(
        verbose_name="تاريخ النشاط"
    )
    
    location = models.CharField(
        max_length=300,
        verbose_name="مكان النشاط"
    )
    
    organizer = models.CharField(
        max_length=200,
        verbose_name="المنظم",
        blank=True,
        help_text="الجهة المنظمة للنشاط"
    )
    
    participants_count = models.IntegerField(
        default=0,
        verbose_name="عدد المشاركين"
    )
    
    # الإعدادات
    is_featured = models.BooleanField(
        default=False,
        verbose_name="مميز؟",
        help_text="يظهر في الصفحة الرئيسية"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    views_count = models.IntegerField(
        default=0,
        verbose_name="عدد المشاهدات"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    # التواريخ
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإضافة"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "نشاط"
        verbose_name_plural = "النشاطات"
        ordering = ['-date', '-created_at']
    
    def __str__(self):
        return self.title
    
    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title, allow_unicode=True)
        super().save(*args, **kwargs)
    
    def get_absolute_url(self):
        return reverse('activities:activity_detail', kwargs={'slug': self.slug})
    
    def get_type_icon(self):
        """أيقونة حسب نوع النشاط"""
        icons = {
            'sport': 'fa-trophy',
            'culture': 'fa-book',
            'scientific': 'fa-flask',
            'social': 'fa-users',
            'artistic': 'fa-paint-brush',
            'other': 'fa-star',
        }
        return icons.get(self.activity_type, 'fa-star')
    
    def get_type_color(self):
        """لون حسب نوع النشاط"""
        colors = {
            'sport': '#667eea',
            'culture': '#f093fb',
            'scientific': '#4facfe',
            'social': '#43e97b',
            'artistic': '#fa709a',
            'other': '#8cc040',
        }
        return colors.get(self.activity_type, '#8cc040')
    
    def get_type_gradient(self):
        """تدرج لوني للخلفية"""
        gradients = {
            'sport': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'culture': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'scientific': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'social': 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'artistic': 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'other': 'linear-gradient(135deg, #8cc040 0%, #6fa832 100%)',
        }
        return gradients.get(self.activity_type, 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')


class ActivityImage(models.Model):
    """صور إضافية للنشاط"""
    
    activity = models.ForeignKey(
        Activity,
        on_delete=models.CASCADE,
        related_name='images',
        verbose_name="النشاط"
    )
    
    image = models.ImageField(
        upload_to='activities/gallery/',
        verbose_name="الصورة"
    )
    
    caption = models.CharField(
        max_length=300,
        verbose_name="التعليق",
        blank=True
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    uploaded_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الرفع"
    )
    
    class Meta:
        verbose_name = "صورة نشاط"
        verbose_name_plural = "صور النشاطات"
        ordering = ['order', '-uploaded_at']
    
    def __str__(self):
        return f"صورة {self.activity.title}"