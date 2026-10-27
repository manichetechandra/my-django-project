<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة كل الطلبات - <?= e($student['full_name_arabic']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.4;
            color: #333;
            padding: 5mm;
            font-size: 13px;
        }
        
        /* صفحة الغلاف */
        .cover-page {
            text-align: center;
            padding: 50px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            page-break-after: always;
        }
        
        .cover-page h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .cover-page h2 {
            font-size: 24px;
            color: #3498db;
            margin-bottom: 30px;
        }
        
        .cover-info {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            border: 2px solid #3498db;
            margin: 30px 0;
            max-width: 600px;
        }
        
        .cover-info table {
            width: 100%;
            font-size: 16px;
        }
        
        .cover-info td {
            padding: 10px;
        }
        
        .cover-info td:first-child {
            font-weight: bold;
            color: #2c3e50;
            text-align: right;
            width: 40%;
        }
        
        /* كل طلب في صفحة منفصلة */
        .application-page {
            page-break-after: always;
            page-break-inside: avoid;
        }
        
        .application-page:last-child {
            page-break-after: auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 6px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 13px;
            margin: 3px 0;
        }
        
        .header .app-number {
            margin-top: 8px;
            font-weight: bold;
            font-size: 15px;
            color: #3498db;
        }
        
        /* Barcode */
        .barcode-section {
            text-align: center;
            margin: 10px 0;
            padding: 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .barcode-section img {
            max-width: 250px;
            height: auto;
        }
        
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section h2 {
            background: #ecf0f1;
            padding: 8px;
            border-right: 4px solid #3498db;
            font-size: 14px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        
        .info-table td:first-child {
            width: 30%;
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            color: white;
            font-size: 12px;
        }
        
        .status.pending { background: #f39c12; }
        .status.under_review { background: #3498db; }
        .status.approved { background: #2ecc71; }
        .status.rejected { background: #e74c3c; }
        .status.cancelled { background: #95a5a6; }
        
        .documents {
            list-style: none;
            padding: 0;
        }
        
        .documents li {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            background: #fff;
            font-size: 12px;
        }
        
        .documents li:nth-child(odd) {
            background: #f8f9fa;
        }
        
        .documents li:last-child {
            border-bottom: none;
        }
        
        .admin-notes {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
            page-break-inside: avoid;
        }
        
        .signature-box {
            text-align: center;
            flex: 1;
            padding: 15px;
        }
        
        .signature-box p {
            margin-bottom: 40px;
            font-weight: bold;
            color: #495057;
            font-size: 13px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            width: 150px;
            margin: 0 auto;
        }
        
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid #3498db;
            text-align: center;
            font-size: 11px;
            color: #7f8c8d;
        }
        
        /* زر الطباعة */
        .print-button {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
            transition: all 0.3s;
            z-index: 9999;
        }
        
        .print-button:hover {
            background: #2980b9;
            transform: translateX(-50%) translateY(-3px);
        }
        
        @media print {
            body {
                padding: 0;
                font-size: 12px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .application-page {
                page-break-after: always;
            }
            
            .application-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

    <!-- صفحة الغلاف -->
    <div class="cover-page no-print">
        <h1>🏛️ مديرية الخدمات الجامعية لولاية سطيف</h1>
        <h2>ملف الطلبات الكامل</h2>
        
        <div class="cover-info">
            <table>
                <tr>
                    <td>اسم الطالب:</td>
                    <td><?= e($student['full_name_arabic']) ?></td>
                </tr>
                <tr>
                    <td>رقم التسجيل:</td>
                    <td><strong><?= e($student['registration_number']) ?></strong></td>
                </tr>
                <tr>
                    <td>الكلية:</td>
                    <td><?= e($student['faculty']) ?></td>
                </tr>
                <tr>
                    <td>القسم:</td>
                    <td><?= e($student['department']) ?></td>
                </tr>
                <tr>
                    <td>عدد الطلبات:</td>
                    <td><strong><?= e(count($applications)) ?></strong></td>
                </tr>
                <tr>
                    <td>تاريخ الطباعة:</td>
                    <td id="printDate"></td>
                </tr>
            </table>
        </div>
        
        <p style="color: #7f8c8d; margin-top: 30px;">
            يحتوي هذا الملف على تفاصيل كاملة لجميع الطلبات المقدمة
        </p>
    </div>

    <!-- كل طلب في صفحة منفصلة -->
    <?php if (!empty($applications)): ?><?php $__list = array_values($applications); $__n = count($__list); foreach ($__list as $__i => $app): ?>
    <div class="application-page">
        
        <!-- Header -->
        <div class="header">
            <h1>🏛️ مديرية الخدمات الجامعية لولاية سطيف</h1>
            <p>استمارة طلب - <?= e($app['application_type']['name']) ?></p>
            <p class="app-number">رقم الطلب: <?= e($app['application_number']) ?></p>
        </div>
        
        <!-- Barcode -->
        <div class="barcode-section">
            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?= e($app['application_number']) ?>&scale=3&height=12&includetext=true" 
                 alt="Barcode" 
                 onerror="this.style.display='none';">
        </div>
        
        <!-- معلومات الطلب -->
        <div class="section">
            <h2>📋 معلومات الطلب</h2>
            <table class="info-table">
                <tr>
                    <td>نوع الطلب</td>
                    <td><?= e($app['application_type']['name']) ?></td>
                </tr>
                <tr>
                    <td>رقم الطلب</td>
                    <td><strong><?= e($app['application_number']) ?></strong></td>
                </tr>
                <tr>
                    <td>الحالة</td>
                    <td>
                        <span class="status <?= e($app['status']) ?>">
                            <?= e($app->get_status_display()) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>تاريخ التقديم</td>
                    <td><?= e(format_date($app['submitted_at'], 'd/m/Y - H:i')) ?></td>
                </tr>
                <?php if (!empty($app['reviewed_at'])): ?>
                <tr>
                    <td>تاريخ المراجعة</td>
                    <td><?= e(format_date($app['reviewed_at'], 'd/m/Y - H:i')) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($app['reviewed_by'])): ?>
                <tr>
                    <td>تمت المراجعة بواسطة</td>
                    <td><?= e(($app['reviewed_by']->get_full_name() ?: $app['reviewed_by']['username'])) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- معلومات الطالب -->
        <div class="section">
            <h2>👤 معلومات الطالب</h2>
            <table class="info-table">
                <tr>
                    <td>الاسم الكامل</td>
                    <td><?= e($student['full_name_arabic']) ?></td>
                </tr>
                <tr>
                    <td>رقم التسجيل</td>
                    <td><strong><?= e($student['registration_number']) ?></strong></td>
                </tr>
                <tr>
                    <td>الكلية</td>
                    <td><?= e($student['faculty']) ?></td>
                </tr>
                <tr>
                    <td>القسم</td>
                    <td><?= e($student['department']) ?></td>
                </tr>
                <tr>
                    <td>السنة الدراسية</td>
                    <td><?= e($student->get_current_year_display()) ?></td>
                </tr>
                <tr>
                    <td>رقم الهاتف</td>
                    <td><?= e($student['phone']) ?></td>
                </tr>
            </table>
        </div>
        
        <!-- تفاصيل الطلب -->
        <?php if (!empty($app['details'])): ?>
        <div class="section">
            <h2>📝 تفاصيل الطلب</h2>
            <div style="padding: 15px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 5px;">
                <?= linebreaks($app['details']) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- الوثائق المرفقة -->
        <?php if (!empty($app['documents'])): ?>
        <div class="section">
            <h2>📎 الوثائق المرفقة</h2>
            <ul class="documents">
                <?php $__list = array_values($app['documents']); $__n = count($__list); foreach ($__list as $__i => $doc): ?>
                <li>
                    <strong><?= e(($__i + 1)) ?>. <?= e($doc['document_name']) ?></strong><br>
                    <small style="color: #6c757d;">
                        📅 تاريخ الرفع: <?= e(format_date($doc['uploaded_at'], 'd/m/Y - H:i')) ?>
                    </small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- ملاحظات الإدارة -->
        <?php if (!empty($app['admin_notes'])): ?>
        <div class="section">
            <h2>⚠️ ملاحظات الإدارة</h2>
            <div class="admin-notes">
                <?= linebreaks($app['admin_notes']) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- التوقيعات -->
        <div class="signatures">
            <div class="signature-box">
                <p>توقيع الطالب</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>توقيع المسؤول</p>
                <div class="signature-line"></div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>مديرية الخدمات الجامعية لولاية سطيف</strong></p>
            <p>www.dsu-setif.dz | الهاتف: 036 XX XX XX</p>
            <p style="margin-top: 5px; font-style: italic;">
                الطلب <?= e(($__i + 1)) ?> من <?= e(count($applications)) ?>
            </p>
        </div>
        
    </div>
    <?php endforeach; ?><?php else: ?>
    <div style="text-align: center; padding: 100px; color: #7f8c8d;">
        <h2>لا توجد طلبات</h2>
        <p>لم تقدم أي طلبات بعد</p>
    </div>
    <?php endif; ?>

    <!-- زر الطباعة -->
    <button class="print-button no-print" onclick="window.print()">
        🖨️ طباعة الملف الكامل (<?= e(count($applications)) ?> طلب)
    </button>
    
    <script>
        // إضافة تاريخ الطباعة
        const printDateElement = document.getElementById('printDate');
        if (printDateElement) {
            printDateElement.textContent = new Date().toLocaleString('ar-DZ', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
    
</body>
</html>