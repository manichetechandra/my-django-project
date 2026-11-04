<?php
/**
 * فحص منظِّف النصوص المنسّقة (php/app/core/RichText.php) بلا قاعدة بيانات ولا خادم:
 *   /tmp/tools/bin/php dev/richtext_check.php
 * 1) التنسيق الذي ينتجه المحرر (TinyMCE) يبقى كما هو  2) هجمات XSS المعروفة لا يبقى منها شيء خطير
 * 3) النص القديم العادي يُعرض كما كان  4) التحويل لنص عادي  5) الترجمة كتلة بكتلة (MyMemory)
 */
define('BASE_PATH', dirname(__DIR__) . '/php');
require BASE_PATH . '/app/core/RichText.php';
// ما يحتاجه RichText من helpers.php (linebreaks + e) دون تحميل الموقع كله
function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function linebreaks($value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
    $out = [];
    foreach (preg_split("/\n{2,}/", $value) as $p) {
        $out[] = '<p>' . str_replace("\n", '<br>', e(trim($p, "\n"))) . '</p>';
    }
    return implode("\n\n", $out);
}

$fail = 0;
$pass = 0;
function check(bool $ok, string $label, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) { $pass++; return; }
    $fail++;
    echo "  ✗ $label" . ($detail !== '' ? "\n      " . $detail : '') . "\n";
}

// 1) مخرجات المحرر العادية تبقى كما هي (بعد التنظيف مرتين: idempotent)
$keep = [
    '<p>نص <strong>غامق</strong> و<i>مائل</i> و<u>تسطير</u> و<s>شطب</s></p>',
    '<h2>عنوان</h2><h3>عنوان فرعي</h3><h4>صغير</h4>',
    '<ul><li>أول</li><li>ثاني<ul><li>متداخل</li></ul></li></ul><ol start="3"><li>ثالث</li></ol>',
    '<p class="text-center">وسط</p><p class="text-justify">ضبط</p><h2 class="text-left">يسار</h2>',
    '<blockquote><p>اقتباس</p></blockquote><hr><p>سطر<br>جديد</p>',
    '<p><a href="https://example.dz/a?b=1&amp;c=2" target="_blank" rel="noopener noreferrer">رابط خارجي</a> و<a href="/news/">داخلي</a> و<a href="mailto:dou@example.dz">بريد</a> و<a href="tel:+213360000">هاتف</a></p>',
    '<figure class="table"><table><thead><tr><th>الإقامة</th><th>السعة</th></tr></thead><tbody><tr><td colspan="2">دباغين</td></tr></tbody></table></figure>',
    '<p>Résidence <strong>El Bez</strong> — 2 000 lits</p>',
    '<p>a &lt; b &amp;&amp; c &gt; d</p>',
    // «كل الأدوات» في TinyMCE: ألوان، خطوط، أحجام، تباعد، إزاحة، محاذاة بـ style، اتجاه
    '<p style="text-align: center;"><span style="color: #e03e2d; background-color: #fbeeb8;">ملوّن</span> <span style="font-family: \'traditional arabic\', serif; font-size: 18pt;">خط</span></p>',
    '<p style="line-height: 1.5; padding-right: 40px;" dir="rtl">إزاحة</p><p dir="ltr" style="text-align: left;">Texte</p>',
    '<h1>عنوان كبير</h1><h5>صغير</h5><h6>أصغر</h6><div style="text-align: justify;">كتلة</div>',
    '<ul style="list-style-type: square;"><li>مربع</li></ul><ol style="list-style-type: lower-roman;" start="2"><li>روماني</li></ol>',
    '<p>H<sub>2</sub>O و x<sup>2</sup> و <code>code</code> و <mark>تظليل</mark> و <abbr title="الديوان الوطني">د.و</abbr></p>',
    // صور: مرفوعة، وبرابط خارجي، مع تعليق ومحاذاة
    '<p><img src="/uploads/editor/2026/11/photo_ab12cd34.jpg" alt="الإقامة" width="300" height="200" loading="lazy"></p>',
    '<p><img style="float: left; margin: 0 10px 10px 0;" src="https://example.dz/a.png" alt="" width="120" height="80" loading="lazy"> نص بجانب الصورة</p>',
    '<p><img style="display: block; margin-left: auto; margin-right: auto;" src="/uploads/editor/b.png" alt="وسط" loading="lazy"></p>',
    '<figure class="image align-center"><img src="/uploads/editor/c.webp" alt="ت" width="400" height="300" loading="lazy"><figcaption>تعليق الصورة</figcaption></figure>',
    // فيديو وخرائط
    '<p><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="314" allowfullscreen="allowfullscreen" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox" referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe></p>',
    '<p><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12" width="600" height="450" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox" referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe></p>',
    '<p><video width="400" height="225" controls="controls"><source src="/uploads/editor/v.mp4" type="video/mp4"></video></p>',
    // جداول بكل خصائصها، أكورديون، مرساة، مثال برمجي
    '<table style="border-collapse: collapse; width: 100%;" border="1"><colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup><tbody><tr><td style="background-color: #ecf0f1; text-align: center; vertical-align: middle;">أ</td><td style="border: 1px solid #ccc;">ب</td></tr></tbody></table>',
    '<details class="mce-accordion" open="open"><summary class="mce-accordion-summary">سؤال؟</summary><p>جواب.</p></details>',
    '<p><a id="section-2"></a>مرساة و<a href="#section-2">رابط إليها</a></p>',
    '<pre class="language-php"><code>&lt;?php echo 1;</code></pre>',
    '<p>😀 &amp; © — <time datetime="2026-11-04">4 نوفمبر</time></p>',
];
foreach ($keep as $html) {
    $out = RichText::clean($html);
    check($out === $html, 'يبقى كما هو: ' . mb_substr($html, 0, 60), 'الناتج: ' . $out);
    check(RichText::clean($out) === $out, 'التنظيف مرتين = مرة واحدة: ' . mb_substr($html, 0, 40));
}

// 2) هجمات XSS: لا يبقى أي عنصر/خاصية/رابط خطير في الناتج
$attacks = [
    '<script>alert(1)</script><p>نص</p>',
    '<p onclick="alert(1)" onmouseover=alert(1)>نص</p>',
    '<img src=x onerror=alert(1)>',
    '<svg onload=alert(1)><circle/></svg>',
    '<svg><script>alert(1)</script></svg>',
    '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>',
    '<iframe src="javascript:alert(1)"></iframe>',
    '<a href="javascript:alert(1)">x</a>',
    '<a href="JaVaScRiPt:alert(1)">x</a>',
    '<a href=" javascript:alert(1)">x</a>',
    '<a href="java&#x09;script:alert(1)">x</a>',
    '<a href="java&#10;script:alert(1)">x</a>',
    '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">x</a>',
    '<a href="javascript&colon;alert(1)">x</a>',
    '<a href="vbscript:msgbox(1)">x</a>',
    '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>',
    '<a href="https://ok.dz" onfocus="alert(1)" autofocus>x</a>',
    '<a href="https://ok.dz" target="_top" rel="opener">x</a>',
    '<p style="background:url(javascript:alert(1))">x</p>',
    '<p style="behavior:url(x.htc)">x</p>',
    '<div style="position:fixed;top:0;left:0;width:100%;height:100%">تراكب</div>',
    '<p class="text-center x-evil ck-hidden">x</p>',
    '<form action="https://evil.example"><input name=a><button>x</button></form>',
    '<object data="x.swf"></object><embed src="x.swf">',
    '<meta http-equiv="refresh" content="0;url=https://evil.example">',
    '<link rel=stylesheet href="https://evil.example/x.css">',
    '<base href="https://evil.example/">',
    '<style>body{display:none}</style>',
    '<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>',
    '<textarea><script>alert(1)</script></textarea>',
    '<title><img src=x onerror=alert(1)></title>',
    '<xmp><img src=x onerror=alert(1)></xmp>',
    '<template><img src=x onerror=alert(1)></template>',
    '<!--<img src=x onerror=alert(1)>-->',
    '<![CDATA[<img src=x onerror=alert(1)>]]>',
    '<p>x</p><!--[if IE]><script>alert(1)</script><![endif]-->',
    '<details open ontoggle=alert(1)>x</details>',
    '<video><source onerror=alert(1)></video>',
    '<body onload=alert(1)><p>x</p>',
    '<p id="x" name="y" data-x="1" title="t" lang="fr" dir="ltr">x</p>',
    '<table background="javascript:alert(1)"><tr><td background="x" onclick="alert(1)">x</td></tr></table>',
    '<a href="https://ok.dz"><img src=x onerror=alert(1)>صورة</a>',
    '<scr<script>ipt>alert(1)</scr</script>ipt>',
    '<<script>script>alert(1)<</script>/script>',
    '<p>"><script>alert(1)</script></p>',
    "<a href=\"\x01javascript:alert(1)\">x</a>",
    '<a href="//evil.example/%0Ajavascript:alert(1)">x</a>',
    '<isindex action=javascript:alert(1) type=submit>',
    '<marquee onstart=alert(1)>x</marquee>',
    '<keygen autofocus onfocus=alert(1)>',
    '<a xlink:href="javascript:alert(1)">x</a>',
    '<p>نص</p><meta charset="iso-8859-1"><p>نص عربي بعده</p>',
    // style: كل ما يمكن أن يحمّل شيئاً أو يغطي الصفحة أو يخرج من القيمة
    '<p style="color:red;background-image:url(https://evil.example/x.png)">x</p>',
    '<p style="color: expression(alert(1))">x</p>',
    '<p style="color:red; position:fixed; z-index:99999; top:0; left:0">x</p>',
    '<span style="font-family:x;}body{display:none">x</span>',
    '<p style="margin-top:-5000px;width:99999px;font-size:900px">x</p>',
    '<p style="color:\\72 ed;background:url(x)">x</p>',
    '<p style="color:red/**/;background:url(x)">x</p>',
    '<p style="--x:url(javascript:alert(1));color:var(--x)">x</p>',
    '<p style="color:red !important;behavior:url(x.htc);-moz-binding:url(x.xml#xss)">x</p>',
    '<p style="background:#fff url(javascript:alert(1))">x</p>',
    '<p style="font-family:\'a\';src:url(x)">x</p>',
    '<p style="width:calc(100% + 9999px);transform:scale(50);content:\'x\';opacity:0;filter:blur(9px)">x</p>',
    '<p style="color:rgb(1,2,3);background-color:rgba(0,0,0,.5)ab">x</p>',
    '<p style="&#99;olor:red;&#98;ackground:url(x)">x</p>',
    // صور
    '<img src="javascript:alert(1)">',
    '<img src="  JaVa&#x0A;ScRiPt:alert(1)">',
    '<img src="data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9YWxlcnQoMSk+">',
    '<img src="https://ok.dz/a.png" srcset="javascript:alert(1) 1x" usemap="#m" ismap onerror="alert(1)" style="position:fixed">',
    '<img src="vbscript:msgbox(1)"><img src="blob:https://ok.dz/1"><img src="file:///etc/passwd">',
    '<img src="https://ok.dz/a.png" width="99999" height="-1">',
    '<figure class="image"><img src="javascript:alert(1)"><figcaption>c</figcaption></figure>',
    // iframe: مواقع الفيديو المسموحة فقط، وsandbox دائماً
    '<iframe src="https://evil.example/embed/x"></iframe>',
    '<iframe src="https://www.youtube.com.evil.example/embed/x"></iframe>',
    '<iframe src="https://evil.example/?u=https://www.youtube.com/embed/x"></iframe>',
    '<iframe src="https://user:pass@www.youtube.com/embed/x"></iframe>',
    '<iframe src="https://www.youtube.com:8443/embed/x"></iframe>',
    '<iframe src="https://www.youtube.com/watch?v=x"></iframe>',
    '<iframe src="https://www.youtube.com/embed/x/../../redirect?q=https://evil.example"></iframe>',
    '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" srcdoc="<script>alert(1)</script>" sandbox="allow-top-navigation allow-forms" allow="camera; microphone; geolocation; autoplay"></iframe>',
    '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>',
    '<iframe src="https://www.youtube.com/embed/x">فيه <script>alert(1)</script> نص</iframe>',
    '<iframe src="  https://www.youtube.com/embed/x\\@evil.example"></iframe>',
    // وسائط
    '<video src="javascript:alert(1)" autoplay onplay="alert(1)"></video>',
    '<video controls poster="javascript:alert(1)" autoplay><source src="https://ok.dz/v.mp4" onerror="alert(1)" type="text/html"></video>',
    '<audio src="data:audio/wav;base64,AAAA" autoplay></audio>',
    '<video><source src="javascript:alert(1)"><img src=x onerror=alert(1)></video>',
    // مراسي وأصناف
    '<a id="javascript:alert(1)">x</a><a id="1abc" name="x">y</a>',
    '<a id="ok" href="javascript:alert(1)">z</a>',
    '<pre class="language-js onerror x-evil">x</pre><figure class="image evil"><img src="/a.png"></figure>',
    '<details class="mce-accordion evil" ontoggle="alert(1)"><summary onclick="alert(1)">s</summary>x</details>',
    '<p dir="rtl onmouseover=alert(1)">x</p><p dir="javascript">y</p>',
];
foreach ($attacks as $html) {
    $out = RichText::clean($html);
    $bad = [];
    if (preg_match('/<(script|style|svg|math|form|input|button|object|embed|meta|link|base|textarea|title|xmp|template|marquee|isindex|keygen|noscript|body|frame|frameset|applet|font|picture|track)\b/i', $out, $m)) {
        $bad[] = 'وسم ' . $m[1];
    }
    if (preg_match('/\son[a-z]+\s*=/i', $out)) {
        $bad[] = 'خاصية on...=';
    }
    if (preg_match('/\s(name|data-[a-z-]+|background|autofocus|xlink:href|action|formaction|srcdoc|srcset|usemap|ismap|autoplay|lang)\s*=/i', $out, $m)) {
        $bad[] = 'خاصية ' . $m[1];
    }
    // بروتوكول كل رابط ومصدر كما يفهمه المتصفح (بعد فك الكيانات وحذف المسافات وأحرف التحكم)
    preg_match_all('/\s(href|src|poster)="([^"]*)"/i', $out, $urls, PREG_SET_ORDER);
    foreach ($urls as [, $attr, $url]) {
        $h = (string) preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $okSchemes = strtolower($attr) === 'href' ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'];
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $h, $m) && !in_array(strtolower($m[1]), $okSchemes, true)) {
            $bad[] = 'رابط خطير ' . $m[1] . ':';
        }
    }
    // كل iframe: من مواقع الفيديو المسموحة، مع sandbox بلا allow-top-navigation ولا allow-forms
    preg_match_all('/<iframe\b([^>]*)>/i', $out, $frames);
    foreach ($frames[1] as $a) {
        if (!preg_match('#\ssrc="https://(www\.youtube\.com|youtube\.com|www\.youtube-nocookie\.com|player\.vimeo\.com|www\.dailymotion\.com|geo\.dailymotion\.com|www\.google\.com)/(embed|video|maps|player)[/?"]#', $a)) {
            $bad[] = 'iframe من موقع غير مسموح';
        }
        if (!preg_match('/\ssandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox"/', $a)) {
            $bad[] = 'iframe بلا sandbox';
        }
        if (preg_match('/allow="[^"]*(camera|microphone|geolocation)/', $a)) {
            $bad[] = 'iframe بصلاحيات كاميرا/ميكروفون/موقع';
        }
    }
    if (preg_match('#<iframe\b[^>]*>[^<]#', $out)) {
        $bad[] = 'نص داخل iframe';
    }
    // كل style: خصائص مسموحة فقط ولا شيء يحمّل أو يغطي الصفحة
    preg_match_all('/\sstyle="([^"]*)"/i', $out, $styles);
    $okProps = ['color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style', 'font-variant', 'text-align',
        'text-decoration', 'text-decoration-line', 'text-transform', 'text-indent', 'line-height', 'letter-spacing', 'word-spacing',
        'white-space', 'direction', 'vertical-align', 'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'padding',
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'width', 'height', 'max-width', 'min-width', 'max-height',
        'min-height', 'float', 'clear', 'display', 'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-width',
        'border-style', 'border-color', 'border-radius', 'border-collapse', 'border-spacing', 'list-style-type'];
    foreach ($styles[1] as $st) {
        $st = html_entity_decode($st, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/url\s*\(|expression|javascript|behavio|binding|@|\\\\|\/\*|[<>{}]|position|z-index|!|var\(|calc\(/i', $st, $m)) {
            $bad[] = 'style خطير ' . $m[0];
        }
        foreach (array_filter(array_map('trim', explode(';', $st))) as $decl) {
            $prop = strtolower(trim(explode(':', $decl, 2)[0]));
            if (!in_array($prop, $okProps, true)) {
                $bad[] = 'خاصية CSS ' . $prop;
            }
            if (preg_match('/-\d/', $decl) && !in_array($prop, ['letter-spacing', 'word-spacing', 'text-indent'], true)) {
                $bad[] = 'قيمة سالبة ' . $decl;
            }
            if (preg_match('/(\d{4,})px/', $decl, $m) && (int) $m[1] > 4000) {
                $bad[] = 'قيمة ضخمة ' . $decl;
            }
        }
    }
    // id: على المراسي فقط وبنمط آمن
    preg_match_all('/<([a-z0-9]+)\b[^>]*\sid="([^"]*)"/i', $out, $ids, PREG_SET_ORDER);
    foreach ($ids as [, $tag, $id]) {
        if (strtolower($tag) !== 'a' || !preg_match('/^[A-Za-z][A-Za-z0-9_.:-]{0,63}$/', $id)) {
            $bad[] = 'id غير مسموح ' . $tag . '#' . $id;
        }
    }
    preg_match_all('/class="([^"]*)"/', $out, $classes);
    foreach ($classes[1] as $cl) {
        foreach (explode(' ', $cl) as $c) {
            if (!in_array($c, ['text-left', 'text-center', 'text-right', 'text-justify', 'table', 'image', 'align-left', 'align-right', 'align-center', 'mce-accordion', 'mce-accordion-summary', 'mce-accordion-body'], true)
                && !preg_match('/^language-[a-z0-9+#-]{1,20}$/', $c)) {
                $bad[] = 'صنف غير مسموح ' . $c;
            }
        }
    }
    if (preg_match('/dir="(?!rtl"|ltr"|auto")/', $out)) {
        $bad[] = 'dir غير مسموح';
    }
    if (preg_match('/target="_blank"/', $out) && !preg_match('/rel="noopener noreferrer"/', $out)) {
        $bad[] = 'target بلا rel';
    }
    if (preg_match('/<(video|audio)\b(?![^>]*controls)/', $out)) {
        $bad[] = 'وسيط بلا أزرار';
    }
    check(!$bad, 'هجوم: ' . mb_substr($html, 0, 70), implode('، ', $bad) . ' ← ' . $out);
    check(RichText::clean($out) === $out, 'الناتج مستقر: ' . mb_substr($html, 0, 50), $out . ' ← ' . RichText::clean($out));
}
// أمثلة دقيقة: ما يبقى بالضبط
$exact = [
    ['<script>alert(1)</script><p>نص</p>', '<p>نص</p>'],
    ['<p onclick="alert(1)">نص</p>', '<p>نص</p>'],
    ['<a href="javascript:alert(1)">اضغط</a>', '<p>اضغط</p>'],
    ['<a href="https://ok.dz" target="_top">x</a>', '<p><a href="https://ok.dz">x</a></p>'],
    ['<a href="https://ok.dz" target="_blank">x</a>', '<p><a href="https://ok.dz" target="_blank" rel="noopener noreferrer">x</a></p>'],
    ['<p class="text-center x-evil">x</p>', '<p class="text-center">x</p>'],
    ['<div><span style="color:red">نص</span> <font face="x">آخر</font></div>', '<div><span style="color: red;">نص</span> آخر</div>'],
    ['<p><span style="position:fixed">بلا تنسيق باقٍ</span></p>', '<p>بلا تنسيق باقٍ</p>'],
    ['<p style="color:red; position:fixed; z-index:9; COLOR:blue; Font-Size : 14PT">x</p>', '<p style="color: red; font-size: 14PT;">x</p>'],
    ['<p style="background:url(x);color:red">x</p>', '<p>x</p>'],
    ['<p style="margin: 0 auto; margin-left: -20px; padding: 5px 10px; width: 120%">x</p>', '<p style="margin: 0 auto; padding: 5px 10px;">x</p>'],
    ['<p style="font-size: 9999px; font-size: 12pt">x</p>', '<p style="font-size: 12pt;">x</p>'],
    ['<span style="color: rgb(224, 62, 45); border: 1px solid rgba(0,0,0,.2)">x</span>', '<p><span style="color: rgb(224, 62, 45); border: 1px solid rgba(0,0,0,.2);">x</span></p>'],
    ['<img src="javascript:alert(1)" alt="x">نص', '<p>نص</p>'],
    ['<img src="https://ok.dz/a.png" onerror="alert(1)" srcset="x 1x" style="position:fixed;float:right" width="99999" height="80">', '<p><img src="https://ok.dz/a.png" style="float: right;" height="80" loading="lazy"></p>'],
    ['<iframe src="https://evil.example/embed/x"></iframe><p>بعده</p>', '<p>بعده</p>'],
    ['<iframe src="//www.youtube.com/embed/abcDEF12345" allow="camera; autoplay; encrypted-media" frameborder="0" sandbox="allow-top-navigation"></iframe>',
        '<p><iframe src="https://www.youtube.com/embed/abcDEF12345" allow="autoplay; encrypted-media" frameborder="0" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox" referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe></p>'],
    ['<iframe src="https://player.vimeo.com/video/123456?h=ab">x</iframe>', '<p><iframe src="https://player.vimeo.com/video/123456?h=ab" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox" referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe></p>'],
    ['<video src="https://ok.dz/v.mp4" autoplay muted><track src="x.vtt"><p>fallback</p></video>', '<p><video src="https://ok.dz/v.mp4" muted="muted" controls="controls"></video></p>'],
    ['<video><source src="javascript:alert(1)"></video><p>x</p>', '<p>x</p>'],
    ['<a id="1abc">بلا رابط</a><a id="a b">ولا</a>', '<p>بلا رابطولا</p>'],
    ['<figure class="image"><img src="javascript:x"><figcaption>تعليق</figcaption></figure>', '<p>تعليق</p>'],
    ['<figure class="image evil align-left"><img src="/a.png" alt=""><figcaption>ت</figcaption></figure>', '<figure class="image align-left"><img src="/a.png" alt="" loading="lazy"><figcaption>ت</figcaption></figure>'],
    ['<pre class="language-javascript onerror"><code>x</code></pre>', '<pre class="language-javascript"><code>x</code></pre>'],
    ['<p><img src="/a.png"></p>', '<p><img src="/a.png" loading="lazy"></p>'],
    ['<img src="/a.png">', '<p><img src="/a.png" loading="lazy"></p>'],
    ['<p>"&gt;&lt;script&gt;alert(1)&lt;/script&gt;</p>', '<p>"&gt;&lt;script&gt;alert(1)&lt;/script&gt;</p>'],
    ['<li>بلا قائمة</li>', '<p>بلا قائمة</p>'],
    ['<td>بلا جدول</td>', '<p>بلا جدول</p>'],
    ['<figure class="image"><p>x</p></figure>', '<p>x</p>'],
    ['<p>&nbsp;</p><p> </p>', ''],
    ['<p><br></p>', ''],
    ['   ', ''],
    ['نص طليق <strong>غامق</strong>', '<p>نص طليق <strong>غامق</strong></p>'],
    ['<ol start="3x"><li>a</li></ol>', '<ol><li>a</li></ol>'],
    ['<table><tr><td colspan="999" rowspan="2">x</td></tr></table>', '<table><tr><td rowspan="2">x</td></tr></table>'],
];
foreach ($exact as [$in, $want]) {
    $out = RichText::clean($in);
    check($out === $want, 'بالضبط: ' . mb_substr($in, 0, 60), "المنتظر: $want\n      الناتج:  $out");
}

// 3) القيم القديمة (نص عادي) والحفظ
check(!RichText::isHtml("سطر أول\nسطر ثانٍ"), 'النص العادي ليس HTML');
check(!RichText::isHtml('a < b <script>'), 'نص يحتوي < ليس HTML (يُهرَّب عند العرض)');
check(RichText::isHtml('<p>x</p>') && RichText::isHtml("\n <h2>x</h2>"), 'HTML المحرر يُعرف');
check(RichText::render("سطر أول\nسطر ثانٍ\n\nفقرة <b>ثانية</b>") === "<p>سطر أول<br>سطر ثانٍ</p>\n\n<p>فقرة &lt;b&gt;ثانية&lt;/b&gt;</p>", 'النص القديم يُعرض كما كان (linebreaks مع التهريب)', RichText::render("سطر أول\nسطر ثانٍ\n\nفقرة <b>ثانية</b>"));
check(RichText::render('<p>x<script>alert(1)</script></p>') === '<p>x</p>', 'العرض ينظّف أيضاً (دفاع مزدوج)', RichText::render('<p>x<script>alert(1)</script></p>'));
check(RichText::forEditor("سطر\n\nفقرة < 3") === '<p>سطر</p><p>فقرة &lt; 3</p>', 'النص القديم يدخل المحرر فقرات', RichText::forEditor("سطر\n\nفقرة < 3"));
check(RichText::normalize("سطر\nثانٍ") === '<p>سطر<br>ثانٍ</p>', 'حفظ نص عادي (بدون JavaScript) => فقرات', RichText::normalize("سطر\nثانٍ"));
check(RichText::normalize('<p>&nbsp;</p>') === '', 'حفظ محرر فارغ => ""');
check(RichText::normalize('<p>x</p><script>alert(1)</script>') === '<p>x</p>', 'الحفظ ينظّف');

// 4) نص عادي للمقتطفات
check(RichText::toText('<h2>عنوان</h2><p>فقرة <strong>أولى</strong>&nbsp;هنا</p><ul><li>أ</li><li>ب</li></ul>') === "عنوان\nفقرة أولى هنا\nأ\nب",
    'toText', json_encode(RichText::toText('<h2>عنوان</h2><p>فقرة <strong>أولى</strong>&nbsp;هنا</p><ul><li>أ</li><li>ب</li></ul>'), JSON_UNESCAPED_UNICODE));
check(RichText::toText('نص قديم <b>') === 'نص قديم <b>', 'toText للنص القديم يبقى كما هو (يُهرَّب عند العرض)');
check(RichText::toText('<p>نص</p><script>alert(1)</script><style>x{}</style>') === 'نص', 'toText: لا يدخل نص script أو style في المقتطف', RichText::toText('<p>نص</p><script>alert(1)</script><style>x{}</style>'));
check(RichText::toText('<p>a &lt;script&gt;</p>') === 'a <script>', 'toText يفك الكيانات (ثم e() عند العرض)');

check(RichText::normalize('<p><img src="/uploads/editor/a.png"></p>') === '<p><img src="/uploads/editor/a.png" loading="lazy"></p>', 'حقل فيه صورة فقط ليس فارغاً');
check(RichText::normalize('<p><iframe src="https://www.youtube.com/embed/abcdef"></iframe></p>') !== '', 'حقل فيه فيديو فقط ليس فارغاً');
check(RichText::toText('<p>قبل <img src="/a.png" alt="صورة"> بعد</p><p><iframe src="https://www.youtube.com/embed/abcdef"></iframe></p>') === 'قبل بعد', 'toText: لا نص من الصور والفيديو', json_encode(RichText::toText('<p>قبل <img src="/a.png" alt="صورة"> بعد</p><p><iframe src="https://www.youtube.com/embed/abcdef"></iframe></p>'), JSON_UNESCAPED_UNICODE));
$schema = RichText::editorSchema();
check(strpos($schema['elements'], 'img[src|alt|title|width|height|style|dir|loading]') !== false
    && strpos($schema['elements'], 'iframe[src|width|height|title|allowfullscreen|frameborder|allow|style|dir|sandbox|referrerpolicy|loading]') !== false
    && !preg_match('/(^|,)(script|style|svg|math|object|embed|form|input|meta|link|base|template)\b/', $schema['elements'])
    && !preg_match('/[\[|](on[a-z]+|srcdoc|srcset|formaction)[\]|]/', $schema['elements']),
    'editorSchema: قائمة المحرر من قائمة الخادم نفسها (بلا وسوم خطيرة ولا on...)', $schema['elements']);
check(strpos($schema['styles'], 'color,background-color,font-family,font-size') === 0 && strpos($schema['styles'], 'position') === false,
    'editorSchema: خصائص CSS للمحرر', $schema['styles']);

// 5) الترجمة كتلة بكتلة
$mapped = RichText::mapText('<h2>عنوان</h2><p>نص <strong>غامق</strong><br>سطر</p><ul><li>أ<ul><li>ب</li></ul></li></ul>', fn($t) => '[' . $t . ']');
check($mapped === "<h2>[عنوان]</h2><p>[نص غامق<br>سطر]</p><ul><li>[أ]<ul><li>[ب]</li></ul></li></ul>", 'mapText', $mapped);
$mapped = RichText::mapText('<p>قبل <img src="/a.png" alt="ص"> بعد</p><figure class="image"><img src="/b.png"><figcaption>تعليق</figcaption></figure>', fn($t) => '[' . $t . ']');
check($mapped === '<p>[قبل]<img src="/a.png" alt="ص" loading="lazy">[بعد]</p><figure class="image"><img src="/b.png" loading="lazy"><figcaption>[تعليق]</figcaption></figure>', 'mapText: الصور تبقى في مكانها والتعليق يُترجم', $mapped);
$mapped = RichText::mapText('<p>x</p>', fn($t) => '<script>alert(1)</script>');
check($mapped === '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', 'mapText: ناتج الترجمة نص (يُهرَّب)', $mapped);

echo $fail ? "✗ $fail فحص فاشل ($pass ناجح)\n" : "✓ منظِّف النصوص المنسّقة: $pass فحصاً ناجحاً\n";
exit($fail ? 1 : 0);
