from django.db import models

class FAQCategory(models.Model):
    """تصنيفات الأسئلة الشائعة"""
    
    name = models.CharField(
        max_length=100,
        verbose_name="اسم التصنيف"
    )
    
    icon = models.CharField(
        max_length=50,
        default="fa-question-circle",
        verbose_name="الأيقونة",
        help_text="مثال: fa-bus, fa-home, fa-money"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب",
        help_text="رقم أقل = يظهر أولاً"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    class Meta:
        verbose_name = "تصنيف"
        verbose_name_plural = "التصنيفات"
        ordering = ['order', 'name']
    
    def __str__(self):
        return self.name
    
    def get_color(self):
        """لون حسب التصنيف"""
        colors = {
            'عام': '#3498db',
            'المنح': '#2ecc71',
            'السكن': '#e74c3c',
            'النقل': '#9b59b6',
            'الإطعام': '#f39c12',
        }
        return colors.get(self.name, '#95a5a6')


class FAQ(models.Model):
    """الأسئلة الشائعة"""
    
    category = models.ForeignKey(
        FAQCategory,
        on_delete=models.CASCADE,
        related_name='faqs',
        verbose_name="التصنيف"
    )
    
    question = models.CharField(
        max_length=300,
        verbose_name="السؤال"
    )
    
    answer = models.TextField(
        verbose_name="الإجابة"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب",
        help_text="رقم أقل = يظهر أولاً"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط؟"
    )
    
    views_count = models.PositiveIntegerField(
        default=0,
        editable=False,
        verbose_name="عدد المشاهدات"
    )
    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الإنشاء"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="تاريخ التحديث"
    )
    
    class Meta:
        verbose_name = "سؤال شائع"
        verbose_name_plural = "الأسئلة الشائعة"
        ordering = ['category__order', 'order', '-created_at']
    
    def __str__(self):
        return self.question
    
    def increment_views(self):
        """زيادة عداد المشاهدات"""
        self.views_count += 1
        self.save(update_fields=['views_count'])