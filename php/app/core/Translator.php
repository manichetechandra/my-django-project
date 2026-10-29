<?php
/**
 * الترجمة التلقائية من العربية إلى الفرنسية والإنجليزية (تستعملها لوحة الإدارة).
 *
 *   Translator::translate(['title' => 'صرف المنحة', 'summary' => '...'])
 *     => ['fr' => ['title' => 'Versement de la bourse', 'summary' => '...'],
 *         'en' => ['title' => 'Scholarship payment',    'summary' => '...']]
 *
 * الخدمات (config.php ← 'translation'):
 *   gemini   : Google Gemini — مفتاح مجاني من https://aistudio.google.com/apikey
 *              طلب واحد لكل حقول السجل وللغتين معاً، وجودة عالية للنصوص الإدارية.
 *   mymemory : MyMemory — مجانية بلا تسجيل، لكن بحد يومي (5 آلاف حرف، أو 50 ألفاً مع بريدك)
 *              و500 بايت لكل طلب: النصوص الطويلة تُقسَّم إلى جمل.
 *   none     : معطلة (الترجمة يدوية فقط).
 *   test     : للتطوير فقط، بلا إنترنت: تضع [FR] أو [EN] قبل النص العربي.
 * إن فشلت الخدمة الأولى (لا مفتاح، انتهت الحصة، انقطاع) نجرّب الخدمة البديلة 'fallback'.
 */
class Translator
{
    /** آخر خدمة نجحت (لعرضها في الرسائل) */
    public static string $usedProvider = '';

    /**
     * للترجمة الجماعية (صفحة «ترجمة المحتوى» وسطر الأوامر): عند تجاوز حد الطلبات في الدقيقة (429)
     * ننتظر المدة التي يطلبها Gemini ثم نعيد المحاولة، بدل الانتقال فوراً إلى الخدمة البديلة.
     */
    public static bool $patient = false;

    /** هل الترجمة التلقائية مفعّلة؟ */
    public static function enabled(): bool
    {
        return self::providers() !== [];
    }

    /** ترتيب الخدمات التي سنجربها */
    public static function providers(): array
    {
        $list = [];
        foreach ([config('translation.provider', 'gemini'), config('translation.fallback', 'mymemory')] as $p) {
            $p = strtolower(trim((string) $p));
            if ($p === 'gemini' && trim((string) config('translation.gemini_key', '')) === '') {
                continue; // لا مفتاح: ننتقل إلى البديل
            }
            if (in_array($p, ['gemini', 'mymemory', 'test'], true) && !in_array($p, $list, true)) {
                $list[] = $p;
            }
        }
        return $list;
    }

    /**
     * ترجمة مجموعة نصوص عربية (حقول سجل واحد) إلى عدة لغات.
     * النصوص الفارغة تبقى فارغة. يرمي RuntimeException برسالة عربية إن فشلت كل الخدمات.
     */
    public static function translate(array $texts, ?array $targets = null): array
    {
        $targets = $targets ?? array_values(array_diff(array_keys(languages()), [default_lang()]));
        $result = array_fill_keys($targets, array_fill_keys(array_keys($texts), ''));
        $todo = array_filter(array_map(fn($t) => trim((string) $t), $texts), fn($t) => $t !== '');
        if (!$todo || !$targets) {
            return $result;
        }
        $errors = [];
        foreach (self::providers() as $provider) {
            try {
                $out = match ($provider) {
                    'gemini'   => self::gemini($todo, $targets),
                    'mymemory' => self::mymemory($todo, $targets),
                    default    => self::test($todo, $targets),
                };
                foreach ($targets as $lang) {
                    foreach ($todo as $key => $_) {
                        $result[$lang][$key] = trim((string) ($out[$lang][$key] ?? ''));
                    }
                }
                self::$usedProvider = $provider;
                return $result;
            } catch (Throwable $e) {
                $errors[] = $provider . ': ' . $e->getMessage();
            }
        }
        throw new RuntimeException($errors ? implode(' | ', $errors) : 'الترجمة التلقائية غير مفعّلة. أضف مفتاح Gemini في config.php (translation.gemini_key).');
    }

    // ================================================================== ترجمة المحتوى القديم (دفعات)

    /** النماذج التي لها حقول مترجمة ($translatable) */
    public static function contentModels(): array
    {
        $out = [];
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, 'Model') && $class::translatable()) {
                $out[] = $class;
            }
        }
        return $out;
    }

    /** شرط SQL: سجل فيه نص عربي ينقصه ترجمة (أو كل السجلات مع $force) */
    private static function missingWhere(string $class, bool $force = false): string
    {
        $parts = [];
        foreach ($class::translatable() as $f) {
            $miss = [];
            foreach (array_diff(array_keys(languages()), [default_lang()]) as $lang) {
                $miss[] = "`{$f}_{$lang}` IS NULL OR `{$f}_{$lang}` = ''";
            }
            $parts[] = "(`$f` IS NOT NULL AND `$f` <> ''" . ($force ? '' : ' AND (' . implode(' OR ', $miss) . ')') . ')';
        }
        return implode(' OR ', $parts);
    }

    /** عدد السجلات التي تنقصها ترجمة في كل جدول: ['news' => 3, ...] */
    public static function missingCounts(bool $force = false): array
    {
        $counts = [];
        foreach (self::contentModels() as $class) {
            $n = (int) DB::value('SELECT COUNT(*) FROM `' . $class::table() . '` WHERE ' . self::missingWhere($class, $force));
            if ($n > 0) {
                $counts[$class::table()] = $n;
            }
        }
        return $counts;
    }

    /**
     * يترجم حتى $limit سجلات تنقصها ترجمة (سجل = طلب ترجمة واحد لكل حقوله).
     * يرجع ['done' => عدد السجلات، 'log' => [سطر لكل سجل]] ويرمي RuntimeException إن فشلت الخدمة.
     * $force: إعادة ترجمة كل الحقول حتى المترجمة سابقاً.
     */
    public static function fillMissing(int $limit = 3, bool $force = false, array $skip = []): array
    {
        $done = 0;
        $log = [];
        $ids = [];
        $targets = array_values(array_diff(array_keys(languages()), [default_lang()]));
        foreach (self::contentModels() as $class) {
            $table = $class::table();
            if ($done >= $limit) {
                break;
            }
            $skipIds = array_map('intval', $skip[$table] ?? []);
            $skipSql = $skipIds ? ' AND id NOT IN (' . implode(',', $skipIds) . ')' : '';
            $rows = DB::all('SELECT * FROM `' . $table . '` WHERE (' . self::missingWhere($class, $force) . ')' . $skipSql . ' ORDER BY id LIMIT ' . ($limit - $done));
            foreach ($rows as $row) {
                $texts = [];
                foreach ($class::translatable() as $f) {
                    $need = $force;
                    foreach ($targets as $lang) {
                        $need = $need || trim((string) ($row[$f . '_' . $lang] ?? '')) === '';
                    }
                    if ($need && trim((string) $row[$f]) !== '') {
                        $texts[$f] = $row[$f];
                    }
                }
                $tr = self::translate($texts, $targets);
                $set = [];
                foreach ($texts as $f => $_) {
                    foreach ($targets as $lang) {
                        $col = $f . '_' . $lang;
                        if (($force || trim((string) ($row[$col] ?? '')) === '') && $tr[$lang][$f] !== '') {
                            $set[$col] = self::fit($table, $col, $tr[$lang][$f]);
                        }
                    }
                }
                if ($set) {
                    DB::query('UPDATE `' . $table . '` SET ' . implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($set))) . ' WHERE id = ?',
                        array_merge(array_values($set), [(int) $row['id']]));
                }
                $label = mb_strimwidth(trim((string) ($row[$class::translatable()[0]] ?? '')), 0, 60, '…');
                $log[] = $table . ' #' . $row['id'] . ' — ' . $label . ' (' . count($set) . ' حقل)';
                $ids[$table][] = (int) $row['id'];
                $done++;
            }
        }
        return ['done' => $done, 'log' => $log, 'ids' => $ids];
    }

    /** قص النص حتى لا يتجاوز طول العمود في قاعدة البيانات */
    private static function fit(string $table, string $col, string $value): string
    {
        static $max = [];
        if (!isset($max[$table])) {
            $max[$table] = [];
            foreach (DB::all('SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]) as $c) {
                $max[$table][$c['COLUMN_NAME']] = (int) $c['CHARACTER_MAXIMUM_LENGTH'];
            }
        }
        $limit = $max[$table][$col] ?? 0;
        return $limit > 0 && mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }

    // ================================================================== Google Gemini

    private static function gemini(array $texts, array $targets): array
    {
        $names = ['fr' => 'French', 'en' => 'English'];
        $langs = implode(' and ', array_map(fn($l) => ($names[$l] ?? $l) . ' (key "' . $l . '")', $targets));
        $system = 'You are the professional translator of the official website of the Directorate of University Services '
            . '(Direction des Œuvres Universitaires, DOU) of Sétif, Algeria. Translate Arabic administrative texts and news '
            . 'into natural, formal French and English as used in Algeria. Rules: translate the meaning faithfully without adding '
            . 'or removing information; keep line breaks, lists, numbers, dates, times, phone numbers, emails, URLs and any HTML '
            . 'tags exactly as they are; write Algerian names of people and places in their usual French spelling '
            . '(سطيف = Sétif, الباز = El Bez, عين الفوارة = Aïn El Fouara); use "Direction des Œuvres Universitaires" / '
            . '"Directorate of University Services" for مديرية الخدمات الجامعية.';
        $prompt = "Translate every value of the following JSON object from Arabic into $langs.\n"
            . 'Return only a JSON object of the form {' . implode(', ', array_map(fn($l) => "\"$l\": {...same keys...}", $targets)) . "}.\n\n"
            . json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $body = json_encode([
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.2],
        ], JSON_UNESCAPED_UNICODE);

        $model = (string) config('translation.gemini_model', 'gemini-2.5-flash');
        foreach (array_unique([$model, 'gemini-flash-latest']) as $m) {
            $endpoint = rtrim((string) config('translation.gemini_endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');
            for ($attempt = 1; ; $attempt++) {
                [$status, $raw] = self::http('POST', $endpoint . '/models/' . rawurlencode($m) . ':generateContent',
                    ['Content-Type: application/json', 'x-goog-api-key: ' . config('translation.gemini_key')], $body);
                $data = json_decode($raw, true);
                $wait = $status === 429 ? self::retryDelay($data) : null;
                if (!self::$patient || $wait === null || $wait > 65 || $attempt > 3) {
                    break;
                }
                sleep($wait + 1); // حد الدقيقة في الخطة المجانية: ننتظر ثم نعيد
            }
            if ($status === 404) {
                continue; // اسم النموذج غير متاح: نجرب الاسم المستعار الأحدث
            }
            if ($status !== 200) {
                $msg = $data['error']['message'] ?? ('HTTP ' . $status);
                throw new RuntimeException($status === 429 ? 'انتهت الحصة المجانية مؤقتاً (' . $msg . ')' : $msg);
            }
            $text = '';
            foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
                $text .= $part['text'] ?? '';
            }
            $json = json_decode($text, true);
            if (!is_array($json) && preg_match('/\{.*\}/s', $text, $mm)) {
                $json = json_decode($mm[0], true);
            }
            if (!is_array($json)) {
                throw new RuntimeException('ردّ غير مفهوم من Gemini');
            }
            foreach ($targets as $lang) {
                if (!isset($json[$lang]) || !is_array($json[$lang])) {
                    throw new RuntimeException('ترجمة ناقصة من Gemini (' . $lang . ')');
                }
            }
            return $json;
        }
        throw new RuntimeException('نموذج Gemini غير متاح: ' . $model);
    }

    /** مدة الانتظار التي يقترحها Gemini مع الخطأ 429 (RetryInfo.retryDelay مثل "17s") */
    private static function retryDelay($data): ?int
    {
        foreach ($data['error']['details'] ?? [] as $d) {
            if (isset($d['retryDelay']) && preg_match('/^(\d+(?:\.\d+)?)s$/', (string) $d['retryDelay'], $m)) {
                return (int) ceil((float) $m[1]);
            }
        }
        return null;
    }

    // ================================================================== MyMemory

    private static function mymemory(array $texts, array $targets): array
    {
        $out = [];
        foreach ($targets as $lang) {
            foreach ($texts as $key => $text) {
                // نقسم النص إلى أسطر ثم جمل، وكل قطعة أقل من 450 بايت
                $lines = preg_split('/(\R)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                $translated = '';
                foreach ($lines as $line) {
                    if (preg_match('/^\R$/u', $line) || trim($line) === '') {
                        $translated .= $line;
                        continue;
                    }
                    $pieces = [];
                    foreach (self::chunks($line, 450) as $chunk) {
                        $pieces[] = self::mymemoryOne($chunk, $lang);
                    }
                    $translated .= implode(' ', $pieces);
                }
                $out[$lang][$key] = $translated;
            }
        }
        return $out;
    }

    private static function mymemoryOne(string $text, string $lang): string
    {
        $q = ['q' => $text, 'langpair' => default_lang() . '|' . $lang];
        if (($email = trim((string) config('translation.mymemory_email', ''))) !== '') {
            $q['de'] = $email;
        }
        $endpoint = (string) config('translation.mymemory_endpoint', 'https://api.mymemory.translated.net/get');
        [$status, $raw] = self::http('GET', $endpoint . '?' . http_build_query($q), [], null);
        $data = json_decode($raw, true);
        if ($status !== 200 || !is_array($data)) {
            throw new RuntimeException('HTTP ' . $status);
        }
        if (!empty($data['quotaFinished']) || (int) ($data['responseStatus'] ?? 200) !== 200) {
            throw new RuntimeException(!empty($data['quotaFinished']) ? 'انتهت الحصة اليومية' : (string) ($data['responseDetails'] ?? 'خطأ'));
        }
        return html_entity_decode((string) ($data['responseData']['translatedText'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** تقسيم نص طويل عند نهايات الجمل (ثم الكلمات) بحيث لا تتجاوز القطعة $max بايت */
    private static function chunks(string $text, int $max): array
    {
        if (strlen($text) <= $max) {
            return [$text];
        }
        $parts = preg_split('/(?<=[.!؟?؛;،,])\s+/u', $text);
        $chunks = [];
        $cur = '';
        foreach ($parts as $p) {
            while (strlen($p) > $max) { // جملة طويلة جداً: نقسمها على الكلمات
                $cut = mb_strcut($p, 0, $max, 'UTF-8');
                $sp = mb_strrpos($cut, ' ');
                $cut = $sp ? mb_substr($cut, 0, $sp) : $cut;
                $chunks[] = $cut;
                $p = ltrim(mb_substr($p, mb_strlen($cut)));
            }
            if ($cur !== '' && strlen($cur) + 1 + strlen($p) > $max) {
                $chunks[] = $cur;
                $cur = $p;
            } else {
                $cur = $cur === '' ? $p : $cur . ' ' . $p;
            }
        }
        if ($cur !== '') {
            $chunks[] = $cur;
        }
        return $chunks;
    }

    // ================================================================== وضع الاختبار (بلا إنترنت)

    private static function test(array $texts, array $targets): array
    {
        $out = [];
        foreach ($targets as $lang) {
            foreach ($texts as $key => $text) {
                $out[$lang][$key] = '[' . strtoupper($lang) . '] ' . $text;
            }
        }
        return $out;
    }

    // ================================================================== HTTP

    /** طلب HTTP بسيط: يستعمل cURL إن وُجد وإلا file_get_contents. يرجع [رمز الحالة, النص] */
    private static function http(string $method, string $url, array $headers, ?string $body): array
    {
        $timeout = (int) config('translation.timeout', 45);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                throw new RuntimeException('تعذر الاتصال بخدمة الترجمة: ' . $err);
            }
            return [$status, (string) $raw];
        }
        $ctx = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'timeout' => $timeout,
            'ignore_errors' => true,
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new RuntimeException('تعذر الاتصال بخدمة الترجمة (تحقق من الإنترنت ومن تفعيل openssl في PHP)');
        }
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        return [$status, $raw];
    }
}
