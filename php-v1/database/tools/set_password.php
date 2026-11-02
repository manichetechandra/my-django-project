<?php
/**
 * تعيين كلمة مرور جديدة لأي حساب موجود (طالب أو موظف أو مدير) دون تغيير صلاحياته.
 * (لإنشاء مدير عام جديد استعمل create_admin.php)
 *
 *   php database/tools/set_password.php                      (يسألك عن الاسم وكلمة المرور)
 *   php database/tools/set_password.php hicham41 "MyPass#2025"
 *
 * في XAMPP على Windows (من داخل مجلد المشروع):
 *   C:\xampp\php\php.exe database\tools\set_password.php hicham41 "MyPass#2025"
 *
 * تغيير كلمة المرور يُغلق تلقائياً كل الجلسات المفتوحة لهذا الحساب.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

function ask(string $q, bool $hidden = false): string
{
    echo $q;
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $v = trim((string) fgets(STDIN));
        system('stty echo');
        echo PHP_EOL;
        return $v;
    }
    return trim((string) fgets(STDIN));
}

$username = $argv[1] ?? ask('اسم المستخدم: ');
$user = User::firstWhere('username = ?', [$username]);
if (!$user) {
    fwrite(STDERR, "لا يوجد حساب باسم \"$username\".\n");
    exit(1);
}
$password = $argv[2] ?? ask('كلمة المرور الجديدة: ', true);
if ($errors = password_errors($password, $username)) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}
$user->update(['password' => Auth::hashPassword($password)]);
$role = $user['is_superuser'] ? 'مدير عام' : ($user['is_staff'] ? 'موظف' : 'طالب');
echo "✔ تم تعيين كلمة مرور جديدة للحساب \"$username\" ($role).\n";
echo $user->is_staff_account() ? "الدخول من واجهة الإدارة: /admin/login/\n" : "الدخول من فضاء الطالب: /students/login/\n";
