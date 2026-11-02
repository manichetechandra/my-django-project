<?php
/**
 * فضاء الطالب (students/views.py): التسجيل، الدخول، الطلبات، الإشعارات، الملف الشخصي، البطاقة.
 */

/** ملف الطالب للمستخدم الحالي أو إعادة توجيه (مثل try: request.user.student_profile) */
function require_student(): StudentProfile
{
    require_login();
    $student = current_student();
    if (!$student) {
        if (is_ajax()) {
            json_response(['success' => false, 'error' => 'لا يوجد ملف طالب']);
        }
        flash('error', 'لا يوجد ملف طالب لهذا المستخدم');
        redirect_to('homepage:index');
    }
    return $student;
}

/** طلب يخص الطالب الحالي أو 404 */
function student_application_or_404(StudentProfile $student, $pk): StudentApplication
{
    $app = StudentApplication::firstWhere('id = ? AND student_id = ?', [(int) $pk, (int) $student['id']]);
    if (!$app) {
        abort(404);
    }
    return $app;
}

// ====================================================================== التسجيل والدخول

function student_register(): void
{
    if (is_authenticated()) {
        redirect_to('students:dashboard');
    }
    $form = student_registration_form(is_post() ? $_POST : null);
    if (is_post()) {
        if ($form->is_valid()) {
            $d = $form->cleaned;
            try {
                $user = DB::transaction(function () use ($d) {
                    $user = User::create([
                        'username' => $d['registration_number'],
                        'email'    => $d['email'],
                        'password' => Auth::hashPassword($d['password1']),
                    ]);
                    $student = StudentProfile::create([
                        'user_id'             => (int) $user['id'],
                        'registration_number' => $d['registration_number'],
                        'full_name_arabic'    => $d['full_name_arabic'],
                        'birth_date'          => $d['birth_date'],
                        'birth_place'         => $d['birth_place'],
                        'gender'              => $d['gender'],
                        'phone'               => $d['phone'],
                        'address'             => $d['address'],
                        'wilaya'              => $d['wilaya'],
                        'faculty'             => $d['faculty'],
                        'department'          => $d['department'],
                        'current_year'        => $d['current_year'],
                        'is_verified'         => 1,
                    ]);
                    $student->notify('مرحباً بك!', 'مرحباً ' . $d['full_name_arabic'] . '، تم إنشاء حسابك بنجاح. يمكنك الآن تقديم الطلبات.', '/students/dashboard/');
                    return $user;
                });
                Auth::login($user);
                flash('success', 'تم إنشاء حسابك بنجاح! مرحباً بك');
                redirect_to('students:dashboard');
            } catch (PDOException $e) {
                error_log((string) $e);
                flash('error', 'حدث خطأ أثناء إنشاء الحساب. حاول مرة أخرى.');
            }
        } else {
            foreach ($form->all_errors() as $errors) {
                foreach ($errors as $error) {
                    flash('error', $error);
                }
            }
        }
    }
    render('students/register', ['form' => $form, 'page_title' => 'تسجيل طالب جديد']);
}

function student_login(): void
{
    never_cache();
    if (is_authenticated()) {
        redirect_to(is_staff() ? 'students:admin_dashboard' : 'students:dashboard');
    }
    $form = student_login_form(is_post() ? $_POST : null);
    if (is_post() && $form->is_valid()) {
        $d = $form->cleaned;
        $user = Auth::attempt($d['username'], $d['password']);
        if ($user) {
            $next = $_GET['next'] ?? '';
            $student = $user->student_profile();
            if ($user['is_staff'] || $user['is_superuser']) {
                Auth::login($user, (bool) $d['remember_me']);
                flash('success', 'مرحباً! تم تسجيل الدخول كمشرف');
                redirect(safe_next($next, url('students:admin_dashboard')));
            }
            if ($student) {
                if (!$student['is_active']) {
                    flash('error', 'حسابك غير نشط. يرجى الاتصال بالإدارة');
                    redirect_to('students:login');
                }
                Auth::login($user, (bool) $d['remember_me']);
                flash('success', 'مرحباً ' . $student['full_name_arabic'] . '!');
                redirect(safe_next($next, url('students:dashboard')));
            }
            Auth::login($user, (bool) $d['remember_me']);
            flash('warning', 'لا يوجد ملف طالب. يمكنك الوصول للوحة الإدارة فقط');
            redirect_to('homepage:index');
        }
        flash('error', 'اسم المستخدم أو كلمة المرور غير صحيحة');
    }
    render('students/login', ['form' => $form, 'page_title' => 'تسجيل الدخول']);
}

function student_logout(): void
{
    never_cache();
    Auth::logout();
    flash('success', 'تم تسجيل الخروج بنجاح');
    redirect_to('students:login');
}

// ====================================================================== لوحة الطالب

function student_dashboard(): void
{
    never_cache();
    $student = require_student();
    $sid = (int) $student['id'];
    $counts = DB::one("SELECT
            SUM(status = 'pending') AS pending, SUM(status = 'under_review') AS under_review,
            SUM(status = 'approved') AS approved, SUM(status = 'rejected') AS rejected
        FROM student_applications WHERE student_id = ?", [$sid]);

    render('students/dashboard', [
        'student'                     => $student,
        'pending_count'               => (int) $counts['pending'],
        'under_review_count'          => (int) $counts['under_review'],
        'approved_count'              => (int) $counts['approved'],
        'rejected_count'              => (int) $counts['rejected'],
        'recent_applications'         => StudentApplication::where('student_id = ?', [$sid], 'submitted_at DESC', 5),
        'unread_notifications'        => StudentNotification::where('student_id = ? AND is_read = 0', [$sid], 'created_at DESC', 5),
        'available_application_types' => ApplicationType::active(),
        'page_title'                  => 'لوحة التحكم',
    ]);
}

// ====================================================================== الطلبات

function student_create_application($appTypeId = null): void
{
    never_cache();
    $student = require_student();
    $selectedType = null;
    $initial = [];
    if ($appTypeId !== null) {
        $selectedType = ApplicationType::firstWhere('id = ? AND is_active = 1', [(int) $appTypeId]);
        if (!$selectedType) {
            abort(404);
        }
        $initial['application_type'] = $selectedType['id'];
    }

    $form = application_submission_form(is_post() ? $_POST : null, $initial);
    if (is_post()) {
        // التحقق من الوثائق المرفقة قبل حفظ أي شيء
        $docs = [];
        $files = $_FILES['documents'] ?? null;
        $names = (array) ($_POST['document_names'] ?? []);
        if ($files && !is_array($files['name'])) {
            // حقل ملف واحد بدون [] => نحوله إلى قائمة
            $files = array_map(fn($v) => [$v], $files);
        }
        if ($files) {
            foreach ($files['name'] as $i => $name) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                $err = Upload::validate($file, Upload::DOCUMENTS, (float) config('uploads.max_document_mb', 10));
                if ($err) {
                    $form->add_error(null, $name . ': ' . $err);
                }
                $docName = trim((string) ($names[$i] ?? ''));
                $docs[] = [$file, mb_substr($docName !== '' ? $docName : $name, 0, 200)];
            }
        }
        if ($form->is_valid()) {
            $saved = [];
            try {
                $app = DB::transaction(function () use ($form, $student, $docs, &$saved) {
                    $app = StudentApplication::create([
                        'student_id'          => (int) $student['id'],
                        'application_type_id' => (int) $form->cleaned['application_type'],
                        'details'             => $form->cleaned['details'],
                        'status'              => 'pending',
                    ]);
                    foreach ($docs as [$file, $docName]) {
                        $path = Upload::store($file, 'documents/' . date('Y/m'), Upload::DOCUMENTS, (float) config('uploads.max_document_mb', 10), true);
                        $saved[] = $path;
                        ApplicationDocument::create([
                            'application_id' => (int) $app['id'],
                            'document_name'  => $docName,
                            'document_file'  => $path,
                        ]);
                    }
                    $student->notify('تم تقديم طلبك بنجاح',
                        'تم تقديم طلب ' . $app->application_type['name'] . ' برقم ' . $app['application_number'],
                        '/students/applications/' . $app['id'] . '/');
                    return $app;
                });
                flash('success', 'تم تقديم طلبك بنجاح! رقم الطلب: ' . $app['application_number']);
                redirect_to('students:application_detail', $app['id']);
            } catch (RuntimeException | PDOException $e) {
                foreach ($saved as $p) {
                    Upload::delete($p, true);
                }
                error_log((string) $e);
                flash('error', 'حدث خطأ: ' . ($e instanceof RuntimeException ? $e->getMessage() : 'تعذر حفظ الطلب'));
            }
        } else {
            foreach (array_merge($form->non_field_errors(), ...array_values($form->all_errors())) as $error) {
                flash('error', $error);
            }
        }
    }

    render('students/create_application', [
        'form'              => $form,
        'application_types' => ApplicationType::active(),
        'page_title'        => 'تقديم طلب جديد',
        'selected_type'     => $selectedType,
    ]);
}

function student_my_applications(): void
{
    never_cache();
    $student = require_student();
    $sid = (int) $student['id'];
    $where = 'student_id = ?';
    $params = [$sid];
    $statusFilter = query('status');
    if ($statusFilter !== '') {
        $where .= ' AND status = ?';
        $params[] = $statusFilter;
    }
    $typeFilter = query('type');
    if ($typeFilter !== '') {
        $where .= ' AND application_type_id = ?';
        $params[] = (int) $typeFilter;
    }
    $counts = DB::one("SELECT COUNT(*) AS total,
            SUM(status = 'pending') AS pending, SUM(status = 'under_review') AS under_review,
            SUM(status = 'approved') AS approved, SUM(status = 'rejected') AS rejected
        FROM student_applications WHERE student_id = ?", [$sid]);

    render('students/my_applications', [
        'student'            => $student,
        'applications'       => StudentApplication::where($where, $params, 'submitted_at DESC'),
        'total_count'        => (int) $counts['total'],
        'pending_count'      => (int) $counts['pending'],
        'under_review_count' => (int) $counts['under_review'],
        'approved_count'     => (int) $counts['approved'],
        'rejected_count'     => (int) $counts['rejected'],
        'application_types'  => ApplicationType::active(),
        'status_filter'      => $statusFilter,
        'type_filter'        => $typeFilter,
        'page_title'         => 'طلباتي',
    ]);
}

function student_application_detail($pk): void
{
    never_cache();
    $student = require_student();
    $application = student_application_or_404($student, $pk);
    render('students/application_detail', [
        'student'     => $student,
        'application' => $application,
        'documents'   => $application['documents'],
        'page_title'  => 'تفاصيل الطلب - ' . $application['application_number'],
    ]);
}

function student_cancel_application($pk): void
{
    $student = require_student();
    $application = student_application_or_404($student, $pk);
    if ($application['status'] === 'pending') {
        $application->change_status('cancelled');
        flash('success', 'تم إلغاء الطلب بنجاح');
    } else {
        flash('error', 'لا يمكن إلغاء هذا الطلب');
    }
    redirect_to('students:my_applications');
}

function student_application_print($pk): void
{
    never_cache();
    $application = is_staff() ? StudentApplication::findOrFail($pk) : student_application_or_404(require_student(), $pk);
    $student = $application['student'];
    render('students/application_print', [
        'student'     => $student,
        'application' => $application,
        'documents'   => $application['documents'],
    ]);
}

function student_download_application_pdf($pk): void
{
    require_student();
    flash('info', 'استخدم "طباعة" من المتصفح واختر "حفظ كـ PDF"');
    redirect_to('students:application_print', $pk);
}

function student_print_all_applications(): void
{
    never_cache();
    $student = require_student();
    render('students/print_all', [
        'student'      => $student,
        'applications' => StudentApplication::where('student_id = ?', [(int) $student['id']], 'submitted_at DESC'),
        'page_title'   => 'طباعة كل الطلبات',
    ]);
}

function student_export_all_pdf(): void
{
    require_student();
    flash('info', 'استخدم "طباعة" من المتصفح واختر "حفظ كـ PDF"');
    redirect_to('students:print_all_applications');
}

/** تحميل وثيقة مرفقة — لصاحب الطلب أو للموظفين فقط */
function student_document_download($pk): void
{
    require_login();
    $doc = ApplicationDocument::findOrFail($pk);
    $app = $doc->application;
    $student = current_student();
    $isOwner = $app && $student && (int) $app['student_id'] === (int) $student['id'];
    if (!$isOwner && !is_staff()) {
        abort(403);
    }
    $path = $doc->full_path();
    if (!$path) {
        abort(404, 'الملف غير موجود على الخادم');
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $name = slugify($doc['document_name']) ?: 'document';
    Upload::send($path, $name . '.' . $ext, in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true));
}

// ====================================================================== الإشعارات

function student_notifications_list(): void
{
    never_cache();
    $student = require_student();
    $sid = (int) $student['id'];
    $filterType = query('filter', 'all') ?: 'all';
    $where = 'student_id = ?';
    if ($filterType === 'unread') {
        $where .= ' AND is_read = 0';
    } elseif ($filterType === 'read') {
        $where .= ' AND is_read = 1';
    }
    $c = DB::one('SELECT COUNT(*) AS total, SUM(is_read = 0) AS unread, SUM(is_read = 1) AS readc FROM student_notifications WHERE student_id = ?', [$sid]);
    render('students/notifications_list', [
        'student'       => $student,
        'notifications' => StudentNotification::where($where, [$sid], 'created_at DESC, id DESC'),
        'total_count'   => (int) $c['total'],
        'unread_count'  => (int) $c['unread'],
        'read_count'    => (int) $c['readc'],
        'filter_type'   => $filterType,
        'page_title'    => 'إشعاراتي',
    ]);
}

function student_mark_notification_read($pk): void
{
    $student = require_student();
    $n = StudentNotification::firstWhere('id = ? AND student_id = ?', [(int) $pk, (int) $student['id']]);
    if (!$n) {
        json_response(['success' => false, 'error' => 'الإشعار غير موجود'], 404);
    }
    $n->mark_as_read();
    json_response(['success' => true, 'unread_count' => $student->get_unread_notifications_count()]);
}

function student_mark_all_notifications_read(): void
{
    $student = require_student();
    $count = DB::query('UPDATE student_notifications SET is_read = 1, read_at = ? WHERE student_id = ? AND is_read = 0', [now(), (int) $student['id']])->rowCount();
    json_response(['success' => true, 'count' => $count, 'message' => "تم تمييز $count إشعار كمقروء"]);
}

function student_delete_notification($pk): void
{
    $student = require_student();
    $n = StudentNotification::firstWhere('id = ? AND student_id = ?', [(int) $pk, (int) $student['id']]);
    if (!$n) {
        json_response(['success' => false, 'error' => 'الإشعار غير موجود'], 404);
    }
    $n->delete();
    json_response(['success' => true, 'unread_count' => $student->get_unread_notifications_count(), 'message' => 'تم حذف الإشعار']);
}

function student_delete_read_notifications(): void
{
    $student = require_student();
    $count = DB::delete('student_notifications', 'student_id = ? AND is_read = 1', [(int) $student['id']]);
    json_response(['success' => true, 'count' => $count, 'message' => "تم حذف $count إشعار مقروء"]);
}

/** واجهة JSON يستدعيها شريط التنقل دورياً لتحديث الإشعارات */
function student_notifications_api(): void
{
    never_cache();
    if (!is_authenticated()) {
        json_response(['success' => false, 'error' => 'غير مسجل الدخول'], 401);
    }
    $student = current_student();
    if (!$student) {
        json_response(['success' => false, 'error' => 'لا يوجد ملف طالب']);
    }
    $data = [];
    foreach ($student->get_recent_notifications() as $n) {
        $created = to_datetime($n['created_at']);
        $data[] = [
            'id'         => (int) $n['id'],
            'title'      => $n['title'],
            'message'    => $n['message'],
            'link'       => $n['link'] ? link_url($n['link']) : '',
            'is_read'    => (bool) $n['is_read'],
            'icon'       => $n->get_icon(),
            'color'      => $n->get_color(),
            'created_at' => $created ? $created->format(DATE_ATOM) : '',
            'time_ago'   => timesince($n['created_at']),
        ];
    }
    json_response([
        'success'       => true,
        'unread_count'  => $student->get_unread_notifications_count(),
        'total_count'   => StudentNotification::count('student_id = ?', [(int) $student['id']]),
        'notifications' => $data,
    ]);
}

// ====================================================================== الملف الشخصي

function student_profile_view(): void
{
    never_cache();
    $student = require_student();
    $sid = (int) $student['id'];
    render('students/profile/view', [
        'student'            => $student,
        'total_applications' => StudentApplication::count('student_id = ?', [$sid]),
        'approved_count'     => StudentApplication::count("student_id = ? AND status = 'approved'", [$sid]),
        'pending_count'      => StudentApplication::count("student_id = ? AND status = 'pending'", [$sid]),
        'page_title'         => 'معلوماتي الشخصية',
    ]);
}

function student_profile_edit(): void
{
    never_cache();
    $student = require_student();
    $user = current_user();
    $profileForm = student_profile_form(is_post() ? $_POST : null, is_post() ? $_FILES : null, $student);
    $emailForm = user_email_form(is_post() ? $_POST : null, $user);

    if (is_post()) {
        $okProfile = $profileForm->is_valid();
        $okEmail = $emailForm->is_valid();
        if ($okProfile && $okEmail) {
            $d = $profileForm->cleaned;
            try {
                $data = [];
                foreach (['full_name_arabic', 'birth_date', 'birth_place', 'gender', 'phone', 'address', 'wilaya', 'faculty', 'department', 'current_year'] as $f) {
                    $data[$f] = $d[$f];
                }
                if (!empty($d['photo'])) {
                    $newPhoto = Upload::store($d['photo'], 'students/photos', Upload::IMAGES, (float) config('uploads.max_image_mb', 5));
                    Upload::delete($student['photo']);
                    $data['photo'] = $newPhoto;
                }
                $data['updated_at'] = now();
                $student->update($data);
                $user->update(['email' => $emailForm->cleaned['email']]);
                $student->notify('تم تحديث معلوماتك ✅', 'تم تحديث معلوماتك الشخصية بنجاح.', '/students/profile/', 'success');
                flash('success', '✅ تم تحديث معلوماتك بنجاح!');
                redirect_to('students:profile_view');
            } catch (RuntimeException $e) {
                flash('error', '❌ حدث خطأ: ' . $e->getMessage());
            }
        } else {
            foreach ([$profileForm, $emailForm] as $f) {
                foreach ($f->all_errors() as $errors) {
                    foreach ($errors as $error) {
                        flash('error', '❌ ' . $error);
                    }
                }
            }
        }
    }

    render('students/profile/edit', [
        'student'      => $student,
        'profile_form' => $profileForm,
        'email_form'   => $emailForm,
        'page_title'   => 'تعديل معلوماتي',
    ]);
}

function student_change_password(): void
{
    never_cache();
    $student = require_student();
    $user = current_user();
    $form = password_change_form(is_post() ? $_POST : null);
    if (is_post()) {
        if ($form->is_valid()) {
            if (!$user->check_password($form->cleaned['old_password'])) {
                flash('error', '❌ كلمة المرور القديمة غير صحيحة');
            } else {
                $user->update(['password' => Auth::hashPassword($form->cleaned['new_password1'])]);
                Auth::refreshSession($user);
                $student->notify('تم تغيير كلمة المرور 🔒', 'تم تغيير كلمة المرور الخاصة بك بنجاح.', null, 'success');
                flash('success', '✅ تم تغيير كلمة المرور بنجاح!');
                redirect_to('students:profile_view');
            }
        } else {
            foreach (array_merge($form->non_field_errors(), ...array_values($form->all_errors())) as $error) {
                flash('error', '❌ ' . $error);
            }
        }
    }
    render('students/profile/change_password', ['student' => $student, 'form' => $form, 'page_title' => 'تغيير كلمة المرور']);
}

function student_delete_profile_image(): void
{
    $student = require_student();
    if (empty($student['photo'])) {
        json_response(['success' => false, 'error' => 'لا توجد صورة لحذفها']);
    }
    Upload::delete($student['photo']);
    $student->update(['photo' => null, 'updated_at' => now()]);
    json_response(['success' => true, 'message' => 'تم حذف الصورة بنجاح']);
}

function student_card(): void
{
    never_cache();
    $student = require_student();
    render('students/profile/student_card', [
        'student'    => $student,
        'qr_data'    => 'Student:' . $student['registration_number'] . '|Name:' . $student['full_name_arabic'] . '|Faculty:' . $student['faculty'],
        'page_title' => 'بطاقة الطالب الرقمية',
    ]);
}
