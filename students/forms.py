from django import forms
from django.contrib.auth.models import User
from django.contrib.auth.forms import UserCreationForm
from .models import StudentProfile, StudentApplication, ApplicationDocument


class StudentRegistrationForm(UserCreationForm):
    """نموذج تسجيل طالب جديد"""
    
    # معلومات التسجيل
    registration_number = forms.CharField(
        max_length=20,
        required=True,
        label="رقم التسجيل الجامعي",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'رقم التسجيل الجامعي'
        })
    )
    
    email = forms.EmailField(
        required=True,
        label="البريد الإلكتروني",
        widget=forms.EmailInput(attrs={
            'class': 'form-control',
            'placeholder': 'البريد الإلكتروني الجامعي'
        })
    )
    
    # معلومات شخصية
    full_name_arabic = forms.CharField(
        max_length=100,
        required=True,
        label="الاسم الكامل بالعربية",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'الاسم الكامل بالعربية'
        })
    )
    
    birth_date = forms.DateField(
        required=True,
        label="تاريخ الميلاد",
        widget=forms.DateInput(attrs={
            'class': 'form-control',
            'type': 'date'
        })
    )
    
    birth_place = forms.CharField(
        max_length=100,
        required=True,
        label="مكان الميلاد",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'مكان الميلاد'
        })
    )
    
    gender = forms.ChoiceField(
        choices=[('M', 'ذكر'), ('F', 'أنثى')],
        required=True,
        label="الجنس",
        widget=forms.Select(attrs={'class': 'form-control'})
    )
    
    # معلومات الاتصال
    phone = forms.CharField(
        max_length=10,
        required=True,
        label="رقم الهاتف",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': '0555123456'
        })
    )
    
    address = forms.CharField(
        required=True,
        label="العنوان",
        widget=forms.Textarea(attrs={
            'class': 'form-control',
            'rows': 3,
            'placeholder': 'العنوان الكامل'
        })
    )
    
    wilaya = forms.CharField(
        max_length=100,
        required=True,
        label="الولاية",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'الولاية'
        })
    )
    
    # معلومات أكاديمية
    faculty = forms.CharField(
        max_length=200,
        required=True,
        label="الكلية",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'الكلية'
        })
    )
    
    department = forms.CharField(
        max_length=200,
        required=True,
        label="القسم/التخصص",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'القسم/التخصص'
        })
    )
    
    current_year = forms.ChoiceField(
        choices=[
            ('1', 'السنة الأولى'),
            ('2', 'السنة الثانية'),
            ('3', 'السنة الثالثة'),
            ('M1', 'ماستر 1'),
            ('M2', 'ماستر 2'),
            ('D', 'دكتوراه'),
        ],
        required=True,
        label="السنة الدراسية",
        widget=forms.Select(attrs={'class': 'form-control'})
    )
    
    class Meta:
        model = User
        fields = ['email', 'password1', 'password2']
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        
        self.fields['password1'].widget.attrs.update({
            'class': 'form-control',
            'placeholder': 'كلمة المرور'
        })
        self.fields['password2'].widget.attrs.update({
            'class': 'form-control',
            'placeholder': 'تأكيد كلمة المرور'
        })
        self.fields['password1'].label = 'كلمة المرور'
        self.fields['password2'].label = 'تأكيد كلمة المرور'
        
       
        self.fields['email'].required = False
    
    def clean_registration_number(self):
        registration_number = self.cleaned_data.get('registration_number')
        if StudentProfile.objects.filter(registration_number=registration_number).exists():
            raise forms.ValidationError('رقم التسجيل هذا مسجل مسبقاً')
        if User.objects.filter(username=registration_number).exists():
            raise forms.ValidationError('رقم التسجيل هذا مسجل مسبقاً')
        return registration_number
    
    def clean_email(self):
        email = self.cleaned_data.get('email')
        if User.objects.filter(email=email).exists():
            raise forms.ValidationError('البريد الإلكتروني مسجل مسبقاً')
        return email
    
    def clean_phone(self):
        phone = self.cleaned_data.get('phone')
        if not phone.startswith(('05', '06', '07')) or len(phone) != 10:
            raise forms.ValidationError('رقم الهاتف يجب أن يبدأ بـ 05 أو 06 أو 07 ويحتوي على 10 أرقام')
        return phone
    
    def save(self, commit=True):
        user = super().save(commit=False)
        user.username = self.cleaned_data['registration_number']
        user.email = self.cleaned_data['email']
        
        if commit:
            user.save()
        return user


class StudentLoginForm(forms.Form):
    """نموذج تسجيل الدخول"""
    
    username = forms.CharField(
        max_length=150,
        required=True,
        label="رقم التسجيل أو اسم المستخدم",
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'رقم التسجيل أو اسم المستخدم'
        })
    )
    
    password = forms.CharField(
        required=True,
        label="كلمة المرور",
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'كلمة المرور'
        })
    )
    
    remember_me = forms.BooleanField(
        required=False,
        label="تذكرني",
        widget=forms.CheckboxInput(attrs={
            'class': 'form-check-input'
        })
    )


class ApplicationSubmissionForm(forms.ModelForm):
    """نموذج تقديم طلب جديد"""
    
    class Meta:
        model = StudentApplication
        fields = ['application_type', 'details']
        widgets = {
            'application_type': forms.Select(attrs={
                'class': 'form-control',
                'required': True
            }),
            'details': forms.Textarea(attrs={
                'class': 'form-control',
                'rows': 5,
                'placeholder': 'اكتب تفاصيل إضافية عن الطلب (اختياري)'
            }),
        }
        labels = {
            'application_type': 'نوع الطلب',
            'details': 'تفاصيل الطلب',
        }
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # فقط أنواع الطلبات النشطة
        from .models import ApplicationType
        self.fields['application_type'].queryset = ApplicationType.objects.filter(is_active=True)
        self.fields['details'].required = False


class ApplicationDocumentForm(forms.ModelForm):
    """نموذج رفع وثيقة"""
    
    class Meta:
        model = ApplicationDocument
        fields = ['document_name', 'document_file']
        widgets = {
            'document_name': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'اسم الوثيقة'
            }),
            'document_file': forms.FileInput(attrs={
                'class': 'form-control',
                'accept': '.pdf,.jpg,.jpeg,.png'
            }),
        }
        labels = {
            'document_name': 'اسم الوثيقة',
            'document_file': 'الملف',
        }


        # ========================================
# Forms إدارة الحساب الشخصي
# ========================================

# ========================================
# Forms إدارة الحساب الشخصي
# ========================================

class StudentProfileUpdateForm(forms.ModelForm):
    """
    نموذج تحديث معلومات الطالب الشخصية
    
    الشرح:
    - يسمح بتعديل المعلومات الأساسية
    - لا يسمح بتعديل رقم التسجيل (للأمان)
    """
    
    class Meta:
        model = StudentProfile
        fields = [
            'full_name_arabic',
            'birth_date',
            'birth_place',
            'gender',
            'phone',
            'address',
            'wilaya',
            'faculty',
            'department',
            'current_year',
            'photo'  # ← غيرنا من profile_image إلى photo
        ]
        
        widgets = {
            'full_name_arabic': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'الاسم الكامل بالعربية'
            }),
            'birth_date': forms.DateInput(attrs={
                'class': 'form-control',
                'type': 'date'
            }),
            'birth_place': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'مكان الميلاد'
            }),
            'gender': forms.Select(attrs={
                'class': 'form-control'
            }),
            'phone': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': '0555123456'
            }),
            'address': forms.Textarea(attrs={
                'class': 'form-control',
                'rows': 3,
                'placeholder': 'العنوان الكامل'
            }),
            'wilaya': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'الولاية'
            }),
            'faculty': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'الكلية'
            }),
            'department': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'القسم/التخصص'
            }),
            'current_year': forms.Select(attrs={
                'class': 'form-control'
            }),
            'photo': forms.FileInput(attrs={  # ← غيرنا هنا أيضاً
                'class': 'form-control',
                'accept': 'image/*'
            })
        }


class UserEmailUpdateForm(forms.ModelForm):
    """
    نموذج تحديث البريد الإلكتروني
    
    الشرح:
    - منفصل لأن البريد في User وليس StudentProfile
    """
    
    class Meta:
        model = User
        fields = ['email']
        
        widgets = {
            'email': forms.EmailInput(attrs={
                'class': 'form-control',
                'placeholder': 'البريد الإلكتروني (اختياري)'
            })
        }
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['email'].required = False


class PasswordChangeForm(forms.Form):
    """
    نموذج تغيير كلمة المرور
    
    الشرح:
    - يطلب كلمة المرور القديمة (للأمان)
    - كلمة المرور الجديدة مرتين (للتأكيد)
    - يتحقق من تطابقهما
    """
    
    old_password = forms.CharField(
        label='كلمة المرور القديمة',
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'أدخل كلمة المرور الحالية'
        })
    )
    
    new_password1 = forms.CharField(
        label='كلمة المرور الجديدة',
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'أدخل كلمة المرور الجديدة'
        }),
        help_text='يجب أن تحتوي على 8 أحرف على الأقل'
    )
    
    new_password2 = forms.CharField(
        label='تأكيد كلمة المرور الجديدة',
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'أعد إدخال كلمة المرور الجديدة'
        })
    )
    
    def clean(self):
        """
        التحقق من تطابق كلمات المرور
        """
        cleaned_data = super().clean()
        new_password1 = cleaned_data.get('new_password1')
        new_password2 = cleaned_data.get('new_password2')
        
        if new_password1 and new_password2:
            if new_password1 != new_password2:
                raise forms.ValidationError('كلمات المرور الجديدة غير متطابقة')
        
        return cleaned_data