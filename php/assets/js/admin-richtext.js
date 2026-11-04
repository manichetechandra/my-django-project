/*
 * محرر النصوص المنسّقة في لوحة الإدارة: CKEditor 5 (assets/vendor/ckeditor5) لكل textarea[data-richtext].
 *   data-richtext="ar"  لغة النص: العربية من اليمين لليسار، و fr / en من اليسار لليمين (حقول الترجمة)
 * أدوات المحرر هي ما يقبله الخادم فقط (app/core/RichText.php): عناوين، غامق، مائل، تسطير، شطب، روابط، قوائم،
 * اقتباس، جداول، خط فاصل، ومحاذاة بأصناف Bootstrap (text-center ...) بدل style. وكل HTML يُنظَّف في الخادم مهما كان.
 * إن لم يُحمَّل المحرر (متصفح قديم جداً) يبقى textarea عادياً، والخادم يحوّل النص العادي إلى فقرات.
 */
(function () {
    'use strict';
    var C = window.CKEDITOR;
    var fields = document.querySelectorAll('textarea[data-richtext]');
    if (!fields.length || !C || !C.ClassicEditor) { return; }

    var plugins = [C.Essentials, C.Paragraph, C.Heading, C.Bold, C.Italic, C.Underline, C.Strikethrough,
        C.Link, C.AutoLink, C.List, C.Indent, C.BlockQuote, C.Table, C.TableToolbar, C.HorizontalLine,
        C.Alignment, C.RemoveFormat, C.Autoformat, C.PasteFromOffice];
    var topbar = document.querySelector('.admin-topbar');

    Array.prototype.forEach.call(fields, function (ta) {
        var lang = ta.getAttribute('data-richtext') || 'ar';
        var rows = parseInt(ta.getAttribute('rows'), 10) || 8;
        C.ClassicEditor.create(ta, {
            licenseKey: 'GPL',
            plugins: plugins,
            language: {ui: 'ar', content: lang},
            toolbar: {
                items: ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'link', 'bulletedList', 'numberedList',
                    'outdent', 'indent', '|', 'alignment', 'blockQuote', 'insertTable', 'horizontalLine', '|', 'removeFormat', 'undo', 'redo'],
                shouldNotGroupWhenFull: false
            },
            alignment: {
                options: [
                    {name: 'right', className: 'text-right'}, {name: 'center', className: 'text-center'},
                    {name: 'left', className: 'text-left'}, {name: 'justify', className: 'text-justify'}
                ]
            },
            link: {addTargetToExternalLinks: true, defaultProtocol: 'https://'},
            table: {contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']},
            // شريط أدوات المحرر يلتصق أسفل الشريط العلوي الثابت للوحة الإدارة عند التمرير
            ui: {viewportOffset: {top: topbar ? topbar.offsetHeight : 0}}
        }).then(function (editor) {
            ta.ckeditorInstance = editor;
            // ارتفاع المحرر حسب عدد أسطر الحقل (rows)
            editor.editing.view.change(function (writer) {
                writer.setStyle('min-height', Math.max(4, rows) * 1.7 + 'em', editor.editing.view.document.getRoot());
            });
            // نُبقي textarea محدَّثاً (زر «ترجمة تلقائية» وتنبيه «تغيّر النص العربي» في admin.js يقرآنه)
            editor.model.document.on('change:data', function () {
                editor.updateSourceElement();
                ta.dispatchEvent(new Event('input', {bubbles: true}));
            });
            var label = ta.id && document.querySelector('label[for="' + ta.id + '"]');
            if (label) {
                label.addEventListener('click', function (ev) { ev.preventDefault(); editor.editing.view.focus(); });
            }
            if (ta.closest('.has-error')) { editor.ui.element.classList.add('admin-richtext-error'); }
        }).catch(function (err) {
            ta.style.display = '';
            if (window.console) { console.error('CKEditor:', err); }
        });
    });
})();
