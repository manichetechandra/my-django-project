from django.apps import AppConfig


class StudentsConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'students'
    verbose_name = 'إدارة الطلبة'
    
    def ready(self):
        """
        تحميل الـ Signals عند بدء Django
        
        الشرح:
        - ready() تُنفّذ عند بدء التطبيق
        - نستورد signals هنا لتفعيلها
        """
        import students.signals  # ← مهم جداً!