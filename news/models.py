from django.db import models
from django.utils.text import slugify
from django.contrib.auth.models import User


class News(models.Model):
    """
    الأخبار (للـ News Ticker في الأعلى)
    """
    
    title = models.CharField(
        max_length=300,
        verbose_name="عنوان الخبر"
    )
    
    slug = models.SlugField(
        max_length=300,
        unique=True,
        blank=True,
        verbose_name="الرابط الصديق"
    )
    
    summary = models.TextField(
        max_length=500,
        verbose_name="ملخص الخبر",
        blank=True
    )
    
    content = models.TextField(
        verbose_name="محتوى الخبر"
    )
    
    main_image = models.ImageField(
        upload_to='news/main/',
        verbose_name="الصورة الرئيسية",
        blank=True,
        null=True
    )
    
    author = models.ForeignKey(
        User,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        verbose_name="الكاتب",
        related_name='news_articles'
    )
    
    published_date = models.DateTimeField(
     verbose_name="تاريخ النشر",
     auto_now_add=False,  # ← غيّرناها
     null=True,
     blank=True,
     help_text="اترك فارغاً لاستخدام التاريخ الحالي"
)
    
    STATUS_CHOICES = [
        ('draft', 'مسودة'),
        ('published', 'منشور'),
        ('archived', 'مؤرشف'),
    ]
    
    status = models.CharField(
        max_length=20,
        choices=STATUS_CHOICES,
        default='published',
        verbose_name="الحالة"
    )
    
    views_count = models.IntegerField(
        default=0,
        verbose_name="عدد المشاهدات"
    )
    
    is_featured = models.BooleanField(
        default=False,
        verbose_name="خبر مميز؟"
    )
    
    show_in_ticker = models.BooleanField(
        default=False,
        verbose_name="إظهار في شريط الأخبار العلوي؟"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ آخر تحديث"
    )
    
    class Meta:
        verbose_name = "خبر"
        verbose_name_plural = "الأخبار"
        ordering = ['-published_date']
        
    def __str__(self):
        return self.title
    
    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title, allow_unicode=True)
        super().save(*args, **kwargs)


class NewsImage(models.Model):
    """صور إضافية للخبر"""
    
    news = models.ForeignKey(
        News,
        on_delete=models.CASCADE,
        related_name='images',
        verbose_name="الخبر"
    )
    
    image = models.ImageField(
        upload_to='news/gallery/',
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
        verbose_name = "صورة خبر"
        verbose_name_plural = "صور الأخبار"
        ordering = ['order', '-uploaded_at']
    
    def __str__(self):
        return f"صورة {self.news.title}"


class LatestNews(models.Model):
    """
    آخر الأخبار (القسم الجانبي المتحرك)
    """
    
    description = models.TextField(
        verbose_name="وصف الخبر",
        max_length=500
    )
    
    image = models.ImageField(
        upload_to='latest_news/',
        verbose_name="الصورة"
    )
    
    link = models.URLField(
        verbose_name="الرابط",
        blank=True,
        null=True
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
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
        verbose_name = "خبر جانبي"
        verbose_name_plural = "الأخبار الجانبية"
        ordering = ['order', '-created_at']
    
    def __str__(self):
        return self.description[:50]