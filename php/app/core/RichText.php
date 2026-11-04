<?php
/**
 * النصوص المنسّقة (محرر TinyMCE في لوحة الإدارة): تنظيف HTML بقائمة سماح صارمة، وعرضه، وتحويله لنص عادي.
 *
 *   RichText::normalize($_POST['content'])  عند الحفظ: HTML منظَّف ('' إن كان فارغاً)، والنص العادي يصير فقرات <p>
 *   RichText::render($news['content'])      في الصفحات: HTML منظَّف — والنص القديم العادي يُعرض كما كان (linebreaks)
 *   RichText::toText($cite['description'])  للمقتطفات والبحث: نص عادي بلا وسوم
 *
 * الأمان: كل HTML يُنظَّف عند الحفظ وعند العرض (دفاع مزدوج) — حتى لو وصل HTML خطير إلى قاعدة البيانات
 * (من الترجمة الآلية أو استيراد أو تعديل مباشر) لا يصل إلى المتصفح:
 *   - الوسوم المسموحة فقط (TAGS)، والوسوم الخطيرة تُحذف مع محتواها (DROP)، وغير المعروفة يبقى نصها فقط
 *   - الخصائص المسموحة فقط: لا style ولا on...= ولا id؛ class للمحاذاة فقط (text-center ...)
 *   - الروابط: http(s) و mailto و tel والروابط الداخلية فقط (لا javascript: ولا data:)، و target="_blank" مع rel="noopener noreferrer"
 *   - لا صور ولا iframe ولا svg ولا نماذج — وكل نص يُهرَّب عند الإخراج
 * القيم القديمة نص عادي بلا وسوم: لا تحتاج قاعدة البيانات أي تحويل.
 */
final class RichText
{
    /** الوسوم المسموحة => خصائصها المسموحة */
    private const TAGS = [
        'p' => ['class'], 'h2' => ['class'], 'h3' => ['class'], 'h4' => ['class'],
        'br' => [], 'hr' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [], 'code' => [],
        'ul' => [], 'ol' => ['start'], 'li' => ['class'],
        'blockquote' => [], 'pre' => [],
        'a' => ['href', 'target'],
        'figure' => ['class'], 'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'class'], 'td' => ['colspan', 'rowspan', 'class'],
    ];

    /** وسوم تُحذف مع كل ما بداخلها */
    private const DROP = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'param', 'noscript', 'noembed',
        'noframes', 'template', 'svg', 'math', 'form', 'input', 'button', 'select', 'option', 'optgroup', 'datalist',
        'textarea', 'label', 'output', 'link', 'meta', 'base', 'title', 'head', 'img', 'image', 'picture', 'video', 'audio',
        'source', 'track', 'canvas', 'map', 'area', 'xmp', 'plaintext', 'listing', 'dialog', 'portal', 'slot',
    ];

    /** عناصر الكتل (ما سواها نص داخل فقرة) */
    private const BLOCKS = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'figure', 'table', 'thead',
        'tbody', 'tfoot', 'tr', 'th', 'td', 'hr'];

    /** عناصر لا تصح إلا داخل عنصر معيّن (وإلا يبقى محتواها فقط) */
    private const PARENTS = [
        'li' => ['ul', 'ol'], 'tr' => ['table', 'thead', 'tbody', 'tfoot'], 'td' => ['tr'], 'th' => ['tr'],
        'thead' => ['table'], 'tbody' => ['table'], 'tfoot' => ['table'],
    ];

    /** المحاذاة في المحرر (formats في admin-richtext.js: أصناف Bootstrap بدل style) */
    private const ALIGN = ['text-left', 'text-center', 'text-right', 'text-justify'];

    /** أقصى حجم نقبله (MEDIUMTEXT يتسع لأكثر، لكن لا يوجد نص إداري بهذا الطول) */
    private const MAX_BYTES = 1048576;

    // ================================================================== الواجهة

    /** هل القيمة HTML من المحرر (تبدأ بعنصر كتلة) أم نص عادي قديم؟ */
    public static function isHtml($value): bool
    {
        return is_string($value) && preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|pre|figure|table|hr|div)[\s>\/]/i', $value) === 1;
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
     * ويُستبدل نص كل كتلة بنتيجة $fn(نص الكتلة). التنسيق داخل الفقرة (غامق، رابط) لا يبقى.
     */
    public static function mapText(string $html, callable $fn): string
    {
        if (!self::hasDom()) {
            return self::fromPlain((string) $fn(self::stripTags($html)));
        }
        [$doc, $body] = self::load(self::clean($html));
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $el) {
            if (in_array(strtolower($el->nodeName), ['p', 'h2', 'h3', 'h4', 'li', 'th', 'td', 'blockquote', 'pre'], true)) {
                self::mapRuns($doc, $el, $fn);
            }
        }
        return self::clean(self::save($doc, $body));
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
        return trim($out);
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
                    self::cleanChildren($child);
                    $parentTag = strtolower($parent->nodeName);
                    $misplaced = isset(self::PARENTS[$tag]) && !in_array($parentTag, self::PARENTS[$tag], true);
                    if (!isset(self::TAGS[$tag]) || $misplaced || !self::cleanAttributes($child, $tag)) {
                        while ($child->firstChild !== null) { // عنصر غير مسموح: يبقى محتواه (المنظَّف) فقط
                            $parent->insertBefore($child->firstChild, $child);
                        }
                        $parent->removeChild($child);
                    }
                }
            } elseif (!($child instanceof DOMText) || $child instanceof DOMCdataSection) {
                $parent->removeChild($child); // تعليقات <!-- --> و CDATA وتعليمات المعالجة
            }
            $child = $next;
        }
    }

    /** يحذف الخصائص غير المسموحة. يرجع false إن وجب حذف العنصر نفسه (رابط بلا عنوان صالح، figure بلا جدول) */
    private static function cleanAttributes(DOMElement $el, string $tag): bool
    {
        $allowed = self::TAGS[$tag];
        $attrs = [];
        foreach ($el->attributes as $a) {
            $attrs[] = $a;
        }
        foreach ($attrs as $a) {
            $name = strtolower($a->nodeName);
            $value = (string) $a->nodeValue;
            $el->removeAttributeNode($a);
            if (!in_array($name, $allowed, true)) {
                continue;
            }
            switch ($name) {
                case 'class':
                    $ok = $tag === 'figure' ? ['table'] : self::ALIGN;
                    $keep = array_values(array_unique(array_intersect(preg_split('/\s+/', trim($value)) ?: [], $ok)));
                    if ($keep) {
                        $el->setAttribute('class', implode(' ', $keep));
                    }
                    break;
                case 'colspan':
                case 'rowspan':
                    if (preg_match('/^\s*([1-9]\d?)\s*$/', $value, $m) && (int) $m[1] <= 50) {
                        $el->setAttribute($name, $m[1]);
                    }
                    break;
                case 'start':
                    if (preg_match('/^\s*(-?\d{1,5})\s*$/', $value, $m)) {
                        $el->setAttribute('start', $m[1]);
                    }
                    break;
                case 'href':
                    if (($url = self::safeHref($value)) !== null) {
                        $el->setAttribute('href', $url);
                    }
                    break;
                case 'target':
                    if (trim($value) === '_blank') {
                        $el->setAttribute('target', '_blank');
                    }
                    break;
            }
        }
        if ($tag === 'a') {
            if (!$el->hasAttribute('href')) {
                return false;
            }
            if ($el->hasAttribute('target')) {
                $el->setAttribute('rel', 'noopener noreferrer');
            }
        }
        if ($tag === 'figure' && ($el->getAttribute('class') !== 'table' || $el->getElementsByTagName('table')->length === 0)) {
            return false;
        }
        return true;
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
            foreach ($run as $n) {
                $text .= $n->textContent;
            }
            if (self::blankText($text)) {
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
        return self::blankText($body->textContent)
            && $body->getElementsByTagName('hr')->length === 0
            && $body->getElementsByTagName('table')->length === 0;
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
            if ($n instanceof DOMElement && in_array(strtolower($n->nodeName), self::BLOCKS, true)) {
                $flush();
            } else {
                $run[] = $n;
            }
        }
        $flush();
    }
}
