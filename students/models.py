from django.db import models
from django.contrib.auth.models import User
from django.utils import timezone
from django.core.validators import RegexValidator


class StudentProfile(models.Model):
    """ملف تعريف الطالب"""
    
    GENDER_CHOICES = [
        ('M', 'ذكر'),
        ('F', 'أنثى'),
    ]
    
    YEAR_CHOICES = [
        ('1', 'السنة الأولى'),
        ('2', 'السنة الثانية'),
        ('3', 'السنة الثالثة'),
        ('M1', 'ماستر 1'),
        ('M2', 'ماستر 2'),
        ('D', 'دكتوراه'),
    ]
    
    # ربط مع User
    user = models.OneToOneField(
        User,
        on_delete=models.CASCADE,
        related_name='student_profile',
        verbose_name="المستخدم"
    )
    
    # معلومات التسجيل
    registration_number = models.CharField(
        max_length=20,
        unique=True,
        verbose_name="رقم التسجيل"
    )
    
    # معلومات شخصية
    full_name_arabic = models.CharField(
        max_length=100,
        verbose_name="الاسم الكامل بالعربية"
    )
    
    birth_date = models.DateField(
        verbose_name="تاريخ الميلاد"
    )
    
    birth_place = models.CharField(
        max_length=100,
        verbose_name="مكان الميلاد"
    )
    
    gender = models.CharField(
        max_length=1,
        choices=GENDER_CHOICES,
        verbose_name="الجنس"
    )
    
    # معلومات الاتصال
    phone_regex = RegexValidator(
        regex=r'^(05|06|07)\d{8}$',
        message="رقم الهاتف يجب أن يبدأ بـ 05 أو 06 أو 07 ويحتوي على 10 أرقام"
    )
    phone = models.CharField(
        validators=[phone_regex],
        max_length=10,
        verbose_name="رقم الهاتف"
    )
    
    address = models.TextField(
        verbose_name="العنوان"
    )
    
    wilaya = models.CharField(
        max_length=100,
        verbose_name="الولاية"
    )
    
    # معلومات أكاديمية
    university = models.CharField(
        max_length=200,
        default="جامعة سطيف",
        verbose_name="الجامعة"
    )
    
    faculty = models.CharField(
        max_length=200,
        verbose_name="الكلية"
    )
    
    department = models.CharField(
        max_length=200,
        verbose_name="القسم/التخصص"
    )
    
    current_year = models.CharField(
        max_length=2,
        choices=YEAR_CHOICES,
        verbose_name="السنة الدراسية"
    )
    
    average = models.DecimalField(
        max_digits=4,
        decimal_places=2,
        null=True,
        blank=True,
        verbose_name="المعدل السنوي"
    )
    
    # صورة الطالب
    photo = models.ImageField(
        upload_to='students/photos/',
        null=True,
        blank=True,
        verbose_name="الصورة الشخصية"
    )
    
    # حالة الحساب
    is_verified = models.BooleanField(
        default=False,
        verbose_name="حساب مُفعّل"
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="حساب نشط"
    )
    
    # تواريخ
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ التسجيل"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="آخر تحديث"
    )
    
    class Meta:
        verbose_name = "ملف طالب"
        verbose_name_plural = "ملفات الطلبة"
        ordering = ['-created_at']
    
    def __str__(self):
        return f"{self.full_name_arabic} - {self.registration_number}"
 # ========================================
    # Methods جديدة للإشعارات ← أضف هنا!
    # ========================================
    
    def get_unread_notifications_count(self):
        """إرجاع عدد الإشعارات غير المقروءة"""
        return self.student_notifications.filter(is_read=False).count()
    
    def get_recent_notifications(self, limit=5):
        """إرجاع آخر الإشعارات"""
        return self.student_notifications.all()[:limit]

class ApplicationType(models.Model):
    """أنواع الطلبات المتاحة"""
    
    name = models.CharField(
        max_length=100,
        verbose_name="اسم الطلب"
    )
    
    description = models.TextField(
        verbose_name="الوصف"
    )
    
    icon = models.CharField(
        max_length=50,
        default="fa-file-text",
        verbose_name="الأيقونة"
    )
    
    color = models.CharField(
        max_length=7,
        default="#3498db",
        verbose_name="اللون"
    )
    
    required_documents = models.TextField(
        help_text="قائمة الوثائق المطلوبة (كل وثيقة في سطر)",
        verbose_name="الوثائق المطلوبة",
        blank=True
    )
    
    is_active = models.BooleanField(
        default=True,
        verbose_name="نشط"
    )
    
    order = models.IntegerField(
        default=0,
        verbose_name="الترتيب"
    )
    
    class Meta:
        verbose_name = "نوع طلب"
        verbose_name_plural = "أنواع الطلبات"
        ordering = ['order', 'name']
    
    def __str__(self):
        return self.name


class StudentApplication(models.Model):
    """طلبات الطلبة"""
    
    STATUS_CHOICES = [
        ('pending', 'قيد الانتظار'),
        ('under_review', 'قيد المراجعة'),
        ('approved', 'معتمد'),
        ('rejected', 'مرفوض'),
        ('cancelled', 'ملغي'),
    ]
    
    student = models.ForeignKey(
        StudentProfile,
        on_delete=models.CASCADE,
        related_name='applications',
        verbose_name="الطالب"
    )
    
    application_type = models.ForeignKey(
        ApplicationType,
        on_delete=models.CASCADE,
        verbose_name="نوع الطلب"
    )
    
    application_number = models.CharField(
        max_length=20,
        unique=True,
        verbose_name="رقم الطلب"
    )
    
    status = models.CharField(
        max_length=20,
        choices=STATUS_CHOICES,
        default='pending',
        verbose_name="الحالة"
    )
    
    details = models.TextField(
        blank=True,
        null=True,
        verbose_name="تفاصيل الطلب"
    )
    
    admin_notes = models.TextField(
        blank=True,
        null=True,
        verbose_name="ملاحظات الإدارة"
    )
    
    submitted_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ التقديم"
    )
    
    reviewed_at = models.DateTimeField(
        null=True,
        blank=True,
        verbose_name="تاريخ المراجعة"
    )
    
    reviewed_by = models.ForeignKey(
        User,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name='reviewed_applications',
        verbose_name="راجعه"
    )
    
    updated_at = models.DateTimeField(
        auto_now=True,
        verbose_name="آخر تحديث"
    )
    
    class Meta:
        verbose_name = "طلب"
        verbose_name_plural = "الطلبات"
        ordering = ['-submitted_at']
    
    def __str__(self):
        return f"{self.application_number} - {self.student.full_name_arabic}"
    
    def save(self, *args, **kwargs):
        if not self.application_number:
            year = timezone.now().year
            count = StudentApplication.objects.filter(
                submitted_at__year=year
            ).count() + 1
            self.application_number = f"{year}{count:05d}"
        super().save(*args, **kwargs)
    
    def get_status_color(self):
        colors = {
            'pending': '#f39c12',
            'under_review': '#3498db',
            'approved': '#2ecc71',
            'rejected': '#e74c3c',
            'cancelled': '#95a5a6',
        }
        return colors.get(self.status, '#95a5a6')
    
    def get_status_icon(self):
        icons = {
            'pending': 'fa-clock-o',
            'under_review': 'fa-search',
            'approved': 'fa-check-circle',
            'rejected': 'fa-times-circle',
            'cancelled': 'fa-ban',
        }
        return icons.get(self.status, 'fa-question')


class ApplicationDocument(models.Model):
    """وثائق الطلب"""
    
    application = models.ForeignKey(
        StudentApplication,
        on_delete=models.CASCADE,
        related_name='documents',
        verbose_name="الطلب"
    )
    
    document_name = models.CharField(
        max_length=200,
        verbose_name="اسم الوثيقة"
    )
    
    document_file = models.FileField(
        upload_to='students/documents/%Y/%m/',
        verbose_name="الملف"
    )
    
    uploaded_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="تاريخ الرفع"
    )
    
    class Meta:
        verbose_name = "وثيقة"
        verbose_name_plural = "الوثائق"
        ordering = ['-uploaded_at']
    
    def __str__(self):
        return f"{self.document_name} - {self.application.application_number}"


class StudentNotification(models.Model):
    """إشعارات الطلبة"""
    student = models.ForeignKey(
        StudentProfile, 
        on_delete=models.CASCADE, 
        related_name='student_notifications',
        verbose_name='الطالب'
    )
    title = models.CharField('العنوان', max_length=200)
    message = models.TextField('الرسالة')
    link = models.CharField('الرابط', max_length=500, blank=True, null=True)
    is_read = models.BooleanField('مقروء', default=False)
    created_at = models.DateTimeField('تاريخ الإنشاء', auto_now_add=True)
    read_at = models.DateTimeField('تاريخ القراءة', blank=True, null=True)  # ← جديد!
    
    # ← جديد: أنواع الإشعارات
    NOTIFICATION_TYPES = [
        ('info', 'معلومة'),
        ('success', 'نجاح'),
        ('warning', 'تحذير'),
        ('error', 'خطأ'),
    ]
    notification_type = models.CharField(
        'نوع الإشعار',
        max_length=20,
        choices=NOTIFICATION_TYPES,
        default='info'
    )
    
    class Meta:
        verbose_name = 'إشعار طالب'
        verbose_name_plural = 'إشعارات الطلبة'
        ordering = ['-created_at']
    
    def __str__(self):
        return f'{self.student.full_name_arabic} - {self.title}'
    
    def mark_as_read(self):
        """تمييز الإشعار كمقروء"""
        if not self.is_read:
            self.is_read = True
            self.read_at = timezone.now()
            self.save()
    
    def get_icon(self):
        """إرجاع أيقونة حسب النوع"""
        icons = {
            'info': 'fa-info-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'error': 'fa-times-circle',
        }
        return icons.get(self.notification_type, 'fa-bell')
    
    def get_color(self):
        """إرجاع لون حسب النوع"""
        colors = {
            'info': '#3498db',
            'success': '#2ecc71',
            'warning': '#f39c12',
            'error': '#e74c3c',
        }
        return colors.get(self.notification_type, '#3498db')