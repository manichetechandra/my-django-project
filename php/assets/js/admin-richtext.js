/*
 * محرر النصوص المنسّقة في لوحة الإدارة: TinyMCE (assets/vendor/tinymce — نسخة المشروع أو نسختك، الإصدارات 5 إلى 8)
 * لكل textarea[data-richtext]:  data-richtext="ar" نص عربي من اليمين لليسار، و fr / en من اليسار لليمين (حقول الترجمة).
 * الأدوات هي ما يقبله الخادم فقط (app/core/RichText.php): عناوين، غامق، مائل، تسطير، شطب، روابط، قوائم، اقتباس، جداول،
 * خط فاصل، ومحاذاة بأصناف Bootstrap (text-center ...) بدل style — فما تراه في المحرر هو ما سيُحفظ.
 * وكل HTML يُنظَّف في الخادم مهما أرسل المتصفح. إن لم يُحمَّل المحرر يبقى textarea عادياً.
 */
(function () {
    'use strict';
    var me = document.currentScript;
    var T = window.tinymce;
    var fields = document.querySelectorAll('textarea[data-richtext]');
    if (!fields.length || !T || !me) { return; }

    var major = parseInt(T.majorVersion, 10) || 8;
    var lang = me.getAttribute('data-lang');
    var topbar = document.querySelector('.admin-topbar');
    var aligned = 'p,h2,h3,h4,li,td,th';
    // نفس قائمة السماح في الخادم: المحرر نفسه يحذف غيرها (اللصق من Word، أو زر «الكود»)
    var allowed = 'p[class],h2[class],h3[class],h4[class],br,hr,strong/b,em/i,u,s/strike,sub,sup,code,pre,'
        + 'ul,ol[start],li[class],blockquote,a[href|target|rel],table,thead,tbody,tfoot,tr,'
        + 'th[colspan|rowspan|class],td[colspan|rowspan|class]';

    Array.prototype.forEach.call(fields, function (ta) {
        var content = ta.getAttribute('data-richtext') || 'ar';
        var rows = parseInt(ta.getAttribute('rows'), 10) || 8;
        var conf = {
            target: ta,
            base_url: me.getAttribute('data-base'),
            suffix: me.getAttribute('data-suffix'),
            license_key: 'gpl', // TinyMCE 7 و 8 مفتوح المصدر (GPL): بدونه يعطّل TinyMCE 8 المحرر
            directionality: content === 'ar' ? 'rtl' : 'ltr',
            menubar: false,
            branding: false,
            promotion: false,
            elementpath: false,
            help_accessibility: false,
            toolbar_mode: 'wrap',
            toolbar_sticky: true,
            toolbar_sticky_offset: topbar ? topbar.offsetHeight : 0,
            plugins: major < 6 ? 'lists link autolink table code hr' : 'lists link autolink table code',
            toolbar: 'undo redo | ' + (major < 6 ? 'formatselect' : 'blocks') + ' | bold italic underline strikethrough | link'
                + ' | bullist numlist outdent indent | alignright aligncenter alignleft alignjustify | blockquote table hr | removeformat code',
            block_formats: 'فقرة=p; عنوان 1=h2; عنوان 2=h3; عنوان 3=h4',
            valid_elements: allowed,
            valid_classes: 'text-left text-center text-right text-justify',
            formats: {
                alignleft: {selector: aligned, classes: 'text-left'},
                aligncenter: {selector: aligned, classes: 'text-center'},
                alignright: {selector: aligned, classes: 'text-right'},
                alignjustify: {selector: aligned, classes: 'text-justify'},
                underline: {inline: 'u', exact: true},
                strikethrough: {inline: 's', exact: true}
            },
            link_assume_external_targets: 'https',
            link_title: false,
            convert_urls: false, // الروابط الداخلية (/news/...) تبقى كما هي، لا ../news/
            relative_urls: false,
            remove_script_host: false,
            entity_encoding: 'raw',
            paste_data_images: false,
            object_resizing: false,
            table_resize_bars: false,
            table_use_colgroups: false,
            table_sizing_mode: 'responsive',
            table_default_attributes: {},
            table_default_styles: {},
            table_appearance_options: false,
            table_advtab: false,
            table_cell_advtab: false,
            table_row_advtab: false,
            browser_spellcheck: true,
            height: Math.max(220, rows * 28 + 110),
            min_height: 180,
            content_style: 'body{font-family:Tahoma,"Segoe UI",Arial,sans-serif;font-size:15px;line-height:1.8;margin:10px 14px}'
                + 'h2{font-size:22px}h3{font-size:19px}h4{font-size:16px}'
                + '.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.text-justify{text-align:justify}'
                + 'table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:4px 8px}th{background:#f3f6f9}'
                + 'blockquote{margin:0 0 1em;padding:.5em 1em;background:#f8f9fa;border-inline-start:4px solid #8cc040}',
            setup: function (editor) {
                // نُبقي textarea محدَّثاً (زر «ترجمة تلقائية» وتنبيه «تغيّر النص العربي» في admin.js يقرآنه)
                editor.on('input change undo redo ExecCommand', function () {
                    editor.save();
                    ta.dispatchEvent(new Event('input', {bubbles: true}));
                });
                editor.on('init', function () {
                    ta.richEditor = {
                        editor: editor,
                        element: editor.getContainer(),
                        getData: function () { return editor.getContent(); },
                        setData: function (html) { editor.setContent(html); editor.save(); }
                    };
                    var label = ta.id && document.querySelector('label[for="' + ta.id + '"]');
                    if (label) {
                        label.addEventListener('click', function (ev) { ev.preventDefault(); editor.focus(); });
                    }
                    if (ta.closest('.has-error')) { editor.getContainer().classList.add('admin-richtext-error'); }
                });
            }
        };
        if (lang) { conf.language = lang; } // ملف العربية assets/vendor/tinymce/langs/ar.js (وبدونه: الواجهة بالإنجليزية)
        T.init(conf);
    });
})();
