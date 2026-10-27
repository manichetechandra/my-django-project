<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الطلب - <?= e($application['application_number']) ?></title>
 <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    @page {
        size: A4;
        margin: 10mm; /* ← قللنا من 15mm */
    }
    
    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.4; /* ← قللنا من 1.6 */
        color: #333;
        padding: 5mm; /* ← قللنا من 10mm */
        font-size: 13px; /* ← أضفنا حجم خط أصغر */
    }
    
    .header {
        text-align: center;
        margin-bottom: 15px; /* ← قللنا من 30px */
        border-bottom: 3px solid #3498db;
        padding-bottom: 12px; /* ← قللنا من 20px */
        position: relative;
    }
    
    .header h1 {
        color: #2c3e50;
        font-size: 20px; /* ← قللنا من 24px */
        margin-bottom: 6px; /* ← قللنا من 10px */
    }
    
    .header p {
        color: #7f8c8d;
        font-size: 13px; /* ← قللنا من 14px */
        margin: 3px 0; /* ← جديد */
    }
    
    .header .app-number {
        margin-top: 8px; /* ← قللنا من 15px */
        font-weight: bold;
        font-size: 15px; /* ← قللنا من 16px */
        color: #3498db;
    }
    
    /* Barcode */
    .barcode-section {
        text-align: center;
        margin: 10px 0; /* ← قللنا من 20px */
        padding: 8px; /* ← قللنا من 10px */
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }
    
    .barcode-section img {
        max-width: 250px; /* ← قللنا من 300px */
        height: auto;
    }
    
    .section {
        margin-bottom: 15px; /* ← قللنا من 25px */
        page-break-inside: auto; /* ← غيرنا من avoid */
    }
    
    .section h2 {
        background: #ecf0f1;
        padding: 8px; /* ← قللنا من 10px */
        border-right: 4px solid #3498db;
        font-size: 14px; /* ← قللنا من 16px */
        margin-bottom: 10px; /* ← قللنا من 15px */
        color: #2c3e50;
    }
    
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px; /* ← قللنا من 15px */
    }
    
    .info-table td {
        padding: 8px; /* ← قللنا من 12px */
        border: 1px solid #ddd;
        font-size: 12px; /* ← جديد */
    }
    
    .info-table td:first-child {
        width: 30%; /* ← قللنا من 35% */
        background: #f8f9fa;
        font-weight: bold;
        color: #495057;
    }
    
    .status {
        display: inline-block;
        padding: 5px 15px; /* ← قللنا من 8px 20px */
        border-radius: 15px; /* ← قللنا من 20px */
        font-weight: bold;
        color: white;
        font-size: 12px; /* ← قللنا من 14px */
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
        padding: 8px; /* ← قللنا من 12px */
        border-bottom: 1px solid #ddd;
        background: #fff;
        font-size: 12px; /* ← جديد */
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
        padding: 10px; /* ← قللنا من 15px */
        border-radius: 5px;
        font-size: 12px; /* ← جديد */
    }
    
    .signatures {
        margin-top: 30px; /* ← قللنا من 50px */
        display: flex;
        justify-content: space-around;
        page-break-inside: avoid;
    }
    
    .signature-box {
        text-align: center;
        flex: 1;
        padding: 15px; /* ← قللنا من 20px */
    }
    
    .signature-box p {
        margin-bottom: 40px; /* ← قللنا من 60px */
        font-weight: bold;
        color: #495057;
        font-size: 13px; /* ← جديد */
    }
    
    .signature-line {
        border-top: 2px solid #000;
        width: 150px; /* ← قللنا من 200px */
        margin: 0 auto;
    }
    
    .footer {
        margin-top: 25px; /* ← قللنا من 40px */
        padding-top: 15px; /* ← قللنا من 20px */
        border-top: 2px solid #3498db;
        text-align: center;
        font-size: 11px; /* ← قللنا من 12px */
        color: #7f8c8d;
    }
    
    .print-date {
        margin-top: 5px; /* ← قللنا من 10px */
        font-style: italic;
        color: #6c757d;
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
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.6);
    }
    
    /* إخفاء عند الطباعة */
    @media print {
        body {
            padding: 0;
            font-size: 12px;
        }
        
        .no-print {
            display: none !important;
        }
        
        .print-button {
            display: none !important;
        }
        
        .section {
            page-break-inside: auto;
        }
        
        /* منع قطع التوقيعات */
        .signatures {
            page-break-inside: avoid;
        }
    }
</style>
</head>
<body>
    
    <!-- رأس الصفحة -->
    <div class="header">
        <h1>🏛️ مديرية الخدمات الجامعية لولاية سطيف</h1>
        <p>استمارة طلب - <?= e($application['application_type']['name']) ?></p>
        <p class="app-number">رقم الطلب: <?= e($application['application_number']) ?></p>
    </div>
    
    <!-- Barcode -->
    <div class="barcode-section">
        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?= e($application['application_number']) ?>&scale=3&height=12&includetext=true" 
             alt="Barcode" 
             onerror="this.style.display='none';">
    </div>
    
    <!-- معلومات الطلب -->
    <div class="section">
        <h2>📋 معلومات الطلب</h2>
        <table class="info-table">
            <tr>
                <td>نوع الطلب</td>
                <td><?= e($application['application_type']['name']) ?></td>
            </tr>
            <tr>
                <td>رقم الطلب</td>
                <td><strong><?= e($application['application_number']) ?></strong></td>
            </tr>
            <tr>
                <td>الحالة</td>
                <td>
                    <span class="status <?= e($application['status']) ?>">
                        <?= e($application->get_status_display()) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>تاريخ التقديم</td>
                <td><?= e(format_date($application['submitted_at'], 'd/m/Y - H:i')) ?></td>
            </tr>
            <?php if (!empty($application['reviewed_at'])): ?>
            <tr>
                <td>تاريخ المراجعة</td>
                <td><?= e(format_date($application['reviewed_at'], 'd/m/Y - H:i')) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($application['reviewed_by'])): ?>
            <tr>
                <td>تمت المراجعة بواسطة</td>
                <td><?= e(($application['reviewed_by']->get_full_name() ?: $application['reviewed_by']['username'])) ?></td>
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
                <td><?= e($application['student']['full_name_arabic']) ?></td>
            </tr>
            <tr>
                <td>رقم التسجيل</td>
                <td><strong><?= e($application['student']['registration_number']) ?></strong></td>
            </tr>
            <tr>
                <td>الكلية</td>
                <td><?= e($application['student']['faculty']) ?></td>
            </tr>
            <tr>
                <td>القسم</td>
                <td><?= e($application['student']['department']) ?></td>
            </tr>
            <tr>
                <td>السنة الدراسية</td>
                <td><?= e($application['student']->get_current_year_display()) ?></td>
            </tr>
            <tr>
                <td>رقم الهاتف</td>
                <td><?= e($application['student']['phone']) ?></td>
            </tr>
        </table>
    </div>
    
    <!-- تفاصيل الطلب -->
    <?php if (!empty($application['details'])): ?>
    <div class="section">
        <h2>📝 تفاصيل الطلب</h2>
        <div style="padding: 15px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 5px;">
            <?= linebreaks($application['details']) ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- الوثائق المرفقة -->
    <?php if (!empty($application['documents'])): ?>
    <div class="section">
        <h2>📎 الوثائق المرفقة</h2>
        <ul class="documents">
            <?php $__list = array_values($application['documents']); $__n = count($__list); foreach ($__list as $__i => $doc): ?>
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
    <?php if (!empty($application['admin_notes'])): ?>
    <div class="section">
        <h2>⚠️ ملاحظات الإدارة</h2>
        <div class="admin-notes">
            <?= linebreaks($application['admin_notes']) ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- توقيعات -->
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
        <p class="print-date" id="printDate">تاريخ الطباعة: ...</p>
    </div>
    
    <!-- زر الطباعة -->
    <button class="print-button no-print" onclick="window.print()">
        🖨️ طباعة الطلب
    </button>
    
    <script>
        // إضافة تاريخ الطباعة
        document.getElementById('printDate').textContent = 
            'تاريخ الطباعة: ' + new Date().toLocaleString('ar-DZ', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
    </script>
</body>
</html>