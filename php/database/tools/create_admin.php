<?php
/**
 * إنشاء مدير عام أو إعادة تعيين كلمة مروره (بديل: python manage.py createsuperuser)
 *
 *   php database/tools/create_admin.php                 (يسألك عن الاسم وكلمة المرور)
 *   php database/tools/create_admin.php admin "MyPass#2025"
 *
 * في XAMPP على Windows (من داخل مجلد المشروع):
 *   C:\xampp\php\php.exe database\tools\create_admin.php admin "MyPass#2025"
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
$password = $argv[2] ?? ask('كلمة المرور: ', true);

if ($username === '' || !preg_match('/^[\w.@+-]+$/u', $username)) {
    fwrite(STDERR, "اسم مستخدم غير صالح.\n");
    exit(1);
}
if ($errors = password_errors($password, $username)) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$user = User::firstWhere('username = ?', [$username]);
if ($user) {
    $user->update(['password' => Auth::hashPassword($password), 'is_staff' => 1, 'is_superuser' => 1, 'is_active' => 1]);
    echo "✔ تم تحديث كلمة مرور \"$username\" ومنحه صلاحيات المدير العام.\n";
} else {
    User::create(['username' => $username, 'password' => Auth::hashPassword($password), 'is_staff' => 1, 'is_superuser' => 1, 'is_active' => 1]);
    echo "✔ تم إنشاء المدير العام \"$username\".\n";
}
echo "ادخل إلى لوحة الإدارة من: /admin/\n";
