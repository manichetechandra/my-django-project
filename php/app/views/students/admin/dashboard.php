<?php extend('base/base'); ?>
<?php block('title'); ?><?= e($page_title) ?> - مديرية الخدمات الجامعية سطيف<?php endblock(); ?>

<?php block('extra_css'); ?>
<style>
    /* ========================================
       تصميم خاص بلوحة المشرف
       ======================================== */
    
    .admin-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        margin-top: 70px;
    }
    
    /* بطاقات الإحصائيات الكبيرة */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        border-right: 4px solid;
        transition: all 0.3s;
    }
    
    /* hover effect - عند مرور الماوس */
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    /* ألوان مختلفة لكل نوع */
    .stat-card.total { border-right-color: #3498db; }
    .stat-card.pending { border-right-color: #f39c12; }
    .stat-card.review { border-right-color: #9b59b6; }
    .stat-card.approved { border-right-color: #2ecc71; }
    .stat-card.rejected { border-right-color: #e74c3c; }
    
    .stat-card .icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    
    .stat-card.total .icon { color: #3498db; }
    .stat-card.pending .icon { color: #f39c12; }
    .stat-card.review .icon { color: #9b59b6; }
    .stat-card.approved .icon { color: #2ecc71; }
    .stat-card.rejected .icon { color: #e74c3c; }
    
    .stat-card .number {
        font-size: 42px;
        font-weight: bold;
        color: #2c3e50;
        margin: 10px 0;
    }
    
    .stat-card .label {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    /* جدول آخر الطلبات */
    .recent-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .recent-table h3 {
        background: #34495e;
        color: white;
        padding: 20px;
        margin: 0;
    }
    
    .table {
        margin: 0;
    }
    
    /* شارة الحالة */
    .status-badge {
        padding: 5px 12px;
        border-radius: 15px;
        color: white;
        font-size: 12px;
        font-weight: bold;
    }
    
    .status-pending { background: #f39c12; }
    .status-under_review { background: #3498db; }
    .status-approved { background: #2ecc71; }
    .status-rejected { background: #e74c3c; }
    
    /* أزرار سريعة */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .quick-action-btn {
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: bold;
    }
    
    .quick-action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        text-decoration: none;
        color: white;
    }
</style>
<?php endblock(); ?>

<?php block('content'); ?>

<!-- ========================================
     رأس الصفحة - Header
     ======================================== -->
<div class="admin-header">
    <div class="container">
        <h1 style="margin: 0; font-size: 36px;">
            <i class="fa fa-dashboard"></i> لوحة تحكم المشرف
        </h1>
        <p style="margin-top: 10px; opacity: 0.9;">
            مرحباً <?= e((($user ? $user->get_full_name() : '') ?: ($user['username'] ?? ''))) ?>
        </p>
    </div>
</div>

<div class="container">
    
    <!-- ========================================
         أزرار سريعة - Quick Actions
         ======================================== -->
    <div class="quick-actions">
        <a href="<?= e(url('students:admin_applications_list')) ?>" class="quick-action-btn" style="background: #3498db;">
            <i class="fa fa-list" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
            عرض كل الطلبات
        </a>
        
        <a href="<?= e(url('students:admin_applications_list')) ?>?status=pending" class="quick-action-btn" style="background: #f39c12;">
            <i class="fa fa-clock-o" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
            الطلبات المعلقة
        </a>
        
        <a href="/admin/students/" class="quick-action-btn" style="background: #9b59b6;">
            <i class="fa fa-cog" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
            إعدادات النظام
        </a>
    </div>
    
    <!-- ========================================
         بطاقات الإحصائيات
         
         الشرح:
         - نستخدم Grid لترتيب البطاقات
         - كل بطاقة تعرض رقم وأيقونة
         - الألوان مختلفة لكل نوع
         ======================================== -->
    <div class="stats-grid">
        <!-- إجمالي الطلبات -->
        <div class="stat-card total">
            <div class="icon">
                <i class="fa fa-files-o"></i>
            </div>
            <div class="number"><?= e($total_applications) ?></div>
            <div class="label">إجمالي الطلبات</div>
        </div>
        
        <!-- قيد الانتظار -->
        <div class="stat-card pending">
            <div class="icon">
                <i class="fa fa-clock-o"></i>
            </div>
            <div class="number"><?= e($pending_count) ?></div>
            <div class="label">قيد الانتظار</div>
        </div>
        
        <!-- قيد المراجعة -->
        <div class="stat-card review">
            <div class="icon">
                <i class="fa fa-search"></i>
            </div>
            <div class="number"><?= e($under_review_count) ?></div>
            <div class="label">قيد المراجعة</div>
        </div>
        
        <!-- معتمدة -->
        <div class="stat-card approved">
            <div class="icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="number"><?= e($approved_count) ?></div>
            <div class="label">معتمدة</div>
        </div>
        
        <!-- مرفوضة -->
        <div class="stat-card rejected">
            <div class="icon">
                <i class="fa fa-times-circle"></i>
            </div>
            <div class="number"><?= e($rejected_count) ?></div>
            <div class="label">مرفوضة</div>
        </div>
    </div>
    
    <!-- ========================================
         إحصائيات زمنية (اليوم/الأسبوع/الشهر)
         ======================================== -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-4">
            <div class="stat-card" style="border-right-color: #1abc9c;">
                <div class="label">طلبات اليوم</div>
                <div class="number" style="font-size: 32px;"><?= e($today_applications) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-right-color: #e67e22;">
                <div class="label">طلبات الأسبوع</div>
                <div class="number" style="font-size: 32px;"><?= e($week_applications) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-right-color: #8e44ad;">
                <div class="label">طلبات الشهر</div>
                <div class="number" style="font-size: 32px;"><?= e($month_applications) ?></div>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         آخر الطلبات المقدمة
         
         الشرح:
         - جدول يعرض آخر 10 طلبات
         - يستخدم Bootstrap table classes
         - كل صف clickable (قابل للنقر)
         ======================================== -->
    <div class="recent-table">
        <h3><i class="fa fa-history"></i> آخر الطلبات المقدمة</h3>
        
        <?php if (!empty($recent_applications)): ?>
        <table class="table table-hover" style="margin: 0;">
            <thead style="background: #ecf0f1;">
                <tr>
                    <th>رقم الطلب</th>
                    <th>الطالب</th>
                    <th>نوع الطلب</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <!-- 
                    Loop عبر كل الطلبات
                    حلقة foreach في PHP
                -->
                <?php foreach ($recent_applications as $app): ?>
                <tr>
                    <td><strong><?= e($app['application_number']) ?></strong></td>
                    <td><?= e($app['student']['full_name_arabic']) ?></td>
                    <td>
                        <i class="fa <?= e($app['application_type']['icon']) ?>" style="color: <?= e($app['application_type']['color']) ?>;"></i>
                        <?= e($app['application_type']['name']) ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= e($app['status']) ?>">
                            <?= e($app->get_status_display()) ?>
                        </span>
                    </td>
                    <td><?= e(format_date($app['submitted_at'], 'd/m/Y H:i')) ?></td>
                    <td>
                        <a href="<?= e(url('students:admin_application_detail', $app['id'])) ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-eye"></i> عرض
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <!-- إذا لم توجد طلبات -->
        <p style="text-align: center; padding: 40px; color: #7f8c8d;">
            لا توجد طلبات بعد
        </p>
        <?php endif; ?>
        
        <!-- زر عرض الكل -->
        <div style="padding: 15px; background: #f8f9fa; text-align: center;">
            <a href="<?= e(url('students:admin_applications_list')) ?>" class="btn btn-default">
                <i class="fa fa-list"></i> عرض كل الطلبات
            </a>
        </div>
    </div>
    
</div>

<br><br>
    <!-- ========================================
         قسم الرسوم البيانية ← جديد! 📊
         ======================================== -->
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12">
            <h2 style="color: #2c3e50; margin-bottom: 30px; border-bottom: 3px solid #e74c3c; padding-bottom: 10px;">
                <i class="fa fa-bar-chart"></i> الرسوم البيانية
            </h2>
        </div>
    </div>
    
    <div class="row">
        <!-- ========================================
             1. Pie Chart - حسب الحالة
             ======================================== -->
        <div class="col-md-6" style="margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0; color: #34495e; text-align: center;">
                    <i class="fa fa-pie-chart"></i> توزيع الطلبات حسب الحالة
                </h4>
                <canvas id="statusChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        
        <!-- ========================================
             2. Bar Chart - حسب النوع
             ======================================== -->
        <div class="col-md-6" style="margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0; color: #34495e; text-align: center;">
                    <i class="fa fa-bar-chart"></i> عدد الطلبات حسب النوع
                </h4>
                <canvas id="typeChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- ========================================
             3. Line Chart - آخر 7 أيام
             ======================================== -->
        <div class="col-md-12" style="margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0; color: #34495e; text-align: center;">
                    <i class="fa fa-line-chart"></i> الطلبات المقدمة خلال آخر 7 أيام
                </h4>
                <canvas id="daysChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
    
</div>

<br><br>

<?php endblock(); ?>

<?php block('extra_js'); ?>
<!-- ========================================
     Chart.js - مكتبة الرسوم البيانية
     CDN = Content Delivery Network (نجلبها من الإنترنت)
     ======================================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// ========================================
// 1. Pie Chart - توزيع حسب الحالة
// ========================================

const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'pie',  // نوع الرسم: دائري
    data: {
        labels: <?= json_js($chart_status_labels) ?>,  // ['قيد الانتظار', 'قيد المراجعة', ...]
        datasets: [{
            label: 'عدد الطلبات',
            data: <?= json_js($chart_status_data) ?>,  // [5, 3, 10, 2]
            backgroundColor: <?= json_js($chart_status_colors) ?>,  // ألوان
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,  // يتكيف مع حجم الشاشة
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',  // الأسطورة في الأسفل
                rtl: true,  // من اليمين لليسار (عربي)
                labels: {
                    font: {
                        size: 14,
                        family: 'Arial'
                    },
                    padding: 15
                }
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' طلب (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// ========================================
// 2. Bar Chart - حسب النوع
// ========================================

const typeCtx = document.getElementById('typeChart').getContext('2d');
const typeChart = new Chart(typeCtx, {
    type: 'bar',  // نوع الرسم: أعمدة
    data: {
        labels: <?= json_js($chart_type_labels) ?>,
        datasets: [{
            label: 'عدد الطلبات',
            data: <?= json_js($chart_type_data) ?>,
            backgroundColor: <?= json_js($chart_type_colors) ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,  // المحور Y يبدأ من الصفر
                ticks: {
                    stepSize: 1,  // خطوة واحدة (1, 2, 3, ...)
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 12
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false  // إخفاء الأسطورة (غير ضرورية)
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        return 'العدد: ' + context.parsed.y + ' طلب';
                    }
                }
            }
        }
    }
});

// ========================================
// 3. Line Chart - آخر 7 أيام
// ========================================

const daysCtx = document.getElementById('daysChart').getContext('2d');
const daysChart = new Chart(daysCtx, {
    type: 'line',  // نوع الرسم: خطي
    data: {
        labels: <?= json_js($chart_days_labels) ?>,
        datasets: [{
            label: 'عدد الطلبات',
            data: <?= json_js($chart_days_data) ?>,
            fill: true,  // تعبئة المنطقة تحت الخط
            backgroundColor: 'rgba(52, 152, 219, 0.2)',  // لون شفاف
            borderColor: '#3498db',  // لون الخط
            borderWidth: 3,
            tension: 0.4,  // انحناء الخط (0 = مستقيم، 1 = منحني جداً)
            pointRadius: 5,  // حجم النقاط
            pointBackgroundColor: '#3498db',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7  // حجم النقطة عند المرور عليها
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    title: function(context) {
                        return context[0].label;
                    },
                    label: function(context) {
                        return 'عدد الطلبات: ' + context.parsed.y;
                    }
                }
            }
        }
    }
});
</script>

<?php endblock(); ?>