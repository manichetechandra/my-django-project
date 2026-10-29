# ترجمات إضافية مفتاحها النص العربي نفسه (للنصوص التي أضيفت بعد keys-v1.tsv)
T = {
    '{count} شخص': ("{count} personnes", "{count} people"),
    '({count} نشاط)': ("({count} activités)", "({count} activities)"),
    '{count} مشارك': ("{count} participants", "{count} participants"),
    '{count} طالب': ("{count} étudiants", "{count} students"),
    '({count} إقامة)': ("({count} résidences)", "({count} residences)"),
    '({count} قسم)': ("({count} services)", "({count} departments)"),
    '{count} تحميل': ("{count} téléchargements", "{count} downloads"),
    '{count} ملف في هذا التصنيف': ("{count} fichier(s) dans cette catégorie", "{count} file(s) in this category"),
    '{count} ملف': ("{count} fichiers", "{count} files"),
    '{count} مشاهدة': ("{count} vues", "{count} views"),
    '{count} سؤال': ("{count} questions", "{count} questions"),
    '({count} طلب)': ("({count} demandes)", "({count} applications)"),
    'عرض {count} طلب من أصل {total}': ("Affichage de {count} demande(s) sur {total}", "Showing {count} of {total} applications"),
    'العدد: {count} طلب': ("Nombre : {count} demande(s)", "Count: {count} application(s)"),
    'الطلب {n} من {total}': ("Demande {n} sur {total}", "Application {n} of {total}"),
}

# عناوين مختصرة في القائمة العلوية (tx('nav', ...)) حتى لا تلتف القائمة في الفرنسية والإنجليزية
T.update({
    'nav|الإقامات الجامعية': ("Résidences", "Residences"),
    'nav|الخدمات الجامعية': ("Services", "Services"),
    'nav|الأسئلة الشائعة': ("FAQ", "FAQ"),
    'nav|لوحة المشرف': ("Superviseur", "Supervisor"),
})
