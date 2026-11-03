<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<ol class="breadcrumb">
    <li><a href="<?= e(url('admin:index')) ?>">لوحة الإدارة</a></li>
    <li><a href="<?= e(url('admin:recruit_applications') . '?competition=' . (int) $c['id']) ?>">طلبات الترشح</a></li>
    <li class="active">حذف المعطيات</li>
</ol>
<h1 class="admin-title"><i class="fa fa-trash text-danger"></i> <?= e($page_title) ?></h1>
<div class="panel panel-danger admin-panel" style="max-width:720px">
    <div class="panel-heading"><strong><?= e($c['title']) ?></strong></div>
    <div class="panel-body">
        <p>القانون 18-07 يوجب عدم الاحتفاظ بالمعطيات الشخصية أكثر من المدة اللازمة لغرض جمعها. بعد انتهاء المسابقة ونشر النتائج
            (وانقضاء آجال الطعون) يمكنك حذف ملفات المترشحين نهائياً.</p>
        <ul>
            <li>طلبات الترشح: <strong><?= e($count) ?></strong> (مع معطياتها الشخصية المشفّرة)</li>
            <li>الوثائق المرفوعة: <strong><?= e($files) ?></strong> ملفاً مشفّراً</li>
            <li>تبقى المسابقة نفسها وملفاتها المنشورة (الإعلان، القوائم، النتائج) وسجل الاطلاع.</li>
        </ul>
        <?php if (!$allowed): ?>
            <div class="alert alert-warning"><i class="fa fa-clock-o"></i> أجل الترشح لم ينته بعد (آخر يوم: <?= e(format_date($c['closes_at'], 'd/m/Y')) ?>). الحذف متاح بعد انتهائه.</div>
        <?php elseif (!$count): ?>
            <div class="alert alert-info">لا توجد طلبات لحذفها.</div>
        <?php else: ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> الحذف نهائي ولا يمكن التراجع عنه. صدّر القوائم التي تحتاجها قبل الحذف.</div>
            <form method="post" class="form-inline">
                <?= csrf_field() ?>
                <label for="confirm">للتأكيد اكتب كلمة <strong>حذف</strong>:</label>
                <input type="text" name="confirm" id="confirm" class="form-control" autocomplete="off" required>
                <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> حذف <?= e($count) ?> طلب ووثائقه نهائياً</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endblock(); ?>
