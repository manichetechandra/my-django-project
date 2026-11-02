<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<h1 class="admin-title"><i class="fa fa-home"></i> مرحباً، <?= e(current_user()->display_name()) ?></h1>

<div class="row">
    <div class="col-sm-6 col-md-3">
        <a class="admin-stat" style="border-color:#f39c12" href="<?= e(url('students:admin_applications_list')) ?>?status=pending">
            <i class="fa fa-clock-o" style="color:#f39c12"></i><strong><?= e($pending_apps) ?></strong><span>طلبات قيد الانتظار</span>
        </a>
    </div>
    <div class="col-sm-6 col-md-3">
        <a class="admin-stat" style="border-color:#e74c3c" href="<?= e(isset($resources['contact_messages']) ? url('admin:list', 'contact_messages') . '?f_is_read=0' : '#') ?>">
            <i class="fa fa-envelope" style="color:#e74c3c"></i><strong><?= e($unread_messages) ?></strong><span>رسائل غير مقروءة</span>
        </a>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="admin-stat" style="border-color:#3498db">
            <i class="fa fa-users" style="color:#3498db"></i><strong><?= e(number_format((int) $stats['total_visitors'])) ?></strong><span>إجمالي الزوار</span>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="admin-stat" style="border-color:#8cc040">
            <i class="fa fa-eye" style="color:#8cc040"></i><strong><?= e(number_format((int) $stats['total_page_views'])) ?></strong><span>مشاهدات الصفحات (<?= e($today_visits) ?> اليوم)</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <?php $groups = []; foreach ($resources as $key => $res) { $groups[$res['group']][$key] = $res; } ?>
        <?php foreach ($groups as $group => $items): ?>
            <div class="panel panel-default admin-panel">
                <div class="panel-heading"><strong><?= e($group) ?></strong></div>
                <table class="table table-hover admin-index-table">
                    <?php foreach ($items as $key => $res): ?>
                        <tr>
                            <td><a href="<?= e(url('admin:list', $key)) ?>"><i class="fa <?= e($res['icon']) ?> fa-fw"></i> <?= e($res['plural']) ?></a></td>
                            <td class="text-muted" style="width:90px"><?= e($counts[$key]) ?> سجل</td>
                            <td style="width:170px" class="text-left">
                                <?php if ($res['can_add'] ?? true): ?>
                                    <a href="<?= e(url('admin:add', $key)) ?>" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> إضافة</a>
                                <?php endif; ?>
                                <a href="<?= e(url('admin:list', $key)) ?>" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i> إدارة</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-envelope"></i> آخر الرسائل</strong></div>
            <div class="list-group">
                <?php foreach ($latest_messages as $m): ?>
                    <a class="list-group-item" href="<?= e(url('admin:edit', 'contact_messages', $m['id'])) ?>">
                        <?php if (!$m['is_read']): ?><span class="badge" style="background:#e74c3c">جديدة</span><?php endif; ?>
                        <strong><?= e($m['full_name']) ?></strong> — <?= e($m->get_subject_display()) ?>
                        <div class="small text-muted"><?= e(truncatewords($m['message'], 10)) ?> · <?= e(timesince($m['created_at'])) ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$latest_messages): ?><div class="list-group-item text-muted">لا توجد رسائل</div><?php endif; ?>
            </div>
        </div>
        <div class="panel panel-default admin-panel">
            <div class="panel-heading"><strong><i class="fa fa-file-text"></i> آخر طلبات الطلاب</strong></div>
            <div class="list-group">
                <?php foreach ($latest_apps as $a): ?>
                    <a class="list-group-item" href="<?= e(url('students:admin_application_detail', $a['id'])) ?>">
                        <span class="badge" style="background:<?= e($a->get_status_color()) ?>"><?= e($a->get_status_display()) ?></span>
                        <strong><?= e($a['application_number']) ?></strong>
                        <div class="small text-muted"><?= e($a['student']['full_name_arabic'] ?? '') ?> · <?= e(format_date($a['submitted_at'], 'd/m/Y H:i')) ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$latest_apps): ?><div class="list-group-item text-muted">لا توجد طلبات</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endblock(); ?>
