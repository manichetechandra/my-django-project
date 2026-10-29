<?php
/**
 * رفع الملفات بأمان: التحقق من الامتداد ونوع MIME الحقيقي والحجم، واسم ملف عشوائي.
 *
 *   $path = Upload::store($_FILES['photo'], 'students/photos', Upload::IMAGES, 5);   // => "students/photos/xxx.jpg"
 *   $path = Upload::store($_FILES['doc'], 'documents/2025/11', Upload::DOCUMENTS, 10, true); // ملف خاص
 */
final class Upload
{
    public const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const DOCUMENTS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    public const FILES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'jpg', 'jpeg', 'png'];

    private const MIME = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'svg'  => ['image/svg+xml'],
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/octet-stream', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/octet-stream', 'application/CDFV2'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'rar'  => ['application/x-rar', 'application/vnd.rar', 'application/x-rar-compressed', 'application/octet-stream'],
        'txt'  => ['text/plain'],
    ];

    /** يرجع رسالة الخطأ أو null إن كان الملف صالحاً */
    public static function validate(?array $file, array $allowedExt, float $maxMb): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return __('لم يتم اختيار ملف.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::errorMessage((int) $file['error']);
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            return __('ملف غير صالح.');
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return __('حجم الملف كبير جداً. الحد الأقصى {mb} ميغابايت.', ['mb' => rtrim(rtrim(number_format($maxMb, 1), '0'), '.')]);
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return __('نوع الملف غير مسموح. الأنواع المسموحة: {types}', ['types' => implode(', ', $allowedExt)]);
        }
        if (class_exists('finfo')) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $okMimes = self::MIME[$ext] ?? [];
            if ($okMimes && !in_array($mime, $okMimes, true)) {
                return __('محتوى الملف لا يطابق امتداده ({mime}).', ['mime' => $mime]);
            }
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && function_exists('getimagesize')) {
            if (@getimagesize($file['tmp_name']) === false) {
                return __('الملف ليس صورة صالحة.');
            }
        }
        return null;
    }

    /**
     * حفظ الملف وإرجاع مساره النسبي.
     * $private = true  => يُحفظ في storage/ (غير متاح مباشرة من المتصفح)
     */
    public static function store(array $file, string $subdir, array $allowedExt, float $maxMb, bool $private = false): string
    {
        $error = self::validate($file, $allowedExt, $maxMb);
        if ($error !== null) {
            throw new RuntimeException($error);
        }
        $root = $private ? STORAGE_PATH . '/private' : UPLOAD_PATH;
        $subdir = trim($subdir, '/');
        $dir = $root . '/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('تعذر إنشاء مجلد الرفع: ' . $subdir);
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
        $base = substr($base !== '' ? $base : 'file', 0, 40);
        $name = $base . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        $moved = PHP_SAPI === 'cli' ? rename($file['tmp_name'], $dest) : move_uploaded_file($file['tmp_name'], $dest);
        if (!$moved) {
            throw new RuntimeException('تعذر حفظ الملف المرفوع.');
        }
        @chmod($dest, 0644);
        return $subdir . '/' . $name;
    }

    /** حذف ملف مرفوع (عام أو خاص) */
    public static function delete(?string $path, bool $private = false): void
    {
        if (!$path) {
            return;
        }
        $root = realpath($private ? STORAGE_PATH . '/private' : UPLOAD_PATH);
        $full = realpath(($private ? STORAGE_PATH . '/private' : UPLOAD_PATH) . '/' . ltrim($path, '/'));
        if ($root && $full && strpos($full, $root . DIRECTORY_SEPARATOR) === 0 && is_file($full)) {
            @unlink($full);
        }
    }

    public static function errorMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('حجم الملف يتجاوز الحد المسموح به في الخادم.');
            case UPLOAD_ERR_PARTIAL:
                return __('تم رفع جزء من الملف فقط. حاول مجدداً.');
            case UPLOAD_ERR_NO_FILE:
                return __('لم يتم اختيار ملف.');
            default:
                return __('حدث خطأ أثناء رفع الملف (رمز {code}).', ['code' => $code]);
        }
    }

    /** إرسال ملف للتحميل */
    public static function send(string $fullPath, string $downloadName, bool $inline = false): void
    {
        if (!is_file($fullPath)) {
            abort(404);
        }
        $mime = class_exists('finfo') ? ((new finfo(FILEINFO_MIME_TYPE))->file($fullPath) ?: 'application/octet-stream') : 'application/octet-stream';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        readfile($fullPath);
        exit;
    }
}
