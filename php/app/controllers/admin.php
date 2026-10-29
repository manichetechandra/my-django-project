<?php
/**
 * لوحة الإدارة العامة /admin/ (بديل Django admin).
 * عامة: تقرأ إعدادات كل جدول من app/admin_config.php
 */

/** كل الموارد المتاحة للمستخدم الحالي */
function admin_resources(): array
{
    static $all = null;
    if ($all === null) {
        $all = require APP_PATH . '/admin_config.php';
    }
    return array_filter($all, fn($r) => empty($r['superuser_only']) || is_superuser());
}

function admin_resource(string $key): array
{
    $resources = admin_resources();
    if (!isset($resources[$key])) {
        abort(404, 'هذا القسم غير موجود في لوحة الإدارة');
    }
    $r = $resources[$key] + [
        'can_add' => true, 'can_edit' => true, 'can_delete' => true, 'list' => [], 'links' => [], 'editable' => [],
        'filters' => [], 'search' => [], 'actions' => [], 'columns' => [], 'inlines' => [], 'per_page' => 30,
        'order_by' => 'id DESC',
    ];
    $r['key'] = $key;
    if (!$r['links']) {
        $r['links'] = [$r['list'][0] ?? 'id'];
    }
    return $r;
}

/** يتطلب أن يكون المستخدم موظفاً — مع صفحة دخول خاصة إن لم يكن مسجلاً */
function admin_guard(): User
{
    set_lang(default_lang()); // لوحة الإدارة عربية دائماً
    never_cache();
    $u = current_user();
    if (!$u) {
        redirect(url('students:login') . '?next=' . rawurlencode(request_path() . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'])));
    }
    if (!is_staff()) {
        abort(403, 'لوحة الإدارة مخصصة للموظفين فقط.');
    }
    return $u;
}

function admin_render(string $view, array $data = []): void
{
    $data['admin_menu'] = admin_menu();
    render('admin/' . $view, $data);
}

/** القائمة الجانبية مجمّعة حسب القسم */
function admin_menu(): array
{
    $menu = [];
    foreach (admin_resources() as $key => $r) {
        if (!empty($r['hidden'])) {
            continue;
        }
        $badge = isset($r['badge']) ? (int) ($r['badge'])() : 0;
        $menu[$r['group']][] = ['key' => $key, 'label' => $r['plural'], 'icon' => $r['icon'], 'badge' => $badge];
    }
    return $menu;
}

// ====================================================================== الصفحات

function admin_index(): void
{
    admin_guard();
    $counts = [];
    foreach (admin_resources() as $key => $r) {
        $counts[$key] = $r['model']::count();
    }
    $stats = SiteStatistics::get_stats();
    admin_render('index', [
        'page_title'      => 'لوحة الإدارة',
        'resources'       => admin_resources(),
        'counts'          => $counts,
        'stats'           => $stats,
        'pending_apps'    => StudentApplication::count("status = 'pending'"),
        'unread_messages' => ContactMessage::count('is_read = 0'),
        'today_visits'    => SiteVisit::count('visited_at >= ?', [date('Y-m-d 00:00:00')]),
        'latest_messages' => ContactMessage::where('1', [], 'created_at DESC', 5),
        'latest_apps'     => StudentApplication::where('1', [], 'submitted_at DESC', 5),
    ]);
}

function admin_login(): void
{
    if (is_staff()) {
        redirect_to('admin:index');
    }
    redirect(url('students:login') . '?next=' . rawurlencode('/admin/'));
}

function admin_logout(): void
{
    Auth::logout();
    flash('success', 'تم تسجيل الخروج بنجاح');
    redirect_to('students:login');
}

function admin_password(): void
{
    $user = admin_guard();
    $form = new Form([
        'old_password'  => ['type' => 'password', 'label' => 'كلمة المرور الحالية', 'attrs' => FC],
        'new_password1' => ['type' => 'password', 'label' => 'كلمة المرور الجديدة', 'attrs' => FC, 'help_text' => '8 أحرف على الأقل، ولا تكون أرقاماً فقط'],
        'new_password2' => ['type' => 'password', 'label' => 'تأكيد كلمة المرور الجديدة', 'attrs' => FC],
    ], is_post() ? $_POST : null);
    $form->clean = function (Form $f) use ($user) {
        if (!$user->check_password($f->cleaned['old_password'])) {
            $f->add_error('old_password', 'كلمة المرور الحالية غير صحيحة');
        }
        if ($f->cleaned['new_password1'] !== $f->cleaned['new_password2']) {
            $f->add_error('new_password2', 'كلمتا المرور غير متطابقتين');
        }
        foreach (password_errors($f->cleaned['new_password1'], (string) $user['username']) as $err) {
            $f->add_error('new_password1', $err);
        }
    };
    if (is_post() && $form->is_valid()) {
        $user->update(['password' => Auth::hashPassword($form->cleaned['new_password1'])]);
        Auth::refreshSession($user);
        flash('success', 'تم تغيير كلمة المرور بنجاح');
        redirect_to('admin:index');
    }
    admin_render('password', ['page_title' => 'تغيير كلمة المرور', 'form' => $form]);
}

// ====================================================================== قائمة السجلات

function admin_list(string $key): void
{
    admin_guard();
    $r = admin_resource($key);
    $model = $r['model'];
    $table = $model::table();

    if (is_post()) {
        admin_handle_list_post($r);
    }

    [$where, $params, $activeFilters] = admin_list_filters($r);
    $q = query('q');
    if ($q !== '' && $r['search']) {
        $parts = [];
        foreach ($r['search'] as $s) {
            if (strpos($s, '>') !== false) {
                [$fk, $target] = explode('>', $s, 2);
                [$t, $c] = explode('.', $target, 2);
                $parts[] = "`$fk` IN (SELECT id FROM `$t` WHERE `$c` LIKE ?)";
            } else {
                $parts[] = "`$s` LIKE ?";
            }
            $params[] = DB::like($q);
        }
        $where .= ' AND (' . implode(' OR ', $parts) . ')';
    }
    $page = $model::paginate($where, $params, $r['order_by'], (int) $r['per_page']);

    admin_render('list', [
        'page_title'     => $r['plural'],
        'r'              => $r,
        'page'           => $page,
        'q'              => $q,
        'filter_options' => admin_filter_options($r),
        'active_filters' => $activeFilters,
        'total_all'      => $model::count(),
    ]);
}

/** شروط الفلاتر من ?f_<field>=value */
function admin_list_filters(array $r): array
{
    $where = '1';
    $params = [];
    $active = [];
    foreach ($r['filters'] as $f) {
        $v = query('f_' . $f);
        if ($v === '') {
            continue;
        }
        $active[$f] = $v;
        $type = $r['fields'][$f]['type'] ?? (in_array($f, ['created_at', 'updated_at', 'uploaded_at', 'visited_at', 'submitted_at'], true) ? 'datetime' : 'text');
        if (in_array($type, ['date', 'datetime', 'readonly'], true) && in_array($v, ['today', '7d', 'month', 'year'], true)) {
            $from = ['today' => date('Y-m-d 00:00:00'), '7d' => date('Y-m-d H:i:s', strtotime('-7 days')), 'month' => date('Y-m-01 00:00:00'), 'year' => date('Y-01-01 00:00:00')][$v];
            $where .= " AND `$f` >= ?";
            $params[] = $from;
        } elseif ($v === '__null') {
            $where .= " AND `$f` IS NULL";
        } else {
            $where .= " AND `$f` = ?";
            $params[] = $v;
        }
    }
    return [$where, $params, $active];
}

/** خيارات كل فلتر */
function admin_filter_options(array $r): array
{
    $out = [];
    foreach ($r['filters'] as $f) {
        $spec = $r['fields'][$f] ?? ['type' => 'datetime', 'label' => admin_column_label($r, $f)];
        $type = $spec['type'];
        $label = $spec['label'] ?? $f;
        if ($type === 'bool') {
            $opts = ['1' => 'نعم', '0' => 'لا'];
        } elseif ($type === 'select' || isset($spec['choices'])) {
            $opts = $r['model']::choiceMap($spec['choices'] ?? $f);
        } elseif ($type === 'fk') {
            $opts = admin_fk_options($spec);
        } elseif (in_array($type, ['date', 'datetime', 'readonly'], true)) {
            $opts = ['today' => 'اليوم', '7d' => 'آخر 7 أيام', 'month' => 'هذا الشهر', 'year' => 'هذه السنة'];
        } else {
            $opts = [];
        }
        $out[$f] = ['label' => $label, 'options' => $opts];
    }
    return $out;
}

/** إجراءات جماعية + حفظ التعديلات المباشرة من القائمة */
function admin_handle_list_post(array $r): void
{
    $model = $r['model'];
    $table = $model::table();
    $back = url('admin:list', $r['key']) . ($_GET ? '?' . http_build_query($_GET) : '');

    // حفظ list_editable
    if (isset($_POST['save_list']) && $r['editable']) {
        $count = 0;
        foreach ((array) ($_POST['edit'] ?? []) as $id => $values) {
            $obj = $model::find((int) $id);
            if (!$obj) {
                continue;
            }
            $data = [];
            foreach ($r['editable'] as $f) {
                if (!array_key_exists($f, (array) $values)) {
                    continue;
                }
                $type = $r['fields'][$f]['type'] ?? 'text';
                $v = $values[$f];
                $data[$f] = $type === 'bool' ? (int) !empty($v) : ($type === 'int' ? (int) $v : trim((string) $v));
            }
            if ($data) {
                $obj->update($data);
                $count++;
            }
        }
        flash('success', "تم حفظ التعديلات ($count سجل)");
        redirect($back);
    }

    $action = (string) ($_POST['action'] ?? '');
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    if (!$ids) {
        flash('warning', 'يجب تحديد سجل واحد على الأقل لتنفيذ الإجراء.');
        redirect($back);
    }
    if ($action === 'delete' && $r['can_delete']) {
        $objects = $model::where('id IN (' . DB::in($ids) . ')', $ids);
        $n = 0;
        foreach ($objects as $obj) {
            if ($r['key'] === 'users' && (int) $obj['id'] === (int) current_user()['id']) {
                flash('error', 'لا يمكنك حذف حسابك الخاص.');
                continue;
            }
            admin_delete_object($r, $obj);
            $n++;
        }
        flash('success', "تم حذف $n سجل بنجاح.");
    } elseif (isset($r['actions'][$action])) {
        $a = $r['actions'][$action];
        $sets = [];
        $params = [];
        foreach ($a['set'] as $col => $val) {
            $sets[] = "`$col` = ?";
            $params[] = $val;
        }
        $n = DB::query("UPDATE `$table` SET " . implode(', ', $sets) . ' WHERE id IN (' . DB::in($ids) . ')', array_merge($params, $ids))->rowCount();
        flash('success', sprintf($a['message'], count($ids)));
    } else {
        flash('warning', 'لم يتم اختيار إجراء.');
    }
    redirect($back);
}

// ====================================================================== الإضافة والتعديل

function admin_add(string $key): void
{
    admin_guard();
    $r = admin_resource($key);
    if (!$r['can_add']) {
        abort(403);
    }
    admin_form($r, null);
}

function admin_edit(string $key, $id): void
{
    admin_guard();
    $r = admin_resource($key);
    $obj = $r['model']::findOrFail($id);
    if (isset($r['on_view']) && !is_post()) {
        ($r['on_view'])($obj);
    }
    admin_form($r, $obj);
}

function admin_form(array $r, ?Model $obj): void
{
    $isNew = $obj === null;
    $values = [];
    $errors = [];
    $readonly = !$isNew && !$r['can_edit'];

    if (is_post() && !$readonly) {
        [$data, $errors, $newFiles, $oldFiles] = admin_collect_form($r, $obj);
        if (!$errors) {
            $data = admin_auto_translate($r, $data);
            $model = $r['model'];
            $old = $obj ? $obj->toArray() : null;
            try {
                DB::transaction(function () use (&$obj, $model, $data, $r) {
                    if ($obj === null) {
                        $obj = new $model($data);
                    } else {
                        foreach ($data as $k => $v) {
                            $obj[$k] = $v;
                        }
                    }
                    $obj->save();
                    admin_save_inlines($r, $obj);
                });
            } catch (Throwable $e) {
                foreach ($newFiles as [$path, $private]) {
                    Upload::delete($path, $private);
                }
                throw $e;
            }
            // حذف الملفات القديمة التي استُبدلت (إن لم تُستعمل في سجل آخر)
            foreach ($oldFiles as [$col, $path, $private]) {
                admin_delete_file_if_unused($r['model']::table(), $col, $path, $private);
            }
            if (isset($r['on_save'])) {
                ($r['on_save'])($obj, $old);
            }
            flash('success', ($isNew ? 'تمت إضافة "' : 'تم حفظ "') . $obj . '" بنجاح.');
            if (isset($_POST['_addanother'])) {
                redirect_to('admin:add', $r['key']);
            }
            if (isset($_POST['_continue'])) {
                redirect_to('admin:edit', $r['key'], $obj['id']);
            }
            redirect_to('admin:list', $r['key']);
        }
        $values = $_POST;
        flash('error', 'يرجى تصحيح الأخطاء أدناه.');
    } else {
        foreach ($r['fields'] as $f => $spec) {
            if ($obj) {
                $values[$f] = $obj[$f];
            } else {
                $values[$f] = admin_default_value($spec);
            }
        }
        // حقول الترجمة (title_fr، title_en ...) كما هي في قاعدة البيانات
        $raw = $obj ? $obj->toArray() : [];
        foreach (admin_translatable($r) as $f) {
            foreach (admin_other_langs() as $lang) {
                $values[$f . '_' . $lang] = $raw[$f . '_' . $lang] ?? '';
            }
        }
    }

    $inlineRows = [];
    if ($obj) {
        foreach ($r['inlines'] as $i => $inl) {
            $order = $inl['order'] ? '`' . $inl['order'] . '`, id' : 'id';
            $inlineRows[$i] = $inl['model']::where('`' . $inl['fk'] . '` = ?', [(int) $obj['id']], $order);
        }
    }

    admin_render('form', [
        'page_title'  => $isNew ? 'إضافة ' . $r['label'] : ($readonly ? 'عرض ' : 'تعديل ') . $r['label'],
        'r'           => $r,
        'obj'         => $obj,
        'values'      => $values,
        'errors'      => $errors,
        'readonly'    => $readonly,
        'inline_rows' => $inlineRows,
    ]);
}

function admin_default_value(array $spec)
{
    $d = $spec['default'] ?? null;
    if ($d === 'now') {
        return $spec['type'] === 'date' ? date('Y-m-d') : now();
    }
    if ($d === 'current_user') {
        return current_user()['id'] ?? null;
    }
    return $d;
}

/**
 * التحقق من بيانات النموذج.
 * يرجع [البيانات, الأخطاء, الملفات الجديدة, الملفات القديمة المستبدلة]
 */
function admin_collect_form(array $r, ?Model $obj): array
{
    $data = [];
    $errors = [];
    $newFiles = [];
    $oldFiles = [];
    $model = $r['model'];
    $table = $model::table();

    foreach ($r['fields'] as $f => $spec) {
        $type = $spec['type'];
        $required = $spec['required'] ?? !in_array($type, ['bool', 'readonly'], true);
        $label = $spec['label'] ?? $f;
        if ($type === 'readonly') {
            continue;
        }
        if ($type === 'bool') {
            $data[$f] = empty($_POST[$f]) ? 0 : 1;
            continue;
        }
        if ($type === 'image' || $type === 'file') {
            $file = $_FILES[$f] ?? null;
            $current = $obj ? (string) $obj[$f] : '';
            $hasNew = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hasNew) {
                $ext = $spec['ext'] ?? ($type === 'image' ? Upload::IMAGES : Upload::FILES);
                $max = (float) config($spec['max_mb'] ?? ($type === 'image' ? 'uploads.max_image_mb' : 'uploads.max_file_mb'), 10);
                $err = Upload::validate($file, $ext, $max);
                if ($err) {
                    $errors[$f] = $err;
                    continue;
                }
                $dir = strtr($spec['dir'] ?? 'files', ['{Y}' => date('Y'), '{m}' => date('m')]);
                $path = Upload::store($file, $dir, $ext, $max);
                $newFiles[] = [$path, false];
                $data[$f] = $path;
                if ($current !== '') {
                    $oldFiles[] = [$f, $current, false];
                }
            } elseif (!empty($_POST[$f . '__clear']) && !$required) {
                $data[$f] = !empty($spec['nullable']) ? null : '';
                if ($current !== '') {
                    $oldFiles[] = [$f, $current, false];
                }
            } elseif ($required && $current === '') {
                $errors[$f] = 'هذا الحقل مطلوب.';
            }
            continue;
        }

        $raw = $_POST[$f] ?? '';
        $v = is_string($raw) ? trim($raw) : '';
        if ($type === 'password') {
            $v = is_string($raw) ? $raw : '';
            if ($v === '') {
                if (!$obj) {
                    $errors[$f] = 'كلمة المرور مطلوبة للمستخدم الجديد.';
                }
                continue;
            }
            if ($pe = password_errors($v, (string) ($_POST['username'] ?? ''))) {
                $errors[$f] = implode(' ', $pe);
                continue;
            }
            $data[$f] = Auth::hashPassword($v);
            continue;
        }
        if ($type === 'slug' && $v === '') {
            // يُولَّد تلقائياً من الحقل المصدر عند الحفظ
            $data[$f] = '';
            continue;
        }
        if ($v === '') {
            if ($required) {
                $errors[$f] = 'هذا الحقل مطلوب.';
                continue;
            }
            if (!empty($spec['nullable'])) {
                $data[$f] = null;
            } elseif ($type === 'int') {
                $data[$f] = (int) ($spec['default'] ?? 0);
            } elseif (in_array($type, ['decimal', 'date', 'datetime', 'fk'], true)) {
                $data[$f] = null;
            } else {
                $data[$f] = '';
            }
            continue;
        }
        if (isset($spec['max']) && mb_strlen($v) > $spec['max']) {
            $errors[$f] = 'الحد الأقصى ' . $spec['max'] . ' حرفاً.';
            continue;
        }
        switch ($type) {
            case 'email':
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $errors[$f] = 'أدخل عنوان بريد إلكتروني صالح.';
                }
                break;
            case 'url':
                if (!filter_var($v, FILTER_VALIDATE_URL)) {
                    $errors[$f] = 'أدخل رابطاً صالحاً يبدأ بـ http:// أو https://';
                }
                break;
            case 'int':
                if (filter_var($v, FILTER_VALIDATE_INT) === false) {
                    $errors[$f] = 'أدخل رقماً صحيحاً.';
                } elseif (isset($spec['min']) && (int) $v < $spec['min']) {
                    $errors[$f] = 'القيمة يجب أن تكون ' . $spec['min'] . ' أو أكثر.';
                }
                $v = (int) $v;
                break;
            case 'decimal':
                $v = str_replace(',', '.', $v);
                if (!is_numeric($v)) {
                    $errors[$f] = 'أدخل رقماً.';
                } elseif ((isset($spec['min']) && $v < $spec['min']) || (isset($spec['max_value']) && $v > $spec['max_value'])) {
                    $errors[$f] = 'القيمة خارج المجال المسموح.';
                }
                break;
            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v) {
                    $errors[$f] = 'أدخل تاريخاً صالحاً.';
                }
                break;
            case 'datetime':
                $d = DateTime::createFromFormat('Y-m-d\TH:i', $v) ?: DateTime::createFromFormat('Y-m-d H:i:s', $v) ?: DateTime::createFromFormat('Y-m-d H:i', $v);
                if (!$d) {
                    $errors[$f] = 'أدخل تاريخاً ووقتاً صالحين.';
                } else {
                    $v = $d->format('Y-m-d H:i:s');
                }
                break;
            case 'select':
                $choices = $model::choiceMap($spec['choices'] ?? $f);
                if (!array_key_exists($v, $choices)) {
                    $errors[$f] = 'اختر قيمة صحيحة.';
                }
                break;
            case 'fk':
                if (!$spec['model']::find((int) $v)) {
                    $errors[$f] = 'اختر قيمة صحيحة.';
                }
                $v = (int) $v;
                break;
            case 'color':
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                    $errors[$f] = 'لون غير صالح.';
                }
                break;
            case 'slug':
                $v = strtolower($v);
                if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $v)) {
                    $errors[$f] = 'الرابط يجب أن يحتوي على حروف وأرقام وشرطات فقط (بدون مسافات).';
                }
                break;
        }
        if (!isset($errors[$f]) && isset($spec['pattern']) && !preg_match($spec['pattern'], (string) $v)) {
            $errors[$f] = $spec['pattern_message'] ?? 'قيمة غير صالحة.';
        }
        if (!isset($errors[$f]) && !empty($spec['unique'])) {
            $exists = DB::value("SELECT COUNT(*) FROM `$table` WHERE `$f` = ?" . ($obj ? ' AND id <> ?' : ''), $obj ? [$v, (int) $obj['id']] : [$v]);
            if ($exists) {
                $errors[$f] = 'هذه القيمة مستعملة من قبل في سجل آخر.';
            }
        }
        if (!isset($errors[$f])) {
            $data[$f] = $v;
        }
    }

    // الترجمات (title_fr، title_en ...): اختيارية — الفارغة تُترجم تلقائياً عند الحفظ أو يظهر مكانها النص العربي
    foreach (admin_translatable($r) as $f) {
        foreach (admin_other_langs() as $lang) {
            $col = $f . '_' . $lang;
            $v = trim((string) ($_POST[$col] ?? ''));
            $max = admin_translation_max($r['fields'][$f]);
            if ($max && mb_strlen($v) > $max) {
                $errors[$col] = 'الحد الأقصى ' . $max . ' حرفاً.';
                continue;
            }
            $data[$col] = $v === '' ? null : $v;
        }
    }

    // عند وجود أخطاء نحذف الملفات التي رُفعت للتو
    if ($errors) {
        foreach ($newFiles as [$path, $private]) {
            Upload::delete($path, $private);
        }
        $newFiles = [];
    }
    return [$data, $errors, $newFiles, $oldFiles];
}

/** حفظ الصور/الوثائق المرتبطة (inlines) */
function admin_save_inlines(array $r, Model $obj): void
{
    foreach ($r['inlines'] as $i => $inl) {
        $m = $inl['model'];
        $private = !empty($inl['private']);
        // تعديل/حذف الموجود
        foreach ((array) ($_POST['inline'][$i] ?? []) as $rowId => $vals) {
            $row = $m::firstWhere('id = ? AND `' . $inl['fk'] . '` = ?', [(int) $rowId, (int) $obj['id']]);
            if (!$row) {
                continue;
            }
            if (!empty($vals['_delete'])) {
                $path = (string) $row[$inl['file']];
                $row->delete();
                admin_delete_file_if_unused($m::table(), $inl['file'], $path, $private);
                continue;
            }
            $upd = [];
            foreach ($inl['text'] as $col => $lbl) {
                if (isset($vals[$col])) {
                    $upd[$col] = mb_substr(trim((string) $vals[$col]), 0, 200);
                }
                foreach (in_array($col, $m::translatable(), true) ? admin_other_langs() : [] as $lang) {
                    if (isset($vals[$col . '_' . $lang])) {
                        $t = mb_substr(trim((string) $vals[$col . '_' . $lang]), 0, 300);
                        $upd[$col . '_' . $lang] = $t === '' ? null : $t;
                    }
                }
            }
            if ($inl['order'] && isset($vals[$inl['order']])) {
                $upd[$inl['order']] = (int) $vals[$inl['order']];
            }
            if ($upd) {
                $row->update($upd);
            }
        }
        // إضافة ملفات جديدة
        $files = $_FILES['inline_new_' . $i] ?? null;
        if ($files && is_array($files['name'])) {
            $ext = $inl['ext'] ?? Upload::IMAGES;
            $max = (float) config($inl['kind'] === 'document' ? 'uploads.max_document_mb' : 'uploads.max_image_mb', 10);
            foreach ($files['name'] as $k => $name) {
                if (($files['error'][$k] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = ['name' => $name, 'type' => $files['type'][$k], 'tmp_name' => $files['tmp_name'][$k], 'error' => $files['error'][$k], 'size' => $files['size'][$k]];
                $err = Upload::validate($file, $ext, $max);
                if ($err) {
                    flash('warning', $name . ': ' . $err);
                    continue;
                }
                $dir = strtr($inl['dir'], ['{Y}' => date('Y'), '{m}' => date('m')]);
                $path = Upload::store($file, $dir, $ext, $max, $private);
                $data = [$inl['fk'] => (int) $obj['id'], $inl['file'] => $path];
                foreach ($inl['text'] as $col => $lbl) {
                    $data[$col] = $inl['kind'] === 'document' ? mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 200) : '';
                }
                $m::create($data);
            }
        }
        admin_auto_translate_inline($inl, $obj);
    }
}

// ====================================================================== الترجمة (الحقول _fr و _en)

/** الحقول العربية التي لها أعمدة ترجمة في هذا القسم (من $translatable في النموذج) */
function admin_translatable(array $r): array
{
    return array_values(array_filter($r['model']::translatable(), fn($f) => isset($r['fields'][$f]) && $r['fields'][$f]['type'] !== 'readonly'));
}

/** لغات الترجمة (كل اللغات ما عدا العربية): ['fr', 'en'] */
function admin_other_langs(): array
{
    return array_values(array_diff(array_keys(languages()), [default_lang()]));
}

/** الطول الأقصى لعمود الترجمة: أوسع من العربي كما في database/migrations/2026_10_i18n.sql */
function admin_translation_max(array $spec): ?int
{
    if (!isset($spec['max'])) {
        return null;
    }
    return [50 => 100, 100 => 200, 200 => 300, 300 => 500, 500 => 800][$spec['max']] ?? min($spec['max'] * 2, 1000);
}

/** حالة ترجمة سجل في قائمة السجلات: ['fr' => true (مكتمل) أو false (ينقصه حقل), 'en' => ...] */
function admin_translation_status(array $r, Model $obj): array
{
    $raw = $obj->toArray();
    $status = [];
    foreach (admin_other_langs() as $lang) {
        $status[$lang] = true;
        foreach (admin_translatable($r) as $f) {
            if (trim((string) ($raw[$f] ?? '')) !== '' && trim((string) ($raw[$f . '_' . $lang] ?? '')) === '') {
                $status[$lang] = false;
                break;
            }
        }
    }
    return $status;
}

/** ترجمة حقول الفرنسية والإنجليزية الفارغة تلقائياً قبل حفظ السجل */
function admin_auto_translate(array $r, array $data): array
{
    if (!config('translation.auto_on_save', true) || !Translator::enabled()) {
        return $data;
    }
    $texts = [];
    foreach (admin_translatable($r) as $f) {
        $ar = trim((string) ($data[$f] ?? ''));
        foreach (admin_other_langs() as $lang) {
            if ($ar !== '' && !isset($data[$f . '_' . $lang])) {
                $texts[$f] = $ar;
            }
        }
    }
    if (!$texts) {
        return $data;
    }
    try {
        $tr = Translator::translate($texts);
        $n = 0;
        foreach ($texts as $f => $_) {
            foreach (admin_other_langs() as $lang) {
                $col = $f . '_' . $lang;
                if (!isset($data[$col]) && $tr[$lang][$f] !== '') {
                    $max = admin_translation_max($r['fields'][$f]);
                    $data[$col] = $max ? mb_substr($tr[$lang][$f], 0, $max) : $tr[$lang][$f];
                    $n++;
                }
            }
        }
        if ($n) {
            flash('info', 'الترجمة التلقائية: مُلئت حقول الفرنسية والإنجليزية الفارغة (' . $n . ') عبر ' . Translator::$usedProvider . '. يمكنك مراجعتها وتعديلها من صفحة السجل.');
        }
    } catch (Throwable $e) {
        flash('warning', 'تعذرت الترجمة التلقائية: ' . $e->getMessage() . ' — سيظهر النص العربي في النسختين الفرنسية والإنجليزية إلى أن تضيف الترجمة.');
    }
    return $data;
}

/** ترجمة تعليقات الصور المرفقة الفارغة (طلب واحد لكل صور السجل) */
function admin_auto_translate_inline(array $inl, Model $obj): void
{
    $m = $inl['model'];
    $cols = array_values(array_intersect(array_keys($inl['text']), $m::translatable()));
    if (!$cols || !config('translation.auto_on_save', true) || !Translator::enabled()) {
        return;
    }
    $texts = [];
    $rows = [];
    foreach ($m::where('`' . $inl['fk'] . '` = ?', [(int) $obj['id']]) as $row) {
        $raw = $row->toArray();
        foreach ($cols as $col) {
            foreach (admin_other_langs() as $lang) {
                if (trim((string) $raw[$col]) !== '' && trim((string) ($raw[$col . '_' . $lang] ?? '')) === '') {
                    $texts[$row['id'] . ':' . $col] = $raw[$col];
                    $rows[$row['id']] = $row;
                }
            }
        }
    }
    if (!$texts) {
        return;
    }
    try {
        $tr = Translator::translate($texts);
        foreach ($texts as $key => $_) {
            [$id, $col] = explode(':', $key, 2);
            $upd = [];
            foreach (admin_other_langs() as $lang) {
                if (trim((string) ($rows[$id]->toArray()[$col . '_' . $lang] ?? '')) === '' && $tr[$lang][$key] !== '') {
                    $upd[$col . '_' . $lang] = mb_substr($tr[$lang][$key], 0, 300);
                }
            }
            if ($upd) {
                $rows[$id]->update($upd);
            }
        }
    } catch (Throwable $e) {
        flash('warning', 'تعذرت ترجمة تعليقات الصور: ' . $e->getMessage());
    }
}

/** زر «ترجمة تلقائية» في نموذج السجل (AJAX): يستقبل النصوص العربية ويرجع الفرنسية والإنجليزية */
function admin_translate(): void
{
    admin_guard();
    $texts = json_decode((string) ($_POST['texts'] ?? ''), true);
    if (!is_array($texts) || !$texts) {
        json_response(['success' => false, 'error' => 'لا توجد نصوص عربية للترجمة.'], 400);
    }
    if (!Translator::enabled()) {
        json_response(['success' => false, 'error' => 'الترجمة التلقائية غير مفعّلة. أضف مفتاح Gemini المجاني في config.php (translation.gemini_key) أو فعّل MyMemory.']);
    }
    $clean = [];
    foreach (array_slice($texts, 0, 80, true) as $k => $v) {
        $clean[(string) $k] = mb_substr((string) $v, 0, 30000);
    }
    try {
        $tr = Translator::translate($clean);
        json_response(['success' => true, 'provider' => Translator::$usedProvider] + $tr);
    } catch (Throwable $e) {
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

/** صفحة «ترجمة المحتوى القديم»: تترجم السجلات التي تنقصها ترجمة على دفعات صغيرة */
function admin_translate_all(): void
{
    admin_guard();
    if (!is_superuser()) {
        abort(403, 'هذه الصفحة للمدير العام فقط.');
    }
    if (is_post()) {
        @set_time_limit(300);
        $skip = json_decode((string) ($_POST['skip'] ?? '{}'), true) ?: [];
        Translator::$patient = true;
        try {
            $res = Translator::fillMissing(3, false, $skip);
            json_response(['success' => true, 'provider' => Translator::$usedProvider, 'remaining' => array_sum(Translator::missingCounts())] + $res);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    $labels = [];
    foreach (admin_resources() as $key => $res) {
        $labels[$res['model']::table()] = $res['plural'];
    }
    admin_render('translate_all', [
        'page_title' => 'ترجمة المحتوى إلى الفرنسية والإنجليزية',
        'counts'     => Translator::missingCounts(),
        'labels'     => $labels,
        'enabled'    => Translator::enabled(),
        'providers'  => Translator::providers(),
    ]);
}

// ====================================================================== الحذف

function admin_delete(string $key, $id): void
{
    admin_guard();
    $r = admin_resource($key);
    if (!$r['can_delete']) {
        abort(403);
    }
    $obj = $r['model']::findOrFail($id);
    if (is_post()) {
        if ($key === 'users' && (int) $obj['id'] === (int) current_user()['id']) {
            flash('error', 'لا يمكنك حذف حسابك الخاص.');
            redirect_to('admin:edit', $key, $id);
        }
        $name = (string) $obj;
        admin_delete_object($r, $obj);
        flash('success', 'تم حذف "' . $name . '" بنجاح.');
        redirect_to('admin:list', $key);
    }
    admin_render('delete', ['page_title' => 'حذف ' . $r['label'], 'r' => $r, 'obj' => $obj]);
}

function admin_delete_object(array $r, Model $obj): void
{
    $table = $r['model']::table();
    $files = [];
    foreach ($r['fields'] as $f => $spec) {
        if (in_array($spec['type'], ['image', 'file'], true) && !empty($obj[$f])) {
            $files[] = [$f, (string) $obj[$f], false];
        }
    }
    foreach ($r['inlines'] as $inl) {
        foreach ($inl['model']::where('`' . $inl['fk'] . '` = ?', [(int) $obj['id']]) as $row) {
            $files[] = [null, (string) $row[$inl['file']], !empty($inl['private']), $inl['model']::table(), $inl['file']];
        }
    }
    $obj->delete();
    foreach ($files as $f) {
        if ($f[0] === null) {
            admin_delete_file_if_unused($f[3], $f[4], $f[1], $f[2]);
        } else {
            admin_delete_file_if_unused($table, $f[0], $f[1], $f[2]);
        }
    }
}

/** حذف ملف من القرص إذا لم يعد أي سجل يستعمله */
function admin_delete_file_if_unused(string $table, string $col, string $path, bool $private): void
{
    if ($path === '') {
        return;
    }
    if ((int) DB::value("SELECT COUNT(*) FROM `$table` WHERE `$col` = ?", [$path]) === 0) {
        Upload::delete($path, $private);
    }
}

// ====================================================================== مساعدات العرض

function admin_fk_options(array $spec): array
{
    static $cache = [];
    $m = $spec['model'];
    $display = $spec['display'] ?? 'id';
    $key = $m . '.' . $display;
    if (!isset($cache[$key])) {
        $cache[$key] = [];
        foreach (DB::all('SELECT id, `' . $display . '` AS d FROM `' . $m::table() . '` ORDER BY `' . $display . '` LIMIT 2000') as $row) {
            $cache[$key][$row['id']] = $row['d'];
        }
    }
    return $cache[$key];
}

function admin_column_label(array $r, string $col): string
{
    $fixed = ['id' => '#', 'created_at' => 'تاريخ الإنشاء', 'updated_at' => 'آخر تحديث', 'uploaded_at' => 'تاريخ الرفع'];
    return $r['columns'][$col]['label'] ?? $r['fields'][$col]['label'] ?? $fixed[$col] ?? $col;
}

/** قيمة الخلية في القائمة (HTML آمن) */
function admin_cell(array $r, Model $obj, string $col): string
{
    if (isset($r['columns'][$col])) {
        return e(($r['columns'][$col]['value'])($obj));
    }
    $spec = $r['fields'][$col] ?? ['type' => in_array($col, ['created_at', 'updated_at', 'uploaded_at'], true) ? 'datetime' : 'text'];
    $v = $obj[$col];
    $type = $spec['type'];
    if ($type === 'readonly' && isset($spec['model'])) {
        $type = 'fk';
    }
    if ($type === 'readonly' && preg_match('/(_at|_date|last_login|date_joined)$/', $col)) {
        $type = 'datetime';
    }
    switch ($type) {
        case 'bool':
            return $v ? '<i class="fa fa-check-circle text-success" title="نعم"></i>' : '<i class="fa fa-times-circle text-danger" title="لا"></i>';
        case 'image':
            return $v ? '<img src="' . e(media_url($v)) . '" class="admin-thumb" alt="">' : '<span class="text-muted">—</span>';
        case 'file':
            return $v ? '<i class="fa fa-file-o"></i> ' . e(basename((string) $v)) : '—';
        case 'select':
            $label = $r['model']::choiceMap($spec['choices'] ?? $col)[(string) $v] ?? $v;
            return '<span class="label label-default admin-choice admin-choice-' . e($v) . '">' . e($label) . '</span>';
        case 'fk':
            if ($v === null || $v === '') {
                return '<span class="text-muted">—</span>';
            }
            $opts = admin_fk_options($spec);
            return e($opts[$v] ?? ('#' . $v));
        case 'date':
            return e(format_date($v, 'd/m/Y'));
        case 'datetime':
            return $v ? e(format_date($v, 'd/m/Y H:i')) : '<span class="text-muted">—</span>';
        case 'color':
            return '<span class="admin-color" style="background:' . e($v) . '"></span> ' . e($v);
        case 'icon':
            return '<i class="fa ' . e($v) . ' fa-lg"></i> <small class="text-muted">' . e($v) . '</small>';
        case 'textarea':
            return e(truncatewords((string) $v, 12));
        default:
            if ($col === 'file_size') {
                return e(human_filesize($v));
            }
            return e(truncatechars((string) $v, 80));
    }
}

/** حقل الإدخال في نموذج الإضافة/التعديل */
function admin_input(array $r, string $f, array $spec, $value, bool $readonly, ?Model $obj): string
{
    $type = $spec['type'];
    $name = e($f);
    $id = 'f_' . $name;
    $req = ($spec['required'] ?? !in_array($type, ['bool', 'readonly'], true)) && !in_array($type, ['image', 'file', 'password', 'slug'], true);
    $reqAttr = $req ? ' required' : '';
    $dis = $readonly ? ' disabled' : '';
    $val = $value === null ? '' : (string) $value;

    if ($type === 'readonly') {
        if ($val === '') {
            return '<p class="form-control-static text-muted">—</p>';
        }
        if (isset($spec['model'])) {
            $opts = admin_fk_options($spec);
            return '<p class="form-control-static">' . e($opts[$val] ?? $val) . '</p>';
        }
        if (isset($spec['choices'])) {
            return '<p class="form-control-static">' . e($r['model']::choiceMap($spec['choices'])[$val] ?? $val) . '</p>';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $val)) {
            return '<p class="form-control-static">' . e(format_date($val, 'd/m/Y H:i')) . '</p>';
        }
        if (!empty($spec['multiline'])) {
            return '<div class="form-control-static admin-multiline">' . nl2br(e($val)) . '</div>';
        }
        return '<p class="form-control-static">' . e($val) . '</p>';
    }

    switch ($type) {
        case 'textarea':
            return '<textarea name="' . $name . '" id="' . $id . '" class="form-control" rows="' . (int) ($spec['rows'] ?? 4) . '"' . $reqAttr . $dis . '>' . e($val) . '</textarea>';
        case 'bool':
            return '<div class="checkbox"><label><input type="checkbox" name="' . $name . '" id="' . $id . '" value="1"' . ($val !== '' && $val !== '0' ? ' checked' : '') . $dis . '> ' . e($spec['label']) . '</label></div>';
        case 'select':
            $html = '<select name="' . $name . '" id="' . $id . '" class="form-control"' . $reqAttr . $dis . '>';
            if (!$req) {
                $html .= '<option value="">---------</option>';
            }
            foreach ($r['model']::choiceMap($spec['choices'] ?? $f) as $k => $label) {
                $html .= '<option value="' . e($k) . '"' . ((string) $k === $val ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            return $html . '</select>';
        case 'fk':
            $html = '<select name="' . $name . '" id="' . $id . '" class="form-control"' . $reqAttr . $dis . '><option value="">---------</option>';
            foreach (admin_fk_options($spec) as $k => $label) {
                $html .= '<option value="' . e($k) . '"' . ((string) $k === $val ? ' selected' : '') . '>' . e($label) . '</option>';
            }
            return $html . '</select>';
        case 'image':
        case 'file':
            $html = '';
            if ($val !== '' && !$readonly && $obj) {
                $current = $obj[$f];
                if ($current) {
                    $html .= '<div class="admin-current-file">';
                    $html .= $type === 'image'
                        ? '<a href="' . e(media_url($current)) . '" target="_blank"><img src="' . e(media_url($current)) . '" class="admin-preview" alt=""></a>'
                        : '<a href="' . e(media_url($current)) . '" target="_blank"><i class="fa fa-file-o"></i> ' . e(basename((string) $current)) . '</a>';
                    $html .= '<div class="text-muted small">الحالي: ' . e($current) . '</div>';
                    if (!($spec['required'] ?? true)) {
                        $html .= '<label class="small"><input type="checkbox" name="' . $name . '__clear" value="1"> حذف الملف الحالي</label>';
                    }
                    $html .= '</div>';
                }
            }
            $accept = $type === 'image' ? 'image/*' : '.' . implode(',.', $spec['ext'] ?? Upload::FILES);
            return $html . '<input type="file" name="' . $name . '" id="' . $id . '" class="form-control" accept="' . e($accept) . '"' . ((($spec['required'] ?? true) && $val === '') ? ' required' : '') . $dis . '>';
        case 'password':
            return '<input type="password" name="' . $name . '" id="' . $id . '" class="form-control" autocomplete="new-password"' . ($obj ? '' : ' required') . $dis . '>';
        case 'date':
            return '<input type="date" name="' . $name . '" id="' . $id . '" class="form-control" value="' . e(substr($val, 0, 10)) . '"' . $reqAttr . $dis . '>';
        case 'datetime':
            $dt = $val !== '' && to_datetime($val) ? to_datetime($val)->format('Y-m-d\TH:i') : '';
            return '<input type="datetime-local" name="' . $name . '" id="' . $id . '" class="form-control" value="' . e($dt) . '"' . $reqAttr . $dis . '>';
        case 'int':
            return '<input type="number" step="1" name="' . $name . '" id="' . $id . '" class="form-control" value="' . e($val) . '"' . $reqAttr . $dis . '>';
        case 'decimal':
            return '<input type="number" step="0.01" name="' . $name . '" id="' . $id . '" class="form-control" value="' . e($val) . '"' . $reqAttr . $dis . '>';
        case 'color':
            return '<input type="color" name="' . $name . '" id="' . $id . '" class="form-control admin-color-input" value="' . e($val ?: '#3498db') . '"' . $dis . '>';
        case 'icon':
            return '<div class="input-group"><span class="input-group-addon"><i class="fa ' . e($val) . '" data-icon-preview="' . $id . '"></i></span>'
                . '<input type="text" name="' . $name . '" id="' . $id . '" class="form-control" dir="ltr" value="' . e($val) . '"' . $reqAttr . $dis . ' data-icon-input></div>';
        case 'slug':
            return '<input type="text" name="' . $name . '" id="' . $id . '" class="form-control" dir="ltr" value="' . e($val) . '"' . $dis
                . ' data-slug-from="f_' . e($spec['from'] ?? 'title') . '"' . ($obj ? ' data-slug-locked="1"' : '') . '>';
        case 'email':
        case 'url':
            return '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" class="form-control" dir="ltr" value="' . e($val) . '"' . $reqAttr . $dis . '>';
        default:
            return '<input type="text" name="' . $name . '" id="' . $id . '" class="form-control" value="' . e($val) . '"'
                . (isset($spec['max']) ? ' maxlength="' . (int) $spec['max'] . '"' : '') . $reqAttr . $dis . '>';
    }
}

/** رابط القائمة مع تعديل معاملات GET */
function admin_list_url(string $key, array $changes): string
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        }
    }
    unset($q['page']);
    if (isset($changes['page'])) {
        $q['page'] = $changes['page'];
    }
    return url('admin:list', $key) . ($q ? '?' . http_build_query($q) : '');
}
