from django.db import models


class Slider(models.Model):
    """صور السلايدر في الصفحة الرئيسية"""
    
    title = models.CharField(
        max_length=200,
        verbose_name="العنوان",
        help_text="العنوان الذي يظهر على الصورة"
    )
    
    subtitle = models.CharField(
        max_length=300,
        verbose_name="العنوان الفرعي",
        blank=True,
        help_text="نص إضافي تحت العنوان"
    )
    
    image = models.ImageField(
        upload_to='slider/',
        verbose_name="صورة السلايدر",
        help_text="يُفضل أن تكون بحجم 1920x1080 بكسل"
    )
    
    link = models.URLField(
        verbose_name="الرابط",
        blank=True,
        null=True,
        help_text="رابط اختياري عند الضغط على الصورة"
    )
    
    button_text = models.CharField(
        max_length=50,
        verbose_name="نص الزر",
        blank=True,
        help_text="مثال: اقرأ المزيد، سجل الآن"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب",
        help_text="ترتيب ظهور الصورة (الأصغر أولاً)"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإضافة"
    )
    
    class Meta:
        verbose_name = "صورة سلايدر"
        verbose_name_plural = "صور السلايدر"
        ordering = ['order', '-created_at']
    
    def __str__(self):
        return self.title


class Statistics(models.Model):
    """الإحصائيات في الصفحة الرئيسية"""
    
    icon = models.CharField(
        max_length=50,
        verbose_name="أيقونة",
        help_text="اسم الأيقونة من Font Awesome"
    )
    
    number = models.IntegerField(
        verbose_name="الرقم"
    )
    
    label = models.CharField(
        max_length=100,
        verbose_name="التسمية"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    class Meta:
        verbose_name = "إحصائية"
        verbose_name_plural = "الإحصائيات"
        ordering = ['order']
    
    def __str__(self):
        return f"{self.number} {self.label}"


class DirectorWord(models.Model):
    """كلمة مدير المديرية"""
    
    director_name = models.CharField(
        max_length=200,
        verbose_name="اسم المدير",
        help_text="مثال: د. أحمد بن محمد"
    )
    
    director_title = models.CharField(
        max_length=200,
        verbose_name="المنصب",
        default="مدير الخدمات الجامعية",
        help_text="مثال: مدير الخدمات الجامعية"
    )
    
    director_image = models.ImageField(
        upload_to='director/',
        verbose_name="صورة المدير",
        blank=True,
        null=True,
        help_text="صورة شخصية للمدير"
    )
    
    word_title = models.CharField(
        max_length=200,
        verbose_name="عنوان الكلمة",
        default="كلمة المدير",
        help_text="العنوان الذي يظهر فوق الكلمة"
    )
    
    word_content = models.TextField(
        verbose_name="نص الكلمة",
        help_text="كلمة المدير الكاملة"
    )
    
    signature = models.ImageField(
        upload_to='director/signatures/',
        verbose_name="التوقيع",
        blank=True,
        null=True,
        help_text="صورة توقيع المدير (اختياري)"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟",
        help_text="إظهار الكلمة في الصفحة الرئيسية"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإضافة"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "كلمة المدير"
        verbose_name_plural = "كلمة المدير"
        ordering = ['-created_at']
    
    def __str__(self):
        return f"كلمة {self.director_name}"
    
class OrganizationStructure(models.Model):
    """الهيكل التنظيمي للمديرية"""
    
    title = models.CharField(
        max_length=200,
        verbose_name="العنوان",
        default="الهيكل التنظيمي"
    )
    
    description = models.TextField(
        verbose_name="المقدمة",
        help_text="نص تعريفي عن الهيكل التنظيمي",
        blank=True
    )
    
    organization_chart = models.ImageField(
        upload_to='organization/',
        verbose_name="صورة الهيكل التنظيمي",
        help_text="مخطط الهيكل التنظيمي",
        blank=True,
        null=True
    )
    
    content = models.TextField(
        verbose_name="المحتوى التفصيلي",
        help_text="شرح تفصيلي للهيكل",
        blank=True
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "الهيكل التنظيمي"
        verbose_name_plural = "الهيكل التنظيمي"
    
    def __str__(self):
        return self.title

class DirectorateHistory(models.Model):
    """تاريخ المديرية"""
    
    title = models.CharField(
        max_length=200,
        verbose_name="العنوان",
        default="تاريخ المديرية"
    )
    
    introduction = models.TextField(
        verbose_name="المقدمة",
        help_text="نبذة تاريخية موجزة"
    )
    
    content = models.TextField(
        verbose_name="المحتوى التفصيلي",
        help_text="تاريخ المديرية بالتفصيل"
    )
    
    main_image = models.ImageField(
        upload_to='history/',
        verbose_name="الصورة الرئيسية",
        blank=True,
        null=True
    )
    
    founding_year = models.IntegerField(
        verbose_name="سنة التأسيس",
        help_text="مثال: 1970",
        blank=True,
        null=True
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "تاريخ المديرية"
        verbose_name_plural = "تاريخ المديرية"
    
    def __str__(self):
        return self.title    