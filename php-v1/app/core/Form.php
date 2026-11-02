<?php
/**
 * نماذج HTML مع التحقق من البيانات (بديل Django Forms).
 *
 *   $form = new Form([
 *       'email' => ['type' => 'email', 'label' => 'البريد', 'required' => true, 'attrs' => ['class' => 'form-control']],
 *   ], $_POST);
 *   if (is_post() && $form->is_valid()) { $data = $form->cleaned; }
 *
 * في القالب:  <?= $form->widget('email') ?>  <?= $form->errors_html('email') ?>
 *
 * أنواع الحقول: text, email, password, textarea, select, date, number, decimal, checkbox, file, hidden
 * خيارات: required, label, help_text, attrs, choices, initial, max_length, min_length, pattern + pattern_message,
 *         min, max, validators => [fn($value, $form) => 'رسالة خطأ' أو null]
 */
class Form
{
    public array $fields;
    public array $data;
    public array $files;
    public bool $bound;
    public array $cleaned = [];
    private array $errors = [];
    private array $nonFieldErrors = [];
    private bool $validated = false;

    /** دالة تحقق عامة بعد تحقق الحقول (مثل clean() في Django) */
    public $clean = null;

    public function __construct(array $fields, ?array $data = null, ?array $files = null, array $initial = [])
    {
        $this->fields = $fields;
        $this->bound = $data !== null;
        $this->data = $data ?? [];
        $this->files = $files ?? [];
        foreach ($initial as $name => $value) {
            if (isset($this->fields[$name])) {
                $this->fields[$name]['initial'] = $value;
            }
        }
    }

    // ------------------------------------------------------------------ التحقق

    public function is_valid(): bool
    {
        if (!$this->bound) {
            return false;
        }
        if (!$this->validated) {
            $this->validate();
        }
        return !$this->errors && !$this->nonFieldErrors;
    }

    private function validate(): void
    {
        $this->validated = true;
        foreach ($this->fields as $name => $f) {
            $type = $f['type'] ?? 'text';
            $required = $f['required'] ?? true;
            if ($type === 'file') {
                $file = $this->files[$name] ?? null;
                $has = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (!$has) {
                    if ($required) {
                        $this->add_error($name, 'هذا الحقل مطلوب.');
                    }
                    $this->cleaned[$name] = null;
                    continue;
                }
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $this->add_error($name, Upload::errorMessage((int) $file['error']));
                    continue;
                }
                $value = $file;
            } elseif ($type === 'checkbox') {
                $value = !empty($this->data[$name]);
            } else {
                $raw = $this->data[$name] ?? '';
                $value = is_string($raw) ? trim($raw) : '';
                if ($type === 'password') {
                    $value = is_string($raw) ? $raw : '';
                }
                if ($value === '') {
                    if ($required) {
                        $this->add_error($name, 'هذا الحقل مطلوب.');
                    }
                    $this->cleaned[$name] = ($type === 'select' && !empty($f['nullable'])) || in_array($type, ['date', 'number', 'decimal'], true) ? null : '';
                    continue;
                }
                $error = $this->checkType($name, $f, $value);
                if ($error !== null) {
                    $this->add_error($name, $error);
                    continue;
                }
                if ($type === 'number') {
                    $value = (int) $value;
                }
            }
            foreach ($f['validators'] ?? [] as $validator) {
                $msg = $validator($value, $this);
                if ($msg) {
                    $this->add_error($name, $msg);
                    continue 2;
                }
            }
            $this->cleaned[$name] = $value;
        }
        if ($this->clean && !$this->errors) {
            ($this->clean)($this);
        }
    }

    private function checkType(string $name, array $f, string $value): ?string
    {
        $type = $f['type'] ?? 'text';
        if (isset($f['max_length']) && mb_strlen($value) > $f['max_length']) {
            return 'تأكد أن هذه القيمة تحتوي على ' . $f['max_length'] . ' حرفًا على الأكثر (حاليًا ' . mb_strlen($value) . ').';
        }
        if (isset($f['min_length']) && mb_strlen($value) < $f['min_length']) {
            return 'تأكد أن هذه القيمة تحتوي على ' . $f['min_length'] . ' حرفًا على الأقل.';
        }
        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'أدخل عنوان بريد إلكتروني صالح.';
                }
                break;
            case 'select':
                $valid = array_map('strval', array_keys($this->choiceMap($name)));
                if (!in_array($value, $valid, true)) {
                    return 'اختر خياراً صالحاً. ' . $value . ' ليست واحدة من الخيارات المتاحة.';
                }
                break;
            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $value);
                if (!$d || $d->format('Y-m-d') !== $value) {
                    return 'أدخل تاريخاً صالحاً.';
                }
                break;
            case 'number':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return 'أدخل رقماً صحيحاً.';
                }
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    return 'أدخل رقماً.';
                }
                break;
        }
        if (isset($f['min']) && is_numeric($value) && $value < $f['min']) {
            return 'تأكد أن هذه القيمة أكبر من أو تساوي ' . $f['min'] . '.';
        }
        if (isset($f['max']) && is_numeric($value) && $value > $f['max']) {
            return 'تأكد أن هذه القيمة أصغر من أو تساوي ' . $f['max'] . '.';
        }
        if (isset($f['pattern']) && !preg_match($f['pattern'], $value)) {
            return $f['pattern_message'] ?? 'أدخل قيمة صالحة.';
        }
        return null;
    }

    public function add_error(?string $field, string $message): void
    {
        if ($field === null || !isset($this->fields[$field])) {
            $this->nonFieldErrors[] = $message;
        } else {
            $this->errors[$field][] = $message;
        }
        unset($this->cleaned[$field]);
    }

    public function errors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function all_errors(): array
    {
        return $this->errors;
    }

    public function has_errors(): bool
    {
        return $this->errors || $this->nonFieldErrors;
    }

    public function non_field_errors(): array
    {
        return $this->nonFieldErrors;
    }

    // ------------------------------------------------------------------ العرض

    public function value(string $field)
    {
        if ($this->bound) {
            $v = $this->data[$field] ?? null;
            return is_string($v) ? $v : null;
        }
        return $this->fields[$field]['initial'] ?? null;
    }

    public function label(string $field): string
    {
        return $this->fields[$field]['label'] ?? $field;
    }

    public function help_text(string $field): string
    {
        return $this->fields[$field]['help_text'] ?? '';
    }

    public function id(string $field): string
    {
        return 'id_' . $field;
    }

    private function choiceMap(string $field): array
    {
        $choices = $this->fields[$field]['choices'] ?? [];
        if (is_callable($choices)) {
            $choices = $choices();
        }
        return $choices;
    }

    private function attrs(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $k => $v) {
            if ($v === false || $v === null) {
                continue;
            }
            $html .= $v === true ? ' ' . e($k) : ' ' . e($k) . '="' . e($v) . '"';
        }
        return $html;
    }

    /** HTML الحقل (مكافئ {{ form.field }}) */
    public function widget(string $field): string
    {
        $f = $this->fields[$field];
        $type = $f['type'] ?? 'text';
        $attrs = ['name' => $field, 'id' => $this->id($field)] + ($f['attrs'] ?? []);
        if (($f['required'] ?? true) && $type !== 'checkbox' && !isset($attrs['required'])) {
            $attrs['required'] = true;
        }
        if ($this->errors($field)) {
            $attrs['aria-invalid'] = 'true';
        }
        $value = $this->value($field);
        switch ($type) {
            case 'textarea':
                $attrs += ['cols' => 40, 'rows' => 10];
                return '<textarea' . $this->attrs($attrs) . ">\n" . e($value) . '</textarea>';
            case 'select':
                $html = '<select' . $this->attrs($attrs) . '>';
                $choices = $this->choiceMap($field);
                if (isset($f['empty_label'])) {
                    $choices = ['' => $f['empty_label']] + $choices;
                }
                foreach ($choices as $val => $label) {
                    $sel = (string) $val === (string) $value ? ' selected' : '';
                    $html .= '<option value="' . e($val) . '"' . $sel . '>' . e($label) . '</option>';
                }
                return $html . '</select>';
            case 'checkbox':
                $checked = $this->bound ? !empty($this->data[$field]) : !empty($f['initial']);
                return '<input type="checkbox"' . $this->attrs($attrs + ['checked' => $checked]) . '>';
            case 'password':
                return '<input type="password"' . $this->attrs($attrs) . '>';
            case 'file':
                unset($attrs['required']);
                if (($f['required'] ?? true)) {
                    $attrs['required'] = true;
                }
                return '<input type="file"' . $this->attrs($attrs) . '>';
            case 'decimal':
                return '<input type="number"' . $this->attrs($attrs + ['step' => 'any', 'value' => $value]) . '>';
            default:
                $htmlType = in_array($type, ['email', 'date', 'number', 'hidden', 'url', 'tel'], true) ? $type : 'text';
                if (isset($f['max_length']) && !isset($attrs['maxlength'])) {
                    $attrs['maxlength'] = $f['max_length'];
                }
                return '<input type="' . $htmlType . '"' . $this->attrs($attrs + ['value' => $value]) . '>';
        }
    }

    /** قائمة أخطاء الحقل بصيغة Django <ul class="errorlist"> */
    public function errors_html(string $field): string
    {
        $errs = $this->errors($field);
        if (!$errs) {
            return '';
        }
        return '<ul class="errorlist">' . implode('', array_map(fn($m) => '<li>' . e($m) . '</li>', $errs)) . '</ul>';
    }

    public function non_field_errors_html(): string
    {
        if (!$this->nonFieldErrors) {
            return '';
        }
        return '<ul class="errorlist nonfield">' . implode('', array_map(fn($m) => '<li>' . e($m) . '</li>', $this->nonFieldErrors)) . '</ul>';
    }
}
