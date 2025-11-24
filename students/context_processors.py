def student_profile(request):
    """إضافة معلومات الطالب لكل الصفحات بشكل آمن"""
    
    profile = None
    if request.user.is_authenticated:
        try:
            profile = request.user.student_profile
        except:
            profile = None
    
    return {'student_profile': profile}