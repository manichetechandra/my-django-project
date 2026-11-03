/*
 * مسابقات التوظيف: فحص أولي للوثائق في المتصفح (النوع والحجم) قبل رفعها.
 * الفحص الحقيقي (محتوى PDF، الشيفرات المخفية، الحجم) يتم دائماً في الخادم.
 *
 * <form data-pdf-upload data-max-total="15728640" data-msg-big=".." data-msg-type=".." data-msg-total=".." [data-msg-none=".."]>
 *     <input type="file" class="cmp-file" data-max="3145728"> <div class="cmp-file-state"></div>
 *     <button type="submit" data-sending="..">
 */
(function () {
    'use strict';

    function size(b) {
        return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.ceil(b / 1024) + ' KB';
    }

    document.querySelectorAll('form[data-pdf-upload]').forEach(function (form) {
        var maxTotal = parseInt(form.getAttribute('data-max-total'), 10) || 0;
        var msgBig = form.getAttribute('data-msg-big') || '';
        var msgType = form.getAttribute('data-msg-type') || '';
        var msgTotal = form.getAttribute('data-msg-total') || '';
        var msgNone = form.getAttribute('data-msg-none') || '';
        var inputs = form.querySelectorAll('input.cmp-file');

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var state = input.parentNode.querySelector('.cmp-file-state');
                var f = input.files[0];
                input.setCustomValidity('');
                if (state) {
                    state.className = 'cmp-file-state';
                    state.textContent = '';
                }
                if (!f) {
                    return;
                }
                var bad = !/\.pdf$/i.test(f.name) ? msgType : (f.size > parseInt(input.getAttribute('data-max'), 10) ? msgBig : '');
                input.setCustomValidity(bad);
                if (state) {
                    state.className = 'cmp-file-state ' + (bad ? 'is-bad' : 'is-ok');
                    state.textContent = bad ? '✗ ' + bad : '✓ ' + f.name + ' (' + size(f.size) + ')';
                }
            });
        });

        form.addEventListener('submit', function (ev) {
            var total = 0;
            var count = 0;
            inputs.forEach(function (i) {
                if (i.files[0]) {
                    total += i.files[0].size;
                    count++;
                }
            });
            if (msgNone && count === 0) {
                ev.preventDefault();
                alert(msgNone);
                return;
            }
            if (maxTotal && total > maxTotal) {
                ev.preventDefault();
                alert(msgTotal);
                return;
            }
            var btn = form.querySelector('button[type=submit][data-sending]');
            if (btn) {
                // بعد بدء الإرسال: منع الضغط مرتين أثناء رفع الملفات
                setTimeout(function () {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ';
                    btn.appendChild(document.createTextNode(btn.getAttribute('data-sending')));
                }, 0);
            }
        });
    });
})();
