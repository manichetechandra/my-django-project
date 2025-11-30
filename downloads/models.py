from django.db import models
from django.urls import reverse
from django.utils.text import slugify
import os

class DownloadFile(models.Model):
    """ملفات التحميل"""
    
    # التصنيفات
    CATEGORY_CHOICES = [
        ('scholarship_forms', 'نماذج المنح والإعانات'),
        ('housing_forms', 'نماذج السكن الجامعي'),
        ('transport_forms', 'نماذج النقل'),
        ('catering_forms', 'نماذج المطاعم'),
        ('guides', 'دلائل إرشادية'),
        ('regulations', 'لوائح وقوانين'),
        ('reports', 'تقارير وإحصائيات'),
        ('other', 'أخرى'),
    ]
    
    # الحقول الأساسية
    title = models.CharField(
        max_length=200,
        verbose_name="عنوان الملف"
    )
    
    slug = models.SlugField(
        max_length=200,
        unique=True,
        blank=True,
        verbose_name="الرابط"
    )
    
    description = models.TextField(
        verbose_name="الوصف",
        help_text="وصف مختصر عن محتوى الملف"
    )
    
    category = models.CharField(
        max_length=30,
        choices=CATEGORY_CHOICES,
        verbose_name="التصنيف"
    )
    
    # الملف
    file = models.FileField(
        upload_to='downloads/%Y/%m/',
        verbose_name="الملف",
        help_text="PDF, DOC, DOCX, XLS, XLSX (حد أقصى 10 MB)"
    )
    
    # معلومات الملف
    file_size = models.IntegerField(
        default=0,
        verbose_name="حجم الملف (بايت)",
        editable=False
    )
    
    downloads_count = models.PositiveIntegerField(
        default=0,
        verbose_name="عدد التحميلات",
        editable=False
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
        verbose_name="تاريخ الرفع"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "ملف تحميل"
        verbose_name_plural = "ملفات التحميل"
        ordering = ['display_order', '-created_at']
    
    def __str__(self):
        return self.title
    
    def save(self, *args, **kwargs):
        """حفظ مع إنشاء slug وحساب حجم الملف"""
        # إنشاء slug
        if not self.slug:
            base_slug = slugify(self.title, allow_unicode=True)
            slug = base_slug
            counter = 1
            while DownloadFile.objects.filter(slug=slug).exists():
                slug = f"{base_slug}-{counter}"
                counter += 1
            self.slug = slug
        
        # حساب حجم الملف
        if self.file:
            self.file_size = self.file.size
        
        super().save(*args, **kwargs)
    
    def get_absolute_url(self):
        """رابط التحميل"""
        return reverse('downloads:download_file', kwargs={'slug': self.slug})
    
    def get_file_extension(self):
        """امتداد الملف"""
        return os.path.splitext(self.file.name)[1].lower()
    
    def get_file_icon(self):
        """أيقونة حسب نوع الملف"""
        ext = self.get_file_extension()
        icons = {
            '.pdf': 'fa-file-pdf-o',
            '.doc': 'fa-file-word-o',
            '.docx': 'fa-file-word-o',
            '.xls': 'fa-file-excel-o',
            '.xlsx': 'fa-file-excel-o',
            '.ppt': 'fa-file-powerpoint-o',
            '.pptx': 'fa-file-powerpoint-o',
            '.zip': 'fa-file-archive-o',
            '.rar': 'fa-file-archive-o',
        }
        return icons.get(ext, 'fa-file-o')
    
    def get_file_color(self):
        """لون حسب نوع الملف"""
        ext = self.get_file_extension()
        colors = {
            '.pdf': '#e74c3c',      # أحمر
            '.doc': '#3498db',      # أزرق
            '.docx': '#3498db',
            '.xls': '#2ecc71',      # أخضر
            '.xlsx': '#2ecc71',
            '.ppt': '#e67e22',      # برتقالي
            '.pptx': '#e67e22',
            '.zip': '#95a5a6',      # رمادي
            '.rar': '#95a5a6',
        }
        return colors.get(ext, '#34495e')
    
    def get_file_size_display(self):
        """عرض حجم الملف بشكل مقروء"""
        size = self.file_size
        if size < 1024:
            return f"{size} بايت"
        elif size < 1024 * 1024:
            return f"{size / 1024:.1f} KB"
        else:
            return f"{size / (1024 * 1024):.1f} MB"
    
    def get_category_color(self):
        """لون حسب التصنيف"""
        colors = {
            'scholarship_forms': '#2ecc71',
            'housing_forms': '#e74c3c',
            'transport_forms': '#3498db',
            'catering_forms': '#f39c12',
            'guides': '#9b59b6',
            'regulations': '#34495e',
            'reports': '#1abc9c',
            'other': '#95a5a6',
        }
        return colors.get(self.category, '#95a5a6')
    
    def increment_downloads(self):
        """زيادة عداد التحميلات"""
        self.downloads_count += 1
        self.save(update_fields=['downloads_count'])