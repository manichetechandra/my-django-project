#!/usr/bin/env python3
"""ترجمة المحتوى التجريبي (book/demo/demo-content.sql + أنواع الطلبات والإشعارات في demo-seed.php)
إلى الفرنسية والإنجليزية — بديل يدوي لما تفعله الترجمة التلقائية (Gemini) في الموقع الحقيقي.

  python3 dev/demo_i18n.py      ← يكتب dev/demo-i18n.sql (تستعمله dev/demo-site.sh)
"""
import os

# (الجدول، شرط WHERE، {الحقل: (français, english)})
ROWS = [
    # ------------------------------------------------------------ السلايدر
    ('sliders', '`order` = 1', {
        'title': ("Bienvenue à la Direction des Œuvres Universitaires de Sétif", "Welcome to the Directorate of University Services of Sétif"),
        'subtitle': ("Au service de l'étudiant : hébergement, restauration, transport et bourses", "Serving students: accommodation, catering, transport and scholarships"),
    }),
    ('sliders', '`order` = 2', {
        'title': ("Ouverture des inscriptions aux résidences universitaires", "Registration for university residences is now open"),
        'subtitle': ("Année universitaire 2026/2027 — déposez votre demande depuis l'espace étudiant", "Academic year 2026/2027 — submit your application from the student area"),
    }),
    ('sliders', '`order` = 3', {
        'title': ("Des restaurants universitaires aux repas équilibrés", "University restaurants with balanced meals"),
        'subtitle': ("Trois repas par jour à des prix symboliques", "Three meals a day at symbolic prices"),
    }),
    # ------------------------------------------------------------ الإحصائيات
    ('statistics', '`order` = 1', {'label': ("Lits dans les résidences", "Beds in residences")}),
    ('statistics', '`order` = 2', {'label': ("Repas par jour", "Meals per day")}),
    ('statistics', '`order` = 3', {'label': ("Bus de transport", "Transport buses")}),
    ('statistics', '`order` = 4', {'label': ("Étudiants boursiers", "Scholarship students")}),
    # ------------------------------------------------------------ كلمة المدير
    ('director_words', 'id = 1', {
        'director_name': ("Mohamed El Amine Benali", "Mohamed El Amine Benali"),
        'director_title': ("Directeur des Œuvres Universitaires", "Director of University Services"),
        'word_title': ("Mot du directeur", "Director's message"),
        'word_content': (
            "Au nom de Dieu, le Clément, le Miséricordieux\n\n"
            "J'ai le plaisir de vous souhaiter la bienvenue sur le site officiel de la Direction des Œuvres Universitaires de la wilaya de Sétif, une fenêtre que nous voulons être un pont de communication entre la Direction et nos chers étudiants.\n\n"
            "La Direction veille à offrir les meilleures conditions d'hébergement, de restauration, de transport et de bourses à des milliers d'étudiants. Elle s'efforce sans cesse d'améliorer ses services et de les rapprocher de l'étudiant grâce à la numérisation, notamment avec l'espace étudiant qui vous permet de déposer et de suivre vos demandes depuis chez vous.\n\n"
            "Nos portes restent ouvertes à toute suggestion ou remarque. Nous vous souhaitons une excellente année universitaire.",
            "In the name of God, the Most Gracious, the Most Merciful\n\n"
            "I am pleased to welcome you to the official website of the Directorate of University Services of the Wilaya of Sétif, a window we want to be a bridge of communication between the Directorate and our dear students.\n\n"
            "The Directorate strives to provide the best accommodation, catering, transport and scholarship conditions to thousands of students. It constantly works to improve its services and bring them closer to students through digitalization, including the student area that allows you to submit and track your applications from home.\n\n"
            "Our doors are open to any suggestion or remark. We wish you a successful academic year."),
    }),
    # ------------------------------------------------------------ الهيكل التنظيمي
    ('organization_structures', 'id = 1', {
        'title': ("Organigramme", "Organizational structure"),
        'description': ("La Direction se compose d'un directeur dont relèvent quatre services principaux, ainsi que de bureaux de coordination avec les résidences universitaires.",
                        "The Directorate consists of a director supervising four main departments, along with offices coordinating with the university residences."),
        'content': ("Les quatre services (bourses, hébergement, restauration, transport) travaillent sous l'autorité directe du directeur et se coordonnent avec les directeurs des résidences universitaires pour assurer le bon déroulement des services quotidiens aux étudiants.",
                    "The four departments (scholarships, accommodation, catering, transport) work under the direct supervision of the director and coordinate with the university residence directors to ensure the smooth running of daily services for students."),
    }),
    # ------------------------------------------------------------ تاريخ المديرية
    ('directorate_histories', 'id = 1', {
        'title': ("Historique de la Direction", "History of the Directorate"),
        'introduction': ("La Direction des Œuvres Universitaires de la wilaya de Sétif a été créée pour accompagner la forte expansion de l'université ; elle prend en charge depuis les services sociaux des étudiants.",
                         "The Directorate of University Services of the Wilaya of Sétif was created to keep pace with the university's major expansion, and has since been in charge of students' social services."),
        'content': ("La Direction a débuté avec une seule résidence universitaire et un seul restaurant, puis s'est progressivement développée avec la création du pôle universitaire d'El Bez. Elle compte aujourd'hui plusieurs résidences, restaurants et une flotte de transport au service de milliers d'étudiants chaque jour.\n\n"
                    "Ces dernières années, la Direction est entrée dans l'ère du numérique : l'étudiant peut désormais déposer et suivre ses demandes en ligne.",
                    "The Directorate started with a single university residence and a single restaurant, then gradually expanded with the creation of the El Bez university hub. Today it runs several residences, restaurants and a transport fleet serving thousands of students every day.\n\n"
                    "In recent years, the Directorate has embraced digitalization: students can now submit and track their applications online."),
    }),
    # ------------------------------------------------------------ الأخبار
    ('news', "slug = 'sarf-al-minha'", {
        'title': ("Versement de la première tranche de la bourse universitaire", "Payment of the first installment of the university scholarship"),
        'summary': ("Le versement de la première tranche de la bourse universitaire a commencé au profit des étudiants bénéficiaires, sur leurs comptes postaux.",
                    "Payment of the first installment of the university scholarship has started for eligible students, via their postal accounts."),
        'content': ("La Direction des Œuvres Universitaires de la wilaya de Sétif informe l'ensemble des étudiants boursiers du lancement du versement de la première tranche de la bourse universitaire. Le montant peut être retiré du compte courant postal à partir de dimanche.\n\n"
                    "Pour toute information, veuillez vous rapprocher du service des bourses.",
                    "The Directorate of University Services of the Wilaya of Sétif informs all scholarship students that payment of the first installment of the university scholarship has started. The amount can be withdrawn from the postal current account starting Sunday.\n\n"
                    "For any inquiries, please contact the scholarship department."),
    }),
    ('news', "slug = 'maktaba-jadida'", {
        'title': ("Inauguration d'une nouvelle bibliothèque à la résidence universitaire Debaghine", "A new library opens at the Debaghine university residence"),
        'summary': ("Une nouvelle bibliothèque équipée d'une salle de lecture de cent vingt places a été inaugurée.",
                    "A new library with a reading room for one hundred and twenty students has been inaugurated."),
        'content': ("Dans le cadre de l'amélioration des conditions de révision, une nouvelle bibliothèque a été inaugurée à la résidence universitaire Debaghine. Elle dispose d'une salle de lecture de cent vingt places et de plus de trois mille titres.",
                    "As part of efforts to improve study conditions, a new library has been inaugurated at the Debaghine university residence. It features a reading room for one hundred and twenty students and more than three thousand titles."),
    }),
    ('news', "slug = 'naql-jamii'", {
        'title': ("Renforcement des lignes de transport vers le pôle universitaire d'El Bez", "More university transport lines to the El Bez university hub"),
        'summary': ("De nouveaux bus ont été ajoutés pour réduire l'attente aux heures de pointe.",
                    "New buses have been added to reduce waiting times at peak hours."),
        'content': ("Afin de renforcer le service de transport universitaire, de nouveaux bus ont été ajoutés sur les lignes desservant le pôle universitaire d'El Bez pour réduire le temps d'attente aux heures de pointe.",
                    "To strengthen the university transport service, new buses have been added to the lines serving the El Bez university hub in order to reduce waiting times at peak hours."),
    }),
    ('news', "slug = 'qaimat-al-wajabat'", {
        'title': ("Menu hebdomadaire des restaurants universitaires", "Weekly menu in the university restaurants"),
        'summary': ("Découvrez le menu proposé cette semaine dans tous les restaurants universitaires.",
                    "Discover this week's menu in all university restaurants."),
        'content': ("Le service de la restauration publie le menu hebdomadaire des restaurants universitaires, en veillant à l'équilibre nutritionnel et à la variété des plats.",
                    "The catering department publishes the weekly menu of the university restaurants, with attention to nutritional balance and a variety of dishes."),
    }),
    ('news', "slug = 'tasjilat-al-iqamat'", {
        'title': ("Ouverture des inscriptions aux résidences universitaires pour l'année 2026/2027", "Registration for university residences opens for 2026/2027"),
        'summary': ("Les nouveaux et anciens étudiants peuvent déposer leurs demandes d'hébergement via l'espace étudiant.",
                    "New and returning students can submit their accommodation applications through the student area."),
        'content': ("La Direction annonce l'ouverture des inscriptions aux résidences universitaires pour l'année 2026/2027. L'étudiant dépose sa demande via l'espace étudiant, accompagnée des pièces requises.",
                    "The Directorate announces that registration for university residences for the 2026/2027 academic year is now open. Students submit their application through the student area, along with the required documents."),
    }),
    ('news_images', "image = 'news/gallery/bibliotheque-1.jpg'", {'caption': ("La salle de lecture", "The reading room")}),
    ('news_images', "image = 'news/gallery/bibliotheque-2.jpg'", {'caption': ("Les rayonnages", "The bookshelves")}),
    ('news_images', "image = 'news/gallery/transport-1.jpg'", {'caption': ("Les nouveaux bus", "The new buses")}),
    # ------------------------------------------------------------ النشاطات
    ('activities', "slug = 'dawrat-korat-al-qadam'", {
        'title': ("Tournoi de football inter-résidences universitaires", "Inter-residence university football tournament"),
        'short_description': ("Un tournoi sportif réunissant les équipes des résidences universitaires de la wilaya de Sétif dans une ambiance fraternelle, avec plus de vingt équipes.",
                              "A sports tournament bringing together teams from the university residences of the Wilaya of Sétif in a friendly atmosphere, with more than twenty teams."),
        'description': ("Un tournoi sportif réunissant les équipes des résidences universitaires de la wilaya de Sétif dans une ambiance fraternelle, avec plus de vingt équipes.\n\n"
                        "La Direction organise cette activité dans le cadre de son programme culturel et sportif annuel au profit des étudiants résidents.",
                        "A sports tournament bringing together teams from the university residences of the Wilaya of Sétif in a friendly atmosphere, with more than twenty teams.\n\n"
                        "The Directorate organizes this activity as part of its annual cultural and sports program for resident students."),
        'location': ("Stade universitaire d'El Bez", "El Bez university stadium"),
        'organizer': ("Direction des Œuvres Universitaires", "Directorate of University Services"),
    }),
    ('activities', "slug = 'umsiya-shiriya'", {
        'title': ("Soirée poétique à l'occasion de la Journée de l'étudiant", "Poetry evening for Student Day"),
        'short_description': ("Une soirée littéraire où les étudiants déclament leurs poèmes et concourent pour le prix du meilleur poème.",
                              "A literary evening where students recite their poems and compete for the best poem award."),
        'description': ("Une soirée littéraire où les étudiants déclament leurs poèmes et concourent pour le prix du meilleur poème.\n\n"
                        "La Direction organise cette activité dans le cadre de son programme culturel et sportif annuel au profit des étudiants résidents.",
                        "A literary evening where students recite their poems and compete for the best poem award.\n\n"
                        "The Directorate organizes this activity as part of its annual cultural and sports program for resident students."),
        'location': ("Salle de conférences, résidence Debaghine", "Lecture hall, Debaghine residence"),
        'organizer': ("Direction des Œuvres Universitaires", "Directorate of University Services"),
    }),
    ('activities', "slug = 'maarad-al-kitab'", {
        'title': ("Salon du livre universitaire", "University book fair"),
        'short_description': ("Un salon proposant des milliers de titres dans toutes les spécialités, à prix réduits pour les étudiants.",
                              "A fair offering thousands of titles in all fields at reduced prices for students."),
        'description': ("Un salon proposant des milliers de titres dans toutes les spécialités, à prix réduits pour les étudiants.\n\n"
                        "La Direction organise cette activité dans le cadre de son programme culturel et sportif annuel au profit des étudiants résidents.",
                        "A fair offering thousands of titles in all fields at reduced prices for students.\n\n"
                        "The Directorate organizes this activity as part of its annual cultural and sports program for resident students."),
        'location': ("Bibliothèque centrale", "Central library"),
        'organizer': ("Direction des Œuvres Universitaires", "Directorate of University Services"),
    }),
    ('activity_images', "image = 'activities/gallery/football-1.jpg'", {'caption': ("La finale", "The final match")}),
    # ------------------------------------------------------------ الأقسام
    ('departements', "slug = 'maslahat-al-minah'", {
        'name': ("Service des bourses", "Scholarship department"),
        'description': ("Il étudie les dossiers de bourse universitaire et en assure le versement aux étudiants bénéficiaires, ainsi que le suivi des recours et des transferts entre wilayas.",
                        "It processes university scholarship files and pays scholarships to eligible students, and handles appeals and transfers between provinces."),
        'head_of_department': ("M. le chef de service", "Head of department"),
    }),
    ('departements', "slug = 'maslahat-al-iwaa'", {
        'name': ("Service de l'hébergement", "Accommodation department"),
        'description': ("Il organise l'hébergement dans les résidences universitaires : inscription, répartition des chambres et suivi des conditions de résidence.",
                        "It organizes accommodation in the university residences: registration, room allocation and monitoring of living conditions."),
        'head_of_department': ("M. le chef de service", "Head of department"),
    }),
    ('departements', "slug = 'maslahat-al-itaam'", {
        'name': ("Service de la restauration", "Catering department"),
        'description': ("Il supervise les restaurants universitaires et veille à la qualité et à l'équilibre nutritionnel des repas servis.",
                        "It supervises the university restaurants and ensures the quality and nutritional balance of the meals served."),
        'head_of_department': ("M. le chef de service", "Head of department"),
    }),
    ('departements', "slug = 'maslahat-al-naql'", {
        'name': ("Service du transport", "Transport department"),
        'description': ("Il gère les lignes de transport universitaire qui relient les quartiers et les résidences aux pôles universitaires.",
                        "It manages the university transport lines connecting neighborhoods and residences to the university hubs."),
        'head_of_department': ("M. le chef de service", "Head of department"),
    }),
]

# الإقامات: نفس الوصف بأعداد مختلفة
CITE_DESC = (
    "Résidence universitaire d'une capacité de {n} lits, offrant hébergement, restauration et transport aux étudiants.\n\n"
    "La résidence dispose de salles de lecture et d'internet, de terrains de sport et d'un restaurant universitaire.",
    "A university residence with a capacity of {n} beds, providing accommodation, catering and transport for students.\n\n"
    "The residence has reading and internet rooms, sports fields and a university restaurant.",
)
for slug, names, n, addr in [
    ('debaghine', ("Debaghine", "Debaghine"), 2000, ("Pôle universitaire d'El Bez, Sétif", "El Bez university hub, Sétif")),
    ('hachemi-hocine', ("Hachemi Hocine", "Hachemi Hocine"), 1500, ("Cité des Hauts-Plateaux, Sétif", "Hauts-Plateaux district, Sétif")),
    ('boukhrissa', ("Boukhrissa", "Boukhrissa"), 1000, ("Route de Béjaïa, Sétif", "Béjaïa road, Sétif")),
    ('rouabah', ("Rouabah", "Rouabah"), 1000, ("Cité 1014 Logements, Sétif", "1014 Housing district, Sétif")),
    ('fadila', ("Fadila", "Fadila"), 1200, ("Cité El Maabouda, Sétif", "El Maabouda district, Sétif")),
    ('19-mai-1956', ("19 Mai 1956", "19 May 1956"), 2500, ("Pôle universitaire d'El Bez, Sétif", "El Bez university hub, Sétif")),
    ('24-avril', ("24 Avril", "24 April"), 800, ("Cité Kaaboub, Sétif", "Kaaboub district, Sétif")),
]:
    ROWS.append(('cites', f"slug = '{slug}'", {
        'name': names,
        'description': (CITE_DESC[0].format(n=f'{n:,}'.replace(',', ' ')), CITE_DESC[1].format(n=f'{n:,}')),
        'address': addr,
    }))
ROWS += [
    ('cite_images', "image = 'cites/gallery/debaghine-1.jpg'", {'caption': ("L'entrée principale", "Main entrance")}),
    ('cite_images', "image = 'cites/gallery/debaghine-2.jpg'", {'caption': ("Le restaurant", "The restaurant")}),
]

# الخدمات: نفس المتطلبات وطريقة التقديم
REQ = ("Carte d'étudiant\nCertificat de scolarité\nPhoto d'identité", "Student card\nCertificate of enrollment\nPassport photo")
HOW = ("La demande est déposée via l'espace étudiant du site ; les dossiers sont ensuite étudiés et les résultats annoncés.",
       "Applications are submitted through the student area of the website; files are then reviewed and the results announced.")
CONTACT = ("Téléphone : 036 00 00 10", "Phone: 036 00 00 10")
SUPERVISED = ("Ce service est assuré par le service compétent de la Direction.", "This service is managed by the relevant department of the Directorate.")
for slug, title, short in [
    ('naql-jamii', ("Transport universitaire", "University transport"),
     ("Transport quotidien gratuit des étudiants entre les résidences et les pôles universitaires grâce à un réseau de lignes régulières.",
      "Free daily transport for students between residences and university hubs through a network of regular lines.")),
    ('iwaa-jamii', ("Hébergement universitaire", "University accommodation"),
     ("Un logement universitaire confortable et sûr, dans des résidences dotées de toutes les commodités nécessaires, pour les étudiants éligibles.",
      "Comfortable and safe university housing in residences equipped with all the necessary facilities for eligible students.")),
    ('itaam-jamii', ("Restauration universitaire", "University catering"),
     ("Trois repas par jour, équilibrés et variés, dans des restaurants universitaires modernes à des prix symboliques.",
      "Three balanced and varied meals a day in modern university restaurants at symbolic prices.")),
    ('minha-jamiiya', ("Bourse universitaire", "University scholarship"),
     ("Une bourse d'études versée en trois tranches aux étudiants éligibles selon le revenu familial.",
      "A study grant paid in three installments to eligible students according to family income.")),
]:
    ROWS.append(('services', f"slug = '{slug}'", {
        'title': title,
        'short_description': short,
        'detailed_description': (short[0] + '\n\n' + SUPERVISED[0], short[1] + '\n\n' + SUPERVISED[1]),
        'requirements': REQ,
        'how_to_apply': HOW,
        'contact_info': CONTACT,
    }))

ROWS += [
    # ------------------------------------------------------------ مركز التحميلات
    ('download_files', "slug = 'istimarat-al-minha'", {
        'title': ("Formulaire de demande de bourse universitaire", "University scholarship application form"),
        'description': ("Le formulaire officiel de demande de bourse, à remplir et à accompagner des pièces requises.", "The official scholarship application form, to be filled in and submitted with the required documents."),
    }),
    ('download_files', "slug = 'istimarat-al-iwaa'", {
        'title': ("Formulaire de demande d'hébergement", "Accommodation application form"),
        'description': ("Formulaire de demande de chambre dans les résidences universitaires pour la nouvelle année.", "Application form for a room in the university residences for the new academic year."),
    }),
    ('download_files', "slug = 'dalil-at-talib'", {
        'title': ("Guide de l'étudiant résident", "Resident student guide"),
        'description': ("Tout ce que l'étudiant doit savoir sur la vie en résidence : droits, devoirs et services.", "Everything students need to know about life in residence: rights, duties and services."),
    }),
    # ------------------------------------------------------------ الأسئلة الشائعة
    ('faq_categories', '`order` = 1', {'name': ("Général", "General")}),
    ('faq_categories', '`order` = 2', {'name': ("Bourses", "Scholarships")}),
    ('faq_categories', '`order` = 3', {'name': ("Hébergement", "Accommodation")}),
    ('faqs', '`order` = 1', {
        'question': ("Qu'est-ce que la Direction des Œuvres Universitaires ?", "What is the Directorate of University Services?"),
        'answer': ("Un établissement public chargé des services sociaux des étudiants universitaires : hébergement, restauration, transport, bourses et activités culturelles et sportives.",
                   "A public institution in charge of university students' social services: accommodation, catering, transport, scholarships and cultural and sports activities."),
    }),
    ('faqs', '`order` = 2', {
        'question': ("Comment contacter la Direction ?", "How can I contact the Directorate?"),
        'answer': ("Via la page « Contactez-nous » du site, ou en vous présentant au siège de la Direction pendant les heures de travail.",
                   "Through the \"Contact us\" page of the website, or by visiting the Directorate's headquarters during working hours."),
    }),
    ('faqs', '`order` = 3', {
        'question': ("Comment obtenir la bourse universitaire ?", "How do I get the university scholarship?"),
        'answer': ("En déposant une demande de bourse via l'espace étudiant, accompagnée d'une attestation de revenu familial dans les délais fixés, puis en suivant l'état de la demande.",
                   "By submitting a scholarship application through the student area, together with a family income certificate within the set deadlines, and then tracking the status of the application."),
    }),
    ('faqs', '`order` = 4', {
        'question': ("Quand la bourse est-elle versée ?", "When is the scholarship paid?"),
        'answer': ("La bourse est versée en trois tranches au cours de l'année universitaire ; chaque tranche est annoncée dans la rubrique Actualités.",
                   "The scholarship is paid in three installments during the academic year; each installment is announced in the News section."),
    }),
    ('faqs', '`order` = 5', {
        'question': ("Quelles sont les conditions pour bénéficier d'un logement universitaire ?", "What are the conditions for university housing?"),
        'answer': ("Être inscrit à l'université et résider à une certaine distance de son établissement, la priorité étant accordée selon l'éloignement et la situation sociale.",
                   "Students must be enrolled at the university and live a certain distance from their institution, with priority given according to distance and social situation."),
    }),
    ('faqs', '`order` = 6', {
        'question': ("Puis-je changer de résidence ?", "Can I change residence?"),
        'answer': ("Oui, en déposant une demande de transfert auprès du service de l'hébergement ; elle est étudiée selon les places disponibles.",
                   "Yes, by submitting a transfer request to the accommodation department; it is reviewed according to the places available."),
    }),
    # ------------------------------------------------------------ أنواع الطلبات (demo-seed.php)
    ('application_types', '`order` = 1', {
        'name': ("Demande de bourse universitaire", "University scholarship application"),
        'description': ("Demande d'obtention de la bourse d'études", "Application for a study grant"),
        'required_documents': ("Attestation de revenu", "Income certificate"),
    }),
    ('application_types', '`order` = 2', {
        'name': ("Demande de logement universitaire", "University housing application"),
        'description': ("Demande de bénéficier d'un logement universitaire", "Application for university housing"),
        'required_documents': ("Certificat de résidence", "Certificate of residence"),
    }),
    # ------------------------------------------------------------ إشعارات الطالب أحمد (demo-seed.php)
    ('student_notifications', "title = 'تم استلام طلبك'", {
        'title': ("Votre demande a été reçue", "Your application has been received"),
        'message': ("Votre demande n° 202600007 (Demande de bourse universitaire) a été reçue ; elle est en attente.", "Your application no. 202600007 (University scholarship application) has been received and is pending."),
    }),
    ('student_notifications', "title = 'تم اعتماد طلبك ✅'", {
        'title': ("Votre demande a été approuvée ✅", "Your application has been approved ✅"),
        'message': ("Votre demande n° 202600011 (Demande de logement universitaire) a été approuvée.", "Your application no. 202600011 (University housing application) has been approved."),
    }),
    ('student_notifications', "title = 'مرحباً بك في فضاء الطالب'", {
        'title': ("Bienvenue dans l'espace étudiant", "Welcome to the student area"),
        'message': ("Vous pouvez désormais déposer et suivre vos demandes depuis votre compte.", "You can now submit and track your applications from your account."),
    }),
]


def sql(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "''").replace('\n', '\\n') + "'"


out = ['-- محتوى تجريبي مترجم (يُطبَّق بعد demo-seed.php في dev/demo-site.sh) — مولَّد من dev/demo_i18n.py', 'SET NAMES utf8mb4;', '']
for table, where, fields in ROWS:
    sets = []
    for f, (fr, en) in fields.items():
        sets.append(f'`{f}_fr` = {sql(fr)}')
        sets.append(f'`{f}_en` = {sql(en)}')
    out.append(f'UPDATE `{table}` SET ' + ', '.join(sets) + f' WHERE {where};')
path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'demo-i18n.sql')
open(path, 'w', encoding='utf-8').write('\n'.join(out) + '\n')
print(f'{len(ROWS)} سجلاً → {path}')
