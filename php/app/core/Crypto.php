<?php
/**
 * تشفير المعطيات الشخصية للمترشحين ووثائقهم (AES-256-GCM عبر امتداد openssl المفعّل في XAMPP).
 *
 *   $blob = Crypto::encrypt('نص سري', 'application:C26-ABCD2345');      // نص مشفّر يُحفظ في قاعدة البيانات
 *   $text = Crypto::decrypt($blob, 'application:C26-ABCD2345');         // يرمي استثناء إن تغيّر حرف واحد
 *   $hash = Crypto::blindIndex('109876543210987654');                    // بصمة ثابتة للبحث عن التكرار دون حفظ الرقم
 *
 * - GCM يكشف أي تعديل على البيانات المشفّرة (لا يمكن تغييرها دون معرفة المفتاح).
 * - «السياق» (AAD) يربط كل نص مشفّر بسجله: نسخ بيانات طلب إلى طلب آخر في قاعدة البيانات يفشل فك تشفيرها.
 * - المفتاح الرئيسي لا يُحفظ في قاعدة البيانات أبداً: من config.php (RECRUITMENT_KEY) أو من ملف
 *   storage/private/recruitment.key الذي يُنشأ تلقائياً عند أول استعمال. سرقة قاعدة البيانات وحدها لا تكفي.
 * - من المفتاح الرئيسي نشتق (HKDF) مفتاحاً مستقلاً لكل استعمال: التشفير، البصمات، توقيع النماذج.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const FILE_MAGIC = "DOUENC1\n";
    private static ?string $master = null;

    /** هل الخادم يدعم التشفير؟ (امتداد openssl) */
    public static function available(): bool
    {
        return function_exists('openssl_encrypt') && in_array(self::CIPHER, openssl_get_cipher_methods(), true);
    }

    /** مسار ملف المفتاح (عند عدم وضعه في config.php) */
    public static function keyFile(): string
    {
        return STORAGE_PATH . '/private/recruitment.key';
    }

    /** هل المفتاح موجود فعلاً (في الإعدادات أو في الملف)؟ */
    public static function hasKey(): bool
    {
        return trim((string) config('recruitment.key', '')) !== '' || is_file(self::keyFile());
    }

    /** المفتاح الرئيسي (32 بايت) — يُنشأ ملفه عند أول استعمال */
    private static function masterKey(): string
    {
        if (self::$master !== null) {
            return self::$master;
        }
        if (!self::available()) {
            throw new RuntimeException('امتداد openssl غير مفعّل في PHP: فعّله في php.ini (extension=openssl) لاستعمال الترشح الإلكتروني.');
        }
        $configured = trim((string) config('recruitment.key', ''));
        if ($configured !== '') {
            return self::$master = self::parseKey($configured, 'RECRUITMENT_KEY في config.php');
        }
        $file = self::keyFile();
        if (!is_file($file)) {
            self::createKeyFile($file);
        }
        return self::$master = self::parseKey((string) file_get_contents($file), 'storage/private/recruitment.key');
    }

    private static function parseKey(string $hex, string $where): string
    {
        $hex = trim($hex);
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
            throw new RuntimeException('مفتاح التشفير غير صالح (' . $where . '): يجب أن يكون 64 حرفاً من 0-9 و a-f.');
        }
        return hex2bin($hex);
    }

    /** إنشاء ملف المفتاح مرة واحدة (fopen 'x' يمنع طلبين متزامنين من إنشاء مفتاحين مختلفين) */
    private static function createKeyFile(string $file): void
    {
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('تعذر إنشاء المجلد storage/private لحفظ مفتاح التشفير.');
        }
        $fh = @fopen($file, 'x');
        if ($fh === false) {
            clearstatcache(true, $file);
            if (is_file($file)) {
                return; // أنشأه طلب آخر في نفس اللحظة
            }
            throw new RuntimeException('تعذر إنشاء ملف مفتاح التشفير في storage/private (تحقق من صلاحيات الكتابة).');
        }
        fwrite($fh, bin2hex(random_bytes(32)) . "\n");
        fclose($fh);
        @chmod($file, 0600);
        security_log('recruitment_key_created', ['file' => 'storage/private/recruitment.key']);
    }

    /** مفتاح فرعي مستقل لكل استعمال */
    private static function subkey(string $purpose): string
    {
        return hash_hkdf('sha256', self::masterKey(), 32, 'dou-setif/recruitment/' . $purpose);
    }

    // ------------------------------------------------------------------ النصوص

    public static function encrypt(string $plain, string $context): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plain, self::CIPHER, self::subkey('enc-v1'), OPENSSL_RAW_DATA, $iv, $tag, $context, 16);
        if ($ct === false || strlen($tag) !== 16) {
            throw new RuntimeException('تعذر تشفير البيانات.');
        }
        return 'v1:' . base64_encode($iv . $tag . $ct);
    }

    public static function decrypt(string $blob, string $context): string
    {
        $raw = str_starts_with($blob, 'v1:') ? base64_decode(substr($blob, 3), true) : false;
        if ($raw === false || strlen($raw) < 28) {
            throw new RuntimeException('بيانات مشفّرة تالفة.');
        }
        $plain = openssl_decrypt(substr($raw, 28), self::CIPHER, self::subkey('enc-v1'), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $context);
        if ($plain === false) {
            throw new RuntimeException('تعذر فك التشفير: المفتاح غير مطابق أو البيانات عُدّلت.');
        }
        return $plain;
    }

    public static function encryptArray(array $data, string $context): string
    {
        return self::encrypt(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $context);
    }

    public static function decryptArray(string $blob, string $context): array
    {
        $data = json_decode(self::decrypt($blob, $context), true);
        return is_array($data) ? $data : [];
    }

    // ------------------------------------------------------------------ الملفات (بضعة ميغابايت: في الذاكرة)

    /** يشفّر محتوى ملف ويكتبه في $dest (لا يُكتب الملف الأصلي على القرص في أي مكان آخر) */
    public static function encryptToFile(string $plain, string $dest, string $context): void
    {
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plain, self::CIPHER, self::subkey('file-v1'), OPENSSL_RAW_DATA, $iv, $tag, $context, 16);
        if ($ct === false) {
            throw new RuntimeException('تعذر تشفير الملف.');
        }
        $tmp = $dest . '.part';
        if (@file_put_contents($tmp, self::FILE_MAGIC . $iv . $tag . $ct, LOCK_EX) === false || !@rename($tmp, $dest)) {
            @unlink($tmp);
            throw new RuntimeException('تعذر حفظ الملف المشفّر (تحقق من صلاحيات الكتابة في storage/private).');
        }
        @chmod($dest, 0600);
    }

    public static function decryptFile(string $path, string $context): string
    {
        $raw = @file_get_contents($path);
        $n = strlen(self::FILE_MAGIC);
        if ($raw === false || strlen($raw) < $n + 28 || substr($raw, 0, $n) !== self::FILE_MAGIC) {
            throw new RuntimeException('الملف المشفّر غير موجود أو تالف.');
        }
        $plain = openssl_decrypt(substr($raw, $n + 28), self::CIPHER, self::subkey('file-v1'), OPENSSL_RAW_DATA, substr($raw, $n, 12), substr($raw, $n + 12, 16), $context);
        if ($plain === false) {
            throw new RuntimeException('تعذر فك تشفير الملف: المفتاح غير مطابق أو الملف عُدّل.');
        }
        return $plain;
    }

    // ------------------------------------------------------------------ البصمات والتواقيع

    /** بصمة ثابتة (HMAC) لقيمة سرية: نفس القيمة ← نفس البصمة، ولا يمكن الرجوع منها إلى القيمة دون المفتاح */
    public static function blindIndex(string $value, string $purpose = 'nin'): string
    {
        return hash_hmac('sha256', $value, self::subkey('idx-' . $purpose));
    }

    /** توقيع بيانات صغيرة (رمز وقت فتح الاستمارة) */
    public static function sign(string $data): string
    {
        return hash_hmac('sha256', $data, self::subkey('sign-v1'));
    }
}
