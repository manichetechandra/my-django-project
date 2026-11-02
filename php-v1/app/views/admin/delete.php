<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<h1 class="admin-title"><i class="fa fa-trash text-danger"></i> تأكيد الحذف</h1>
<div class="panel panel-danger admin-panel">
    <div class="panel-body">
        <p style="font-size:16px">هل أنت متأكد من حذف <?= e($r['label']) ?>: <strong>"<?= e($obj) ?>"</strong>؟</p>
        <p class="text-muted">سيتم أيضاً حذف كل البيانات المرتبطة به (مثل الصور والوثائق المرفقة). لا يمكن التراجع عن هذه العملية.</p>
        <form method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> نعم، احذف</button>
            <a href="<?= e(url('admin:edit', $r['key'], $obj['id'])) ?>" class="btn btn-default">لا، رجوع</a>
        </form>
    </div>
</div>
<?php endblock(); ?>
