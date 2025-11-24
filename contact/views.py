from django.shortcuts import render, redirect
from django.contrib import messages
from .forms import ContactForm
from .models import ContactMessage

def get_client_ip(request):
    """الحصول على IP المرسل"""
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip = x_forwarded_for.split(',')[0]
    else:
        ip = request.META.get('REMOTE_ADDR')
    return ip

def contact_view(request):
    """صفحة اتصل بنا"""
    
    if request.method == 'POST':
        form = ContactForm(request.POST)
        
        if form.is_valid():
            # حفظ الرسالة
            contact_message = form.save(commit=False)
            contact_message.ip_address = get_client_ip(request)
            contact_message.save()
            
            # رسالة نجاح
            messages.success(
                request,
                f'شكراً لك {contact_message.full_name}! تم إرسال رسالتك بنجاح. '
                'سنتواصل معك في أقرب وقت ممكن.'
            )
            
            return redirect('contact:contact')
        else:
            # رسالة خطأ
            messages.error(
                request,
                'يرجى تصحيح الأخطاء في النموذج'
            )
    else:
        form = ContactForm()
    
    # إحصائيات بسيطة
    total_messages = ContactMessage.objects.count()
    
    context = {
        'form': form,
        'page_title': 'اتصل بنا',
        'total_messages': total_messages,
    }
    
    return render(request, 'contact/contact.html', context)