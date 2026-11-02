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

    // إخفاء الرسائل بعد 6 ثوانٍ
    setTimeout(function () {
        document.querySelectorAll('.admin-main > .alert-success').forEach(function (a) { a.style.display = 'none'; });
    }, 6000);
})();
