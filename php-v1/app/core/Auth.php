<?php
/**
 * المصادقة وتسجيل الدخول (بديل django.contrib.auth).
 * يدعم كلمات مرور Django القديمة (pbkdf2_sha256) ويحوّلها تلقائياً إلى password_hash عند أول دخول.
 */
final class Auth
{
    private static $user = false;

    public static function user(): ?User
    {
        if (self::$user === false) {
            self::$user = null;
            $id = $_SESSION['_auth_user_id'] ?? null;
            if ($id) {
                $u = User::find((int) $id);
                // تسجيل الخروج تلقائياً إذا تغيّرت كلمة المرور أو عُطّل الحساب
                if ($u && $u['is_active'] && hash_equals($_SESSION['_auth_hash'] ?? '', self::sessionHash($u))) {
                    self::$user = $u;
                } else {
                    unset($_SESSION['_auth_user_id'], $_SESSION['_auth_hash']);
                }
            }
        }
        return self::$user;
    }

    /** التحقق من اسم المستخدم وكلمة المرور (مثل authenticate) */
    public static function attempt(string $username, string $password): ?User
    {
        $u = User::firstWhere('username = ?', [$username]);
        if (!$u) {
            // نفس الوقت تقريباً لمنع تخمين أسماء المستخدمين
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return null;
        }
        if (!self::verifyPassword($password, (string) $u['password'])) {
            return null;
        }
        // ترقية الهاش القديم (Django) إلى صيغة PHP الحديثة
        if (self::needsRehash((string) $u['password'])) {
            $u->update(['password' => self::hashPassword($password)]);
        }
        return $u['is_active'] ? $u : null;
    }

    public static function login(User $u, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['_auth_user_id'] = (int) $u['id'];
        $_SESSION['_auth_hash'] = self::sessionHash($u);
        $u->update(['last_login' => now()]);
        self::$user = $u;
        // "تذكرني": أسبوعين، وإلا تنتهي الجلسة بإغلاق المتصفح
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => $remember ? time() + 1209600 : 0,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    /** بعد تغيير كلمة المرور: إبقاء المستخدم متصلاً (update_session_auth_hash) */
    public static function refreshSession(User $u): void
    {
        $_SESSION['_auth_hash'] = self::sessionHash($u);
        self::$user = $u;
    }

    private static function sessionHash(User $u): string
    {
        return hash('sha256', 'auth|' . $u['id'] . '|' . $u['password']);
    }

    // ------------------------------------------------------------------ كلمات المرور

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        if ($hash === '' || $hash[0] === '!') {
            return false; // كلمة مرور غير قابلة للاستعمال
        }
        // صيغة Django: pbkdf2_sha256$<iterations>$<salt>$<hash>
        if (strpos($hash, 'pbkdf2_') === 0) {
            $parts = explode('$', $hash);
            if (count($parts) !== 4) {
                return false;
            }
            [$algo, $iterations, $salt, $expected] = $parts;
            $digest = $algo === 'pbkdf2_sha1' ? 'sha1' : 'sha256';
            $len = $digest === 'sha1' ? 20 : 32;
            $calc = base64_encode(hash_pbkdf2($digest, $plain, $salt, (int) $iterations, $len, true));
            return hash_equals($expected, $calc);
        }
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return strpos($hash, 'pbkdf2_') === 0 || password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
