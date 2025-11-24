from django.shortcuts import redirect
from django.contrib import messages
from functools import wraps

# ========================================
# Decorator للتحقق من أن المستخدم مشرف
# ========================================

def staff_required(view_func):
    """
    ديكوريتور للتحقق من أن المستخدم موظف/مشرف
    
    الفكرة:
    - نفحص إذا كان المستخدم مسجل دخول
    - نفحص إذا كان موظف (is_staff = True)
    - إذا لا، نرجعه للصفحة الرئيسية مع رسالة خطأ
    
    الاستخدام:
    @staff_required
    def my_view(request):
        ...
    """
    
    @wraps(view_func)  # هذا يحافظ على معلومات الدالة الأصلية
    def wrapper(request, *args, **kwargs):
        # التحقق 1: هل المستخدم مسجل دخول؟
        if not request.user.is_authenticated:
            messages.error(request, 'يجب تسجيل الدخول أولاً')
            return redirect('students:login')
        
        # التحقق 2: هل المستخدم موظف/مشرف؟
        if not request.user.is_staff:
            messages.error(request, 'ليس لديك صلاحية الوصول لهذه الصفحة')
            return redirect('students:dashboard')
        
        # إذا نجح التحقق، ننفذ الدالة الأصلية
        return view_func(request, *args, **kwargs)
    
    return wrapper