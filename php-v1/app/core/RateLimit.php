<?php
/**
 * تحديد عدد المحاولات خلال نافذة زمنية (Rate Limiting):
 * يحمي من تخمين كلمات المرور، ومن إغراق نماذج «اتصل بنا» والتسجيل بالرسائل المزعجة.
 *
 * يحفظ أوقات المحاولات في ملفات صغيرة داخل storage/ratelimit (بدون جدول في قاعدة البيانات)
 * مع قفل الملف (flock) حتى لا تضيع محاولة عند وصول طلبين في نفس اللحظة.
 *
 *   if ($wait = RateLimit::retryAfter('contact:' . client_ip(), 5, 600)) { ... انتظر $wait ثانية }
 *   RateLimit::hit('contact:' . client_ip(), 600);
 */
final class RateLimit
{
    /** مجلد الحفظ: storage/ratelimit، أو المجلد المؤقت للنظام إن تعذرت الكتابة فيه */
    private static function dir(): ?string
    {
        static $dir = false;
        if ($dir === false) {
            $dir = null;
            $candidates = [
                STORAGE_PATH . '/ratelimit',
                rtrim(sys_get_temp_dir(), '/\\') . '/dou-ratelimit-' . substr(md5(BASE_PATH), 0, 10),
            ];
            foreach ($candidates as $candidate) {
                if (!is_dir($candidate)) {
                    @mkdir($candidate, 0700, true);
                }
                if (is_dir($candidate) && is_writable($candidate)) {
                    $dir = $candidate;
                    break;
                }
            }
        }
        return $dir;
    }

    /**
     * يقرأ أوقات المحاولات الحديثة لمفتاح، ويعدّلها اختيارياً داخل القفل.
     * إن تعذر الحفظ لا نمنع أحداً (الموقع يبقى يعمل).
     */
    private static function times(string $key, int $window, ?callable $change = null): array
    {
        $dir = self::dir();
        if ($dir === null) {
            return [];
        }
        $file = $dir . '/' . hash('sha256', $key) . '.json';
        if ($change === null && !is_file($file)) {
            return [];
        }
        $fh = @fopen($file, 'c+');
        if (!$fh) {
            return [];
        }
        flock($fh, $change ? LOCK_EX : LOCK_SH);
        $times = json_decode((string) stream_get_contents($fh), true);
        $now = time();
        $times = array_values(array_filter(is_array($times) ? $times : [], fn($t) => is_int($t) && $t > $now - $window));
        if ($change) {
            $times = array_slice($change($times, $now), -500);
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($times));
            fflush($fh);
        }
        flock($fh, LOCK_UN);
        fclose($fh);
        if ($change && mt_rand(1, 200) === 1) {
            self::cleanup($dir);
        }
        return $times;
    }

    /** عدد المحاولات خلال آخر $window ثانية */
    public static function attempts(string $key, int $window): int
    {
        return count(self::times($key, $window));
    }

    /** تسجيل محاولة جديدة، ويعيد عدد المحاولات بعدها */
    public static function hit(string $key, int $window): int
    {
        return count(self::times($key, $window, function (array $times, int $now) {
            $times[] = $now;
            return $times;
        }));
    }

    /** هل تجاوز الحد؟ */
    public static function tooMany(string $key, int $max, int $window): bool
    {
        return self::retryAfter($key, $max, $window) > 0;
    }

    /** الثواني المتبقية حتى تُسمح محاولة جديدة (0 = مسموح الآن) */
    public static function retryAfter(string $key, int $max, int $window): int
    {
        $times = self::times($key, $window);
        $count = count($times);
        if ($max <= 0 || $count < $max) {
            return 0;
        }
        sort($times);
        // يجب أن تنتهي صلاحية أقدم (count - max + 1) محاولات
        return max(1, $times[$count - $max] + $window - time());
    }

    /** مسح العداد (مثلاً بعد دخول ناجح) */
    public static function clear(string $key): void
    {
        $dir = self::dir();
        if ($dir !== null) {
            @unlink($dir . '/' . hash('sha256', $key) . '.json');
        }
    }

    /** حذف الملفات القديمة (أكثر من يوم) */
    private static function cleanup(string $dir): void
    {
        foreach (glob($dir . '/*.json') ?: [] as $f) {
            if (@filemtime($f) < time() - 86400) {
                @unlink($f);
            }
        }
    }
}
