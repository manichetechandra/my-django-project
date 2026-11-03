<?php
/**
 * فحص وثائق PDF التي يرفعها المترشحون قبل قبولها (دفاع إضافي فوق التشفير وعدم تنفيذ أي ملف):
 *
 *  1) الحجم، وأن الملف يبدأ فعلاً بـ %PDF- (لا يكفي الامتداد .pdf) وأن نوعه الحقيقي application/pdf وأنه مكتمل (%%EOF).
 *  2) يرفض الملفات المحمية بكلمة مرور (لا يستطيع الموظف قراءتها ولا نستطيع فحصها).
 *  3) يرفض «المحتوى النشط» الذي تستعمله الملفات الخبيثة: JavaScript، تشغيل برامج (Launch)، ملفات مضمّنة،
 *     نماذج XFA، أوامر تلقائية عند الفتح (AA)، فتح ملفات عبر الشبكة (GoToR: تسريب كلمات مرور Windows)، إرسال بيانات...
 *     حتى لو كُتبت الأسماء بترميز #xx (مثل /Java#53cript) أو خُبّئت داخل مجاري الكائنات المضغوطة (ObjStm).
 *  4) يرفض الكائنات المخبّأة داخل بيانات المجاري (حيلة معروفة لتجاوز برامج الفحص).
 *
 * لا نشغّل تعابير نمطية على البيانات الثنائية (الصور الممسوحة): الفحص يمر على «هيكل» الملف فقط، مع حدود
 * لحجم فك الضغط حتى لا يُستعمل ملف مضغوط ضخم لإسقاط الخادم.
 *
 *   if ($error = PdfGuard::check($_FILES['doc']['tmp_name'], 3 * 1024 * 1024)) { ... رفض مع $error }
 */
final class PdfGuard
{
    /** أسماء PDF الخطرة */
    public const DANGEROUS = ['JavaScript', 'JS', 'Launch', 'EmbeddedFile', 'EmbeddedFiles', 'RichMedia', 'XFA', 'AA',
        'GoToR', 'GoToE', 'ImportData', 'SubmitForm', 'Sound', 'Movie'];

    /** سبب آخر رفض بصيغة قصيرة لسجل الأمان (مثل active:/JavaScript) */
    public static string $reason = '';

    private const MAX_OBJECT_STREAMS = 300;
    private const MAX_INFLATE = 8 * 1024 * 1024;        // فك ضغط المجرى الواحد
    private const MAX_INFLATE_TOTAL = 40 * 1024 * 1024; // مجموع فك الضغط للملف

    /** null = الملف مقبول، وإلا رسالة الرفض بلغة الصفحة */
    public static function check(string $path, int $maxBytes): ?string
    {
        self::$reason = '';
        $size = @filesize($path);
        if ($size === false || $size < 64) {
            return __('الملف فارغ أو تالف.');
        }
        if ($size > $maxBytes) {
            return __('حجم الملف كبير جداً. الحد الأقصى {mb} ميغابايت.', ['mb' => rtrim(rtrim(number_format($maxBytes / 1048576, 1), '0'), '.')]);
        }
        $data = @file_get_contents($path);
        return $data === false ? __('الملف فارغ أو تالف.') : self::checkBytes($data);
    }

    public static function checkBytes(string $data): ?string
    {
        self::$reason = '';
        if (!preg_match('/\A%PDF-[12]\.\d/', $data)) {
            self::$reason = 'not_pdf';
            return __('الملف ليس وثيقة PDF صالحة.');
        }
        if (class_exists('finfo') && (new finfo(FILEINFO_MIME_TYPE))->buffer($data) !== 'application/pdf') {
            self::$reason = 'mime';
            return __('الملف ليس وثيقة PDF صالحة.');
        }
        if (strpos(substr($data, -1024), '%%EOF') === false) {
            self::$reason = 'truncated';
            return __('ملف PDF غير مكتمل أو تالف. أعد حفظه ثم ارفعه مجدداً.');
        }
        try {
            return self::scan($data);
        } catch (RuntimeException $e) {
            self::$reason = 'complex:' . $e->getMessage();
            return __('تعذر فحص الملف: بنيته معقدة أو غير مألوفة. احفظه من جديد عبر «الطباعة إلى PDF» ثم ارفعه.');
        }
    }

    /** يفصل هيكل الملف عن بيانات المجاري، ثم يفحص الأسماء في الهيكل وفي مجاري الكائنات */
    private static function scan(string $data): ?string
    {
        $len = strlen($data);
        $pos = 0;
        $skeleton = '';
        $objectStreams = [];
        while (($s = strpos($data, 'stream', $pos)) !== false) {
            $kwEnd = $s + 6;
            if ($s >= 3 && substr_compare($data, 'end', $s - 3, 3) === 0) { // endstream
                $skeleton .= substr($data, $pos, $kwEnd - $pos);
                $pos = $kwEnd;
                continue;
            }
            if ($kwEnd + 1 < $len && substr_compare($data, "\r\n", $kwEnd, 2) === 0) {
                $start = $kwEnd + 2;
            } elseif ($kwEnd < $len && ($data[$kwEnd] === "\n" || $data[$kwEnd] === "\r")) {
                $start = $kwEnd + 1;
            } else {
                $skeleton .= substr($data, $pos, $kwEnd - $pos);
                $pos = $kwEnd;
                continue;
            }
            // قاموس المجرى: النص بين رأس الكائن «N 0 obj» والكلمة stream
            $winStart = max($pos, $s - 8192);
            $window = substr($data, $winStart, $s - $winStart);
            $o = strrpos($window, 'obj');
            $dict = $o === false ? $window : substr($window, $o + 3);
            $names = self::names($dict);
            if (!in_array('Length', $names, true)) { // ليست مجرى حقيقياً (مثلاً كلمة stream داخل نص)
                $skeleton .= substr($data, $pos, $kwEnd - $pos);
                $pos = $kwEnd;
                continue;
            }
            // النهاية: الطول المباشر /Length N إن وُجد وتبعته endstream، وإلا أول endstream
            $end = null;
            if (preg_match('~/Length\s+(\d{1,10})(?!\s+\d+\s+R)~', $dict, $m) && $start + (int) $m[1] <= $len
                && preg_match('/\A\s{0,8}endstream/', substr($data, $start + (int) $m[1], 24))) {
                $end = $start + (int) $m[1];
            }
            if ($end === null) {
                $e = strpos($data, 'endstream', $start);
                $end = $e === false ? $len : $e;
            }
            $skeleton .= substr($data, $pos, $start - $pos);
            $raw = substr($data, $start, $end - $start);
            // كائن PDF مخبّأ داخل بيانات مجرى = محاولة لتضليل الفحص
            if (preg_match('/(?:\A|[\s>\]])\d{1,10}[ \t\r\n]{1,4}\d{1,5}[ \t\r\n]{1,4}obj[\s<\[\/]/', $raw)) {
                self::$reason = 'hidden_object';
                return __('بنية ملف PDF غير مألوفة. احفظه من جديد عبر «الطباعة إلى PDF» ثم ارفعه.');
            }
            if (in_array('ObjStm', $names, true)) {
                $objectStreams[] = [$names, $raw];
                if (count($objectStreams) > self::MAX_OBJECT_STREAMS) {
                    throw new RuntimeException('too many object streams');
                }
            }
            $pos = $end;
        }
        $skeleton .= substr($data, $pos);

        $error = self::checkNames(self::names($skeleton));
        if ($error !== null) {
            return $error;
        }
        $budget = self::MAX_INFLATE_TOTAL;
        foreach ($objectStreams as [$names, $raw]) {
            $filters = array_values(array_filter($names, fn($n) => is_string($n) && (str_ends_with($n, 'Decode') || in_array($n, ['Fl', 'AHx', 'A85', 'LZW', 'RL', 'CCF', 'DCT'], true))));
            if (in_array('DecodeParms', $names, true) || in_array('DP', $names, true) || array_diff($filters, ['FlateDecode', 'Fl'])) {
                throw new RuntimeException('unsupported object stream encoding');
            }
            if ($filters) {
                $raw = @zlib_decode($raw, min(self::MAX_INFLATE, $budget));
                if ($raw === false) {
                    throw new RuntimeException('object stream inflate failed');
                }
                $budget -= strlen($raw);
            }
            $error = self::checkNames(self::names($raw));
            if ($error !== null) {
                return $error;
            }
        }
        return null;
    }

    private static function checkNames(array $names): ?string
    {
        if (in_array('Encrypt', $names, true)) {
            self::$reason = 'encrypted';
            return __('الملف محمي بكلمة مرور أو مشفّر. احفظ نسخة بدون حماية ثم ارفعها.');
        }
        $bad = array_values(array_intersect(self::DANGEROUS, $names));
        if ($bad) {
            self::$reason = 'active:/' . $bad[0];
            return __('الملف يحتوي على محتوى نشط غير مسموح به ({name}). احفظه من جديد عبر «الطباعة إلى PDF» أو امسحه ضوئياً ثم ارفعه.', ['name' => '/' . $bad[0]]);
        }
        return null;
    }

    /** أسماء PDF الموجودة في نص (بعد فك الترميز #xx). التعبير بلا تراجع (possessive) فيبقى خطياً */
    private static function names(string $s): array
    {
        if (preg_match_all('~/([^\x00-\x20/<>\[\]()%{}]{1,127}+)~', $s, $m) === false) {
            throw new RuntimeException('name scan failed');
        }
        $out = [];
        foreach ($m[1] as $n) {
            if (strpos($n, '#') !== false) {
                $n = preg_replace_callback('/#([0-9A-Fa-f]{2})/', fn($h) => chr(hexdec($h[1])), $n);
            }
            $out[(string) $n] = true;
        }
        return array_map('strval', array_keys($out));
    }
}
