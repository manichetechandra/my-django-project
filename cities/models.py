from django.db import models
from django.utils.text import slugify

class Cite(models.Model):
    """
    موديل الإقامات الجامعية
    مثال: دباغين، مريم، علي سواحي، هاشمي حسين
    """
    
    # الحقول الأساسية
    name = models.CharField(
        max_length=200,
        verbose_name="اسم الإقامة",
        unique=True,
        help_text="مثال: دباغين"
    )
    
    slug = models.SlugField(
        max_length=200,
        unique=True,
        blank=True,
        verbose_name="الرابط الصديق",
        help_text="يتم إنشاؤه تلقائياً من الاسم"
    )
    
    description = models.TextField(
        verbose_name="الوصف",
        help_text="وصف تفصيلي للإقامة"
    )
    
    # الصورة الرئيسية
    main_image = models.ImageField(
        upload_to='cites/main/',
        verbose_name="الصورة الرئيسية",
        help_text="الصورة الأساسية للإقامة"
    )
    
    # معلومات إضافية (اختياري)
    address = models.CharField(
        max_length=300,
        verbose_name="العنوان",
        blank=True,
        null=True
    )
    
    capacity = models.IntegerField(
        verbose_name="السعة الاستيعابية",
        help_text="عدد الطلاب الممكن استيعابهم",
        blank=True,
        null=True
    )
    
    phone = models.CharField(
        max_length=20,
        verbose_name="رقم الهاتف",
        blank=True,
        null=True
    )
    
    # حقول النظام
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟",
        help_text="هل الإقامة نشطة ومعروضة؟"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ آخر تحديث"
    )
    
    # الترتيب
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب",
        help_text="ترتيب العرض (الأصغر أولاً)"
    )
    
    class Meta:
        verbose_name = "إقامة"
        verbose_name_plural = "الإقامات"
        ordering = ['order', 'name']
        
    def __str__(self):
        return self.name
    
    def save(self, *args, **kwargs):
        """
        عند الحفظ، ننشئ slug تلقائياً من الاسم
        """
        if not self.slug:
            self.slug = slugify(self.name, allow_unicode=True)
        super().save(*args, **kwargs)

class CiteImage(models.Model):
    """
    صور إضافية للإقامة (غير الصورة الرئيسية)
    """
    
    cite = models.ForeignKey(
        Cite,
        on_delete=models.CASCADE,
        related_name='images',
        verbose_name="الإقامة"
    )
    
    image = models.ImageField(
        upload_to='cites/gallery/',
        verbose_name="الصورة"
    )
    
    caption = models.CharField(
        max_length=200,
        verbose_name="التعليق",
        blank=True,
        help_text="وصف اختياري للصورة"
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
        verbose_name = "صورة إقامة"
        verbose_name_plural = "صور الإقامات"
        ordering = ['order', '-uploaded_at']
    
    def __str__(self):
        return f"صورة {self.cite.name} - {self.id}"        