<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" dir="<?= text_dir() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        @page {
            size: 8.5cm 5.3cm;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Segoe UI', sans-serif;
            background: #f0f0f0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        /* البطاقة */
        .student-card {
            width: 8.5cm;
            height: 5.3cm;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* الشريط العلوي */
        .card-header {
            background: linear-gradient(to left, #e3f2fd, #bbdefb);
            padding: 0.15cm;
            text-align: center;
            border-bottom: 2px solid #64b5f6;
        }
        
        .card-header h3 {
            font-size: 10pt;
            color: #1565c0;
            margin: 0;
            font-weight: bold;
        }
        
        .card-header p {
            font-size: 7pt;
            color: #555;
            margin: 2px 0 0 0;
        }
        
        /* جسم البطاقة */
        .card-body {
            display: flex;
            padding: 0.3cm;
            gap: 0.25cm;
        }
        
        /* قسم الصورة (يسار) */
        .photo-section {
            width: 2.5cm;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15cm;
        }
        
        /* الصورة - مربعة */
        .student-photo {
            width: 2.2cm;
            height: 2.4cm;
            border: 2px solid #90caf9;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .student-photo i {
            font-size: 1cm;
            color: #bdbdbd;
        }
        
        /* Barcode */
        .barcode-container {
            width: 1.8cm;
            background: white;
            padding: 2px;
            border: 1px solid #e0e0e0;
            border-radius: 2px;
            margin-top: 0.1cm;
        }
        
        .barcode-container img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        /* قسم المعلومات (يمين) */
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* الاسم ورقم التسجيل */
        .student-header {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 0.1cm;
        }
        
        .student-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 0.1cm;
        }
        
        .student-id {
            background: #1976d2;
            color: white;
            font-size: 8pt;
            font-weight: bold;
            padding: 5px 8px;
            border-radius: 3px;
            display: inline-block;
        }
        
        /* المعلومات الأكاديمية */
        .student-details {
            flex: 1;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.1cm;
            font-size: 7.5pt;
        }
        
        .detail-label {
            min-width: 1.3cm;
            color: #666;
            font-weight: 600;
        }
        
        .detail-value {
            color: #333;
            flex: 1;
        }
        
        /* التذييل */
        .card-footer {
            position: absolute;
            bottom: 0;
            inset-inline-end: 0;
            inset-inline-start: 0;
            background: #fafafa;
            text-align: center;
            padding: 0.15cm;
            font-size: 6pt;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        
        /* شعار صغير */
        .university-icon {
            position: absolute;
            top: 0.2cm;
            inset-inline-start: 0.2cm;
            font-size: 16px;
            color: #1976d2;
            opacity: 0.3;
        }
        
        /* أزرار الطباعة */
        .print-controls {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 25px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1976d2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1565c0;
            color: white;
        }
        
        .btn-secondary {
            background: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #616161;
            color: white;
        }
        
        /* إخفاء عند الطباعة */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .student-card {
                box-shadow: none;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- البطاقة -->
<div class="student-card">
    
    <!-- أيقونة الجامعة -->
    <div class="university-icon">
        <i class="fa fa-university"></i>
    </div>
    
    <!-- الهيدر -->
    <div class="card-header">
        <h3>جامعة سطيف</h3>
        <p>بطاقة طالب - Student Card</p>
    </div>
    
    <!-- الجسم -->
    <div class="card-body">
        
        <!-- قسم الصورة والباركود -->
        <div class="photo-section">
            <!-- الصورة الشخصية - مربعة -->
            <div class="student-photo">
                <?php if (!empty($student['photo'])): ?>
                <img src="<?= e(media_url($student['photo'])) ?>" alt="<?= e($student['full_name_arabic']) ?>">
                <?php else: ?>
                <i class="fa fa-user"></i>
                <?php endif; ?>
            </div>
            
            <!-- Barcode -->
            <div class="barcode-container">
                <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?= e($student['registration_number']) ?>&scale=2&height=10&includetext=false" 
                     alt="Barcode" 
                     onerror="this.style.display='none';">
            </div>
        </div>
        
        <!-- قسم المعلومات -->
        <div class="info-section">
            
            <!-- الاسم والرقم -->
            <div class="student-header">
                <div class="student-name"><?= e($student['full_name_arabic']) ?></div>
                <div class="student-id"><?= e($student['registration_number']) ?></div>
            </div>
            
            <!-- التفاصيل -->
            <div class="student-details">
                <div class="detail-row">
                    <span class="detail-label">الكلية:</span>
                    <span class="detail-value"><?= e(truncatewords($student['faculty'], 3)) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">القسم:</span>
                    <span class="detail-value"><?= e(truncatewords($student['department'], 3)) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">السنة:</span>
                    <span class="detail-value"><?= e($student->get_current_year_display()) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">تاريخ الميلاد:</span>
                    <span class="detail-value"><?= e(format_date($student['birth_date'], 'd/m/Y')) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">صالحة حتى:</span>
                    <span class="detail-value">30/09/<?= e(format_date($student['created_at'], 'Y')) ?></span>
                </div>
            </div>
            
        </div>
        
    </div>
    
    <!-- التذييل -->
    <div class="card-footer">
        مديرية الخدمات الجامعية - ولاية سطيف | www.dsu-setif.dz
    </div>
    
</div>

<!-- أزرار التحكم -->
<div class="no-print print-controls">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fa fa-print"></i> طباعة البطاقة
    </button>
    
    <a href="<?= e(url('students:dashboard')) ?>" class="btn btn-secondary">
        <i class="fa fa-arrow-right"></i> رجوع للوحة التحكم
    </a>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; max-width: 600px; margin-inline-end: auto; margin-inline-start: auto;">
        <strong><i class="fa fa-info-circle"></i> نصائح للطباعة:</strong>
        <ul style="text-align: start; margin: 10px 0 0 0; padding-inline-start: 20px; font-size: 13px; color: #856404;">
            <li>استخدم ورق مقوى (Cardstock) للحصول على بطاقة متينة</li>
            <li>تأكد من تفعيل خيار "طباعة الخلفيات" في إعدادات الطباعة</li>
            <li>القياس: 8.5 سم × 5.3 سم (حجم بطاقة ائتمانية)</li>
            <li>يمكنك حفظها كـ PDF من خيارات الطباعة</li>
            <li>الباركود يحتوي على رقم التسجيل للمسح السريع</li>
        </ul>
    </div>
</div>

</body>
</html>