<?php
/**
 * النصوص المنسّقة (محرر TinyMCE في لوحة الإدارة): تنظيف HTML بقائمة سماح، وعرضه، وتحويله لنص عادي.
 *
 *   RichText::normalize($_POST['content'])  عند الحفظ: HTML منظَّف ('' إن كان فارغاً)، والنص العادي يصير فقرات <p>
 *   RichText::render($news['content'])      في الصفحات: HTML منظَّف — والنص القديم العادي يُعرض كما كان (linebreaks)
 *   RichText::toText($cite['description'])  للمقتطفات والبحث: نص عادي بلا وسوم
 *   RichText::editorSchema()                القائمة نفسها لمحرر TinyMCE (valid_elements و valid_styles)
 *
 * الأمان: كل HTML يُنظَّف عند الحفظ وعند العرض (دفاع مزدوج) — حتى لو وصل HTML خطير إلى قاعدة البيانات
 * (من الترجمة الآلية أو استيراد أو تعديل مباشر) لا يصل إلى المتصفح:
 *   - الوسوم المسموحة فقط (TAGS)، والخطيرة تُحذف مع محتواها (DROP)، وغير المعروفة يبقى نصها فقط
 *   - الخصائص المسموحة لكل وسم فقط: لا on...= ولا srcdoc ولا srcset ولا data-...؛ id على المراسي <a> فقط
 *   - style: خصائص CSS المسموحة فقط (STYLES) وكل قيمة تُفحص بنمطها (ألوان، أطوال بحدود، كلمات محددة):
 *     لا url() ولا expression ولا position ولا هوامش سالبة ولا أحجام ضخمة — فلا تغطي الصفحة ولا تحمّل شيئاً
 *   - الروابط: http(s) و mailto و tel والداخلية فقط، و target="_blank" مع rel="noopener noreferrer"
 *   - الصور والفيديو والصوت: http(s) أو مسار في الموقع فقط (لا data: ولا javascript:)
 *   - iframe: فيديو YouTube و Vimeo و Dailymotion وخرائط Google فقط، مع sandbox دائماً
 *   - لا svg ولا نماذج ولا object/embed ولا سكربت — وكل نص يُهرَّب عند الإخراج
 * القيم القديمة نص عادي بلا وسوم: لا تحتاج قاعدة البيانات أي تحويل.
 */
final class RichText
{
    /** الوسوم المسموحة => خصائصها المسموحة (إضافة إلى COMMON) */
    private const TAGS = [
        'p' => ['class'], 'div' => ['class'],
        'h1' => ['class'], 'h2' => ['class'], 'h3' => ['class'], 'h4' => ['class'], 'h5' => ['class'], 'h6' => ['class'],
        'br' => [], 'hr' => [],
        'span' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'strike' => [], 'del' => [],
        'ins' => [], 'sub' => [], 'sup' => [], 'code' => [], 'kbd' => [], 'samp' => [], 'var' => [], 'small' => [],
        'mark' => [], 'q' => [], 'cite' => [], 'dfn' => [], 'abbr' => ['title'], 'time' => ['datetime'], 'bdi' => [], 'bdo' => [],
        'ul' => ['type'], 'ol' => ['start', 'type', 'reversed'], 'li' => ['class', 'value'],
        'blockquote' => [], 'pre' => ['class'],
        'a' => ['href', 'target', 'title', 'id'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => ['class'], 'figcaption' => [],
        'iframe' => ['src', 'width', 'height', 'title', 'allowfullscreen', 'frameborder', 'allow'],
        'video' => ['src', 'poster', 'width', 'height', 'controls', 'loop', 'muted', 'playsinline', 'preload'],
        'audio' => ['src', 'controls', 'loop', 'preload'],
        'source' => ['src', 'type'],
        'table' => ['border', 'cellpadding', 'cellspacing', 'width', 'height'],
        'caption' => [], 'colgroup' => ['span', 'width'], 'col' => ['span', 'width'],
        'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => ['height'],
        'th' => ['colspan', 'rowspan', 'class', 'scope', 'width', 'height'],
        'td' => ['colspan', 'rowspan', 'class', 'width', 'height'],
        'details' => ['open', 'class'], 'summary' => ['class'],
    ];

    /** خصائص مسموحة لكل الوسوم ما عدا NO_COMMON */
    private const COMMON = ['style', 'dir'];
    private const NO_COMMON = ['br', 'source'];

    /** خصائص يضعها المنظِّف نفسه (يُبقيها المحرر أيضاً) */
    private const FORCED = ['a' => ['rel'], 'img' => ['loading'], 'iframe' => ['sandbox', 'referrerpolicy', 'loading']];

    /** وسوم تُحذف مع كل ما بداخلها */
    private const DROP = [
        'script', 'style', 'frame', 'frameset', 'object', 'embed', 'applet', 'param', 'noscript', 'noembed',
        'noframes', 'template', 'svg', 'math', 'form', 'input', 'button', 'select', 'option', 'optgroup', 'datalist',
        'textarea', 'label', 'output', 'link', 'meta', 'base', 'title', 'head', 'image', 'picture', 'track',
        'canvas', 'map', 'area', 'xmp', 'plaintext', 'listing', 'dialog', 'portal', 'slot',
    ];

    /** عناصر الكتل (ما سواها نص داخل فقرة) */
    private const BLOCKS = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'pre', 'figure',
        'figcaption', 'table', 'caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'hr',
        'details', 'summary'];

    /** صور ووسائط: محتوى وإن لم يكن فيها نص (فقرة فيها صورة فقط ليست فارغة) */
    private const MEDIA = ['img', 'iframe', 'video', 'audio'];

    /** عناصر لا تصح إلا داخل عنصر معيّن (وإلا يبقى محتواها فقط) */
    private const PARENTS = [
        'li' => ['ul', 'ol'], 'tr' => ['table', 'thead', 'tbody', 'tfoot'], 'td' => ['tr'], 'th' => ['tr'],
        'thead' => ['table'], 'tbody' => ['table'], 'tfoot' => ['table'], 'caption' => ['table'], 'colgroup' => ['table'],
        'col' => ['colgroup', 'table'], 'figcaption' => ['figure'], 'summary' => ['details'], 'source' => ['video', 'audio'],
    ];

    /** المحاذاة القديمة بالأصناف (المحتوى المكتوب قبل «كل الأدوات») */
    private const ALIGN = ['text-left', 'text-center', 'text-right', 'text-justify'];

    /** أصناف أخرى يحتاجها المحرر: صورة بتعليق ومحاذاتها، الأكورديون */
    private const CLASSES = [
        'figure' => ['table', 'image', 'align-left', 'align-right', 'align-center'],
        'details' => ['mce-accordion'], 'summary' => ['mce-accordion-summary'], 'div' => ['mce-accordion-body'],
    ];

    /**
     * خصائص CSS المسموحة في style => نوع قيمتها (أو نمط regex). كل ما سواها يُحذف:
     * لا position ولا background-image ولا url() ولا z-index ولا transform ولا content...
     */
    private const STYLES = [
        'color' => 'color', 'background-color' => 'color',
        'font-family' => 'font', 'font-size' => 'size', 'font-weight' => 'weight', 'font-style' => '/^(normal|italic|oblique)$/',
        'font-variant' => '/^(normal|small-caps)$/',
        'text-align' => '/^(left|right|center|justify|start|end)$/', 'text-decoration' => 'decoration',
        'text-decoration-line' => 'decoration', 'text-transform' => '/^(none|uppercase|lowercase|capitalize)$/',
        'text-indent' => 'slen', 'line-height' => 'lineheight', 'letter-spacing' => 'slen', 'word-spacing' => 'slen',
        'white-space' => '/^(normal|nowrap|pre|pre-wrap|pre-line)$/', 'direction' => '/^(rtl|ltr)$/',
        'vertical-align' => '/^(baseline|sub|super|top|text-top|middle|bottom|text-bottom)$/',
        'margin' => 'box', 'margin-top' => 'lenauto', 'margin-right' => 'lenauto', 'margin-bottom' => 'lenauto', 'margin-left' => 'lenauto',
        'padding' => 'box', 'padding-top' => 'len', 'padding-right' => 'len', 'padding-bottom' => 'len', 'padding-left' => 'len',
        'width' => 'dim', 'height' => 'dim', 'max-width' => 'dim', 'min-width' => 'dim', 'max-height' => 'dim', 'min-height' => 'dim',
        'float' => '/^(left|right|none)$/', 'clear' => '/^(left|right|both|none)$/', 'display' => '/^(block|inline|inline-block|table)$/',
        'border' => 'border', 'border-top' => 'border', 'border-right' => 'border', 'border-bottom' => 'border', 'border-left' => 'border',
        'border-width' => 'widths', 'border-style' => 'bstyles', 'border-color' => 'colors', 'border-radius' => 'box',
        'border-collapse' => '/^(collapse|separate)$/', 'border-spacing' => 'box',
        'list-style-type' => '/^(none|disc|circle|square|decimal|decimal-leading-zero|lower-alpha|upper-alpha|lower-latin|upper-latin|lower-roman|upper-roman|lower-greek|arabic-indic)$/',
    ];

    /** أكبر قيمة لكل وحدة (لا عنصر بعرض 99999px يغطي الصفحة) */
    private const CAPS = ['px' => 4000, 'pt' => 3000, 'em' => 200, 'rem' => 200, '%' => 100, 'cm' => 100, 'mm' => 1000,
        'in' => 40, 'ex' => 400, 'ch' => 400, 'vw' => 100, 'vh' => 100];

    private const BORDER_STYLES = ['none', 'hidden', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'];

    /** iframe: المواقع المسموحة => نمط المسار (فيديو وخرائط فقط) */
    private const EMBEDS = [
        'www.youtube.com' => '#^/embed/[A-Za-z0-9_-]{2,40}$#',
        'youtube.com' => '#^/embed/[A-Za-z0-9_-]{2,40}$#',
        'www.youtube-nocookie.com' => '#^/embed/[A-Za-z0-9_-]{2,40}$#',
        'player.vimeo.com' => '#^/video/\d{1,15}$#',
        'www.dailymotion.com' => '#^/embed/video/[A-Za-z0-9]{2,30}$#',
        'geo.dailymotion.com' => '#^/player(/[A-Za-z0-9]{1,30})?\.html$#',
        'www.google.com' => '#^/maps/embed(/v1/[a-z]{1,20})?$#',
    ];

    /** صلاحيات iframe المقبولة في allow (فيديو) — لا كاميرا ولا ميكروفون ولا موقع */
    private const IFRAME_ALLOW = ['accelerometer', 'autoplay', 'clipboard-write', 'encrypted-media', 'gyroscope',
        'picture-in-picture', 'web-share', 'fullscreen'];

    /** sandbox لكل iframe: يعمل الفيديو، لكن لا يغيّر صفحتنا ولا يرسل نماذج */
    private const SANDBOX = 'allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox';

    /** أقصى حجم نقبله (MEDIUMTEXT يتسع لأكثر، لكن لا يوجد نص إداري بهذا الطول) */
    private const MAX_BYTES = 1048576;

    // ================================================================== الواجهة

    /** هل القيمة HTML من المحرر (تبدأ بعنصر كتلة) أم نص عادي قديم؟ */
    public static function isHtml($value): bool
    {
        return is_string($value) && preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|pre|figure|table|hr|div|details)[\s>\/]/i', $value) === 1;
    }

    /** قيمة حقل منسّق قبل حفظها: HTML منظَّف، أو '' إن كان فارغاً. النص العادي (بلا وسوم) يصير فقرات */
    public static function normalize($value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim((string) $value));
        if ($value === '') {
            return '';
        }
        if (!preg_match('/<\/?[a-z][a-z0-9]*[\s>\/]/i', $value)) {
            $value = self::fromPlain($value);
        }
        return self::clean($value);
    }

    /** للعرض في الصفحات: HTML منظَّف — والنص القديم العادي فقرات وأسطر مع التهريب (linebreaks) */
    public static function render($value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }
        return self::isHtml($value) ? self::clean($value) : linebreaks($value);
    }

    /** للمحرر في لوحة الإدارة: النص القديم العادي يتحول إلى فقرات حتى تبقى أسطره كما هي */
    public static function forEditor($value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }
        return self::isHtml($value) ? self::clean($value) : self::fromPlain($value);
    }

    /** نص عادي بلا وسوم (للمقتطفات والبحث والقوائم) */
    public static function toText($value): string
    {
        $value = (string) $value;
        if (!self::isHtml($value)) {
            return $value;
        }
        if (!self::hasDom()) {
            return self::stripTags($value);
        }
        [, $body] = self::load(self::clean($value)); // بعد التنظيف: لا يدخل نص <script> أو <style> في المقتطف
        $text = self::text($body, true);
        return trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', (string) preg_replace('/\s*\n\s*/u', "\n", $text)));
    }

    /** نص عادي (فقرات تفصلها أسطر فارغة، وأسطر <br>) => HTML بفقرات <p> مع التهريب */
    public static function fromPlain(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }
        $out = '';
        foreach (preg_split("/\n\s*\n/", $text) as $para) {
            $para = trim($para, "\n");
            if (trim($para) !== '') {
                $out .= '<p>' . str_replace("\n", '<br>', htmlspecialchars($para, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
            }
        }
        return $out;
    }

    /** تنظيف HTML بقائمة السماح. يرجع '' إن لم يبقَ نص (مثل <p>&nbsp;</p>) */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        if (strlen($html) > self::MAX_BYTES) {
            $html = mb_strcut($html, 0, self::MAX_BYTES, 'UTF-8');
        }
        if (!self::hasDom()) {
            return self::fromPlain(self::stripTags($html)); // بلا إضافة dom: لا نخرج أي HTML، النص فقط (مهرَّباً)
        }
        [$doc, $body] = self::load($html);
        self::cleanChildren($body);
        self::wrapInline($doc, $body);
        if (self::isBlank($body)) {
            return '';
        }
        return self::save($doc, $body);
    }

    /**
     * تطبيق دالة على نص كل فقرة (للترجمة التي لا تفهم HTML مثل MyMemory): يبقى الهيكل (عناوين، قوائم، جداول)
     * ويُستبدل نص كل كتلة بنتيجة $fn(نص الكتلة). التنسيق داخل الفقرة (غامق، رابط، لون) لا يبقى، والصور والفيديو تبقى في مكانها.
     */
    public static function mapText(string $html, callable $fn): string
    {
        if (!self::hasDom()) {
            return self::fromPlain((string) $fn(self::stripTags($html)));
        }
        [$doc, $body] = self::load(self::clean($html));
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $el) {
            if (in_array(strtolower($el->nodeName), ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'th', 'td', 'blockquote', 'pre', 'figcaption', 'summary', 'caption'], true)) {
                self::mapRuns($doc, $el, $fn);
            }
        }
        return self::clean(self::save($doc, $body));
    }

    /**
     * قائمة السماح نفسها لمحرر TinyMCE: ما يقبله المحرر هو ما يحفظه الخادم.
     * @return array{elements: string, styles: string}
     */
    public static function editorSchema(): array
    {
        $elements = [];
        foreach (self::TAGS as $tag => $attrs) {
            $all = array_values(array_unique(array_merge($attrs, in_array($tag, self::NO_COMMON, true) ? [] : self::COMMON, self::FORCED[$tag] ?? [])));
            $elements[] = $tag . ($all ? '[' . implode('|', $all) . ']' : '');
        }
        return ['elements' => implode(',', $elements), 'styles' => implode(',', array_keys(self::STYLES))];
    }

    // ================================================================== التفاصيل

    /** إضافة dom (مفعّلة افتراضياً في XAMPP وأغلب الاستضافات) */
    private static function hasDom(): bool
    {
        return class_exists('DOMDocument', false);
    }

    /** احتياط بلا dom: النص فقط، الكتل و<br> أسطر (الناتج يُهرَّب دائماً بعده) */
    private static function stripTags(string $html): string
    {
        $html = (string) preg_replace('#<(script|style|template|svg|math|noscript|textarea|title|xmp|iframe)\b.*?</\1\s*>#is', '', $html);
        $html = (string) preg_replace('#<(br|/p|/h[1-6]|/li|/tr|/blockquote|hr)\b[^>]*>#i', "\n", $html);
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @return array{0: DOMDocument, 1: DOMElement} */
    private static function load(string $html): array
    {
        // كل حرف غير ASCII يصير &#...; فلا يتأثر التحليل بأي ترميز يُذكر داخل النص نفسه
        $ascii = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $ascii . '</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $doc->encoding = 'UTF-8';
        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            $body = $doc->createElement('body');
            $doc->appendChild($body);
        }
        return [$doc, $body];
    }

    private static function save(DOMDocument $doc, DOMElement $body): string
    {
        $out = '';
        foreach ($body->childNodes as $n) {
            $out .= $doc->saveHTML($n);
        }
        // libxml يعرف HTML4 فقط: <source> عنصر فارغ في HTML5 بلا وسم إغلاق (النص "</source>" نفسه يُهرَّب دائماً)
        return trim(str_replace('</source>', '', $out));
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        $child = $parent->firstChild;
        while ($child !== null) {
            $next = $child->nextSibling;
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);
                if (in_array($tag, self::DROP, true)) {
                    $parent->removeChild($child);
                } else {
                    if ($tag === 'iframe') {
                        while ($child->firstChild !== null) { // نص داخل iframe لا يُعرض أبداً
                            $child->removeChild($child->firstChild);
                        }
                    }
                    self::cleanChildren($child);
                    $parentTag = strtolower($parent->nodeName);
                    $misplaced = isset(self::PARENTS[$tag]) && !in_array($parentTag, self::PARENTS[$tag], true);
                    $keep = isset(self::TAGS[$tag]) && !$misplaced ? self::cleanAttributes($child, $tag) : false;
                    if ($keep === null) {
                        $parent->removeChild($child); // صورة/فيديو/iframe بلا مصدر مقبول: يُحذف كله
                    } elseif ($keep === false) {
                        self::unwrap($parent, $child); // عنصر غير مسموح: يبقى محتواه (المنظَّف) فقط
                    }
                }
            } elseif (!($child instanceof DOMText) || $child instanceof DOMCdataSection) {
                $parent->removeChild($child); // تعليقات <!-- --> و CDATA وتعليمات المعالجة
            }
            $child = $next;
        }
    }

    /** يحذف الوسم ويُبقي محتواه مكانه؛ وما صار في غير مكانه بعد ذلك (تعليق صورة بلا figure) يُعامل مثله */
    private static function unwrap(DOMNode $parent, DOMElement $child): void
    {
        $moved = [];
        while ($child->firstChild !== null) {
            $moved[] = $child->firstChild;
            $parent->insertBefore($child->firstChild, $child);
        }
        $parent->removeChild($child);
        $parentTag = strtolower($parent->nodeName);
        foreach ($moved as $n) {
            if ($n instanceof DOMElement && isset(self::PARENTS[strtolower($n->nodeName)])
                && !in_array($parentTag, self::PARENTS[strtolower($n->nodeName)], true)) {
                self::unwrap($parent, $n);
            }
        }
    }

    /**
     * يحذف الخصائص غير المسموحة ويتحقق من قيم المسموحة.
     * يرجع true (يبقى)، أو false (يُحذف الوسم ويبقى محتواه)، أو null (يُحذف بمحتواه: صورة بلا مصدر صالح...)
     */
    private static function cleanAttributes(DOMElement $el, string $tag): ?bool
    {
        $allowed = in_array($tag, self::NO_COMMON, true) ? self::TAGS[$tag] : array_merge(self::TAGS[$tag], self::COMMON);
        $attrs = [];
        foreach ($el->attributes as $a) {
            $attrs[] = $a;
        }
        foreach ($attrs as $a) {
            $name = strtolower($a->nodeName);
            $value = (string) $a->nodeValue;
            $el->removeAttributeNode($a);
            if (!in_array($name, $allowed, true) || $el->hasAttribute($name)) {
                continue;
            }
            $v = trim($value);
            $set = null;
            switch ($name) {
                case 'style':
                    $set = self::safeStyle($value);
                    break;
                case 'dir':
                    $set = in_array(strtolower($v), ['rtl', 'ltr', 'auto'], true) ? strtolower($v) : null;
                    break;
                case 'class':
                    $ok = self::CLASSES[$tag] ?? [];
                    if (in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th'], true)) {
                        $ok = array_merge($ok, self::ALIGN);
                    }
                    $keep = [];
                    foreach (preg_split('/\s+/', $v) ?: [] as $c) {
                        if (in_array($c, $ok, true) || ($tag === 'pre' && preg_match('/^language-[a-z0-9+#-]{1,20}$/', $c))) {
                            $keep[$c] = $c;
                        }
                    }
                    $set = $keep ? implode(' ', $keep) : null;
                    break;
                case 'colspan':
                case 'rowspan':
                case 'span':
                    $set = preg_match('/^([1-9]\d?)$/', $v, $m) && (int) $m[1] <= 50 ? $m[1] : null;
                    break;
                case 'start':
                case 'value':
                    $set = preg_match('/^(-?\d{1,5})$/', $v, $m) ? $m[1] : null;
                    break;
                case 'type':
                    $set = in_array($v, $tag === 'ol' ? ['1', 'a', 'A', 'i', 'I'] : ($tag === 'ul' ? ['disc', 'circle', 'square'] : []), true) ? $v
                        : ($tag === 'source' && preg_match('#^(video|audio)/[a-z0-9.+-]{1,30}$#', strtolower($v)) ? strtolower($v) : null);
                    break;
                case 'reversed':
                case 'open':
                case 'controls':
                case 'loop':
                case 'muted':
                case 'playsinline':
                case 'allowfullscreen':
                    $set = $name;
                    break;
                case 'href':
                    $set = self::safeHref($value);
                    break;
                case 'target':
                    $set = $v === '_blank' ? '_blank' : null;
                    break;
                case 'title':
                case 'alt':
                    $set = $v !== '' || $name === 'alt' ? mb_substr($v, 0, 300) : null;
                    break;
                case 'id':
                    $set = preg_match('/^[A-Za-z][A-Za-z0-9_.:-]{0,63}$/', $v) ? $v : null;
                    break;
                case 'src':
                    $set = $tag === 'iframe' ? self::safeEmbed($value) : self::safeSrc($value);
                    break;
                case 'poster':
                    $set = self::safeSrc($value);
                    break;
                case 'width':
                case 'height':
                    $set = preg_match('/^(\d{1,4})(px|%)?$/', $v, $m) && (int) $m[1] > 0 && (($m[2] ?? '') !== '%' || (int) $m[1] <= 100)
                        ? $m[1] . (($m[2] ?? '') === '%' ? '%' : '') : null;
                    break;
                case 'border':
                case 'cellpadding':
                case 'cellspacing':
                    $set = preg_match('/^(\d{1,2})$/', $v, $m) && (int) $m[1] <= 20 ? $m[1] : null;
                    break;
                case 'frameborder':
                    $set = in_array($v, ['0', '1'], true) ? $v : null;
                    break;
                case 'allow':
                    $keep = [];
                    foreach (explode(';', strtolower($v)) as $feature) {
                        $f = strtok(trim($feature), " \t");
                        if ($f !== false && in_array($f, self::IFRAME_ALLOW, true)) {
                            $keep[$f] = $f;
                        }
                    }
                    $set = $keep ? implode('; ', $keep) : null;
                    break;
                case 'datetime':
                    $set = preg_match('/^[0-9T:.+\-Z ]{4,35}$/', $v) ? $v : null;
                    break;
                case 'scope':
                    $set = in_array($v, ['row', 'col', 'rowgroup', 'colgroup'], true) ? $v : null;
                    break;
                case 'preload':
                    $set = in_array($v, ['none', 'metadata', 'auto'], true) ? $v : null;
                    break;
            }
            if ($set !== null && ($set !== '' || $name === 'alt')) { // alt="" مقبول: صورة زخرفية
                $el->setAttribute($name, $set);
            }
        }
        switch ($tag) {
            case 'a':
                if (!$el->hasAttribute('href') && !$el->hasAttribute('id')) {
                    return false;
                }
                if ($el->hasAttribute('target')) {
                    $el->setAttribute('rel', 'noopener noreferrer');
                }
                break;
            case 'img':
                if (!$el->hasAttribute('src')) {
                    return null;
                }
                $el->setAttribute('loading', 'lazy');
                break;
            case 'iframe':
                if (!$el->hasAttribute('src')) {
                    return null;
                }
                $el->setAttribute('sandbox', self::SANDBOX);
                $el->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                $el->setAttribute('loading', 'lazy');
                break;
            case 'video':
            case 'audio':
                foreach (iterator_to_array($el->childNodes) as $c) { // داخل الوسيط: مصادر <source> فقط
                    if (!($c instanceof DOMElement) || strtolower($c->nodeName) !== 'source') {
                        $el->removeChild($c);
                    }
                }
                if (!$el->hasAttribute('src') && $el->getElementsByTagName('source')->length === 0) {
                    return null;
                }
                $el->setAttribute('controls', 'controls'); // لا وسيط مخفي بلا أزرار إيقاف
                break;
            case 'source':
                if (!$el->hasAttribute('src')) {
                    return null;
                }
                break;
            case 'figure':
                $classes = explode(' ', $el->getAttribute('class'));
                $ok = (in_array('table', $classes, true) && $el->getElementsByTagName('table')->length > 0)
                    || (in_array('image', $classes, true) && $el->getElementsByTagName('img')->length > 0);
                if (!$ok) {
                    return false;
                }
                break;
            case 'span':
                if (!$el->attributes->length) {
                    return false; // <span> بلا تنسيق: النص فقط
                }
                break;
        }
        return true;
    }

    /** style: الخصائص المسموحة فقط بقيم مفحوصة، بصيغة TinyMCE نفسها ("color: #e03e2d; font-size: 18pt;") */
    private static function safeStyle(string $style): ?string
    {
        $style = trim($style);
        // ما لا يكتبه المحرر أبداً ويُستعمل في الهجمات: نرفض style كله
        if ($style === '' || strlen($style) > 2000
            || preg_match('/[\\\\<>{}@]|\/\*|expression|url\s*\(|javascript|vbscript|behaviou?r|binding|import|!/i', $style)) {
            return null;
        }
        $out = [];
        foreach (explode(';', $style) as $decl) {
            if (strpos($decl, ':') === false) {
                continue;
            }
            [$prop, $value] = explode(':', $decl, 2);
            $prop = strtolower(trim($prop));
            $value = trim((string) preg_replace('/\s+/', ' ', $value));
            if (!isset(self::STYLES[$prop]) || isset($out[$prop]) || $value === '' || strlen($value) > 200
                || !self::cssValue(self::STYLES[$prop], strtolower($value))) {
                continue;
            }
            $out[$prop] = $prop . ': ' . $value . ';';
        }
        return $out ? implode(' ', $out) : null;
    }

    private static function cssValue(string $type, string $v): bool
    {
        if ($type[0] === '/') {
            return preg_match($type, $v) === 1;
        }
        switch ($type) {
            case 'color':
                return self::cssColor($v);
            case 'colors':
                return self::cssEach($v, 4, [self::class, 'cssColor']);
            case 'font': // 'traditional arabic', serif — أسماء خطوط فقط
                $name = '(\'[^\'";:,()]{1,60}\'|"[^\'";:,()]{1,60}"|[a-z0-9\x{0600}-\x{06FF}][a-z0-9\x{0600}-\x{06FF} -]{0,59})';
                return preg_match('/^\s*' . $name . '\s*(,\s*' . $name . '\s*){0,9}$/u', $v) === 1;
            case 'size':
                return in_array($v, ['xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large', 'xxx-large', 'smaller', 'larger'], true)
                    || self::cssLength($v, ['px' => 200, 'pt' => 150, 'em' => 12, 'rem' => 12, '%' => 600]);
            case 'weight':
                return preg_match('/^(normal|bold|bolder|lighter|[1-9]00)$/', $v) === 1;
            case 'decoration':
                return self::cssEach($v, 3, fn($t) => in_array($t, ['none', 'underline', 'line-through', 'overline'], true));
            case 'lineheight':
                return $v === 'normal' || (preg_match('/^\d{1,2}(\.\d{1,3})?$/', $v) === 1 && (float) $v <= 10) || self::cssLength($v);
            case 'len':
                return self::cssLength($v);
            case 'slen': // تباعد الحروف وإزاحة السطر: سالب صغير مقبول
                return self::cssLength(strpos($v, '-') === 0 ? substr($v, 1) : $v, ['px' => 100, 'pt' => 75, 'em' => 5, 'rem' => 5, '%' => 50]);
            case 'lenauto':
            case 'dim':
                return $v === 'auto' || self::cssLength($v);
            case 'box':
                return self::cssEach($v, 4, fn($t) => $t === 'auto' || self::cssLength($t));
            case 'border':
                return self::cssEach($v, 3, fn($t) => self::cssLength($t, ['px' => 50, 'pt' => 40, 'em' => 3, 'rem' => 3])
                    || in_array($t, ['thin', 'medium', 'thick'], true) || in_array($t, self::BORDER_STYLES, true) || self::cssColor($t));
            case 'widths':
                return self::cssEach($v, 4, fn($t) => self::cssLength($t, ['px' => 50, 'pt' => 40, 'em' => 3, 'rem' => 3])
                    || in_array($t, ['thin', 'medium', 'thick'], true));
            case 'bstyles':
                return self::cssEach($v, 4, fn($t) => in_array($t, self::BORDER_STYLES, true));
        }
        return false;
    }

    /** طول موجب بوحدة معروفة وحدّ أقصى (0 بلا وحدة) */
    private static function cssLength(string $v, array $caps = self::CAPS): bool
    {
        if (preg_match('/^0+(\.0+)?$/', $v)) {
            return true;
        }
        if (!preg_match('/^(\d{1,5}(?:\.\d{1,4})?|\.\d{1,4})(px|pt|em|rem|%|cm|mm|in|ex|ch|vw|vh)$/', $v, $m)) {
            return false;
        }
        return isset($caps[$m[2]]) && (float) $m[1] <= $caps[$m[2]];
    }

    private static function cssColor(string $v): bool
    {
        $n = '\s*\d{1,3}(\.\d{1,3})?%?\s*';
        $alpha = '(\s*[,\/]\s*(0|1|0?\.\d{1,4}|\d{1,3}%))?';
        return preg_match('/^(#[0-9a-f]{3,4}|#[0-9a-f]{6}|#[0-9a-f]{8}|[a-z]{3,25}'
            . '|rgba?\(' . $n . ',' . $n . ',' . $n . $alpha . '\s*\)'
            . '|hsla?\(\s*\d{1,3}(deg)?\s*,' . $n . ',' . $n . $alpha . '\s*\))$/', $v) === 1;
    }

    /** قيمة من 1 إلى $max أجزاء، كل جزء يقبله $ok (rgb(...) جزء واحد) */
    private static function cssEach(string $v, int $max, callable $ok): bool
    {
        if (!preg_match_all('/[a-z-]+\([^()]*\)|[^\s()]+/', $v, $m) || implode('', $m[0]) !== str_replace(' ', '', $v)) {
            return false;
        }
        if (count($m[0]) > $max) {
            return false;
        }
        foreach ($m[0] as $t) {
            if (!$ok($t)) {
                return false;
            }
        }
        return true;
    }

    /** مصدر صورة/فيديو/صوت: http(s) أو مسار في الموقع. لا data: ولا javascript: ولا blob: */
    private static function safeSrc(string $src): ?string
    {
        $src = trim($src);
        if ($src === '' || strlen($src) > 2048 || preg_match('/[\x00-\x1F\x7F\\\\]/', $src)) {
            return null;
        }
        $compact = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $src);
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https'], true) ? $src : null;
        }
        return preg_match('#^[^/?\#]*:#', $compact) ? null : $src;
    }

    /** iframe: https من المواقع المسموحة فقط (EMBEDS)، بلا اسم مستخدم ولا منفذ */
    private static function safeEmbed(string $src): ?string
    {
        $src = trim($src);
        if ($src === '' || strlen($src) > 2048 || preg_match('/[\x00-\x20\x7F\\\\"\'<>`]/', $src)) {
            return null;
        }
        if (strpos($src, '//') === 0) {
            $src = 'https:' . $src;
        }
        $u = parse_url($src);
        if (!is_array($u) || !in_array(strtolower($u['scheme'] ?? ''), ['https', 'http'], true)
            || isset($u['user']) || isset($u['pass']) || isset($u['port'])) {
            return null;
        }
        $host = strtolower($u['host'] ?? '');
        $path = $u['path'] ?? '';
        if (!isset(self::EMBEDS[$host]) || !preg_match(self::EMBEDS[$host], $path)) {
            return null;
        }
        return 'https://' . $host . $path . (isset($u['query']) ? '?' . $u['query'] : '') . (isset($u['fragment']) ? '#' . $u['fragment'] : '');
    }

    /** رابط مسموح: http(s) و mailto و tel، أو داخلي (/news/ أو #قسم). وإلا null */
    private static function safeHref(string $href): ?string
    {
        $href = trim($href);
        if ($href === '' || strlen($href) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $href)) {
            return null;
        }
        // المتصفح يتجاهل المسافات وأحرف التحكم داخل البروتوكول: " java\tscript:" = javascript:
        $compact = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $href);
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $href : null;
        }
        // بلا بروتوكول: أي «:» قبل أول / أو ? أو # قد يُفهم بروتوكولاً — نرفضه
        return preg_match('#^[^/?\#]*:#', $compact) ? null : $href;
    }

    /** النص الطليق في أعلى المستوى (بلا فقرة) يوضع داخل <p> */
    private static function wrapInline(DOMDocument $doc, DOMElement $body): void
    {
        $run = [];
        $flush = function () use (&$run, $doc, $body): void {
            if (!$run) {
                return;
            }
            $text = '';
            $media = false;
            foreach ($run as $n) {
                $text .= $n->textContent;
                $media = $media || self::hasMedia($n);
            }
            if (self::blankText($text) && !$media) {
                foreach ($run as $n) {
                    $body->removeChild($n);
                }
            } else {
                $p = $doc->createElement('p');
                $body->insertBefore($p, $run[0]);
                foreach ($run as $n) {
                    $p->appendChild($n);
                }
            }
            $run = [];
        };
        foreach (iterator_to_array($body->childNodes) as $n) {
            if ($n instanceof DOMElement && in_array(strtolower($n->nodeName), self::BLOCKS, true)) {
                $flush();
            } else {
                $run[] = $n;
            }
        }
        $flush();
    }

    private static function isBlank(DOMElement $body): bool
    {
        return self::blankText($body->textContent) && !self::hasMedia($body)
            && $body->getElementsByTagName('hr')->length === 0
            && $body->getElementsByTagName('table')->length === 0;
    }

    /** صورة أو فيديو أو iframe (العنصر نفسه أو بداخله) */
    private static function hasMedia(DOMNode $n): bool
    {
        if (!($n instanceof DOMElement)) {
            return false;
        }
        if (in_array(strtolower($n->nodeName), self::MEDIA, true)) {
            return true;
        }
        foreach (self::MEDIA as $tag) {
            if ($n->getElementsByTagName($tag)->length > 0) {
                return true;
            }
        }
        return false;
    }

    private static function blankText(string $text): bool
    {
        return trim((string) preg_replace('/[\s\x{00A0}\x{200B}-\x{200F}\x{FEFF}]+/u', '', $text)) === '';
    }

    /** نص العنصر: الكتل و<br> تفصلها أسطر */
    private static function text(DOMNode $node, bool $blocks): string
    {
        $out = '';
        foreach ($node->childNodes as $c) {
            if ($c instanceof DOMText) {
                $out .= $c->nodeValue;
            } elseif ($c instanceof DOMElement) {
                $tag = strtolower($c->nodeName);
                if ($tag === 'br') {
                    $out .= "\n";
                } elseif (in_array($tag, self::MEDIA, true)) {
                    continue;
                } elseif ($blocks && in_array($tag, self::BLOCKS, true)) {
                    $out .= "\n" . self::text($c, true) . "\n";
                } else {
                    $out .= self::text($c, $blocks) . (in_array($tag, ['td', 'th'], true) ? ' ' : '');
                }
            }
        }
        return $out;
    }

    /** يستبدل كل مقطع نصي مباشر داخل الكتلة (بين الكتل الداخلية) بـ $fn(نصه) */
    private static function mapRuns(DOMDocument $doc, DOMElement $el, callable $fn): void
    {
        $run = [];
        $flush = function () use (&$run, $doc, $el, $fn): void {
            if (!$run) {
                return;
            }
            $text = '';
            foreach ($run as $n) {
                if ($n instanceof DOMText) {
                    $text .= $n->nodeValue;
                } elseif ($n instanceof DOMElement) {
                    $text .= strtolower($n->nodeName) === 'br' ? "\n" : self::text($n, false);
                }
            }
            if (!self::blankText($text)) {
                $translated = (string) $fn(trim($text));
                $lines = explode("\n", $translated);
                foreach ($lines as $i => $line) {
                    if ($i > 0) {
                        $el->insertBefore($doc->createElement('br'), $run[0]);
                    }
                    $el->insertBefore($doc->createTextNode($line), $run[0]);
                }
                foreach ($run as $n) {
                    $el->removeChild($n);
                }
            }
            $run = [];
        };
        foreach (iterator_to_array($el->childNodes) as $n) {
            if ($n instanceof DOMElement && (in_array(strtolower($n->nodeName), self::BLOCKS, true) || self::hasMedia($n))) {
                $flush();
            } else {
                $run[] = $n;
            }
        }
        $flush();
    }
}
