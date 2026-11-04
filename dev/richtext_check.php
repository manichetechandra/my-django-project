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
];
foreach ($attacks as $html) {
    $out = RichText::clean($html);
    $bad = [];
    if (preg_match('/<(script|style|iframe|svg|math|img|form|input|button|object|embed|meta|link|base|textarea|title|xmp|template|video|source|details|marquee|isindex|keygen|noscript|body)\b/i', $out, $m)) {
        $bad[] = 'وسم ' . $m[1];
    }
    if (preg_match('/\son[a-z]+\s*=/i', $out)) {
        $bad[] = 'خاصية on...=';
    }
    if (preg_match('/\s(style|id|name|data-[a-z-]+|title|background|autofocus|xlink:href|action)\s*=/i', $out, $m)) {
        $bad[] = 'خاصية ' . $m[1];
    }
    // بروتوكول كل رابط كما يفهمه المتصفح (بعد فك الكيانات وحذف المسافات وأحرف التحكم)
    preg_match_all('/href="([^"]*)"/i', $out, $hrefs);
    foreach ($hrefs[1] as $href) {
        $h = (string) preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $h, $m) && !in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true)) {
            $bad[] = 'رابط خطير ' . $m[1] . ':';
        }
    }
    if (preg_match('/class="([^"]*)"/', $out, $m) && array_diff(explode(' ', $m[1]), ['text-left', 'text-center', 'text-right', 'text-justify', 'table'])) {
        $bad[] = 'صنف غير مسموح ' . $m[1];
    }
    if (preg_match('/target="_blank"/', $out) && !preg_match('/rel="noopener noreferrer"/', $out)) {
        $bad[] = 'target بلا rel';
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
    ['<div><span style="color:red">نص</span> <font face="x">آخر</font></div>', '<p>نص آخر</p>'],
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

// 5) الترجمة كتلة بكتلة
$mapped = RichText::mapText('<h2>عنوان</h2><p>نص <strong>غامق</strong><br>سطر</p><ul><li>أ<ul><li>ب</li></ul></li></ul>', fn($t) => '[' . $t . ']');
check($mapped === "<h2>[عنوان]</h2><p>[نص غامق<br>سطر]</p><ul><li>[أ]<ul><li>[ب]</li></ul></li></ul>", 'mapText', $mapped);
$mapped = RichText::mapText('<p>x</p>', fn($t) => '<script>alert(1)</script>');
check($mapped === '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', 'mapText: ناتج الترجمة نص (يُهرَّب)', $mapped);

echo $fail ? "✗ $fail فحص فاشل ($pass ناجح)\n" : "✓ منظِّف النصوص المنسّقة: $pass فحصاً ناجحاً\n";
exit($fail ? 1 : 0);
