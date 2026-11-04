/*
 * محرر النصوص المنسّقة في لوحة الإدارة: TinyMCE — نسختك أينما وضعتها في مجلد الموقع، وإلا نسخة المشروع
 * (الإصدارات 4 إلى 8؛ البحث في admin_tinymce_locate في app/controllers/admin.php، والسطر أسفل النموذج يبيّن النسخة المستعملة).
 *
 * كل الأدوات: نفعّل كل إضافة معروفة موجودة في مجلد TinyMCE المستعمل (data-plugins من الخادم)، ونبني شريط الأدوات
 * والقوائم منها حسب إصدار TinyMCE: خطوط وأحجام وألوان وتباعد أسطر، صور (رفع ولصق وسحب)، فيديو، جداول، رموز وتعابير،
 * مثال برمجي، أكورديون، مرساة، اتجاه النص، بحث واستبدال، معاينة، ملء الشاشة، طباعة، مساعدة...
 *
 * لكل textarea[data-richtext]:  data-richtext="ar" نص عربي من اليمين لليسار، و fr / en من اليسار لليمين (حقول الترجمة).
 * الوسوم وخصائص CSS المقبولة في المحرر هي قائمة الخادم نفسها (RichText::editorSchema): فما تراه هو ما يُحفظ،
 * وكل HTML يُنظَّف في الخادم مهما أرسل المتصفح. إن لم يُحمَّل المحرر يبقى textarea عادياً.
 */
(function () {
    'use strict';
    var me = document.currentScript;
    var T = window.tinymce;
    var fields = document.querySelectorAll('textarea[data-richtext]');
    if (!fields.length || !T || !me) { return; }

    var major = parseInt(T.majorVersion, 10) || 8;
    var minor = parseFloat(T.minorVersion) || 0;
    var attr = function (name) { return me.getAttribute(name) || ''; };
    var lang = attr('data-lang');
    var langBase = attr('data-lang-base');
    var available = attr('data-plugins').split(/\s+/).filter(Boolean);
    var has = function (p) { return available.indexOf(p) !== -1; };
    var uploadUrl = attr('data-upload-url');
    var csrf = document.querySelector('input[name=csrfmiddlewaretoken]');
    var topbar = document.querySelector('.admin-topbar');

    // الإضافات التي نفعّلها إن وُجدت في المجلد. لا نفعّل: save و autosave (للنموذج أزراره)، pagebreak (لا معنى له في
    // صفحة ويب)، template و importcss (تحتاج إعداداً)، autoresize (الارتفاع ثابت بمقبض)، والإضافات التجارية غير المعروفة.
    var WANT = ['advlist', 'anchor', 'autolink', 'charmap', 'code', 'codesample', 'directionality', 'emoticons', 'fullscreen',
        'help', 'image', 'imagetools', 'insertdatetime', 'link', 'lists', 'media', 'nonbreaking', 'preview', 'quickbars',
        'searchreplace', 'table', 'visualblocks', 'visualchars', 'wordcount', 'accordion',
        // TinyMCE 4 و 5: أدوات صارت في النواة بعد ذلك
        'hr', 'print', 'paste', 'textcolor', 'colorpicker', 'contextmenu', 'textpattern'];
    var plugins = WANT.filter(has);

    // زر => الإضافة التي يحتاجها (غير المذكور في النواة)
    var NEEDS = {link: 'link', anchor: 'anchor', image: 'image', media: 'media', table: 'table', accordion: 'accordion',
        charmap: 'charmap', emoticons: 'emoticons', insertdatetime: 'insertdatetime', nonbreaking: 'nonbreaking',
        codesample: 'codesample', searchreplace: 'searchreplace', visualblocks: 'visualblocks', visualchars: 'visualchars',
        code: 'code', preview: 'preview', fullscreen: 'fullscreen', help: 'help', ltr: 'directionality', rtl: 'directionality'};
    if (major < 6) { NEEDS.hr = 'hr'; NEEDS.print = 'print'; }
    if (major < 5) { NEEDS.forecolor = 'textcolor'; NEEDS.backcolor = 'textcolor'; }
    if (major >= 6) { NEEDS.bullist = 'lists'; NEEDS.numlist = 'lists'; }
    var group = function (items) {
        return items.filter(function (b) { return !NEEDS[b] || has(NEEDS[b]); }).join(' ');
    };
    var toolbar = [
        ['undo', 'redo'],
        major < 6 ? ['formatselect', 'fontselect', 'fontsizeselect'] : ['blocks', 'fontfamily', 'fontsize'],
        ['bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript'],
        ['forecolor', 'backcolor', 'removeformat'],
        ['alignright', 'aligncenter', 'alignleft', 'alignjustify'].concat(major > 5 || (major === 5 && minor >= 5) ? ['lineheight'] : []),
        ['bullist', 'numlist', 'outdent', 'indent'],
        ['rtl', 'ltr'],
        ['link', 'anchor', 'image', 'media', 'table', 'accordion'],
        ['blockquote', 'hr', 'charmap', 'emoticons', 'insertdatetime', 'nonbreaking', 'codesample'],
        ['searchreplace', 'visualblocks', 'visualchars', 'code', 'preview', 'fullscreen', 'print', 'help']
    ].map(group).filter(Boolean).join(' | ');

    // خطوط تعمل على Windows (منها العربية) — يقبلها الخادم (أسماء خطوط فقط)
    var fonts = 'Tahoma=tahoma,arial,helvetica,sans-serif;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times,serif;'
        + 'Traditional Arabic=traditional arabic,serif;Simplified Arabic=simplified arabic,serif;Sakkal Majalla=sakkal majalla,serif;'
        + 'Arabic Typesetting=arabic typesetting,serif;Segoe UI=segoe ui,tahoma,sans-serif;Georgia=georgia,palatino,serif;'
        + 'Verdana=verdana,geneva,sans-serif;Courier New=courier new,courier,monospace';

    // رفع الصور (زر «صورة» ← رفع، أو لصق/سحب صورة): POST إلى /admin/editor/upload/ مع رمز CSRF ← {location: رابط الصورة}
    function send(blobInfo, progress) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-CSRFToken', csrf ? csrf.value : '');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (progress && xhr.upload) {
                xhr.upload.onprogress = function (e) { if (e.lengthComputable) { progress(e.loaded / e.total * 100); } };
            }
            xhr.onload = function () {
                var json = null;
                try { json = JSON.parse(xhr.responseText); } catch (e) { /* ليست JSON: انتهت الجلسة مثلاً */ }
                if (xhr.status === 200 && json && json.location) {
                    resolve(json.location);
                } else {
                    reject({message: (json && json.error) || ('تعذّر رفع الصورة (HTTP ' + xhr.status + ')'), remove: true});
                }
            };
            xhr.onerror = function () { reject({message: 'تعذّر رفع الصورة: لا اتصال بالخادم', remove: true}); };
            var data = new FormData();
            data.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(data);
        });
    }
    // TinyMCE 6 وما بعده: Promise. و 4 و 5: دالتا نجاح وفشل
    var uploadHandler = major >= 6 ? send : function (blobInfo, success, failure, progress) {
        send(blobInfo, progress).then(success, function (err) { failure(err.message, {remove: true}); });
    };

    // تسميات أضافتها إصدارات TinyMCE الحديثة ولا توجد في ملف العربية المجتمعي (تُضاف فقط إن نقصت)
    var EXTRA_AR = {
        'Block {0}': 'نوع الكتلة: {0}', 'Font {0}': 'الخط: {0}', 'Font size {0}': 'حجم الخط: {0}',
        'Text color': 'لون النص', 'Text color {0}': 'لون النص: {0}', 'Text color menu': 'قائمة لون النص',
        'Background color': 'لون الخلفية', 'Background color {0}': 'لون الخلفية: {0}', 'Background color menu': 'قائمة لون الخلفية',
        'Insert date/time menu': 'قائمة إدراج التاريخ/الوقت', 'Line height': 'ارتفاع السطر', 'Accordion': 'أكورديون'
    };
    var code = lang || 'ar';
    // ملف العربية: من مجلد TinyMCE نفسه إن وُجد فيه، وإلا ملف المشروع المناسب لإصداره (assets/js/tinymce-lang/ar-4.js ... ar-8.js)
    var langSrc = lang ? attr('data-base') + '/langs/' + lang + '.js'
        : (langBase ? langBase + 'ar-' + Math.min(8, Math.max(4, major)) + '.js' : '');

    /** يحمّل ملف العربية قبل إنشاء المحررات (فلا يحمّله TinyMCE مرة أخرى)، ويكمل ما ينقصه من EXTRA_AR */
    function withArabic(done) {
        if (!langSrc) { done(false); return; }
        var script = document.createElement('script');
        script.src = langSrc;
        script.onload = function () {
            var I = T.util && T.util.I18n;
            var data = (I && I.getData && I.getData()[code]) || (I && I.data && I.data[code]) || {};
            var add = {};
            // TinyMCE 6 وما بعده يخزّن المفاتيح بحروف صغيرة
            Object.keys(EXTRA_AR).forEach(function (k) { if (!data[k] && !data[k.toLowerCase()]) { add[k] = EXTRA_AR[k]; } });
            T.addI18n(code, add);
            done(true);
        };
        script.onerror = function () { done(false); }; // بلا ملف العربية: الواجهة بالإنجليزية والمحرر يعمل
        document.head.appendChild(script);
    }

    var loaded = 0;
    withArabic(function (arabic) { Array.prototype.forEach.call(fields, function (ta) {
        var content = ta.getAttribute('data-richtext') || 'ar';
        var rows = parseInt(ta.getAttribute('rows'), 10) || 8;
        var conf = {
            target: ta,
            base_url: attr('data-base'),
            suffix: attr('data-suffix'),
            license_key: 'gpl', // TinyMCE 7 و 8 مفتوح المصدر (GPL): بدونه يعطّل TinyMCE 8 المحرر (والإصدارات الأقدم تتجاهله)
            directionality: content === 'ar' ? 'rtl' : 'ltr',
            plugins: plugins.join(' '),
            toolbar: toolbar,
            menubar: major < 5 ? 'file edit insert view format table tools help' : 'file edit view insert format tools table help',
            toolbar_mode: 'wrap',
            toolbar_sticky: true,
            toolbar_sticky_offset: topbar ? topbar.offsetHeight : 0,
            branding: false,
            promotion: false,
            elementpath: false,
            help_accessibility: false,
            // عنوان 1 = h2: الصفحة نفسها فيها h1 (عنوان الخبر)
            block_formats: 'فقرة=p; عنوان 1=h2; عنوان 2=h3; عنوان 3=h4; عنوان 4=h5; عنوان 5=h6; نص منسّق مسبقاً=pre; كتلة=div',
            font_family_formats: fonts, // 6 وما بعده
            font_formats: fonts,        // 4 و 5
            // قائمة الخادم نفسها (RichText::editorSchema): الوسوم وخصائصها، وخصائص CSS المسموحة في style
            valid_elements: attr('data-valid-elements'),
            valid_styles: {'*': attr('data-valid-styles')},
            formats: {
                underline: {inline: 'u', exact: true},
                strikethrough: {inline: 's', exact: true}
            },
            link_assume_external_targets: 'https',
            convert_urls: false, // الروابط الداخلية (/news/...) والصور المرفوعة تبقى كما هي، لا ../news/
            relative_urls: false,
            remove_script_host: false,
            entity_encoding: 'raw',
            // الصور: رفع إلى uploads/editor/ (وكذلك الملصوقة أو المسحوبة)، تعليق تحت الصورة، أبعاد وهوامش
            image_caption: true,
            image_advtab: true,
            image_title: true,
            image_uploadtab: true,
            images_file_types: 'jpeg,jpg,png,gif,webp',
            images_upload_handler: uploadHandler,
            automatic_uploads: true,
            paste_data_images: true,
            images_reuse_filename: false,
            // فيديو: YouTube و Vimeo و Dailymotion وخرائط Google (يقبلها الخادم مع sandbox)، أو ملف mp4 برابط
            media_alt_source: false,
            media_poster: false,
            media_live_embeds: true,
            table_default_attributes: {border: '1'},
            table_default_styles: {'border-collapse': 'collapse', width: '100%'},
            quickbars_insert_toolbar: false,
            browser_spellcheck: true,
            height: Math.max(300, rows * 28 + 170),
            min_height: 220,
            content_style: 'body{font-family:Tahoma,"Segoe UI",Arial,sans-serif;font-size:15px;line-height:1.8;margin:10px 14px}'
                + 'h2{font-size:22px}h3{font-size:19px}h4{font-size:16px}img{max-width:100%;height:auto}'
                + '.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.text-justify{text-align:justify}'
                + 'table{border-collapse:collapse}th,td{border:1px solid #ccc;padding:4px 8px}th{background:#f3f6f9}'
                + 'blockquote{margin:0 0 1em;padding:.5em 1em;background:#f8f9fa;border-inline-start:4px solid #8cc040}'
                + 'pre{direction:ltr;text-align:left;background:#f6f8fa;padding:.6em .9em}',
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
                    // سطر «محرر النصوص» أسفل النموذج: عدد الإضافات المفعّلة فعلاً
                    loaded = Math.max(loaded, Object.keys(editor.plugins || {}).length);
                    Array.prototype.forEach.call(document.querySelectorAll('[data-tinymce-plugins]'), function (el) {
                        el.textContent = loaded;
                    });
                });
            }
        };
        if (major < 5) { conf.plugins = plugins; } // TinyMCE 4 يقبل قائمة
        if (arabic) { conf.language = code; } // ملف العربية محمَّل مسبقاً (withArabic)
        T.init(conf);
    }); });

    // سطر «محرر النصوص: TinyMCE x.y — ...» أسفل النموذج: الإصدار الفعلي المحمَّل
    Array.prototype.forEach.call(document.querySelectorAll('[data-tinymce-version]'), function (el) {
        el.textContent = T.majorVersion + '.' + T.minorVersion;
    });
})();
