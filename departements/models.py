from django.db import models
from django.utils.text import slugify

class Departement(models.Model):
    """
    موديل الأقسام الأكاديمية
    """
    
    # الحقول الأساسية
    name = models.CharField(
        max_length=200,
        verbose_name="اسم القسم",
        unique=True,
        help_text="مثال: قسم الإعلام الآلي"
    )
    
    slug = models.SlugField(
        max_length=200,
        unique=True,
        blank=True,
        verbose_name="الرابط الصديق"
    )
    
    description = models.TextField(
        verbose_name="الوصف",
        help_text="وصف تفصيلي للقسم"
    )
    
    # الصورة (واحدة فقط)
    image = models.ImageField(
        upload_to='departements/',
        verbose_name="صورة القسم",
        help_text="صورة تمثيلية للقسم"
    )
    
    # معلومات إضافية
    head_of_department = models.CharField(
        max_length=200,
        verbose_name="رئيس القسم",
        blank=True,
        null=True
    )
    
    email = models.EmailField(
        verbose_name="البريد الإلكتروني",
        blank=True,
        null=True
    )
    
    phone = models.CharField(
        max_length=20,
        verbose_name="رقم الهاتف",
        blank=True,
        null=True
    )
    
    website = models.URLField(
        verbose_name="الموقع الإلكتروني",
        blank=True,
        null=True
    )
    
    # حقول النظام
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ آخر تحديث"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    class Meta:
        verbose_name = "قسم"
        verbose_name_plural = "الأقسام"
        ordering = ['order', 'name']
    
    def __str__(self):
        return self.name
    
    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.name, allow_unicode=True)
        super().save(*args, **kwargs)