<?php
/**
 * صفحة من نتائج مقسّمة (بديل Django Paginator / Page).
 * يمكن المرور عليها بـ foreach وقراءة $page['number'] و $page['paginator']['num_pages'].
 */
class Page implements ArrayAccess, IteratorAggregate, Countable
{
    public array $items;
    public int $total;
    public int $perPage;
    public int $number;
    public int $numPages;

    public function __construct(array $items, int $total, int $perPage, $page = 1)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->numPages = max(1, (int) ceil($total / $this->perPage));
        // مثل get_page: رقم غير صالح => الصفحة الأولى، رقم كبير => الأخيرة
        $n = filter_var($page, FILTER_VALIDATE_INT);
        if ($n === false || $n < 1) {
            $n = 1;
        }
        $this->number = min($n, $this->numPages);
    }

    /** إنشاء صفحة من مصفوفة كاملة */
    public static function fromArray(array $all, int $perPage, $page = null): self
    {
        $p = new self([], count($all), $perPage, $page ?? ($_GET['page'] ?? 1));
        $p->items = array_slice(array_values($all), ($p->number - 1) * $p->perPage, $p->perPage);
        return $p;
    }

    public function has_next(): bool
    {
        return $this->number < $this->numPages;
    }

    public function has_previous(): bool
    {
        return $this->number > 1;
    }

    public function has_other_pages(): bool
    {
        return $this->has_next() || $this->has_previous();
    }

    public function next_page_number(): int
    {
        return $this->number + 1;
    }

    public function previous_page_number(): int
    {
        return $this->number - 1;
    }

    public function page_range(): array
    {
        return range(1, $this->numPages);
    }

    public function start_index(): int
    {
        return $this->total === 0 ? 0 : ($this->number - 1) * $this->perPage + 1;
    }

    public function end_index(): int
    {
        return min($this->number * $this->perPage, $this->total);
    }

    /** رابط صفحة مع الإبقاء على باقي معاملات GET (البحث، الفلاتر...) */
    public function url(int $n): string
    {
        $q = $_GET;
        $q['page'] = $n;
        return '?' . http_build_query($q);
    }

    public function isEmpty(): bool
    {
        return !$this->items;
    }

    // ---------------------------------------------------------------- interfaces

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists($key): bool
    {
        return $this->offsetGet($key) !== null;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        switch ($key) {
            case 'number':
                return $this->number;
            case 'paginator':
                return ['num_pages' => $this->numPages, 'count' => $this->total, 'page_range' => $this->page_range(), 'per_page' => $this->perPage];
            case 'object_list':
                return $this->items;
        }
        return $this->items[$key] ?? null;
    }

    public function offsetSet($key, $value): void
    {
    }

    public function offsetUnset($key): void
    {
    }
}
