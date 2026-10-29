/* لوحة الإدارة: تحديد الكل، تأكيد الحذف، توليد slug، معاينة الأيقونات */
(function () {
    'use strict';

    // جدول تحويل الحروف العربية (نفس urlify.js في Django)
    var AR = {'أ': 'a', 'ب': 'b', 'ت': 't', 'ث': 'th', 'ج': 'g', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'th', 'ر': 'r', 'ز': 'z',
        'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'th', 'ع': 'aa', 'غ': 'gh', 'ف': 'f', 'ق': 'k', 'ك': 'k',
        'ل': 'l', 'م': 'm', 'ن': 'n', 'ه': 'h', 'و': 'o', 'ي': 'y'};

    function slugify(text) {
        var out = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            out += AR.hasOwnProperty(ch) ? AR[ch] : ch;
        }
        out = out.normalize ? out.normalize('NFKD').replace(/[\u0300-\u036f]/g, '') : out;
        return out.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
    }

    document.querySelectorAll('[data-slug-from]').forEach(function (slug) {
        var src = document.getElementById(slug.getAttribute('data-slug-from'));
        if (!src) { return; }
        var touched = slug.value !== '' || slug.hasAttribute('data-slug-locked');
        slug.addEventListener('input', function () { touched = slug.value !== ''; });
        src.addEventListener('input', function () {
            if (!touched) { slug.value = slugify(src.value).slice(0, 190); }
        });
    });

    // تحديد كل السجلات
    var all = document.querySelector('[data-select-all]');
    var counter = document.querySelector('[data-selected-count]');
    function updateCount() {
        var n = document.querySelectorAll('[data-row-check]:checked').length;
        if (counter) { counter.textContent = n ? ('تم تحديد ' + n + ' سجل') : ''; }
    }
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('[data-row-check]').forEach(function (c) { c.checked = all.checked; });
            updateCount();
        });
    }
    document.querySelectorAll('[data-row-check]').forEach(function (c) { c.addEventListener('change', updateCount); });

    // تأكيد الحذف الجماعي
    var form = document.getElementById('admin-list-form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            var submitter = ev.submitter;
            if (submitter && submitter.name === 'save_list') { return; }
            var action = form.querySelector('select[name="action"]');
            if (action && action.value === 'delete') {
                var n = document.querySelectorAll('[data-row-check]:checked').length;
                if (!confirm('هل أنت متأكد من حذف ' + n + ' سجل؟ لا يمكن التراجع.')) { ev.preventDefault(); }
            }
        });
    }

    // معاينة أيقونات Font Awesome
    document.querySelectorAll('[data-icon-input]').forEach(function (input) {
        var preview = document.querySelector('[data-icon-preview="' + input.id + '"]');
        input.addEventListener('input', function () { if (preview) { preview.className = 'fa ' + input.value.trim(); } });
    });

    // ================================================================ الترجمة التلقائية
    // كل حقل ترجمة يحمل data-tr-of="اسم الحقل العربي" و data-tr-lang="fr|en"
    function trSource(input) {
        var name = input.getAttribute('data-tr-of');
        return document.querySelector('[name="' + name.replace(/(["\\])/g, '\\$1') + '"]');
    }

    var trButton = document.querySelector('[data-auto-translate]');
    var trStatus = document.querySelector('[data-tr-status]');
    if (trButton) {
        trButton.addEventListener('click', function () {
            var targets = Array.prototype.slice.call(document.querySelectorAll('[data-tr-of]'));
            var texts = {};
            var hasOld = false;
            targets.forEach(function (t) {
                var src = trSource(t);
                if (src && src.value.trim() !== '') {
                    texts[t.getAttribute('data-tr-of')] = src.value;
                    if (t.value.trim() !== '') { hasOld = true; }
                }
            });
            if (!Object.keys(texts).length) {
                trStatus.textContent = 'اكتب النص العربي أولاً.';
                return;
            }
            var replace = !hasOld || confirm('بعض الحقول مترجمة مسبقاً. هل تريد استبدال ترجمتها؟\n(إلغاء = ملء الحقول الفارغة فقط)');
            var body = new FormData();
            body.append('csrfmiddlewaretoken', document.querySelector('input[name=csrfmiddlewaretoken]').value);
            body.append('texts', JSON.stringify(texts));
            trButton.disabled = true;
            trStatus.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ الترجمة...';
            fetch(trButton.getAttribute('data-url'), {method: 'POST', body: body, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) { throw new Error(data.error || 'خطأ غير معروف'); }
                    var n = 0;
                    targets.forEach(function (t) {
                        var value = (data[t.getAttribute('data-tr-lang')] || {})[t.getAttribute('data-tr-of')];
                        if (value && (replace || t.value.trim() === '')) {
                            t.value = value;
                            t.classList.add('admin-tr-new');
                            t.classList.remove('admin-tr-stale');
                            n++;
                        }
                    });
                    trStatus.textContent = '✓ تمت الترجمة (' + n + ') عبر ' + data.provider + '. راجعها ثم احفظ.';
                })
                .catch(function (e) { trStatus.textContent = '✗ تعذرت الترجمة: ' + e.message; })
                .then(function () { trButton.disabled = false; });
        });
    }

    // تعديل النص العربي بعد ترجمته: نلوّن حقول الترجمة لتذكيرك بتحديثها
    document.querySelectorAll('[data-tr-of]').forEach(function (t) {
        var src = trSource(t);
        if (!src) { return; }
        src.addEventListener('input', function () {
            if (t.value.trim() !== '') { t.classList.add('admin-tr-stale'); t.title = 'تغيّر النص العربي: اضغط «ترجمة تلقائية» أو عدّل الترجمة'; }
        });
        t.addEventListener('input', function () { t.classList.remove('admin-tr-stale', 'admin-tr-new'); });
    });

    // إخفاء الرسائل بعد 6 ثوانٍ
    setTimeout(function () {
        document.querySelectorAll('.admin-main > .alert-success').forEach(function (a) { a.style.display = 'none'; });
    }, 6000);
})();
