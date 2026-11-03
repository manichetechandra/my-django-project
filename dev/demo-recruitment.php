<?php
/**
 * مسابقات توظيف تجريبية + طلبات ترشح (معطيات مشفّرة ووثائق PDF مشفّرة) للموقع التجريبي للنسخة 2.0.
 *   DB_NAME=dou_setif2 php dev/demo-recruitment.php /tmp/site2      (تستدعيه dev/demo-site.sh)
 * يستعمل كود الموقع نفسه (Crypto، recruit_store_application) فيُنشأ مفتاح التشفير في storage/private/ للموقع.
 * التواريخ نسبية لليوم: مسابقتان مفتوحتان (إحداهما تنتهي بعد 3 أيام)، واحدة قريبة، واحدة منتهية بنتائجها، وواحدة بالإيداع الورقي.
 * المرحلة 2: مواعيد اختبار + مترشح مستدعى + مترشحة ملفها ناقص (بأجل إكمال) + نتائج فردية منشورة للامتحان المهني.
 * ثلاثة مترشحين برموز ثابتة لتجربة «متابعة طلب» (تُطبع في آخر التنفيذ) — في قاعدة التجربة فقط.
 */
$site = rtrim($argv[1] ?? '/tmp/site2', '/');
chdir($site);
require $site . '/app/bootstrap.php';
set_lang('ar');

$day = fn(int $offset) => date('Y-m-d', strtotime(($offset >= 0 ? '+' : '') . $offset . ' days'));
$at = fn(int $daysAgo, string $hm) => date('Y-m-d', strtotime("-$daysAgo days")) . " $hm:00";

/** ملف PDF صغير صالح (نص لاتيني) */
function demo_pdf(string $title, string $line2 = ''): string
{
    $esc = fn(string $s) => strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    $content = "BT /F1 20 Tf 60 760 Td (" . $esc($title) . ") Tj ET\nBT /F1 12 Tf 60 730 Td (" . $esc($line2) . ") Tj ET\n"
        . "BT /F1 10 Tf 60 60 Td (Direction des Oeuvres Universitaires de Setif - document de demonstration) Tj ET";
    $objs = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [];
    foreach ($objs as $i => $o) {
        $offsets[] = strlen($out);
        $out .= ($i + 1) . " 0 obj\n" . $o . "\nendobj\n";
    }
    $xref = strlen($out);
    $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $off) {
        $out .= sprintf("%010d 00000 n \n", $off);
    }
    return $out . "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
}

/** ملف منشور في uploads/competitions/ */
function demo_public_file(Competition $c, string $kind, array $title, string $pdfTitle, int $order): void
{
    $dir = UPLOAD_PATH . '/competitions/' . date('Y');
    @mkdir($dir, 0775, true);
    $name = $kind . '-' . $c['id'] . '_' . bin2hex(random_bytes(3)) . '.pdf';
    file_put_contents("$dir/$name", demo_pdf($pdfTitle, $c->tr('title', 'fr')));
    CompetitionFile::create(['competition_id' => (int) $c['id'], 'title' => $title[0], 'title_fr' => $title[1], 'title_en' => $title[2],
        'kind' => $kind, 'file' => 'competitions/' . date('Y') . '/' . $name, 'order' => $order]);
}

$common = [
    'conditions' => "أن يكون المترشح جزائري الجنسية\nأن يكون متمتعاً بحقوقه المدنية\nألا يحمل سوابق قضائية تتنافى وممارسة الوظيفة\nأن يكون في وضعية قانونية تجاه الخدمة الوطنية (للذكور)\nأن يبلغ من العمر 18 سنة على الأقل",
    'conditions_fr' => "Être de nationalité algérienne\nJouir de ses droits civiques\nNe pas avoir d'antécédents judiciaires incompatibles avec la fonction\nÊtre en situation régulière vis-à-vis du service national (hommes)\nÊtre âgé(e) de 18 ans au moins",
    'conditions_en' => "Be an Algerian national\nEnjoy full civil rights\nHave no criminal record incompatible with the position\nHave a regular military service status (men)\nBe at least 18 years old",
];

$competitions = [
    [
        'title' => 'مسابقة على أساس الشهادة لتوظيف متصرفين إداريين وملحقين إداريين',
        'title_fr' => 'Concours sur titre pour le recrutement d\'administrateurs et d\'attachés d\'administration',
        'title_en' => 'Qualification-based competition to recruit administrators and administrative attachés',
        'reference' => '07/2026', 'recruitment_mode' => 'qualification', 'opens_at' => $day(-5), 'closes_at' => $day(12), 'exam_date' => $day(30),
        'summary' => 'تعلن مديرية الخدمات الجامعية سطيف عن فتح مسابقة على أساس الشهادة لتوظيف 7 مناصب في رتبتي متصرف إداري وملحق إداري.',
        'summary_fr' => 'La DOU de Sétif annonce l\'ouverture d\'un concours sur titre pour 7 postes d\'administrateur et d\'attaché d\'administration.',
        'summary_en' => 'The DOU of Sétif announces a qualification-based competition for 7 administrator and administrative attaché positions.',
        'description' => "تعلن مديرية الخدمات الجامعية سطيف عن فتح مسابقة على أساس الشهادة لتوظيف متصرفين إداريين وملحقين إداريين، طبقاً للمرسوم التنفيذي المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك المشتركة في المؤسسات والإدارات العمومية.\n\nيتم الانتقاء على أساس الشهادة مع مقابلة مع لجنة الانتقاء. تُنشر قائمة المترشحين المقبولين لإجراء المقابلة في هذه الصفحة.",
        'description_fr' => "La Direction des Œuvres Universitaires de Sétif annonce l'ouverture d'un concours sur titre pour le recrutement d'administrateurs et d'attachés d'administration, conformément au statut particulier des fonctionnaires appartenant aux corps communs.\n\nLa sélection se fait sur titre avec un entretien devant le jury. La liste des candidats admis à l'entretien sera publiée sur cette page.",
        'description_en' => "The Directorate of University Services of Sétif announces a qualification-based competition to recruit administrators and administrative attachés, in accordance with the special statute of civil servants in common corps.\n\nSelection is based on qualifications followed by an interview with the selection board. The list of candidates admitted to the interview will be published on this page.",
        'posts' => [
            ['متصرف إداري', 'Administrateur', 'Administrator', 3, 'ليسانس في الحقوق أو العلوم الاقتصادية أو علوم التسيير', 'Licence en droit, sciences économiques ou sciences de gestion', 'Bachelor\'s degree in law, economics or management', 'مقر المديرية', 'Siège de la direction', 'Directorate headquarters'],
            ['ملحق إداري', 'Attaché d\'administration', 'Administrative attaché', 4, 'شهادة تقني سامي في الإدارة أو ما يعادلها', 'Diplôme de technicien supérieur en administration ou équivalent', 'Higher technician diploma in administration or equivalent', 'الإقامات الجامعية بسطيف', 'Résidences universitaires de Sétif', 'University residences in Sétif'],
        ],
        'requirements' => [
            ['طلب خطي موقّع', 'Demande manuscrite signée', 'Signed handwritten application', 1],
            ['نسخة من بطاقة التعريف الوطنية', 'Copie de la carte nationale d\'identité', 'Copy of the national ID card', 1],
            ['نسخة من الشهادة أو ما يعادلها', 'Copie du diplôme ou équivalent', 'Copy of the diploma or equivalent', 1],
            ['كشف النقاط لكل سنوات الدراسة', 'Relevés de notes de toutes les années', 'Transcripts for all years of study', 1],
            ['شهادة الخبرة المهنية', 'Attestation d\'expérience professionnelle', 'Work experience certificate', 0],
        ],
        'files' => [['announcement', ['الإعلان الرسمي للمسابقة', 'Avis officiel du concours', 'Official competition notice'], 'Avis de concours N 07/2026'],
                    ['form', ['استمارة الترشح', 'Formulaire de candidature', 'Application form'], 'Formulaire de candidature']],
        'applications' => true,
    ],
    [
        'title' => 'مسابقة على أساس الاختبار لتوظيف أعوان أمن ووقاية',
        'title_fr' => 'Concours sur épreuves pour le recrutement d\'agents de sécurité et de prévention',
        'title_en' => 'Exam-based competition to recruit security and prevention officers',
        'reference' => '08/2026', 'recruitment_mode' => 'exam', 'opens_at' => $day(-18), 'closes_at' => $day(3), 'exam_date' => $day(20),
        'summary' => '10 مناصب لأعوان الأمن والوقاية موزعة على الإقامات الجامعية بولاية سطيف.',
        'summary_fr' => '10 postes d\'agents de sécurité et de prévention répartis sur les résidences universitaires de Sétif.',
        'summary_en' => '10 security and prevention officer positions across the university residences of Sétif.',
        'description' => "تنظم المديرية مسابقة على أساس الاختبار المهني لتوظيف أعوان الأمن والوقاية. يُستدعى المترشحون المقبولون لإجراء الاختبار عبر هذه الصفحة.",
        'description_fr' => "La direction organise un concours sur épreuve professionnelle pour le recrutement d'agents de sécurité et de prévention. Les candidats retenus seront convoqués via cette page.",
        'description_en' => "The directorate is holding a professional exam to recruit security and prevention officers. Shortlisted candidates will be invited via this page.",
        'posts' => [['عون أمن ووقاية', 'Agent de sécurité et de prévention', 'Security and prevention officer', 10, 'مستوى السنة الرابعة متوسط على الأقل', 'Niveau 4e année moyenne au moins', 'At least 4th year of middle school', 'الإقامات الجامعية', 'Résidences universitaires', 'University residences']],
        'requirements' => [
            ['نسخة من بطاقة التعريف الوطنية', 'Copie de la carte nationale d\'identité', 'Copy of the national ID card', 1],
            ['شهادة مدرسية تثبت المستوى الدراسي', 'Certificat de scolarité justifiant le niveau', 'School certificate proving the education level', 1],
            ['وثيقة تثبت الوضعية تجاه الخدمة الوطنية', 'Justificatif de la situation vis-à-vis du service national', 'Military service status document', 0],
        ],
        'files' => [['announcement', ['الإعلان الرسمي للمسابقة', 'Avis officiel du concours', 'Official competition notice'], 'Avis de concours N 08/2026']],
    ],
    [
        'title' => 'توظيف بالتعاقد: طباخ رئيسي ومساعدو طباخين للمطاعم الجامعية',
        'title_fr' => 'Recrutement contractuel : chef cuisinier et aides-cuisiniers pour les restaurants universitaires',
        'title_en' => 'Contract recruitment: head cook and assistant cooks for university restaurants',
        'reference' => '09/2026', 'recruitment_mode' => 'contract', 'opens_at' => $day(10), 'closes_at' => $day(25),
        'summary' => 'يفتح الترشح قريباً لتوظيف طباخ رئيسي و6 مساعدي طباخين بالتعاقد.',
        'summary_fr' => 'Les candidatures ouvriront bientôt pour un chef cuisinier et 6 aides-cuisiniers sous contrat.',
        'summary_en' => 'Applications will soon open for a head cook and 6 assistant cooks on contract.',
        'description' => 'تفاصيل المسابقة متاحة في الإعلان الرسمي. يفتح الترشح الإلكتروني في التاريخ المحدد.',
        'description_fr' => 'Les détails figurent dans l\'avis officiel. Les candidatures en ligne ouvriront à la date indiquée.',
        'description_en' => 'Details are in the official notice. Online applications open on the indicated date.',
        'posts' => [['طباخ رئيسي', 'Chef cuisinier', 'Head cook', 1, 'شهادة تكوين مهني في الطبخ', 'Diplôme de formation professionnelle en cuisine', 'Vocational diploma in cooking', 'المطعم المركزي', 'Restaurant central', 'Central restaurant'],
                    ['مساعد طباخ', 'Aide-cuisinier', 'Assistant cook', 6, 'شهادة تكوين مهني أو خبرة', 'Formation professionnelle ou expérience', 'Vocational training or experience', 'المطاعم الجامعية', 'Restaurants universitaires', 'University restaurants']],
        'requirements' => [['نسخة من بطاقة التعريف الوطنية', 'Copie de la carte nationale d\'identité', 'Copy of the national ID card', 1], ['شهادة التكوين أو الخبرة', 'Attestation de formation ou d\'expérience', 'Training or experience certificate', 1]],
        'files' => [],
    ],
    [
        'title' => 'امتحان مهني للترقية إلى رتبة متصرف رئيسي',
        'title_fr' => 'Examen professionnel pour la promotion au grade d\'administrateur principal',
        'title_en' => 'Professional exam for promotion to principal administrator',
        'reference' => '03/2026', 'recruitment_mode' => 'professional', 'opens_at' => $day(-60), 'closes_at' => $day(-40), 'exam_date' => $day(-25),
        'summary' => 'نُشرت النتائج النهائية للامتحان المهني.',
        'summary_fr' => 'Les résultats définitifs de l\'examen professionnel sont publiés.',
        'summary_en' => 'The final results of the professional exam have been published.',
        'description' => 'امتحان مهني مفتوح للمتصرفين المرسمين الذين يثبتون 5 سنوات من الخدمة الفعلية بهذه الصفة.',
        'description_fr' => 'Examen professionnel ouvert aux administrateurs titulaires justifiant de 5 ans de service effectif en cette qualité.',
        'description_en' => 'Professional exam open to tenured administrators with 5 years of effective service in that capacity.',
        'posts' => [['متصرف رئيسي', 'Administrateur principal', 'Principal administrator', 2, 'متصرف مرسّم بخبرة 5 سنوات', 'Administrateur titulaire avec 5 ans d\'expérience', 'Tenured administrator with 5 years of experience', 'مقر المديرية', 'Siège de la direction', 'Directorate headquarters']],
        'requirements' => [['قرار الترسيم', 'Arrêté de titularisation', 'Tenure decision', 1]],
        'files' => [['announcement', ['الإعلان الرسمي', 'Avis officiel', 'Official notice'], 'Avis N 03/2026'],
                    ['admitted', ['قائمة المترشحين المقبولين لإجراء الامتحان', 'Liste des candidats admis à concourir', 'List of candidates admitted to the exam'], 'Liste des candidats admis'],
                    ['results', ['النتائج النهائية', 'Résultats définitifs', 'Final results'], 'Resultats definitifs']],
    ],
    [
        'title' => 'مسابقة لتوظيف تقنيين في الإعلام الآلي (إيداع ورقي)',
        'title_fr' => 'Concours de recrutement de techniciens en informatique (dépôt papier)',
        'title_en' => 'Competition to recruit IT technicians (paper application)',
        'reference' => '10/2026', 'recruitment_mode' => 'qualification', 'opens_at' => $day(-2), 'closes_at' => $day(20), 'online_apply' => 0,
        'summary' => 'الترشح لهذه المسابقة بإيداع الملف الورقي فقط لدى مصلحة المستخدمين.',
        'summary_fr' => 'Candidature par dépôt du dossier papier uniquement auprès du service du personnel.',
        'summary_en' => 'Applications by paper file only, at the personnel department.',
        'description' => 'مسابقة على أساس الشهادة لتوظيف تقنيين في الإعلام الآلي.',
        'description_fr' => 'Concours sur titre pour le recrutement de techniciens en informatique.',
        'description_en' => 'Qualification-based competition to recruit IT technicians.',
        'deposit_info' => "تودع الملفات لدى مصلحة المستخدمين بمقر المديرية (حي 08 ماي 1945، سطيف) من الأحد إلى الخميس، من 08:30 إلى 15:30.",
        'deposit_info_fr' => "Les dossiers sont à déposer au service du personnel, siège de la direction (cité 08 Mai 1945, Sétif), du dimanche au jeudi de 08h30 à 15h30.",
        'deposit_info_en' => "Files must be submitted to the personnel department at the directorate headquarters (08 May 1945 district, Sétif), Sunday to Thursday, 08:30 to 15:30.",
        'posts' => [['تقني في الإعلام الآلي', 'Technicien en informatique', 'IT technician', 2, 'شهادة تقني في الإعلام الآلي', 'Diplôme de technicien en informatique', 'IT technician diploma', 'مقر المديرية', 'Siège de la direction', 'Directorate headquarters']],
        'requirements' => [['طلب خطي', 'Demande manuscrite', 'Handwritten application', 1], ['نسخة من الشهادة', 'Copie du diplôme', 'Copy of the diploma', 1]],
        'files' => [['announcement', ['الإعلان الرسمي للمسابقة', 'Avis officiel du concours', 'Official competition notice'], 'Avis de concours N 10/2026']],
    ],
];

$first = null;
foreach ($competitions as $i => $d) {
    $c = Competition::create(array_merge($common, array_diff_key($d, array_flip(['posts', 'requirements', 'files', 'applications'])), [
        'created_at' => $at(max(1, -((int) ((strtotime($d['opens_at']) - time()) / 86400)) + 2), '09:00'),
        'views_count' => [412, 1289, 96, 2034, 187][$i],
    ]));
    foreach ($d['posts'] as $k => [$t, $tfr, $ten, $n, $req, $reqfr, $reqen, $loc, $locfr, $locen]) {
        CompetitionPost::create(['competition_id' => (int) $c['id'], 'title' => $t, 'title_fr' => $tfr, 'title_en' => $ten, 'positions' => $n,
            'requirements' => $req, 'requirements_fr' => $reqfr, 'requirements_en' => $reqen, 'location' => $loc, 'location_fr' => $locfr, 'location_en' => $locen, 'order' => $k + 1]);
    }
    foreach ($d['requirements'] as $k => [$l, $lfr, $len, $required]) {
        CompetitionRequirement::create(['competition_id' => (int) $c['id'], 'label' => $l, 'label_fr' => $lfr, 'label_en' => $len, 'is_required' => $required, 'order' => $k + 1]);
    }
    foreach ($d['files'] as $k => [$kind, $title, $pdfTitle]) {
        demo_public_file($c, $kind, $title, $pdfTitle, $k + 1);
    }
    if (!empty($d['applications'])) {
        $first = $c;
    }
}

// ------------------------------------------------------------------ طلبات ترشح للمسابقة الأولى (بنفس كود الموقع: تشفير + وثائق)
$people = [
    ['بن علي', 'سارة', 'BENALI', 'Sara', 'F', '1996-03-14', 'سطيف', '109961403000118457', '0661223344', 'sara.benali@example.dz', 'حي 1006 مسكن، عمارة 12', 'سطيف', 'na', 'ليسانس في العلوم القانونية', 'قانون إداري', 2018, 3, 0, 'under_review', ''],
    ['مزياني', 'كريم', 'MEZIANI', 'Karim', 'M', '1993-11-02', 'العلمة', '109931102000215890', '0770112233', 'k.meziani@example.dz', 'حي الهضاب، شارع 5', 'سطيف', 'done', 'ماستر في علوم التسيير', 'تسيير الموارد البشرية', 2017, 5, 0, 'accepted', 'تهانينا، ملفك مقبول. اطبع استدعاءك من هذه الصفحة واحضر به يوم المقابلة.'],
    ['حمدي', 'أمينة', 'HAMDI', 'Amina', 'F', '1998-07-21', 'بجاية', '109980721000312345', '0550998877', '', 'حي إحدادن', 'بجاية', 'na', 'ليسانس في العلوم الاقتصادية', 'اقتصاد نقدي وبنكي', 2020, 1, 0, 'incomplete', 'ملفك ناقص: كشف نقاط السنة الثالثة غير واضح. ارفع نسخة واضحة (مسح ضوئي) من هذه الصفحة قبل الأجل.'],
    ['بوزيد', 'ياسين', 'BOUZID', 'Yacine', 'M', '1995-01-30', 'عين ولمان', '109950130000498765', '0698765432', 'yacine.bz@example.dz', 'وسط المدينة', 'سطيف', 'exempt', 'تقني سامي في الإدارة', 'إدارة الأعمال', 2016, 6, 1, 'pending', ''],
    ['قاسمي', 'نور الهدى', 'KACEMI', 'Nour El Houda', 'F', '1999-09-09', 'برج بوعريريج', '109990909000587654', '0662345678', 'nour.kacemi@example.dz', 'حي 500 مسكن', 'برج بوعريريج', 'na', 'تقني سامي في المحاسبة', 'محاسبة وجباية', 2021, 2, 1, 'pending', ''],
    ['رحماني', 'عبد الرؤوف', 'RAHMANI', 'Abderraouf', 'M', '1990-05-17', 'سطيف', '109900517000676543', '0771456789', '', 'حي يحياوي', 'سطيف', 'done', 'ليسانس في الحقوق', 'قانون خاص', 2014, 8, 0, 'rejected', 'نأسف، تخصصك لا يطابق الشروط المطلوبة لهذه الرتبة.'],
    ['شريف', 'إيمان', 'CHERIF', 'Imane', 'F', '1997-12-03', 'المسيلة', '109971203000765432', '0553214567', 'imane.cherif@example.dz', 'حي 300 مسكن', 'المسيلة', 'na', 'تقني سامي في الإدارة', 'سكرتارية', 2019, 4, 1, 'pending', ''],
];
$tmp = sys_get_temp_dir() . '/dou-demo-pdf';
@mkdir($tmp, 0700, true);
$posts = $first['posts'];
$reqs = $first['requirements'];
$created = [];
foreach ($people as $n => [$ln, $fn, $lnl, $fnl, $g, $bd, $bp, $nin, $ph, $em, $addr, $wil, $ns, $dip, $spec, $year, $exp, $postIdx, $status, $msg]) {
    $d = ['post_id' => (int) $posts[$postIdx]['id'], 'last_name' => $ln, 'first_name' => $fn, 'last_name_latin' => $lnl, 'first_name_latin' => $fnl,
          'gender' => $g, 'birth_date' => $bd, 'birth_place' => $bp, 'nin' => $nin, 'phone' => $ph, 'email' => $em, 'address' => $addr, 'wilaya' => $wil,
          'national_service' => $ns, 'diploma' => $dip, 'specialty' => $spec, 'graduation_year' => $year, 'experience_years' => $exp];
    foreach ($reqs as $k => $r) {
        if (!$r['is_required'] && $n % 2) {
            continue; // الوثيقة الاختيارية عند بعض المترشحين فقط
        }
        $path = "$tmp/doc-$n-$k.pdf";
        file_put_contents($path, demo_pdf('Document: ' . $r->tr('label', 'fr'), "$lnl $fnl - $nin"));
        $d['doc_' . $r['id']] = ['name' => ['Demande signée', 'CNI recto-verso', 'Diplôme', 'Relevés de notes', 'Attestation de travail'][$k] . ' - ' . $fnl . '.pdf', 'tmp_name' => $path, 'size' => filesize($path), 'error' => 0];
    }
    [$app] = recruit_store_application($first, $d, $posts, $reqs);
    $when = $at(5 - min(4, intdiv($n, 2)), sprintf('%02d:%02d', 9 + $n, 7 * $n % 60));
    $upd = ['submitted_at' => $when, 'updated_at' => $when, 'status' => $status, 'candidate_message' => $msg !== '' ? $msg : null];
    if ($status !== 'pending') {
        $upd += ['reviewed_at' => $at(1, '10:' . (10 + $n)), 'reviewed_by_id' => (int) DB::value("SELECT id FROM users WHERE username = 'miloudsellam'")];
    }
    $app->update($upd);
    DB::query('UPDATE recruitment_audit SET created_at = ?, ip_address = ? WHERE application_id = ?', [$when, '197.200.' . (10 + $n) . '.' . (20 + $n), (int) $app['id']]);
    $created[] = $app;
}
// أمثلة في سجل الاطلاع: المدير درس بعض الملفات
$admin = DB::one("SELECT id, username FROM users WHERE username = 'miloudsellam'");
foreach (array_slice($created, 0, 3) as $k => $app) {
    foreach ([['view', ''], ['file', 'عرض: نسخة من الشهادة أو ما يعادلها'], ['status', 'قيد الانتظار ← ' . $app->get_status_display()]] as $j => [$action, $details]) {
        DB::insert('recruitment_audit', ['user_id' => (int) $admin['id'], 'username' => $admin['username'], 'action' => $action, 'competition_id' => (int) $first['id'],
            'application_id' => (int) $app['id'], 'details' => $details, 'ip_address' => '41.111.20.5', 'created_at' => $at(1, sprintf('10:%02d', 10 + 3 * $k + $j))]);
    }
}
// ------------------------------------------------------------------ المرحلة 2: الاستدعاءات، إكمال الملف، النتائج الفردية
$adminId = (int) $admin['id'];

/** رموز ثابتة لمترشح تجريبي (يُعاد تشفير معطياته لأنها مربوطة برقم المتابعة) */
function demo_fixed_codes(CompetitionApplication $app, string $tracking, string $secret): void
{
    $p = $app->personal();
    $app->update(['tracking_code' => $tracking, 'secret_hash' => CompetitionApplication::hashSecret($secret),
        'personal_data' => Crypto::encryptArray($p, 'application:' . $tracking)]);
}

$examFields = fn(Competition $c, array $t, string $when, array $place, array $instr, int $order) => CompetitionExam::create([
    'competition_id' => (int) $c['id'], 'title' => $t[0], 'title_fr' => $t[1], 'title_en' => $t[2], 'starts_at' => $when,
    'place' => $place[0], 'place_fr' => $place[1], 'place_en' => $place[2],
    'instructions' => $instr[0], 'instructions_fr' => $instr[1], 'instructions_en' => $instr[2], 'order' => $order]);
$hq = ['مقر المديرية، قاعة الاجتماعات (حي 08 ماي 1945، سطيف)', 'Siège de la direction, salle de réunion (cité 08 Mai 1945, Sétif)', 'Directorate headquarters, meeting room (08 May 1945 district, Sétif)'];
$originals = ['أحضر النسخ الأصلية للشهادة وكشوف النقاط.', 'Apportez les originaux du diplôme et des relevés de notes.', 'Bring the originals of your diploma and transcripts.'];
$ex1 = $examFields($first, ['المقابلة مع لجنة الانتقاء — الفوج الأول', 'Entretien avec le jury — 1er groupe', 'Interview with the selection board — group 1'], $day(30) . ' 08:30:00', $hq, $originals, 1);
$examFields($first, ['المقابلة مع لجنة الانتقاء — الفوج الثاني', 'Entretien avec le jury — 2e groupe', 'Interview with the selection board — group 2'], $day(31) . ' 08:30:00', $hq, $originals, 2);

// كريم (ملف مقبول) مستدعى للفوج الأول، وأمينة (ملف ناقص) تستطيع رفع الوثيقة الناقصة قبل أجل 5 أيام
[$karim, $amina] = [$created[1], $created[2]];
recruit_convoke($ex1, [(int) $karim['id']]);
$amina->update(['complete_until' => $day(5)]);
demo_fixed_codes(CompetitionApplication::find((int) $karim['id']), 'C26-KMZ4N7Q2', 'DEMO2-KARIM');
demo_fixed_codes(CompetitionApplication::find((int) $amina['id']), 'C26-AHM8D3R5', 'DEMO2-AMINA');

// الامتحان المهني (منتهٍ): 6 مترشحين، 5 ملفات مقبولة ومستدعاة، ونتائج فردية منشورة
$pro = Competition::firstWhere('reference = ?', ['03/2026']);
$proPeople = [
    ['بلقاسمي', 'مراد', 'BELKACEMI', 'Mourad', 'M', '1984-02-11', 'سطيف', '109840211000123456', 'passed', '16.25'],
    ['زروقي', 'حنان', 'ZERROUKI', 'Hanane', 'F', '1986-06-25', 'العلمة', '109860625000234567', 'passed', '14.75'],
    ['عيساوي', 'رشيد', 'AISSAOUI', 'Rachid', 'M', '1982-10-03', 'بوقاعة', '109821003000345678', 'reserve', '13.50'],
    ['دراجي', 'سميرة', 'DERRADJI', 'Samira', 'F', '1987-04-19', 'سطيف', '109870419000456789', 'failed', '08.75'],
    ['مناصري', 'فاتح', 'MENASRI', 'Fateh', 'M', '1985-12-08', 'عين أرنات', '109851208000567890', 'absent', null],
    ['لعريبي', 'وردة', 'LARIBI', 'Warda', 'F', '1989-08-30', 'سطيف', '109890830000678901', null, null],
];
$proPosts = $pro['posts'];
$proReqs = $pro['requirements'];
$proApps = [];
foreach ($proPeople as $n => [$ln, $fn, $lnl, $fnl, $g, $bd, $bp, $nin, $res, $score]) {
    $d = ['post_id' => (int) $proPosts[0]['id'], 'last_name' => $ln, 'first_name' => $fn, 'last_name_latin' => $lnl, 'first_name_latin' => $fnl,
          'gender' => $g, 'birth_date' => $bd, 'birth_place' => $bp, 'nin' => $nin, 'phone' => '06612' . sprintf('%05d', 34500 + $n), 'email' => '',
          'address' => 'حي المعبودة', 'wilaya' => 'سطيف', 'national_service' => $g === 'M' ? 'done' : 'na', 'diploma' => 'ليسانس في العلوم القانونية والإدارية',
          'specialty' => 'إدارة عامة', 'graduation_year' => 2008 + $n, 'experience_years' => 9 + $n];
    foreach ($proReqs as $k => $r) {
        $path = "$tmp/pro-$n-$k.pdf";
        file_put_contents($path, demo_pdf('Arrete de titularisation', "$lnl $fnl"));
        $d['doc_' . $r['id']] = ['name' => 'Arrêté de titularisation - ' . $fnl . '.pdf', 'tmp_name' => $path, 'size' => filesize($path), 'error' => 0];
    }
    [$app] = recruit_store_application($pro, $d, $proPosts, $proReqs);
    $when = $at(55 - 2 * $n, sprintf('%02d:%02d', 9 + $n, 11 * $n % 60));
    $accepted = $n < 5;
    $app->update(['submitted_at' => $when, 'updated_at' => $when, 'status' => $accepted ? 'accepted' : 'rejected',
        'candidate_message' => $accepted ? null : 'نأسف، لم تستوفِ شرط 5 سنوات من الخدمة الفعلية في رتبة متصرف.',
        'reviewed_at' => $at(38, '10:' . (10 + $n)), 'reviewed_by_id' => $adminId]);
    DB::query('UPDATE recruitment_audit SET created_at = ?, ip_address = ? WHERE application_id = ?', [$when, '197.201.' . (30 + $n) . '.' . (40 + $n), (int) $app['id']]);
    $proApps[] = $app;
}
$proExam = $examFields($pro, ['الامتحان المهني — الاختبار الكتابي', 'Examen professionnel — épreuve écrite', 'Professional exam — written test'],
    $day(-25) . ' 09:00:00', ['جامعة فرحات عباس سطيف 1، المدرج 5', 'Université Ferhat Abbas Sétif 1, amphithéâtre 5', 'Ferhat Abbas University Sétif 1, lecture hall 5'],
    ['مدة الاختبار ثلاث ساعات. يُسمح بالقلم الأزرق فقط.', 'Durée de l\'épreuve : trois heures. Stylo bleu uniquement.', 'The test lasts three hours. Blue pen only.'], 1);
recruit_convoke($proExam, array_map(fn($a) => (int) $a['id'], array_slice($proApps, 0, 5)));
$rank = 0;
foreach (array_slice($proPeople, 0, 5) as $n => $p) {
    $app = CompetitionApplication::find((int) $proApps[$n]['id']);
    $note = ['passed' => 'يرجى الحضور إلى مصلحة المستخدمين لاستكمال ملف الترقية قبل نهاية الشهر.', 'reserve' => 'القائمة الاحتياطية صالحة لمدة سنة.'][$p[8]] ?? '';
    recruit_save_result($app, (string) $p[8], (string) $p[9], $p[9] !== null ? (string) ++$rank : '', $note);
}
$pro->update(['results_published_at' => $at(10, '14:00')]);
RecruitmentAudit::log('publish', null, (int) $pro['id'], $pro['title'] . ' — 5 نتيجة');
demo_fixed_codes(CompetitionApplication::find((int) $proApps[0]['id']), 'C26-RSN7T9P3', 'DEMO2-NAJAH');
// أحداث الموظفين المسجّلة من سطر الأوامر (بلا مستخدم) تُنسب للمدير التجريبي
DB::query("UPDATE recruitment_audit SET user_id = ?, username = ? WHERE user_id IS NULL AND action IN ('convoke', 'unconvoke', 'result', 'publish', 'unpublish', 'status', 'update')",
    [$adminId, (string) $admin['username']]);

@array_map('unlink', glob("$tmp/*.pdf"));
// الموظف التجريبي hicham41 بلا «صلاحية مسابقات التوظيف» عمداً: لا يرى المسابقات ولا ملفات المترشحين (لاختبار الصلاحيات)
echo 'المسابقات التجريبية: ' . count($competitions) . ' مسابقات، ' . (count($created) + count($proApps)) . " طلبات ترشح مشفّرة\n";
echo "رموز تجريبية لصفحة «متابعة طلب»: C26-KMZ4N7Q2 / DEMO2-KARIM (مستدعى)، C26-AHM8D3R5 / DEMO2-AMINA (ملف ناقص)، C26-RSN7T9P3 / DEMO2-NAJAH (ناجح)\n";
