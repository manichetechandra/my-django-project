from django import forms
from .models import ContactMessage

class ContactForm(forms.ModelForm):
    """نموذج الاتصال"""
    
    class Meta:
        model = ContactMessage
        fields = ['full_name', 'email', 'phone', 'subject', 'message']
        
        widgets = {
            'full_name': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'الاسم الكامل',
                'required': True
            }),
            'email': forms.EmailInput(attrs={
                'class': 'form-control',
                'placeholder': 'example@email.com',
                'required': True
            }),
            'phone': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': '0555123456 (اختياري)',
            }),
            'subject': forms.Select(attrs={
                'class': 'form-control',
                'required': True
            }),
            'message': forms.Textarea(attrs={
                'class': 'form-control',
                'rows': 6,
                'placeholder': 'اكتب رسالتك أو استفسارك هنا...',
                'required': True
            }),
        }
    
    def clean_full_name(self):
        """التحقق من الاسم"""
        full_name = self.cleaned_data.get('full_name')
        if len(full_name) < 3:
            raise forms.ValidationError('الاسم يجب أن يكون 3 أحرف على الأقل')
        return full_name
    
    def clean_message(self):
        """التحقق من الرسالة"""
        message = self.cleaned_data.get('message')
        if len(message) < 10:
            raise forms.ValidationError('الرسالة يجب أن تكون 10 أحرف على الأقل')
        return message
    
    def clean_phone(self):
        """التحقق من رقم الهاتف"""
        phone = self.cleaned_data.get('phone')
        if phone:
            # إزالة المسافات والشرطات
            phone = phone.replace(' ', '').replace('-', '')
            # التحقق من أنه أرقام فقط
            if not phone.isdigit():
                raise forms.ValidationError('رقم الهاتف يجب أن يحتوي على أرقام فقط')
            if len(phone) < 10:
                raise forms.ValidationError('رقم الهاتف غير صحيح')
        return phone