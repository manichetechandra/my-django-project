<?php extend('base/base'); ?>
<?php block('title'); ?><?= t('متابعة طلب ترشح') ?> <?= t('- مديرية الخدمات الجامعية') ?><?php endblock(); ?>
<?php block('extra_css'); ?><link rel="stylesheet" href="<?= asset('css/competitions.css') ?>"><?php endblock(); ?>
<?php block('inline_messages'); ?>1<?php endblock(); ?>

<?php block('content'); ?>
<div class="container cmp-page">
    <div class="cmp-title">
        <h1><i class="fa fa-search"></i> <?= t('متابعة طلب ترشح') ?></h1>
        <a href="<?= e(url('competitions:list')) ?>" class="btn btn-default"><i class="fa fa-trophy"></i> <?= t('مسابقات التوظيف') ?></a>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?= e(alert_class($message['tags'])) ?>" role="alert">
            <i class="fa <?= $message['tags'] === 'success' ? 'fa-check-circle' : ($message['tags'] === 'info' ? 'fa-info-circle' : 'fa-exclamation-triangle') ?>"></i>
            <?= e($message['message']) ?>
        </div>
    <?php endforeach; ?>

    <?php if ($app): ?>
        <?php
        $st = (string) $app['status'];
        $info = [
            'pending'      => ['fa-inbox', 'تم استلام طلبك', 'طلبك مسجّل وفي انتظار دراسته من طرف لجنة المسابقة.'],
            'under_review' => ['fa-search', 'طلبك قيد الدراسة', 'لجنة المسابقة تدرس ملفك حالياً.'],
            'incomplete'   => ['fa-exclamation-triangle', 'ملفك ناقص', 'اطّلع على رسالة الإدارة أدناه لمعرفة ما ينقص ملفك.'],
            'accepted'     => ['fa-check-circle', 'قُبل ملفك', 'تهانينا! يظهر استدعاؤك في هذه الصفحة عند تحديد موعد الاختبار أو المقابلة.'],
            'rejected'     => ['fa-times-circle', 'لم يُقبل ملفك', 'نأسف، لم يُقبل ملفك في هذه المسابقة. اطّلع على رسالة الإدارة أدناه إن وُجدت.'],
        ][$st] ?? ['fa-info-circle', '', ''];
        if ($show_result) {
            $info = ['fa-flag-checkered', 'نُشرت نتائج المسابقة', 'نتيجتك مبيّنة أدناه، ويمكنك طباعة إشعار بها.'];
        } elseif ($exam) {
            $info = ['fa-calendar-check-o', 'قُبل ملفك وتم استدعاؤك', 'اطبع استدعاءك واحضر به يوم الاختبار مع بطاقة التعريف الوطنية.'];
        }
        // المراحل: [النص, منجزة؟]
        $steps = [
            ['استلام الطلب', true],
            ['دراسة الملف', in_array($st, ['accepted', 'rejected'], true)],
            ['الاستدعاء', $exam !== null],
            ['النتيجة', $show_result],
        ];
        $current = null;
        foreach ($steps as $n => $s) {
            if (!$s[1]) { $current = $n; break; }
        }
        $byRequirement = [];
        foreach ($files as $f) {
            $byRequirement[(int) $f['requirement_id']][] = $f;
        }
        // اسم الوثيقة بلغة الصفحة إن كانت الوثيقة المطلوبة ما تزال موجودة، وإلا الاسم المحفوظ يوم الرفع
        $reqLabels = [];
        foreach ($c ? $c['requirements'] : [] as $r) {
            $reqLabels[(int) $r['id']] = (string) $r['label'];
        }
        ?>
        <div class="row">
            <div class="col-md-8">
                <div class="cmp-status is-<?= e($exam || $show_result ? 'accepted' : $st) ?>">
                    <i class="fa <?= e($info[0]) ?>"></i>
                    <div><h3><?= t($info[1]) ?></h3><p><?= t($info[2]) ?></p></div>
                </div>
                <ol class="cmp-steps">
                    <?php foreach ($steps as $n => [$label, $done]): ?>
                        <li class="<?= $done ? 'done' : ($n === $current && $st !== 'rejected' ? 'current' : '') ?>"><?= t($label) ?></li>
                    <?php endforeach; ?>
                </ol>

                <?php if (trim((string) $app['candidate_message']) !== ''): ?>
                    <h4 class="cmp-h4"><i class="fa fa-envelope-o"></i> <?= t('رسالة الإدارة') ?></h4>
                    <div class="cmp-message" dir="auto"><?= nl2br(e($app['candidate_message'])) ?></div>
                <?php endif; ?>

                <?php if ($show_result): $r = (string) $app['result']; ?>
                    <?php
                    $resultText = [
                        'passed'  => ['fa-trophy', 'تهانينا! أنت من الناجحين في هذه المسابقة. ستتصل بك المديرية لاستكمال إجراءات التوظيف.'],
                        'reserve' => ['fa-list-ol', 'أنت في القائمة الاحتياطية: قد تُستدعى للتوظيف إن شغر منصب.'],
                        'failed'  => ['fa-info-circle', 'نأسف، لم توفَّق في هذه المسابقة. نتمنى لك التوفيق في المسابقات القادمة.'],
                        'absent'  => ['fa-user-times', 'سُجّلت غائباً عن الاختبار.'],
                    ][$r] ?? ['fa-info-circle', ''];
                    ?>
                    <section class="cmp-card cmp-result is-<?= e($r) ?>" id="result">
                        <div class="cmp-result-main">
                            <i class="fa <?= e($resultText[0]) ?>"></i>
                            <div>
                                <small><?= t('نتيجة المسابقة') ?></small>
                                <strong><?= e(CompetitionApplication::choiceLabel('result', $r)) ?></strong>
                            </div>
                        </div>
                        <div class="cmp-card-body">
                            <p class="cmp-lead"><?= t($resultText[1]) ?></p>
                            <?php if ($app->score_text() !== '' || !empty($app['result_rank'])): ?>
                                <div class="cmp-figures">
                                    <?php if ($app->score_text() !== ''): ?>
                                        <div><span><?= t('العلامة') ?></span><b dir="ltr"><?= e($app->score_text()) ?> / 20</b></div>
                                    <?php endif; ?>
                                    <?php if (!empty($app['result_rank'])): ?>
                                        <div><span><?= t('الترتيب') ?></span><b><?= (int) $app['result_rank'] ?></b></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (trim((string) $app['result_note']) !== ''): ?>
                                <div class="cmp-message" dir="auto"><?= nl2br(e($app['result_note'])) ?></div>
                            <?php endif; ?>
                            <a href="<?= e(url('competitions:track_result')) ?>" class="btn btn-primary" target="_blank" rel="noopener"><i class="fa fa-print"></i> <?= t('طباعة إشعار النتيجة') ?></a>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($exam): ?>
                    <section class="cmp-card cmp-convocation" id="convocation">
                        <div class="cmp-card-head"><i class="fa fa-calendar-check-o"></i> <h3><?= t('الاستدعاء') . colon() ?> <?= e($exam['title']) ?></h3></div>
                        <div class="cmp-card-body">
                            <div class="cmp-when">
                                <div class="cmp-when-day">
                                    <b><?= e(format_date($exam['starts_at'], 'j')) ?></b>
                                    <span><?= e(format_date($exam['starts_at'], 'F Y')) ?></span>
                                </div>
                                <table class="cmp-kv">
                                    <tr><th><?= t('اليوم') ?></th><td><?= e(format_date($exam['starts_at'], 'l j F Y')) ?></td></tr>
                                    <tr><th><?= t('الساعة') ?></th><td><bdi dir="ltr"><?= e(format_date($exam['starts_at'], 'H:i')) ?></bdi></td></tr>
                                    <?php if (trim((string) $exam['place']) !== ''): ?>
                                        <tr><th><?= t('المكان') ?></th><td><?= e($exam['place']) ?></td></tr>
                                    <?php endif; ?>
                                    <tr><th><?= t('رقم التسجيل') ?></th><td><span class="cmp-seat"><?= e($app['seat_number']) ?></span></td></tr>
                                </table>
                            </div>
                            <?php if (trim((string) $exam['instructions']) !== ''): ?>
                                <p class="cmp-instructions"><i class="fa fa-info-circle"></i> <?= e($exam['instructions']) ?></p>
                            <?php endif; ?>
                            <?php if ($exam->is_past()): ?>
                                <p class="cmp-muted"><i class="fa fa-clock-o"></i> <?= t('انقضى موعد هذا الاختبار.') ?></p>
                            <?php endif; ?>
                            <a href="<?= e(url('competitions:track_convocation')) ?>" class="btn btn-success" target="_blank" rel="noopener"><i class="fa fa-print"></i> <?= t('طباعة الاستدعاء') ?></a>
                            <span class="help-block cmp-inline-help"><?= t('احضر بالاستدعاء مطبوعاً وبطاقة التعريف الوطنية الأصلية.') ?></span>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($st === 'incomplete'): ?>
                    <?php if ($requirements): ?>
                        <?php $until = (string) ($app['complete_until'] ?? ''); $left = $until !== '' ? strtotime($until . ' 23:59:59') - time() : 0; ?>
                        <section class="cmp-card cmp-complete" id="complete">
                            <div class="cmp-card-head"><i class="fa fa-upload"></i> <h3><?= t('إكمال ملفك') ?></h3></div>
                            <div class="cmp-card-body">
                                <p><?= t('ارفع الوثائق الناقصة المذكورة في رسالة الإدارة (ملف PDF لكل وثيقة). لا حاجة لإعادة رفع الوثائق الأخرى.') ?></p>
                                <?php if ($until !== ''): ?>
                                    <p class="cmp-deadline"><i class="fa fa-hourglass-half"></i>
                                        <?= t('آخر أجل') . colon() ?> <b><?= e(format_date($until, 'j F Y')) ?></b>
                                        <?php if ($left > 0): ?><span class="cmp-muted">(<?= t('بقي {time}', ['time' => time_left($left)]) ?>)</span><?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('competitions:track_complete')) ?>" enctype="multipart/form-data" accept-charset="UTF-8" id="cmp-complete" data-pdf-upload
                                      data-max-total="<?= (int) $limits['total'] ?>"
                                      data-msg-big="<?= t('الملف أكبر من الحد المسموح ({mb} ميغابايت).', ['mb' => $limits['file_mb']]) ?>"
                                      data-msg-type="<?= t('يُقبل ملف PDF فقط.') ?>"
                                      data-msg-total="<?= t('مجموع أحجام الوثائق أكبر من {mb} ميغابايت. قلّل حجم الملفات (مسح ضوئي بدقة 150 نقطة يكفي).', ['mb' => $limits['total_mb']]) ?>"
                                      data-msg-none="<?= t('اختر وثيقة واحدة على الأقل (ملف PDF).') ?>">
                                    <?= csrf_field() ?>
                                    <p class="help-block small"><i class="fa fa-info-circle"></i>
                                        <?= t('ملف PDF واحد لكل وثيقة، الحد الأقصى {mb} ميغابايت للملف و{total} ميغابايت للمجموع. امسح الوثائق ضوئياً بوضوح (150 نقطة تكفي). لا تُقبل الملفات المحمية بكلمة مرور.', ['mb' => $limits['file_mb'], 'total' => $limits['total_mb']]) ?></p>
                                    <?php foreach ($requirements as $r): $name = 'doc_' . (int) $r['id']; $sent = $byRequirement[(int) $r['id']] ?? []; ?>
                                        <div class="cmp-doc">
                                            <label for="<?= e($name) ?>"><i class="fa fa-file-pdf-o text-danger"></i> <?= e($r['label']) ?>
                                                <?php if ($sent): ?><small class="cmp-sent"><i class="fa fa-check"></i> <?= t('مرفوعة سابقاً') ?></small><?php endif; ?></label>
                                            <input type="file" name="<?= e($name) ?>" id="<?= e($name) ?>" class="cmp-file" accept="application/pdf,.pdf" data-max="<?= (int) $limits['file'] ?>">
                                            <div class="cmp-file-state" aria-live="polite"></div>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn btn-success btn-lg" data-sending="<?= t('جارٍ الإرسال والتشفير...') ?>"><i class="fa fa-paper-plane"></i> <?= t('إرسال الوثائق') ?></button>
                                </form>
                            </div>
                        </section>
                    <?php else: ?>
                        <div class="alert alert-warning cmp-closed-note"><i class="fa fa-clock-o"></i>
                            <?= t('انتهى أجل إكمال الملف من الموقع ({date}). اتصل بمصلحة المستخدمين بالمديرية.', ['date' => format_date((string) $app['complete_until'], 'j F Y')]) ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <h4 class="cmp-h4"><i class="fa fa-file-text-o"></i> <?= t('معلومات الطلب') ?></h4>
                <table class="cmp-kv">
                    <tr><th><?= t('رقم المتابعة') ?></th><td><span class="cmp-code" style="font-size:18px;display:inline"><?= e($app['tracking_code']) ?></span></td></tr>
                    <tr><th><?= t('المسابقة') ?></th><td><?php if ($c && $c['status'] !== 'draft'): ?><a href="<?= e($c->get_absolute_url()) ?>"><?= e($c['title']) ?></a><?php else: ?><?= e($c['title'] ?? '') ?><?php endif; ?></td></tr>
                    <tr><th><?= t('الرتبة المترشح لها') ?></th><td><?= e($app->post_label()) ?></td></tr>
                    <tr><th><?= t('المترشح') ?></th><td><?php if (is_rtl()): ?><?= e($app->full_name()) ?><?php else: ?><bdi><?= e($app->full_name_latin()) ?></bdi> — <bdi><?= e($app->full_name()) ?></bdi><?php endif; ?></td></tr>
                    <tr><th><?= t('تاريخ الإرسال') ?></th><td><?= e(format_date($app['submitted_at'], 'j F Y - H:i')) ?></td></tr>
                    <?php if (!empty($app['completed_at'])): ?>
                        <tr><th><?= t('آخر إكمال للملف') ?></th><td><?= e(format_date($app['completed_at'], 'j F Y - H:i')) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!$exam && $c && !empty($c['exam_date'])): ?><tr><th><?= t('تاريخ الاختبار أو المقابلة') ?></th><td><?= e(format_date($c['exam_date'], 'j F Y')) ?></td></tr><?php endif; ?>
                    <tr><th><?= t('الوثائق المرفوعة') ?></th><td>
                        <?php foreach ($files as $f): ?>
                            <div><i class="fa fa-file-pdf-o text-danger"></i> <?= e($reqLabels[(int) $f['requirement_id']] ?? $f['label']) ?>
                                <?php if ($f['added_later']): ?><small class="cmp-muted">— <?= t('أُضيفت في {date}', ['date' => format_date($f['uploaded_at'], 'j F Y')]) ?></small><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$files): ?>—<?php endif; ?>
                    </td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="cmp-side-box">
                    <h4><i class="fa fa-lock"></i> <?= t('أمان حسابك') ?></h4>
                    <p class="cmp-muted" style="line-height:1.9"><?= t('لا تشارك رقم المتابعة والرمز السري مع أحد. أغلق هذه الصفحة بعد الاطلاع إن كنت تستعمل جهازاً عاماً (مقهى إنترنت).') ?></p>
                    <p class="cmp-muted small"><i class="fa fa-clock-o"></i> <?= t('تُغلق هذه الصفحة تلقائياً بعد {minutes} دقيقة.', ['minutes' => max(1, (int) ceil($expires_in / 60))]) ?></p>
                    <form method="post" action="<?= e(url('competitions:track')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" name="logout" value="1" class="btn btn-default btn-block"><i class="fa fa-sign-out"></i> <?= t('إغلاق صفحة طلبي') ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="cmp-track-form" autocomplete="off">
            <?= csrf_field() ?>
            <p class="cmp-muted" style="line-height:1.9"><?= t('أدخل رقم المتابعة والرمز السري المكتوبين في وصل الترشح لمعرفة حالة طلبك، وإكمال ملفك إن كان ناقصاً، وطباعة استدعائك ونتيجتك.') ?></p>
            <?php if ($error): ?><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>
            <div class="form-group">
                <label for="tracking_code"><?= t('رقم المتابعة') ?></label>
                <input type="text" name="tracking_code" id="tracking_code" class="form-control" dir="ltr" value="<?= e($code) ?>" placeholder="C26-XXXXXXXX" maxlength="20" required autocapitalize="characters" spellcheck="false">
            </div>
            <div class="form-group">
                <label for="secret"><?= t('الرمز السري') ?></label>
                <input type="password" name="secret" id="secret" class="form-control" dir="ltr" placeholder="XXXXX-XXXXX" maxlength="20" required autocomplete="off" autocapitalize="characters" spellcheck="false">
            </div>
            <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-search"></i> <?= t('عرض حالة الطلب') ?></button>
            <p class="help-block small" style="margin-top:14px;line-height:1.8"><i class="fa fa-question-circle"></i>
                <?= t('أضعت الرمز السري؟ لا يمكن استرجاعه لأسباب أمنية. اتصل بمصلحة المستخدمين بالمديرية مع بطاقة تعريفك.') ?></p>
        </form>
    <?php endif; ?>
</div>
<?php endblock(); ?>

<?php block('extra_js'); ?>
<?php if ($app && $requirements): ?>
<script src="<?= asset('js/competitions.js') ?>"></script>
<?php endif; ?>
<?php endblock(); ?>
