<?php extend('admin/layout'); ?>
<?php block('title'); ?><?= e($page_title) ?><?php endblock(); ?>
<?php block('content'); ?>
<ol class="breadcrumb">
    <li><a href="<?= e(url('admin:index')) ?>">لوحة الإدارة</a></li>
    <li class="active">ترجمة المحتوى</li>
</ol>
<h1 class="admin-title"><i class="fa fa-language"></i> <?= e($page_title) ?></h1>

<div class="panel panel-default admin-panel">
    <div class="panel-body">
        <p>
            المحتوى الذي أُضيف قبل النسخة 2.0 (أو حُفظ دون ترجمة) يظهر بالعربية في النسختين الفرنسية والإنجليزية.
            هذه الصفحة تترجمه تلقائياً سجلاً بعد سجل، ولا تلمس الترجمات الموجودة.
        </p>
        <?php if (!$enabled): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> الترجمة التلقائية غير مفعّلة. افتح <code>config.php</code> وضع مفتاح Gemini المجاني
                (من <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a>) في <code>'gemini_key'</code>،
                أو اجعل <code>'provider' => 'mymemory'</code>.
            </div>
        <?php else: ?>
            <p class="text-muted small">الخدمة: <strong><?= e(implode(' ← ', $providers)) ?></strong> (الثانية بديل عند فشل الأولى)</p>
        <?php endif; ?>

        <?php if (!$counts): ?>
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> كل المحتوى مترجم. لا يوجد شيء ينقصه ترجمة.</div>
        <?php else: ?>
            <table class="table table-condensed" style="max-width: 520px">
                <thead><tr><th>القسم</th><th style="width: 160px">سجلات تنقصها ترجمة</th></tr></thead>
                <tbody>
                <?php foreach ($counts as $table => $n): ?>
                    <tr><td><?= e($labels[$table] ?? $table) ?></td><td><strong><?= e($n) ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th>المجموع</th><th data-remaining><?= e(array_sum($counts)) ?></th></tr></tfoot>
            </table>
            <?php if ($enabled): ?>
                <form id="translate-all-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success"><i class="fa fa-play"></i> ابدأ الترجمة</button>
                    <button type="button" class="btn btn-default" data-stop disabled><i class="fa fa-stop"></i> إيقاف</button>
                    <span class="admin-tr-status" data-status style="margin-right: 10px"></span>
                </form>
                <div class="progress" style="margin-top: 15px; max-width: 520px">
                    <div class="progress-bar progress-bar-success" data-bar style="width: 0%"></div>
                </div>
                <div class="admin-tr-log" data-log hidden></div>
                <p class="small text-muted" style="margin-top: 10px">
                    يمكنك إغلاق الصفحة في أي وقت ثم العودة لاحقاً: تستأنف الترجمة من حيث توقفت.
                    من سطر الأوامر: <code dir="ltr">php database/tools/translate_content.php</code>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('translate-all-form');
    if (!form) { return; }
    var total = <?= (int) array_sum($counts) ?>, done = 0, running = false, skip = {};
    var status = document.querySelector('[data-status]'), bar = document.querySelector('[data-bar]');
    var log = document.querySelector('[data-log]'), stop = document.querySelector('[data-stop]');
    var remainingCell = document.querySelector('[data-remaining]');
    function step() {
        if (!running) { status.textContent = 'تم الإيقاف.'; return; }
        var body = new FormData(form);
        body.append('skip', JSON.stringify(skip));
        fetch(location.href, {method: 'POST', body: body, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { throw new Error(data.error || 'خطأ'); }
                Object.keys(data.ids || {}).forEach(function (t) { skip[t] = (skip[t] || []).concat(data.ids[t]); });
                done += data.done;
                log.hidden = false;
                (data.log || []).forEach(function (line) { log.textContent += '✓ ' + line + '\n'; });
                log.scrollTop = log.scrollHeight;
                bar.style.width = Math.min(100, Math.round(done * 100 / Math.max(total, 1))) + '%';
                remainingCell.textContent = data.remaining;
                if (data.done === 0 || data.remaining === 0) {
                    running = false; stop.disabled = true;
                    status.textContent = '✓ انتهت الترجمة. السجلات المترجمة: ' + done + (data.provider ? ' (' + data.provider + ')' : '') + '.';
                    return;
                }
                status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ الترجمة: ' + done + ' / ' + total;
                step();
            })
            .catch(function (e) {
                running = false; stop.disabled = true;
                status.textContent = '✗ توقفت الترجمة: ' + e.message;
            });
    }
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (running) { return; }
        running = true; stop.disabled = false;
        status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ الترجمة...';
        step();
    });
    stop.addEventListener('click', function () { running = false; stop.disabled = true; });
})();
</script>
<?php endblock(); ?>
