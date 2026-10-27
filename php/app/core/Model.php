<?php
/**
 * نموذج بسيط على طريقة Active Record (بديل Django ORM).
 *
 * - كل صف من قاعدة البيانات يصبح كائناً يمكن قراءته كمصفوفة:  $news['title']
 * - العلاقات (ForeignKey) تُحمَّل عند الحاجة:                  $app['application_type']['name']
 * - get_FIELD_display() تعمل تلقائياً مع $choices مثل Django:   $app->get_status_display()
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    /** اسم الجدول */
    protected static string $table = '';

    /** خيارات الحقول (choices) : ['status' => ['pending' => 'قيد الانتظار', ...]] */
    protected static array $choices = [];

    /** علاقات ForeignKey : 'author' => [User::class, 'author_id'] */
    protected static array $belongsTo = [];

    /** علاقات عكسية : 'images' => [NewsImage::class, 'news_id', 'ORDER BY `order`'] */
    protected static array $hasMany = [];

    /** الحقول المنطقية (تُحوَّل إلى true/false) */
    protected static array $booleans = [];

    /** قيم افتراضية عند الإنشاء (مثل default= في Django) */
    protected static array $defaults = [];

    /** حقول تأخذ وقت الإنشاء تلقائياً (auto_now_add) */
    protected static array $createdFields = [];

    /** حقول تأخذ وقت التعديل تلقائياً (auto_now) */
    protected static array $updatedFields = [];

    protected array $attributes = [];
    protected array $loaded = [];

    final public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        foreach (static::$booleans as $b) {
            if (array_key_exists($b, $this->attributes) && $this->attributes[$b] !== null) {
                $this->attributes[$b] = (bool) $this->attributes[$b];
            }
        }
    }

    // ------------------------------------------------------------------ قراءة الحقول

    public function offsetExists($key): bool
    {
        return $this->offsetGet($key) !== null;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        if (array_key_exists($key, $this->loaded)) {
            return $this->loaded[$key];
        }
        if (isset(static::$belongsTo[$key])) {
            [$class, $fk] = static::$belongsTo[$key];
            $id = $this->attributes[$fk] ?? null;
            return $this->loaded[$key] = $id === null ? null : $class::find($id);
        }
        if (isset(static::$hasMany[$key])) {
            [$class, $fk, $order] = static::$hasMany[$key] + [2 => ''];
            return $this->loaded[$key] = $class::query(
                'SELECT * FROM `' . $class::table() . "` WHERE `$fk` = ? $order",
                [$this->attributes['id'] ?? 0]
            );
        }
        return null;
    }

    public function offsetSet($key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function offsetUnset($key): void
    {
        unset($this->attributes[$key], $this->loaded[$key]);
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /** تعيين علاقة محمّلة مسبقاً (لتجنب استعلامات إضافية) */
    public function setRelation(string $name, $value): void
    {
        $this->loaded[$name] = $value;
    }

    /** get_FIELD_display() مثل Django */
    public function __call($name, $args)
    {
        if (preg_match('/^get_(\w+)_display$/', $name, $m)) {
            $field = $m[1];
            $value = $this->attributes[$field] ?? '';
            return static::$choices[$field][(string) $value] ?? (string) $value;
        }
        throw new BadMethodCallException(static::class . '::' . $name . '() غير موجودة');
    }

    /** النص المعروض للكائن (مثل __str__ في Django) */
    public function __toString(): string
    {
        foreach (['title', 'name', 'username', 'question', 'full_name_arabic', 'application_number', 'director_name'] as $f) {
            if (isset($this->attributes[$f]) && $this->attributes[$f] !== '') {
                return (string) $this->attributes[$f];
            }
        }
        return static::class . ' #' . ($this->attributes['id'] ?? '?');
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    // ------------------------------------------------------------------ الاستعلامات

    public static function table(): string
    {
        return static::$table;
    }

    /** خيارات حقل كقائمة أزواج [[value, label], ...] */
    public static function choices(string $field): array
    {
        $out = [];
        foreach (static::$choices[$field] ?? [] as $value => $label) {
            $out[] = [$value, $label];
        }
        return $out;
    }

    /** خيارات حقل كمصفوفة value => label */
    public static function choiceMap(string $field): array
    {
        return static::$choices[$field] ?? [];
    }

    /** تحويل صفوف إلى كائنات */
    public static function hydrate(array $rows): array
    {
        return array_map(fn($r) => new static($r), $rows);
    }

    /** استعلام SQL كامل يرجع قائمة كائنات */
    public static function query(string $sql, array $params = []): array
    {
        return static::hydrate(DB::all($sql, $params));
    }

    /** استعلام SQL كامل يرجع كائناً واحداً أو null */
    public static function first(string $sql, array $params = []): ?static
    {
        $row = DB::one($sql, $params);
        return $row ? new static($row) : null;
    }

    /** SELECT * FROM table WHERE ... ORDER BY ... LIMIT ... */
    public static function where(string $where = '1', array $params = [], string $orderBy = '', ?int $limit = null, int $offset = 0): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE ' . $where;
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        return static::query($sql, $params);
    }

    public static function firstWhere(string $where, array $params = [], string $orderBy = ''): ?static
    {
        $rows = static::where($where, $params, $orderBy, 1);
        return $rows[0] ?? null;
    }

    public static function all(string $orderBy = ''): array
    {
        return static::where('1', [], $orderBy);
    }

    public static function find($id): ?static
    {
        if ($id === null || $id === '') {
            return null;
        }
        return static::first('SELECT * FROM `' . static::$table . '` WHERE `id` = ?', [(int) $id]);
    }

    /** مثل get_object_or_404 */
    public static function findOrFail($id): static
    {
        $obj = static::find($id);
        if (!$obj) {
            abort(404);
        }
        return $obj;
    }

    public static function count(string $where = '1', array $params = []): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM `' . static::$table . '` WHERE ' . $where, $params);
    }

    public static function exists(string $where, array $params = []): bool
    {
        return (bool) DB::value('SELECT 1 FROM `' . static::$table . '` WHERE ' . $where . ' LIMIT 1', $params);
    }

    /** تقسيم النتائج إلى صفحات (مثل Django Paginator.get_page) */
    public static function paginate(string $where, array $params, string $orderBy, int $perPage, $page = null): Page
    {
        $total = static::count($where, $params);
        $p = new Page([], $total, $perPage, $page ?? ($_GET['page'] ?? 1));
        $p->items = static::where($where, $params, $orderBy, $perPage, ($p->number - 1) * $perPage);
        return $p;
    }

    // ------------------------------------------------------------------ الكتابة

    public static function create(array $data): static
    {
        $obj = new static($data);
        $obj->save();
        return $obj;
    }

    /** يُستدعى قبل الحفظ (للتعديل في الأصناف الفرعية مثل توليد slug) */
    protected function beforeSave(bool $isNew): void
    {
    }

    public function save(): void
    {
        $isNew = empty($this->attributes['id']);
        if ($isNew) {
            foreach (static::$defaults as $k => $v) {
                if (!array_key_exists($k, $this->attributes) || $this->attributes[$k] === null) {
                    $this->attributes[$k] = $v;
                }
            }
            foreach (static::$createdFields as $f) {
                if (empty($this->attributes[$f])) {
                    $this->attributes[$f] = now();
                }
            }
        }
        foreach (static::$updatedFields as $f) {
            $this->attributes[$f] = now();
        }
        $this->beforeSave($isNew);
        $data = $this->attributes;
        unset($data['id']);
        foreach ($data as $k => $v) {
            if (is_bool($v)) {
                $data[$k] = $v ? 1 : 0;
            } elseif (is_array($v) || is_object($v)) {
                unset($data[$k]); // ليست أعمدة حقيقية
            }
        }
        if ($isNew) {
            $this->attributes['id'] = DB::insert(static::$table, $data);
        } else {
            DB::update(static::$table, $data, '`id` = :id', ['id' => (int) $this->attributes['id']]);
        }
    }

    /** تحديث أعمدة محددة فقط */
    public function update(array $data): void
    {
        foreach ($data as $k => $v) {
            $this->attributes[$k] = $v;
            if (is_bool($v)) {
                $data[$k] = $v ? 1 : 0;
            }
        }
        if (!empty($this->attributes['id'])) {
            DB::update(static::$table, $data, '`id` = :id', ['id' => (int) $this->attributes['id']]);
        }
    }

    public function delete(): void
    {
        if (!empty($this->attributes['id'])) {
            DB::delete(static::$table, '`id` = ?', [(int) $this->attributes['id']]);
        }
    }
}
